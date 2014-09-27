<?php
if (! class_exists('SLP_BaseClass_UI')) {

    /**
     * A base class that helps add-on packs separate UI functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file when NOT in admin mode.
     *
     * @package StoreLocatorPlus\BaseClass\UI
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 - 2014 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_UI {

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
         * The base SLPlus object.
         *
         * @var \SLPlus $slplus
         */
        protected $slplus;

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
            
            $this->do_ui_startup();
            $this->add_hooks_and_filters();
        }

        /**
         * Add the plugin specific hooks and filter configurations here.
         *
         * Should include WordPress and SLP specific hooks and filters.
         */
        function add_hooks_and_filters() {
            // Add your hooks and filters in the class that extends this base class.
        }
        
        /**
         * Things we want our add on packs to do when they start.
         */
        function do_ui_startup() {
            // Add your startup methods you want the add on to run here.
        }
    }
}