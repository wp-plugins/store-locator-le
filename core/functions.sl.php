<?php
/****************************************************************************
 ** file: functions.sl.php
 **
 ** The collection of main core functions for Store Locator Plus
 ***************************************************************************/

$text_domain=SLPLUS_PREFIX;
$prefix = SLPLUS_PREFIX;

$map_character_encoding=(get_option('sl_map_character_encoding')!="")? 
    "&amp;oe=".get_option('sl_map_character_encoding') : 
    "";
 
 
/**
 * 
 * @global type $sl_height
 * @global type $sl_width
 * @global type $sl_width_units
 * @global type $sl_height_units
 * @global type $cl_icon
 * @global type $cl_icon2
 * @global type $sl_google_map_domain
 * @global type $sl_google_map_country
 * @global type $sl_theme
 * @global type $sl_location_table_view
 * @global type $sl_search_label
 * @global type $sl_zoom_level
 * @global type $sl_zoom_tweak
 * @global type $sl_use_name_search
 * @global type $sl_default_map
 * @global type $sl_radius_label
 * @global type $sl_website_label
 * @global type $sl_num_initial_displayed
 * @global type $sl_load_locations_default
 * @global type $sl_distance_unit
 * @global type $sl_map_overview_control
 * @global type $sl_admin_locations_per_page
 * @global type $sl_instruction_message
 * @global type $sl_map_character_encoding
 * @global string $slplus_name_label
 */
