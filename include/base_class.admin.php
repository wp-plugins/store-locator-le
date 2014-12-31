<?php
if (! class_exists('SLP_BaseClass_Admin')) {

    /**
     * A base class that helps add-on packs separate admin functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file in admin mode
     * via the admin_menu call.   Reduces the front-end footprint.
     *
     * @package StoreLocatorPlus\BaseClass\Admin
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 - 2014 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_Admin {

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
            
            $this->set_addon_properties();
            $this->do_admin_startup();
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
         * Check for updates of active add on packs.
         */
        function check_for_updates() {
            if ( is_plugin_active( $this->addon->slug ) ) {
                if ( ! class_exists( 'SLPlus_Updates' ) ) {
                    require_once('class.updates.php');
                }
                if ( class_exists('SLPlus_Updates') ) {
                    $this->Updates = new SLPlus_Updates(
                            $this->addon->metadata['Version'], 
                            $this->slplus->updater_url, 
                            $this->addon->slug
                    );
                }
            }
        }        
        
        /**
         * Things we want our add on packs to do when they start.
         */
        function do_admin_startup() {
            
            // Add your startup methods you want the add on to run here.
            // Some suggestions: 
            // $this->check_for_updates();
            // $this->update_install_info();
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
         * Update the install info for this add on.
         */
        function update_install_info() {
            $installed_version =
                isset( $this->addon->options['installed_version'] ) ?
                    $this->addon->options['installed_version']      :
                    '0.0.0'                                         ;

            if ( version_compare( $installed_version , $this->addon->version , '<' ) ) {
                $this->update_prior_installs();
                $this->addon->options['installed_version'] = $this->addon->version;
                update_option( $this->addon->option_name , $this->addon->options);
            }
        }

        /**
         * Update prior add-on pack installations.
         */
        function update_prior_installs() {
                if ( ! empty ( $this->addon->activation_class_name ) ) {                
                    if ( class_exists( $this->addon->activation_class_name ) == false) {
                        if ( file_exists( $this->addon->dir.'include/class.activation.php' ) ) {
                            require_once($this->addon->dir.'include/class.activation.php');
                            $this->activation = new $this->addon->activation_class_name(array( 'addon' => $this->addon , 'slplus' => $this->slplus ));
                            $this->activation->update();
                        }
                    }
                }
        }
        

        //-------------------------------------
        // Methods : filters
        //-------------------------------------
        
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