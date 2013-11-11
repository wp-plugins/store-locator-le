<?php
/**
 * Store Locator Plus General Settings Interface
 *
 * @package StoreLocatorPlus\AdminUI\GeneralSettings
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 Charleston Software Associates, LLC
 */
class SLPlus_AdminUI_GeneralSettings {

    //-----------------------------
    // Properties
    //-----------------------------

    /**
     * The SLPlus plugin object.
     *
     * @var \SLPlus $plugin
     */
    private $plugin;

    /**
     * Our wpCSL settings object.
     * 
     * @var wpCSL_settings__slplus $settings
     */
    public $settings = null;

    /**
     * The page slug.
     * 
     * @var string $slug
     */
    private $slug = 'slp_general_settings';


    //-----------------------------
    // Methods
    //-----------------------------

    /**
     * Called when this object is created.
     *
     */
    function __construct() {
        if (!$this->set_Plugin()) {
            die('could not set plugin');
            return;
        }
    }

    /**
     * Set the plugin property to point to the primary plugin object.
     *
     * Returns false if we can't get to the main plugin object.
     *
     * @global SLPlus the wpCSL object
     * @return boolean true if plugin property is valid
     */
    function set_Plugin() {
        if (!isset($this->plugin) || ($this->plugin == null)) {
            global $slplus_plugin;
            $this->plugin = $slplus_plugin;
        }
        return (isset($this->plugin) && ($this->plugin != null));
    }

    /**
     * Execute the save settings action.
     *
     */
    function save_Settings() {
        do_action('slp_save_generalsettings');

        // Standard Input Saves
        // FILTER: slp_save_general_settings_inputs
        //
        $BoxesToHit =
            apply_filters('slp_save_general_settings_inputs',
                array(
                    SLPLUS_PREFIX.'-api_key'            ,
                    SLPLUS_PREFIX.'-geocode_retries'    ,
                )
            );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SavePostToOptionsTable($JustAnotherBox);
        }

