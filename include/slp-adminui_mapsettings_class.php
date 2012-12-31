<?php

/***********************************************************************
* Class: SLPlus_AdminUI_MapSettings
*
* The Store Locator Plus admin UI Map Settings class.
*
* Provides various UI functions when someone is an admin on the WP site.
*
************************************************************************/

if (! class_exists('SLPlus_AdminUI_MapSettings')) {
    class SLPlus_AdminUI_MapSettings {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        public $parent = null;
        public $settings = null;

        /**
         * Called when this object is created.
         *
         */
        function __construct() {
            if (!$this->setParent()) {
                die('could not set parent');
                return;
                }

            $this->settings = new wpCSL_settings__slplus(
                array(
                        'no_license'        => true,
                        'prefix'            => $this->parent->prefix,
                        'url'               => $this->parent->url,
                        'name'              => $this->parent->name . ' - Map Settings',
                        'plugin_url'        => $this->parent->plugin_url,
                        'render_csl_blocks' => false,
                        'form_action'       => '',
                        'save_text'         => __('Save Settings','csl-slplus')
                    )
             );
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

        //=======================================
        // HELPER FUNCTIONS
        //=======================================

        /**************************************
         ** function: slp_createhelpdiv()
         **
         ** Generate the string that displays the help icon and the expandable div
         ** that mimics the WPCSL-Generic forms more info buttons.
         **
         ** Parameters:
         **  $divname (string, required) - the name of the div to toggle
         **  $msg (string, required) - the message to display
         **/
        function slp_createhelpdiv($divname,$msg) {
            return "<a class='moreinfo_clicker' onclick=\"swapVisibility('".SLPLUS_PREFIX."-help$divname');\" href=\"javascript:;\">".
                '<div class="'.SLPLUS_PREFIX.'-moreicon" title="click for more info"><br/></div>'.
                "</a>".
                "<div id='".SLPLUS_PREFIX."-help$divname' class='input_note' style='display: none;'>".
                    $msg.
                "</div>"
                ;
        }

        /**
         * function: SavePostToOptionsTable
         */
        function SavePostToOptionsTable($optionname,$default=null) {
            if ($default != null) {
                if (!isset($_POST[$optionname])) {
                    $_POST[$optionname] = $default;
                }
            }
            if (isset($_POST[$optionname])) {
                update_option($optionname,$_POST[$optionname]);
            }
        }

        /**************************************
         ** function: SaveCheckboxToDB
         **
         ** Update the checkbox setting in the database.
         **
         ** Parameters:
         **  $boxname (string, required) - the name of the checkbox (db option name)
         **  $prefix (string, optional) - defaults to SLPLUS_PREFIX, can be ''
         **/
        function SaveCheckboxToDB($boxname,$prefix = SLPLUS_PREFIX, $separator='-') {
            $whichbox = $prefix.$separator.$boxname;
            $_POST[$whichbox] = isset($_POST[$whichbox])?1:0;
            $this->SavePostToOptionsTable($whichbox,0);
        }

        /**************************************
        ** function: CreateCheckboxDiv
         **
         ** Update the checkbox setting in the database.
         **
         ** Parameters:
         **  $boxname (string, required) - the name of the checkbox (db option name)
         **  $label (string, optional) - default '', the label to go in front of the checkbox
         **  $message (string, optional) - default '', the help message
         **  $prefix (string, optional) - defaults to SLPLUS_PREFIX, can be ''
         **/
        function CreateCheckboxDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $disabled=false, $default=0) {
            $whichbox = $prefix.$boxname;
            return
                "<div class='form_entry'>".
                    "<div class='".SLPLUS_PREFIX."-input'>" .
                    "<label  for='$whichbox' ".
                        ($disabled?"class='disabled '":' ').
                        ">$label:</label>".
                    "<input name='$whichbox' value='1' ".
                        "type='checkbox' ".
                        ((get_option($whichbox,$default) ==1)?' checked ':' ').
                        ($disabled?"disabled='disabled'":' ') .
                    ">".
                    "</div>".
                    $this->slp_createhelpdiv($boxname,$msg) .
                "</div>"
                ;
        }


        /**
         * function: CreateInputDiv
         */
        function CreateInputDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='') {
            $whichbox = $prefix.$boxname;
            return
                "<div class='form_entry'>" .
                    "<div class='".SLPLUS_PREFIX."-input'>" .
                        "<label for='$whichbox'>$label:</label>".
                        "<input  name='$whichbox' value='".$this->parent->Actions->getCompoundOption($whichbox,$default)."'>".
                    "</div>".
                    $this->slp_createhelpdiv($boxname,$msg).
                 "</div>"
                ;
        }

        /**
         * function: CreatePulldownDiv
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
                        $this->slp_createhelpdiv($boxname,$msg).
                    "</div>"
                    ;

            return $content;
        }

        /**
         * function: CreateTextAreaDiv
         */
        function CreateTextAreaDiv($boxname,$label='',$msg='',$prefix=SLPLUS_PREFIX, $default='') {
            $whichbox = $prefix.$boxname;
            return
                "<div class='form_entry'>" .
                    "<div class='".SLPLUS_PREFIX."-input'>" .
                        "<label for='$whichbox'>$label:</label>".
                        "<textarea  name='$whichbox'>".stripslashes(esc_textarea(get_option($whichbox,$default)))."</textarea>".
                    "</div>".
                    $this->slp_createhelpdiv($boxname,$msg).
                 "</div>"
                ;

        }


        //=======================================
        // RENDER FUNCTIONS
        //=======================================

         /**
          * Add the map panel to the map settings page on the admin UI.
          *
          */
         function map_settings() {
            global $sl_height,$sl_height_units,$sl_width,$sl_width_units,$checked3, $slplus_plugin;

            $slplus_message = ($slplus_plugin->license->packages['Pro Pack']->isenabled) ?
                __('',SLPLUS_PREFIX) :
                __('Extended settings are available in the <a href="%s">%s</a> premium add-on.',SLPLUS_PREFIX)
               ;

            // Features
            //
            $slpDescription =
                "<div class='section_column'>".
                "<div class='map_designer_settings'>".
                "<h2>".__('Features', SLPLUS_PREFIX)."</h2>".
                "<div class='section_column_content'>" .
                '<p class="slp_admin_info"><strong>'.__('Initial Look and Feel',SLPLUS_PREFIX).'</strong></p>' .
                "<div class='form_entry'>" .
                "<label for='sl_remove_credits'>".__('Remove Credits', SLPLUS_PREFIX)."</label>" .
                "<input name='sl_remove_credits' value='1' type='checkbox' $checked3>" .
                "</div>" .
                $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                    '-force_load_js',
                    __('Force Load JavaScript',SLPLUS_PREFIX),
                    __('Force the JavaScript for Store Locator Plus to load on every page with early loading. ' .
                    'This can slow down your site, but is compatible with more themes and plugins.', SLPLUS_PREFIX),
                    SLPLUS_PREFIX,
                    false,
                    1
                    ).
                $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                        'sl_load_locations_default',
                        __('Immediately Show Locations', SLPLUS_PREFIX),
                        __('Display locations as soon as map loads, based on map center and default radius',SLPLUS_PREFIX),
                        ''
                        ).
                $slplus_plugin->AdminUI->MapSettings->CreateInputDiv(
                        'sl_num_initial_displayed',
                        __('Number To Show Initially',SLPLUS_PREFIX),
                        __('How many locations should be shown when Immediately Show Locations is checked.  Recommended maximum is 50.',SLPLUS_PREFIX),
                        ''
                        )
                    ;

                // Pro Pack : Initial Look & Feel
                //
                if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
                        $slpDescription .=
                            $slplus_plugin->AdminUI->MapSettings->CreateInputDiv(
                                'sl_starting_image',
                                __('Starting Image',SLPLUS_PREFIX),
                                __('If set, this image will be displayed until a search is performed.  Enter the full URL for the image.',SLPLUS_PREFIX),
                                ''
                                ) .
                            $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                                '_disable_initialdirectory',
                                __('Disable Initial Directory',SLPLUS_PREFIX),
                                __('Do not display the listings under the map when "immediately show locations" is checked.', SLPLUS_PREFIX)
                                );
                }

                // Features : Country
                $slpDescription .=
                    '<p class="slp_admin_info" style="clear:both;"><strong>'.__('Country',SLPLUS_PREFIX).'</strong></p>' .
                    "<div class='form_entry'>" .
                    "<label for='google_map_domain'>". __("Map Domain", SLPLUS_PREFIX) . "</label>" .
                    "<select name='google_map_domain'>"
                    ;
                foreach ($slplus_plugin->AdminUI->MapSettings->get_map_domains() as $key=>$sl_value) {
                    $selected=(get_option('sl_google_map_domain')==$sl_value)?" selected " : "";
                    $slpDescription .= "<option value='$key:$sl_value' $selected>$key ($sl_value)</option>\n";
                }
                $slpDescription .=
                    "</select></div>" .
                    "<div class='form_entry'>" .
                    "<label for='sl_map_character_encoding'>".__('Character Encoding', SLPLUS_PREFIX)."</label>" .
                    "<select name='sl_map_character_encoding'>"
                    ;
                foreach ($slplus_plugin->AdminUI->MapSettings->get_map_encodings() as $key=>$sl_value) {
                    $selected=(get_option('sl_map_character_encoding')==$sl_value)?" selected " : "";
                    $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
                }
                $slpDescription .= "</select></div></div></div></div>";
                $mapSettings['features'] = apply_filters('slp_map_features_settings',$slpDescription);

                // Settings
                //
                $slpDescription =
                    "<div class='section_column'>" .
                    "<div class='map_designer_settings'>" .
                    "<h2>".__('Settings', SLPLUS_PREFIX)."</h2>" .
                    "<div class='section_column_content'>" .
                    '<p class="slp_admin_info" style="clear:both;"><strong>'.__('Dimensions',SLPLUS_PREFIX).'</strong></p>' .
                    $slplus_plugin->AdminUI->MapSettings->CreatePulldownDiv(
                        'sl_zoom_level',
                        array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                        __('Zoom Level', SLPLUS_PREFIX),
                        __('Initial zoom level of the map if "immediately show locations" is NOT selected or if only a single location is found.  0 = world view, 19 = house view.', SLPLUS_PREFIX),
                        '',
                        4
                        ) .
                    $slplus_plugin->AdminUI->MapSettings->CreatePulldownDiv(
                        'sl_zoom_tweak',
                        array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                        __('Zoom Adjustment', SLPLUS_PREFIX),
                        __('Changes how tight auto-zoom bounds the locations shown.  Lower numbers are closer to the locations.', SLPLUS_PREFIX),
                        '',
                        4
                        ) .
                    "<div class='form_entry'>" .
                    "<label for='height'>".__("Map Height", SLPLUS_PREFIX).":</label>" .
                    "<input name='height' value='$sl_height' class='small'>&nbsp;" .
                    $slplus_plugin->AdminUI->MapSettings->render_unit_selector($sl_height_units, "height_units") .
                    "</div>" .
                    "<div class='form_entry'>" .
                    "<label for='height'>".__("Map Width", SLPLUS_PREFIX).":</label>" .
                    "<input name='width' value='$sl_width' class='small'>&nbsp;" .
                    $slplus_plugin->AdminUI->MapSettings->render_unit_selector($sl_width_units, "width_units") .
                    "</div>" .
                    '<p class="slp_admin_info" style="clear:both;"><strong>'.__('General',SLPLUS_PREFIX).'</strong></p>' .
                    $slplus_plugin->AdminUI->MapSettings->CreatePulldownDiv(
                        'sl_map_type',
                        array('roadmap','hybrid','satellite','terrain'),
                        __('Default Map Type', SLPLUS_PREFIX),
                        __('What style Google Map should we use?', SLPLUS_PREFIX),
                        '',
                        'roadmap'
                        )
                        ;

                // Pro Pack : Map Settings
                //
                if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
                    $slpDescription .=
                        $slplus_plugin->AdminUI->MapSettings->CreateTextAreaDiv(
                                SLPLUS_PREFIX.'_map_center',
                                __('Center Map At',SLPLUS_PREFIX),
                                __('Enter an address to serve as the initial focus for the map. Default is the center of the country.',SLPLUS_PREFIX),
                                ''
                                ) .
                        '<p class="slp_admin_info" style="clear:both;"><strong>'.__('Controls',SLPLUS_PREFIX).'</strong></p>' .
                        $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                            'sl_map_overview_control',
                            __('Show Map Inset Box',SLPLUS_PREFIX),
                            __('When checked the map inset is shown.', SLPLUS_PREFIX),
                            ''
                            ) .
                        $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                            '_disable_scrollwheel',
                            __('Disable Scroll Wheel',SLPLUS_PREFIX),
                            __('Disable the scrollwheel zoom on the maps interface.', SLPLUS_PREFIX)
                            ) .
                        $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                            '_disable_largemapcontrol3d',
                            __('Hide map 3d control',SLPLUS_PREFIX),
                            __('Turn the large map 3D control off.', SLPLUS_PREFIX)
                            ) .
                        $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                            '_disable_scalecontrol',
                            __('Hide map scale',SLPLUS_PREFIX),
                            __('Turn the map scale off.', SLPLUS_PREFIX)
                            ) .
                        $slplus_plugin->AdminUI->MapSettings->CreateCheckboxDiv(
                            '_disable_maptypecontrol',
                            __('Hide map type',SLPLUS_PREFIX),
                            __('Turn the map type selector off.', SLPLUS_PREFIX)
                            )
                        ;
                }
                $slpDescription .= "</div></div></div></div>";
                $mapSettings['settings'] = apply_filters('slp_map_settings_settings',$slpDescription);


            // ===== Icons
            //
            $slpDescription =
                "<div class='section_column'>".
                    "<div class='map_designer_settings'>".
                        "<h2>".__('Icons', SLPLUS_PREFIX)."</h2>".
                        $this->parent->data['iconNotice'] .
                        "<div class='form_entry'>".
                            "<label for='icon'>".__('Home Icon', SLPLUS_PREFIX)."</label>".
                            "<input id='icon' name='icon' dir='rtl' size='45' value='".$this->parent->data['homeicon']."' ".
                                    'onchange="document.getElementById(\'prev\').src=this.value">'.
                            "<img id='prev' src='".$this->parent->data['homeicon']."' align='top'><br/>".
                            $this->parent->data['homeIconPicker'].
                        "</div>".
                        "<div class='form_entry'>".
                            "<label for='icon2'>".__('Destination Icon', SLPLUS_PREFIX)."</label>".
                            "<input id='icon2' name='icon2' dir='rtl' size='45' value='".$this->parent->data['endicon']."' ".
                                'onchange="document.getElementById(\'prev2\').src=this.value">'.
                            "<img id='prev2' src='".$this->parent->data['endicon']."'align='top'><br/>".
                            $this->parent->data['endIconPicker'].
                        "</div>".
                    "</div>"
                ;
            $mapSettings['icons'] = apply_filters('slp_map_icons_settings',$slpDescription);


            $slpDescription =
                "<div id='map_settings'>" .
                    sprintf('<p style="display:block; clear: both;">'.$slplus_message.'</p>',$slplus_plugin->purchase_url,'Pro Pack') .
                    $mapSettings['features'] .
                    $mapSettings['settings'] .
                    $mapSettings['icons'] .
                "</div>"
                ;



            $this->settings->add_section(
                array(
                        'name'          => __('Map',SLPLUS_PREFIX),
                        'description'   => $slpDescription,
                        'auto'          => true
                    )
             );
         }

         /**
          *Render the HTML for the map size units pulldown (%,px,em,pt)
          *
          * @param string $unit
          * @param string $input_name
          * @return string HTML for the pulldown
          */
        function render_unit_selector($unit, $input_name) {
            $unit_arr     = array('%','px','em','pt');
            $select_field = "<select name='$input_name'>";
            foreach ($unit_arr as $sl_value) {
                $selected=($sl_value=="$unit")? " selected='selected' " : "" ;
                $select_field.="\n<option value='$sl_value' $selected>$sl_value</option>";
            }
            $select_field.="</select>";
            return  $select_field;
        }

         /**
          * Return the list of Google map domains.
          * 
          * @return named array - list of domains, key is the name, value is the Google URL
          */
         function get_map_domains() {
             return apply_filters(
                     'slp_map_domains',
                    array(
                        __('United States' )=>'maps.google.com',
                        __('Argentina'     )=>'maps.google.com.ar',
                        __('Australia'     )=>'maps.google.com.au',
                        __('Austria'       )=>'maps.google.at',
                        __('Belgium'       )=>'maps.google.be',
                        __('Brazil'        )=>'maps.google.com.br',
                        __('Canada'        )=>'maps.google.ca',
                        __('Chile'         )=>'maps.google.cl',
                        __('China'         )=>'ditu.google.com',
                        __('Czech Republic')=>'maps.google.cz',
                        __('Denmark'       )=>'maps.google.dk',
                        __('Estonia'       )=> 'maps.google.ee',
                        __('Finland'       )=>'maps.google.fi',
                        __('France'        )=>'maps.google.fr',
                        __('Germany'       )=>'maps.google.de',
                        __('Greece'        )=>'maps.google.gr',
                        __('Hong Kong'     )=>'maps.google.com.hk',
                        __('Hungary'       )=>'maps.google.hu',
                        __('India'         )=>'maps.google.co.in',
                        __('Republic of Ireland')=>'maps.google.ie',
                        __('Italy'         )=>'maps.google.it',
                        __('Japan'         )=>'maps.google.co.jp',
                        __('Liechtenstein' )=>'maps.google.li',
                        __('Mexico'        )=>'maps.google.com.mx',
                        __('Netherlands'   )=>'maps.google.nl',
                        __('New Zealand'   )=>'maps.google.co.nz',
                        __('Norway'        )=>'maps.google.no',
                        __('Poland'        )=>'maps.google.pl',
                        __('Portugal'      )=>'maps.google.pt',
                        __('Russia'        )=>'maps.google.ru',
                        __('Singapore'     )=>'maps.google.com.sg',
                        __('South Africa'  )=>'maps.google.co.za',
                        __('South Korea'   )=>'maps.google.co.kr',
                        __('Spain'         )=>'maps.google.es',
                        __('Sweden'        )=>'maps.google.se',
                        __('Switzerland'   )=>'maps.google.ch',
                        __('Taiwan'        )=>'maps.google.com.tw',
                        __('United Kingdom')=>'maps.google.co.uk',
                        )
                     );
         }

         /**
          * Return the list of Google map character encodings.
          *
          * @return named array - list of encodings, key is the name, value is the Google encoding notation

          */
         function get_map_encodings() {
             return apply_filters(
                     'slp_map_encodings',
                        array(
                        'Default (UTF-8)'                               =>'utf-8',
                        'Western European (ISO-8859-1)'                 =>'iso-8859-1',
                        'Western/Central European (ISO-8859-2)'         =>'iso-8859-2',
                        'Western/Southern European (ISO-8859-3)'        =>'iso-8859-3',
                        'Western European/Baltic Countries (ISO-8859-4)'=>'iso-8859-4',
                        'Russian (Cyrillic)'                            =>'iso-8859-5',
                        'Arabic (ISO-8859-6)'                           =>'iso-8859-6',
                        'Greek (ISO-8859-7)'                            =>'iso-8859-7',
                        'Hebrew (ISO-8859-8)'                           =>'iso-8859-8',
                        'Western European Amended Turkish (ISO-8859-9)' =>'iso-8859-9',
                        'Western European Nordic Characters (ISO-8859-10)'=>'iso-8859-10',
                        'Thai (ISO-8859-11)'                            =>'iso-8859-11',
                        'Baltic languages & Polish (ISO-8859-13)'       =>'iso-8859-13',
                        'Celtic languages (ISO-8859-14)'                =>'iso-8859-14',
                        'Japanese (Shift JIS)'                          =>'shift_jis',
                        'Simplified Chinese (China)(GB 2312)'           =>'gb2312',
                        'Traditional Chinese (Taiwan)(Big 5)'           =>'big5',
                        'Hong Kong (HKSCS)'                             =>'hkscs',
                        'Korea (EUS-KR)'                                =>'eus-kr',
                        )
                    );
         }

         /**
          * Render the map settings admin page.
          */
         function render_adminpage() {
             if (!$this->setParent()) { return; }
             
            if (!$_POST) {
                if (is_a($this->parent->Activate,'SLPlus_Activate')) {
                    $this->parent->Activate->move_upload_directories();
                }
                $update_msg ='';
            } else {
                $sl_google_map_arr=explode(":", $_POST['google_map_domain']);
                update_option('sl_google_map_country', $sl_google_map_arr[0]);
                update_option('sl_google_map_domain', $sl_google_map_arr[1]);

                $_POST['height']=preg_replace('/[^0-9]/', '', $_POST['height']);
                $_POST['width'] =preg_replace('/[^0-9]/', '', $_POST['width']);

                // Height if % set range 0..100
                if ($_POST['height_units'] == '%') {
                    $_POST['height'] = max(0,min($_POST['height'],100));
                }
                update_option('sl_map_height_units', $_POST['height_units']);
                update_option('sl_map_height', $_POST['height']);

                // Width if % set range 0..100
                if ($_POST['width_units'] == '%') {
                    $_POST['width'] = max(0,min($_POST['width'],100));
                }
                update_option('sl_map_width_units', $_POST['width_units']);
                update_option('sl_map_width', $_POST['width']);

                update_option('sl_map_home_icon', $_POST['icon']);
                update_option('sl_map_end_icon', $_POST['icon2']);


                // Text boxes
                //
                $BoxesToHit = array(
                    'sl_language'                           ,
                    'sl_map_character_encoding'             ,
                    'sl_map_radii'                          ,
                    'sl_instruction_message'                ,
                    'sl_zoom_level'                         ,
                    'sl_zoom_tweak'                         ,
                    'sl_map_type'                           ,
                    'sl_num_initial_displayed'              ,
                    'sl_distance_unit'                      ,
                    'sl_name_label'                         ,
                    'sl_radius_label'                       ,
                    'sl_search_label'                       ,
                    'sl_website_label'                      ,

                    SLPLUS_PREFIX.'_label_directions'       ,
                    SLPLUS_PREFIX.'_label_fax'              ,
                    SLPLUS_PREFIX.'_label_hours'            ,
                    SLPLUS_PREFIX.'_label_phone'            ,

                    SLPLUS_PREFIX.'_message_noresultsfound' ,

                    'sl_starting_image'                     ,
                    SLPLUS_PREFIX.'_tag_search_selections'  ,
                    SLPLUS_PREFIX.'_map_center'             ,
                    SLPLUS_PREFIX.'_maxreturned'            ,

                    SLPLUS_PREFIX.'_search_tag_label'       ,
                    SLPLUS_PREFIX.'_state_pd_label'         ,
                    SLPLUS_PREFIX.'_find_button_label'      ,

                    );
                foreach ($BoxesToHit as $JustAnotherBox) {
                    $this->SavePostToOptionsTable($JustAnotherBox);
                }


                // Checkboxes with custom names
                //
                $BoxesToHit = array(
                    SLPLUS_PREFIX.'-force_load_js',
                    'sl_use_city_search',
                    'sl_use_country_search',
                    'sl_load_locations_default',
                    'sl_map_overview_control',
                    'sl_remove_credits',
                    'slplus_show_state_pd',
                    );
                foreach ($BoxesToHit as $JustAnotherBox) {
                    $this->SaveCheckBoxToDB($JustAnotherBox, '','');
                }

                // Checkboxes with normal names
                //
                $BoxesToHit = array(
                    'show_tag_search',
                    'show_tag_any',
                    'email_form',
                    'show_tags',
                    'disable_find_image',
                    'disable_initialdirectory',
                    'disable_largemapcontrol3d',
                    'disable_scalecontrol',
                    'disable_scrollwheel',
                    'disable_search',
                    'disable_maptypecontrol',
                    'hide_radius_selections',
                    'hide_address_entry',
                    'show_search_by_name',
                    'use_email_form',
                    'use_location_sensor',
                    );
                foreach ($BoxesToHit as $JustAnotherBox) {
                    $this->SaveCheckBoxToDB($JustAnotherBox, SLPLUS_PREFIX, '_');
                }

                do_action('slp_save_map_settings');
                $update_msg = "<div class='highlight'>".__("Successful Update", SLPLUS_PREFIX).'</div>';
            }

            //---------------------------
            //
            $this->parent->AdminUI->initialize_variables();

            //-- Set Checkboxes
            //
            $checked2   	    = (isset($checked2)  ?$checked2  :'');
            $sl_city_checked	= (get_option('sl_use_city_search',0) ==1)?' checked ':'';
            $checked3	        = (get_option('sl_remove_credits',0)  ==1)?' checked ':'';

            /**
             * @see http://goo.gl/UAXly - endIcon - the default map marker to be used for locations shown on the map
             * @see http://goo.gl/UAXly - endIconPicker -  the icon selection HTML interface
             * @see http://goo.gl/UAXly - homeIcon - the default map marker to be used for the starting location during a search
             * @see http://goo.gl/UAXly - homeIconPicker -  the icon selection HTML interface
             * @see http://goo.gl/UAXly - iconNotice - the admin panel message if there is a problem with the home or end icon
             * @see http://goo.gl/UAXly - siteURL - get_site_url() WordPress call
             */
            if (!isset($this->parent->data['homeIconPicker'] )) {
                $this->parent->data['homeIconPicker'] = $this->parent->AdminUI->rendorIconSelector('icon','prev');
            }
            if (!isset($this->parent->data['endIconPicker'] )) {
                $this->parent->data['endIconPicker'] = preg_replace('/\.icon\.value/','.icon2.value',$this->parent->data['homeIconPicker']);
                $this->parent->data['endIconPicker'] = preg_replace('/getElementById\("prev"\)/','getElementById("prev2")',$this->parent->data['endIconPicker']);
                $this->parent->data['endIconPicker'] = preg_replace('/getElementById\("icon"\)/','getElementById("icon2")',$this->parent->data['endIconPicker']);
            }

            // Icon is the old path, notify them to re-select
            //
            $this->parent->data['iconNotice'] = '';
            if (!isset($this->parent->data['siteURL'] )) { $this->parent->data['siteURL']  = get_site_url();                  }
            $this->parent->helper->setData(
                      'homeicon',
                      'get_option',
                      array('sl_map_home_icon', SLPLUS_ICONURL . 'sign_yellow_home.png')
                      );
            $this->parent->helper->setData(
                      'endicon',
                      'get_option',
                      array('sl_map_end_icon', SLPLUS_ICONURL . 'a_marker_azure.png')
                      );
            if (!(strpos($this->parent->data['homeicon'],'http')===0)) {
                $this->parent->data['homeicon'] = $this->parent->data['siteURL']. $this->parent->data['homeicon'];
            }
            if (!(strpos($this->parent->data['endicon'],'http')===0)) {
                $this->parent->data['endicon'] = $this->parent->data['siteURL']. $this->parent->data['endicon'];
            }
            if (!$this->parent->helper->webItemExists($this->parent->data['homeicon'])) {
                $this->parent->data['iconNotice'] .=
                    sprintf(
                            __('Your home icon %s cannot be located, please select a new one.', 'csl-slplus'),
                            $this->parent->data['homeicon']
                            )
                            .
                    '<br/>'
                    ;
            }
            if (!$this->parent->helper->webItemExists($this->parent->data['endicon'])) {
                $this->parent->data['iconNotice'] .=
                    sprintf(
                            __('Your destination icon %s cannot be located, please select a new one.', 'csl-slplus'),
                            $this->parent->data['endicon']
                            )
                            .
                    '<br/>'
                    ;
            }
            if ($this->parent->data['iconNotice'] != '') {
                $this->parent->data['iconNotice'] =
                    "<div class='highlight' style='background-color:LightYellow;color:red'><span style='color:red'>".
                        $this->parent->data['iconNotice'] .
                    "</span></div>"
                    ;
            }

            //-------------------------
            // Navbar Section
            //-------------------------
            $this->parent->AdminUI->MapSettings->settings->add_section(
                array(
                    'name'          => 'Navigation',
                    'div_id'        => 'slplus_navbar_wrapper',
                    'description'   => $this->parent->helper->get_string_from_phpexec(SLPLUS_COREDIR.'/templates/navbar.php'),
                    'auto'          => false,
                    'headerbar'     => false,
                    'innerdiv'      => false,
                    'is_topmenu'    => true
                )
            );

            //------------------------------------
            // Create The Search Form Settings Panel
            //
            add_action('slp_build_map_settings_panels',array($this->parent->AdminUI->MapSettings,'search_form_settings') ,10);
            add_action('slp_build_map_settings_panels',array($this->parent->AdminUI->MapSettings,'map_settings')         ,20);
            add_action('slp_build_map_settings_panels',array($this->parent->AdminUI->MapSettings,'results_settings')     ,30);


            //------------------------------------
            // Render It
            //
            print $update_msg;
            do_action('slp_build_map_settings_panels');
            $this->parent->AdminUI->MapSettings->settings->render_settings_page();
        }

         /**
          * Create the results settings panel
          *
          */
         function results_settings() {
            $slplus_message = ($this->parent->license->packages['Pro Pack']->isenabled) ?
                __('',SLPLUS_PREFIX) :
                __('Extended settings are available in the <a href="%s">%s</a> premium add-on.',SLPLUS_PREFIX)
                ;


            // ===== Location Info
            //
            // -- Search Results
            //
            $slpDescription =
                    '<h2>' . __('Location Info',SLPLUS_PREFIX).'</h2>'.
                    '<p class="slp_admin_info" style="clear:both;"><strong>'.__('Search Results',SLPLUS_PREFIX).'</strong></p>' .
                    '<p>'.sprintf($slplus_message,$this->parent->purchase_url,'Pro Pack').'</p>'
                    ;
            $slpDescription .= $this->CreateInputDiv(
                        '_maxreturned',
                        __('Max search results',SLPLUS_PREFIX),
                        __('How many locations does a search return? Default is 25.',SLPLUS_PREFIX)
                        );

            //--------
            // Pro Pack : Search Results Settings
            //
            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $slpDescription .= $this->CreateCheckboxDiv(
                    '_show_tags',
                    __('Show Tags In Output',SLPLUS_PREFIX),
                    __('Show the tags in the location output table and bubble.', SLPLUS_PREFIX)
                    );

                $slpDescription .= $this->CreateCheckboxDiv(
                    '_use_email_form',
                    __('Use Email Form',SLPLUS_PREFIX),
                    __('Use email form instead of mailto: link when showing email addresses.', SLPLUS_PREFIX)
                    );
            }

            // Filter on Results : Search Output Box
            //
            $slpDescription = apply_filters('slp_add_results_settings',$slpDescription);
            $slpDescription =
                "<div class='section_column'>".
                    "<div class='map_designer_settings'>".
                    $slpDescription .
                    "</div>" .
                "</div>"
                ;

            // ===== Labels
            //
            $slpDescription .=
                "<div class='section_column'>" .
                    '<h2>'.__('Labels', 'csl-slplus') . '</h2>' .
                    $this->CreateInputDiv(
                       'sl_website_label',
                       __('Website URL', 'csl-slplus'),
                       __('Search results text for the website link.','csl-slplus'),
                       '',
                       'website'
                       ) .
                   $this->CreateInputDiv(
                       '_label_hours',
                       __('Hours', SLPLUS_PREFIX),
                       __('Hours label.',SLPLUS_PREFIX),
                       SLPLUS_PREFIX,
                       'Hours: '
                       ) .
                   $this->CreateInputDiv(
                       '_label_phone',
                       __('Phone', SLPLUS_PREFIX),
                       __('Phone label.',SLPLUS_PREFIX),
                       SLPLUS_PREFIX,
                       'Phone: '
                       ) .
                   $this->CreateInputDiv(
                       '_label_fax',
                       __('Fax', SLPLUS_PREFIX),
                       __('Fax label.',SLPLUS_PREFIX),
                       SLPLUS_PREFIX,
                       'Fax: '
                       ) .
                   $this->CreateInputDiv(
                       '_label_directions',
                       __('Directions', SLPLUS_PREFIX),
                       __('Directions label.',SLPLUS_PREFIX),
                       SLPLUS_PREFIX,
                       'Directions'
                       ) .
                   $this->CreateInputDiv(
                       'sl_instruction_message',
                       __('Instructions', SLPLUS_PREFIX),
                       __('Search results instructions shown if immediately show locations is not selected.',SLPLUS_PREFIX),
                       '',
                       __('Enter an address or zip code and click the find locations button.',SLPLUS_PREFIX)
                       )
                       ;

            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $slpDescription .= $this->CreateInputDiv(
                        '_message_noresultsfound',
                        __('No Results Message', SLPLUS_PREFIX),
                        __('No results found message that appears under the map.',SLPLUS_PREFIX),
                        SLPLUS_PREFIX,
                        __('Results not found.',SLPLUS_PREFIX)
                        );
            }
            $slpDescription .= '</div>';


            // Render the results setting
            //
            $this->settings->add_section(
                array(
                        'name'          => __('Results',SLPLUS_PREFIX),
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
            global  $sl_city_checked, $sl_country_checked, $sl_show_tag_checked, $sl_show_any_checked,
                $sl_radius_label, $sl_website_label,$sl_the_distance_unit;

            $sl_the_distance_unit[__("Kilometers", SLPLUS_PREFIX)]="km";
            $sl_the_distance_unit[__("Miles", SLPLUS_PREFIX)]="miles";
            $ppFeatureMsg = (!$this->parent->license->packages['Pro Pack']->isenabled ?
                    sprintf(
                            __(' This is a <a href="%s" target="csa">Pro Pack</a> feature.', SLPLUS_PREFIX),
                            $this->parent->purchase_url
                            ) :
                    ''
                 );
            $slplus_message = ($this->parent->license->packages['Pro Pack']->isenabled) ?
                __('',SLPLUS_PREFIX) :
                __('Tag features are available in the <a href="%s">%s</a> premium add-on.',SLPLUS_PREFIX)
                ;

            $slpDescription =
                "<div id='search_settings'>" .
                    "<div class='section_column'>" .
                        "<h2>".__('Features', SLPLUS_PREFIX)."</h2>"
                ;
            $slpDescription .= $this->CreateInputDiv(
                    'sl_map_radii',
                    __('Radii Options', SLPLUS_PREFIX),
                    __('Separate each number with a comma ",". Put parenthesis "( )" around the default.',SLPLUS_PREFIX),
                    '',
                    '10,25,50,100,(200),500'
                    );
            $slpDescription .=
                "<div class='form_entry'>" .
                    "<label for='sl_distance_unit'>".__('Distance Unit', 'csl-slplus').':</label>' .
                        "<select name='sl_distance_unit'>"
                ;

            foreach ($sl_the_distance_unit as $key=>$sl_value) {
                $selected=(get_option('sl_distance_unit')==$sl_value)?" selected " : "";
                $slpDescription .= "<option value='$sl_value' $selected>$key</option>\n";
            }
            $slpDescription .=
                    '</select>'.
                '</div>'
                ;

            $slpDescription .=$this->CreateCheckboxDiv(
                '_hide_radius_selections',
                __('Hide radius selection',SLPLUS_PREFIX),
                __('Hides the radius selection from the user, the default radius will be used.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                '_show_search_by_name',
                __('Show search by name box', SLPLUS_PREFIX),
                __('Shows the name search entry box to the user.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->parent->license->packages['Pro Pack']->isenabled

                );

            $slpDescription .=$this->CreateCheckboxDiv(
                '_hide_address_entry',
                __('Hide address entry box',SLPLUS_PREFIX),
                __('Hides the address entry box from the user.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                '_use_location_sensor',
                __('Use location sensor', SLPLUS_PREFIX),
                __('This turns on the location sensor (GPS) to set the default search address.  This can be slow to load and customers are prompted whether or not to allow location sensing.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                'sl_use_city_search',
                __('Show City Pulldown',SLPLUS_PREFIX),
                __('Displays the city pulldown on the search form. It is built from the unique city names in your location list.',SLPLUS_PREFIX) . $ppFeatureMsg,
                '',
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                'sl_use_country_search',
                __('Show Country Pulldown',SLPLUS_PREFIX),
                __('Displays the country pulldown on the search form. It is built from the unique country names in your location list.',SLPLUS_PREFIX) . $ppFeatureMsg,
                '',
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                'slplus_show_state_pd',
                __('Show State Pulldown',SLPLUS_PREFIX),
                __('Displays the state pulldown on the search form. It is built from the unique state names in your location list.',SLPLUS_PREFIX) . $ppFeatureMsg,
                '',
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                '_disable_search',
                __('Hide Find Locations button',SLPLUS_PREFIX),
                __('Remove the "Find Locations" button from the search form.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX,
                !$this->parent->license->packages['Pro Pack']->isenabled
                );

            $slpDescription .=$this->CreateCheckboxDiv(
                '_disable_find_image',
                __('Use Find Location Text Button',SLPLUS_PREFIX),
                __('Use a standard text button for "Find Locations" instead of the provided button images.', SLPLUS_PREFIX) . $ppFeatureMsg,
                SLPLUS_PREFIX
                );

            ob_start();
            do_action('slp_add_search_form_features_setting');
            $slpDescription .= ob_get_clean();
            $slpDescription .= '</div>';

            /**
             * Tags Section
             */
            $slpDescription .= "<div class='section_column'>";
            $slpDescription .= '<h2>'.__('Tags', 'csl-slplus').'</h2>';
            $slpDescription .= '<div class="section_column_content">';
            $slpDescription .= '<p>'.sprintf($slplus_message,$this->parent->purchase_url,'Pro Pack').'</p>';

            //----------------------------------------------------------------------
            // Pro Pack Enabled
            //
            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $slpDescription .= $this->CreateCheckboxDiv(
                    '_show_tag_search',
                    __('Tag Input',SLPLUS_PREFIX),
                    __('Show the tag entry box on the search form.', SLPLUS_PREFIX)
                    );
                $slpDescription .= $this->CreateInputDiv(
                        '_tag_search_selections',
                        __('Preselected Tag Searches', SLPLUS_PREFIX),
                        __("Enter a comma (,) separated list of tags to show in the search pulldown, mark the default selection with parenthesis '( )'. This is a default setting that can be overriden on each page within the shortcode.",SLPLUS_PREFIX)
                        );

                $slpDescription .= $this->CreateCheckboxDiv(
                    '_show_tag_any',
                    __('Add "any" to tags pulldown',SLPLUS_PREFIX),
                    __('Add an "any" selection on the tag pulldown list thus allowing the user to show all locations in the area, not just those matching a selected tag.', SLPLUS_PREFIX)
                    );
            }


            ob_start();
            do_action('slp_add_search_form_tag_setting');
            $slpDescription .= ob_get_clean();

            $slpDescription .= '</div></div>';

            // Search Form Labels
            //
            $settingsHTML =
                $this->CreateInputDiv(
                    'sl_search_label',
                    __('Address', SLPLUS_PREFIX),
                    __('Search form address label.',SLPLUS_PREFIX),
                    '',
                    'Address / Zip'
                    ) .
                $this->CreateInputDiv(
                    'sl_name_label',
                    __('Name', SLPLUS_PREFIX),
                    __('Search form name label.',SLPLUS_PREFIX),
                    '',
                    'Name'
                    ) .
                $this->CreateInputDiv(
                    'sl_radius_label',
                    __('Radius', SLPLUS_PREFIX),
                    __('Search form radius label.',SLPLUS_PREFIX),
                    '',
                    'Within'
                    )
                ;

            //----------------------------------------------------------------------
            // Pro Pack Enabled
            //
            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $settingsHTML .=
                    $this->CreateInputDiv(
                        '_search_tag_label',
                        __('Tags', 'csl-slplus'),
                        __('Search form label to prefix the tag selector.','csl-slplus')
                        ) .
                    $this->CreateInputDiv(
                        '_state_pd_label',
                        __('State Label', 'csl-slplus'),
                        __('Search form label to prefix the state selector.','csl-slplus')
                        ).
                    $this->CreateInputDiv(
                        '_find_button_label',
                        __('Find Button', 'csl-slplus'),
                        __('The label on the find button, if text mode is selected.','csl-slplus'),
                        SLPLUS_PREFIX,
                        __('Find Locations','csl-slplus')
                        );
            }

            ob_start();
            do_action('slp_add_search_form_label_setting');
            $settingsHTML .= ob_get_clean() . '</div>';

            $slpDescription .= $this->createSettingsGroup(
                                    'search_labels',
                                    __('Labels','csl-slplus'),
                                    '',
                                    $settingsHTML
                                    );

            $this->settings->add_section(
                array(
                        'div_id'        => 'csa_mapsettings_searchform',
                        'name'          => __('Search Form','csl-slplus'),
                        'description'   => apply_filters('slp_map_settings_searchform',$slpDescription),
                        'auto'          => true
                    )
             );
         }

         /**
          * Create a settings group box.
          *
          * @param string $slug - a unique div ID (slug) for this group box, required.  alpha_numeric _ and - only please.
          * @param string $header - the text to put in the header
          * @param string $intro - the text to put directly under the header
          * @param string $content - the settings HTML
          */
         function createSettingsGroup($slug=null, $header='Settings',$intro='',$content='') {
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
    }
}        
     

