<?php

/**
 * The base plugin class for Store Locator Plus.
 *
 * "gloms onto" the WPCSL base class, extending it for our needs.
 *
 * @package StoreLocatorPlus
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2014 Charleston Software Associates, LLC
 *
 */
class SLPlus extends wpCSL_plugin__slplus {
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
     * The User Interface object.
     *
     * @var SLPlus_UI $UI
     */
    public $UI;

    /**
     * The WPML Interface object.
     *
     * @var SLPlus_WPML $WPML
     */
    public $WPML;

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
     * @var mixed[] $defaults
     */
    public $defaults = array(

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
     * @var \SLP_Extension
     */
    public $extension;

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
        'bubblelayout'               => '',
        'distance_unit'              => 'miles',
        'immediately_show_locations' => '1',
        'initial_radius'             => '10000',
        'initial_results_returned'   => '25',
        'label_directions'           => '',
        'label_email'                => 'Email',
        'label_fax'                  => '',
        'label_hours'                => '',
        'label_phone'                => '',
        'label_website'              => '',
        'map_domain'                 => 'maps.google.com',
        'slplus_version'             => SLPLUS_VERSION,

        // AJAX Incoming Variables
        //
        'ignore_radius'              => '0',  // Passed in as form var from Enhanced Search
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
        'label_directions'  ,
        'label_email'       ,
        'label_fax'         ,
        'label_hours'       ,
        'label_phone'       ,
        'label_website'     ,
        );