        // Checkboxes
        // FILTER: slp_save_general_settings_checkboxes
        //
        $BoxesToHit =
            apply_filters('slp_save_general_settings_checkboxes',
                array(
                    SLPLUS_PREFIX.'-force_load_js'              ,
                    SLPLUS_PREFIX.'-no_google_js'               ,
                    SLPLUS_PREFIX.'-thisbox'                    ,
                    )
                );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SaveCheckBoxToDB($JustAnotherBox, '','');
        }

        // Serialized Options Setting for stuff going into slp.js.
        // This should be used for ALL new JavaScript options.
        //
        array_walk($_REQUEST,array($this->plugin,'set_ValidOptions'));
        update_option(SLPLUS_PREFIX.'-options', $this->plugin->options);

        // Serialized Options Setting for stuff NOT going to slp.js.
        // This should be used for ALL new options not going to slp.js.
        //
        array_walk($_REQUEST,array($this->plugin,'set_ValidOptionsNoJS'));
        update_option(SLPLUS_PREFIX.'-options_nojs', $this->plugin->options_nojs);
    }

    /**
     * Build the admin settings panel.
     */
     function build_AdminSettingsPanel() {
        $sectName = __('Admin','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $sectName                                    ,
                    'group'         => __('Settings'                  ,'csa-slplus'),
                    'label'         => __('Turn off rate notification','csa-slplus'),
                    'setting'       => 'thisbox'                                    ,
                    'type'          => 'checkbox'                                   ,
                    'description'   =>
                        __('This will disable the notification asking you to rate our product.','csa-slplus')
                )
            );
        
        // ACTION: slp_generalsettings_modify_adminpanel
        //    params: settings object, section name
        do_action('slp_generalsettings_modify_adminpanel',$this->settings,$sectName);
     }

     /**
      * Build the Google settings panel.
      */
     function build_GoogleSettingsPanel() {
        $sectName = __('Google','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));

        $groupName = __('Geocoding','csa-slplus');
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $sectName                            ,
                    'group'         => $groupName                           ,
                    'label'         => __('Geocode Retries','csa-slplus')   ,
                    'setting'       => 'geocode_retries'                    ,
                    'type'          => 'list'                               ,
                    'value'         => get_option(SLPLUS_PREFIX.'-geocode_retries','3'),
                    'custom'        =>
                        array (
                              'None' => 0,
                              '1' => '1',
                              '2' => '2',
                              '3' => '3',
                              '4' => '4',
                              '5' => '5',
                              '6' => '6',
                              '7' => '7',
                              '8' => '8',
                              '9' => '9',
                              '10' => '10',
                            ),
                     'description'   =>
                            __('How many times should we try to set the latitude/longitude for a new address? '         ,'csa-slplus').
                            __('Higher numbers mean slower bulk uploads. '                                              ,'csa-slplus').
                            __('Lower numbers make it more likely the location will not be set during bulk uploads. '   ,'csa-slplus').
                            sprintf(__('Bulk import or re-geocoding is a %s feature.','csa-slplus'),SLPLUS::linkToPRO)
                )
            );

        $this->settings->add_ItemToGroup(
            array(
                'section'       => $sectName                                            ,
                'group'         => $groupName                                           ,
                'label'         => __('Maximum Retry Delay','csa-slplus')               ,
                'setting'       => 'retry_maximum_delay'                                ,
                'use_prefix'    => false                                                ,
                'value'         => $this->plugin->options_nojs['retry_maximum_delay']   ,
                'description'   =>
                    __('Maximum time to wait between retries, in seconds. ','csa-slplus')   .
                    __('Use multiples of 1. ','csa-slplus')                                .
                    __('Recommended value is 5. ','csa-slplus')                            .
                    sprintf(__('Bulk import or re-geocoding is a %s feature.','csa-slplus'),SLPLUS::linkToPRO)
            )
        );

        $this->settings->add_ItemToGroup(
            array(
                'section'      => $sectName                        ,
                'group'        => $groupName                       ,
                'label'        => __('Google API Key','csa-slplus'),
                'setting'      => 'api_key'                        ,
                'description'  =>
                    __('This setting helps with query limits for businesses with a Google Business Account only.','csa-slplus').
                    __('The free Google API keys do not have an impact on query limits.','csa-slplus')
            )
        );

        $this->settings->add_ItemToGroup(
            array(
                'section'       => $sectName                            ,
                'group'        => $groupName                            ,
                'label'         => __('Turn Off SLP Maps','csa-slplus') ,
                'setting'       => 'no_google_js'                       ,
                'type'          => 'checkbox'                           ,
                'description'   =>
                    __('Check this box if your Theme or another plugin is providing Google Maps and generating warning messages. '.
                       'THIS MAY BREAK THIS PLUGIN.',
                       'csa-slplus')
            )
        );

        // ACTION: slp_generalsettings_modify_googlepanel
        //    params: settings object, section name
        do_action('slp_generalsettings_modify_googlepanel',$this->settings,$sectName);
     }

     /**
      * Build the User Settings Panel
      */
     function build_UserSettingsPanel() {
        $sectName   = __('User Interface','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));

        $groupName  = __('JavaScript','csa-slplus');
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $sectName,
                    'group'         => $groupName,
                    'label'         => __('Force Load JavaScript','csa-slplus'),
                    'setting'       => 'force_load_js',
                    'type'          => 'checkbox',
                    'default'       => 1,
                    'description'   =>
                        __('Force the JavaScript for Store Locator Plus to load on every page with early loading. ' , 'csa-slplus') .
                        __('This can slow down your site, but is compatible with more themes and plugins.'          , 'csa-slplus')
                   )
               );

        // ACTION: slp_generalsettings_modify_userpanel
        //    params: settings object, section name
        do_action('slp_generalsettings_modify_userpanel',$this->settings,$sectName);
     }

     /**
      * Render the map settings admin page.
      */
     function render_adminpage() {
         
        // If we are updating settings...
        //
        if (isset($_REQUEST['action']   ) && ($_REQUEST['action']==='update') &&
            isset($_REQUEST['_wpnonce'] ) && (!empty($_REQUEST['_wpnonce']  ))
            ) {
            $this->save_Settings();
        }

        // Setup and render settings page
        //
        $this->settings = new wpCSL_settings__slplus(
            array(
                    'prefix'            => $this->plugin->prefix,
                    'css_prefix'        => $this->plugin->prefix,
                    'url'               => $this->plugin->url,
                    'name'              => $this->plugin->name . ' - ' . __('General Settings','csa-slplus'),
                    'plugin_url'        => $this->plugin->plugin_url,
                    'render_csl_blocks' => false,
                    'form_action'       => admin_url().'admin.php?page='.$this->slug
                )
         );

        $this->settings->add_section(
            array(
                'name'          => 'Navigation',
                'div_id'        => 'navbar_wrapper',
                'description'   => $this->plugin->AdminUI->create_Navbar(),
                'innerdiv'      => false,
                'is_topmenu'    => true,
                'auto'          => false,
                'headerbar'     => false
            )
        );

        // Panel building actions
        //
        add_action('slp_build_general_settings_panels',array($this,'build_UserSettingsPanel'  ) ,10);
        add_action('slp_build_general_settings_panels',array($this,'build_GoogleSettingsPanel') ,20);
        add_action('slp_build_general_settings_panels',array($this,'build_AdminSettingsPanel' ) ,30);

        //------------------------------------
        // Render It
        //
        do_action('slp_build_general_settings_panels');
        $this->settings->render_settings_page();
    }
}

// Dad. Husband. Rum Lover. Code Geek. Not necessarily in that order.