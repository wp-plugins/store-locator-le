<?php
/**
 * Store Locator Plus User Experience admin user interface.
 *
 * @package StoreLocatorPlus\AdminUI\UserExperience
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
 */
class SLPlus_AdminUI_UserExperience {

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
     * The SLPlus object.
     *
     * @var \SLPlus $slplus
     */
	public $slplus;

	/**
	 * A string array store user notify message
	 *
	 * @var string[] $update_info
	 */
	private $update_info = array();

    /**
     * @var \wpCSL_settings__slplus $settings
     */
	public $settings;


    //-----------------------------
    // Methods
    //-----------------------------

    /**
     * Invoke the User Experience object.
     *
     * @param $params
     */
    function __construct( $params ) {
        foreach ( $params as $property => $value ) {
            if ( property_exists( $this , $property ) ) {
                $this->$property = $value;
            }
        }

        $this->settings = new wpCSL_settings__slplus(
            array(
                    'parent'            => $this->slplus,
                    'prefix'            => $this->slplus->prefix,
                    'url'               => $this->slplus->url,
                    'name'              => $this->slplus->name . __(' - User Experience','csa-slplus'),
                    'plugin_url'        => $this->slplus->plugin_url,
                    'render_csl_blocks' => false,
                    'form_action'       => '',
                    'save_text'         => __('Save Settings','csa-slplus')
                )
         );
    }

    /**
     * Add the UX View Section on the User Experience Tab
     */
    function action_AddUXViewSection() {
        $this->slplus->helper->loadPluginData();
        $sectName = __('View','csa-slplus');
        $this->settings->add_section(array('name' => $sectName));

        // Theme Selector
        //
        $this->slplus->themes->admin->add_settings($this->settings,$sectName,'Style');

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
        $this->slplus->debugMP('slp.main','msg','SLPlus_AdminUI_MapSettings:'.__FUNCTION__);
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
                $this->slplus->helper->CreateHelpDiv($boxname,$msg).
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
                    $this->slplus->helper->CreateHelpDiv($boxname,$msg).
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
            "<div class='form_entry'>" .
                "<div class='wpcsl-input wpcsl-textarea'>" .
                    "<label for='$whichbox'>$label:</label>".
                    "<textarea  name='$whichbox'>{$value}</textarea>".
                "</div>".
                $this->slplus->helper->CreateHelpDiv($boxname,$msg).
             "</div>"
            ;

    }
	
	/**
	 * Save or update custom CSS
	 *
	 * Called when "Save Settings" button is clicked
	 * 
	 * @param string $css_file
	 */
	function save_custom_css( $css_file ) {
		if ( ! is_dir( SLPLUS_UPLOADDIR . "css/" ) ) {
			wp_mkdir_p( SLPLUS_UPLOADDIR . "css/" );
		}
		$this->slplus->createobject_Activation();
		$this->slplus->Activation->copy_newer_files( SLPLUS_PLUGINDIR . "css/$css_file" , SLPLUS_UPLOADDIR . "css/$css_file" );
	}

