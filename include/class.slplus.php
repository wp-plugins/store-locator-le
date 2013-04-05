<?php

/**
 * The base plugin class for Store Locator Plus.
 *
 * "gloms onto" the WPCSL base class, extending it for our needs.
 *
 * @package StoreLocatorPlus
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 *
 */
class SLPlus extends wpCSL_plugin__slplus {

    /**
     * An array of the add-on slugs that are active.
     * 
     * @var string[] active add-on slugs 
     */
    public $addons = array();

    /**
     * The current location.
     * 
     * @var SLPlus_Location $currentLocation
     */
    public $currentLocation;

    /**
     * The global $wpdb object for WordPress.
     *
     * @var wpdb $db
     */
    public $db;

    /**
     * Array of slugs + booleans for plugins we've already fetched info for.
     * 
     * @var array[] named array, key = slug, value = true
     */
    public $infoFetched = array();

    /**
     * Initialize a new SLPlus Object
     *
     * @param mixed[] $params - a named array of the plugin options for wpCSL.
     */
    public function __construct($params) {
        global $wpdb;
        $this->db = $wpdb;
        parent::__construct($params);
        $this->currentLocation = new SLPlus_Location(array('plugin'=>$this));
        $this->themes->css_dir = SLPLUS_PLUGINDIR . 'css/';
        do_action('slp_invocation_complete');
    }

    /**
     * Register an add-on pack.
     * 
     * @param string $slug
     */
    public function register_addon($slug) {
        list ($t1, $t2) = explode('/', $slug);
        $this->addons[] = str_replace('.php', '', $t2);
    }

    /**
     * Build a query string of the add-on packages.
     *
     * @return string
     */
    public function create_addon_query() {
        return http_build_query($this->addons,'addon_');
    }

    /**
     * Add DebugMyPlugin messages.
     *
     * @param string $type - what type of debugging (msg = simple string, pr = print_r of variable)
     * @param string $header - the header
     * @param string $message - what you want to say
     * @param string $file - file of the call (__FILE__)
     * @param int $line - line number of the call (__LINE__)
     * @return null
     */
    function debugMP($type='msg', $header='Store Locator Plus',$message='',$file=null,$line=null) {
        if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
        switch (strtolower($type)):
            case 'pr':
                $GLOBALS['DebugMyPlugin']->panels['main']->addPR($header,$message,$file,$line);
                break;
            default:
                $GLOBALS['DebugMyPlugin']->panels['main']->addMessage($header,$message,$file,$line);
        endswitch;
    }
}
