<?php
/**
 * Store Locator Plus General Settings Interface
 *
 * @package StoreLocatorPlus\AdminUI\GeneralSettings
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 - 2015 Charleston Software Associates, LLC
 */
class SLPlus_AdminUI_GeneralSettings {

    //-----------------------------
    // Properties
    //-----------------------------

    /**
     * The SLPlus plugin object.
     *
     * @var \SLPlus
     */
    private $slplus;

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
    function __construct( $params ) {
        foreach ( $params as $property => $value ) {
            if ( property_exists( $this , $property ) ) {
                $this->$property = $value;
            }
        }
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
                    SLPLUS_PREFIX.'-geocode_retries'    ,
                )
            );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->slplus->helper->SavePostToOptionsTable($JustAnotherBox);
        }

        // Checkboxes
        // FILTER: slp_save_general_settings_checkboxes
        //
        $BoxesToHit =
            apply_filters('slp_save_general_settings_checkboxes',
                array(
                    SLPLUS_PREFIX.'-no_google_js'               ,
                    SLPLUS_PREFIX.'-thisbox'                    ,
                    )
                );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->slplus->helper->SaveCheckBoxToDB($JustAnotherBox, '','');
        }

        // Serialized Checkboxes, Need To Blank If Not Received
        //
        $BoxesToHit = array(
            'extended_admin_messages'   ,
            'force_load_js'             ,
            );
        foreach ($BoxesToHit as $BoxName) {
            if (!isset($_REQUEST[$BoxName])) {
                $_REQUEST[$BoxName] = '0';
            }
        }

        // Serialized Options Setting for stuff going into slp.js.
        // This should be used for ALL new JavaScript options.
        //
        array_walk($_REQUEST,array($this->slplus,'set_ValidOptions'));
        update_option(SLPLUS_PREFIX.'-options', $this->slplus->options);

        // Serialized Options Setting for stuff NOT going to slp.js.
        // This should be used for ALL new options not going to slp.js.
        //
        array_walk($_REQUEST,array($this->slplus,'set_ValidOptionsNoJS'));
        update_option(SLPLUS_PREFIX.'-options_nojs', $this->slplus->options_nojs);
    }

    /**
     * Build the admin settings panel.
     */
     function build_AdminSettingsPanel() {
        $panel_name     = __('Admin'    ,'csa-slplus');
        $section_name   = __('Settings' ,'csa-slplus');
        $this->settings->add_section(array('name' => $panel_name));
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $panel_name                                    ,
                    'group'         => $section_name,
                    'label'         => __('Turn off rate notification','csa-slplus'),
                    'setting'       => 'thisbox'                                    ,
                    'type'          => 'checkbox'                                   ,
                    'description'   =>
                        __('This will disable the notification asking you to rate our product.','csa-slplus')
                )
            );
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $panel_name                                  ,
                    'group'         => $section_name                                ,
                    'type'          => 'checkbox'                                   ,
                    'use_prefix'    => false,
                    'label'         => __('Extended Admin Messages'   ,'csa-slplus'),
                    'setting'       => 'extended_admin_messages'                    ,
                    'value'         => $this->slplus->is_CheckTrue($this->slplus->options_nojs['extended_admin_messages']),
                    'description'   =>
                        __('Show extended messages on the admin panel.','csa-slplus')
                )
            );
        
        // ACTION: slp_generalsettings_modify_adminpanel
        //    params: settings object, section name
        do_action('slp_generalsettings_modify_adminpanel',$this->settings,$panel_name);
     }

     /**
      * Build the Google settings panel.
      */
     function build_ServerSection() {
         $this->slplus->createobject_AddOnManager();
         
        $sectName = __('Server','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));

        $groupName = __('Geocoding','csa-slplus');
         $this->settings->add_ItemToGroup(
             array(
                 'section'       => $sectName                                   ,
                 'group'         => $groupName                                  ,
                 'label'         => __('Server-To-Server Speed','csa-slplus')   ,
                 'setting'       => 'http_timeout'                              ,
                 'use_prefix'    => false,
                 'type'          => 'list'                                      ,
                 'value'         => $this->slplus->options_nojs['http_timeout'] ,
                 'custom'        =>
                     array (
                         'Slow'     => '30',
                         'Normal'   => '10',
                         'Fast'     => '3',
                     ),
                 'description'   =>
                     __('How fast is your server when communicating with other servers like Google? '               ,'csa-slplus') .
                     __('Set this to slow if you get frequent geocoding errors but geocoding works sometimes. '     , 'csa-slplus') .
                     __('Set this to fast if you never have geocoding errors and are bulk loading more than 100 locations at a time. '     , 'csa-slplus')
             )
         );
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
                            sprintf(__('Bulk import or re-geocoding is a %s feature.','csa-slplus'),$this->slplus->add_ons->available['slp-pro']['link'])
                )
            );

        $this->settings->add_ItemToGroup(
            array(
                'section'       => $sectName                                            ,
                'group'         => $groupName                                           ,
                'label'         => __('Maximum Retry Delay','csa-slplus')               ,
                'setting'       => 'retry_maximum_delay'                                ,
                'use_prefix'    => false                                                ,
                'value'         => $this->slplus->options_nojs['retry_maximum_delay']   ,
                'description'   =>
                    __('Maximum time to wait between retries, in seconds. ','csa-slplus')   .
                    __('Use multiples of 1. ','csa-slplus')                                .
                    __('Recommended value is 5. ','csa-slplus')                            .
                    sprintf(__('Bulk import or re-geocoding is a %s feature.','csa-slplus'),$this->slplus->add_ons->available['slp-pro']['link'])
            )
        );

        // Google License
        //
        $groupName = __('Google Business License','csa-slplus');
         $this->settings->add_ItemToGroup(
             array(
                 'section'       => $sectName                                            ,
                 'group'         => $groupName                                           ,
                 'label'         => __('Google Client ID','csa-slplus')                  ,
                 'setting'       => 'google_client_id'                                   ,
                 'use_prefix'    => false                                                ,
                 'value'         => $this->slplus->options_nojs['google_client_id']      ,
                 'description'   =>
                     __('If you have a Google Maps for Work client ID, enter it here. ','csa-slplus') .
                     __('All Google API requests will go through your account at Google. ','csa-slplus') .
                     __('You will receive higher quotas and faster maps I/O performance. ','csa-slplus')
             )
         );
         $this->settings->add_ItemToGroup(
             array(
                 'section'       => $sectName                                            ,
                 'group'         => $groupName                                           ,
                 'label'         => __('Google Private Key','csa-slplus')                  ,
                 'setting'       => 'google_private_key'                                   ,
                 'use_prefix'    => false                                                ,
                 'value'         => $this->slplus->options_nojs['google_private_key']      ,
                 'description'   =>
                     __('Your Google private key (Crypto Key) for signing Geocoding requests. ','csa-slplus') .
                     __('Do NOT share this with anyone and take extra measures to keep it private. ','csa-slplus')
             )
         );

        // ACTION: slp_generalsettings_modify_googlepanel
        //    params: settings object, section name
        do_action( 'slp_generalsettings_modify_googlepanel' ,   $this->settings ,   $sectName );
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
                    'section' => $sectName,
                    'group' => $groupName,
                    'label' => '',
                    'type' => 'subheader',
                    'show_label' => false,
                    'description' =>
                    __('These settings change how JavaScript behaves on your site. ', 'csa-slplus') .
                      ( $this->slplus->javascript_is_forced ? 
                        '<br/><em>*' .
                        sprintf(
                                __('You have <a href="%s" target="csa">Force Load JavaScript</a> ON. ', 'csa-slplus') ,
                                $this->slplus->support_url . 'general-settings/user-interface/javascript/'
                            ) .
                        __('Themes that follow WordPress best practices and employ wp_footer() properly do not need this. ', 'csa-slplus') .
                        __('Leaving it on slows down your site and disables a lot of extra features with the plugin and add-on packs. ', 'csa-slplus') .
                        '</em>' : 
                        '' 
                        )
                )
        );             
        $this->settings->add_ItemToGroup(
                array(
                    'section'       => $sectName,
                    'group'         => $groupName,
                    'type'          => 'checkbox',
                    'use_prefix'    => false,
                    'label'         => __('Force Load JavaScript','csa-slplus'),
                    'setting'       => 'force_load_js',
                    'value'         => $this->slplus->is_CheckTrue($this->slplus->options_nojs['force_load_js']),
                    'description'   =>
                        __('Force the JavaScript for Store Locator Plus to load on every page with early loading. ' , 'csa-slplus') .
                        __('This can slow down your site, but is compatible with more themes and plugins. '         , 'csa-slplus') . 
                        __('If you need to do this to make SLP work you should ask your theme author to add proper wp_footer() support to their code. '         , 'csa-slplus')
                   )
               );

         // Map Interface
         //
         $groupName = __('Map Interface','csa-slplus');
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

        // ACTION: slp_generalsettings_modify_userpanel
        //    params: settings object, section name
        do_action('slp_generalsettings_modify_userpanel',$this->settings,$sectName);
     }

    /**
     * Add web app settings.
     */
    function build_WebAppSettings( ) {
        $section   = __('Server','csa-slplus');
        $groupName = __( 'Web App Settings' ,'csa-slplus');
        $this->settings->add_ItemToGroup(
            array(
                'section'       => $section                                             ,
                'group'         => $groupName                                           ,
                'label'         => __('PHP Time Limit','csa-slplus')                    ,
                'setting'       => 'php_max_execution_time'                             ,
                'use_prefix'    => false                                                ,
                'value'         => $this->slplus->options_nojs['php_max_execution_time'],
                'description'   =>
                    __('Maximum execution time, in seconds, for PHP processing. ','csa-slplus')  .
                    __('Affects all CSV imports for add-ons and Janitor delete all locations. ','csa-slplus')  .
                    __('SLP Default 600. ' , 'csa-slplus' ) .
                    sprintf( __('Your server default %s. ' , 'csa-slplus' )  ,
                             ini_get( 'max_execution_time')
                        )
            )
        );
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
                    'prefix'            => $this->slplus->prefix,
                    'css_prefix'        => $this->slplus->prefix,
                    'url'               => $this->slplus->url,
                    'name'              => $this->slplus->name . ' - ' . __('General Settings','csa-slplus'),
                    'plugin_url'        => $this->slplus->plugin_url,
                    'render_csl_blocks' => false,
                    'form_action'       => admin_url().'admin.php?page='.$this->slug
                )
         );

        $this->settings->add_section(
            array(
                'name'          => 'Navigation',
                'div_id'        => 'navbar_wrapper',
                'description'   => $this->slplus->AdminUI->create_Navbar(),
                'innerdiv'      => false,
                'is_topmenu'    => true,
                'auto'          => false,
                'headerbar'     => false
            )
        );

        // Panel building actions
        //
        add_action('slp_build_general_settings_panels',array($this,'build_UserSettingsPanel'    ) ,10 );
        add_action('slp_build_general_settings_panels',array($this,'build_AdminSettingsPanel'   ) ,20 );
        add_action('slp_build_general_settings_panels',array($this,'build_ServerSection'        ) ,30 );
        add_action( 'slp_build_general_settings_panels' , array( $this, 'build_WebAppSettings'  ), 40 );


         //------------------------------------
        // Render It
        //
        do_action('slp_build_general_settings_panels');
        $this->settings->render_settings_page();
    }
}

// Dad. Explorer. Rum Lover. Code Geek. Not necessarily in that order.