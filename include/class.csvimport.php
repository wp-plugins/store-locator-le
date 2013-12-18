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
        private $filehandle;

        //----------------------------------------------------------------------------
        // Properties : Protected (access by defining class, inherited class, parents)
        //----------------------------------------------------------------------------

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
         * The parent object.
         *
         * @var object $parent
         */
        protected $parent;

        /**
         * The main SLP Plugin object.
         *
         * @var SLPlus $plugin
         */
        protected $plugin;

        /**
         *
         * @var mixed[]
         */
        protected $processing_counts;

        /**
         * Skip the first line in the file?
         *
         * @var boolean $skip_firstline
         */
        protected $skip_firstline = false;

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
         * @param object $parent
         */
        function __construct($params) {
            foreach ($params as $property=>$value) {
                if (property_exists($this,$property)) { $this->$property = $value; }
                else { die("import property $property does not exist<br/>\n"); }
            }
            if ($this->firstline_has_fieldname) { $this->skip_firstline = true; }
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
         */
        function process_File() {

            // Is the file name set?  If not, exit.
            //
            if (!isset($_FILES['csvfile']['name']) || empty($_FILES['csvfile']['name'])) {
                print "<div class='updated fade'>".__('Import file name not set.','csa-slp-pro').'</div>';
                return;
            }

            // Does the file have any content?  If not, exit.
            //
            if ($_FILES['csvfile']['size'] <= 0)    {
                print "<div class='updated fade'>".__('Import file was empty.','csa-slp-pro').'</div>';
                return;
            }
            
            // Is the file CSV?  If not, exit.
            //
            $arr_file_type = wp_check_filetype(basename($_FILES['csvfile']['name']));
            if ($arr_file_type['type'] != 'text/csv') {
                print "<div class='updated fade'>".
                    __('Uploaded file needs to be in CSV format.','csa-slp-pro')        .
                    sprintf(__('Type was %s.','csa-slp-pro'),$arr_file_type['type'])    .
                    '</div>';
                return;
            }

            // Can the file be saved to disk?  If not, exit.
            //
            $updir = wp_upload_dir();
            $updir = $updir['basedir'].'/slplus_csv';
            if (!is_dir($updir)) {   mkdir($updir,0755); }
            if (!move_uploaded_file($_FILES['csvfile']['tmp_name'],$updir.'/'.$_FILES['csvfile']['name'])) {
                return;
            }

            // Line Endings
            //
            $adle_setting = ini_get('auto_detect_line_endings');
            ini_set('auto_detect_line_endings', true);

            // Can the file be opened? If not, exit.
            //
            if (($this->filehandle = fopen($updir.'/'.$_FILES['csvfile']['name'], "r")) === FALSE) {
                print "<div class='updated fade'>".
                    __('Could not open CSV file for processing.','csa-slp-pro')         . '<br/>' .
                    $updir.'/'.$_FILES['csvfile']['name']                               .
                    '</div>';
                ini_set('auto_detect_line_endings', $adle_setting);
                return;
            }
            
            // Set first line processing flag.
            //
            $this->first_has_been_skipped = false;

            // Set the field names.
            //
            $this->set_FieldNames();

            // Reset the notification message to get a clean message stack.
            //
            $this->plugin->notifications->delete_all_notices();

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
                'updated'           => 0,
                );

            // FILTER: slp_csv_processing_messages
            // Set the message array to be printed out for the above counters.
            //
            $location_processing_types = apply_filters('slp_csv_processing_messages',array());


            // Turn off notifications for OK addresses.
            //
            $this->plugin->AdminUI->ManageLocations->geocodeSkipOKNotices = true;

            // Loop through all records
            //
            while (($this->data = fgetcsv($this->filehandle)) !== FALSE) {

                // Skip First Line
                //
                if (!$this->first_has_been_skipped && $this->skip_firstline){
                    $this->first_has_been_skipped = true;
                    continue;
                }

                // HOOK: slp_csv_processing
                // Process the CSV data.
                //
                do_action('slp_csv_processing');
                $reccount++;
            }
            fclose($this->filehandle);

            ini_set('auto_detect_line_endings', $adle_setting);

            // Show Notices
            //
            $this->plugin->notifications->display();

            // Tell them how many locations were added
            //
            if ($reccount > 0) {
                print "<div class='updated fade'>".
                        sprintf("%d",$reccount) ." " .
                        __(" processed.",'csa-slp-pro') .
                        '</div>';
            }
            foreach ($this->processing_counts as $count_type=>$count) {
                if ($count > 0) {
                    print "<div class='updated fade'>".sprintf("%d %s",$count,$location_processing_types[$count_type]).'</div>';
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
                        $label = preg_replace('/\W/','_',trim(strtolower($label)));
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
            if (!isset($this->fieldnames)) {

                // FILTER: slp_csv_default_fieldnames
                // Set default field names if the header line does not have field names.
                //
                $this->fieldnames = apply_filters('slp_csv_default_fieldnames',array());
            }
        }
    }
}