function initialize_variables() {
    global $sl_height, $sl_width, $sl_width_units, $sl_height_units;
    global $cl_icon, $cl_icon2, $sl_google_map_domain, $sl_google_map_country, $sl_theme, $sl_location_table_view;
    global $sl_search_label, $sl_zoom_level, $sl_zoom_tweak, $sl_use_name_search, $sl_default_map;
    global $sl_radius_label, $sl_website_label, $sl_num_initial_displayed, $sl_load_locations_default;
    global $sl_distance_unit, $sl_map_overview_control, $sl_admin_locations_per_page, $sl_instruction_message;
    global $sl_map_character_encoding, $slplus_name_label;
    
    $sl_map_character_encoding=get_option('sl_map_character_encoding');
    if (empty($sl_map_character_encoding)) {
        $sl_map_character_encoding="";
        add_option('sl_map_character_encoding', $sl_map_character_encoding);
        }
    $sl_instruction_message=get_option('sl_instruction_message');
    if (empty($sl_instruction_message)) {
        $sl_instruction_message="Enter Your Address or Zip Code Above.";
        add_option('sl_instruction_message', $sl_instruction_message);
        }
    $sl_admin_locations_per_page=get_option('sl_admin_locations_per_page');
    if (empty($sl_admin_locations_per_page)) {
        $sl_admin_locations_per_page="100";
        add_option('sl_admin_locations_per_page', $sl_admin_locations_per_page);
        }
    $sl_map_overview_control=get_option('sl_map_overview_control');
    if (empty($sl_map_overview_control)) {
        $sl_map_overview_control="0";
        add_option('sl_map_overview_control', $sl_map_overview_control);
        }
    $sl_distance_unit=get_option('sl_distance_unit');
    if (empty($sl_distance_unit)) {
        $sl_distance_unit="miles";
        add_option('sl_distance_unit', $sl_distance_unit);
        }
    $sl_load_locations_default=get_option('sl_load_locations_default');
    if (empty($sl_load_locations_default)) {
        $sl_load_locations_default="1";
        add_option('sl_load_locations_default', $sl_load_locations_default);
        }
    $sl_num_initial_displayed=get_option('sl_num_initial_displayed');
    if (empty($sl_num_initial_displayed)) {
        $sl_num_initial_displayed="25";
        add_option('sl_num_initial_displayed', $sl_num_initial_displayed);
        }
    $sl_website_label=get_option('sl_website_label');
    if (empty($sl_website_label)) {
        $sl_website_label="Website";
        add_option('sl_website_label', $sl_website_label);
        }
    $sl_radius_label=get_option('sl_radius_label');
    if (empty($sl_radius_label)) {
        $sl_radius_label="Radius";
        add_option('sl_radius_label', $sl_radius_label);
        }
    $sl_map_type=get_option('sl_map_type');
    if (isset($sl_map_type)) {
        $sl_map_type='roadmap';
        add_option('sl_map_type', $sl_map_type);
        }
    $sl_remove_credits=get_option('sl_remove_credits');
    if (empty($sl_remove_credits)) {
        $sl_remove_credits="0";
        add_option('sl_remove_credits', $sl_remove_credits);
        }
    $sl_use_name_search=get_option('sl_use_name_search');
    if (empty($sl_use_name_search)) {
        $sl_use_name_search="0";
        add_option('sl_use_name_search', $sl_use_name_search);
        }

    $sl_zoom_level=get_option('sl_zoom_level','4');
    add_option('sl_zoom_level', $sl_zoom_level);
    
    $sl_zoom_tweak=get_option('sl_zoom_tweak','1');
    add_option('sl_zoom_tweak', $sl_zoom_tweak);

    $sl_search_label=get_option('sl_search_label');
    if (empty($sl_search_label)) {
        $sl_search_label="Address";
        add_option('sl_search_label', $sl_search_label);
        }
	if (empty($slplus_name_label)) {
		$$slplus_name_label = "Store to search for";
		add_option('sl_name_label', $slplus_name_label);
	}
    $sl_location_table_view=get_option('sl_location_table_view');
    if (empty($sl_location_table_view)) {
        $sl_location_table_view="Normal";
        add_option('sl_location_table_view', $sl_location_table_view);
        }
    $sl_theme=get_option('sl_map_theme');
    if (empty($sl_theme)) {
        $sl_theme="";
        add_option('sl_map_theme', $sl_theme);
        }
    $sl_google_map_country=get_option('sl_google_map_country');
    if (empty($sl_google_map_country)) {
        $sl_google_map_country="United States";
        add_option('sl_google_map_country', $sl_google_map_country);
    }
    $sl_google_map_domain=get_option('sl_google_map_domain');
    if (empty($sl_google_map_domain)) {
        $sl_google_map_domain="maps.google.com";
        add_option('sl_google_map_domain', $sl_google_map_domain);
    }
    $cl_icon2=get_option('sl_map_end_icon');
    if (empty($cl_icon2)) {
        add_option('sl_map_end_icon', SLPLUS_COREURL . 'images/icons/marker.png');
        $cl_icon2=get_option('sl_map_end_icon');
    }
    $cl_icon=get_option('sl_map_home_icon');
    if (empty($cl_icon)) {
        add_option('sl_map_home_icon', SLPLUS_COREURL . 'images/icons/arrow.png');
        $cl_icon=get_option('sl_map_home_icon');
    }
    $sl_height=get_option('sl_map_height');
    if (empty($sl_height)) {
        add_option('sl_map_height', '350');
        $sl_height=get_option('sl_map_height');
        }
    
    $sl_height_units=get_option('sl_map_height_units');
    if (empty($sl_height_units)) {
        add_option('sl_map_height_units', "px");
        $sl_height_units=get_option('sl_map_height_units');
        }	
    
    $sl_width=get_option('sl_map_width');
    if (empty($sl_width)) {
        add_option('sl_map_width', "100");
        $sl_width=get_option('sl_map_width');
        }
    
    $sl_width_units=get_option('sl_map_width_units');
    if (empty($sl_width_units)) {
        add_option('sl_map_width_units', "%");
        $sl_width_units=get_option('sl_map_width_units');
        }	
}

/**
 *
 * @global type $wpdb
 * @global type $slplus_plugin
 * @param type $address
 * @param type $sl_id
 */
