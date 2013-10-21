<?php
/**
 * Store Locator Plus map settings admin user interface.
 *
 * @package StoreLocatorPlus\AdminUI\MapSettings
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 */
class SLPlus_AdminUI_MapSettings {

    //-----------------------------
    // Properties
    //-----------------------------

    /**
     * Has the createSettingsGroup deprecation notice been shown already?
     *
     * @var boolean $depnotice_createSettingsGroup
     */
    private  $depnotice_createSettingsGroup = false;


    /**
     * The SLPlus plugin object.
     *
     * @var \SLPlus $plugin
     */
    private $plugin;

    /**
     * @var \wpCSL_settings__slplus $settings
     */
    public $settings;

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

        $this->settings = new wpCSL_settings__slplus(
            array(
                    'parent'            => $this->plugin,
                    'prefix'            => $this->plugin->prefix,
                    'css_prefix'        => $this->plugin->prefix,
                    'url'               => $this->plugin->url,
                    'name'              => $this->plugin->name . __(' - User Experience','csa-slplus'),
                    'plugin_url'        => $this->plugin->plugin_url,
                    'render_csl_blocks' => false,
                    'form_action'       => '',
                    'save_text'         => __('Save Settings','csa-slplus')
                )
         );
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
     * Add the UX View Section on the User Experience Tab
     */
    function action_AddUXViewSection() {
        $this->plugin->helper->loadPluginData();
        $sectName = __('View','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));

        // Theme Selector
        //
        $this->plugin->themes->add_admin_settings($this->settings,$sectName,'Style');

        // ACTION: slp_uxsettings_modify_viewpanel
        //    params: settings object, section name
        do_action('slp_uxsettings_modify_viewpanel',$this->settings,$sectName);
    }

    /**
     * Generate the HTML for an input settings interface element.
     *
     * @param string $boxname
     * @param string $label
     * @param string $msg
     * @param string $prefix
     * @param string $default
     * @param string $value - forced value
     * @return string HTML for the div box.
     */
    function CreateInputDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='',$value=null) {
        $this->plugin->debugMP('slp.main','msg','SLPlus_AdminUI_MapSettings:'.__FUNCTION__);
        $whichbox = $prefix.$boxname;
        if ($value===null) { 
            $value = $this->getCompoundOption($whichbox,$default);
        }
        return
            "<div class='form_entry'>" .
                "<div class='wpcsl-input wpcsl-list'>" .
                    "<label for='$whichbox'>$label:</label>".
                    "<input  name='$whichbox' value='$value'>".
                "</div>".
                $this->plugin->helper->CreateHelpDiv($boxname,$msg).
             "</div>"
            ;
    }

    /**
     * Generate the HTML for a Pulldown settings interface element.
     * 
     * @param string $boxname
     * @param string $values
     * @param string $label
     * @param string $msg
     * @param string $prefix
     * @param string $default
     * @return string HTML
     */
    function CreatePulldownDiv($boxname,$values,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='') {
        $whichbox = $prefix.$boxname;
        $selected = get_option($whichbox,$default);

        $content =
                "<div class='form_entry'>".
                "<div class='wpcsl-input wpcsl-list'>" .
                        "<label for='$whichbox'>$label:</label>" .
                        "<select name='$whichbox'>"
                ;

        foreach ($values as $value){
            $content.="<option value='$value' ".(($value == $selected)?'selected':'').">".
                      $value.
                    "</option>";
        }

        $content.=      "</select>".
                    "</div>".
                    $this->plugin->helper->CreateHelpDiv($boxname,$msg).
                "</div>"
                ;

        return $content;
    }

