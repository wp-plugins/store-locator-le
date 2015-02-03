<?php
/*
Plugin Name: Store Locator Plus
Plugin URI: http://www.storelocatorplus.com/
Description: Add a location finder or directory to your site in minutes. A Google Business Maps API licensed product. Extensive premium add-on library available!
Version: 4.2.31
Tested up to: 4.1
Author: Charleston Software Associates
Author URI: http://www.storelocatorplus.com
License: GPL3

Text Domain: csa-slplus
Domain Path: /languages/

Copyright 2012 - 2015  Charleston Software Associates (info@charlestonsw.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if (!defined( 'ABSPATH'     )) { exit;   } // Exit if accessed directly, dang hackers

if (defined('SLPLUS_VERSION') === false) {
    define('SLPLUS_VERSION', '4.2.31');
}

// Drive Path Defines
//
if (defined('SLPLUS_PLUGINDIR') === false) {
    define('SLPLUS_PLUGINDIR', plugin_dir_path(__FILE__));
}
if (defined('SLPLUS_COREDIR') === false) {
    define('SLPLUS_COREDIR', SLPLUS_PLUGINDIR . 'core/');
}
if (defined('SLPLUS_ICONDIR') === false) {
    define('SLPLUS_ICONDIR', SLPLUS_PLUGINDIR . 'images/icons/');
}


// URL Defines
//
if (defined('SLPLUS_PLUGINURL') === false) {
    define('SLPLUS_PLUGINURL', plugins_url('',__FILE__));
}
if (defined('SLPLUS_COREURL') === false) {
    define('SLPLUS_COREURL', SLPLUS_PLUGINURL . '/core/');
}
if (defined('SLPLUS_ICONURL') === false) {
    define('SLPLUS_ICONURL', SLPLUS_PLUGINURL . '/images/icons/');
}
if (defined('SLPLUS_ADMINPAGE') === false) {
    define('SLPLUS_ADMINPAGE', admin_url() . 'admin.php?page=' . SLPLUS_COREDIR );
}
if (defined('SLPLUS_PLUSPAGE') === false) {
    define('SLPLUS_PLUSPAGE', admin_url() . 'admin.php?page=' . SLPLUS_PLUGINDIR );
}
// The relative path from the plugins directory
//
if (defined('SLPLUS_BASENAME') === false) {
    define('SLPLUS_BASENAME', plugin_basename(__FILE__));
}

// SLP Uploads Dir
//
if (defined('SLPLUS_UPLOADDIR') === false) {
    $upload_dir = wp_upload_dir('slp');
    $error = $upload_dir['error'];
    if (empty($error)) {
        define('SLPLUS_UPLOADDIR', $upload_dir['path']);
        define('SLPLUS_UPLOADURL', $upload_dir['url']);
    } else {
        $error = preg_replace(
                '/Unable to create directory /',
                'Unable to create directory ' . ABSPATH ,
                $error
                );
        define('SLPLUS_UPLOADDIR', SLPLUS_PLUGINDIR);
        define('SLPLUS_UPLOADURL', SLPLUS_PLUGINURL);
    }
}

// Our product prefix
//
if (defined('SLPLUS_PREFIX') === false) {
    define('SLPLUS_PREFIX', 'csl-slplus');
}

// Admin Page Slug Prefix
if (defined('SLP_ADMIN_PAGEPRE') === false) {
    define('SLP_ADMIN_PAGEPRE', 'store-locator-plus_page_');
}

//====================================================================
// Main Plugin Configuration
//====================================================================

/**
 * @var SLPlus $slplus_plugin an extended wpCSL object for this plugin.
 */
global $slplus_plugin;

/**
 * We need the generic WPCSL plugin class, since that is thed
 * foundation of much of our plugin.  So here we make sure that it has
 * not already been loaded by another plugin that may also be
 * installed, and if not then we load it.
 */
