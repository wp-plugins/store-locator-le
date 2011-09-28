<?php
/****************************************************************************
 ** file: data-xml.php
 **
 ** Generate the data markers for the Google Maps JavaScript.
 ***************************************************************************/
error_reporting(0);
header("Content-type: text/xml");
include("database-info.php");

// Opens a connection to a MySQL server
$connection=mysql_connect ($host, $username, $password);
if (!$connection) {
  die('Not connected : ' . mysql_error());
}

// Set the active MySQL database
$db_selected = mysql_select_db($database, $connection);
mysql_query("SET NAMES utf8");
if (!$db_selected) {
  die ('Can\'t use db : ' . mysql_error());
}


$num_initial_displayed=(trim(get_option('sl_num_initial_displayed'))!="")? 
    get_option('sl_num_initial_displayed') : 
    '25';


// If tags are passed filter to just those tags
//
$tag_filter = ''; 
if (
	(get_option($prefix.'_show_tag_search') ==1) &&
	isset($_GET['tags']) && ($_GET['tags'] != '')
   ){
    $posted_tag = preg_replace('/\s+(.*?)/','$1',$_GET['tags']);
    $posted_tag = preg_replace('/(.*?)\s+/','$1',$posted_tag);
	$tag_filter = " AND ( sl_tags LIKE '%%". $posted_tag ."%%') ";
}
   

//Since miles is default, if kilometers is selected, divide by 1.609344 in order to convert the kilometer value selection back in miles when generating the XML
//
$multiplier=3959;
$multiplier=(get_option('sl_distance_unit')=="km")? ($multiplier*1.609344) : $multiplier;
    
// Select all the rows in the markers table
$query = "SELECT sl_address, sl_address2, sl_store, sl_city, sl_state, ".
    "sl_zip, sl_country, sl_latitude, sl_longitude, sl_description, sl_url, ".
	"( $multiplier * acos( cos( radians('".$_GET['lat']."') ) * cos( radians( sl_latitude ) ) * " .
	        "cos( radians( sl_longitude ) - radians('".$_GET['lng']."') ) + sin( radians('".$_GET['lat']."') ) * ".
	        "sin( radians( sl_latitude ) ) ) ) AS sl_distance, ".    
    "sl_email, sl_hours, sl_phone, sl_image FROM ".$wpdb->prefix."store_locator ".
    "WHERE sl_store<>'' AND sl_longitude<>'' AND sl_latitude<>'' $tag_filter ".
    "LIMIT $num_initial_displayed";
    
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

// Start XML file, echo parent node
echo "<markers>\n";
// Iterate through the rows, printing XML nodes for each
while ($row = @mysql_fetch_assoc($result)){
  // ADD TO XML DOCUMENT NODE
  echo '<marker ';
  echo 'name="' . htmlentities($row['sl_store']) . '" ';
  echo 'address="' . 
    htmlentities($row['sl_address']) . ', '.
    htmlentities($row['sl_address2']) . ', '.  
    htmlentities($row['sl_city']). ', ' .htmlentities($row['sl_state']).' ' .
    htmlentities($row['sl_zip']).'" ';
  echo 'lat="' . $row['sl_latitude'] . '" ';
  echo 'lng="' . $row['sl_longitude'] . '" ';
  echo 'distance="' . $row['sl_distance'] . '" ';
  echo 'description="' . htmlentities($row['sl_description']) . '" ';
  echo 'url="' . htmlentities($row['sl_url']) . '" ';
  echo 'email="' . htmlentities($row['sl_email']) . '" ';
  echo 'hours="' . htmlentities($row['sl_hours']) . '" ';
  echo 'phone="' . htmlentities($row['sl_phone']) . '" ';
  echo 'image="' . htmlentities($row['sl_image']) . '" ';
  echo "/>\n";
}

// End XML file
echo "</markers>\n";
