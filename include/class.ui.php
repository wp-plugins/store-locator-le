<?php

/**
 * Store Locator Plus basic user interface.
 *
 * @package StoreLocatorPlus\UI
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 */
class SLPlus_UI {

    //-------------------------------------
    // Properties
    //-------------------------------------

    /**
     * Has the setResultsString deprecation notice been shown already?
     *
     * @var boolean $depnotice_setResultsString
     */
    private  $depnotice_setResultsString = false;

    /**
     * Name of this module.
     *
     * @var string $name
     */
    private $name;

    //----------------------------------
    // Methods
    //----------------------------------

    /**
     * Instantiate the UI Class.
     *
     * @param mixed[] $params
     */
    function __construct($params = null) {
        $this->name = 'UI';

        // Do the setting override or initial settings.
        //
        if ($params != null) {
            foreach ($params as $name => $sl_value) {
                $this->$name = $sl_value;
            }
        }
    }

    /**
     * Set the plugin property to point to the primary plugin object.
     *
     * Returns false if we can't get to the main plugin object.
     *
     * @global SLPlus $slplus_plugin
     * @return boolean true if plugin property is valid
     */
    function setPlugin() {
        if (!isset($this->plugin) || ($this->plugin == null)) {
            global $slplus_plugin;
            $this->plugin = $slplus_plugin;
            $this->plugin->register_module($this->name,$this);
        }
        return (isset($this->plugin) && ($this->plugin != null));
    }

    /**
     * Return the HTML for a slider button.
     *
     * The setting parameter will be used for several things:
     * the div ID will be "settingid_div"
     * the assumed matching label option will be "settingid_label" for WP get_option()
     * the a href ID will be "settingid_toggle"
     *
     * @param string $setting the ID for the setting
     * @param string $label the default label to show
     * @param boolean $isChecked default on/off state of checkbox
     * @param string $onClick the onClick javascript
     * @return string the slider HTML
     */
    function CreateSliderButton($setting=null, $label='', $isChecked = true, $onClick='') {
        if ($setting === null) { return ''; }
        if (!$this->setPlugin()) { return ''; }

        $label   = $this->plugin->settings->get_item($setting.'_label',$label);
        $checked = ($isChecked ? 'checked' : '');
        $onClick = (($onClick === '') ? '' : ' onClick="'.$onClick.'"');

        $content =
            "<div id='{$setting}_div' class='onoffswitch-block'>" .
            "<span class='onoffswitch-pretext'>$label</span>" .
            "<div class='onoffswitch'>" .
            "<input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' id='{$setting}-checkbox' $checked>" .
            "<label class='onoffswitch-label' for='{$setting}-checkbox'  $onClick>" .
            '<div class="onoffswitch-inner"></div>'.
            "<div class='onoffswitch-switch'></div>".
            '</label>'.
            '</div>' .
            '</div>';
         return $content;
    }


    /**
     * Returns true if the shortcode attribute='true' or settings is set to 1 (checkbox enabled)
     *
     * If...
     *
     * The shortcode attribute is set.
     *
     * AND EITHER...
     *    The shortcode attribute = true.
     *    OR
     *    attribute is not set (null)
     *       AND the setting is checked
     *
     *
     * @param string $attribute - the key for the shortcode attribute
     * @param string $setting - the key for the admin panel setting
     * @return boolean
     */
    function ShortcodeOrSettingEnabled($attribute,$setting) {
        if (!$this->setPlugin()) { return false; }

       // If the data attribute is set
       //
       // return TRUE if the value is 'true' (this is for shortcode atts)
       if (isset($this->plugin->data[$attribute])) {
            return ($this->ShortcodeAttTrue($this->plugin->data[$attribute]) === '1');

       // If the data attribute is NOT set or it is set and is null (isset = false if value is null)
       // return the value of the database setting
       } else {
            return ($this->plugin->settings->get_item($setting,0) == 1);
       }
    }

