<?php

/**
 * The wpCSL Themes Class
 *
 * @package wpCSL\Themes
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2014 Charleston Software Associates, LLC
 *
 */
class PluginTheme {

    //-------------------------------------
    // Properties
    //-------------------------------------

    /**
     * The theme CSS directory, absolute.
     * 
     * @var string $css_dir
     */
    public $css_dir;

    /**
     * The theme CSS URL, absolute.
     *
     * @var string $css_url
     */
    public $css_url;

    /**
     * Plugin notifications system.
     *
     * @var \wpCSL_notifications__slplus $notifications
     */
    public $notifications;

    /**
     * The plugin base object.
     * 
     * @var \wpCSL_plugin__slplus $parent
     */
    private $parent;

    /**
     *
     * @var string $plugin_path
     */
    private $plugin_path;

    /**
     * Full web address to this plugin directory.
     *
     * @var string $plugin_url
     */
    private $plugin_url;

    /**
     * The CSS and name space prefix for the plugin.
     * 
     * @var string $prefix
     */
    public $prefix;

    /**
     * Full web address to the support web pages.
     *
     * @var string $support_url
     */
    private $support_url;

    //-------------------------------------
    // Methods
    //-------------------------------------

    /**
     * Theme constructor.
     * 
     * @param mixed[] $params named array of properties
     */
    function __construct($params) {
        
        // Properties with default values
        //
        $this->css_dir = 'css/';
        
        foreach ($params as $name => $value) {            
            $this->$name = $value;
        }

        // Remember the base directory path, then
        // Append plugin path to the directories
        //
        $this->css_url = $this->plugin_url . '/'. $this->css_dir;
        $this->css_dir = $this->plugin_path . $this->css_dir;

        // Load Up Admin Class As Needed
        //
        if ( $this->parent->check_IsOurAdminPage() ) {
            require_once('class.themes.admin.php');
            $this->admin = 
                new PluginThemeAdmin(
                    array_merge(
                        $params,
                        array(
                            'css_dir'   => $this->css_dir,
                            'css_url'   => $this->css_url
                        )
                     )
                );
        }
    }

     /**
      * Assign the plugin specific UI stylesheet.
      *
      * For this to work with shortcode testing you MUST call it via the WordPress wp_footer action hook.
      *
      * @param string $themeFile if set use this theme v. the database setting
      * @param boolean $preRendering
      */
    function assign_user_stylesheet($themeFile = '',$preRendering = false) {
        // If themefile not passed, fetch from db
        //
        if ($themeFile == '') {
            $themeFile = get_option($this->prefix.'-theme','default') . '.css';

        } else {
            // append .css if left off
            if ((strlen($themeFile) < 4) || substr_compare($themeFile, '.css', -strlen('.css'), strlen('.css')) != 0) {
                $themeFile .= '.css';
            }
        }

        // go to default if theme file is missing
        //
        if ( !file_exists($this->css_dir.$themeFile)) {
            $themeFile = 'default.css';
        }

        // If the theme file exists (after forcing default if necessary)
        // queue it up
        //
        if ( file_exists($this->css_dir.$themeFile)) {
            wp_deregister_style($this->prefix.'_user_header_css');
            wp_dequeue_style($this->prefix.'_user_header_css');
            if ($this->parent->shortcode_was_rendered || $preRendering) {
                wp_enqueue_style($this->prefix.'_user_header_css', $this->css_url .$themeFile);
            }
        }
    }  
}
