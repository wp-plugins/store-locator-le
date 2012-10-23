<?php
/****************************************************************************
 ** file: csl-ajax-search.php
 **
 ** Perform a search via ajax
 ***************************************************************************/

/**
 * Format the result data into a named array.
 * 
 * We will later use this to build our JSONP response.
 * 
 * @param array $data - the data from the SLP database
 * @return named array
 */
function slp_add_marker($row = null,$type='load') {
    if ($row == null) {
        return '';
    }
    $marker = array(
          'name'        => esc_attr($row['sl_store']),
          'address'     => esc_attr($row['sl_address']),
          'address2'    => esc_attr($row['sl_address2']),
          'city'        => esc_attr($row['sl_city']),
          'state'       => esc_attr($row['sl_state']),
          'zip'         => esc_attr($row['sl_zip']),
          'lat'         => $row['sl_latitude'],
          'lng'         => $row['sl_longitude'],
          'description' => html_entity_decode($row['sl_description']),
          'url'         => esc_attr($row['sl_url']),
          'sl_pages_url'=> esc_attr($row['sl_pages_url']),
          'email'       => esc_attr($row['sl_email']),
          'hours'       => esc_attr($row['sl_hours']),
          'phone'       => esc_attr($row['sl_phone']),
          'fax'         => esc_attr($row['sl_fax']),
          'image'       => esc_attr($row['sl_image']),
          'distance'    => $row['sl_distance'],
          'tags'        => ((get_option(SLPLUS_PREFIX.'_show_tags',0) ==1)? esc_attr($row['sl_tags']) : ''),
          'data_from'   => $type,
          'option_value'=> esc_js($row['sl_option_value']),
          'id'          => $row['sl_id'],
      );

      $marker = apply_filters('slp_results_marker_data',$marker);
      return $marker;
}

/**
 * Handle AJAX request for OnLoad action.
 * 
 * @global type $wpdb
 */
function csl_ajax_onload() {
	global $wpdb;
	$username=DB_USER;
	$password=DB_PASSWORD;
	$database=DB_NAME;
	$host=DB_HOST;
	$dbPrefix = $wpdb->prefix;
	$connection=mysql_connect ($host, $username, $password);
	if (!$connection) {
		die (json_encode( array('success' => false, 'response' => 'Not connected : ' . mysql_error())));
	}

	// Set the active MySQL database
	$db_selected = mysql_select_db($database, $connection);
	mysql_query("SET NAMES utf8");
	if (!$db_selected) {
	  die (json_encode( array('success' => false, 'response' => 'Can\'t use db : ' . mysql_error())));
	}

	$num_initial_displayed=trim(get_option('sl_num_initial_displayed','25'));

	// If tags are passed filter to just those tags
	//
	$tag_filter = ''; 
	if (
		(get_option(SLPLUS_PREFIX.'_show_tag_search') ==1) &&
		isset($_POST['tags']) && ($_POST['tags'] != '')
	   ){
		$posted_tag = preg_replace('/^\s+(.*?)/','$1',$_POST['tags']);
		$posted_tag = preg_replace('/(.*?)\s+$/','$1',$posted_tag);
		$tag_filter = " AND ( sl_tags LIKE '%%". $posted_tag ."%%') ";
	}
	
	// If store names are passed, filter show those names
	$name_filter = '';
	if ((get_option(SLPLUS_PREFIX.'_show_name_search') == 1) &&
		isset($_POST['name']) && ($_POST['name'] != ''))
	{
		$posted_name = preg_replace('/^\s+(.*?)/','$1',$_POST['name']);
		$posted_name = preg_replace('/(.*?)\s+$/','$1',$posted_name);
		$name_filter = " AND (sl_store LIKE '%%".$posted_name."%%')";
	}

	// Select all the rows in the markers table
	$multiplier=(get_option('sl_distance_unit')=="km")? 6371 : 3959;
	$query = "SELECT *, ".
		"( $multiplier * acos( cos( radians('".$_POST['lat']."') ) * cos( radians( sl_latitude ) ) * " .
				"cos( radians( sl_longitude ) - radians('".$_POST['lng']."') ) + sin( radians('".$_POST['lat']."') ) * ".
				"sin( radians( sl_latitude ) ) ) ) AS sl_distance ".    
		"FROM ".$wpdb->prefix."store_locator ".
		"WHERE sl_store<>'' AND sl_longitude<>'' AND sl_latitude<>'' $tag_filter<>'' $name_filter  ".
		"ORDER BY sl_distance ASC ".
		"LIMIT $num_initial_displayed";
		
	$result = mysql_query($query);
	if (!$result) {
	  die('Invalid query: ' . mysql_error());
	}
    

	// Iterate through the rows, printing json nodes for each
	$response = array();
	while ($row = @mysql_fetch_assoc($result)){
        $response[] = slp_add_marker($row,'load');
	}	
	
	header( "Content-Type: application/json" );
    echo json_encode( array( 'success' => true, 'count' => count($response) , 'response' => $response ) );
	die();
}

/**
 * Handle AJAX request for Search calls.
 * 
 * @global type $wpdb
 */