    /**
     * Return '1' if the given value is set to 'true', 'on', or '1' (case insensitive).
     * Return '0' otherwise.
     *
     * @param string $attValue
     * @return boolean
     */
    function ShortcodeAttTrue($attValue) {
        if (strcasecmp($attValue,'true')==0) { return '1'; }
        if (strcasecmp($attValue,'on'  )==0) { return '1'; }
        if (strcasecmp($attValue,'1'   )==0) { return '1'; }
        return '0';
    }
    
    /**
     * Create a search form input div.
     */
    function createstring_InputDiv($fldID=null,$label='',$placeholder='',$hidden=false,$divID=null,$default='') {
        $this->plugin->debugMP('slp.main','msg',__FUNCTION__,"field ID: {$fldID} label {$label}");
        if ($fldID === null) { return; }
        if ($divID === null) { $divID = $fldID; }

        // Escape output for special char friendliness
        //
        if ($default     !==''){ $default     = esc_html($default);     }
        if ($placeholder !==''){ $placeholder = esc_html($placeholder); }

        $content =
            ($hidden?'':"<div id='$divID' class='search_item'>") .
                (($hidden || ($label === '')) ? '' : "<label for='$fldID'>$label</label>") .
                "<input type='".($hidden?'hidden':'text')."' id='$fldID' name='$fldID' placeholder='$placeholder' size='50' value='$default' />" .
            ($hidden?'':"</div>")
            ;
        return $content;
    }

    /**
     * Output the search form based on the search results layout.
     */
    function createstring_SearchForm() {
        $this->plugin->debugMP('slp.main','msg','SLPlus_UI:'.__FUNCTION__);

        // Register our custom shortcodes
        // SHORTCODE: slp_search_element
        //
        add_shortcode('slp_search_element',array($this,'create_SearchElement'));

        // Process Layout With Shortcodes
        // 
        $HTML =
            do_shortcode(
                // FILTER: slp_searchlayout
                //
                apply_filters('slp_searchlayout',$this->plugin->defaults['searchlayout'])
            );

        // Disconnect shortcodes
        //
        remove_shortcode('slp_search_element');

        // Make sure the search form is wrapped in the form action to make it
        // work with the JS submit.
        //
        return
            '<form '                                                    .
                "onsubmit='cslmap.searchLocations(); return false;' "   .
                "id='searchForm' "                                      .
                "action=''>"                                            .
            $this->rawDeal($HTML)                                       .
            '</form>'
            ;
    }

    /**
     * Wrap a string in a give prefix/suffix.
     *
     * @param string $content
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    function createstring_WrapText($content,$prefix='',$suffix='') {
        return $prefix.$content.$suffix;
    }

    /**
     * Render the SLP map
     *
     */
    function create_DefaultMap() {
        if(!$this->setPlugin()) { return; }
        $this->plugin->loadPluginData();

        // Add our default map generator, priority 10
        // FILTER: slp_map_html
        //
        add_filter('slp_map_html',array($this,'filter_SetDefaultMapLayout'),10);
        $mapContent =  do_shortcode(apply_filters('slp_map_html',''));

        // Remove the credits
        //
        if ((get_option('sl_remove_credits',0)==1)) {
            $mapContent = preg_replace(
                    '/<div id="slp_tagline"(.*?)<\/div>/',
                    ''  ,
                    $mapContent
                    );
        }

        echo $mapContent;
    }

    /**
     *
     * @param string $HTML current map HTML default is blank
     * @return string modified map HTML
     */
    function filter_SetDefaultMapLayout($HTML) {
        // Only set default HTML if nothing has been defined yet.
        if (!empty($HTML)) { return $HTML; }        
       return $this->plugin->defaults['maplayout'];
    }

    /**
     * Create the default search address div.
     *
     * FILTER: slp_search_default_address
     */
    function createstring_DefaultSearchDiv_Address($placeholder='') {
        $this->plugin->debugMP('msg',__FUNCTION__);
        return $this->plugin->UI->createstring_InputDiv(
            'addressInput',
            get_option('sl_search_label',__('Address','csa-slplus')),
            $placeholder,
            (get_option(SLPLUS_PREFIX.'_hide_address_entry',0) == 1),
            'addy_in_address',
            apply_filters('slp_search_default_address','')
            );
    }

