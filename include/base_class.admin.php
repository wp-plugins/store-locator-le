<?php
if (! class_exists('SLP_BaseClass_Admin')) {

    /**
     * A base class that helps add-on packs separate admin functionalty.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file in admin mode
     * via the admin_menu call.   Reduces the front-end footprint.
     *
     * @package StoreLocatorPlus\BaseClass\Admin
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_Admin {

        //-------------------------------------
        // Properties
        //-------------------------------------

        /**
         * This addon pack.
         *
         * @var mixed $addon
         */
        protected $addon;

        /**
         * The slug for the admin page.
         *
         * @var string $admin_page_slug
         */
        protected $admin_page_slug;

        /**
         * The base SLPlus object.
         *
         * @var \SLPlus $slplus
         */
        protected $slplus;

        //-------------------------------------
        // Methods
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
            $this->set_addon_properties();
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
         * Set base class properties so we can have more cross-add-on methods.
         */
        function set_addon_properties() {
            // Replace this with the properties from the parent add-on to set this class properties.
            //
            // $this->admin_page_slug = <class>::ADMIN_PAGE_SLUG
        }

        /**
         * Add our admin pages to the valid admin page slugs.
         *
         * @param string[] $slugs admin page slugs
         * @return string[] modified list of admin page slugs
         */
        function filter_AddOurAdminSlug($slugs) {
            return array_merge($slugs,
                    array(
                        $this->admin_page_slug,
                        SLP_ADMIN_PAGEPRE.$this->admin_page_slug,
                        )
                    );
        }
    }
}