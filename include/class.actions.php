<?php

/**
 * Store Locator Plus action hooks.
 *
 * The methods in here are normally called from an action hook that is
 * called via the WordPress action stack.
 *
 * @package StoreLocatorPlus\Actions
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2014 Charleston Software Associates, LLC
 */
class SLPlus_Actions {

    //----------------------------------
    // Properties
    //----------------------------------

    /**
     * Has the getCompoundOption deprecation notice been shown already?
     *
     * @var boolean $depnotice_getCompoundOption
     */
    private  $depnotice_getCompoundOption = false;

    /**
     * The SLPlus plugin object.
     *
     * @var SLPlus $plugin
     */
    private $slplus = null;

    /**
     * True if admin init already run.
     * 
     * @var boolean
     */
    public $initialized = false;

    //----------------------------------
    // Methods
    //----------------------------------

    function __construct( $params = null ) {

        // Do the setting override or initial settings.
        //
        if ($params != null) {
            foreach ($params as $name => $sl_value) {
                $this->$name = $sl_value;
            }
        }

        add_action( "load-post.php"     , array( $this, 'action_AddToPageHelp' ), 20 );
        add_action( "load-post-new.php" , array( $this, 'action_AddToPageHelp' ), 20 );
    }

    /**
     * Attach and instantiated AdminUI object to the main plugin object.
     *
     * @return boolean - true unless the main plugin is not found
     */
    function attachAdminUI() {
        if (!isset($this->slplus->AdminUI) || !is_object($this->slplus->AdminUI)) {
            require_once(SLPLUS_PLUGINDIR . '/include/class.adminui.php');
            $this->slplus->AdminUI = new SLPlus_AdminUI();     // Lets invoke this and make it an object
        }
        return true;
    }

	/**
     * Attach and instantiated AdminWPML object to the main plugin object.
     *
     * @return boolean - true unless the main plugin is not found
     */
    function attachAdminWPML() {
        if (!isset($this->slplus->AdminWPML) || !is_object($this->slplus->AdminWPML)) {
            require_once(SLPLUS_PLUGINDIR . '/include/class.adminwpml.php');
            $this->slplus->AdminWPML = new SLPlus_AdminWMPL();     // Lets invoke this and make it an object
        }
        return true;
	}

    /**
     * method: admin_init()
     *
     * Called when the WordPress admin_init action is processed.
     *
     * Builds the interface elements used by WPCSL-generic for the admin interface.
     *
     */
    function admin_init() {

        // Already been here?  Get out.
        if ($this->initialized)  { return; }            
        $this->initialized = true;

        // Update system hook
        // Premium add-ons can use the admin_init hook to utilize this.
        //
        require_once(SLPLUS_PLUGINDIR . '/include/class.updates.php');

        // Activation Helpers
        // Updates are handled via WPCSL via namespace style call
        //
        require_once(SLPLUS_PLUGINDIR . '/include/class.activation.php');
        $this->slplus->Activate = new SLPlus_Activate();
        register_activation_hook( __FILE__, array($this->slplus->Activate,'update')); // WP built-in activation call

        // If we are on an SLP controlled admin page
        //
        if ($this->slplus->check_isOurAdminPage()) {

            // Update the broadcast URL with the registered plugins
            // registered plugins are expected to tell us they are here using
            // slp_init_complete
            //
            $this->slplus->broadcast_url = $this->slplus->broadcast_url . '&' . $this->slplus->AdminUI->create_addon_query();
            $this->slplus->settings->broadcast_url = $this->slplus->broadcast_url;

            // Admin UI Helpers
            //
            $this->attachAdminUI();
            add_action('admin_enqueue_scripts',array($this->slplus->AdminUI,'enqueue_admin_stylesheet'));
            $this->slplus->AdminUI->build_basic_admin_settings();

            // Admin WPML Helper
            // 
            $this->attachAdminWPML();
            $this->slplus->AdminWPML->setParent();

            // Action hook for 3rd party plugins
            //
            do_action('slp_admin_init_complete');
        }
    }

    /**
     * Add content tab help to the post and post-new pages.
     */
    function action_AddToPageHelp() {
        get_current_screen()->add_help_tab(
            array(
                'id' => 'slp_help_tab',
                'title' => __('SLP Hints','csa-slplus'),
                'content' => 
                    '<p>'.
                    sprintf(
                        __('Store Locator Plus documentation can be found online at <a href="%s" target="csa">%s</a>.<br/>','csa-slplus'),
                        'http://www.StoreLocatorPlus.com/support/documentation/store-locator-plus/',
                        'StoreLocatorPlus.com/support/documentation/'
                        ).
                    sprintf(
                        __('View the <a href="%s" target="csa">[slplus] shortcode documentation</a>.','csa-slplus'),
                        'http://www.StoreLocatorPlus.com/support/documentation/store-locator-plus/shortcodes/'
                        ).
                    '</p>'

            )
        );
    }