    /**
     * Create the default search radius div.
     */
    function create_DefaultSearchDiv_Radius() {
        $this->plugin->debugMP('msg',__FUNCTION__);
        if (get_option(SLPLUS_PREFIX.'_hide_radius_selections',0) == 0) {
            $HTML =
                "<div id='addy_in_radius'>".
                "<label for='radiusSelect'>".
                get_option('sl_radius_label',__('Within','csa-slplus')).
                '</label>'.
                "<select id='radiusSelect'>".$this->plugin->data['radius_options'].'</select>'.
                "</div>"
                ;
        } else {
            $HTML =$this->plugin->data['radius_options'];
        }
        return $HTML;
    }

    /**
     * Create the default search submit div.
     *
     * If we are not hiding the submit button.
     */
    function create_DefaultSearchDiv_Submit() {
        $this->plugin->debugMP('msg',__FUNCTION__);
        if (get_option(SLPLUS_PREFIX.'_disable_search') == 0) {

            // Find Button Is An Image
            //
            if ($this->plugin->settings->get_item('disable_find_image','0','_') === '0') {
                $sl_theme_base=SLPLUS_UPLOADURL."/images";
                $sl_theme_path=SLPLUS_UPLOADDIR."/images";

                if (!file_exists($sl_theme_path."/search_button.png")) {
                    $sl_theme_base=SLPLUS_PLUGINURL."/images";
                    $sl_theme_path=SLPLUS_COREDIR."/images";
                }

                $sub_img=$sl_theme_base."/search_button.png";
                $mousedown=(file_exists($sl_theme_path."/search_button_down.png"))?
                    "onmousedown=\"this.src='$sl_theme_base/search_button_down.png'\" onmouseup=\"this.src='$sl_theme_base/search_button.png'\"" :
                    "";
                $mouseover=(file_exists($sl_theme_path."/search_button_over.png"))?
                    "onmouseover=\"this.src='$sl_theme_base/search_button_over.png'\" onmouseout=\"this.src='$sl_theme_base/search_button.png'\"" :
                    "";
                $button_style=(file_exists($sl_theme_path."/search_button.png"))?
                    "type='image' class='slp_ui_image_button' src='$sub_img' $mousedown $mouseover" :
                    "type='submit'  class='slp_ui_button'";

            // Find Button Image Is Disabled
            //
            } else {
                $button_style = 'type="submit" class="slp_ui_button"';
            }

            return
                "<div id='radius_in_submit'>".
                    "<input $button_style " .
                        "value='".get_option(SLPLUS_PREFIX.'_find_button_label','Find Locations')."' ".
                        "id='addressSubmit'/>".
                "</div>"
                ;
        }

        return '';
    }

    /**
     * Render the search form for the map.
     *
     * FILTER: slp_search_form_html
     */
    function create_DefaultSearchForm() {
        if(!$this->setPlugin()) { return; }
        $this->plugin->debugMP('slp.main','msg',__FUNCTION__);

        // The search_form template sets up a bunch of DIV filters for the search form.
        //
        // apply_filters actually builds the output HTML from those div filters.
        //
        $HTML =
            "<form onsubmit='cslmap.searchLocations(); return false;' id='searchForm' action=''>".
            "<table  id='search_table' border='0' cellpadding='3px' class='sl_header'>".
                "<tbody id='search_table_body'>".
                    "<tr id='search_form_table_row'>".
                        "<td id='search_form_table_cell' valign='top'>".
                            "<div id='address_search'>".
            $this->createstring_DefaultSearchDiv_Address() .
            $this->create_DefaultSearchDiv_Radius()  .
            $this->create_DefaultSearchDiv_Submit()  .
            '</div></td></tr></tbody></table></form>'
            ;
        
        echo apply_filters('slp_search_form_html',$HTML);
    }

    /**
     * Create the HTML for the map.
     *
     * HOOK: slp_render_map
     */
    function create_Map() {
        ob_start();
        do_action('slp_render_map');
        return $this->rawDeal(ob_get_clean());
    }

