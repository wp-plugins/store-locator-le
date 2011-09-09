<?php
/******************************************************************************
 * File: store-locator-js.php 
 * 
 * Wires the WordPress options from the database into javascript variables that
 * we will use to change how the javascript elements work, such as the Google
 * Maps parameters.
 *
 ******************************************************************************/

error_reporting(0);
header("Content-type: text/javascript");

// Make the connection to the WordPress environment
//
if (!file_exists('../load_wp_config.php')) {
    echo "alert('SLPLUS: Cannot load WordPress configuration file.');";
    return;
}
include('../load_wp_config.php');

if (!function_exists('get_option')) {
    echo "alert('Unable to load WordPress configuration. [Store Locator Plus]');";
    return;
}

// Setup our base variables needed to make the plugin work
//
include("../variables.sl.php");

if(get_option(SLPLUS_PREFIX.'-debugging') == 'on') {
    error_reporting(1);
}

if (ereg($sl_upload_base, get_option('sl_map_home_icon'))){
	$home_icon_path=ereg_replace($sl_upload_base, $sl_upload_path, get_option('sl_map_home_icon'));
} else {
	$home_icon_path=ereg_replace($sl_base, $sl_path, get_option('sl_map_home_icon'));
}

if (ereg($sl_upload_base, get_option('sl_map_end_icon'))){
	$end_icon_path=ereg_replace($sl_upload_base, $sl_upload_path, get_option('sl_map_end_icon'));
} else {
	$end_icon_path=ereg_replace($sl_base, $sl_path, get_option('sl_map_end_icon'));
}

$zl=(trim(get_option('sl_zoom_level'))!="")? 
    get_option('sl_zoom_level') : 
    4;
$mt=(trim(get_option('sl_map_type'))!="")? 
    get_option('sl_map_type') : 
    "G_NORMAL_MAP";
$wl=(trim(get_option('sl_website_label'))!="")? 
    htmlentities(get_option('sl_website_label')) : 
    "Website";
$du=(trim(get_option('sl_distance_unit'))!="")? 
    get_option('sl_distance_unit') : 
    "miles";
$oc=(trim(get_option('sl_map_overview_control'))!="")? 
    get_option('sl_map_overview_control') : 
    0;
$home_size=(function_exists('getimagesize') && file_exists($home_icon_path))? 
    getimagesize($home_icon_path) : 
    array(0 => 20, 1 => 34);    
$end_size =(function_exists('getimagesize') && file_exists($end_icon_path)) ? 
    getimagesize($end_icon_path)  : 
    array(0 => 20, 1 => 34);

//-----------------------------------------------
// Setup the javascript variable we'll need later
//
print "
if (document.getElementById('map')){window.onunload = function (){ GUnload(); }}
var allScripts=document.getElementsByTagName('script');
var add_base=allScripts[allScripts.length -1].src.replace('/js/store-locator-js.php','');
var add_upload_base='$sl_upload_base';
var slp_encryption_code='".$slp_enc_key."';
var sl_zoom_level=$zl;
var sl_map_type=$mt;
var sl_website_label='$wl';
var sl_distance_unit='$du';
var sl_map_overview_control='$oc';
var sl_map_home_icon_width=$home_size[0];
var sl_map_home_icon_height=$home_size[1];
var sl_map_end_icon_width=$end_size[0];
var sl_map_end_icon_height=$end_size[1];

var sl_map_home_icon='"         .get_option('sl_map_home_icon')         ."';
var sl_map_end_icon='"          .get_option('sl_map_end_icon')          ."';
var sl_google_map_domain='"     .get_option('sl_google_map_domain')     ."';

var sl_google_map_country='".htmlentities(get_option('sl_google_map_country'))."';

var sl_load_locations_default="     .((get_option('sl_load_locations_default'               )==1)?'true':'false').";
var slp_use_email_form="            .((get_option(SLPLUS_PREFIX.'_email_form'               )==1)?'true':'false').";
var slp_disablescrollwheel="        .((get_option(SLPLUS_PREFIX.'_disable_scrollwheel'      )==1)?'true':'false').";
var slp_disableinitialdirectory="   .((get_option(SLPLUS_PREFIX.'_disable_initialdirectory' )==1)?'true':'false').";

// These controls have inverse logic
var slp_largemapcontrol3d=" .((get_option(SLPLUS_PREFIX.'_disable_largemapcontrol3d')==1)?'false':'true').";
var slp_scalecontrol="      .((get_option(SLPLUS_PREFIX.'_disable_scalecontrol'     )==1)?'false':'true').";
var slp_maptypecontrol="    .((get_option(SLPLUS_PREFIX.'_disable_maptypecontrol'   )==1)?'false':'true').";
";