    /**
     * Generate the HTML for a text area settings interface element.
     * 
     * @param string $boxname
     * @param string $label
     * @param string $msg
     * @param string $prefix
     * @param string $default
     * @param boolean $usedefault if set use this value explicitly without get_option fetch.
     * @return string HTML
     */
    function CreateTextAreaDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='', $usedefault = false) {
        $whichbox = $prefix.$boxname;
        $value =
                $usedefault                                                ?
                $default                                                   :
                stripslashes(esc_textarea(get_option($whichbox,$default))) ;
        return
            "<div class='wpcsl-setting'>" .
                "<div class='wpcsl-input wpcsl-textarea'>" .
                    "<label for='$whichbox'>$label:</label>".
                    "<textarea  name='$whichbox'>{$value}</textarea>".
                "</div>".
                $this->plugin->helper->CreateHelpDiv($boxname,$msg).
             "</div>"
            ;

    }

    /**
     * Execute the save settings action.
     *
     * Called when a $_POST is set when doing render_adminpage.
     */
    function save_settings() {
        $sl_google_map_arr=explode(":", $_POST['google_map_domain']);
        update_option('sl_google_map_country', $sl_google_map_arr[0]);
        update_option('sl_google_map_domain', $sl_google_map_arr[1]);

        // Height, strip non-digits, if % set range 0..100
        if (in_array($_POST['sl_map_height_units'],array('%','px','pt','em'))) {
            $_POST['sl_map_height']=preg_replace('/[^0-9]/', '', $_POST['sl_map_height']);
            if ($_POST['sl_map_height_units'] == '%') {
                $_POST['sl_map_height'] = max(0,min($_POST['sl_map_height'],100));
            }
        }

        // Width, strip non-digtis, if % set range 0..100
        if (in_array($_POST['sl_map_width_units'],array('%','px','pt','em'))) {
            $_POST['sl_map_width'] =preg_replace('/[^0-9]/', '', $_POST['sl_map_width']);
            if ($_POST['sl_map_width_units'] == '%') {
                $_POST['sl_map_width'] = max(0,min($_POST['sl_map_width'],100));
            }
        }

        // Standard Input Saves
        //
        $BoxesToHit =
            apply_filters('slp_save_map_settings_inputs',
                array(
                    'sl_language'                           ,
                    'sl_map_radii'                          ,
                    'sl_instruction_message'                ,
                    'sl_zoom_level'                         ,
                    'sl_zoom_tweak'                         ,
                    'sl_map_height_units'                   ,
                    'sl_map_height'                         ,
                    'sl_map_width_units'                    ,
                    'sl_map_width'                          ,
                    'sl_map_home_icon'                      ,
                    'sl_map_end_icon'                       ,
                    'sl_map_type'                           ,
                    'sl_num_initial_displayed'              ,
                    'sl_distance_unit'                      ,
                    'sl_radius_label'                       ,
                    'sl_search_label'                       ,
                    'sl_website_label'                      ,
                    SLPLUS_PREFIX.'_label_directions'       ,
                    SLPLUS_PREFIX.'_label_fax'              ,
                    SLPLUS_PREFIX.'_label_hours'            ,
                    SLPLUS_PREFIX.'_label_phone'            ,
                    SLPLUS_PREFIX.'_tag_search_selections'  ,
                    SLPLUS_PREFIX.'-map_language'           ,
                    SLPLUS_PREFIX.'_maxreturned'            ,
                    SLPLUS_PREFIX.'-theme'                  ,
                )
            );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SavePostToOptionsTable($JustAnotherBox);
        }

        // Checkboxes
        //
        $BoxesToHit =
            apply_filters('slp_save_map_settings_checkboxes',
                array(
                    SLPLUS_PREFIX.'_use_email_form'             ,
                    SLPLUS_PREFIX.'_email_form'                 ,
                    SLPLUS_PREFIX.'_disable_find_image'         ,
                    SLPLUS_PREFIX.'-force_load_js'              ,
                    'sl_load_locations_default'                 ,
                    'sl_remove_credits'                         ,
                    )
                );
        $this->plugin->debugMP('slp.mapsettings','pr','save_settings() Checkboxes',$BoxesToHit,NULL,NULL,true);
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SaveCheckBoxToDB($JustAnotherBox, '','');
        }

        // Serialized Options Setting
        // This should be used for ALL new options.
        // Serialized options = ONE data I/O call, MUCH FASTER!!!
        //
        array_walk($_REQUEST,array($this->plugin,'set_ValidOptions'));
        update_option(SLPLUS_PREFIX.'-options', $this->plugin->options);
        $this->plugin->debugMP('slp.mapsettings','pr','Map Settings Saved to '.SLPLUS_PREFIX.'-options',$this->plugin->options,__FILE__,__LINE__);
    }

    //=======================================
    // RENDER FUNCTIONS
    //=======================================

     /**
      * Add the map panel to the map settings page on the admin UI.
      *
      */
     function map_settings() {
        $this->plugin->helper->loadPluginData();

        // Features
        //
        $slpDescription =
            $this->plugin->helper->create_SubheadingLabel(__('Look and Feel','csa-slplus')) .
                
                $this->CreateInputDiv(
                    'sl_map_height',
                    __('Map Height','csa-slplus'),
                    __('The initial map height in pixels or percent of initial page height. ','csa-slplus') .
                    __('Can also use rules like auto and inherit if Height Units is set to blank ','csa-slplus')
                        ,
                    '',
                    '480'
                    ) .

                $this->CreatePulldownDiv(
                    'sl_map_height_units',
                    array('%','px','em','pt',''),
                    __('Height Units','csa-slplus'),
                    __('Is the width a percentage of page width or absolute pixel size? ','csa-slplus') .
                    __('Select blank to use CSS rules like auto or inherit in the Map Height setting.','csa-slplus')
                        ,
                    '',
                    'px'
                    ) .

                $this->CreateInputDiv(
                    'sl_map_width',
                    __('Map Width','csa-slplus'),
                    __('The initial map width in pixels or percent of page width. Also sets results width.','csa-slplus') .
                    __('Can also use rules like auto and inherit if Width Units is set to blank ','csa-slplus')
                        ,
                    '',
                    '640'
                    ) .
                $this->CreatePulldownDiv(
                    'sl_map_width_units',
                    array('%','px','em','pt',''),
                    __('Width Units','csa-slplus'),
                    __('Is the width a percentage of page width or absolute pixel size?','csa-slplus') .
                    __('Select blank to use CSS rules like auto or inherit in the Map Width setting.','csa-slplus')
                        ,
                    '',
                    '%'
                    ) .
            $this->CreatePulldownDiv(
                    'sl_map_type',
                    array('roadmap','hybrid','satellite','terrain'),
                    __('Default Map Type', 'csa-slplus'),
                    __('What style Google Map should we use?', 'csa-slplus'),
                    '',
                    'roadmap'
                    ) .
            $this->plugin->helper->CreateCheckboxDiv(
                    'sl_remove_credits',
                    __('Remove Credits','csa-slplus'),
                    __('Remove the search provided by tagline under the map.','csa-slplus'),
                    '',
                    false,
                    0
                    )
                ;

            $mapSettings['features'] = apply_filters('slp_map_features_settings',$slpDescription);

            // Settings
            //
            $slpDescription =
                $this->plugin->helper->create_SubheadingLabel(__('Behavior','csa-slplus'))
                ;
            
            $slpDescription .=
                "<div class='form_entry'>" .
                "<label for='google_map_domain'>". __("Map Domain", 'csa-slplus') . "</label>" .
                "<select name='google_map_domain'>"
                ;
                foreach ($this->get_map_domains() as $key=>$sl_value) {
                    $selected=(get_option('sl_google_map_domain')==$sl_value)?" selected " : "";
                    $slpDescription .= "<option value='$key:$sl_value' $selected>$key ($sl_value)</option>\n";
                }
            $slpDescription .=
                    "</select></div>";

                // Language Selection
                //
            $slpDescription .=
                    "<div class='form_entry'>" .
                    "<label for='".SLPLUS_PREFIX."-map_language'>".__('Map Language', 'csa-slplus')."</label>" .
                    "<select name='".SLPLUS_PREFIX."-map_language'>"
                    ;
                foreach ($this->get_map_languages() as $key=>$sl_value) {
                    $selected=($this->plugin->helper->getData('map_language','get_item',null,'en')==$sl_value)?" selected " : "";
                    $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
                }
            $slpDescription .=
                    "</select></div>";

            $slpDescription .=
                $this->CreatePulldownDiv(
                    'sl_zoom_level',
                    array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                    __('Zoom Level', 'csa-slplus'),
                    __('Initial zoom level of the map if "immediately show locations" is NOT selected or if only a single location is found.  0 = world view, 19 = house view.', 'csa-slplus'),
                    '',
                    4
                    ) .

                $this->CreatePulldownDiv(
                    'sl_zoom_tweak',
                    array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                    __('Zoom Adjustment', 'csa-slplus'),
                    __('Changes how tight auto-zoom bounds the locations shown.  Lower numbers are closer to the locations.', 'csa-slplus'),
                    '',
                    4
                    )
                    ;


            $mapSettings['settings'] = 
                "<div class='section_column_content'>".
                apply_filters('slp_map_settings_settings',$slpDescription) .
                '</div>'
                ;


        // ===== Icons
        //
        $slpDescription =
                    $this->plugin->data['iconNotice'] .
                    "<div class='form_entry'>".
                        "<label for='sl_map_home_icon'>".__('Home Marker', 'csa-slplus')."</label>".
                        "<input id='sl_map_home_icon' name='sl_map_home_icon' dir='rtl' size='45' ".
                                "value='".$this->plugin->data['sl_map_home_icon']."' ".
                                'onchange="document.getElementById(\'prev\').src=this.value">'.
                        "<img id='home_icon_preview' src='".$this->plugin->data['sl_map_home_icon']."' align='top'><br/>".
                        $this->plugin->data['homeIconPicker'].
                    "</div>".
                    "<div class='form_entry'>".
                        "<label for='sl_map_end_icon'>".__('Destination Marker', 'csa-slplus')."</label>".
                        "<input id='sl_map_end_icon' name='sl_map_end_icon' dir='rtl' size='45' ".
                            "value='".$this->plugin->data['sl_map_end_icon']."' ".
                            'onchange="document.getElementById(\'prev2\').src=this.value">'.
                        "<img id='end_icon_preview' src='".$this->plugin->data['sl_map_end_icon']."'align='top'><br/>".
                        $this->plugin->data['endIconPicker'] .
                    "</div>".
                    '<br/><p>'.
                    __('Saved markers live here: ','csa-slplus') . SLPLUS_UPLOADDIR . "saved-icons/</p>"
            ;
        $mapSettings['icons'] = apply_filters('slp_map_icons_settings',$slpDescription);

        // TODO: Convert to new panel builder with add_ItemToGroup() in wpCSL (see Tagalong admin panel)
        $mapSections =
                $this->plugin->settings->create_SettingsGroup(
                                    'map_features',
                                    __('Map Features','csa-slplus'),
                                    '',
                                    $mapSettings['features']
                                    ) .
                $this->plugin->settings->create_SettingsGroup(
                                    'map_settings',
                                    __('Map Settings','csa-slplus'),
                                    '',
                                    $mapSettings['settings']
                                    ) .
                $this->plugin->settings->create_SettingsGroup(
                                    'map_icons',
                                    __('Map Markers','csa-slplus'),
                                    '',
                                    $mapSettings['icons']
                                    )
            ;

        $this->settings->add_section(
            array(
                    'name'          => __('Map','csa-slplus'),
                    'div_id'        => 'map',
                    'description'   => $mapSections,
                    'auto'          => true,
                    'innerdiv'      => true
                )
         );
     }

     /**
      * Return the list of Google map domains.
      * 
      * @return string[] list of domains, key is the name, value is the Google URL
      */
     function get_map_domains() {
         return apply_filters(
                 'slp_map_domains',
                array(
                    __('United States' ,'csa-slplus')=>'maps.google.com',
                    __('Argentina'     ,'csa-slplus')=>'maps.google.com.ar',
                    __('Australia'     ,'csa-slplus')=>'maps.google.com.au',
                    __('Austria'       ,'csa-slplus')=>'maps.google.at',
                    __('Bahamas'       ,'csa-slplus')=>'maps.google.bs',
                    __('Belgium'       ,'csa-slplus')=>'maps.google.be',
                    __('Brazil'        ,'csa-slplus')=>'maps.google.com.br',
                    __('Canada'        ,'csa-slplus')=>'maps.google.ca',
                    __('Chile'         ,'csa-slplus')=>'maps.google.cl',
                    __('China'         ,'csa-slplus')=>'ditu.google.com',
                    __('Czech Republic','csa-slplus')=>'maps.google.cz',
                    __('Denmark'       ,'csa-slplus')=>'maps.google.dk',
                    __('Estonia'       ,'csa-slplus')=> 'maps.google.ee',
                    __('Finland'       ,'csa-slplus')=>'maps.google.fi',
                    __('France'        ,'csa-slplus')=>'maps.google.fr',
                    __('Germany'       ,'csa-slplus')=>'maps.google.de',
                    __('Greece'        ,'csa-slplus')=>'maps.google.gr',
                    __('Hong Kong'     ,'csa-slplus')=>'maps.google.com.hk',
                    __('Hungary'       ,'csa-slplus')=>'maps.google.hu',
                    __('India'         ,'csa-slplus')=>'maps.google.co.in',
                    __('Philippines'   ,'csa-slplus')=>'maps.google.com.ph',
                    __('Republic of Ireland','csa-slplus')=>'maps.google.ie',
                    __('Israel'        ,'csa-slplus')=>'maps.google.co.il',
                    __('Italy'         ,'csa-slplus')=>'maps.google.it',
                    __('Japan'         ,'csa-slplus')=>'maps.google.co.jp',
                    __('Liechtenstein' ,'csa-slplus')=>'maps.google.li',
                    __('Lithuania'     ,'csa-slplus')=>'maps.google.lt',
                    __('Mexico'        ,'csa-slplus')=>'maps.google.com.mx',
                    __('Netherlands'   ,'csa-slplus')=>'maps.google.nl',
                    __('New Zealand'   ,'csa-slplus')=>'maps.google.co.nz',
                    __('Norway'        ,'csa-slplus')=>'maps.google.no',
                    __('Poland'        ,'csa-slplus')=>'maps.google.pl',
                    __('Portugal'      ,'csa-slplus')=>'maps.google.pt',
                    __('Russia'        ,'csa-slplus')=>'maps.google.ru',
                    __('Singapore'     ,'csa-slplus')=>'maps.google.com.sg',
                    __('South Africa'  ,'csa-slplus')=>'maps.google.co.za',
                    __('South Korea'   ,'csa-slplus')=>'maps.google.co.kr',
                    __('Spain'         ,'csa-slplus')=>'maps.google.es',
                    __('Sweden'        ,'csa-slplus')=>'maps.google.se',
                    __('Switzerland'   ,'csa-slplus')=>'maps.google.ch',
                    __('Taiwan'                 ,'csa-slplus')=>'maps.google.com.tw',
                    __('United Arab Emirates'   ,'csa-slplus')=>'maps.google.ae',
                    __('United Kingdom'         ,'csa-slplus')=>'maps.google.co.uk',
                    )
                 );
     }

     /**
      * Return the list of Google map languages.
      *
      * @return string[] list of languages, key is the name, value is the Google language

      */
     function get_map_languages() {
         return apply_filters(
                 'slp_map_languages',
                    array(
                        __('English'                  ,'csa-slplus') => 'en',
                        __('Arabic'                   ,'csa-slplus') => 'ar',
                        __('Basque'                   ,'csa-slplus') => 'eu',
                        __('Bulgarian'                ,'csa-slplus') => 'bg',
                        __('Bengali'                  ,'csa-slplus') => 'bn',
                        __('Catalan'                  ,'csa-slplus') => 'ca',
                        __('Czech'                    ,'csa-slplus') => 'cs',
                        __('Danish'                   ,'csa-slplus') => 'da',
                        __('German'                   ,'csa-slplus') => 'de',
                        __('Greek'                    ,'csa-slplus') => 'el',
                        __('English (Australian)'     ,'csa-slplus') => 'en-AU',
                        __('English (Great Britain)'  ,'csa-slplus') => 'en-GB',
                        __('Spanish'                  ,'csa-slplus') => 'es',
                        __('Farsi'                    ,'csa-slplus') => 'fa',
                        __('Finnish'                  ,'csa-slplus') => 'fi',
                        __('Filipino'                 ,'csa-slplus') => 'fil',
                        __('French'                   ,'csa-slplus') => 'fr',
                        __('Galician'                 ,'csa-slplus') => 'gl',
                        __('Gujarati'                 ,'csa-slplus') => 'gu',
                        __('Hindi'                    ,'csa-slplus') => 'hi',
                        __('Croatian'                 ,'csa-slplus') => 'hr',
                        __('Hungarian'                ,'csa-slplus') => 'hu',
                        __('Indonesian'               ,'csa-slplus') => 'id',
                        __('Italian'                  ,'csa-slplus') => 'it',
                        __('Hebrew'                   ,'csa-slplus') => 'iw',
                        __('Japanese'                 ,'csa-slplus') => 'ja',
                        __('Kannada'                  ,'csa-slplus') => 'kn',
                        __('Korean'                   ,'csa-slplus') => 'ko',
                        __('Lithuanian'               ,'csa-slplus') => 'lt',
                        __('Latvian'                  ,'csa-slplus') => 'lv',
                        __('Malayalam'                ,'csa-slplus') => 'ml',
                        __('Marathi'                  ,'csa-slplus') => 'mr',
                        __('Dutch'                    ,'csa-slplus') => 'nl',
                        __('Norwegian'                ,'csa-slplus') => 'no',
                        __('Polish'                   ,'csa-slplus') => 'pl',
                        __('Portuguese'               ,'csa-slplus') => 'pt',
                        __('Portuguese (Brazil)'      ,'csa-slplus') => 'pt-BR',
                        __('Portuguese (Portugal)'    ,'csa-slplus') => 'pt-PT',
                        __('Romanian'                 ,'csa-slplus') => 'ro',
                        __('Russian'                  ,'csa-slplus') => 'ru',
                        __('Slovak'                   ,'csa-slplus') => 'sk',
                        __('Slovenian'                ,'csa-slplus') => 'sl',
                        __('Serbian'                  ,'csa-slplus') => 'sr',
                        __('Swedish'                  ,'csa-slplus') => 'sv',
                        __('Tagalog'                  ,'csa-slplus') => 'tl',
                        __('Tamil'                    ,'csa-slplus') => 'ta',
                        __('Telugu'                   ,'csa-slplus') => 'te',
                        __('Thai'                     ,'csa-slplus') => 'th',
                        __('Turkish'                  ,'csa-slplus') => 'tr',
                        __('Ukrainian'                ,'csa-slplus') => 'uk',
                        __('Vietnamese'               ,'csa-slplus') => 'vi',
                        __('Chinese (Simplified)'     ,'csa-slplus') => 'zh-CN',
                        __('Chinese (Traditional)'    ,'csa-slplus') => 'zh-TW'
                    )
                );
     }

    /**
     * Retrieves map setting options, whether serialized or not.
     *
     * Simple options (non-serialized) return with a normal get_option() call result.
     *
     * Complex options (serialized) save any fetched result in $this->settingsData.
     * Doing so provides a basic cache so we don't keep hammering the database when
     * getting our map settings.  Legacy code expects a 1:1 relationship for options
     * to settings.   This mechanism ensures on database read/page render for the
     * complex options v. one database read/serialized element.
     *
     * @param string $optionName - the option name
     * @param mixed $default - what the default value should be
     * @return mixed the value of the option as saved in the database
     */
    function getCompoundOption($optionName,$default='') {
        if (!$this->set_Plugin()) { return; }
        $matches = array();
        if (preg_match('/^(.*?)\[(.*?)\]/',$optionName,$matches) === 1) {
            if (!isset($this->plugin->mapsettingsData[$matches[1]])) {
                $this->plugin->mapsettingsData[$matches[1]] = get_option($matches[1],$default);
            }
            return
                isset($this->plugin->mapsettingsData[$matches[1]][$matches[2]]) ?
                $this->plugin->mapsettingsData[$matches[1]][$matches[2]] :
                ''
                ;

        } else {
            return $this->plugin->helper->getData($optionName,'get_option',array($optionName,$default));
        }
    }


     /**
      * Render the map settings admin page.
      */
     function render_adminpage() {
        if (!$this->set_Plugin()) { return; }
        $update_msg ='';

        // We Have a POST - Save Settings
        //
        if ($_POST) {
            add_action('slp_save_map_settings',array($this,'save_settings') ,10);
            do_action('slp_save_map_settings');
            $update_msg = "<div class='highlight'>".__('Successful Update', 'csa-slplus').'</div>';
        }

        // Initialize Plugin Settings Data
        //
        $this->plugin->AdminUI->initialize_variables();
        $this->plugin->helper->loadPluginData();

        /**
         * @see http://goo.gl/UAXly - endIcon - the default map marker to be used for locations shown on the map
         * @see http://goo.gl/UAXly - endIconPicker -  the icon selection HTML interface
         * @see http://goo.gl/UAXly - homeIcon - the default map marker to be used for the starting location during a search
         * @see http://goo.gl/UAXly - homeIconPicker -  the icon selection HTML interface
         * @see http://goo.gl/UAXly - iconNotice - the admin panel message if there is a problem with the home or end icon
         * @see http://goo.gl/UAXly - siteURL - get_site_url() WordPress call
         */
        if (!isset($this->plugin->data['homeIconPicker'] )) {
            $this->plugin->data['homeIconPicker'] = $this->plugin->AdminUI->CreateIconSelector('sl_map_home_icon','home_icon_preview');
        }
        if (!isset($this->plugin->data['endIconPicker'] )) {
            $this->plugin->data['endIconPicker'] = $this->plugin->AdminUI->CreateIconSelector('sl_map_end_icon','end_icon_preview');
        }

        // Icon is the old path, notify them to re-select
        //
        $this->plugin->data['iconNotice'] = '';
        if (!isset($this->plugin->data['siteURL'] )) { $this->plugin->data['siteURL']  = get_site_url();                  }
        if (!(strpos($this->plugin->data['sl_map_home_icon'],'http')===0)) {
            $this->plugin->data['sl_map_home_icon'] = $this->plugin->data['siteURL']. $this->plugin->data['sl_map_home_icon'];
        }
        if (!(strpos($this->plugin->data['sl_map_end_icon'],'http')===0)) {
            $this->plugin->data['sl_map_end_icon'] = $this->plugin->data['siteURL']. $this->plugin->data['sl_map_end_icon'];
        }
        if (!$this->plugin->helper->webItemExists($this->plugin->data['sl_map_home_icon'])) {
            $this->plugin->data['iconNotice'] .=
                sprintf(
                        __('Your home marker %s cannot be located, please select a new one.', 'csa-slplus'),
                        $this->plugin->data['sl_map_home_icon']
                        )
                        .
                '<br/>'
                ;
        }
        if (!$this->plugin->helper->webItemExists($this->plugin->data['sl_map_end_icon'])) {
            $this->plugin->data['iconNotice'] .=
                sprintf(
                        __('Your destination marker %s cannot be located, please select a new one.', 'csa-slplus'),
                        $this->plugin->data['sl_map_end_icon']
                        )
                        .
                '<br/>'
                ;
        }
        if ($this->plugin->data['iconNotice'] != '') {
            $this->plugin->data['iconNotice'] =
                "<div class='highlight' style='background-color:LightYellow;color:red'><span style='color:red'>".
                    $this->plugin->data['iconNotice'] .
                "</span></div>"
                ;
        }

        //-------------------------
        // Navbar Section
        //-------------------------
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

        //------------------------------------
        // Create The Search Form Settings Panel
        //
        add_action('slp_build_map_settings_panels',array($this,'search_form_settings'   ),10);
        add_action('slp_build_map_settings_panels',array($this,'map_settings'           ),20);
        add_action('slp_build_map_settings_panels',array($this,'results_settings'       ),30);
        add_action('slp_build_map_settings_panels',array($this,'action_AddUXViewSection'),40);

        //------------------------------------
        // Render It
        //
        print $update_msg;
        do_action('slp_build_map_settings_panels');
        $this->settings->render_settings_page();
    }

     /**
      * Create the results settings panel
      *
      */
     function results_settings() {

        $slpDescription =
            $this->plugin->helper->create_SubheadingLabel(__('Search Results','csa-slplus')) .
            $this->CreateInputDiv(
                        '_maxreturned',
                        __('Max Search Results','csa-slplus'),
                        __('How many locations does a search return? Default is 25.','csa-slplus')
                        ).
            $this->plugin->helper->CreateCheckboxDiv(
                    'sl_load_locations_default',
                    __('Immediately Show Locations', 'csa-slplus'),
                    __('Display locations as soon as map loads, based on map center and default radius. ','csa-slplus'),
                    '',
                    false,
                    1
                    ).
            $this->CreateInputDiv(
                    'sl_num_initial_displayed',
                    __('Number To Show Initially','csa-slplus'),
                    __('How many locations should be shown when Immediately Show Locations is checked.  Recommended maximum is 50.','csa-slplus'),
                    ''
                    ).
                $this->CreateInputDiv(
                        'initial_radius',
                        __('Radius To Search Initially','csa-slplus'),
                        __('What should immediately show locations use as the default search radius? Leave empty to use map radius default or set to a large number like 25000 to search everywhere.','csa-slplus') .
                        sprintf(
                            __('Can be set with <a href="%s" target="csa">shortcode attribute initial_radius</a> if Force Load JavaScript is turned off.','csa-slplus'),
                            $this->plugin->url . 'support/documentation/store-locator-plus/shortcodes/'
                        ),
                        '',
                        $this->plugin->options['initial_radius']
                        )
            ;

        // FILTER: slp_settings_results_locationinfo - add input fields to results locaiton info
        //
        $resultSettings['features'] = apply_filters('slp_settings_results_locationinfo',$slpDescription);


        // ===== Labels
        //
        $slpDescription =
                $this->plugin->helper->create_SubheadingLabel(__('Results Labels','csa-slplus')) .
                $this->CreateInputDiv(
                   'sl_website_label',
                   __('Website URL', 'csa-slplus'),
                   __('Search results text for the website link.','csa-slplus'),
                   '',
                   __('Website','csa-slplus')
                   ) .
               $this->CreateInputDiv(
                   '_label_hours',
                   __('Hours', 'csa-slplus'),
                   __('Hours label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   __('Hours','csa-slplus').': '
                   ) .
               $this->CreateInputDiv(
                   '_label_phone',
                   __('Phone', 'csa-slplus'),
                   __('Phone label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   __('Phone','csa-slplus').': '
                   ) .
               $this->CreateInputDiv(
                   '_label_fax',
                   __('Fax', 'csa-slplus'),
                   __('Fax label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   __('Fax','csa-slplus').': '
                   ) .
               $this->CreateInputDiv(
                   '_label_directions',
                   __('Directions', 'csa-slplus'),
                   __('Directions label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   __('Directions','csa-slplus')
                   ) .
               $this->CreateInputDiv(
                   'sl_instruction_message',
                   __('Instructions', 'csa-slplus'),
                   __('Search results instructions shown if immediately show locations is not selected.','csa-slplus'),
                   '',
                   __('Enter an address or zip code and click the find locations button.','csa-slplus')
                   )
                   ;

        // FILTER: slp_settings_results_labels - add input fields to results labels
        //
        $resultSettings['labels'] = apply_filters('slp_settings_results_labels',$slpDescription);

        // TODO: Convert to new panel builder with add_ItemToGroup() in wpCSL (see Tagalong admin panel)
        $resultSections =
            $this->plugin->settings->create_SettingsGroup(
                'result_features',
                __('Results Features','csa-slplus'),
                '',
                $resultSettings['features']
                ).
            $this->plugin->settings->create_SettingsGroup(
                'result_labels',
                __('Results Labels','csa-slplus'),
                '',
                $resultSettings['labels']
                )
                ;

        // Render the results setting
        //
        $this->settings->add_section(
            array(
                    'name'          => __('Results','csa-slplus'),
                    'div_id'        => 'results',
                    'description'   => $resultSections,
                    'auto'          => true,
                    'innerdiv'      => true
                )
         );
     }

    /**
     * Add the search form panel to the map settings page on the admin UI.
     * TODO : Convert this to wpCSL add_ItemToGroup model.
     */
     function search_form_settings() {
        $slpDescription =
            "<div id='search_settings' class='section'>" .
                "<div class='section_column'>" .
                    "<h2>".__('Search Features', 'csa-slplus')."</h2>"
            .

            $this->CreateInputDiv(
                'sl_map_radii',
                __('Radii Options', 'csa-slplus'),
                __('Separate each number with a comma ",". Put parenthesis "( )" around the default.','csa-slplus'),
                '',
                '10,25,50,100,(200),500'
                ) .

            "<div class='form_entry'>" .
                "<label for='sl_distance_unit'>".__('Distance Unit', 'csa-slplus').':</label>' .
                    "<select name='sl_distance_unit'>"
            ;
        foreach (array(
                        __('Kilometers' , 'csa-slplus')=>__('km'    ,'csa-slplus'),
                        __('Miles'      , 'csa-slplus')=>__('miles' ,'csa-slplus'),
                    ) as $key=>$sl_value) {
            $selected=(get_option('sl_distance_unit','miles')==$sl_value)?" selected " : "";
            $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
        }
        $slpDescription .=
                '</select>'.
            '</div>'.
            $this->plugin->helper->CreateCheckboxDiv(
                '_disable_find_image',
                __('Use Find Location Text Button','csa-slplus'),
                __('Use a standard text button for "Find Locations" instead of the provided button images.', 'csa-slplus'),
                SLPLUS_PREFIX,
                false,
                1
                )
                ;

        // FILTER: slp_settings_search_features
        $slpDescription = apply_filters('slp_settings_search_features',$slpDescription) . '</div>';

        // Search Form Labels
        //
        $settingsHTML =
            $this->CreateInputDiv(
                'sl_search_label',
                __('Address', 'csa-slplus'),
                __('Search form address label.','csa-slplus'),
                '',
                __('Address / Zip','csa-slplus')
                ) .
            $this->CreateInputDiv(
                'sl_radius_label',
                __('Radius', 'csa-slplus'),
                __('Search form radius label.','csa-slplus'),
                '',
                __('Within','csa-slplus')
                )
            ;

        // FILTER: slp_settings_search_labels
        $settingsHTML = apply_filters('slp_settings_search_labels',$settingsHTML) . '</div>';
        $slpDescription .= $this->plugin->settings->create_SettingsGroup(
                                'search_labels',
                                __('Search Labels','csa-slplus'),
                                '',
                                $settingsHTML
                                );

        $this->settings->add_section(
            array(
                    'name'          => __('Search Form','csa-slplus'),
                    'div_id'        => 'search',
                    'description'   => apply_filters('slp_map_settings_searchform',$slpDescription),
                    'auto'          => true,
                    'innerdiv'      => true
                )
         );
     }


     //------------------------------------------------------------------------
     // DEPRECATED
     //------------------------------------------------------------------------

     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      */
     function createSettingsGroup() {
        if (!$this->depnotice_createSettingsGroup) {
            $this->plugin->notifications->add_notice(9,$this->plugin->createstring_Deprecated(__FUNCTION__));
            $this->plugin->notifications->display();
            $this->depnotice_createSettingsGroup = true;
        }
     }
}

// Dad. Husband. Rum Lover. Code Geek. Not necessarily in that order.