    /**
     * Create the map div needed by Google
     *
     */
    function create_MapContent() {
        // FILTER: slp_googlemapdiv
        return apply_filters('slp_googlemapdiv','<div id="map"></div>');
    }

    /**
     * Create the map tagline for SLP link
     *
     */
    function create_MapTagline() {
        return '<div id="slp_tagline">' . 
                sprintf(
                        __('search provided by %s', 'csa-slplus'),
                        "<a href='{$this->plugin->url}' target='_blank'>{$this->plugin->name}</a>"
                        ) .
                '</div>';
    }
    /**
     * Create the HTML for the search results.
     */
    function create_Results() {
        return
            $this->rawDeal(
                '<div id="map_sidebar">'.
                    '<div class="text_below_map">'.
                        get_option('sl_instruction_message',__('Enter Your Address or Zip Code Above.','csa-slplus')) .
                    '</div>'.
                '</div>'
            );
    }

    /**
     * Process shortcodes for search form.
     */
    function create_SearchElement($attributes, $content = null) {
        $this->plugin->debugMP('slp.main','pr','SLPlus_UI:'.__FUNCTION__,$attributes);

        // Pre-process the attributes.
        //
        // This allows third party plugins to man-handle the process by
        // tweaking the attributes.  If, for example, they were to return
        // array('hard_coded_value','blah blah blah') that is all we would return.
        //
        // FILTER: shortcode_slp_searchelement
        //
        $attributes = apply_filters('shortcode_slp_searchelement',$attributes);

        foreach ($attributes as $name=>$value) {

            switch (strtolower($name)) {

                // Hard coded entries take precedence.
                //
                case 'hard_coded_value':
                    return $value;
                    break;

                case 'dropdown_with_label':
                    switch ($value) {
                        case 'radius':
                            return $this->create_DefaultSearchDiv_Radius();
                            break;

                        default:
                            break;
                    }
                    break;

                case 'input_with_label':
                    switch ($value) {
                        case 'address':
                            return $this->createstring_DefaultSearchDiv_Address();
                            break;

                        default:
                            break;
                    }
                    break;

                case 'button':
                    switch ($value) {
                        case 'submit':
                            return $this->create_DefaultSearchDiv_Submit();
                            break;

                        default:
                            break;
                    }
                    break;

                default:
                    break;
            }
        }

        return '';
    }

    /**
     * Do not texturize our shortcodes.
     * 
     * @param array $shortcodes
     * @return array
     */
    static function no_texturize_shortcodes($shortcodes) {
       return array_merge($shortcodes,
                array(
                 'STORE-LOCATOR',
                 'SLPLUS',
                 'slplus',
                )
               );
    }

    /**
     * Process the store locator plus shortcode.
     *
     * Variables this function uses and passes to the template
     * we need a better way to pass vars to the template parser so we don't
     * carry around the weight of these global definitions.
     * the other option is to unset($GLOBAL['<varname>']) at then end of this
     * function call.
     *
     * We now use $this->plugin->data to hold attribute data.
     *
     *
     * @param type $attributes
     * @param type $content
     * @return string HTML the shortcode will render
     */
     function render_shortcode($attributes, $content = null) {
         if (!$this->setPlugin()) {
             return sprintf(__('%s is not ready','csa-slplus'),__('Store Locator Plus','csa-slplus'));
        }
        $this->plugin->debugMP('slp.main','msg','SLPlus_UI:'.__FUNCTION__);

        // Force some plugin data properties
        //
        $this->plugin->data['radius_options'] =
                (isset($this->plugin->data['radius_options'])?$this->plugin->data['radius_options']:'');

        // Load from plugin object data table first,
        // attributes trump options
        //
        $this->plugin->loadPluginData();

        // Setup the base plugin allowed attributes
        //
        add_filter('slp_shortcode_atts',array($this,'filter_SetAllowedShortcodes'));

        // FILTER: slp_shortcode_atts
        // Apply the filter of allowed attributes.
        //
        $attributes =
            shortcode_atts(
                apply_filters('slp_shortcode_atts',array(),$attributes,$content),
                $attributes
               );

        // Set the base plugin data elements to match the allowed
        // shortcode attributes.
        //
        $this->plugin->data =
            array_merge(
                $this->plugin->data,
                (array) $attributes
            );

        // Now set options to attributes
        //
        $this->plugin->options = array_merge($this->plugin->options, (array) $attributes);

        // Localize the CSL Script
        //  This localize modifies the CSLScript with any shortcode attributes.
        //
        $this->plugin->debugMP('slp.main','pr','',$this->plugin->data);
        $this->localizeSLPScript();
        $this->set_RadiusOptions();

        // Set our flag for later processing
        // of JavaScript files
        //
        if (!defined('SLPLUS_SHORTCODE_RENDERED')) {
            define('SLPLUS_SHORTCODE_RENDERED',true);
        }
        $this->plugin->shortcode_was_rendered = true;

        // Setup the style sheets
        //
        $this->setup_stylesheet_for_slplus();

        // Map Actions
        //
        add_action('slp_render_map'         ,array($this,'create_DefaultMap'));

        // FILTER: slp_layout
        //
        return do_shortcode(apply_filters('slp_layout',$this->plugin->defaults['layout']));
    }

