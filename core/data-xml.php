<?php
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
    "25";
    
// Select all the rows in the markers table
$query = "SELECT sl_address, sl_address2, sl_store, sl_city, sl_state, ".
    "sl_zip, sl_country, sl_latitude, sl_longitude, sl_description, sl_url, ".
    "sl_email, sl_hours, sl_phone, sl_image FROM ".$wpdb->prefix."store_locator ".
    "WHERE sl_store<>'' AND sl_longitude<>'' AND sl_latitude<>'' ".
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
  echo "/>\n";
}

// End XML file
echo "</markers>\n";
