<?php

/**
 * A collection of classes that help us provide a consistent plugin experience for WordPress.
 * 
 * This class does most of the heavy lifting for creating a plugin.
 * It takes a hash as its one constructor argument, which can have the
 * following keys and values:
 *
 *     * 'basefile' :: Path and filename of main plugin file. Needed so wordpress
 *               can tell which plugin is calling some of it's generic hooks.
 *
 *     * 'css_prefix' :: The prefix to add to CSS classes, use 'csl_theme' to
 *               enable generic themes.
 *
 *     * 'name' :: The name of the plugin.
 *
 *     * 'prefix' :: A string used to prefix all of the Wordpress
 *       settings for the plugin.
 *
 *     * 'support_url' :; The URL for the support page at WordPress
 *
 *     * 'purchase_url' :: The URL for purchasing the plugin
 *
 *     * 'url' :: The URL for the product page for purchases.
 *
 * */

/**
 * The base WPCSL class, to which all the other WPCSL objects get attached.
 *
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 Charleston Sofware Associates, LLC
 * @package wpCSL
 * @version 2.5.2
 *
 */
class wpCSL_plugin__slplus {
    //---------------------------------------------
    // Properties
    //---------------------------------------------

    /**
     * Allows a different slug to be the main admin page.
     * 
     * @var string $admin_main_slug 
     */
    private $admin_main_slug = '';

    /**
     * The registered admin page hooks for the plugin.
     * 
     * @var string[] $admin_slugs
     */
    private $admin_slugs = array();

    /**
     * Debug My Plugin stack
     * 
     * named array, key is the panel ID
     * 
     * key is an array that is the params for the DMP function calls.
     * 
     * @var mixed[]
     */
    private $dmpStack = array('main' => array());

    /**
     * True if we are on an admin page for the plugin.
     * 
     * @var boolean $isOurAdminPage
     */
    public $isOurAdminPage = false;

    /**
     * The plugin meta data.
     *
     * @var mixed[] $metadata
     */
    public $metadata;

    /**
     * The fully qualified directory name where the plugin is installed.
     *
     * @var string $plugin_path
     */
    public $plugin_path;

    /**
     * The URL that reaches the home directory for the plugin.
     *
     * @var string $plugin_url
     */
    public $plugin_url;

    /**
     * The wpCSL style sheet handle.
     * 
     * @var string $styleHandle
     */
    private $styleHandle = 'wpcsl';

    //-------------------------------
    // Primary Object Types for wpCSL
    //-------------------------------

    /**
     * @var \wpCSL_helper__slplus $helper
     */
    public $helper;

    /**
     * @var \wpCSL_notifications__slplus $notifications
     */
    public $notifications;

    /**
     * The settings object.
     * 
     * @var \wpCSL_settings__slplus $settings
     */
    public $settings;

    /**
     * @var \PluginTheme $themes
     */
    public $themes;

    //---------------------------------------------
    // Methods
    //---------------------------------------------

    /**
     * Run this whenever the class is instantiated.
     *
     * @param mixed[] $params a named array where key is the string of a wpCSL_plugin__slplus property, key is the initial value.
     */
    function __construct($params) {

        // These settings can be overridden
        //
        $this->broadcast_url = 'http://www.charlestonsw.com/signage/index.php';
        $this->css_prefix = '';
        $this->current_admin_page = '';
        $this->prefix = '';
        $this->shortcode_was_rendered = false;
        $this->themes_enabled = false;
        $this->use_obj_defaults = true;

        // Set current admin page
        //
        if (isset($_GET['page'])) {
            $plugin_page = stripslashes($_GET['page']);
            $plugin_page = plugin_basename($plugin_page);
            $this->current_admin_page = $plugin_page;
        }

        // Do the setting override or initial settings.
        //
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }

        // Check to see if we are doing an update
        //
        if (isset($this->version)) {
            if ($this->version != get_option($this->prefix . "-installed_base_version" , '')) {
                if (isset($this->on_update)) {
                    call_user_func_array($this->on_update, array($this, get_option($this->prefix . "-installed_base_version")));
                }
                update_option($this->prefix . '-installed_base_version', $this->version);

                $destruct_time = get_option($this->prefix . "-notice-countdown");

                // We're doing an update, so check to see if they didn't check the check box,
                // and if they didn't... well, show it to them again
                if ($destruct_time) {
                    delete_option($this->prefix . "-notice-countdown");
                }
            }
        }

