<?php

// Find the Wordpress Config File...
//
$possible_path = preg_replace('/\/wp-content\/.*/','',$_SERVER['SCRIPT_FILENAME']);

// Document Root Install
//
if (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/wp-config.php')) {
    include($_SERVER['DOCUMENT_ROOT'].'/wp-config.php');

// One Level Up and not part of another install
//
} else if (isset($_SERVER['DOCUMENT_ROOT']) && file_exists(dirname($_SERVER['DOCUMENT_ROOT']).'/wp-config.php')
    && !file_exists(dirname($_SERVER['DOCUMENT_ROOT']).'/wp-settings.php')
    ) {
    include(dirname($_SERVER['DOCUMENT_ROOT']).'/wp-config.php');

    
// Subdomain Install of WordPress
//
} else if (isset($_SERVER['SUBDOMAIN_DOCUMENT_ROOT']) && file_exists($_SERVER['SUBDOMAIN_DOCUMENT_ROOT'].'/wp-config.php')) {
    include($_SERVER['SUBDOMAIN_DOCUMENT_ROOT'].'/wp-config.php');

// A sub-directory
//
} else if (file_exists($possible_path.'/wp-config.php')) {
    include($possible_path.'/wp-config.php');
    
// Hopefully we are on the standard relative path
//
} else if (file_exists('../../../wp-config.php')) {
    include('../../../wp-config.php');    
    
} else if (file_exists('../../../../wp-config.php')) {
    include('../../../../wp-config.php');    
}

// Turn on short open tags
ini_set( "short_open_tag", 1 );
