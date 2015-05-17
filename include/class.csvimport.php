<?php
if (!defined( 'ABSPATH'     )) { exit;   } // Exit if accessed directly, dang hackers

// Make sure the class is only defined once.
//
if (!class_exists('CSVImport')) {

    /**
     * CSV Import
     *
     * @package StoreLocatorPlus\CSVImport
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 Charleston Software Associates, LLC
     */
    class CSVImport {

        //----------------------------------------------------------------------------
        // Properties : Private (only accessed by defining class)
        //----------------------------------------------------------------------------

        /**
         * The CSV file handle.
         *
         * @var file $filehandle
         */
        protected $filehandle;

        //----------------------------------------------------------------------------
        // Properties : Protected (access by defining class, inherited class, parents)
        //----------------------------------------------------------------------------

        /**
         * The add on.
         *
         * @var mixed $addon
         */
        protected $addon;

        /**
         * Autodetect line endings setting.
         *
         * @var boolean
         */
        protected $adle_setting;

        /**
         * The current CSV data array.
         *
         * @var string[] $data
         */
        protected $data;

        /**
         * List of field names being processed. 
         * 
         * @var string[] $fieldnames 
         */
        protected $fieldnames;

        /**
         * Does the first line contain field names?
         * 
         * @var boolean $firstline_has_fieldname
         */
        protected $firstline_has_fieldname = false;

        /**
         * True if the first line has already been skipped.
         * 
         * @var boolean $first_has_been_skipped
         */
        protected $first_has_been_skipped = false;

        /**
         * What is the maximum data columns allowed for this CSV file?
         *
         * @var int $maxcols
         */
        protected $maxcols;

        /**
         * The message stack for the current import operation.
         *
         * @var \CSVImportMessages
         */
        protected $message_stack;

        /**
         * The parent object.
         *
         * @var object $parent
         */
        protected $parent;

        /**
         * The main SLP Plugin object.
         *
         * @var \SLPlus $plugin
         */
        protected $plugin;

        /**
         *
         * @var mixed[]
         */
        protected $processing_counts;

        /**
         * The processing report.
         *
         * @var string
         */
        public $processing_report = array();

        /**
         * Skip the first line in the file?
         *
         * @var boolean $skip_firstline
         */
        protected $skip_firstline = false;

        /**
         * @var \SLPlus $slplus
         */
        protected $slplus;

        /**
         * The prefix to strip from field name in header row.
         *
         * @var string $strip_prefix
         */
        protected $strip_prefix = '';

        //-------------------------
        // Methods
        //-------------------------

        /**
         * Invoke the CSV Import object using a named array to configure behavior parameters.
         *
         * Parameters:
         * - firstline_has_fieldname <boolean> true if first line has field names for the columns
         * - parent <object> pointer to the invoked add-on object
         * - plugin <object> pointer to the invoked base plugin (\SLPlus) object
         * - skip_firstline <boolean> true if the first line does not have data to process
         * - strip_prefix <string> prefix to strip out of field names if first line has field names
         *
         * Example: 
         * $this->importer = new CSVImport(array('parent'=>$this,'plugin'=>$this->plugin));
         *
         * @param mixed[] $params
         */
        function __construct($params) {
            foreach ($params as $property=>$value) {
                if (property_exists($this,$property)) { $this->$property = $value; }
            }
            if ( isset( $this->plugin ) && ! isset( $this->slplus ) ) { $this->slplus = $this->plugin; }
            if ( isset( $this->parent ) && ! isset( $this->addon  ) ) { $this->addon  = $this->parent; }
            if ($this->firstline_has_fieldname) { $this->skip_firstline = true; }

            // Set execution time limit.
            //
            ini_set( 'max_execution_time' , $this->slplus->options_nojs['php_max_execution_time'] );
            set_time_limit( $this->slplus->options_nojs['php_max_execution_time'] );
        }

        /**
         * Create the bulk upload form using wpCSL settings methods.
         *
         * This should be overriden.
         */
        function create_BulkUploadForm() {
            die( 'function CSVImport::'.__FUNCTION__.' must be over-ridden in a sub-class.' );
        }

        /**
         * Attach a message stack to this import object.
         */
        function initialize_message_stack() {
            if ( ! class_exists( 'CSVImportMessages' ) ) {
                require_once( 'class.csvimport.messages.php' );
            }

            if ( ! isset( $this->message_stack ) ) {
                $this->message_stack =
                    new CSVImportMessages(
                        array(
                            'addon'     => $this        ,
                            'slplus'    => $this->slplus
                        )
                    );
            }
        }

        /**
         * Allows WordPress to process csv file types
         *
         * @param array $existing_mimes
         * @return string
         */
        function filter_AddMimeType ( $existing_mimes=array() ) {
            $existing_mimes['csv'] = 'text/csv';
            return $existing_mimes;
        }

        /**
         * Process a CSV File.
         *
         * This should be extended.
         *
         * HOOK: slp_csv_processing_complete
         *
         */
        function process_File( $file_meta = null ) {
            if ( $file_meta === null ) { $file_meta = $_FILES; }
            $this->process_FileDirect( $file_meta );
            do_action('slp_csv_processing_complete');
        }

        /**
         * Is this a valid CSV File?
         *
         * @param $file_meta
         * @return bool
         */
        function is_valid_csv_file( $file_meta ) {

            // Is the file name set?  If not, exit.
            //
            if (!isset($file_meta['csvfile']['name']) || empty($file_meta['csvfile']['name'])) {
                echo "<div class='updated fade'>".__('Import file name not set.','csa-slplus').'</div>';
                return false;
            }

            // Does the file have any content?  If not, exit.
            //
            if ($file_meta['csvfile']['size'] <= 0)    {
                switch ( $file_meta['csvfile']['error'] ) {
                    case UPLOAD_ERR_INI_SIZE:
                        $message = __( 'Import file exceeds the upload_max_filesize in php.ini.' , 'csa-slplus' );
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        $message = __( 'Import file was only partially loaded.' , 'csa-slplus' );
                        break;

                    case UPLOAD_ERR_NO_FILE:
                        $message = __( 'Import file seems to have gone missing.' , 'csa-slplus' );
                        break;

                    default:
                        $message = __('Import file is empty.','csa-slplus');
                        break;
                }
                echo "<div class='updated fade'>", $message , '</div>';
                return false;
            }

            // Is the file CSV?  If not, exit.
            //
            $arr_file_type = wp_check_filetype( basename( $file_meta['csvfile']['name'] ) , array( 'csv' => 'text/csv' ) );
            if ($arr_file_type['type'] != 'text/csv') {
                echo "<div class='updated fade'>".
                    __('Uploaded file needs to be in CSV format.','csa-slplus')        .
                    sprintf(__('Type was %s.','csa-slplus'),$arr_file_type['type'])    .
                    '</div>';
                return false;
            }

            return true;
        }

        /**
         * Move the CSV File to a local directory.
         *
         * @param $file_meta
         * @return string
         */
        function move_csv_to_slpdir( $file_meta ) {

            // Check WordPress has an uploads directory.
            //
            if ( ! is_dir( SLPLUS_UPLOADDIR ) ) {
                echo "<div class='updated fade'>".
                    sprintf(
                        __('WordPress upload directory %s is missing, check directory permissions.','csa-slplus') ,
                        SLPLUS_UPLOADDIR
                    ) .
                    '</div>';
                return null;
            }

            // Make the SLP CSV Upload Directory \
            //
            $updir = SLPLUS_UPLOADDIR .'csv';
            if ( ! is_dir( $updir ) ) {   mkdir( $updir ,0755 ); }

            $new_file = $updir.'/'.$file_meta['csvfile']['name'];

            // Move File -
            // If csvfile source is set to csv_file_url assume an http or ftp_get
            // direct to disk,
            //
            // otherwise
            //
            // Assume HTTP POST (browser direct) use move_uploaded_file
            //
            if (
                isset( $file_meta['csvfile']['source'] ) &&
                ( $file_meta['csvfile']['source'] === 'direct_url' )
            )  {
                if ( ! rename( $file_meta['csvfile']['tmp_name'] , $new_file ) ) {
                    print "<div class='updated fade'>"                                  .
                        __('Imported CSV file could not be renamed.','csa-slplus')      .
                        '</div>';
                    return '';
                }

            } else {
                if ( ! move_uploaded_file( $file_meta['csvfile']['tmp_name'] , $new_file ) ) {
                    print "<div class='updated fade'>"                                  .
                        __('Uploaded CSV file could not be moved.','csa-slplus')        .
                        '</div>';
                    return '';
                }
            }

            return $new_file;
        }

        /**
         * Open the CSV File and set the filehandle.
         *
         * @return bool
         */
        function open_csv_file( $filename ) {

            // Line Endings
            //
            $this->adle_setting = ini_get('auto_detect_line_endings');
            ini_set('auto_detect_line_endings', true);

            // Can the file be opened? If not, exit.
            //
            if (($this->filehandle = fopen( $filename , "r")) === FALSE) {
                print "<div class='updated fade'>".
                    __('Could not open CSV file for processing.','csa-slplus')         . '<br/>' .
                    $new_file                               .
                    '</div>';
                ini_set('auto_detect_line_endings', $this->adle_setting);
                return false;
            }

            // Set first line processing flag.
            //
            $this->first_has_been_skipped = false;

            return true;
        }

        /**
         * Override this to add special processing that skips the data processing of the file.
         *
         * @return bool
         */
        function ok_to_process_file() {
            return true;
        }

        /**
         * Process a CSV file breaking it into arrays and pass to filters for handling.
         *
         * Hook onto the slp_csv_processing action in your extended class to do something with the array of data.
         *
         * @param $file_meta a $_FILES-like structure.
         */
        function process_FileDirect( $file_meta ) {

            if ( ! $this->is_valid_csv_file( $file_meta ) ) { return; }

            $new_file = $this->move_csv_to_slpdir( $file_meta );
            if ( empty( $new_file ) ) { return; }

            if ( ! $this->open_csv_file( $new_file ) ) { return; }

            $this->processing_report = array();

            // Set the field names.
            //
            $this->set_FieldNames();

            // Reset the notification message to get a clean message stack.
            //
            $this->slplus->notifications->delete_all_notices();

            // Add CSV as a mime type
            //
            add_filter('upload_mimes', array($this,'filter_AddMimeType'));
            $reccount = 0;
            $this->maxcols = count($this->fieldnames);

            $this->processing_counts = array(
                'added'             => 0,
                'exists'            => 0,
                'not_updated'       => 0,
                'skipped'           => 0,
                'malformed'         => 0,
                'updated'           => 0,
                );

            // FILTER: slp_csv_processing_messages
            // Set the message array to be printed out for the above counters.
            //
            $location_processing_types = apply_filters('slp_csv_processing_messages',array());

            // Turn off notifications for OK addresses.
            //
            $this->slplus->currentLocation->geocodeSkipOKNotices = true;
            $this->slplus->currentLocation->validate_fields = true;

            // Loop through all records
            //
            if ( $this->ok_to_process_file() ) {
                while (($this->data = fgetcsv($this->filehandle)) !== FALSE) {

                    // Skip First Line
                    //
                    if (!$this->first_has_been_skipped && $this->skip_firstline) {
                        $this->first_has_been_skipped = true;
                        continue;
                    }

                    // HOOK: slp_csv_processing
                    // Process the CSV data.
                    //
                    do_action('slp_csv_processing');
                    $reccount++;
                }
            }
            fclose($this->filehandle);

            $this->slplus->currentLocation->validate_fields = false;

            ini_set('auto_detect_line_endings', $this->adle_setting);

            // Show Notices
            //
            $this->slplus->notifications->display();

            // Processing Report
            //
            if ($reccount > 0) {
                $this->processing_report[] = sprintf( __( '%d data lines read from the CSV file.' , 'csa-slplus') , $reccount );
            }
            foreach ($this->processing_counts as $count_type=>$count) {
                if ($count > 0) {
                    $this->processing_report[] = sprintf( "%d %s" , $count , $location_processing_types[$count_type] );
                }
            }
            if ( count($this->processing_report) > 0 ) {
                foreach ( $this->processing_report as $message ) {
                    printf('<div class="updated fade">%s</div>', $message);
                }
            }
        }

        /**
         * Set the field names array for the fields being processed.
         *
         */
        function set_FieldNames() {
            
            // Special header processing
            //
            if ($this->skip_firstline && $this->firstline_has_fieldname) {
                if (($headerColumns = fgetcsv($this->filehandle)) !== FALSE) {
                    foreach($headerColumns as $label) {
                        $clean_label = trim(sanitize_key($label));
                        $label = preg_replace('/\W/','_',$clean_label);
                        if (!empty($this->strip_prefix)) {
                            if (preg_match('/^'.$this->strip_prefix.'/',$label)!==1) { $label = $this->strip_prefix.$label; }
                        }
                        $this->fieldnames[] = $label;
                    }
                    $this->first_has_been_skipped = true;
                }
                
                // FILTER: slp_csv_fieldnames
                // Modify the field names read in via the CSV header line.
                //
                $this->fieldnames = apply_filters('slp_csv_fieldnames',$this->fieldnames);
            }

            // Set the default
            //
            if ( ! isset( $this->fieldnames ) ) {

                // FILTER: slp_csv_default_fieldnames
                // Set default field names if the header line does not have field names.
                //
                $this->fieldnames = apply_filters( 'slp_csv_default_fieldnames' , array() );
            }
        }
    }
}