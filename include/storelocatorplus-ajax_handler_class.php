<?php

/**
 * The Store Locator Plus Ajax Handler Class
 *
 * Manage the AJAX calls that come in from our admin and frontend UI.
 * Currently only holds new AJAX calls, all calls need to go in here.
 * 
 */

if (! class_exists('SLPlus_AjaxHandler')) {
    class SLPlus_AjaxHandler {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        public $parent = null;

        /*************************************
         * The Constructor
         */
        function __construct($params=null) {
        }

        /**
         * Set the parent property to point to the primary plugin object.
         *
         * Returns false if we can't get to the main plugin object.
         *
         * @global wpCSL_plugin__slplus $slplus_plugin
         * @return type boolean true if plugin property is valid
         */
        function setParent() {
            if (!isset($this->parent) || ($this->parent == null)) {
                global $slplus_plugin;
                $this->parent = $slplus_plugin;
            }
            return (isset($this->parent) && ($this->parent != null));
        }

        /**
         * Remove the Store Pages license.
         */
        function license_reset_pages() {
            if (!$this->setParent()) { die(__('Store Pages license could not be removed.',SLPLUS_PREFIX)); }

            global $wpdb;

            foreach (array(
                        SLPLUS_PREFIX.'-SLP-PAGES-isenabled',
                        SLPLUS_PREFIX.'-SLP-PAGES-last_lookup',
                        SLPLUS_PREFIX.'-SLP-PAGES-latest-version',
                        SLPLUS_PREFIX.'-SLP-PAGES-latest-version-numeric',
                        SLPLUS_PREFIX.'-SLP-PAGES-lk',
                        SLPLUS_PREFIX.'-SLP-PAGES-version',
                        SLPLUS_PREFIX.'-SLP-PAGES-version-numeric',

                        SLPLUS_PREFIX.'-SLPLUS-PAGES-isenabled',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-last_lookup',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-latest-version',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-latest-version-numeric',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-lk',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-version',
                        SLPLUS_PREFIX.'-SLPLUS-PAGES-version-numeric',
                        )
                    as $optionName) {
                $query = 'DELETE FROM '.$wpdb->prefix."options WHERE option_name='$optionName'";
                $wpdb->query($query);
            }
            
            die(__('Store Pages license has been removed. Refresh the General Settings page.', SLPLUS_PREFIX));
        }

        /**
         * Remove the Pro Pack license.
         */
        function license_reset_propack() {
            if (!$this->setParent()) { die(__('Pro Pack license could not be removed.',SLPLUS_PREFIX)); }

            global $wpdb;

            foreach (array(
                        SLPLUS_PREFIX.'-SLPLUS-PRO-isenabled',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-last_lookup',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-latest-version',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-latest-version-numeric',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-lk',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-version',
                        SLPLUS_PREFIX.'-SLPLUS-PRO-version-numeric',
                        )
                    as $optionName) {
                $query = 'DELETE FROM '.$wpdb->prefix."options WHERE option_name='$optionName'";
                $wpdb->query($query);
            }
            
            die(__('Pro Pack license has been removed. Refresh the General Settings page.', SLPLUS_PREFIX));
        }

	}
}
