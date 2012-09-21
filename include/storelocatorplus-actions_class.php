<?php

/***********************************************************************
* Class: SLPlus_Actions
*
* The Store Locator Plus action hooks and helpers.
*
* The methods in here are normally called from an action hook that is
* called via the WordPress action stack.  
* 
* See http://codex.wordpress.org/Plugin_API/Action_Reference
*
************************************************************************/

if (! class_exists('SLPlus_Actions')) {
    class SLPlus_Actions {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        
        /*************************************
         * The Constructor
         */
        function __construct($params) {
        } 
        
        /**************************************
         ** method: admin_init()
         **
         ** Called when the WordPress admin_init action is processed.
         **
         ** Builds the interface elements used by WPCSL-generic for the admin interface.
         **
         **/
        function admin_init() {
            global $slplus_plugin;
            
            // Don't have what we need? Leave.
            if (!isset($slplus_plugin)) { return; }
        
            // Already been here?  Get out.
            if (isset($slplus_plugin->settings->sections['How to Use'])) { return; }

            // Add admin helpers
            //
            require_once(SLPLUS_PLUGINDIR . '/include/storelocatorplus-adminui_class.php');
            
            //-------------------------
            // Navbar Section
            //-------------------------    
            $slplus_plugin->settings->add_section(
                array(
                    'name' => 'Navigation',
                    'div_id' => 'slplus_navbar',
                    'description' => get_string_from_phpexec(SLPLUS_COREDIR.'/templates/navbar.php'),
                    'is_topmenu' => true,
                    'auto' => false,
                    'headerbar'     => false        
                )
            );       
          
            //-------------------------
            // How to Use Section
            //-------------------------    
            $slplus_plugin->settings->add_section(
                array(
                    'name' => 'How to Use',
                    'description' => get_string_from_phpexec(SLPLUS_PLUGINDIR.'/how_to_use.txt'),
                    'start_collapsed' => true
                )
            );
        
            //-------------------------
            // Google Communiations
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
                '<a href="http://code.google.com/apis/console/" target="newinfo">'.
                'go to Google</a> to get your Google Maps API Key.'
            );
        
        
            $slplus_plugin->settings->add_item(
                'Google Communication', 
                'Geocode Retries', 
                'goecode_retries', 
                'list', 
                false,
                'How many times should we try to set the latitude/longitude for a new address. ' .
                'Higher numbers mean slower bulk uploads ('.
                '<a href="http://www.charlestonsw.com/product/store-locator-plus/">plus version</a>'.
                '), lower numbers makes it more likely the location will not be set during bulk uploads.',
                array (
                      'None' => 0,
                      '1' => '1',
                      '2' => '2',
                      '3' => '3',
                      '4' => '4',
                      '5' => '5',
                      '6' => '6',
                      '7' => '7',
                      '8' => '8',
                      '9' => '9',
                      '10' => '10',
                    )
            );
            
            //--------------------------
            // Store Pages
            //
            $slp_rep_desc = __('These settings affect how the Store Pages add-on behaves. ', SLPLUS_PREFIX);
            if (!$slplus_plugin->license->AmIEnabled(true, "SLPLUS-PAGES")) {
                $slp_rep_desc .= '<br/><br/>'.
                    __('This is a <a href="http://www.charlestonsw.com/product/store-locator-plus-store-pages/">Store Pages</a>'.
                    ' feature.  It provides a way to auto-create individual WordPress pages' .
                    ' for each of your locations. ', SLPLUS_PREFIX);
            }
            $slp_rep_desc .= '<br/><br/>';                 
            $slplus_plugin->settings->add_section(
                array(
                    'name'        => 'Store Pages',
                    'description' => $slp_rep_desc
                )
            );         
            if ($slplus_plugin->license->AmIEnabled(true, "SLPLUS-PAGES")) {            
                slplus_add_pages_settings();
            }                
            
            //-------------------------
            // Pro Pack
            //
            $slp_rep_desc = __('These settings affect how the Pro Pack add-on behaves. ', SLPLUS_PREFIX);
            if (!$slplus_plugin->license->AmIEnabled(true, "SLPLUS-PRO")) {
                $slp_rep_desc .= '<br/><br/>'.
                    __('This is a <a href="http://www.charlestonsw.com/product/store-locator-plus/">Pro Pack</a>'.
                    ' feature.  It provides more settings and features that are not provided in the free plugin'
                    , SLPLUS_PREFIX);
            }
            $slp_rep_desc .= '<br/><br/>'; 
            $slplus_plugin->settings->add_section(
                array(
                    'name'        => 'Pro Pack',
                    'description' => $slp_rep_desc
                )
            );
            if ($slplus_plugin->license->AmIEnabled(true, "SLPLUS-PRO")) {
                slplus_add_report_settings();
            }                
        }
        
        /**************************************
         ** method: init()
         **
         ** Called when the WordPress init action is processed.
         **
         **/
        function init() {
            global $slplus_plugin;
            
            //--------------------------------
            // Store Pages Is Licensed
            //
            if ($slplus_plugin->license->packages['Store Pages']->isenabled) {

                // Register Store Pages Custom Type
                register_post_type( 'store_page',
                    array(
                        'labels' => array(
                            'name'              => __( 'Store Pages',SLPLUS_PREFIX ),
                            'singular_name'     => __( 'Store Page', SLPLUS_PREFIX ),
                            'add_new'           => __('Add New Store Page', SLPLUS_PREFIX),
                        ),
                    'public'            => true,
                    'has_archive'       => true,
                    'description'       => __('Store Locator Plus location pages.',SLPLUS_PREFIX),
                    'menu_postion'      => 20,   
                    'menu_icon'         => SLPLUS_COREURL . 'images/icon_from_jpg_16x16.png',
                    'capability_type'   => 'page',
                    )
                );                
                
                // Register Stores Taxonomy
                //                
                register_taxonomy(
                        'stores',
                        'store_page',
                        array (
                            'hierarchical'  => true,
                            'labels'        => 
                                array(
                                        'menu_name' => __('Stores',SLPLUS_PREFIX),
                                        'name'      => __('Store Attributes',SLPLUS_PREFIX),
                                     )
                            )
                    );                
            } 
        }
        
        /*************************************
         * method: wp_enqueue_scripts()
         * 
         * This is called whenever the WordPress wp_enqueue_scripts action is called.
         */
        static function wp_enqueue_scripts() {
            global $slplus_plugin;            
            $api_key= (isset($slplus_plugin) && $slplus_plugin->ok_to_show()) ?
                $slplus_plugin->driver_args['api_key'] :
                ''
                ;
            $force_load = (
                        isset($slplus_plugin) ?
                        $slplus_plugin->settings->get_item('force_load_js',true) :
                        false
                    );

            $sl_google_map_domain=(get_option('sl_google_map_domain','')!="")?
                    get_option('sl_google_map_domain') : 
                    "maps.google.com";                
            $sl_map_character_encoding='&oe='.get_option('sl_map_character_encoding','utf8');    

            //------------------------
            // Register our scripts for later enqueue when needed
            //
            //wp_register_script('slplus_functions',SLPLUS_PLUGINURL.'/core/js/functions.js');
            if (isset($api_key))
            {
                wp_enqueue_script(
                        'google_maps',
                        "http://$sl_google_map_domain/maps/api/js?v=3.9&amp;key=$api_key&amp;sensor=false" //todo:character encoding ???
                        //"http://$sl_google_map_domain/maps?file=api&amp;v=2&amp;key=$api_key&amp;sensor=false{$sl_map_character_encoding}"
                        );
            }
            else {
                wp_enqueue_script(
                    'google_maps',
                    "http://$sl_google_map_domain/maps/api/js?v=3.9&amp;sensor=false"
                );
            }

            wp_enqueue_script(
                    'csl_script',
                    SLPLUS_PLUGINURL.'/core/js/csl.js',
                    array('jquery'),
                    false,
                    !$force_load
            );

            //--------------------
            // Localize The Script
            //--------------------
            // Prepare some data for JavaScript injection...
            //
            $slplus_home_icon = get_option('sl_map_home_icon');
            $slplus_end_icon  = get_option('sl_map_end_icon');
            $slplus_home_icon_file = str_replace(SLPLUS_ICONURL,SLPLUS_ICONDIR,$slplus_home_icon);
            $slplus_end_icon_file  = str_replace(SLPLUS_ICONURL,SLPLUS_ICONDIR,$slplus_end_icon);
            $slplus_home_size=(function_exists('getimagesize') && file_exists($slplus_home_icon_file))?
                getimagesize($slplus_home_icon_file) :
                array(0 => 20, 1 => 34);
            $slplus_end_size =(function_exists('getimagesize') && file_exists($slplus_end_icon_file)) ?
                getimagesize($slplus_end_icon_file)  :
                array(0 => 20, 1 => 34);

            // Results Output String In JavaScript Format
            //
            $results_string = '<center>' .
                    '<table width="96%" cellpadding="4px" cellspacing="0" class="searchResultsTable">'  .
                        '<tr class="slp_results_row">'  .
                            '<td class="results_row_left_column"><span class="location_name">{0}</span><br>{1} {2}</td>'  .
                            '<td class="results_row_center_column">{3}{4}{5}{6}{7}</td>'  .
                            '<td class="results_row_right_column">{8}{9}'  .
                                '<a href="http://{10}' .
                                '/maps?saddr={11}'  .
                                '&daddr={12}'  .
                                '" target="_blank" class="storelocatorlink">Directions</a>{13}</td>'  .
                            '</tr>'  .
                        '</table>'  .
                        '</center>';

            // Lets get some variables into our script
            //
            $scriptData = array(
                'debug_mode'        => (get_option(SLPLUS_PREFIX.'-debugging') == 'on'),
                'disable_scroll'    => (get_option(SLPLUS_PREFIX.'_disable_scrollwheel')==1),
                'disable_dir'       => (get_option(SLPLUS_PREFIX.'_disable_initialdirectory' )==1),
                'distance_unit'     => esc_attr(get_option('sl_distance_unit'),'miles'),
                'load_locations'    => (get_option('sl_load_locations_default')==1),
                'map_3dcontrol'     => (get_option(SLPLUS_PREFIX.'_disable_largemapcontrol3d')==0),
                'map_country'       => SetMapCenter(),
                'map_domain'        => get_option('sl_google_map_domain','maps.google.com'),
                'map_home_icon'     => $slplus_home_icon,
                'map_home_sizew'    => $slplus_home_size[0],
                'map_home_sizeh'    => $slplus_home_size[1],
                'map_end_icon'      => $slplus_end_icon,
                'map_end_sizew'     => $slplus_end_size[0],
                'map_end_sizeh'     => $slplus_end_size[1],
                'use_sensor'        => (get_option(SLPLUS_PREFIX."_use_location_sensor")==1),
                'map_scalectrl'     => (get_option(SLPLUS_PREFIX.'_disable_scalecontrol')==0),
                'map_type'          => get_option('sl_map_type','roadmap'),
                'map_typectrl'      => (get_option(SLPLUS_PREFIX.'_disable_maptypecontrol')==0),
                'results_string'    => apply_filters('slp_javascript_results_string',$results_string),
                'show_tags'         => (get_option(SLPLUS_PREFIX.'_show_tags')==1),
                'overview_ctrl'     => get_option('sl_map_overview_control',0),
                'use_email_form'    => (get_option(SLPLUS_PREFIX.'_email_form')==1),
                'use_pages_links'   => ($slplus_plugin->settings->get_item('use_pages_links')=='on'),
                'use_same_window'   => ($slplus_plugin->settings->get_item('use_same_window')=='on'),
                'website_label'     => esc_attr(get_option('sl_website_label','Website')),
                'zoom_level'        => get_option('sl_zoom_level',4),
                'zoom_tweak'        => get_option('sl_zoom_tweak',1),
                );
            wp_localize_script('csl_script','slplus',$scriptData);
            wp_localize_script('csl_script','csl_ajax',array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('em')));
        }     
        

        /*************************************
         * method: wp_footer()
         *
         * This is called whenever the WordPress shutdown action is called.
         */
        function wp_footer() {
            SLPlus_Actions::ManageTheScripts();
		}


        /*************************************
         * method: shutdown()
         * 
         * This is called whenever the WordPress shutdown action is called.
         */
        function shutdown() {
            // Safety for themes not using wp_footer
            SLPlus_Actions::ManageTheScripts();
		}

        // Unload The SLP Scripts If No Shortcode
        //
        function ManageTheScripts() {
            if (!defined('SLPLUS_SCRIPTS_MANAGED') || !SLPLUS_SCRIPTS_MANAGED) {

                // If no shortcode rendered, remove scripts
                //
                if (!defined('SLPLUS_SHORTCODE_RENDERED') || !SLPLUS_SHORTCODE_RENDERED) {
                    wp_dequeue_script('google_maps');
                    wp_deregister_script('google_maps');
                    wp_dequeue_script('csl_script');
                    wp_deregister_script('csl_script');
                }
                define('SLPLUS_SCRIPTS_MANAGED',true);
            }
        }
	}
}
