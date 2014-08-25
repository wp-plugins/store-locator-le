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
            
            $this->do_ajax_startup();
        }

        /**
         * Things we want our add on packs to do when they start in AJAX mode.
         */
        function do_ajax_startup() {
            
        }
    }
}