function do_geocoding($address,$sl_id='') {    
    global $wpdb, $slplus_plugin;    
    
    $delay = 0;    
    $base_url = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false";
    
    // Loop through for X retries
    //
    $iterations = get_option(SLPLUS_PREFIX.'-goecode_retries');
    if ($iterations <= 0) { $iterations = 1; }
    while($iterations){
    	$iterations--;     
    
        // Iterate through the rows, geocoding each address
        $request_url = $base_url . "&address=" . urlencode($address);
        $errorMessage = '';
        

        // Use HTTP Handler (WP_HTTP) first...
        //
        if (isset($slplus_plugin->http_handler)) { 
            $result = $slplus_plugin->http_handler->request( 
                            $request_url, 
                            array('timeout' => 3) 
                            ); 
            if ($slplus_plugin->http_result_is_ok($result) ) {
                $raw_json = $result['body'];
            }
            
        // Then Curl...
        //
        } elseif (extension_loaded("curl") && function_exists("curl_init")) {
                $cURL = curl_init();
                curl_setopt($cURL, CURLOPT_URL, $request_url);
                curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
                $raw_json = curl_exec($cURL);
                curl_close($cURL);

        // Lastly file_get_contents
        //
        } else {
             $raw_json = file_get_contents($request_url);
        }

        // If raw_json exists, parse it
        //
        if (isset($raw_json)) {
            $json = json_decode($raw_json);
            $status = $json->{'status'};
            
        // no raw json
        //
        } else {
            $json = '';
            $status = '';
        }
        
        // Geocode completed successfully
        //
        if (strcmp($status, "OK") == 0) {
            $iterations = 0;      // Break out of retry loop if we are OK
            $delay = 0;
            
            // successful geocode
            $geocode_pending = false;
            $lat = $json->results[0]->geometry->location->lat;
            $lng = $json->results[0]->geometry->location->lng;
            // Update newly inserted address
            //
            if ($sl_id=='') {
                $query = sprintf("UPDATE " . $wpdb->prefix ."store_locator " .
                       "SET sl_latitude = '%s', sl_longitude = '%s' " .
                       "WHERE sl_id = LAST_INSERT_ID()".
                       " LIMIT 1;", 
                       mysql_real_escape_string($lat), 
                       mysql_real_escape_string($lng)
                       );
            }
            // Update an existing address
            //
            else {
                $query = sprintf("UPDATE " . $wpdb->prefix ."store_locator SET sl_latitude = '%s', sl_longitude = '%s' WHERE sl_id = $sl_id LIMIT 1;", mysql_real_escape_string($lat), mysql_real_escape_string($lng));
            }
            
            // Run insert/update
            //
            $update_result = $wpdb->query($query);
            if ($update_result == 0) {
                $theDBError = htmlspecialchars(mysql_error($wpdb->dbh),ENT_QUOTES);
                $errorMessage .= __("Could not add/update address.  ", SLPLUS_PREFIX);
                if ($theDBError != '') {
                    $errorMessage .= sprintf(
                                            __("Error: %s.", SLPLUS_PREFIX),
                                            $theDBError
                                            );
                } elseif ($update_result === 0) {
                    $errorMessage .=  __("It appears the data did not change.", SLPLUS_PREFIX);
                } else {
                    $errorMessage .=  __("No error logged.", SLPLUS_PREFIX);
                    $errorMessage .= "<br/>\n" . __('Query: ', SLPLUS_PREFIX);
                    $errorMessage .= print_r($wpdb->last_query,true);
                    $errorMessage .= "<br/>\n" . "Results: " . gettype($update_result) . ' '. $update_result;
                }

            }

        // Geocoding done too quickly
        //
        } else if (strcmp($status, "OVER_QUERY_LIMIT") == 0) {
            
          // No iterations left, tell user of failure
          //
	      if(!$iterations){
            $errorMessage .= sprintf(__("Address %s <font color=red>failed to geocode</font>. ", SLPLUS_PREFIX),$address);
            $errorMessage .= sprintf(__("Received status %s.", SLPLUS_PREFIX),$status)."\n<br>";
	      }                       
          $delay += 100000;

        // Invalid address
        //
        } else if (strcmp($status, 'ZERO_RESULTS') == 0) {
	    	$iterations = 0; 
	    	$errorMessage .= sprintf(__("Address %s <font color=red>failed to geocode</font>. ", SLPLUS_PREFIX),$address);
	      	$errorMessage .= sprintf(__("Unknown Address! Received status %s.", SLPLUS_PREFIX),$status)."\n<br>";
          
        // Could Not Geocode
        //
        } else {
            $geocode_pending = false;
            echo sprintf(__("Address %s <font color=red>failed to geocode</font>. ", SLPLUS_PREFIX),$address);
            if ($status != '') {
                $errorMessage .= sprintf(__("Received data %s.", SLPLUS_PREFIX),'<pre>'.print_r($json,true).'</pre>')."\n";
            } else {
                $errorMessage .= sprintf(__("Reqeust sent to %s.", SLPLUS_PREFIX),$request_url)."\n<br>";
                $errorMessage .= sprintf(__("Received status %s.", SLPLUS_PREFIX),$status)."\n<br>";
            }
        }

        // Show Error Messages
        //
        if ($errorMessage != '') {
            print '<div class="geocode_error">' .
                    $errorMessage .
                    '</div>';
        }

        usleep($delay);
    }
}    

