<?php
if (!defined( 'ABSPATH'     )) { exit;   } // Exit if accessed directly, dang hackers

// Make sure the class is only defined once.
//
if (!class_exists('CSVImportMessages')) {

    /**
     * CSV ImportMessages
     *
     * @package StoreLocatorPlus\CSVImport\Messages
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 Charleston Software Associates, LLC
     */
    class CSVImportMessages {

        //----------------------------------------------------------------------------
        // Properties : Private (only accessed by defining class)
        //----------------------------------------------------------------------------

        /**
         * The messages stack.
         *
         * @var string[]
         */
        private $messages = array();

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
         * @var \SLPlus $slplus
         */
        protected $slplus;

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
        function __construct( $params ) {
            foreach ( $params as $property => $value ) {
                if ( property_exists( $this , $property ) ) { $this->$property = $value; }
            }
            $this->set_messages();
        }

        /**
         * Add a message to the queue.
         *
         * @param $message
         */
        function add_message( $message ) {
            if ( ! empty( $message ) ) {
                $this->messages[] = $message;
            }
        }

        /**
         * Clear the messages from memory and persistent storage.
         */
        function clear_messages() {
            update_option( 'slp-import-messages' , array() );
            $this->messages = array();
        }

        /*
         * Get the messages back in a formatted HTML div block.
         *
         * @return string HTML including message text.
         */
        function get_message_string() {
            $message_string = '';

            foreach ( $this->messages as $message ) {
                $message_string .=
                    sprintf( '<div class="import_message">%s</div>' ,
                        $message
                    );
            }

            if ( ! empty ( $message_string ) ) {
                $message_string = sprintf( '<div class="import_message_block">%s</div>' , $message_string );
            }

            return $message_string;
        }

        /**
         * Set the message stack, fetching from persistent storage.
         */
        function set_messages() {
            $this->messages = get_option( 'slp-import-messages' , array() );
        }

        /**
         * Save the messages in persistent storage.
         */
        function save_messages() {
            if ( count( $this->messages ) > 0 ) {
                update_option('slp-import-messages', $this->messages);
            }
        }
    }
}