    /**
     * Set the allowed shortcode attributes
     * 
     * @param mixed[] $atts
     */
    function filter_SetAllowedShortcodes($atts) {
        return array_merge(
                array(
                    'initial_radius'     => $this->plugin->options['initial_radius'],
                    'theme'              => null,                    
                    ),
                $atts
            );
    }

    /**
     * Localize the CSL Script
     *
     */
    function localizeSLPScript() {
        if (!$this->setPlugin()) { return false; }
        $this->plugin->debugMP('slp.main','msg','SLPlus_UI:'.__FUNCTION__);
        $this->plugin->loadPluginData();

        $slplus_home_icon_file = str_replace(SLPLUS_ICONURL,SLPLUS_ICONDIR,$this->plugin->data['sl_map_home_icon']);
        $slplus_end_icon_file  = str_replace(SLPLUS_ICONURL,SLPLUS_ICONDIR,$this->plugin->data['sl_map_end_icon']);
        $this->plugin->data['home_size'] =(function_exists('getimagesize') && file_exists($slplus_home_icon_file))?
            getimagesize($slplus_home_icon_file) :
            array(0 => 20, 1 => 34);
        $this->plugin->data['end_size']  =(function_exists('getimagesize') && file_exists($slplus_end_icon_file)) ?
            getimagesize($slplus_end_icon_file)  :
            array(0 => 20, 1 => 34);

        // The shortcodes we will care about...
        //
        add_shortcode('slp_location',array($this,'process_slp_location_Shortcode'));
        $resultString =
                do_shortcode(
                    stripslashes(
                        esc_textarea(
                            apply_filters('slp_javascript_results_string',$this->plugin->defaults['resultslayout'])
                        )
                    )
                );

        // Lets get some variables into our script
        //
        $scriptData = array(
            'plugin_url'        => SLPLUS_PLUGINURL,
            'core_url'          => SLPLUS_COREURL,
            'disable_scroll'    => (get_option(SLPLUS_PREFIX.'_disable_scrollwheel')==1),
            'distance_unit'     => esc_attr(get_option('sl_distance_unit'),__('miles', 'csa-slplus')),
            'load_locations'    => (get_option('sl_load_locations_default','1')==1),
            'map_3dcontrol'     => (get_option(SLPLUS_PREFIX.'_disable_largemapcontrol3d')==0),
            'map_country'       => $this->set_MapCenter(),
            'map_domain'        => get_option('sl_google_map_domain','maps.google.com'),
            'map_home_icon'     => $this->plugin->data['sl_map_home_icon'],
            'map_home_sizew'    => $this->plugin->data['home_size'][0],
            'map_home_sizeh'    => $this->plugin->data['home_size'][1],
            'map_end_icon'      => $this->plugin->data['sl_map_end_icon'],
            'map_end_sizew'     => $this->plugin->data['end_size'][0],
            'map_end_sizeh'     => $this->plugin->data['end_size'][1],
            'use_sensor'        => (get_option(SLPLUS_PREFIX.'_use_location_sensor',0 )==1),
            'map_scalectrl'     => (get_option(SLPLUS_PREFIX.'_disable_scalecontrol'  )==0),
            'map_type'          => get_option('sl_map_type','roadmap'),
            'map_typectrl'      => (get_option(SLPLUS_PREFIX.'_disable_maptypecontrol')==0),
            'msg_noresults'     => $this->plugin->settings->get_item('message_noresultsfound','No results found.','_'),
            'results_string'    => $resultString,
            'overview_ctrl'     => get_option('sl_map_overview_control',0),
            'use_email_form'    => (get_option(SLPLUS_PREFIX.'_use_email_form',0)==1),
            'zoom_level'        => get_option('sl_zoom_level',12),
            'zoom_tweak'        => get_option('sl_zoom_tweak',1),

            // FILTER: slp_js_options
            'options'           => apply_filters('slp_js_options',$this->plugin->options)
            );

        remove_shortcode('slp_location');

        // AJAX URL Stuff
        //
        $scriptData['ajaxurl']  = admin_url('admin-ajax.php');
        $scriptData['nonce']    = wp_create_nonce('em');

        // FILTER: slp_script_data
        //
        $scriptData = apply_filters('slp_script_data',$scriptData);

        wp_localize_script('csl_script' ,'slplus'   , $scriptData);
    }

