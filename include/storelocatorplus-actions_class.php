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
        

        /*************************************
         * method: wp_enqueue_scripts()
         * 
         * This is called whenever the WordPress wp_enqueue_scripts action is called.
         */
        static function wp_enqueue_scripts() {
            global $slplus_plugin;
            
            if (isset($slplus_plugin) && $slplus_plugin->ok_to_show()) {            
                $api_key=$slplus_plugin->driver_args['api_key'];
                $google_map_domain=(get_option('sl_google_map_domain')!="")? 
                        get_option('sl_google_map_domain') : 
                        "maps.google.com";                
                $sl_map_character_encoding='&oe='.get_option('sl_map_character_encoding','utf8');    
                
                //------------------------
                // Register our scripts for later enqueue when needed
                //
                wp_register_script('slplus_functions',SLPLUS_PLUGINURL.'/core/js/functions.js');
                wp_register_script(
                        'google_maps',
                        "http://$google_map_domain/maps?file=api&amp;v=2&amp;key=$api_key&amp;sensor=false{$sl_map_character_encoding}"                        
                        );
                wp_register_script(
                        'slplus_map',
                        SLPLUS_PLUGINURL.'/core/js/store-locator-map.js',
                        array('google_maps')
                        ); 
                
                // Setup Email Form Script If Selected
                //                
                if (get_option(SLPLUS_PREFIX.'_email_form')==1) {
                    wp_register_script(
                            'slplus_emailform',
                            SLPLUS_PLUGINURL.'/core/js/store-locator-emailform.js',
                            array('google_maps','slplus_map')
                            );                       
                }

                //------------------------
                // Register our styles for later enqueue when needed
                //                
                if (get_option(SLPLUS_PREFIX . '-theme' ) != '') {
                    setup_stylesheet_for_slplus();
                } else {
                    $has_custom_css=(file_exists($sl_upload_path."/custom-css/csl-slplus.css"))? 
                        $sl_upload_base."/custom-css" : 
                        $sl_base; 
                    wp_register_style('slplus_customcss',$has_custom_css.'/core/css/csl-slplus.css');
                }
                $theme=get_option('sl_map_theme');
                if ($theme!="") {
                    wp_register_style('slplus_themecss',$sl_upload_base.'/themes/'.$theme.'/style.css');
                }                                
            }                        
        }     
        
        
        /*************************************
         * method: shutdown()
         * 
         * This is called whenever the WordPress shutdown action is called.
         */
        function shutdown() {
            
            // If we rendered an SLPLUS shortcode...
            //
            if (defined('SLPLUS_SHORTCODE_RENDERED') && SLPLUS_SHORTCODE_RENDERED) {
                
                // Register Load JavaScript
                //
                wp_enqueue_script('slplus_functions');
                wp_enqueue_script('google_maps');                
                wp_enqueue_script('slplus_map');
                
                if (get_option(SLPLUS_PREFIX.'_email_form')==1) {
                    wp_enqueue_script('slplus_emailform');
                }
            
                // Register & Load CSS
                //
                wp_enqueue_style('slplus_customcss');
                wp_enqueue_style('slplus_themecss');
                
                // Force our scripts to load for badly behaved themes
                //
                wp_print_footer_scripts();
?>                
                <script type='text/javascript'>
                    jQuery(window).load(function() {
                            allScripts=document.getElementsByTagName('script');
                            
                            // Check our scripts were enqueued
                            //
                            if (allScripts.length-1 < 4) {
                                alert('<?php echo __('SLPLUS: The theme or a plugin is preventing trailing JavaScript from loading.',SLPLUS_PREFIX); ?>');
                                
                            // Check the Google Maps was loaded
                            //
                            } else if (typeof GLatLng == 'undefined' ) {        
                                alert('<?php echo __('SLPLUS: Google Map Interface did not load.\n\nCheck your Google API key and make sure you have API V2 enabled.',SLPLUS_PREFIX); ?>');
                        
                            // Yup, set our sl_load to prepopulate map data
                            //
                            } else if (document.getElementById("map")){
                                setTimeout("sl_load()",1000);
                                
                            }
                        }
                    );                
                </script>
<?php                       
            }             
        }            
    }
}        
     

