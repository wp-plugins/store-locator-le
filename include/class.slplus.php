<?php
if (! class_exists('SLPlus')) {

    /**
     * The base plugin class for Store Locator Plus.
     *
     * @package StoreLocatorPlus
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2012-2015 Charleston Software Associates, LLC
     *
     */
    class SLPlus {
        //-------------------------------------
        // Constants
        //-------------------------------------

        /**
         * Define the location post type.
         */
        const locationPostType = 'store_page';

        /**
         * Define the location post taxonomy.
         */
        const locationTaxonomy = 'stores';

        /**
         * PRO: Pro Pack web link.
         *
         * Remove this when UML removes constant reference.
         */
        const linkToPRO = '<a href="http://www.storelocatorplus.com/product/slp4-pro/" target="csa">Pro Pack</a>';

        //-------------------------------------
        // Properties
        //-------------------------------------

	    /**
	     * @var SLPlus_Activation
	     */
	    public $Activation;

        /**
         * An array of the add-on or modules slugs that are active.
         *
         * The keys will always list the add on slugs.
         * Keys starting with slp. are built-in SLP modules.
         *
         * Modules:
         * o 'slp.AjaxHandler' AjaxHandler
         * o 'slp.UI' User Interface
         *
         * Add Ons:
         * o 'slp-enhanced-map' Enhanced Map
         * o 'slp-enhanced-results' Enhanced Results
         * o 'slp-enhanced-search' Enhanced Search
         * o 'slp-extendo' Super Extendo
         * o 'slp-janitor' Janitor
         * o 'slp-pages' Store Pages
         * o 'slp-pro' Pro Pack
         * o 'slp-tagalong' Tagalong
         * o 'slp-user-managed-locations' User Managed Locations
         * o 'slp-widget' Widget
         *
         * The values will be null if there is no pointer to the object,
         * or the object pointer to an instantiated version of the add-on.
         *
         * @var objects[] $addons active add-ons
         */
        public $addons = array();

        /**
         * The add on manager handles add on connections and data.
         *
         * @var \SLPlus_AddOn_Manager
         */
        public $add_ons;

	    /**
	     * The registered admin page hooks for the plugin.
	     *
	     * @var string[] $admin_slugs
	     */
	    public $admin_slugs = array();

        /**
         * The Admin UI object.
         *
         * @var SLPlus_AdminUI $AdminUI
         */
        public $AdminUI;

        /**
         * The Admin WPML object.
         *
         * @var SLPlus_AdminWPML $AdminWPML
         */
        public $AdminWPML;

        /**
         * The Ajax Handler object.
         *
         * @var SLPlus_AjaxHandler
         */
        public $AjaxHandler;

	    /**
	     * @var string
	     */
	    private $current_admin_page = '';

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
         * Default settings for the plugin.
         *
         * These elements are LOADED EVERY TIME the plugin starts.
         *
         * o bubblelayout - default html and shortcodes for map bubbles
         * o layout - default html structure for slplus shortcode page view
         * o maplayout - default html and shortcodes for map bubbles
         * o resultslayout -  default html and shortcodes for search results
         * o searchlayout -  default html and shortcodes for the search form
         * o theme - the default theme if not previously set (new installs)
         *
         * @var mixed[] $defaults
         */
        public $defaults = array(

            // Theme
            //
            'theme' => 'twentyfifteen_rev02',

            // Overall Layout
            //
            // If you change this change default.css theme as well.
            //
            'layout' =>
                '<div id="sl_div">[slp_search][slp_map][slp_results]</div>',


            // Bubble Layout shortcodes have the following format:
            // [<shortcode> <attribute> <optional modifier> <optional modifier argument>]
            //
            // shortcode:
            //    slp_location = marker data elements like the location name or address elements
            //
            //        attribute = a location attribute
            //            same marker fields as enhanced results
            //            'url' is a special marker built from store pages and web options to link to a web page
            //
            //        modifier  = one of the following:
            //            suffix = append the noted HTML or text after the location attribute is output according to the modifier argument:
            //                br     = append '<br/>'
            //                space  = append ' '
            //                comma  = append ','
            //
            //            wrap   = wrap the location attribute in a special tag or text according to the modifier arguement:
            //                img   = make the location attribute the source for an <img /> tag.
            //
            //    slp_option   = slplus options from the option property below
            //
            //        attribute = an option key name
            //
            //        modifier  = one of the following :
            //            ifset = output only if the noted location attribute is empty, the location attribute is specified in the modifier argument:
            //                for example [slp_option label_phone ifset phone] outputs the label_phone option only if the location phone is not empty.
            'bubblelayout' =>
                '<div id="sl_info_bubble" class="[slp_location featured]">
    <span id="slp_bubble_name"><strong>[slp_location name  suffix  br]</strong></span>
    <span id="slp_bubble_address">[slp_location address       suffix  br]</span>
    <span id="slp_bubble_address2">[slp_location address2      suffix  br]</span>
    <span id="slp_bubble_city">[slp_location city          suffix  comma]</span>
    <span id="slp_bubble_state">[slp_location state suffix    space]</span>
    <span id="slp_bubble_zip">[slp_location zip suffix  br]</span>
    <span id="slp_bubble_country"><span id="slp_bubble_country">[slp_location country       suffix  br]</span></span>
    <span id="slp_bubble_directions">[html br ifset directions]
    [slp_option label_directions wrap directions]</span>
    <span id="slp_bubble_website">[html br ifset url]
    [slp_location url           wrap    website][slp_option label_website ifset url][html closing_anchor ifset url][html br ifset url]</span>
    <span id="slp_bubble_email">[slp_location email         wrap    mailto ][slp_option label_email ifset email][html closing_anchor ifset email][html br ifset email]</span>
    <span id="slp_bubble_phone">[html br ifset phone]
    <span class="location_detail_label">[slp_option   label_phone   ifset   phone]</span>[slp_location phone         suffix    br]</span>
    <span id="slp_bubble_fax"><span class="location_detail_label">[slp_option   label_fax     ifset   fax  ]</span>[slp_location fax           suffix    br]<span>
    <span id="slp_bubble_description"><span id="slp_bubble_description">[html br ifset description]
    [slp_location description raw]</span>[html br ifset description]</span>
    <span id="slp_bubble_hours">[html br ifset hours]
    <span class="location_detail_label">[slp_option   label_hours   ifset   hours]</span>
    <span class="location_detail_hours">[slp_location hours         suffix    br]</span></span>
    <span id="slp_bubble_img">[html br ifset img]
    [slp_location image         wrap    img]</span>
    <span id="slp_tags">[slp_location tags]</span>
    </div>'
        ,


            // Map Layout
            // If you change this change default.css theme as well.
            //
            'maplayout' =>
                '[slp_mapcontent][slp_maptagline]',


            // Results Layout
            // If you change this change default.css theme as well.
            //
            'resultslayout' =>
                '<div id="slp_results_[slp_location id]" class="results_entry  [slp_location featured]">
        <div class="results_row_left_column"   id="slp_left_cell_[slp_location id]"   >
            <span class="location_name">[slp_location name]</span>
            <span class="location_distance">[slp_location distance_1] [slp_location distance_unit]</span>
        </div>
        <div class="results_row_center_column" id="slp_center_cell_[slp_location id]" >
            <span class="slp_result_address slp_result_street">[slp_location address]</span>
            <span class="slp_result_address slp_result_street2">[slp_location address2]</span>
            <span class="slp_result_address slp_result_citystatezip">[slp_location city_state_zip]</span>
            <span class="slp_result_address slp_result_country">[slp_location country]</span>
            <span class="slp_result_address slp_result_phone">[slp_location phone]</span>
            <span class="slp_result_address slp_result_fax">[slp_location fax]</span>
        </div>
        <div class="results_row_right_column"  id="slp_right_cell_[slp_location id]"  >
            <span class="slp_result_contact slp_result_website">[slp_location web_link]</span>
            <span class="slp_result_contact slp_result_email">[slp_location email_link]</span>
            <span class="slp_result_contact slp_result_directions"><a href="http://[slp_option map_domain]/maps?saddr=[slp_location search_address]&daddr=[slp_location location_address]" target="_blank" class="storelocatorlink">[slp_location directions_text]</a></span>
            <span class="slp_result_contact slp_result_hours">[slp_location hours]</span>
            [slp_location pro_tags]
            [slp_location iconarray wrap="fullspan"]
            [slp_location socialiconarray wrap="fullspan"]
            </div>
    </div>'
        ,

            // Search Layout
            // If you change this change default.css theme as well.
            //
            // Use the slp_search_element shortcode processor to hook in add-on packs.
            // Look for the attributes add_on and location="..." to place items.
            //
            // TODO: update PRO, ES, GFI&GFL to use the add_on location="..." processing.
            // TODO: deprecate the add-on specific shortcodes at some point
            //
            'searchlayout' =>
                '<div id="address_search">
        [slp_search_element add_on location="very_top"]
        [slp_search_element input_with_label="name"]
        [slp_search_element input_with_label="address"]
        [slp_search_element dropdown_with_label="city"]
        [slp_search_element dropdown_with_label="state"]
        [slp_search_element dropdown_with_label="country"]
        [slp_search_element selector_with_label="tag"]
        [slp_search_element dropdown_with_label="category"]
        [slp_search_element dropdown_with_label="gfl_form_id"]
        [slp_search_element add_on location="before_radius_submit"]
        <div class="search_item">
            [slp_search_element dropdown_with_label="radius"]
            [slp_search_element button="submit"]
        </div>
        [slp_search_element add_on location="after_radius_submit"]
        [slp_search_element add_on location="very_bottom"]
    </div>'
        ,
        );

        /**
         * The extension object.
         *
         * @var SLP_Extension
         */
        public $extension;

	    /**
	     * @var string
	     */
	    public $fqfile;

        /**
         * Array of slugs + booleans for plugins we've already fetched info for.
         *
         * @var array[] named array, key = slug, value = true
         */
        public $infoFetched = array();

        /**
         * The options that the user has set for Store Locator Plus.
         *
         * Key is the name of a supported option, value is the default value.
         *
         * NOTE: Booleans must be set as a string '0' or '1'.
         * That is how serialize/deserialize stores and pulls them from the DB.
         *
         * Anything stored in here also gets passed to the slp.js via the slplus.options object.
         * Reference the settings in the slp.js via slplus.options.<key>
         *
         * These elements are LOADED EVERY TIME the plugin starts.
         *
         * TODO : Create a new MASTER options list called something like master_options.
         * Master options is an array of options names with various properties.
         * The init_options() call sets up those properties for things like:
         *     needs_translation
         *     javascript / nojavascript
         *
         * public $master_options = array(
         *       'label_email' => array(   'javascript' => false , 'translate' => true , default_value=> 'Email' ) ,
         * );
         *
         * @var mixed[] $options
         */
        public $options = array(
            'bubblelayout' => '',
            'distance_unit' => 'miles',
            'immediately_show_locations' => '1',
            'initial_radius' => '10000',
            'initial_results_returned' => '25',
            'label_directions' => '',
            'label_email' => 'Email',
            'label_fax' => '',
            'label_hours' => '',
            'label_phone' => '',
            'label_website' => '',
            'map_center' => '',
            'map_domain' => 'maps.google.com',
	        'no_homeicon_at_start' => '1',        // EM has admin UI for this setting.
            'slplus_version' => SLPLUS_VERSION,
            'zoom_level' => '12',

            // AJAX Incoming Variables
            //
            'ignore_radius' => '0',  // Passed in as form var from Enhanced Search
        );

        /**
         * The default options (before being read from DB)
         *
         * @var array
         */
        public $options_default = array();

        /**
         * These are the options needing translation.
         *
         * @var array
         */
        public $options_needing_translation = array(
            'invalid_query_message',
            'label_directions',
            'label_email',
            'label_fax',
            'label_hours',
            'label_phone',
            'label_website',
        );

        /**
         * Serialized plugin options that do NOT get passed to slp.js.
         *
         * @var mixed[]
         */
        public $options_nojs = array(
            'extended_data_tested' => '0',
            'force_load_js' => '0',
            'google_client_id' => '',
            'google_private_key' => '',
            'has_extended_data' => '',
            'http_timeout' => '10', // HTTP timeout for GeoCode Requests (s)
            'invalid_query_message' => '',
            'max_results_returned' => '25',
            'next_field_id' => 1,
            'next_field_ported' => '',
            'php_max_execution_time' => '600',
            'premium_user_id' => '',
            'premium_subscription_id' => '',
            'retry_maximum_delay' => '5.0',
        );

        /**
         * The default options_nojs (before being read from DB)
         *
         * @var array
         */
        public $options_nojs_default = array();

        /**
         * The settings that impact how the plugin renders.
         *
         * These elements are ONLY WHEN wpCSL.helper.loadPluginData() is called.
         * loadPluginData via 'getoption' always reads a single entry in the wp_options table
         * loadPluginData via 'getitem' checks the settings RAM cache first, then loads single entries from wp_options
         * BOTH are horrible ideas.  Serialized data is far better.
         *
         * @var mixed $data
         */
        public $data;

        /**
         * The data interface helper.
         *
         * @var \SLPlus_Data $database
         */
        public $database;

	    /**
	     * True if debug-my-plugin is installed and active.
	     *
	     * @var bool
	     */
	    private $debugMP_is_active = false;

        /**
         * Full path to this plugin directory.
         *
         * @var string $dir
         */
        private $dir;

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
         * Sets the values of the $data array.
         *
         * Drives the wpCSL loadPluginData method.
         *
         * This has a method to tell it HOW to load the data.
         *   via a simple get_option() call or via the wpCSL.settings.getitem() call.
         *
         * wpCSL getitem() looks for variations in the option names based on an option "root" name.
         *
         * @var mixed $dataElements
         */
        public $dataElements;

	    /**
	     * True if we are on an admin page for the plugin.
	     *
	     * @var boolean $isOurAdminPage
	     */
	    public $isOurAdminPage = false;

        /**
         * Quick reference for the Force Load JavaScript setting.
         *
         * @var boolean
         */
        public $javascript_is_forced = true;

	    /**
	     * The plugin meta data.
	     *
	     * @var mixed[] $metadata
	     */
	    public $metadata;

        /**
         * Set to true if the plugin data was already loaded.
         *
         * @var boolean $pluginDataLoaded
         */
        public $pluginDataLoaded = false;

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
	     * The purchase URL for products.
	     *
	     * @var string
	     */
	    private $purchase_url = 'http://www.storelocatorplus.com/product-category/slp4-products/';

	    /**
	     * @var bool
	     */
	    public $shortcode_was_rendered = false;

        /**
         * What slug do we go by?
         *
         * @var string $slug
         */
        public $slug;

	    /**
	     * The style handle for CSS invocation.
	     *
	     * @var string
	     */
	    public $styleHandle = 'wpcsl';

	    /**
	     * @var string
	     */
	    public $support_url = 'http://www.storelocatorplus.com/support/documentation/store-locator-plus/';

	    /**
	     * The version that was installed at the start of the plugin (prior installed version).
	     *
	     * @var string
	     */
	    public $installed_version = null;

	    /**
	     * @var wpCSL_helper__slplus
	     */
	    public $helper;

	    /**
	     * @var string
	     */
	    public $name;

	    /**
	     * @var wpCSL_notifications__slplus
	     */
	    public $notifications;

	    /**
	     * @var string
	     */
	    public $prefix = '';

	    /**
	     * @var wpCSL_settings__slplus
	     */
	    public $settings;

	    /**
	     * @var PluginTheme
	     */
	    public $themes;

	    /**
	     * @var string
	     */
	    public $updater_url = 'http://www.storelocatorplus.com/wp-admin/admin-ajax.php';

	    /**
	     * Full URL to this plugin directory.
	     *
	     * @var string $url
	     */
	    public $url = 'http://www.storelocatorplus.com/';


	    /**
	     * @var SLPlus_UI
	     */
	    public $UI;

	    /**
	     * The current plugin version intended to be run now.
	     *
	     * @var string
	     */
	    public $version;

	    /**
	     * @var SLPlus_WPML $WPML
	     */
	    public $WPML;

        //-------------------------------------
        // Methods
        //-------------------------------------

        /**
         * Initialize a new SLPlus Object
         */
        public function __construct()  {

	        // Properties set via define or hard calculation.
	        //
	        $this->basefile             = SLPLUS_BASENAME;
	        $this->fqfile               = SLPLUS_FILE;
	        $this->dir                  = plugin_dir_path(SLPLUS_FILE);
	        $this->name                 = SLPLUS_NAME;
	        $this->plugin_path          = SLPLUS_PLUGINDIR;
	        $this->plugin_url           = SLPLUS_PLUGINURL;
	        $this->prefix               = SLPLUS_PREFIX;
	        $this->slug                 = plugin_basename(SLPLUS_FILE);
	        $this->url                  = plugins_url('', SLPLUS_FILE);

	        // Properties Set By Methods
	        //
	        $this->current_admin_page   = $this->get_admin_page();
	        $this->debugMP_is_active    = $this->is_debugMP_active();

	        // Attach objects
	        //
	        $this->attach_notifications();
	        $this->attach_helper();
	        $this->attach_settings();
	        $this->attach_themes();

	        // Setup pointers and WordPress connections
	        //
	        $this->add_refs();
	        $this->add_wp_actions();

            $this->initDB();

            // Hook up the Locations class
            //
            if (class_exists('SLPlus_Location') == false) {
                require_once(SLPLUS_PLUGINDIR.'include/class.location.php');
            }
            $this->currentLocation = new SLPlus_Location(array('slplus' => $this));

            $this->themes->css_dir = SLPLUS_PLUGINDIR . 'css/';
            $this->initOptions();
            $this->initData();

            // HOOK: slp_invocation_complete
            do_action('slp_invocation_complete');
        }

	    /**
	     * Add meta links.
	     *
	     * TODO: ADMIN ONLY
	     *
	     * @param type $links
	     * @param type $file
	     * @return string
	     */
	    function add_meta_links($links, $file) {

		    if ($file == $this->basefile) {
			    if (isset($this->support_url)) {
				    $links[] = '<a href="' . $this->support_url . '" title="' . __('Documentation', 'csa-slplus') . '">' .
				               __('Documentation', 'csa-slplus') . '</a>';
			    }
			    if (isset($this->purchase_url)) {
				    $links[] = '<a href="' . $this->purchase_url . '" title="' . __('Buy Upgrades', 'csa-slplus') . '">' .
				               __('Buy Upgrades', 'csa-slplus') . '</a>';
			    }
			    $links[] = '<a href="options-general.php?page=' . $this->prefix . '-options" title="' .
			               __('Settings', 'csa-slplus') . '">' . __('Settings', 'csa-slplus') . '</a>';
		    }
		    return $links;
	    }

	    /**
	     * Add reflection references to settings.
	     */
	    function add_refs() {
		    // Notifications
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

		    // TODO: Admin Only
		    //
		    if (is_admin()) {
			    add_action('admin_menu', array($this, 'create_options_page'));
			    add_action('admin_init', array($this, 'admin_init'), 50);
			    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_stylesheet'));
			    add_action('admin_notices', array($this->notifications, 'display'));
		    }

		    add_filter('plugin_row_meta', array($this, 'add_meta_links'), 10, 2);
	    }

	    /**
	     * WordPress admin_init hook (runs after admin_menu has run)
	     *
	     * TODO: Admin Only
	     */
	    function admin_init() {
		    $this->settings->register();
	    }

	    /**
	     * Create the help object and attach it.
	     */
	    function attach_helper() {
		    if ( ! isset( $this->helper ) ) {
			    require_once( 'class.helper.php' );
			    $this->helper = new wpCSL_helper__slplus(
				    array(
					    'slplus' => $this
				    )
			    );
		    }
	    }

	    /**
	     * Create the notifications object and attach it.
	     *
	     */
	    function attach_notifications() {
		    if ( ! isset( $this->notifications ) ) {
			    require_once( 'class.notifications.php' );
			    $this->notifications = new wpCSL_notifications__slplus(
				    array(
					    'prefix' => SLPLUS_PREFIX,
					    'name'   => $this->name,
					    'url'    => 'options-general.php?page=' . SLPLUS_PREFIX . '-options',
				    )
			    );
		    }
	    }

	    /**
	     * Create the settings object and attach it.
	     */
	    function attach_settings() {
		    if ( ! isset( $this->settings ) ) {
			    require_once( 'class.settings.php' );
			    $this->settings = new wpCSL_settings__slplus(
				    array(
					    'prefix'     => SLPLUS_PREFIX,
					    'css_prefix' => SLPLUS_PREFIX,
					    'plugin_url' => $this->plugin_url,
					    'name'       => $this->name,
					    'url'        => ( isset( $this->url ) ? $this->url : null ),
					    'parent'     => $this
				    )
			    );
		    }
	    }

	    /**
	     * Create the theme object and attach it.
	     */
	    function attach_themes() {
		    if ( ! isset( $this->themes ) ) {
			    require_once( 'class.themes.php' );
			    $this->themes = new PluginTheme(
				    array(
					    'notifications' => $this->notifications,
					    'plugin_path'   => $this->plugin_path,
					    'plugin_url'    => $this->plugin_url,
					    'prefix'        => SLPLUS_PREFIX,
					    'slplus'        => $this,
					    'support_url'   => $this->support_url,
				    )
			    );
		    }
	    }


	    /**
	     * Connect SLPlus_Activation object to Activation property.
	     */
	    public function createobject_Activation() {
		    if ( ! isset( $this->Activation ) ) {
			    require_once(SLPLUS_PLUGINDIR . '/include/class.activation.php');
			    $this->Activation = new SLPlus_Activation();
		    }
	    }

        /**
         * Create and attach the add on manager object.
         */
        public function createobject_AddOnManager()  {
            if (!isset($this->add_ons)) {
                require_once(SLPLUS_PLUGINDIR . '/include/class.addon.manager.php');
                $this->add_ons = new SLPlus_AddOn_Manager(array('slplus' => $this));
            }
        }

	    /**
	     * Sets $this->isOurAdminPage true if we are on a SLP managed admin page.  Returns true/false accordingly.
	     *
	     * TODO: ADMIN ONLY
	     */
	    function check_IsOurAdminPage() {
		    if ( empty( $this->admin_slugs ) ) {
			    $this->admin_slugs = array(
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
			    );
		    }

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
			    ($this->current_admin_page === 'slp_info' )
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
	     * Create the options page.
	     *
	     * TODO: ADMIN ONLY
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
	     * Return a deprecated notification.
	     *
	     * TODO : move to a deprecated class, invoke with attach_deprecated.
	     *
	     * @param string $function_name name of function that is deprecated.
	     * @return string
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
				       'or <a href="%s" target="csa">downgrade</a> the %s plugin.', 'csa-slplus'), $this->purchase_url, $this->name, 'http://wordpress.org/plugins/store-locator-le/developers/', $this->name
			    )
			    ;
	    }


        /**
         * Setup the database properties.
         *
         * latlongRegex = '^\s*-?\d{1,3}\.\d+,\s*\d{1,3}\.\d+\s*$';
         *
         * @global type $wpdb
         */
        function initDB()
        {
            global $wpdb;
            $this->db = $wpdb;

            // Set the data object
            //
            require_once(SLPLUS_PLUGINDIR . '/include/class.data.php');
            $this->database = new SLPlus_Data();
        }

        /**
         * Initialize the options properties from the WordPress database.
         */
        function initOptions()
        {
            $this->debugMP('slp.main', 'msg', 'SLPlus:' . __FUNCTION__);

            // Set options defaults to values set in property definition above.
            //
            $this->options_default = $this->options;

            // Serialized Options from DB for JS parameters
            //
            $dbOptions = get_option(SLPLUS_PREFIX . '-options');
            if (is_array($dbOptions)) {
                array_walk($dbOptions, array($this, 'set_ValidOptions'));
            }

            // Non-serialized options from DB for JS Parameters
            //
            $this->options['zoom_level'] = get_option('sl_zoom_level', $this->options_default['zoom_level']);

            // Load serialized options for noJS parameters
            //
            $this->options_nojs['invalid_query_message'] = __('Store Locator Plus did not send back a valid JSONP response.', 'csa-slplus');
            $this->options_nojs_default = $this->options_nojs;
            $dbOptions = get_option(SLPLUS_PREFIX . '-options_nojs');
            if (is_array($dbOptions)) {
                array_walk($dbOptions, array($this, 'set_ValidOptionsNoJS'));
            }
            $this->javascript_is_forced = $this->is_CheckTrue($this->options_nojs['force_load_js']);

            // Legacy Items : Set Default from DB Entry
            //
            $this->defaults['label_directions'] = esc_attr(get_option(SLPLUS_PREFIX . '_label_directions', 'Directions'));
            $this->defaults['label_fax'] = esc_attr(get_option(SLPLUS_PREFIX . '_label_fax', 'Fax: '));
            $this->defaults['label_hours'] = esc_attr(get_option(SLPLUS_PREFIX . '_label_hours', 'Hours: '));
            $this->defaults['label_phone'] = esc_attr(get_option(SLPLUS_PREFIX . '_label_phone', 'Phone: '));
            $this->defaults['label_website'] = esc_attr(get_option('sl_website_label', 'Website'));

            // Options that get passed to the JavaScript
            // loaded from properties
            //
            foreach ($this->options as $name => $value) {
                if (!empty($this->defaults[$name])) {
                    $this->options[$name] = $this->defaults[$name];
                }
            }

            // FILTER: slp_set_options_needing_translation
            // gets the options_needing translation array used by the set_ValidOptions and set_ValidOptionsNoJS
            // methods that interface with WPML
            // return a modified array of options setting names
            //
            $this->options_needing_translation = apply_filters('slp_set_options_needing_translation', $this->options_needing_translation);

            $this->debugMP('slp.main', 'msg', '', 'Options passed to JavaScript:');
            $this->debugMP('slp.main', 'pr', '', $this->options);

            $this->debugMP('slp.main', 'msg', '', 'Non-JavaScript Options:');
            $this->debugMP('slp.main', 'pr', '', $this->options_nojs);
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
		    if ( ! $this->debugMP_is_active ) { return; }

		    // Escape HTML Messages
		    //
		    if (($type === 'msg') && ($message !== '')) {
			    $message = esc_html($message);
		    }

		    // TODO : Only if DebugMyPlugin Is Active
		    // otherwise we consume memory for no reason.
		    //
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

	    /**
	     * Enqueue the admin stylesheet when needed.
	     *
	     * TODO: ADMIN ONLY
	     *
	     * @var string $hook
	     */
	    function enqueue_admin_stylesheet($hook) {
		    $this->check_IsOurAdminPage();

		    // The CSS file must exists where we expect it and
		    // The admin page being rendered must be in "our family" of admin pages
		    //
		    if (file_exists($this->plugin_path . '/css/admin/admin.css') &&
		        array_search($hook, $this->admin_slugs)
		    ) {
			    wp_register_style($this->styleHandle, $this->plugin_url . '/css/admin/admin.css');
			    wp_enqueue_style($this->styleHandle);

			    // jQuery Smoothness Theme
			    //
			    if (file_exists($this->plugin_path . '/css/admin/jquery-ui-smoothness.css')) {
				    wp_enqueue_style(
					    'jquery-ui-smoothness', $this->plugin_url . '/css/admin/jquery-ui-smoothness.css'
				    );
			    }

			    if (file_exists($this->plugin_path . '/js/admin-interface.js')) {
				    wp_enqueue_script(
					    $this->styleHandle, $this->plugin_url . '/js/admin-interface.js', 'jquery', SLPLUS_VERSION, true
				    );
			    }
		    }

		    wp_enqueue_script('jquery-ui-dialog');
	    }

	    /**
	     * Get the current admin page.
	     *
	     * @return string
	     */
	    private function get_admin_page() {
	        if (isset($_GET['page'])) {
		        $plugin_page = stripslashes($_GET['page']);
		        return plugin_basename($plugin_page);
	        }
		    return '';
	    }

        /**
         * Set the plugin data property.
         *
         * Plugin data elements, helps make data lookups more efficient
         *
         * 'data' is where actual values are stored
         * 'dataElements' is used to fetch/initialize values whenever helper->loadPluginData() is called
         *
         * FILTER: slp_attribute_values
         * This filter only fires at the very start of SLP, it may not run add-on pack stuff.
         *
         * The slp_attribute_values fitler takes an array of arrays.
         *
         * The outter array is a list of instructions for setting the data property of this class.
         *
         * The inner array has 3 elements:
         *     first element is the name of the data property, the 'blah' in $this->data['blah'].
         *     second element is the method to employ to set the element, 'get_option' or 'get_item'.
         *     third element is the parameters to send along to the get_option or get_item method.
         *
         * If the second element is 'get_option' the third element can be:
         *     null - in this case $this->data['blah'] is set to get_option('blah')
         *     array('moreblah') - in this case $this->data['blah'] = get_option('moreblah')
         *     array('moreblah','default') - $this->data['blah'] = get_option('moreblah','default')
         *
         * get_option('moreblah','default') returns the value 'default' if the option 'moreblah' does not exist in the WP options table.
         */
        function initData()
        {
            $this->data = array();
            $this->dataElements = array(
                    array(
                        'sl_admin_locations_per_page',
                        'get_option',
                        array('sl_admin_locations_per_page', '25')
                    ),
                    array(
                        'sl_map_end_icon',
                        'get_option',
                        array('sl_map_end_icon', SLPLUS_ICONURL . 'bulb_azure.png')
                    ),
                    array('sl_map_home_icon',
                        'get_option',
                        array('sl_map_home_icon', SLPLUS_ICONURL . 'box_yellow_home.png')
                    ),
                    array('sl_map_height',
                        'get_option',
                        array('sl_map_height', '480')
                    ),
                    array('sl_map_height_units',
                        'get_option',
                        array('sl_map_height_units', 'px')
                    ),
                    array('sl_map_width',
                        'get_option',
                        array('sl_map_width', '100')
                    ),
                    array('sl_map_width_units',
                        'get_option',
                        array('sl_map_width_units', '%')
                    ),
                    array('theme',
                        'get_item',
                        array('theme', $this->defaults['theme'])
                    )
            );
        }

        /**
         * Return true if the named add-on pack is active.
         *
         * TODO: Legacy code, move to class.addon.manager when UML is updated.
         *
         * @param string $slug
         * @return boolean
         */
        public function is_AddonActive($slug)  {
            $this->createobject_AddOnManager();
            return $this->add_ons->is_active($slug);
        }

	    /**
	     * Return '1' if the given value is set to 'true', 'on', or '1' (case insensitive).
	     * Return '0' otherwise.
	     *
	     * Useful for checkbox values that may be stored as 'on' or '1'.
	     *
	     * @param $value
	     * @param string $return_type
	     *
	     * @return bool|string
	     */
        public function is_CheckTrue($value, $return_type = 'boolean')  {
            if ($return_type === 'string') {
                $true_value = '1';
                $false_value = '0';
            } else {
                $true_value = true;
                $false_value = false;
            }

            if (strcasecmp($value, 'true') == 0) {
                return $true_value;
            }
            if (strcasecmp($value, 'on') == 0) {
                return $true_value;
            }
            if (strcasecmp($value, '1') == 0) {
                return $true_value;
            }
            if ($value === 1) {
                return $true_value;
            }
            if ($value === true) {
                return $true_value;
            }
            return $false_value;
        }

	    /**
	     * Check if the debugMP plugin is active and installed.
	     *
	     * @return bool
	     */
	    public function is_debugMP_active() {
		    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		    if (!function_exists('is_plugin_active') || !is_plugin_active('debug-my-plugin/debug-my-plugin.php')) {
			    return false;
		    }
		    return true;
	    }

        /**
         * Checks if a URL is valid.
         *
         * @param $url
         * @return bool
         */
        public function is_valid_url($url)
        {
            $url = trim($url);

            return ((strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) &&
                filter_var($url, FILTER_VALIDATE_URL) !== false);
        }

        /**
         * Set valid options from the incoming REQUEST
         *
         * @param mixed $val - the value of a form var
         * @param string $key - the key for that form var
         */
        function set_ValidOptions($val, $key)
        {
            if (array_key_exists($key, $this->options)) {
                if (is_numeric($val) || !empty($val)) {
                    $this->options[$key] = stripslashes_deep($val);
                } else {
                    $this->options[$key] = $this->options_default[$key];
                }

                // i18n/l10n translations may be needed.
                if (array_key_exists($key, $this->options_needing_translation)) {
                    $this->plugin->AdminWPML->regWPMLText($key, $this->options[$key]);
                }
            }
        }

        /**
         * Set valid options from the incoming REQUEST
         *
         * Set this if the incoming value is not an empty string.
         *
         * @param mixed $val - the value of a form var
         * @param string $key - the key for that form var
         */
        function set_ValidOptionsNoJS($val, $key)
        {
            if (array_key_exists($key, $this->options_nojs)) {
                if (is_numeric($val) || !empty($val)) {
                    $this->options_nojs[$key] = stripslashes_deep($val);
                } else {
                    $this->options_nojs[$key] = $this->options_nojs_default[$key];
                }

                // i18n/l10n translations may be needed.
                if (array_key_exists($key, $this->options_needing_translation)) {
                    $this->plugin->AdminWPML->regWPMLText($key, $this->options_nojs[$key]);
                }
            }
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
	     * TODO: update the direct reference from slp-pages then this can go in base_class.addon.php directly.
	     *
	     * @param mixed[] $params
	     */
	    function VersionCheck($params) {

		    // Minimum version requirement not met.
		    //
		    if (version_compare(SLPLUS_VERSION, $params['min_required_version'], '<')) {
			    if (is_admin()) {
				    if (isset($this->notifications)) {
					    $this->notifications->add_notice(4, '<strong>' .
					                                        sprintf(__('%s has been deactivated.', 'csa-slplus'
					                                        ), $params['addon_name']
					                                        ) . '<br/> ' .
					                                        '</strong>' .
					                                        sprintf(__('You have %s version %s.', 'csa-slplus'
					                                        ), $this->name, SLPLUS_VERSION
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
         * Load Plugin Data once.
         *
         * Call $this->helper->loadPluginData(); to force a reload.
         */
        function loadPluginData()
        {
            if (!$this->pluginDataLoaded) {
                $this->helper->loadPluginData();
                $this->pluginDataLoaded = true;
            }
        }

        /**
         * Register an add-on pack.
         *
         * Keys always contain registered add-ons.
         * Values may contain a pointer to an instantiation of an add-on if it exists.
         *
         * @param string $slug
         * @param object $object
         */
        public function register_addon($slug, $object = null)
        {
            $slugparts = explode('/', $slug);
            $cleanslug = str_replace('.php', '', $slugparts[count($slugparts) - 1]);
            if (
                !isset($this->addons[$cleanslug]) ||
                (($this->addons[$cleanslug] == null) && ($object != null))
            ) {
                $this->addons[$cleanslug] = $object;
            }
        }

        /**
         * Register a base plugin module.
         *
         * @param string $name name of the module.
         * @param object $object pointer to the module.
         */
        public function register_module($name, $object = null)
        {
            $name = 'slp.' . $name;
            if (!isset($this->$name)) {
                $this->$name = $object;
            }
            $this->register_addon($name, $object);
        }

	    /**
	     * Update the base plugin if necessary.
	     */
	    function activate_or_update_slplus() {
		    if ( is_null( $this->installed_version ) ) {
			    $this->installed_version = get_option( SLPLUS_PREFIX . "-installed_base_version", '' );
		    }

		    if ( version_compare( $this->installed_version, SLPLUS_VERSION , '<' ) ) {
			    $this->createobject_Activation();
			    $this->Activation->update();
		    }
	    }

        //----------------------------------------------------
        // DEPRECATED
        //----------------------------------------------------

        /**
         * Returns false.  This is a legacy function that has been replaced.
         *
         * Use the direct database call instead.
         *
         * @deprecated since version 4.1.00
         * @return boolean
         */
        public function is_Extended() { return false; }
    }
}