    /**
     * Set the starting point for the center of the map.
     *
     * Uses country by default.
     */
    function set_MapCenter() {
        return apply_filters('slp_map_center',esc_attr(get_option('sl_google_map_country','United States')));
    }

    /**
     * Set the plugin data radius options.
     */
    function set_RadiusOptions() {
        $radiusSelections = get_option('sl_map_radii','1,5,10,(25),50,100,200,500');

        // Hide Radius, set the only (or default) radius
        if (get_option(SLPLUS_PREFIX.'_hide_radius_selections', 0) == 1) {
            preg_match('/\((.*?)\)/', $radiusSelections, $selectedRadius);
            $selectedRadius = preg_replace('/[^0-9]/', '', (isset($selectedRadius[1])?$selectedRadius[1]:$radiusSelections));
            if (empty($selectedRadius) || ($selectedRadius <= 0)) { $selectedRadius = '2500'; }
            $this->plugin->data['radius_options'] =
                    "<input type='hidden' id='radiusSelect' name='radiusSelect' value='$selectedRadius'>";

        // Build Pulldown
        } else {
            $radiusSelectionArray  = explode(",",$radiusSelections);
            $this->plugin->data['radius_options'] = '';
            foreach ($radiusSelectionArray as $radius) {
                $selected=(preg_match('/\(.*\)/', $radius))? " selected='selected' " : "" ;
                $radius=preg_replace('/[^0-9]/', '', $radius);
                $this->plugin->data['radius_options'].=
                        "<option value='$radius' $selected>$radius ".get_option('sl_distance_unit',__('miles', 'csa-slplus'))."</option>";
            }
        }
    }

    /**
     * Setup the CSS for the product pages.
     */
    function setup_stylesheet_for_slplus() {
        if (!$this->setPlugin()) { return false; }
        $this->plugin->helper->loadPluginData();
        if (!isset($this->plugin->data['theme']) || empty($this->plugin->data['theme'])) {
            $this->plugin->data['theme'] = 'default';
        }
        $this->plugin->themes->assign_user_stylesheet($this->plugin->data['theme'],true);
    }