    /**
     * Add the Store Locator panel to the admin sidebar.
     *
     */
    function admin_menu() {

        if (current_user_can('manage_slp')) {
            $this->attachAdminUI();
            do_action('slp_admin_menu_starting');

            // The main hook for the menu
            //
            add_menu_page(
                $this->slplus->name,
                $this->slplus->name,
                'manage_slp',
                $this->slplus->prefix,
                array($this->slplus->AdminUI,'renderPage_GeneralSettings'),
                SLPLUS_PLUGINURL . '/images/icon_from_jpg_16x16.png'
                );

            // Default menu items
            //
            $force_load_indicator = $this->slplus->javascript_is_forced ? '*' : '';            
            $menuItems = array(
                array(
                    'label'             => __('Info','csa-slplus'),
                    'slug'              => 'slp_info',
                    'class'             => $this->slplus->AdminUI,
                    'function'          => 'renderPage_Info'
                ),
                array(
                    'label'             => __('Locations','csa-slplus'),
                    'slug'              => 'slp_manage_locations',
                    'class'             => $this->slplus->AdminUI,
                    'function'          => 'renderPage_Locations'
                ),
                array(
                    'label'             => __('User Experience','csa-slplus'),
                    'slug'              => 'slp_map_settings',
                    'class'             => $this->slplus->AdminUI,
                    'function'          => 'renderPage_MapSettings'
                ),
                array(
                    'label'             => __('General Settings','csa-slplus') . $force_load_indicator,
                    'slug'              => 'slp_general_settings',
                    'class'             => $this->slplus->AdminUI,
                    'function'          => 'renderPage_GeneralSettings'
                ),
            );

            // Third party plugin add-ons
            //
            $menuItems = apply_filters('slp_menu_items', $menuItems);

            // Attach Menu Items To Sidebar and Top Nav
            //
            foreach ($menuItems as $menuItem) {

                // Sidebar connect...
                //
				// Differentiate capability for User Managed Locations
				if ($menuItem['label'] == __('Locations','csa-slplus')) {
					$slpCapability = 'manage_slp_user';
				} else {
					$slpCapability = 'manage_slp_admin';
				}

                // Using class names (or objects)
                //
                if (isset($menuItem['class'])) {
                    add_submenu_page(
                        $this->slplus->prefix,
                        $menuItem['label'],
                        $menuItem['label'],
						$slpCapability,
                        $menuItem['slug'],
                        array($menuItem['class'],$menuItem['function'])
                        );

                // Full URL or plain function name
                //
                } else {
                    add_submenu_page(
                        $this->slplus->prefix,
                        $menuItem['label'],
                        $menuItem['label'],
						$slpCapability,
                        $menuItem['url']
                        );
                }
            }

            // Remove the duplicate menu entry
            //
            remove_submenu_page($this->slplus->prefix, $this->slplus->prefix);

            $this->slplus->debugMP('slp.main','msg','SLP admin_menu() action complete.');
        }
    }


    /**
     * Create a Map Settings Debug My Plugin panel.
     *
     * @return null
     */
    function create_DMPPanels() {
        if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
        if (class_exists('DMPPanelSLPMain') == false) {
            require_once(SLPLUS_PLUGINDIR.'include/class.dmppanels.php');
        }
        $GLOBALS['DebugMyPlugin']->panels['slp.main']           = new DMPPanelSLPMain();
        $GLOBALS['DebugMyPlugin']->panels['slp.location']       = new DMPPanelSLPMapLocation();
        $GLOBALS['DebugMyPlugin']->panels['slp.mapsettings']    = new DMPPanelSLPMapSettings();
        $GLOBALS['DebugMyPlugin']->panels['slp.managelocs']     = new DMPPanelSLPManageLocations();
    }

    /**
     * Create the SLPlus Extensions Object based on the 4.2 Add On Framework
     *
     * This is a variation of "eat our own cooking", using the add-on framework to augment basic
     * SLP functionality within the base plugin itself.
     *
     */
    function create_object_extensions() {
        if ( !isset( $this->slplus->extension ) ) {
            if( !function_exists( 'get_plugin_data' ) )
                include_once( ABSPATH.'wp-admin/includes/plugin.php');

            require_once( SLPLUS_PLUGINDIR . 'include/class.slplus.extension.php');
            $this->slplus->extension = new SLP_Extension(
                array(
                    'version'               => SLPLUS_VERSION                               ,
                    'min_slp_version'       => SLPLUS_VERSION                               ,

                    'name'                  => __('SLP Base Extensions', 'csa-slplus')      ,
                    'option_name'           => SLPLUS_PREFIX . '-options'                   ,
                    'slug'                  => SLPLUS_BASENAME                              ,
                    'metadata'              => get_plugin_data( $this->slplus->fqfile , false, false)      ,

                    'url'                   => SLPLUS_PLUGINURL                             ,
                    'dir'                   => SLPLUS_PLUGINDIR                             ,

                    'ajax_class_name'        => 'SLP_AJAX'                                  ,
                )
            );
        }
    }