    /**
     * Serialized plugin options that do NOT get passed to slp.js.
     *
     * @var mixed[]
     */
    public $options_nojs = array(
        'extended_admin_messages'   => '0',
	    'extended_data_tested'      => '0',
        'force_load_js'             => '0',
        'google_client_id'          => '',
        'google_private_key'        => '',
        'has_extended_data'         => '',
        'http_timeout'              => '10', // HTTP timeout for GeoCode Requests (s)
        'max_results_returned'      => '25',
        'next_field_id'             => 1,
        'next_field_ported'         => '',
        'php_max_execution_time'    => '600',
        'retry_maximum_delay'       => '5.0',
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
     * Full path to this plugin directory.
     *
     * @var string $dir
     */
    private $dir;

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
     * Quick reference for the Force Load JavaScript setting.
     * 
     * @var boolean
     */
    public $javascript_is_forced = true;

    /**
     * Set to true if the plugin data was already loaded.
     *
     * @var boolean $pluginDataLoaded
     */
    public $pluginDataLoaded = false;

    /**
     * What slug do we go by?
     *
     * @var string $slug
     */
    public $slug;

    /**
     * Full URL to this plugin directory.
     *
     * @var string $url
     */
    public $url;

    //-------------------------------------
    // Methods
    //-------------------------------------

    /**
     * Initialize a new SLPlus Object
     *
     * @param mixed[] $params - a named array of the plugin options for wpCSL.
     */
    public function __construct($params) {
        $this->url = plugins_url('', __FILE__);
        $this->dir = plugin_dir_path(__FILE__);
        $this->slug = plugin_basename(__FILE__);

        parent::__construct($params);
        $this->initDB();
        $this->currentLocation = new SLPlus_Location(array('slplus' => $this));
        $this->themes->css_dir = SLPLUS_PLUGINDIR . 'css/';
        $this->initOptions();
        $this->initData();

        // HOOK: slp_invoation_complete
        do_action('slp_invocation_complete');
    }
    
    /**
     * Create and attach the add on manager object.
     */
    public function createobject_AddOnManager() {
        if (!isset($this->add_ons)) {
            require_once(SLPLUS_PLUGINDIR . '/include/class.addon.manager.php');
            $this->add_ons = new SLPlus_AddOn_Manager( array( 'slplus' => $this ) );
        }
    }

    /**
     * Setup the database properties.
     *
     * latlongRegex = '^\s*-?\d{1,3}\.\d+,\s*\d{1,3}\.\d+\s*$';
     *
     * @global type $wpdb
     */
    function initDB() {
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
    function initOptions() {
        $this->debugMP('slp.main', 'msg', 'SLPlus:' . __FUNCTION__);

        // Options from the database
        //
        $this->options_default = $this->options;
        $dbOptions = get_option(SLPLUS_PREFIX . '-options');
        if (is_array($dbOptions)) {
            array_walk($dbOptions, array($this, 'set_ValidOptions'));
        }

        // Load serialized options for noJS
        //
        $this->options_nojs_default = $this->options_nojs;
        $dbOptions = get_option(SLPLUS_PREFIX . '-options_nojs');
        if (is_array($dbOptions)) {
            array_walk($dbOptions, array($this, 'set_ValidOptionsNoJS'));
        }
        $this->javascript_is_forced = $this->is_CheckTrue( $this->options_nojs['force_load_js'] );

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
        $this->options_needing_translation = apply_filters( 'slp_set_options_needing_translation' , $this->options_needing_translation );

        $this->debugMP('slp.main', 'msg', '', 'Options passed to JavaScript:');
        $this->debugMP('slp.main', 'pr', '', $this->options);

        $this->debugMP('slp.main', 'msg', '', 'Non-JavaScript Options:');
        $this->debugMP('slp.main', 'pr', '', $this->options_nojs);
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
    function initData() {
        $this->data = array();
        $this->dataElements = apply_filters('slp_attribute_values', array(
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
                array('theme', 'default')
            ),
                )
        );
    }

    /**
     * Return true if the named add-on pack is active.
     * 
     * TODO: Legacy code, move to class.addon.manager when UML is updated.
     *
     * @param string $addon_slug
     * @return boolean
     */
    public function is_AddonActive( $slug ) {
        $this->createobject_AddOnManager();
		return $this->add_ons->is_active( $slug );
    }

    /**
     * Return '1' if the given value is set to 'true', 'on', or '1' (case insensitive).
     * Return '0' otherwise.
     *
     * Useful for checkbox values that may be stored as 'on' or '1'.
     *
     * @param string $attValue
     * @return boolean
     */
    public function is_CheckTrue($value, $return_type = 'boolean') {
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
     * Checks if a URL is valid.
     *
     * @param $url
     * @return bool
     */
    public function is_valid_url( $url ) {
        $url = trim( $url );

        return ( ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) &&
            filter_var( $url, FILTER_VALIDATE_URL ) !== false );
    }


    /**
     * Set valid options from the incoming REQUEST
     *
     * @param mixed $val - the value of a form var
     * @param string $key - the key for that form var
     */
    function set_ValidOptions($val, $key) {
        if ( array_key_exists($key, $this->options) ) {
            if ( is_numeric($val) || ! empty( $val ) ) {
                $this->options[$key] = stripslashes_deep($val);
            } else {
                $this->options[$key] = $this->options_default[$key];
            }

            // i18n/l10n translations may be needed.
            if  ( array_key_exists( $key , $this->options_needing_translation ) ) {
                $this->plugin->AdminWPML->regWPMLText( $key , $this->options[$key] );
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
    function set_ValidOptionsNoJS($val, $key) {
        if ( array_key_exists($key, $this->options_nojs) ) {
            if ( is_numeric($val) || ! empty( $val ) ) {
                $this->options_nojs[$key] = stripslashes_deep($val);
            } else {
                $this->options_nojs[$key] = $this->options_nojs_default[$key];
            }

            // i18n/l10n translations may be needed.
            if  ( array_key_exists( $key , $this->options_needing_translation ) ) {
                $this->plugin->AdminWPML->regWPMLText( $key , $this->options_nojs[$key] );
            }
        }
    }

    /**
     * Load Plugin Data once.
     *
     * Call $this->helper->loadPluginData(); to force a reload.
     */
    function loadPluginData() {
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
    public function register_addon($slug, $object = null) {
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
    public function register_module($name, $object = null) {
        $name = 'slp.' . $name;
        if (!isset($this->$name)) {
            $this->$name = $object;
        }
        $this->register_addon($name, $object);
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
    public function is_Extended() {
        return false;
    }

}
