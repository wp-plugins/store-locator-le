<?php
/*
Plugin Name: Store Locator Plus
Plugin URI: http://www.charlestonsw.com/products/store-locator-plus/
Description: Manage multiple locations with ease. Map stores or other points of interest with ease via Gooogle Maps.  This is a highly customizable, easily expandable, enterprise-class location management system.
Version: 3.8.18
Author: Charleston Software Associates
Author URI: http://www.charlestonsw.com
License: GPL3

Copyright 2013  Charleston Software Associates (info@charlestonsw.com)

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

===
 * Code Docs Hints (PHPDoc Format)
 *
 * Filters are documented with @see http://goo.gl/ooXFC
 * The plugin object extended data ($slplus_plugin->data[]) elements are documented with @see http://goo.gl/UAXly

*/

if ( 
        (get_option('blogname'          ,'') === 'CSA Testing'    ) &&
        (get_option('blogdescription'  ,'') === 'Lance Testing' ) 
    ){
    error_reporting(-1);
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

//====================================================================
// Main Plugin Configuration ($slplus_plugin)
//====================================================================
global $slplus_plugin;

/**
 * We need the generic WPCSL plugin class, since that is the
 * foundation of much of our plugin.  So here we make sure that it has
 * not already been loaded by another plugin that may also be
 * installed, and if not then we load it.
 */
if (defined('SLPLUS_PLUGINDIR')) {
    if (class_exists('wpCSL_plugin__slplus') === false) {
        require_once(SLPLUS_PLUGINDIR.'WPCSL-generic/classes/CSL-plugin.php');
    }

    if (class_exists('SLPlus_Activation') == false) {
        require_once(SLPLUS_PLUGINDIR.'include/storelocatorplus-activation_class.php');
    }

    /**
     * This section defines the settings for the admin menu.
     */
    $slplus_plugin = new wpCSL_plugin__slplus(
        array(
            'on_update' => array('SLPlus_Activate', 'update'),
            'version' => '3.8.17',


            // Plugin data elements, helps make data lookups more efficient
            //
            // 'data' is where actual values are stored
            // 'dataElements' is used to fetch/initialize values whenever helper->loadPluginData() is called
            //
            'data'                  => array(),
            'dataElements'          =>
                array(
                      array(
                        'sl_admin_locations_per_page',
                        'get_option',
                        array('sl_admin_locations_per_page','25')
                      ),
                      array(
                        'sl_map_end_icon'                   ,
                        'get_option'                ,
                        array('sl_map_end_icon'         ,SLPLUS_ICONURL.'bulb_azure.png'    )
                      ),
                      array('sl_map_home_icon'              ,
                          'get_option'              ,
                          array('sl_map_home_icon'      ,SLPLUS_ICONURL.'box_yellow_home.png'  )
                      ),
                      array('sl_map_height'         ,
                          'get_option'              ,
                          array('sl_map_height'         ,'480'                                  )
                      ),
                      array('sl_map_height_units'   ,
                          'get_option'              ,
                          array('sl_map_height_units'   ,'px'                                   )
                      ),
                      array('sl_map_width'          ,
                          'get_option'              ,
                          array('sl_map_width'          ,'100'                                  )
                      ),
                      array('sl_map_width_units'    ,
                          'get_option'              ,
                          array('sl_map_width_units'    ,'%'                                    )
                      ),
                      array('theme'                 ,
                          'get_item'                ,
                          array('theme'                 ,'default'                              )
                      ),
                ),

            // We don't want default wpCSL objects, let's set our own
            //
            'use_obj_defaults'      => false,
            'cache_obj_name'        => 'none',
            'helper_obj_name'       => 'default',
            'license_obj_name'      => 'default',
            'notifications_obj_name'=> 'default',
            'products_obj_name'     => 'none',
            'settings_obj_name'     => 'default',

            'themes_obj_name'       => 'default',
            'no_default_css'        => true,

            'prefix'                => SLPLUS_PREFIX,
            'css_prefix'            => SLPLUS_PREFIX,
            'name'                  => 'Store Locator Plus',
            'sku'                   => 'SLPLUS',
            'admin_slugs'           => array('slp_general_settings'),

            'url'                   => 'http://www.charlestonsw.com/product/store-locator-plus-2/',
            'support_url'           => 'http://www.charlestonsw.com/support/documentation/store-locator-plus/',
            'purchase_url'          => 'http://www.charlestonsw.com/product/store-locator-plus-2/',
            'rate_url'              => 'http://wordpress.org/extend/plugins/store-locator-le/',
            'forum_url'             => 'http://wordpress.org/support/plugin/store-locator-le',
            'updater_url'           => 'http://www.charlestonsw.com/updater/index.php',

            'basefile'              => SLPLUS_BASENAME,
            'plugin_path'           => SLPLUS_PLUGINDIR,
            'plugin_url'            => SLPLUS_PLUGINURL,
            'cache_path'            => SLPLUS_PLUGINDIR . 'cache',

            'uses_money'            => false,

            'driver_type'           => 'none',
            'driver_args'           => array(
                    'api_key'   => get_option(SLPLUS_PREFIX.'-api_key'),
                    'app_id'    => 'CyberSpr-',
                    'plus_pack_enabled' => get_option(SLPLUS_PREFIX.'-SLPLUS-isenabled'),
                    ),

            'has_packages'           => true,
            'debug_instructions'     => __('Turn debugging off via General Settings in the Plugin Environment panel.','csa-slplus')
        )
    );


    // Pro Pack
    //
    require_once(SLPLUS_PLUGINDIR . '/slp-pro/slp-pro.php');
    if (isset($slplus_plugin->ProPack)) {
        $slplus_plugin->ProPack->add_package();
    }

    // Store Pages
    require_once(SLPLUS_PLUGINDIR . '/slp-pages/slp-pages.php');
    if (isset($slplus_plugin->StorePages)) {
        $slplus_plugin->StorePages->add_package();
    }
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
require_once(SLPLUS_PLUGINDIR . '/include/storelocatorplus-actions_class.php');
$slplus_plugin->Actions = new SLPlus_Actions(array('parent'=>$slplus_plugin));     // Lets invoke this and make it an object

require_once(SLPLUS_PLUGINDIR . '/include/storelocatorplus-activation_class.php');

require_once(SLPLUS_PLUGINDIR . '/include/storelocatorplus-ui_class.php');
$slplus_plugin->UI = new SLPlus_UI(array('parent'=>$slplus_plugin));

require_once(SLPLUS_PLUGINDIR . '/include/mobile-listener.php');

require_once(SLPLUS_PLUGINDIR . '/include/storelocatorplus-ajax_handler_class.php');
$slplus_plugin->AjaxHandler = new SLPlus_AjaxHandler(array('parent'=>$slplus_plugin));     // Lets invoke this and make it an object


//====================================================================
// WordPress Hooks and Filters
//====================================================================


// Regular Actions
//
add_action('init'               ,array($slplus_plugin->Actions,'init')                 );
add_action('wp_enqueue_scripts' ,array($slplus_plugin->Actions,'wp_enqueue_scripts')   );
add_action('wp_footer'          ,array($slplus_plugin->Actions,'wp_footer')            );
add_action('wp_head'            ,array($slplus_plugin->Actions,'wp_head')              );
add_action('shutdown'           ,array($slplus_plugin->Actions,'shutdown')             );

// Admin Actions
//
add_action('admin_menu'         ,array($slplus_plugin->Actions,'admin_menu')           );
add_action('admin_init'         ,array($slplus_plugin->Actions,'admin_init'),10        );
if (isset($slplus_plugin->ProPack)) {
    add_action('admin_head'         ,array($slplus_plugin->ProPack,'report_downloads')     );
}

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

// License resets
add_action('wp_ajax_license_reset_pages'        , array($slplus_plugin->AjaxHandler,'license_reset_pages'));
add_action('wp_ajax_license_reset_propack'      , array($slplus_plugin->AjaxHandler,'license_reset_propack'));


//====================================================================
// WordPress Shortcodes and Text Filters
//====================================================================

// Short Codes
//
add_shortcode('STORE-LOCATOR', array($slplus_plugin->UI,'render_shortcode'));
add_shortcode('SLPLUS',array($slplus_plugin->UI,'render_shortcode'));
add_shortcode('slplus',array($slplus_plugin->UI,'render_shortcode'));


// Text Domains
//
load_plugin_textdomain('csa-slplus', false, SLPLUS_COREDIR . 'languages/');