    /**
     * Called when the WordPress init action is processed.
     */
    function init() {
        add_filter('codestyling_localization_excludedirs',array($this,'filter_CodeStylingSkipTheseDirs'));
        load_plugin_textdomain('csa-slplus', false, plugin_basename(dirname(SLPLUS_PLUGINDIR.'store-locator-le.php')) . '/languages');

        // Fire the SLP init starting trigger
        //
        do_action('slp_init_starting', $this);

        // Do not texturize our shortcodes
        //
        add_filter('no_texturize_shortcodes',array('SLPlus_UI','no_texturize_shortcodes'));

        /**
         * Register the store taxonomy & page type.
         *
         * This is used in multiple add-on packs.
         *
         */
        if (!taxonomy_exists('stores')) {
            // Store Page Labels
            //
            $storepage_labels =
                apply_filters(
                    'slp_storepage_labels',
                    array(
                        'name'              => __( 'Store Pages','csa-slplus' ),
                        'singular_name'     => __( 'Store Page', 'csa-slplus' ),
                        'add_new'           => __('Add New Store Page', 'csa-slplus'),
                    )
                );

            $storepage_features =
                apply_filters(
                    'slp_storepage_features',
                    array(
                        'title',
                        'editor',
                        'author',
                        'excerpt',
                        'trackback',
                        'thumbnail',
                        'comments',
                        'revisions',
                        'custom-fields',
                        'page-attributes',
                        'post-formats'
                    )
                );

            $storepage_attributes =
                apply_filters(
                    'slp_storepage_attributes',
                    array(
                        'labels'            => $storepage_labels,
                        'public'            => false,
                        'has_archive'       => true,
                        'description'       => __('Store Locator Plus location pages.','csa-slplus'),
                        'menu_postion'      => 20,
                        'menu_icon'         => SLPLUS_PLUGINURL . '/images/icon_from_jpg_16x16.png',
                        'show_in_menu'      => current_user_can('manage_slp_admin'),
                        'capability_type'   => 'page',
                        'supports'          => $storepage_features,
                    )
                );

            // Register Store Pages Custom Type
            register_post_type(SLPlus::locationPostType,$storepage_attributes);

            register_taxonomy(
                SLPLus::locationTaxonomy,
                SLPLus::locationPostType,
                    array (
                        'hierarchical'  => true,
                        'labels'        =>
                            array(
                                    'menu_name' => __('Categories','csa-slplus'),
                                    'name'      => __('Store Categories','csa-slplus'),
                                 ) ,
                        'capabilities'  =>
                            array (
                                'manage_terms'  => 'manage_slp_admin',
                                'edit_terms'    => 'manage_slp_admin',
                                'delete_terms'  => 'manage_slp_admin',
                                'assign_terms'  => 'manage_slp_admin',
                            )
                        )
                );
        }

        // Fire the SLP initialized trigger
        //
        add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_scripts'      ) );

        // Attach SLP Extensions via the SLP 4.2 Add On Framework
        //
        $this->create_object_extensions();

        // HOOK: slp_init_complete
        // gets a copy of the slplus actions object as a parameter
        //
        do_action('slp_init_complete', $this);
    }

    /**
     * Tell CodeStyling Localization to skip these directories...
     *
     * @param string[] $dirs
     */
    function filter_CodeStylingSkipTheseDirs($dirs) {
        if ($_POST['textdomain'] !== 'csa-slplus') { return dirs; }
        return array_merge(
                $dirs,
                array(
                    SLPLUS_PLUGINDIR . '.git',
                    SLPLUS_PLUGINDIR . 'css',
                    SLPLUS_PLUGINDIR . 'languages',
                    SLPLUS_PLUGINDIR . 'nbproject',
                    SLPLUS_PLUGINDIR . 'images',
                    SLPLUS_PLUGINDIR . 'WPCSL-generic/.git',
                    SLPLUS_PLUGINDIR . 'WPCSL-generic/base',
                    SLPLUS_PLUGINDIR . 'WPCSL-generic/build',
                )
            );
    }