        // What prefix do we add to the CSS elements?
        if ($this->css_prefix == '') {
            $this->css_prefix = $this->prefix;
        }

        // Make sure we have WP_Http for http posts
        // then instatiate it here in the http_handler property
        // of this class.
        //
        if (!class_exists('WP_Http')) {
            include_once( ABSPATH . WPINC . '/class-http.php' );
        }
        if (class_exists('WP_Http')) {
            $this->http_handler = new WP_Http;
        }

        // Plugin Author URL
        //
        $this->url = (isset($this->url) ? $this->url : 'http://www.charlestonsw.com/');
        $this->support_url = (isset($this->support_url) ? $this->support_url : $this->url );

        // Initialize
        $this->create_objects();
        $this->add_refs();
        $this->add_wp_actions();
    }

    /**
     * Sets $this->isOurAdminPage true if we are on a SLP managed admin page.  Returns true/false accordingly.
     */
    function check_IsOurAdminPage() {
        $this->admin_slugs = apply_filters('wpcsl_admin_slugs', $this->admin_slugs);

        if (!is_admin()) {
            $this->isOurAdminPage = false;
            return false;
        }
        if ($this->isOurAdminPage) {
            return true;
        }

        // Our Admin Page : true if we are on the admin page for this plugin
        // or we are processing the update action sent from this page
        //
        $this->isOurAdminPage = (
                ($this->current_admin_page == $this->prefix . '-options') ||
                ($this->current_admin_page == $this->admin_main_slug )
                );
        if ($this->isOurAdminPage) {
            return true;
        }


        // Request Action is "update" on option page
        //
        $this->isOurAdminPage = isset($_REQUEST['action']) &&
                ($_REQUEST['action'] === 'update') &&
                isset($_REQUEST['option_page']) &&
                (substr($_REQUEST['option_page'], 0, strlen($this->prefix)) === $this->prefix)
        ;
        if ($this->isOurAdminPage) {
            return true;
        }

        // This test allows for direct calling of the options page from an
        // admin page call direct from the sidebar using a class/method
        // operation.
        //
        // To use: pass an array of strings that are valid admin page slugs for
        // this plugin.  You can also pass a single string, we catch that too.
        //
        
        if (isset($this->admin_slugs)) {
            if (!is_array($this->admin_slugs)) {
                $this->admin_slugs = array($this->admin_slugs);
            }
            foreach ($this->admin_slugs as $admin_slug) {
                $this->isOurAdminPage = ($this->current_admin_page === $admin_slug);
                if ($this->isOurAdminPage) {
                    break;
                }
            }
        }
        return $this->isOurAdminPage;
    }

    /**
     * Create a Map Settings Debug My Plugin panel.
     *
     * @return null
     */
    function create_DMPPanels() {
        if (!isset($GLOBALS['DebugMyPlugin'])) {
            return;
        }
        if (class_exists('DMPPanelSLPMain') == false) {
            require_once($this->plugin_path . '/lib/class.dmppanels.php');
        }
        $GLOBALS['DebugMyPlugin']->panels['wpcsl.main'] = new DMPPanelWPCSLMain();
        $GLOBALS['DebugMyPlugin']->panels['wpcsl.settings'] = new DMPPanelWPCSLSettings();
    }

    /**
     * Create and attach helper object if needed.
     *
     * @param string $class
     */
    function create_helper($class = 'none') {
        if ($class === 'none') {
            return;
        }
        require_once('class.helper.php');
        $this->helper = new wpCSL_helper__slplus(
                array(
                    'slplus' => $this
                )
        );
    }

    /**
     * Setup the WPCSL Notifications Object.
     * 
     * @param string $class - 'none' to disable notifications
     */
    function create_notifications($class = 'none') {
        if ($class === 'none') {
            return;
        }
        require_once('class.notifications.php');
        $this->notifications = new wpCSL_notifications__slplus(
                array(
            'prefix' => $this->prefix,
            'name' => $this->name,
            'url' => 'options-general.php?page=' . $this->prefix . '-options',
                )
        );
    }

    /**
     * Attach the settings object to this plugin.
     * 
     * @param string $class
     */
    function create_settings($class = 'none') {
        if ($class === 'none') {
            return;
        }
        require_once('class.settings.php');
        $this->settings = new wpCSL_settings__slplus(
                array(
            'http_handler' => $this->http_handler,
            'broadcast_url' => $this->broadcast_url,
            'prefix' => $this->prefix,
            'css_prefix' => $this->css_prefix,
            'plugin_url' => $this->plugin_url,
            'name' => $this->name,
            'url' => (isset($this->url) ? $this->url : null),
            'parent' => $this
                )
        );
    }

    /**
     * Create the theme object and attach it.
     *
     * @param string $class 'none' to disable themes.
     * @return null
     */
    function create_themes($class = 'none') {
        if ($class === 'none') {
            return;
        }
        require_once('class.themes.php');
        $this->themes = new PluginTheme(
                array(
            'notifications' => $this->notifications,
            'parent' => $this,
            'plugin_path' => $this->plugin_path,
            'plugin_url' => $this->plugin_url,
            'prefix' => $this->prefix,
            'support_url' => $this->support_url,
                )
        );
    }

    /**
     * Create the options page.
     */
    function create_options_page() {
        add_options_page(
                $this->name . ' Options', $this->name, 'administrator', $this->prefix . '-options', array(
            $this->settings,
            'render_settings_page'
                )
        );
    }

    /**
     * Create some objects.
     */
    function create_objects() {

        // use_obj_defaults is set, use the invoke the default 
        // set of wpCSL objects
        //
        if (isset($this->use_obj_defaults) && $this->use_obj_defaults) {
            $this->create_helper('default');
            $this->create_notifications('default');
            $this->create_settings('default');
            $this->create_themes('default');

            // Custom objects are in place
        //
        } else {
            if (isset($this->helper_obj_name))
                $this->create_helper($this->helper_obj_name);
            if (isset($this->notifications_obj_name))
                $this->create_notifications($this->notifications_obj_name);
            if (isset($this->settings_obj_name))
                $this->create_settings($this->settings_obj_name);
            if (isset($this->themes_obj_name))
                $this->create_themes($this->themes_obj_name);
        }
    }

    /*     * *********************************************
     * * method: add_refs
     * * What did you say? Refactoring what now? I don't know what that is
     * *
     * * This connects the instantiated objects of other classes that are
     * * properties of the main CSL-plugin class to each other.  For example
     * * it ensures each of the other classes can access the notification
     * * object for the main plugin.
     * *
     * * settings    <= notifications, themes
     * * themes      <= settings, notifications
     * * helper      <= notifications
     * *
     * */

    function add_refs() {
        // Notifications doesn't require any other objects yet
        // Settings
        if (isset($this->settings)) {
            if (isset($this->notifications) && !isset($this->settings->notifications))
                $this->settings->notifications = &$this->notifications;
            if (isset($this->themes) && !isset($this->settings->themes))
                $this->settings->themes = &$this->themes;
        }

        // Helper
        if (isset($this->helper)) {
            if (isset($this->helper) && !isset($this->helper->notifications))
                $this->helper->notifications = &$this->notifications;
        }

        // Themes
        if (isset($this->themes)) {
            if (isset($this->themes) && !isset($this->themes->notifications))
                $this->themes->notifications = &$this->notifications;
            if (isset($this->settings) && !isset($this->themes->settings))
                $this->themes->settings = &$this->settings;
        }
    }

    /**
     * Setup WordPress action scripts.
     *
     * Note: admin_menu is not called on every admin page load
     * Reference: http://codex.wordpress.org/Plugin_API/Action_Reference
     */
    function add_wp_actions() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'create_options_page'));
            add_action('admin_init', array($this, 'admin_init'), 50);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_stylesheet'));
            add_action('admin_notices', array($this->notifications, 'display'));
            add_action('dmp_addpanel', array($this, 'create_DMPPanels'));
        } else {
            if (!$this->themes_enabled && !$this->no_default_css) {
                // non-admin enqueues, actions, and filters
                add_filter('wp_print_scripts', array($this, 'user_header_js'));
                add_filter('wp_print_styles', array($this, 'user_header_css'));
            }
        }

        add_filter('plugin_row_meta', array($this, 'add_meta_links'), 10, 2);
    }

    /**
     * Add meta links.
     * 
     * @param type $links
     * @param type $file
     * @return string
     */
    function add_meta_links($links, $file) {

        if ($file == $this->basefile) {
            if (isset($this->support_url)) {
                $links[] = '<a href="' . $this->support_url . '" title="' . __('Support', 'csa-slplus') . '">' .
                        __('Support', 'csa-slplus') . '</a>';
            }
            if (isset($this->purchase_url)) {
                $links[] = '<a href="' . $this->purchase_url . '" title="' . __('Purchase', 'csa-slplus') . '">' .
                        __('Buy Now', 'csa-slplus') . '</a>';
            }
            $links[] = '<a href="options-general.php?page=' . $this->prefix . '-options" title="' .
                    __('Settings', 'csa-slplus') . '">' . __('Settings', 'csa-slplus') . '</a>';
        }
        return $links;
    }

    /**
     * WordPress admin_init hook (runs after admin_menu has run)
     */
    function admin_init() {
        $this->settings->register();
    }

    /**
     * Return a deprecated notification.
     *
     * @param string $function_name name of function that is deprecated.
     */
    public function createstring_Deprecated($function_name) {
        return
                sprintf(
                        __('The %s method is no longer available. ', 'csa-slplus'), $function_name
                ) .
                '<br/>' .
                __('It is likely that one of your add-on pack is out of date. ', 'csa-slplus') .
                '<br/>' .
                sprintf(
                        __('You need to <a href="%s" target="csa">upgrade</a> to the latest %s compatible version ' .
                                'or <a href="%s" target="csa">downgrade</a> the %s plugin.', 'csa-slplus'), $this->purchase_url, $this->name, $this->wp_downloads_url, $this->name
                )
        ;
    }

    /**
     * Add DebugMyPlugin messages.
     *
     * @param string $panel - panel name
     * @param string $type - what type of debugging (msg = simple string, pr = print_r of variable)
     * @param string $header - the header
     * @param string $message - what you want to say
     * @param string $file - file of the call (__FILE__)
     * @param int $line - line number of the call (__LINE__)
     * @param boolean $notime - skipping showing the time? default = true
     * @return null
     */
    function debugMP($panel = 'main', $type = 'msg', $header = 'wpCSL DMP', $message = '', $file = null, $line = null, $notime = true, $clearingStack = false) {

        // Escape HTML Messages
        //
        if (($type === 'msg') && ($message !== '')) {
            $message = esc_html($message);
        }

        // Panel not setup yet?  Push onto stack.
        //
        if (
                !isset($GLOBALS['DebugMyPlugin']) ||
                !isset($GLOBALS['DebugMyPlugin']->panels[$panel])
        ) {
            if (!isset($this->dmpStack[$panel])) {
                $this->dmpStack[$panel] = array();
            }
            array_push($this->dmpStack[$panel], array($type, $header, $message, $file, $line, $notime));
            return;
        }

        // Have waiting messages?  Pop off stack.
        //
        if (!$clearingStack && isset($this->dmpStack[$panel]) && is_array($this->dmpStack[$panel])) {
            while ($dmpMessage = array_shift($this->dmpStack[$panel])) {
                $this->debugMP($panel, $dmpMessage[0], $dmpMessage[1], $dmpMessage[2], $dmpMessage[3], $dmpMessage[4], $dmpMessage[5], true);
            }
        }

        // Do normal real-time message output.
        //
        switch (strtolower($type)):
            case 'pr':
                $GLOBALS['DebugMyPlugin']->panels[$panel]->addPR($header, $message, $file, $line, $notime);
                break;
            default:
                $GLOBALS['DebugMyPlugin']->panels[$panel]->addMessage($header, $message, $file, $line, $notime);
        endswitch;
    }

    /*     * -------------------------------------
     * * method: user_header_js
     * */

    function user_header_js() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
    }

    /*     * -------------------------------------
     * * method: user_header_css
     * */

    function user_header_css() {

        $cssPath = '';
        if (isset($this->css_url)) {
            $cssPath = $this->css_url;
        } else if (isset($this->plugin_url)) {
            if (file_exists($this->plugin_path . '/css/' . $this->prefix . '.css')) {
                $cssPath = $this->plugin_url . '/css/' . $this->prefix . '.css';
            }
        }

        if ($cssPath != '') {
            wp_enqueue_style(
                    $this->prefix . 'css', $cssPath
            );
        }
        wp_enqueue_style('thickbox');
    }

    /**
     * Compare current plugin version with minimum required.
     *
     * Set a notification message.
     * Disable the requesting add-on pack if requirement is not met.
     *
     * $params['addon_name'] - the plain text name for the add-on pack.
     * $params['addon_slug'] - the slug for the add-on pack.
     * $params['min_required_version'] - the minimum required version of the base plugin.
     *
     * @param mixed[] $params
     */
    function VersionCheck($params) {

        // Minimum version requirement not met.
        //
        if (version_compare($this->version, $params['min_required_version'], '<')) {
            if (is_admin()) {
                if (isset($this->notifications)) {
                    $this->notifications->add_notice(4, '<strong>' .
                            sprintf(__('%s has been deactivated.', 'csa-slplus'
                                    ), $params['addon_name']
                            ) . '<br/> ' .
                            '</strong>' .
                            sprintf(__('You have %s version %s.', 'csa-slplus'
                                    ), $this->name, $this->version
                            ) . '<br/> ' .
                            sprintf(__('You need version %s or greater for this version of %s.', 'csa-slplus'
                                    ), $params['min_required_version'], $params['addon_name']
                            ) . '<br/> ' .
                            sprintf(__('Please install an older version of %s or upgrade.', 'csa-slplus'
                                    ), $this->name
                            ) . '<br/> ' .
                            sprintf(__('Upgrading major versions of %s requires paid upgrades to all related add-on packs.', 'csa-slplus'
                                    ), $this->name
                            ) .
                            '<br/><br/>'
                    );
                }
                deactivate_plugins(array($params['addon_slug']));
            }
            return;
        }

        // Register add on if version is ok
        //
        $this->register_addon($params['addon_slug']);
    }

    /**
     * Enqueue the admin stylesheet when needed.
     *
     * @var string $hook
     */
    function enqueue_admin_stylesheet($hook) {
        $this->debugMP('main', 'msg', 'wpCSL.enqueue_admin_stylesheet(' . $hook . ')', '', NULL, NULL, true);
        $this->check_IsOurAdminPage();

        // The CSS file must exists where we expect it and
        // The admin page being rendered must be in "our family" of admin pages
        //
        if (file_exists($this->plugin_path . '/lib/admin.css') &&
                array_search($hook, $this->admin_slugs)
        ) {
            wp_register_style($this->styleHandle, $this->plugin_url . '/lib/admin.css');
            wp_enqueue_style($this->styleHandle);

            // jQuery Smoothness Theme
            //
            if (file_exists($this->plugin_path . '/lib/jquery-ui-smoothness.css')) {
                wp_enqueue_style(
                        'jquery-ui-smoothness', $this->plugin_url . '/lib/jquery-ui-smoothness.css'
                );
            }

            if (file_exists($this->plugin_path . '/lib/admin-interface.js')) {
                wp_enqueue_script(
                        $this->styleHandle, $this->plugin_url . '/lib/admin-interface.js', 'jquery', $this->version, true
                );
            }
        }
        
        wp_enqueue_script('jquery-ui-dialog');
    }

    /**
     *  Determine if the http_request result that came back is valid.
     *
     * @param type $result (required, object) - the http result
     * @return boolean true if we got a result, false if we got an error
     */
    function http_result_is_ok($result) {

        // Yes - we can make a very long single logic check
        // on the return, but it gets messy as we extend the
        // test cases. This is marginally less efficient but
        // easy to read and extend.
        //
        if (is_a($result, 'WP_Error')) {
            return false;
        }
        if (!isset($result['body'])) {
            return false;
        }
        if ($result['body'] == '') {
            return false;
        }

        return true;
    }

}
