<?php

/***********************************************************************
* Class: wpCSL_themes
*
* Manage the theme system for WordPress plugins.
*
************************************************************************/

class wpCSL_themes__slplus {
    


    /*-------------------------------------
     * method: __construct
     *
     * Overload of the default class instantiation.
     *
     */
    function __construct($params) {
        
        // Properties with default values
        //
        $this->columns = 1;                 // How many columns/row in our display output.
        
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }        
    }
    
    /*-------------------------------------
     * method: add_admin_settings
     *
     * Add the theme settings to the admin panel.
     *
     */
    function add_admin_settings() {
        
        // The Themes
        // No themes? Force the default at least
        //
        $themeArray = get_option($this->prefix.'-theme_array');
        if (count($themeArray, COUNT_RECURSIVE) <= 2) {
            $themeArray = array('Default MP Layout' => 'mp-white-1up');
        }    
    
        // Check for theme files
        //
        $lastNewThemeDate = get_option($this->prefix.'-theme_lastupdated');
        $newEntry = array();
        if ($dh = opendir($this->plugin_path.'/core/css/')) {
            while (($file = readdir($dh)) !== false) {
                
                // If not a hidden file
                //
                if (!preg_match('/^\./',$file)) {                
                    $thisFileModTime = filemtime($this->plugin_path.'/core/css/'.$file);
                    
                    // We have a new theme file possibly...
                    //
                    if ($thisFileModTime > $lastNewThemeDate) {
                        $newEntry = $this->GetThemeInfo($this->plugin_path.'/core/css/'.$file);
                        $themeArray = array_merge($themeArray, array($newEntry['label'] => $newEntry['file']));                                        
                        update_option($this->prefix.'-theme_lastupdated', $thisFileModTime);
                    }
                }
            }
            closedir($dh);
        }
        
        // We added at least one new theme
        //
        if (count($newEntry, COUNT_RECURSIVE) > 1) {
            update_option($this->prefix.'-theme_array',$themeArray);
        }  
            
        $this->settings->add_item(
            __('Display Settings',$this->prefix), 
            __('Select A Theme',$this->prefix),   
            'theme',    
            'list', 
            false, 
            __('How should the plugin UI elements look?',$this->prefix),
            $themeArray
        );        
    }    
    
    /**************************************
     ** method: GetThemeInfo
     ** 
     ** Extract the label & key from a CSS file header.
     **
     **/
    function GetThemeInfo ($filename) {    
        $dataBack = array();
        if ($filename != '') {
           $default_headers = array(
                'label' => 'label',
                'file' => 'file',
                'columns' => 'columns'
               );
            
           $dataBack = get_file_data($filename,$default_headers,'');
           $dataBack['file'] = preg_replace('/.css$/','',$dataBack['file']);       
        }
        
        return $dataBack;
     }    

 
    /**************************************
     ** method: configure_theme
     ** 
     ** Configure the plugin theme drivers based on the theme file meta data.
     **
     **/
     function configure_theme($themeFile) {
        $newEntry = $this->GetThemeInfo($this->plugin_path.'/core/'.$themeFile);
        $this->products->columns = $newEntry['columns'];
     }
     
    /**************************************
     ** function: assign_user_stylesheet
     **
     ** Set the user stylesheet to what we selected.
     **/
    function assign_user_stylesheet() {
        $theme = get_option($this->prefix.'-theme');
        if ($theme == '') { $theme='mp-white-1up'; }
        $themeFile = "css/$theme.css";
        
        if ( file_exists($this->plugin_path.'/core/'.$themeFile)) {
            wp_register_style($this->prefix.'_user_css', $this->plugin_url . '/core/' .$themeFile); 
            wp_enqueue_style ($this->prefix.'_user_css');
            $this->configure_theme($themeFile);
        }
    }     
}