    /**
     * Execute the save settings action.
     *
     * Called when a $_POST is set when doing render_adminpage.
     */
    function save_settings() {
        $sl_google_map_arr=explode(":", $_POST['google_map_domain']);
        update_option('sl_google_map_country', $sl_google_map_arr[0]);
        $this->slplus->options['map_domain'] = $sl_google_map_arr[1];

		// Set height uint to blank, if height is "auto !important"
		if ($_POST['sl_map_height'] === "auto !important" && $_POST['sl_map_height_units'] != "") {
			$_POST['sl_map_height_units'] = "";
			array_push($this->update_info, __("Auto set height unit to blank when height is 'auto !important'", 'csa-slplus'));
		}
		// Set weight uint to blank, if height is "auto !important"
		if ($_POST['sl_map_width'] === "auto !important" && $_POST['sl_map_width_units'] != "") {
			$_POST['sl_map_width_units'] = "";
			array_push($this->update_info, __("Auto set width unit to blank when width is 'auto !important'", 'csa-slplus'));
		}
		
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
                    'label_email'                           ,
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
                    'sl_radius_label'                       ,
                    'sl_search_label'                       ,
                    'sl_website_label'                      ,
                    SLPLUS_PREFIX.'_label_directions'       ,
                    SLPLUS_PREFIX.'_label_fax'              ,
                    SLPLUS_PREFIX.'_label_hours'            ,
                    SLPLUS_PREFIX.'_label_phone'            ,
                    SLPLUS_PREFIX.'_map_center'             ,
                    SLPLUS_PREFIX.'-map_language'           ,
                    SLPLUS_PREFIX.'-theme'                  ,
                )
            );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->slplus->helper->SavePostToOptionsTable($JustAnotherBox);
		}
		// Register need translate text to WPML
		//
		$BoxesToHit =
            apply_filters('slp_regwpml_map_settings_inputs',
                array(
                    'sl_radius_label'                       ,
                    'sl_search_label'                       ,
                )
            );
		foreach ($BoxesToHit as $JustAnotherBox) {
            $this->slplus->AdminWPML->regPostOptions($JustAnotherBox);
		}
        // Checkboxes
        //
        $BoxesToHit =
            apply_filters('slp_save_map_settings_checkboxes',
                array(
                    SLPLUS_PREFIX.'_disable_find_image'         ,
                    'sl_remove_credits'                         ,
                    )
                );
        $this->slplus->debugMP('slp.mapsettings','pr','save_settings() Checkboxes',$BoxesToHit,NULL,NULL,true);
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->slplus->helper->SaveCheckBoxToDB($JustAnotherBox, '','');
        }
        
        // Serialized Checkboxes, Need To Blank If Not Received
        //
        $BoxesToHit = array(
            'immediately_show_locations' ,
            );
        foreach ($BoxesToHit as $BoxName) {
            if (!isset($_REQUEST[$BoxName])) {
                $_REQUEST[$BoxName] = '0';
            }
        }        

        // Serialized Options Setting
        // This should be used for ALL new options.
        // Serialized options = ONE data I/O call, MUCH FASTER!!!
        //
        array_walk($_REQUEST,array($this->slplus,'set_ValidOptions'));
        update_option(SLPLUS_PREFIX.'-options', $this->slplus->options);

        array_walk($_REQUEST,array($this->slplus,'set_ValidOptionsNoJS'));
        update_option(SLPLUS_PREFIX.'-options_nojs', $this->slplus->options_nojs);
		
		// Save or Update a copy of the css file to the uploads\slp\css dir
		$this->save_custom_css($_POST['csl-slplus-theme'] . ".css");
		
    }

    //=======================================
    // RENDER FUNCTIONS
    //=======================================

     /**
      * Add the map panel to the map settings page on the admin UI.
      *
      */
     function map_settings() {
        $this->slplus->helper->loadPluginData();

        // Features
        //
        $slpDescription =
            $this->slplus->helper->create_SubheadingLabel(__('Look and Feel','csa-slplus')) .
                
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
            $this->slplus->helper->CreateCheckboxDiv(
                    'sl_remove_credits',
                    __('Remove Credits','csa-slplus'),
                    __('Remove the search provided by tagline under the map.','csa-slplus'),
                    '',
                    false,
                    0
                    ) .
            $this->CreateTextAreaDiv(
                    SLPLUS_PREFIX.'_map_center',
                    __('Center Map At','csa-slplus'),
                    __('Enter an address to serve as the initial focus for the map. '                                   ,'csa-slplus') .
                    __('Default is the center of the country.'                                                          ,'csa-slplus') .
                    __('Enhanced Map add-on must be installed to set per-page with center_map_at="address" shortcode. ' ,'csa-slplus') .
                    __('Force JavaScript setting must be off when using the shortcode attribute. '                      ,'csa-slplus') ,
                    ''
                    )
                ;

            $mapSettings['features'] = apply_filters('slp_map_features_settings',$slpDescription);

            // Settings
            //
            $slpDescription =
                $this->slplus->helper->create_SubheadingLabel(__('Behavior','csa-slplus'))
                ;
            
            $slpDescription .=
                "<div class='form_entry'>" .
                "<label for='google_map_domain'>". __("Map Domain", 'csa-slplus') . "</label>" .
                "<select name='google_map_domain'>"
                ;
                foreach ($this->get_map_domains() as $key=>$sl_value) {
                    $selected = ( $this->slplus->options['map_domain'] == $sl_value ) ? ' selected ' : '';
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
                    $selected=($this->slplus->helper->getData('map_language','get_item',null,'en')==$sl_value)?" selected " : "";
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
                    $this->slplus->data['iconNotice'] .
                    "<div class='form_entry'>".
                        "<label for='sl_map_home_icon'>".__('Home Marker', 'csa-slplus')."</label>".
                        "<input id='sl_map_home_icon' name='sl_map_home_icon' dir='rtl' size='45' ".
                                "value='".$this->slplus->data['sl_map_home_icon']."' ".
                                'onchange="document.getElementById(\'prev\').src=this.value">'.
                        "<img id='home_icon_preview' src='".$this->slplus->data['sl_map_home_icon']."' align='top'><br/>".
                        $this->slplus->data['homeIconPicker'].
                    "</div>".
                    "<div class='form_entry'>".
                        "<label for='sl_map_end_icon'>".__('Destination Marker', 'csa-slplus')."</label>".
                        "<input id='sl_map_end_icon' name='sl_map_end_icon' dir='rtl' size='45' ".
                            "value='".$this->slplus->data['sl_map_end_icon']."' ".
                            'onchange="document.getElementById(\'prev2\').src=this.value">'.
                        "<img id='end_icon_preview' src='".$this->slplus->data['sl_map_end_icon']."'align='top'><br/>".
                        $this->slplus->data['endIconPicker'] .
                    "</div>".
                    '<br/><p>'.
                    __('Saved markers live here: ','csa-slplus') . SLPLUS_UPLOADDIR . "saved-icons/</p>"
            ;
        $mapSettings['icons'] = apply_filters('slp_map_icons_settings',$slpDescription);

        // TODO: Convert to new panel builder with add_ItemToGroup() in wpCSL (see Tagalong admin panel)
        $mapSections =
                $this->slplus->settings->create_SettingsGroup(
                                    'map_features',
                                    __('Map Features','csa-slplus'),
                                    '',
                                    $mapSettings['features']
                                    ) .
                $this->slplus->settings->create_SettingsGroup(
                                    'map_settings',
                                    __('Map Settings','csa-slplus'),
                                    '',
                                    $mapSettings['settings']
                                    ) .
                $this->slplus->settings->create_SettingsGroup(
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
                    __('United States'                  ,'csa-slplus')=>'maps.google.com',
                    __('Algeria'                        ,'csa-slplus')=>'maps.google.dz',
                    __('American Samoa'                 ,'csa-slplus')=>'maps.google.as',
                    __('Andorra'                        ,'csa-slplus')=>'maps.google.ad',
                    __('Angola'                         ,'csa-slplus')=>'maps.google.co.ao',
                    __('Antigua and Barbuda'            ,'csa-slplus')=>'maps.google.com.ag',
                    __('Argentina'                      ,'csa-slplus')=>'maps.google.com.ar',
                    __('Australia'                      ,'csa-slplus')=>'maps.google.com.au',
                    __('Austria'                        ,'csa-slplus')=>'maps.google.at',
                    __('Bahamas'                        ,'csa-slplus')=>'maps.google.bs',
                    __('Bahrain'                        ,'csa-slplus')=>'maps.google.com.bh',
                    __('Bandladesh'                     ,'csa-slplus')=>'maps.google.com.bd',
                    __('Belgium'                        ,'csa-slplus')=>'maps.google.be',
                    __('Belize'                         ,'csa-slplus')=>'maps.google.com.bz',
                    __('Benin'                          ,'csa-slplus')=>'maps.google.bj',
                    __('Bolivia'                        ,'csa-slplus')=>'maps.google.com.bo',
                    __('Botswana'                       ,'csa-slplus')=>'maps.google.co.bw',
                    __('Brazil'                         ,'csa-slplus')=>'maps.google.com.br',
                    __('Bulgaria'                       ,'csa-slplus')=>'maps.google.bg',
                    __('Burundi'                        ,'csa-slplus')=>'maps.google.bi',
                    __('Cameroon'                       ,'csa-slplus')=>'maps.google.cm',
                    __('Canada'                         ,'csa-slplus')=>'maps.google.ca',
                    __('Central African Republic'       ,'csa-slplus')=>'maps.google.cf',
                    __('Chile'                          ,'csa-slplus')=>'maps.google.cl',
                    __('China'                          ,'csa-slplus')=>'ditu.google.com',
                    __('Congo'                          ,'csa-slplus')=>'maps.google.cg',
                    __('Czech Republic'                 ,'csa-slplus')=>'maps.google.cz',
                    __('Democratic Republic of Congo'   ,'csa-slplus')=>'maps.google.cd',
                    __('Denmark'                        ,'csa-slplus')=>'maps.google.dk',
                    __('Djibouti'                       ,'csa-slplus')=>'maps.google.dj',
                    __('Ecuador'                        ,'csa-slplus')=> 'maps.google.com.ec',
                    __('Estonia'                        ,'csa-slplus')=> 'maps.google.ee',
                    __('Ethiopia'                       ,'csa-slplus')=> 'maps.google.com.et',
                    __('Finland'                        ,'csa-slplus')=>'maps.google.fi',
                    __('France'                         ,'csa-slplus')=>'maps.google.fr',
                    __('Gabon'                          ,'csa-slplus')=>'maps.google.ga',
                    __('Gambia'                         ,'csa-slplus')=>'maps.google.gm',
                    __('Germany'                        ,'csa-slplus')=>'maps.google.de',
                    __('Ghana'                          ,'csa-slplus')=>'maps.google.com.gh',
                    __('Greece'                         ,'csa-slplus')=>'maps.google.gr',
                    __('Guatemala'                      ,'csa-slplus')=>'maps.google.com.gt',
                    __('Guyana'                         ,'csa-slplus')=>'maps.google.gy',
                    __('Hong Kong'                      ,'csa-slplus')=>'maps.google.com.hk',
                    __('Hungary'                        ,'csa-slplus')=>'maps.google.hu',
                    __('India'                          ,'csa-slplus')=>'maps.google.co.in',
                    __('Indonesia'                      ,'csa-slplus')=>'maps.google.co.id',
                    __('Israel'                         ,'csa-slplus')=>'maps.google.co.il',
                    __('Italy'                          ,'csa-slplus')=>'maps.google.it',
                    __('Japan'                          ,'csa-slplus')=>'maps.google.co.jp',
                    __('Kenya'                          ,'csa-slplus')=>'maps.google.co.ke',
                    __('Lesotho'                        ,'csa-slplus')=>'maps.google.co.ls',
                    __('Liechtenstein'                  ,'csa-slplus')=>'maps.google.li',
                    __('Lithuania'                      ,'csa-slplus')=>'maps.google.lt',
                    __('Macedonia'                      ,'csa-slplus')=>'maps.google.mk',
                    __('Madagascar'                     ,'csa-slplus')=>'maps.google.mg',
                    __('Malawi'                         ,'csa-slplus')=>'maps.google.mw',
                    __('Malaysia'                       ,'csa-slplus')=>'maps.google.my',
                    __('Mauritius'                      ,'csa-slplus')=>'maps.google.mu',
                    __('Mexico'                         ,'csa-slplus')=>'maps.google.mx',
                    __('Mozambique'                     ,'csa-slplus')=>'maps.google.co.mz',
                    __('Namibia'                        ,'csa-slplus')=>'maps.google.co.na',
                    __('Netherlands'                    ,'csa-slplus')=>'maps.google.nl',
                    __('New Zealand'                    ,'csa-slplus')=>'maps.google.co.nz',
                    __('Nigeria'                        ,'csa-slplus')=>'maps.google.com.ng',
                    __('Norway'                         ,'csa-slplus')=>'maps.google.no',
                    __('Paraguay'                       ,'csa-slplus')=>'maps.google.com.py',
                    __('Peru'                           ,'csa-slplus')=>'maps.google.com.pe',
                    __('Philippines'                    ,'csa-slplus')=>'maps.google.com.ph',
                    __('Poland'                         ,'csa-slplus')=>'maps.google.pl',
                    __('Portugal'                       ,'csa-slplus')=>'maps.google.pt',
                    __('Republic of Ireland'            ,'csa-slplus')=>'maps.google.ie',
                    __('Romania'                        ,'csa-slplus')=>'maps.google.ro',
                    __('Russia'                         ,'csa-slplus')=>'maps.google.ru',
                    __('Rwanda'                         ,'csa-slplus')=>'maps.google.rw',
                    __('Sao Tome and Principe'          ,'csa-slplus')=>'maps.google.st',
                    __('Senegal'                        ,'csa-slplus')=>'maps.google.sn',
                    __('Seychelles'                     ,'csa-slplus')=>'maps.google.sc',
                    __('Sierra Leone'                   ,'csa-slplus')=>'maps.google.sl',
                    __('Singapore'                      ,'csa-slplus')=>'maps.google.com.sg',
                    __('South Africa'                   ,'csa-slplus')=>'maps.google.co.za',
                    __('South Korea'                    ,'csa-slplus')=>'maps.google.co.kr',
                    __('Spain'                          ,'csa-slplus')=>'maps.google.es',
                    __('Sri Lanka'                      ,'csa-slplus')=>'maps.google.lk',
                    __('Sweden'                         ,'csa-slplus')=>'maps.google.se',
                    __('Switzerland'                    ,'csa-slplus')=>'maps.google.ch',
                    __('Taiwan'                         ,'csa-slplus')=>'maps.google.com.tw',
                    __('Tanzania'                       ,'csa-slplus')=>'maps.google.co.tz',
                    __('Thailand'                       ,'csa-slplus')=>'maps.google.co.th',
                    __('Togo'                           ,'csa-slplus')=>'maps.google.tg',
                    __('Uganda'                         ,'csa-slplus')=>'maps.google.co.ug',
                    __('United Arab Emirates'           ,'csa-slplus')=>'maps.google.ae',
                    __('United Kingdom'                 ,'csa-slplus')=>'maps.google.co.uk',
                    __('Uruguay'                        ,'csa-slplus')=>'maps.google.com.uy',
                    __('Venezuela'                      ,'csa-slplus')=>'maps.google.co.ve',
                    __('Zambia'                         ,'csa-slplus')=>'maps.google.co.zm',
                    __('Zimbabwe'                       ,'csa-slplus')=>'maps.google.co.zw',
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
        $matches = array();
        if (preg_match('/^(.*?)\[(.*?)\]/',$optionName,$matches) === 1) {
            if (!isset($this->slplus->mapsettingsData[$matches[1]])) {
                $this->slplus->mapsettingsData[$matches[1]] = get_option($matches[1],$default);
            }
            return
                isset($this->slplus->mapsettingsData[$matches[1]][$matches[2]]) ?
                $this->slplus->mapsettingsData[$matches[1]][$matches[2]] :
                ''
                ;

        } else {
            return $this->slplus->helper->getData($optionName,'get_option',array($optionName,$default));
        }
    }


     /**
      * Render the map settings admin page.
      */
     function render_adminpage() {
        $update_msg ='';

        // We Have a POST - Save Settings
        //
        if ($_POST) {
            add_action('slp_save_map_settings',array($this,'save_settings') ,10);
            do_action('slp_save_map_settings');
            do_action('slp_save_ux_settings');
			$update_msg = "<div class='highlight'>".__('Successful Update', 'csa-slplus');
			foreach( $this->update_info as $info_msg)
				$update_msg	.= '<br/>'.$info_msg;
			$update_msg	.= '</div>';
        }

        // Initialize Plugin Settings Data
        //
        $this->slplus->AdminUI->initialize_variables();
        $this->slplus->helper->loadPluginData();

        /**
         * @see http://goo.gl/UAXly - endIcon - the default map marker to be used for locations shown on the map
         * @see http://goo.gl/UAXly - endIconPicker -  the icon selection HTML interface
         * @see http://goo.gl/UAXly - homeIcon - the default map marker to be used for the starting location during a search
         * @see http://goo.gl/UAXly - homeIconPicker -  the icon selection HTML interface
         * @see http://goo.gl/UAXly - iconNotice - the admin panel message if there is a problem with the home or end icon
         * @see http://goo.gl/UAXly - siteURL - get_site_url() WordPress call
         */
        if (!isset($this->slplus->data['homeIconPicker'] )) {
            $this->slplus->data['homeIconPicker'] = $this->slplus->AdminUI->CreateIconSelector('sl_map_home_icon','home_icon_preview');
        }
        if (!isset($this->slplus->data['endIconPicker'] )) {
            $this->slplus->data['endIconPicker'] = $this->slplus->AdminUI->CreateIconSelector('sl_map_end_icon','end_icon_preview');
        }

        // Icon is the old path, notify them to re-select
        //
        $this->slplus->data['iconNotice'] = '';
        if (!isset($this->slplus->data['siteURL'] )) { $this->slplus->data['siteURL']  = get_site_url();                  }
        if (!(strpos($this->slplus->data['sl_map_home_icon'],'http')===0)) {
            $this->slplus->data['sl_map_home_icon'] = $this->slplus->data['siteURL']. $this->slplus->data['sl_map_home_icon'];
        }
        if (!(strpos($this->slplus->data['sl_map_end_icon'],'http')===0)) {
            $this->slplus->data['sl_map_end_icon'] = $this->slplus->data['siteURL']. $this->slplus->data['sl_map_end_icon'];
        }
        if (!$this->slplus->helper->webItemExists($this->slplus->data['sl_map_home_icon'])) {
            $this->slplus->data['iconNotice'] .=
                sprintf(
                        __('Your home marker %s cannot be located, please select a new one.', 'csa-slplus'),
                        $this->slplus->data['sl_map_home_icon']
                        )
                        .
                '<br/>'
                ;
        }
        if (!$this->slplus->helper->webItemExists($this->slplus->data['sl_map_end_icon'])) {
            $this->slplus->data['iconNotice'] .=
                sprintf(
                        __('Your destination marker %s cannot be located, please select a new one.', 'csa-slplus'),
                        $this->slplus->data['sl_map_end_icon']
                        )
                        .
                '<br/>'
                ;
        }
        if ($this->slplus->data['iconNotice'] != '') {
            $this->slplus->data['iconNotice'] =
                "<div class='highlight' style='background-color:LightYellow;color:red'><span style='color:red'>".
                    $this->slplus->data['iconNotice'] .
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
                'description'   => $this->slplus->AdminUI->create_Navbar(),
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
        $this->slplus->AdminUI->render_rate_box();
    }

     /**
      * Create the results settings panel
      *
      */
     function results_settings() {

         // Add Results Section
         //
         $section_name = __('Results','csa-slplus');
         $this->settings->add_section(
             array(
                 'name'          => $section_name,
                 'div_id'        => 'results',
             )
         );

         // Add Results Features Group
         //
        $slpDescription =
            $this->slplus->helper->create_SubheadingLabel(__('Search Results','csa-slplus')) .
            $this->CreateInputDiv(
                        'max_results_returned',
                        __('Max Search Results','csa-slplus'),
                        __('How many locations does a search return? Default is 25.','csa-slplus'),
                        '',
                        $this->slplus->options_nojs['max_results_returned']
                        ).
            $this->slplus->helper->CreateCheckboxDiv(
                    'immediately_show_locations',
                    __('Immediately Show Locations', 'csa-slplus'),
                    __('Display locations as soon as map loads, based on map center and default radius. Enhanced Search provides [slplus immediately_show_locations="true|false"] attribute option.','csa-slplus'),
                    '',
                    false,
                    $this->slplus->options['immediately_show_locations']
                    ).
            $this->CreateInputDiv(
                    'initial_results_returned',
                    __('Number To Show Initially','csa-slplus'),
                    __('How many locations should be shown when Immediately Show Locations is checked.  Recommended maximum is 50.','csa-slplus'),
                    '',
                    $this->slplus->options['initial_results_returned']
                    ).
            $this->CreateInputDiv(
                    'initial_radius',
                    __('Radius To Search Initially','csa-slplus'),
                    __('What should immediately show locations use as the default search radius? Leave empty to use map radius default or set to a large number like 25000 to search everywhere.','csa-slplus') .
                    sprintf(
                        __('Can be set with <a href="%s" target="csa">shortcode attribute initial_radius</a> if Force Load JavaScript is turned off.','csa-slplus'),
                        $this->slplus->url . 'support/documentation/store-locator-plus/shortcodes/'
                    ),
                    '',
                    $this->slplus->options['initial_radius']
                    )
            ;

        // FILTER: slp_settings_results_locationinfo - add input fields to results locaiton info
        //
        $resultSettings['features'] = apply_filters('slp_settings_results_locationinfo',$slpDescription);

         $this->settings->add_ItemToGroup(
             array(
                 'section'       => $section_name,
                 'group'         => __('Results Features','csa-slplus'),
                 'label'         => '',
                 'type'          => 'custom',
                 'show_label'    => false,
                 'custom'   => $resultSettings['features']
             )
         );


        // ===== Labels
        //
        $slpDescription =
                $this->slplus->helper->create_SubheadingLabel(__('Results Labels','csa-slplus')) .

                $this->CreateInputDiv(
                    'sl_website_label',
                    __('Website URL', 'csa-slplus'),
                    __('Search results text for the website link.','csa-slplus') .
                        __('Changes the administrative Manage Locations column header and column content as well. ','csa-slplus')
                        ,
                    '',
                    __('Website','csa-slplus')
                    ) .

               $this->CreateInputDiv(
                    '_label_hours',
                    __('Hours', 'csa-slplus'),
                    __('Hours label.','csa-slplus') .
                       __('Changes the administrative Manage Locations column header and column content as well. ','csa-slplus')
                        ,
                    SLPLUS_PREFIX,
                    __('Hours','csa-slplus').': '
                    ) .

               $this->CreateInputDiv(
                    '_label_phone',
                    __('Phone', 'csa-slplus'),
                    __('Phone label.','csa-slplus') .
                        __('Changes the administrative Manage Locations column header and column content as well. ','csa-slplus')
                        ,
                    SLPLUS_PREFIX,
                    __('Phone','csa-slplus').': '
                    ) .

               $this->CreateInputDiv(
                    '_label_fax',
                    __('Fax', 'csa-slplus'),
                    __('Fax label.','csa-slplus')        .
                       __('Changes the administrative Manage Locations column header and column content as well. ','csa-slplus')
                       ,
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
                    'label_email',
                    __('Email', 'csa-slplus'),
                    __('What to put on the search results in place of an email address. ','csa-slplus') .
                        __('Changes the administrative Manage Locations column header and column content as well. ','csa-slplus')
                        ,
                    '',
                    __('Email','csa-slplus')
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

         $this->settings->add_ItemToGroup(
             array(
                 'section'       => $section_name,
                 'group'         => __('Results Labels','csa-slplus'),
                 'label'         => '',
                 'type'          => 'subheader',
                 'show_label'    => false,
                 'description'   => $resultSettings['labels']
             )
         );

         // ACTION: slp_ux_modify_adminpanel_results
         //    params: settings object, section name
         //
         do_action( 'slp_ux_modify_adminpanel_results' , $this->settings , $section_name );
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
                "<label for='distance_unit'>".__('Distance Unit', 'csa-slplus').':</label>' .
                    "<select name='distance_unit'>"
            ;

        foreach (array(
                        __('Kilometers' , 'csa-slplus')=>__('km'    ,'csa-slplus'),
                        __('Miles'      , 'csa-slplus')=>__('miles' ,'csa-slplus'),
                    ) as $key=>$sl_value) {
            $selected=($this->slplus->options['distance_unit']==$sl_value)?" selected " : "";
            $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
        }

        $slpDescription .=
                '</select>'.
            '</div>'.
            $this->slplus->helper->CreateCheckboxDiv(
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
        $slpDescription .= $this->slplus->settings->create_SettingsGroup(
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
            $this->slplus->notifications->add_notice(9,$this->slplus->createstring_Deprecated(__FUNCTION__));
            $this->slplus->notifications->display();
            $this->depnotice_createSettingsGroup = true;
        }
     }
}

// Dad. Explorer. Rum Lover. Code Geek. Not necessarily in that order.