    /**
     * Process the [slp_location] shortcode in a results string.
     *
     * Attributes for [slp_location] include:
     *     <field name> where field name is a locations table field.
     *
     * Usage: [slp_location country]
     *
     * @param array[] $atts
     */
    function process_slp_location_Shortcode($atts) {
        $this->plugin->debugMP('slp.main','msg','SLPlus_UI:'.__FUNCTION__);

        // Set prefix/suffix based on modifiers
        $content = '';
        $prefix = '';
        $suffix = '';
        $fldName = '';

        // Process the keys
        //
        if (is_array($atts)) {
            foreach ($atts as $key=>$value) {
                $key=strtolower($key);
                $value = preg_replace('/\W/','',htmlspecialchars_decode($value));
                switch ($key) {

                    // First attribute : field name placeholders
                    //
                    case '0':
                        $fldName = strtolower($value);
                        switch ($fldName):
                            case 'distance_1'     :
                                $content = '{1}';
                                break;
                            case 'distance_unit'  :
                                $content =  '{2}';
                                break;
                            case 'city_state_zip' :
                                $content =  '{5}';
                                break;
                            case 'web_link'       :
                                $content =  '{8}';
                                break;
                            case 'email_link'     :
                                $content =  '{9}';
                                break;
                            case 'map_domain'     :
                                $content =  '{10}';
                                break;
                            case 'search_address' :
                                $content =  '{11}';
                                break;
                            case 'location_address':
                                $content =  '{12}';
                                break;
                            case 'directions_text':
                                $content =  '{13}';
                                break;
                            case 'pro_tags':
                                $content =  '{14}';
                                break;
                            case 'id':
                                $content =  '{15}';
                                break;
                            case 'hours':
                                $content =  '{17}';
                                break;
                            default:
                                $content =  '{18.'.$fldName.'}';
                                break;
                        endswitch;
                        break;

                    // Wrapper attribute
                    //
                    case 'wrap':
                        switch ($value) {
                            case 'fullspan':
                                $prefix = '<span class="results_line location_[fldName]">';
                                $suffix = '</span>';
                                break;
                            default:
                                break;
                        }

                    default:
                        break;
                }
            }
        }
        
        // If prefix has [fldName] placeholder and $fldName is set,
        // do the replacement.
        //
        if (!empty($fldName) && strpos($prefix,'[fldName]')) {
            $prefix = str_replace('[fldName]',$fldName,$prefix);
        }

        return $this->createstring_WrapText($content,$prefix,$suffix);
    }

    /**
     * Strip all \r\n from the template to try to "unbreak" Theme Forest themes.
     *
     * This is VERY ugly, but a lot of people use Theme Forest.  They have a known bug
     * that MANY Theme Forest authors have introduced which will change this:
     * <table
     *    style="display:none"
     *    >
     *
     * To this:
     * <table<br/>
     *    style="display:none"<br/>
     *    >
     *
     * Which really fucks things up.
     *
     * Envato response?  "Oh well, we will tell the authors but can't really fix anything."
     *
     * Now our plugin has this ugly slow formatting function which sucks balls.   But we need it
     * if we are going to not alienate a bunch of Envato users that will never tell us they had an
     * issue. :/
     *
     * @param string $inStr
     * @return string
     */
    function rawDeal($inStr) {
        return str_replace(array("\r","\n"),'',$inStr);
    }

    /**
     * Puts the tag list on the search form for users to select tags.
     *
     * @param string[] $tags tags as an array of strings
     * @param boolean $showany show the any pulldown entry if true
     */
    static function slp_render_search_form_tag_list($tags,$showany = false) {
        print "<select id='tag_to_search_for' >";

        // Show Any Option (blank value)
        //
        if ($showany) {
            print "<option value=''>".
                get_option(SLPLUS_PREFIX.'_tag_pulldown_first',__('Any','csa-slplus')).
                '</option>';
        }

        foreach ($tags as $selection) {
            $clean_selection = preg_replace('/\((.*)\)/','$1',$selection);
            print "<option value='$clean_selection' ";
            print (preg_match('#\(.*\)#', $selection))? " selected='selected' " : '';
            print ">$clean_selection</option>";
        }print "</select>";
    }


     //------------------------------------------------------------------------
     // DEPRECATED
     //------------------------------------------------------------------------

     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      *
      * @var null $addingLocation
      */
     public $resultsString = null;

     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      */
     function setResultsString() {
        if (!$this->setPlugin()) { return false; }
        if (!$this->depnotice_setResultsString) {
            $this->plugin->notifications->add_notice(9,$this->plugin->createstring_Deprecated(__FUNCTION__));
            $this->plugin->notifications->display();
            $this->depnotice_setResultsString = true;
         }
     }
}
