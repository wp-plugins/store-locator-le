<?php
if (! class_exists('SLP_BaseClass_Activation')) {

    /**
     * A base class that helps add-on packs separate activation functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file during activation.
     *
     * @package StoreLocatorPlus\BaseClass\Activation
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_Activation {

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
        }

        /**
         * Do this whenever the activation class is instantiated.
         *
         * This is triggered via the update_prior_installs method in the admin class,
         * which is run via update_install_info() in the admin class.
         *
         * update_install_info should be something you put in any add-on pack
         * that is using the base add-on class.  It typically goes inside the
         * do_admin_startup() method which is overridden by the new add on
         * adminui class code.
         *
         */
        function update() {
            // Override this with your extended class.
        }
    }
}