/**************************************
 ** function: store_locator_shortcode
 **
 ** Process the store locator shortcode.
 **
 **/
 function store_locator_shortcode($attributes, $content = null) {
    // Variables this function uses and passes to the template
    // we need a better way to pass vars to the template parser so we don't
    // carry around the weight of these global definitions.
    // the other option is to unset($GLOBAL['<varname>']) at then end of this    
    // function call.
    //
    // Let's start using a SINGLE named array called "fnvars" to pass along anything
    // we want.
    //
    global  $text_domain, $wpdb,
	    $slplus_plugin, $prefix, $sl_search_label, $sl_width, $sl_height, $sl_width_units, $sl_height_units,
	    $sl_radius, $sl_radius_label, $r_options, $sl_instruction_message, $cs_options, $slplus_name_label,
	    $sl_country_options, $slplus_state_options, $fnvars;	 	    
    $fnvars = array();

    //----------------------
    // Attribute Processing
    //
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
        $slplus_plugin->shortcode_was_rendered = true;
        slplus_shortcode_atts($attributes);
    }
                   
    $sl_height         = get_option('sl_map_height','500');    
    $sl_height_units   = get_option('sl_map_height_units','px');    
    $sl_search_label   = get_option('sl_search_label',__('Address',SLPLUS_PREFIX));
    $unit_display   = get_option('sl_distance_unit','mi');    
    $sl_width          = get_option('sl_map_width','100');        
    $sl_width_units    = get_option('sl_map_width_units','%');
	$slplus_name_label = get_option('sl_name_label');
    $r_array        = explode(",",get_option('sl_map_radii','1,5,10,(25),50,100,200,500'));
    
    $sl_instruction_message = get_option('sl_instruction_message',__('Enter Your Address or Zip Code Above.',SLPLUS_PREFIX));
    
    
    $r_options      =(isset($r_options)         ?$r_options      :'');
    $cs_options     =(isset($cs_options)        ?$cs_options     :'');
    $sl_country_options=(isset($sl_country_options)   ?$sl_country_options:'');
    $slplus_state_options=(isset($slplus_state_options)   ?$slplus_state_options:'');

    foreach ($r_array as $sl_value) {
        $selected=(preg_match('/\(.*\)/', $sl_value))? " selected='selected' " : "" ;
        
        // Hiding Radius?
        if (get_option(SLPLUS_PREFIX.'_hide_radius_selections') == 1) {
            if ($s == " selected='selected' ") {
                $sl_value=preg_replace('/[^0-9]/', '', $sl_value);
                $r_options = "<input type='hidden' id='radiusSelect' name='radiusSelect' value='$sl_value'>";
            }
            
        // Not hiding radius, build pulldown.
        } else {
            $sl_value=preg_replace('/[^0-9]/', '', $sl_value);
            $r_options.="<option value='$sl_value' $selected>$sl_value $unit_display</option>";
        }
    }
        
    //-------------------
    // Show City Search option is checked
    // setup the pulldown list
    //
    if (get_option('sl_use_city_search',0)==1) {
        $cs_array=$wpdb->get_results(
            "SELECT CONCAT(TRIM(sl_city), ', ', TRIM(sl_state)) as city_state " .
                "FROM ".$wpdb->prefix."store_locator " .
                "WHERE sl_city<>'' AND sl_state<>'' AND sl_latitude<>'' " .
                    "AND sl_longitude<>'' " .
                "GROUP BY city_state " .
                "ORDER BY city_state ASC", 
            ARRAY_A);
    
        if ($cs_array) {
            foreach($cs_array as $sl_value) {
        $cs_options.="<option value='$sl_value[city_state]'>$sl_value[city_state]</option>";
            }
        }
    }

    //----------------------
    // Create Country Pulldown
    //    
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {                    
        $sl_country_options = slplus_create_country_pd();    
        $slplus_state_options = slplus_create_state_pd();
    } else {
        $sl_country_options = '';    
        $slplus_state_options = '';
    }

    $columns = 1;
    $columns += (get_option('sl_use_city_search',0)!=1) ? 1 : 0;
    $columns += (get_option('sl_use_country_search',0)!=1) ? 1 : 0;
    $columns += (get_option('slplus_show_state_pd',0)!=1) ? 1 : 0;
    $sl_radius_label=get_option('sl_radius_label','');

    // Prep fnvars for passing to our template
    //
    $fnvars = array_merge($fnvars,(array) $attributes);       // merge in passed attributes
    
		
	//todo: make sure map type gets set to a sane value before getting here. Maybe not...

    //todo: if we allow map setting overrides via shortcode attributes we will need
    // to re-localize the script.  It was moved to the actions class so we can
    // localize prior to enqueue in the header.
    //

    // Setup the style sheets
    //
    setup_stylesheet_for_slplus();


    // Set our flag for later processing
    // of JavaScript files
    //
    if (!defined('SLPLUS_SHORTCODE_RENDERED')) {
        define('SLPLUS_SHORTCODE_RENDERED',true);
    }

    // Search / Map Actions
    //
    add_action('slp_render_search_form',array('SLPlus_UI','slp_render_search_form'));

    return get_string_from_phpexec(SLPLUS_COREDIR . 'templates/search_and_map.php');
}


/**************************************
 * SetMapCenter()
 *
 * Set the starting point for the center of the map.
 * Uses country by default.
 * Pro Pack v2.4+ allows for a custom address.
 */
function SetMapCenter() {
    global $slplus_plugin;
    $customAddress = get_option(SLPLUS_PREFIX.'_map_center');
    if (
        (preg_replace('/\W/','',$customAddress) != '') &&
        $slplus_plugin->license->packages['Pro Pack']->isenabled &&
        ($slplus_plugin->license->packages['Pro Pack']->active_version >= 2004000)
        ) {
        return str_replace(array("\r\n","\n","\r"),', ',esc_attr($customAddress));
    }
    return esc_attr(get_option('sl_google_map_country','United States'));    
}

/**
 *
 * @param type $a
 * @return type
 */
function comma($a) {
	$a=preg_replace("/'/"     , '&#39;'   , $a);
	$a=preg_replace('/"/'     , '&quot;'  , $a);
	$a=preg_replace('/>/'     , '&gt;'    , $a);
	$a=preg_replace('/</'     , '&lt;'    , $a);
	$a=preg_replace('/,/'     , '&#44;'   , $a);
	$a=preg_replace('/ & /'   , ' &amp; ' , $a);
    return $a;
}