    /**
     * This is called whenever the WordPress wp_enqueue_scripts action is called.
     */
    function wp_enqueue_scripts() {
        $this->slplus->debugMP('slp.main','msg','SLPlus_Actions:'.__FUNCTION__);                
        
        $this->slplus->debugMP('slp.main','msg','', ( $this->slplus->javascript_is_forced ? 'force load' : 'late loading' ) );

        //------------------------
        // Register our scripts for later enqueue when needed
        //
        if ( ! $this->slplus->is_CheckTrue( get_option(SLPLUS_PREFIX.'-no_google_js','0')  ) ) {

            // Google Maps API for Work client ID
            //
            $client_id =
                ! empty ( $this->slplus->options_nojs['google_client_id'] )           ?
                '&client=' . $this->slplus->options_nojs['google_client_id'] . '&v=3' :
                ''                                                                    ;

            // Set the map language
            //
            $language = '&language='.$this->slplus->helper->getData('map_language','get_item',null,'en');

            // Base Google API URL
            //
            $google_api_url =
                'http' . ( is_ssl() ? 's' : '' ) . '://'    .
                $this->slplus->options['map_domain']        .
                '/maps/api/'                                .
                'js'                                        .
                '?sensor=false'                             ;

            // Enqueue the script
            //
            wp_enqueue_script(
                    'google_maps',
                    $google_api_url . $client_id . $language,
                    array(),
                    SLPLUS_VERSION,
                    ! $this->slplus->javascript_is_forced
                    );
        }

        $sslURL =
            (is_ssl()?
            preg_replace('/http:/','https:',SLPLUS_PLUGINURL) :
            SLPLUS_PLUGINURL
            );
        
        
        // Force load?  Enqueue and localize.
        //
        if ( $this->slplus->javascript_is_forced ) {
            wp_enqueue_script(
                    'csl_script',
                    $sslURL.'/js/slp.js',
                    array('jquery'),
                    SLPLUS_VERSION,
                    ! $this->slplus->javascript_is_forced
            );
            $this->slplus->UI->localizeSLPScript();        
            $this->slplus->UI->setup_stylesheet_for_slplus();
            
        // No force load?  Register only.
        // Localize happens when rendering a shortcode.
        //
        } else {
            wp_register_script(
                    'csl_script',
                    $sslURL.'/js/slp.js',
                    array('jquery'),
                    SLPLUS_VERSION,
                    ! $this->slplus->javascript_is_forced
            );           
        }
    }     


    /**
     * This is called whenever the WordPress shutdown action is called.
     */
    function wp_footer() {
        SLPlus_Actions::ManageTheScripts();
    }


    /**
     * Called when the <head> tags are rendered.
     */
    function wp_head() {
        if (!isset($this->slplus)               ) { return; }
        if (!isset($this->slplus->settings)     ) { return; }
        if (!is_object($this->slplus->settings) ) { return; }
        $this->slplus->loadPluginData();

        echo '<!-- SLP Custom CSS -->'."\n".'<style type="text/css">'."\n" .

                    // Map
                    "div#sl_div div#map {\n".
                        "width:{$this->slplus->data['sl_map_width']}{$this->slplus->data['sl_map_width_units']};\n" .
                        "height:{$this->slplus->data['sl_map_height']}{$this->slplus->data['sl_map_height_units']};\n" .
                    "}\n" .

                    // Tagline
                    "div#sl_div div#slp_tagline {\n".
                        "width:{$this->slplus->data['sl_map_width']}{$this->slplus->data['sl_map_width_units']};\n" .
                    "}\n" .

                    // FILTER: slp_ui_headers
                    //
                    apply_filters('slp_ui_headers','') .

             '</style>'."\n\n";
    }

    /**
     * This is called whenever the WordPress shutdown action is called.
     */
    function shutdown() {
        // Safety for themes not using wp_footer
        SLPlus_Actions::ManageTheScripts();
    }

    /**
     * Unload The SLP Scripts If No Shortcode
     */
    function ManageTheScripts() {
        if (!defined('SLPLUS_SCRIPTS_MANAGED') || !SLPLUS_SCRIPTS_MANAGED) {

            // If no shortcode rendered, remove scripts
            //
            if (!defined('SLPLUS_SHORTCODE_RENDERED') || !SLPLUS_SHORTCODE_RENDERED) {
                wp_dequeue_script('google_maps');
                wp_deregister_script('google_maps');
                wp_dequeue_script('csl_script');
                wp_deregister_script('csl_script');
            }
            define('SLPLUS_SCRIPTS_MANAGED',true);
        }
    }


     //------------------------------------------------------------------------
     // DEPRECATED
     //------------------------------------------------------------------------

     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      */
     function getCompoundOption() {
        if (!$this->depnotice_getCompoundOption) {
            $this->slplus->notifications->add_notice(9,$this->slplus->createstring_Deprecated(__FUNCTION__));
            $this->slplus->notifications->display();
            $this->depnotice_getCompoundOption = true;
        }
     }

}

// These dogs are loaded up way before this class is instantiated.
//
add_action("load-post",array('SLPlus_Actions','init'));
add_action("load-post-new",array('SLPlus_Actions','init'));