function csl_ajax_search() {
	global $wpdb;
	$username=DB_USER;
	$password=DB_PASSWORD;
	$database=DB_NAME;
	$host=DB_HOST;
	$dbPrefix = $wpdb->prefix;
	
	// Get parameters from URL
	$center_lat = $_POST["lat"];
	$center_lng = $_POST["lng"];
	$radius = $_POST["radius"];

	//-----------------
	// Set the active MySQL database
	//
	$connection=mysql_connect ($host, $username, $password);
	if (!$connection) { die(json_encode( array('success' => false, 'response' => 'Not connected : ' . mysql_error()))); }
	$db_selected = mysql_select_db($database, $connection);
	mysql_query("SET NAMES utf8");
	if (!$db_selected) {
		die (json_encode( array('success' => false, 'response' => 'Can\'t use db : ' . mysql_error())));
	}

	// If tags are passed filter to just those tags
	//
	$tag_filter = ''; 
	if (
		(get_option(SLPLUS_PREFIX.'_show_tag_search') ==1) &&
		isset($_POST['tags']) && ($_POST['tags'] != '')
	){
		$posted_tag = preg_replace('/^\s+(.*?)/','$1',$_POST['tags']);
		$posted_tag = preg_replace('/(.*?)\s+$/','$1',$posted_tag);
		$tag_filter = " AND ( sl_tags LIKE '%%". $posted_tag ."%%') ";
	}

	$name_filter = '';
	if(isset($_POST['name']) && ($_POST['name'] != ''))
	{
		$posted_name = preg_replace('/^\s+(.*?)/','$1',$_POST['name']);
		$posted_name = preg_replace('/(.*?)\s+$/','$1',$posted_name);
		$name_filter = " AND (sl_store LIKE '%%".$posted_name."%%')";
	}
	
	//Since miles is default, if kilometers is selected, divide by 1.609344 in order to convert the kilometer value selection back in miles when generating the XML
	//
	$multiplier=(get_option('sl_distance_unit')=="km")? 6371 : 3959;

	$option[SLPLUS_PREFIX.'_maxreturned']=(trim(get_option(SLPLUS_PREFIX.'_maxreturned'))!="")? 
    get_option(SLPLUS_PREFIX.'_maxreturned') : 
    '25';
	
	$max = mysql_real_escape_string($option[SLPLUS_PREFIX.'_maxreturned']);
    $query = sprintf(
        "SELECT *,".
        "( $multiplier * acos( cos( radians('%s') ) * cos( radians( sl_latitude ) ) * cos( radians( sl_longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( sl_latitude ) ) ) ) AS sl_distance ".
        "FROM ${dbPrefix}store_locator ".
        "WHERE sl_longitude<>'' %s %s ".
        "HAVING (sl_distance < '%s') ".
        'ORDER BY sl_distance ASC '.
        'LIMIT %s',
        mysql_real_escape_string($center_lat),
        mysql_real_escape_string($center_lng),
        mysql_real_escape_string($center_lat),
        $tag_filter,
        $name_filter,
        mysql_real_escape_string($radius),
        mysql_real_escape_string($option[SLPLUS_PREFIX.'_maxreturned'])
    );
		
    $result = mysql_query(apply_filters('slp_mysql_search_query',$query));
    if (!$result) {
        die(json_encode( array('success' => false, 'query' => $query, 'response' => 'Invalid query: ' . mysql_error())));
    }

    // Reporting
    // Insert the query into the query DB
    //
    if (get_option(SLPLUS_PREFIX.'-reporting_enabled','off') === 'on') {
        $qry = sprintf(
                "INSERT INTO ${dbPrefix}slp_rep_query ".
                           "(slp_repq_query,slp_repq_tags,slp_repq_address,slp_repq_radius) ".
                    "values ('%s','%s','%s','%s')",
                    mysql_real_escape_string($_SERVER['QUERY_STRING']),
                    mysql_real_escape_string($_POST['tags']),
                    mysql_real_escape_string($_POST['address']),
                    mysql_real_escape_string($_POST['radius'])
                );
        $wpdb->query($qry);
        $slp_QueryID = mysql_insert_id();
    }		
		
    // Iterate through the rows, printing XML nodes for each
    $response = array();
    while ($row = @mysql_fetch_assoc($result)){
        $thisLocation = slp_add_marker($row,'search');
        if (!empty($thisLocation)) {
            $response[] = $thisLocation;

            // Reporting
            // Insert the results into the reporting table
            //
            if (get_option(SLPLUS_PREFIX.'-reporting_enabled') === "on") {
                $wpdb->query(
                    sprintf(
                        "INSERT INTO ${dbPrefix}slp_rep_query_results
                            (slp_repq_id,sl_id) values (%d,%d)",
                            $slp_QueryID,
                            $row['sl_id']
                        )
                    );
            }
        }
    }
    header( "Content-Type: application/json" );
    echo json_encode( array( 'success' => true, 'count' => count($response), 'option' => $_POST['address'], 'response' => $response ) );
    die();
 }
