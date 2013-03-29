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
     * The SLPlus plugin object.
     *
     * @var SLPlus $plugin
     */
    private $plugin;

    public $settings = null;

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
                    'no_license'        => true,
                    'prefix'            => $this->plugin->prefix,
                    'url'               => $this->plugin->url,
                    'name'              => $this->plugin->name . ' - Map Settings',
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

    //=======================================
    // HELPER FUNCTIONS
    //=======================================

    /**
     * Generate the HTML for an input settings interface element.
     *
     * @param string $boxname
     * @param string $label
     * @param string $msg
     * @param string $prefix
     * @param string $default
     * @return string HTML for the div box.
     */
    function CreateInputDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='') {
        $whichbox = $prefix.$boxname;
        return
            "<div class='form_entry'>" .
                "<div class='".SLPLUS_PREFIX."-input'>" .
                    "<label for='$whichbox'>$label:</label>".
                    "<input  name='$whichbox' value='".$this->plugin->Actions->getCompoundOption($whichbox,$default)."'>".
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
                    "<div class='".SLPLUS_PREFIX."-input'>" .
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
      * Create a settings group box.
      *
      * @param string $slug - a unique div ID (slug) for this group box, required.  alpha_numeric _ and - only please.
      * @param string $header - the text to put in the header
      * @param string $intro - the text to put directly under the header
      * @param string $content - the settings HTML
      * @return string HTML
      */
     function CreateSettingsGroup($slug=null, $header='Settings',$intro='',$content='') {
         if ($slug === null) { return ''; }

         $content =
            "<div class='section_column' id='slp_settings_group-$slug'>" .
                "<h2>$header</h2>" .
                (($intro != '')     ?
                    "<div class='section_column_intro' id='slp_settings_group_intro-$slug'>$intro</div>" :
                    ''
                ).
                (($content != '')   ?
                    "<div class='section_column_content' id='slp_settings_group_content-$slug'>$content</div>" :
                    ''
                ).
            '</div>'
            ;
         return apply_filters('slp_settings_group-'.$slug,$content);
     }

    /**
     * Generate the HTML for a sub-heading label in a settings panel.
     * 
     * @param string $label
     * @return string HTML
     */
    function CreateSubheadingLabel($label) {
        return "<p class='slp_admin_info'><strong>$label</strong></p>";
    }

    /**
     * Generate the HTML for a text area settings interface element.
     * 
     * @param string $boxname
     * @param string $label
     * @param string $msg
     * @param string $prefix
     * @param string $default
     * @return string HTML
     */
    function CreateTextAreaDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='') {
        $whichbox = $prefix.$boxname;
        return
            "<div class='form_entry'>" .
                "<div class='".SLPLUS_PREFIX."-input'>" .
                    "<label for='$whichbox'>$label:</label>".
                    "<textarea  name='$whichbox'>".stripslashes(esc_textarea(get_option($whichbox,$default)))."</textarea>".
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
            $_POST['sl_map_height']=preg_replace('/[^0-9]/', '', $_POST['sl_map_height']);
            if ($_POST['sl_map_height_units'] == '%') {
                $_POST['sl_map_height'] = max(0,min($_POST['sl_map_height'],100));
            }

            // Width, strip non-digtis, if % set range 0..100
            $_POST['sl_map_width'] =preg_replace('/[^0-9]/', '', $_POST['sl_map_width']);
            if ($_POST['sl_map_width_units'] == '%') {
                $_POST['sl_map_width'] = max(0,min($_POST['sl_map_width'],100));
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
                        'sl_name_label'                         ,
                        'sl_radius_label'                       ,
                        'sl_search_label'                       ,
                        'sl_starting_image'                     ,
                        'sl_website_label'                      ,
                        SLPLUS_PREFIX.'_label_directions'       ,
                        SLPLUS_PREFIX.'_label_fax'              ,
                        SLPLUS_PREFIX.'_label_hours'            ,
                        SLPLUS_PREFIX.'_label_phone'            ,
                        SLPLUS_PREFIX.'_tag_search_selections'  ,
                        SLPLUS_PREFIX.'-map_language'           ,
                        SLPLUS_PREFIX.'_maxreturned'            ,
                        SLPLUS_PREFIX.'_search_tag_label'       ,
                        SLPLUS_PREFIX.'_state_pd_label'         ,
                        SLPLUS_PREFIX.'_find_button_label'      ,
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
                        SLPLUS_PREFIX.'_show_tag_search'            ,
                        SLPLUS_PREFIX.'_show_tag_any'               ,
                        SLPLUS_PREFIX.'_email_form'                 ,
                        SLPLUS_PREFIX.'_show_tags'                  ,
                        SLPLUS_PREFIX.'_disable_find_image'         ,
                        SLPLUS_PREFIX.'_disable_initialdirectory'   ,
                        SLPLUS_PREFIX.'_disable_search'             ,
                        SLPLUS_PREFIX.'_hide_radius_selections'     ,
                        SLPLUS_PREFIX.'_hide_address_entry'         ,
                        SLPLUS_PREFIX.'_show_search_by_name'        ,
                        SLPLUS_PREFIX.'_use_email_form'             ,
                        SLPLUS_PREFIX.'_use_location_sensor'        ,
                        SLPLUS_PREFIX.'-force_load_js'              ,
                        'sl_use_city_search'                        ,
                        'sl_use_country_search'                     ,
                        'sl_load_locations_default'                 ,
                        'sl_remove_credits'                         ,
                        'slplus_show_state_pd'                      ,
                        )
                    );
            foreach ($BoxesToHit as $JustAnotherBox) {
                $this->plugin->helper->SaveCheckBoxToDB($JustAnotherBox, '','');
            }
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
            "<div class='section_column_content'>" .

            $this->CreateSubheadingLabel(__('Look and Feel','csa-slplus')) .

            $this->plugin->helper->CreateCheckboxDiv(
                    'sl_remove_credits',
                    __('Remove Credits','csa-slplus'),
                    __('Remove the search provided by tagline under the map.','csa-slplus'),
                    '',
                    false,
                    0
                    ).

            $this->plugin->helper->CreateCheckboxDiv(
                '-force_load_js',
                __('Force Load JavaScript','csa-slplus'),
                __('Force the JavaScript for Store Locator Plus to load on every page with early loading. ' .
                'This can slow down your site, but is compatible with more themes and plugins.', 'csa-slplus'),
                SLPLUS_PREFIX,
                false,
                1
                ).

            $this->plugin->helper->CreateCheckboxDiv(
                    'sl_load_locations_default',
                    __('Immediately Show Locations', 'csa-slplus'),
                    __('Display locations as soon as map loads, based on map center and default radius','csa-slplus'),
                    '',
                    false,
                    0
                    ).

            $this->CreateInputDiv(
                    'sl_num_initial_displayed',
                    __('Number To Show Initially','csa-slplus'),
                    __('How many locations should be shown when Immediately Show Locations is checked.  Recommended maximum is 50.','csa-slplus'),
                    ''
                    )
                ;

            // Pro Pack : Initial Look & Feel
            //
            if ($this->plugin->license->packages['Pro Pack']->isenabled) {
                $slpDescription .=
                    $this->CreateInputDiv(
                        'sl_starting_image',
                        __('Starting Image','csa-slplus'),
                        __('If set, this image will be displayed until a search is performed.  Enter the full URL for the image.','csa-slplus'),
                        ''
                        ) .
                    $this->plugin->helper->CreateCheckboxDiv(
                        '_disable_initialdirectory',
                        __('Disable Initial Directory','csa-slplus'),
                        __('Do not display the listings under the map when "immediately show locations" is checked.', 'csa-slplus')
                        );
            }

            // Features : Country
            $slpDescription .=
                $this->CreateSubheadingLabel(__('Country','csa-slplus')) .
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
            $slpDescription .= "</select></div></div>";
            $mapSettings['features'] = apply_filters('slp_map_features_settings',$slpDescription);

            // Settings
            //
            $slpDescription =
                $this->CreateSubheadingLabel(__('Dimensions','csa-slplus')) .

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
                    ) .

                $this->CreateInputDiv(
                    'sl_map_height',
                    __('Map Height','csa-slplus'),
                    __('The initial map height in pixels or percent of initial page height.','csa-slplus'),
                    '',
                    '480'
                    ) .

                $this->CreatePulldownDiv(
                    'sl_map_height_units',
                    array('%','px','em','pt'),
                    __('Height Units','csa-slplus'),
                    __('Is the width a percentage of page width or absolute pixel size?','csa-slplus'),
                    '',
                    'px'
                    ) .

                $this->CreateInputDiv(
                    'sl_map_width',
                    __('Map Width','csa-slplus'),
                    __('The initial map width in pixels or percent of page width. Also sets results width.','csa-slplus'),
                    '',
                    '640'
                    ) .

                $this->CreatePulldownDiv(
                    'sl_map_width_units',
                    array('%','px','em','pt'),
                    __('Width Units','csa-slplus'),
                    __('Is the width a percentage of page width or absolute pixel size?','csa-slplus'),
                    '',
                    '%'
                    ) .

                $this->CreateSubheadingLabel(__('General','csa-slplus')) .

                $this->CreatePulldownDiv(
                    'sl_map_type',
                    array('roadmap','hybrid','satellite','terrain'),
                    __('Default Map Type', 'csa-slplus'),
                    __('What style Google Map should we use?', 'csa-slplus'),
                    '',
                    'roadmap'
                    )
                    ;

            $slpDescription .= "</div>";


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
                        "<label for='sl_map_home_icon'>".__('Home Icon', 'csa-slplus')."</label>".
                        "<input id='sl_map_home_icon' name='sl_map_home_icon' dir='rtl' size='45' ".
                                "value='".$this->plugin->data['sl_map_home_icon']."' ".
                                'onchange="document.getElementById(\'prev\').src=this.value">'.
                        "<img id='home_icon_preview' src='".$this->plugin->data['sl_map_home_icon']."' align='top'><br/>".
                        $this->plugin->data['homeIconPicker'].
                    "</div>".
                    "<div class='form_entry'>".
                        "<label for='sl_map_end_icon'>".__('Destination Icon', 'csa-slplus')."</label>".
                        "<input id='sl_map_end_icon' name='sl_map_end_icon' dir='rtl' size='45' ".
                            "value='".$this->plugin->data['sl_map_end_icon']."' ".
                            'onchange="document.getElementById(\'prev2\').src=this.value">'.
                        "<img id='end_icon_preview' src='".$this->plugin->data['sl_map_end_icon']."'align='top'><br/>".
                        $this->plugin->data['endIconPicker'] .
                    "</div>".
                    "<br/><p>Saved icons live here: " . SLPLUS_UPLOADDIR . "saved-icons/</p>"
            ;
        $mapSettings['icons'] = apply_filters('slp_map_icons_settings',$slpDescription);


        $slpDescription =
            "<div id='map_settings'>" .
                $this->CreateSettingsGroup(
                                    'map_features',
                                    __('Features','csa-slplus'),
                                    '',
                                    $mapSettings['features']
                                    ) .
                $this->CreateSettingsGroup(
                                    'map_settings',
                                    __('Settings','csa-slplus'),
                                    '',
                                    $mapSettings['settings']
                                    ) .
                $this->CreateSettingsGroup(
                                    'map_icons',
                                    __('Icons','csa-slplus'),
                                    '',
                                    $mapSettings['icons']
                                    ) .
            "</div>"
            ;

        $this->settings->add_section(
            array(
                    'name'          => __('Map','csa-slplus'),
                    'description'   => $slpDescription,
                    'auto'          => true
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
                    __('Republic of Ireland','csa-slplus')=>'maps.google.ie',
                    __('Israel'        ,'csa-slplus')=>'maps.google.co.il',
                    __('Italy'         ,'csa-slplus')=>'maps.google.it',
                    __('Japan'         ,'csa-slplus')=>'maps.google.co.jp',
                    __('Liechtenstein' ,'csa-slplus')=>'maps.google.li',
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
                    __('Taiwan'        ,'csa-slplus')=>'maps.google.com.tw',
                    __('United Kingdom','csa-slplus')=>'maps.google.co.uk',
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
                        __('Gujaratia'                ,'csa-slplus') => 'gu',
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
                        __('Marthi'                   ,'csa-slplus') => 'mr',
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
                        __('Taglog'                   ,'csa-slplus') => 'tl',
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
                        __('Your home icon %s cannot be located, please select a new one.', 'csa-slplus'),
                        $this->plugin->data['sl_map_home_icon']
                        )
                        .
                '<br/>'
                ;
        }
        if (!$this->plugin->helper->webItemExists($this->plugin->data['sl_map_end_icon'])) {
            $this->plugin->data['iconNotice'] .=
                sprintf(
                        __('Your destination icon %s cannot be located, please select a new one.', 'csa-slplus'),
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
        $this->plugin->AdminUI->MapSettings->settings->add_section(
            array(
                'name'          => 'Navigation',
                'div_id'        => 'slplus_navbar_wrapper',
                'description'   => $this->plugin->AdminUI->create_Navbar(),
                'auto'          => false,
                'headerbar'     => false,
                'innerdiv'      => false,
                'is_topmenu'    => true
            )
        );

        //------------------------------------
        // Create The Search Form Settings Panel
        //
        add_action('slp_build_map_settings_panels',array($this,'search_form_settings') ,10);
        add_action('slp_build_map_settings_panels',array($this,'map_settings')         ,20);
        add_action('slp_build_map_settings_panels',array($this,'results_settings')     ,30);

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

        // ===== Location Info
        //
        // -- Search Results
        //
        $slpDescription =
                '<h2>' . __('Location Info','csa-slplus').'</h2>'.
                '<p class="slp_admin_info" style="clear:both;"><strong>'.__('Search Results','csa-slplus').'</strong></p>' 
                ;
        $slpDescription .= $this->CreateInputDiv(
                    '_maxreturned',
                    __('Max search results','csa-slplus'),
                    __('How many locations does a search return? Default is 25.','csa-slplus')
                    );

        //--------
        // Pro Pack : Search Results Settings
        //
        if ($this->plugin->license->packages['Pro Pack']->isenabled) {
            $slpDescription .= $this->plugin->helper->CreateCheckboxDiv(
                '_show_tags',
                __('Show Tags In Output','csa-slplus'),
                __('Show the tags in the location output table and bubble.', 'csa-slplus')
                );

            $slpDescription .= $this->plugin->helper->CreateCheckboxDiv(
                '_use_email_form',
                __('Use Email Form','csa-slplus'),
                __('Use email form instead of mailto: link when showing email addresses.', 'csa-slplus')
                );
        }

        // FILTER: slp_settings_results_locationinfo - add input fields to results locaiton info
        //
        $slpDescription = apply_filters('slp_settings_results_locationinfo',$slpDescription);

        $slpDescription =
            "<div class='section_column' id='results_location_info'>".
                "<div class='map_designer_settings'>".
                $slpDescription .
                "</div>" .
            "</div>"
            ;

        // ===== Labels
        //
        $slpDescription .=
            "<div class='section_column'>" .
                '<h2>'.__('Labels', 'csa-slplus') . '</h2>' .
                $this->CreateInputDiv(
                   'sl_website_label',
                   __('Website URL', 'csa-slplus'),
                   __('Search results text for the website link.','csa-slplus'),
                   '',
                   'website'
                   ) .
               $this->CreateInputDiv(
                   '_label_hours',
                   __('Hours', 'csa-slplus'),
                   __('Hours label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   'Hours: '
                   ) .
               $this->CreateInputDiv(
                   '_label_phone',
                   __('Phone', 'csa-slplus'),
                   __('Phone label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   'Phone: '
                   ) .
               $this->CreateInputDiv(
                   '_label_fax',
                   __('Fax', 'csa-slplus'),
                   __('Fax label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   'Fax: '
                   ) .
               $this->CreateInputDiv(
                   '_label_directions',
                   __('Directions', 'csa-slplus'),
                   __('Directions label.','csa-slplus'),
                   SLPLUS_PREFIX,
                   'Directions'
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
        $slpDescription = apply_filters('slp_settings_results_labels',$slpDescription);


        $slpDescription .= '</div>';


        // Render the results setting
        //
        $this->settings->add_section(
            array(
                    'name'          => __('Results','csa-slplus'),
                    'description'   => $slpDescription,
                    'auto'          => true
                )
         );
     }

    /**
     * Add the search form panel to the map settings page on the admin UI.
     *
     */
     function search_form_settings() {
        $ppFeatureMsg = (!$this->plugin->license->packages['Pro Pack']->isenabled ?
                sprintf(
                        __(' This is a <a href="%s" target="csa">Pro Pack</a> feature.', 'csa-slplus'),
                        $this->plugin->purchase_url
                        ) :
                ''
             );

        $slpDescription =
            "<div id='search_settings'>" .
                "<div class='section_column'>" .
                    "<h2>".__('Features', 'csa-slplus')."</h2>"
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

        $sl_the_distance_unit[__("Kilometers", 'csa-slplus')]="km";
        $sl_the_distance_unit[__("Miles", 'csa-slplus')]="miles";
        foreach ($sl_the_distance_unit as $key=>$sl_value) {
            $selected=(get_option('sl_distance_unit')==$sl_value)?" selected " : "";
            $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
        }
        $slpDescription .=
                '</select>'.
            '</div>'
            ;

        $slpDescription .=
            $this->plugin->helper->CreateCheckboxDiv(
                '_hide_radius_selections',
                __('Hide radius selection','csa-slplus'),
                __('Hides the radius selection from the user, the default radius will be used.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_show_search_by_name',
                __('Show search by name box', 'csa-slplus'),
                __('Shows the name search entry box to the user.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_hide_address_entry',
                __('Hide address entry box','csa-slplus'),
                __('Hides the address entry box from the user.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_use_location_sensor',
                __('Use location sensor', 'csa-slplus'),
                __('This turns on the location sensor (GPS) to set the default search address.  This can be slow to load and customers are prompted whether or not to allow location sensing.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'sl_use_city_search',
                __('Show City Pulldown','csa-slplus'),
                __('Displays the city pulldown on the search form. It is built from the unique city names in your location list.','csa-slplus') . $ppFeatureMsg,
                '',
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'sl_use_country_search',
                __('Show Country Pulldown','csa-slplus'),
                __('Displays the country pulldown on the search form. It is built from the unique country names in your location list.','csa-slplus') . $ppFeatureMsg,
                '',
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'slplus_show_state_pd',
                __('Show State Pulldown','csa-slplus'),
                __('Displays the state pulldown on the search form. It is built from the unique state names in your location list.','csa-slplus') . $ppFeatureMsg,
                '',
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_disable_search',
                __('Hide Find Locations button','csa-slplus'),
                __('Remove the "Find Locations" button from the search form.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->plugin->license->packages['Pro Pack']->isenabled
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_disable_find_image',
                __('Use Find Location Text Button','csa-slplus'),
                __('Use a standard text button for "Find Locations" instead of the provided button images.', 'csa-slplus') . $ppFeatureMsg,
                SLPLUS_PREFIX,
                false,
                1
                )
                ;

        ob_start();
        do_action('slp_add_search_form_features_setting');
        $slpDescription .= ob_get_clean();
        $slpDescription .= '</div>';


        //----------------------------------------------------------------------
        // Pro Pack Enabled
        //
        if ($this->plugin->license->packages['Pro Pack']->isenabled) {
            /**
             * Tags Section
             */
            $slpDescription .= "<div class='section_column'>";
            $slpDescription .= '<h2>'.__('Tags', 'csa-slplus').'</h2>';
            $slpDescription .= '<div class="section_column_content">';
            $slpDescription .= $this->plugin->helper->CreateCheckboxDiv(
                '_show_tag_search',
                __('Tag Input','csa-slplus'),
                __('Show the tag entry box on the search form.', 'csa-slplus')
                );
            $slpDescription .= $this->CreateInputDiv(
                    '_tag_search_selections',
                    __('Preselected Tag Searches', 'csa-slplus'),
                    __("Enter a comma (,) separated list of tags to show in the search pulldown, mark the default selection with parenthesis '( )'. This is a default setting that can be overriden on each page within the shortcode.",'csa-slplus')
                    );

            $slpDescription .= $this->plugin->helper->CreateCheckboxDiv(
                '_show_tag_any',
                __('Add "any" to tags pulldown','csa-slplus'),
                __('Add an "any" selection on the tag pulldown list thus allowing the user to show all locations in the area, not just those matching a selected tag.', 'csa-slplus')
                );
            ob_start();
            do_action('slp_add_search_form_tag_setting');
            $slpDescription .= ob_get_clean();
            $slpDescription .= '</div></div>';
        }

        // Search Form Labels
        //
        $settingsHTML =
            $this->CreateInputDiv(
                'sl_search_label',
                __('Address', 'csa-slplus'),
                __('Search form address label.','csa-slplus'),
                '',
                'Address / Zip'
                ) .
            $this->CreateInputDiv(
                'sl_name_label',
                __('Name', 'csa-slplus'),
                __('Search form name label.','csa-slplus'),
                '',
                'Name'
                ) .
            $this->CreateInputDiv(
                'sl_radius_label',
                __('Radius', 'csa-slplus'),
                __('Search form radius label.','csa-slplus'),
                '',
                'Within'
                )
            ;

        //----------------------------------------------------------------------
        // Pro Pack Enabled
        //
        if ($this->plugin->license->packages['Pro Pack']->isenabled) {
            $settingsHTML .=
                $this->CreateInputDiv(
                    '_search_tag_label',
                    __('Tags', 'csa-slplus'),
                    __('Search form label to prefix the tag selector.','csa-slplus')
                    ) .
                $this->CreateInputDiv(
                    '_state_pd_label',
                    __('State Label', 'csa-slplus'),
                    __('Search form label to prefix the state selector.','csa-slplus')
                    ).
                $this->CreateInputDiv(
                    '_find_button_label',
                    __('Find Button', 'csa-slplus'),
                    __('The label on the find button, if text mode is selected.','csa-slplus'),
                    SLPLUS_PREFIX,
                    __('Find Locations','csa-slplus')
                    );
        }

        ob_start();
        do_action('slp_add_search_form_label_setting');
        $settingsHTML .= ob_get_clean() . '</div>';

        $slpDescription .= $this->CreateSettingsGroup(
                                'search_labels',
                                __('Labels','csa-slplus'),
                                '',
                                $settingsHTML
                                );

        $this->settings->add_section(
            array(
                    'div_id'        => 'csa_mapsettings_searchform',
                    'name'          => __('Search Form','csa-slplus'),
                    'description'   => apply_filters('slp_map_settings_searchform',$slpDescription),
                    'auto'          => true
                )
         );
     }
}