if (defined('SLPLUS_PLUGINDIR')) {

    // Hook up WPCSL
    //
    if (class_exists('wpCSL_plugin__slplus') === false) {
        require_once(SLPLUS_PLUGINDIR.'lib/class.plugin.php');
    }

    // SLPlus Base Class
    //
    if (class_exists('SLPlus') == false) {
        require_once(SLPLUS_PLUGINDIR.'include/class.slplus.php');
    }

    // Hook up the Activation class
    //
    if (class_exists('SLPlus_Activation') == false) {
        require_once(SLPLUS_PLUGINDIR.'include/class.activation.php');
    }

    // Hook up the Locations class
    //
    if (class_exists('SLPlus_Location') == false) {
        require_once(SLPLUS_PLUGINDIR.'include/class.location.php');
    }

    /**
     * This section defines the settings for the admin menu.
     */
    global $wpdb;
    $slplus_plugin = new SLPlus(
        array(
            'on_update' => array('SLPlus_Activate', 'update'),
            'version' => SLPLUS_VERSION,


            // We don't want default wpCSL objects, let's set our own
            //
            'use_obj_defaults'      => false,
            'helper_obj_name'       => 'default',
            'notifications_obj_name'=> 'default',
            'settings_obj_name'     => 'default',

            'themes_enabled'        => true,
            'themes_obj_name'       => 'default',
            'no_default_css'        => true,

            'prefix'                => SLPLUS_PREFIX,
            'css_prefix'            => SLPLUS_PREFIX,
            'name'                  => __('Store Locator Plus','csa-slplus'),
            'admin_slugs'           => array(
                'slp_general_settings'                          ,
                'settings_page_csl-slplus-options'              ,
                'slp_general_settings'  ,
                SLP_ADMIN_PAGEPRE . 'slp_general_settings'  ,
                'slp_info'              ,
                SLP_ADMIN_PAGEPRE . 'slp_info'              ,
                'slp_manage_locations'  ,
                SLP_ADMIN_PAGEPRE . 'slp_manage_locations'  ,
                'slp_map_settings'      ,
                SLP_ADMIN_PAGEPRE . 'slp_map_settings'      ,
                ),
            'admin_main_slug'       => 'slp_info'               ,

            'url'                   => 'http://www.storelocatorplus.com/',
            'wp_downloads_url'      => 'http://wordpress.org/plugins/store-locator-le/developers/',
            'support_url'           => 'http://www.storelocatorplus.com/support/documentation/store-locator-plus/',
            'purchase_url'          => 'http://www.storelocatorplus.com/product-category/slp4-products/',
            'rate_url'              => 'http://wordpress.org/extend/plugins/store-locator-le/',
            'forum_url'             => 'http://www.storelocatorplus.com/forums/',
            'updater_url'           => 'http://www.storelocatorplus.com/paypal/updater.php',
            'broadcast_url'         => 'http://www.storelocatorplus.com/signage/index.php?sku=SLP4&version='.SLPLUS_VERSION,

            'fqfile'                => __FILE__,
            'basefile'              => SLPLUS_BASENAME,
            'plugin_path'           => SLPLUS_PLUGINDIR,
            'plugin_url'            => SLPLUS_PLUGINURL,
        )
    );
}

//====================================================================
// Add Required Libraries
//====================================================================


// Errors?
//
if ($error != '') {
    $slplus_plugin->notifications->add_notice(4,$error);
}

// General WP Action Interface
//
//instantiated via admin_init() only...
// adminUI class
// Activation class
//
require_once(SLPLUS_PLUGINDIR . 'include/class.actions.php');
$slplus_plugin->Actions = new SLPlus_Actions(array('slplus'=>$slplus_plugin));

require_once(SLPLUS_PLUGINDIR . 'include/class.activation.php');

require_once(SLPLUS_PLUGINDIR . 'include/class.ui.php');
$slplus_plugin->UI = new SLPlus_UI(array('slplus'=>$slplus_plugin));

require_once(SLPLUS_PLUGINDIR . 'include/class.wpml.php');
$slplus_plugin->WPML = new SLPlus_WPML(array('parent'=>$slplus_plugin));

require_once(SLPLUS_PLUGINDIR . 'include/class.ajax.mobilelistener.php');

require_once(SLPLUS_PLUGINDIR . 'include/class.ajaxhandler.php');
$slplus_plugin->AjaxHandler = new SLPlus_AjaxHandler(array('parent'=>$slplus_plugin));


//====================================================================
// WordPress Hooks and Filters
//====================================================================


// Regular Actions
//
add_action('init'               ,array($slplus_plugin->Actions,'init')                 );
add_action('wp_footer'          ,array($slplus_plugin->Actions,'wp_footer')            );
add_action('wp_head'            ,array($slplus_plugin->Actions,'wp_head')              );
add_action('shutdown'           ,array($slplus_plugin->Actions,'shutdown')             );

// Admin Actions
//
add_action('admin_menu'         ,array($slplus_plugin->Actions,'admin_menu')           );
add_action('admin_init'         ,array($slplus_plugin->Actions,'admin_init'),10        );

add_action('dmp_addpanel'       ,array($slplus_plugin->Actions,'create_DMPPanels')     );

//------------------------
// AJAX Hooks
//------------------------

// Mobile Listener
//
add_action('wp_ajax_csl_get_locations'          , array('csl_mobile_listener', 'GetLocations'));
add_action('wp_ajax_nopriv_csl_get_locations'   , array('csl_mobile_listener', 'GetLocations'));

// Ajax search
//
add_action('wp_ajax_csl_ajax_search'            , array($slplus_plugin->AjaxHandler,'csl_ajax_search'));
add_action('wp_ajax_nopriv_csl_ajax_search'     , array($slplus_plugin->AjaxHandler,'csl_ajax_search'));

add_action('wp_ajax_nopriv_csl_get_closest_location', array('csl_mobile_listener', 'GetClosestLocation'));

// Ajax Load
//
add_action('wp_ajax_csl_ajax_onload'            , array($slplus_plugin->AjaxHandler,'csl_ajax_onload'));
add_action('wp_ajax_nopriv_csl_ajax_onload'     , array($slplus_plugin->AjaxHandler,'csl_ajax_onload'));

//====================================================================
// WordPress Shortcodes and Text Filters
//====================================================================

// Short Codes
//
add_shortcode( 'STORE-LOCATOR'  , array( $slplus_plugin->UI , 'render_shortcode' ) );
add_shortcode( 'SLPLUS'         , array( $slplus_plugin->UI , 'render_shortcode' ) );
add_shortcode( 'slplus'         , array( $slplus_plugin->UI , 'render_shortcode' ) );
