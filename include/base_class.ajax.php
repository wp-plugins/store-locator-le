<?php
if (! class_exists('SLP_BaseClass_AJAX')) {

    /**
     * A base class that helps add-on packs separate AJAX functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file in AJAX mode.
     *
     * @package StoreLocatorPlus\BaseClass\AJAX
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_AJAX {

        //-------------------------------------
        // Properties
        //-------------------------------------

        /**
         * This addon pack.
         *
         * @var \SLP_BaseClass_Addon $addon
         */
        protected $addon;

        /**
         * Form data that comes into the AJAX request in the formdata variable.
         *
         * @var mixed[] $formdata
         */
        protected $formdata = array();

        /**
         * The formdata default values.
         *
         * @var mixed[] $formdata_defaults
         */
        protected $formdata_defaults = array();

        /**
         * The base SLPlus object.
         *
         * @var \SLPlus $slplus
         */
        protected $slplus;

	    /**
	     * What AJAX actions are valid for this add on to process?
	     *
	     * Override in the extended class if not serving the default SLP actions:
	     * * csl_ajax_onload
	     * * csl_ajax_search
	     *
	     * @var array
	     */
	    protected $valid_actions = array(
		    'csl_ajax_onload',
		    'csl_ajax_search'
	    );

        //-------------------------------------
        // Methods : activity
        //-------------------------------------

        /**
         * Instantiate the admin panel object.
         *
         * @param mixed[] $params
         */
        function __construct($params) {
            // Set properties based on constructor params,
            // if the property named in the params array is well defined.
            //
            if ($params !== null) {
                foreach ($params as $property=>$value) {
                    if (property_exists($this,$property)) { $this->$property = $value; }
                }
            }
            
            $this->do_ajax_startup();
        }

        /**
         * Things we want our add on packs to do when they start in AJAX mode.
         */
        function do_ajax_startup() {
            
        }

	    /**
	     * Return true if the AJAX action is one we process.
	     */
	    function is_valid_ajax_action() {
		    if ( ! isset( $_REQUEST['action'] ) ) { return false; }

		    foreach ( $this->valid_actions as $valid_ajax_action ) {
			    if ( $_REQUEST['action'] === $valid_ajax_action ) { return true; }
		    }
		    return false;
	    }

        /**
         * Set incoming query and request parameters into object properties.
         */
        function set_QueryParams() {
            if ( isset( $_REQUEST['formdata'] ) ) {
                $this->formdata = wp_parse_args( $_REQUEST['formdata'] ,$this->formdata_defaults);
            }
        }

    }
}