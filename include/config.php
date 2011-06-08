<?php

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
    
    //// SETTINGS ////////////////////////////////////////////////////////
    
    /**
     * This section defines the settings for the admin menu.
     */       
    $slplus_plugin = new wpCSL_plugin__slplus(
        array(
            'use_obj_defaults'      => true,        
            'prefix'                => SLPLUS_PREFIX,
            'name'                  => 'Store Locator LE',
            'url'                   => 'http://www.cybersprocket.com/products/store-locator-le/',
            'basefile'              => SLPLUS_BASENAME,
            'plugin_path'           => SLPLUS_PLUGINDIR,
            'plugin_url'            => SLPLUS_PLUGINURL,
            'cache_path'            => SLPLUS_PLUGINDIR . 'cache',
            'driver_type'           => 'none',
            'driver_args'           => array(
                    'api_key'   => get_option(SLPLUS_PREFIX.'-api_key'),
                    )
        )
    );    
}    
    
/**************************************
 ** function: csl_slplus_setup_admin_interface
 **
 ** Builds the interface elements used by WPCSL-generic for the admin interface.
 **/
function csl_slplus_setup_admin_interface() {
    global $slplus_plugin;
    
    // Don't have what we need? Leave.
    if (!isset($slplus_plugin)) { return; }
    
    
    // No SimpleXML Support
    if (!function_exists('parsetoxml')) {
        $slplus_plugin->notifications->add_notice(1, __('SimpleXML is required but not enabled.',SLPLUS_PREFIX));
    }

    // Already been here?  Get out.
    if (isset($slplus_plugin->settings->sections['How to Use'])) { return; }
    
    
    //-------------------------
    // How to Use Section
    //-------------------------    
    $slplus_plugin->settings->add_section(
        array(
            'name' => 'How to Use',
            'description' => get_string_from_phpexec(SLPLUS_PLUGINDIR.'how_to_use.txt')
        )
    );

    //-------------------------
    // General Settings
    //-------------------------    
    $slplus_plugin->settings->add_section(
        array(
            'name'        => 'Google Communication',
            'description' => 'These settings affect how the plugin communicates with Google to create your map.'.
                                '<br/><br/>'
        )
    );
    
    $slplus_plugin->settings->add_item(
        'Google Communication', 
        'Google API Key', 
        'api_key', 
        'text', 
        false,
        'Your Google API Key.  You will need to ' .
        '<a href="http://code.google.com/apis/maps/signup.html" target="newinfo">'.
        'go to Google</a> to get your Google Maps API Key.'
    );
    
}

