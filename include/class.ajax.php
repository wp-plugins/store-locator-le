<?php
if (! class_exists('SLP_AJAX')) {
    require_once(SLPLUS_PLUGINDIR.'/include/base_class.ajax.php');


    /**
     * Holds the ajax-only code.
     *
     * This allows the main plugin to only include this file in AJAX mode
     * via the slp_init when DOING_AJAX is true.
     *
     * @package StoreLocatorPlus\Extension\AJAX
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 Charleston Software Associates, LLC
     */
    class SLP_AJAX extends SLP_BaseClass_AJAX {

        //-------------------------------------
        // Methods : Base Override
        //-------------------------------------

        /**
         * Things we do to latch onto an AJAX processing environment.
         *
         * Add WordPress and SLP hooks and filters only if in AJAX mode.
         *
         * WP syntax reminder: add_filter( <filter_name> , <function> , <priority> , # of params )
         *
         * Remember: <function> can be a simple function name as a string
         *  - or - array( <object> , 'method_name_as_string' ) for a class method
         * In either case the <function> or <class method> needs to be declared public.
         *
         * @link http://codex.wordpress.org/Function_Reference/add_filter
         *
         */
        public function do_ajax_startup() {

            // Do not add the filters and field matches if we are not in "immediate mode" on
            // an initial AJAX request
            //
            if (! isset( $_REQUEST['action'] ) ) { return; }

            // Augment these AJAX actions...
            //
            switch ( $_REQUEST['action'] ) {

                // onload - immediately show locations
                // search - find locations from UI
                //
                case 'csl_ajax_onload' :
                case 'csl_ajax_search' :
                    $this->add_load_and_search_filters();
                    break;

                // Default - do nothing
                //
                default:
                    break;
            }
        }

        //-------------------------------------
        // Methods : Custom
        //-------------------------------------

        public function add_load_and_search_filters() {
            add_filter( 'slp_results_marker_data' , array( $this ,'modify_email_link') , 10 , 1);
        }

        /**
         * Modify the email link
         *
         * @param mixed[] $marker the current marker data
         * @return mixed[]
         */
        public function modify_email_link( $marker ) {
            $marker['email_link'] = '';

            if ( ! empty( $marker['email'] ) ) {
                $marker['email_link'] =
                    sprintf(
                        '<a href="mailto:%s" target="_blank" id="slp_marker_email" class="storelocatorlink"><nobr>%s</nobr></a>',
                        $marker['email'],
                        $this->slplus->options['label_email']
                    );
            }

            return $marker;
        }

    }
}