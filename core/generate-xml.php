<?php
error_reporting(0);
header("Content-type: text/xml");
include("database-info.php");

// Get parameters from URL
$center_lat = $_GET["lat"];
$center_lng = $_GET["lng"];
$radius = $_GET["radius"];

//Since miles is default, if kilometers is selected, divide by 1.609344 in order to convert the kilometer value selection back in miles when generating the XML
//
$multiplier=3959;
$multiplier=(get_option('sl_distance_unit')=="km")? ($multiplier*1.609344) : $multiplier;


//-----------------
// Set the active MySQL database
//
$connection=mysql_connect ($host, $username, $password);
if (!$connection) { die('Not connected : ' . mysql_error()); }
$db_selected = mysql_select_db($database, $connection);
mysql_query("SET NAMES utf8");
if (!$db_selected) {
  die ('Can\'t use db : ' . mysql_error());
}

//-----------------
// Show Tag Search Is Enabled
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

// Select all the rows in the markers table
$query = sprintf(
	"SELECT sl_address, sl_address2, sl_store, sl_city, sl_state, sl_zip, ".
	"sl_country, sl_latitude, sl_longitude, sl_description, sl_url, sl_email, sl_hours, ".
	"sl_phone, sl_tags, sl_image,".
	"( $multiplier * acos( cos( radians('%s') ) * cos( radians( sl_latitude ) ) * cos( radians( sl_longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( sl_latitude ) ) ) ) AS sl_distance ".
	"FROM ".$wpdb->prefix."store_locator HAVING (sl_distance < '%s') ".
	$tag_filter .
	'ORDER BY sl_distance',
	mysql_real_escape_string($center_lat),
	mysql_real_escape_string($center_lng),
	mysql_real_escape_string($center_lat),
	mysql_real_escape_string($radius)
	);


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
  echo 'name="' . parseToXML($row['sl_store']) . '" ';
  echo 'address="' . 
        parseToXML($row['sl_address']) . ', '. 
        parseToXML($row['sl_address2']) . ', '.
        parseToXML($row['sl_city']). ', ' .parseToXML($row['sl_state']).' ' .
        parseToXML($row['sl_zip']).'" ';
  echo 'lat="' . $row['sl_latitude'] . '" ';
  echo 'lng="' . $row['sl_longitude'] . '" ';
  echo 'distance="' . $row['sl_distance'] . '" ';
  echo 'description="' . parseToXML($row['sl_description']) . '" ';
  echo 'url="' . parseToXML($row['sl_url']) . '" ';
  echo 'email="' . parseToXML($row['sl_email']) . '" ';
  echo 'hours="' . parseToXML($row['sl_hours']) . '" ';
  echo 'phone="' . parseToXML($row['sl_phone']) . '" ';
  echo 'image="' . parseToXML($row['sl_image']) . '" ';
  if ($tag_filter != '') {
  	  echo 'tags="'  . parseToXML($row['sl_tags']) . '" ';
  }  	  
  echo "/>\n";
}

// End XML file
echo "</markers>\n";

//print $query;
//print "\nPosted Tag: *$posted_tag*\n";

