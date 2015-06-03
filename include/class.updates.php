<?php
/**
 * Store Locator Plus update manager.
 *
 * Checks remote CSA server for add-on pack updates.
 *
 * @package StoreLocatorPlus\Updates
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
 */
class SLPlus_Updates {

    /**
     * The plugin current version
     * @var string
     */
    public $current_version;

    /**
     * The current production version reported by the CSA server.
     *
     * @var string
     */
    public $remote_version;

    /**
     * Base update path.
     *
     * @var $string
     */
    public $base_path;

    /**
     * The plugin remote update path
     * @var string
     */
    public $update_request_path;

    /**
     * The global plugin.
     * 
     * @var \SLPlus
     */
    private $plugin;

    /**
     * Plugin Slug (plugin_directory/plugin_file.php)
     * @var string
     */
    public $plugin_slug;
    /**
     * Plugin name (plugin_file)
     * @var string
     */
    public $slug;

    /**
     * Initialize a new instance of the WordPress Auto-Update class
     * @param string $current_version
     * @param string $update_path
     * @param string $plugin_slug
     */
    function __construct($current_version, $update_path, $plugin_slug)
    {
        global $slplus_plugin;

        // Set the class public variables
        $this->plugin = $slplus_plugin;
        $this->current_version = $current_version;
        $this->plugin_slug = $plugin_slug;
        list ($t1, $t2) = explode('/', $plugin_slug);
        $this->slug = str_replace('.php', '', $t2);
        $this->base_path = $update_path;
        $this->set_update_request_path();

        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        
        // Define the alternative response for information checking
        add_filter('plugins_api', array($this, 'check_info'), 10, 3);
    }
    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get the remote version
        $this->getRemote_version();

        // If a newer version is available, add the update
        if (isset($GLOBALS['DebugMyPlugin'])) {
            error_log('slug ' . $this->slug . ' current version ' . $this->current_version . ' remote version ' . $this->remote_version);
        }
        if (version_compare($this->current_version, $this->remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $this->remote_version;
            $obj->url = $this->update_request_path;
            $obj->package = $this->update_request_path;
            $transient->response[$this->plugin_slug] = $obj;
        }
        return $transient;
    }
    
    /**
     * Add our self-hosted description to the filter
     *
     * @param mixed $orig original incoming args
     * @param array $action
     * @param object $arg
     * @return bool|object
     */
    public function check_info($orig, $action, $arg) {

        // No slug? Not plugin update.
        //
        if (empty($arg->slug)) { return $orig; }
        if (!array_key_exists($arg->slug,$this->plugin->addons)) { return $orig; }
        if (isset($GLOBALS['DebugMyPlugin'])) {
            error_log('check info for action ' . $action . ' arg slug ' . $arg->slug);
        }

        if (!isset($this->plugin->infoFetched[$arg->slug])) {
            $information = $this->getRemote_information($arg->slug);
            $this->plugin->infoFetched[$arg->slug] = true;
            if (isset($GLOBALS['DebugMyPlugin'])) {
                error_log(' plugin info '. print_r($information,true));
            }
            return $information;
        }
        return $orig;
    }
    /**
     * Return the remote version
     * @return string $remote_version
     */
    public function getRemote_version() {
        $request = wp_remote_post(
            $this->update_request_path . '&fetch=version' ,
            array (
                'timeout'   => '60',
            )
        );
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            $this->remote_version = $request['body'];
        } else {
            $this->remote_version = false;
        }
        return $this->remote_version;
    }
    /**
     * Get information about the remote version
     * @return mixed[] false if cannot get info, unserialized info if we could
     */
    public function getRemote_information( $slug = null ) {
        $this->set_update_request_path( $slug );

        if (isset($GLOBALS['DebugMyPlugin'])) {
            error_log(get_class() . '::' . __FUNCTION__ . " slug = {$slug} ");
        }
        
        $request = wp_remote_post(
            $this->update_request_path . '&fetch=info' ,
            array (
                'timeout'   => '60',
            )
        );
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }
        return false;
    }

    /**
     * Set the update path.
     */
    public function set_update_request_path( $slug = null )  {

        // Implied slug, use the update class properties.
        //
        if ( $slug === null ) {
            $slug = $this->slug;
            $version = $this->current_version;

        // Explicit slug, go look up the info.
        //
        } else {
            $version = $this->plugin->addons[$slug]->options['installed_version'];
            if ( empty( $version ) ) {
                $version = $this->set_plugin_version( $slug );;
            }
        }

        $this->update_request_path = $this->base_path .
            '?action=wpdk_updater' .
            '&uid=' . $this->plugin->options_nojs['premium_user_id'] .
            '&sid=' . $this->plugin->options_nojs['premium_subscription_id'] .
            '&slug='.$slug .
            '&current_version=' . $version;
    }

    /**
     * Set the plugin version if not a registered plugin with options['installed_version'] set.
     *
     * @param $slug
     * @return string
     */
    function set_plugin_version( $slug ) {
        $version = '00.00.001';

        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        $plugins = get_plugin_updates();
        foreach ( (array) $plugins as $plugin_file => $plugin_data) {
           if ( $plugin_data->update->slug === $slug ) {
               $version = $plugin_data->Version;
               break;
           }
        }

        return $version;
    }
}
