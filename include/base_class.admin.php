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
         * The expected checkboxes on each admin tab.
         *
         * @var array
         */
        protected $admin_checkboxes = array();

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
         * Add your hooks and filters in the class that extends this base class.
         * Then call parent::add_hooks_and_filters();
         *
         * Should include WordPress and SLP specific hooks and filters.
         */
        function add_hooks_and_filters() {
	        add_action( 'admin_enqueue_scripts' , array( $this , 'enqueue_admin_css' ) );

            add_action('slp_save_ux_settings' ,array( $this ,'save_ux_settings' ) );
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
	                        $this->addon->get_meta('Version'),
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
	     * If the file admin.css exists and the page prefix starts with slp_ , enqueue the admin style.
	     */
	    function enqueue_admin_css( $hook ) {
		    if (
		        file_exists( $this->addon->dir . 'css/admin.css' ) &&
		        ( strpos( $hook , SLP_ADMIN_PAGEPRE ) !== false )
		    ) {
			    wp_enqueue_style( $this->addon->slug . '_admin_css' , $this->addon->url . '/css/admin.css' );
		    }
	    }

        /**
         * Save settings from the UX tab.
         *
         * Set $this->admin_checkboxes with all the expected checkbox names then call parent::save_ux_settings.
         */
        function save_ux_settings() {
            array_walk( $_POST ,array( $this ,'set_ValidOptions' ) );

            $this->options =
                $this->slplus->AdminUI->save_SerializedOption(
                    $this->addon->option_name,
                    $this->addon->options,
                    $this->admin_checkboxes
                );

            $this->addon->init_options();
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
         * Set valid options according to the addon options array.
         *
         * @param $val
         * @param $key
         */
        function set_ValidOptions($val,$key) {
            $simpleKey = str_replace($this->slplus->prefix.'-','',$key);
            if (array_key_exists($simpleKey, $this->addon->options)) {
                $this->addon->options[$simpleKey] = stripslashes_deep($val);
            }
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