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
     * ER: Enhanced Results web link.
     */
    const linkToER = '<a href="http://www.storelocatorplus.com/product/slp4-enhanced-results/" target="csa">Enhanced Results</a>';

    /**
     * ES: Enhanced Search web link.
     */
    const linkToES = '<a href="http://www.storelocatorplus.com/product/slp4-enhanced-search/" target="csa">Enhanced Search</a>';

    /**
     * PRO: Pro Pack web link.
     */
    const linkToPRO = '<a href="http://www.storelocatorplus.com/product/slp4-pro/" target="csa">Pro Pack</a>';

    /**
     * SE: Super Extendo web link.
     */
    const linkToSE = '<a href="http://www.storelocatorplus.com/product/slp4-super-extendo/" target="csa">Super Extendo</a>';

    /**
     * SLP: Store Locator Plus web link.
     */
    const linkToSLP = '<a href="http://www.storelocatorplus.com/product/store-locator-plus-4/" target="csa">Store Locator Plus</a>';

    /**
     * Major Version Definition, SLP3
     */
    const SLP3 = '3';

    /**
     * Major Version Definition, SLP4
     */
    const SLP4 = '4';

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
     * o 'slp-widget' Widget
     *
     * The values will be null if there is no pointer to the object,
     * or the object pointer to an instantiated version of the add-on.
     * 
     * @var objects[] $addons active add-ons
     */
    public $addons = array();

    /**
     * The Admin UI object.
     * 
     * @var SLPlus_AdminUI $AdminUI
     */
    public $AdminUI;

    /**
     * The User Interface object.
     *
     * @var SLPlus_UI $UI
     */
    public $UI;

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
    public  $defaults            = array(

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
        'bubblelayout'   => 
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
[slp_location description   suffix  br]</span></span>
<span id="slp_bubble_hours">[html br ifset hours]
<span class="location_detail_label">[slp_option   label_hours   ifset   hours]</span>
<span class="location_detail_hours">[slp_location hours         suffix    br]</span></span>
<span id="slp_bubble_img">[html br ifset img]
[slp_location image         wrap    img]</span>
<span id="slp_tags">[slp_location tags]</span>
</div>'
                                                                            ,

        'label_email'    => 'Email'                                         ,

        'layout'         =>
            '<div id="sl_div">[slp_search][slp_map][slp_results]</div>'     ,

        'maplayout'      =>
            '[slp_mapcontent][slp_maptagline]'                              ,


        'resultslayout'  => 
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
        <span class="slp_result_contact slp_result_directions"><a href="http://[slp_location map_domain]/maps?saddr=[slp_location search_address]&daddr=[slp_location location_address]" target="_blank" class="storelocatorlink">[slp_location directions_text]</a></span>
        <span class="slp_result_contact slp_result_hours">[slp_location hours]</span>
        [slp_location iconarray wrap="fullspan"]
        </div>
</div>'
                                                                            ,

        'searchlayout'  => 
'<div id="address_search">
    [slp_search_element input_with_label="name"]
    [slp_search_element input_with_label="address"]
    [slp_search_element dropdown_with_label="state"]
    [slp_search_element selector_with_label="tag"]
    [slp_search_element dropdown_with_label="category"]
    <div class="search_item">
        [slp_search_element dropdown_with_label="radius"]
        [slp_search_element button="submit"]
    </div>
</div>'
                                                                            ,

        );


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
     * Anything stored in here also gets passed to the slp.js via the slplus.options object.
     * Reference the settings in the slp.js via slplus.options.<key>
     *
     * These elements are LOADED EVERY TIME the plugin starts.
     *
     * @var mixed[] $options
     */
    public  $options            = array(
        'bubblelayout'          => ''               ,
        'initial_radius'        => '10000'          ,
        'label_directions'      => ''               ,
        'label_email'           => ''               ,
        'label_fax'             => ''               ,
        'label_hours'           => ''               ,
        'label_phone'           => ''               ,
        'label_website'         => ''               ,
        'slplus_version'        => SLPLUS_VERSION   ,
    );

    /**
     * Serialized plugin options that do NOT get passed to slp.js.
     *
     * @var mixed[]
     */
    public $options_nojs    = array(
        'retry_maximum_delay'    => '5.0',
    );

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
        $this->url  = plugins_url('',__FILE__);
        $this->dir  = plugin_dir_path(__FILE__);
        $this->slug = plugin_basename(__FILE__);

        parent::__construct($params);
        $this->initDB();
        $this->currentLocation = new SLPlus_Location(array('plugin'=>$this));
        $this->themes->css_dir = SLPLUS_PLUGINDIR . 'css/';
        $this->initOptions();
        $this->initData();

        // HOOK: slp_invoation_complete
        do_action('slp_invocation_complete');
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
        $this->debugMP('slp.main','msg','SLPlus:'.__FUNCTION__);

        // Options from the database
        //
        $dbOptions = get_option(SLPLUS_PREFIX.'-options');
        if (is_array($dbOptions)) {
            array_walk($dbOptions,array($this,'set_ValidOptions'));
        }

        // Load serialized options for noJS
        //
        $dbOptions = get_option(SLPLUS_PREFIX.'-options_nojs');
        if (is_array($dbOptions)) {
            array_walk($dbOptions,array($this,'set_ValidOptionsNoJS'));
        }

        // Legacy Items : Set Default from DB Entry
        //
        $this->defaults['label_directions'] = esc_attr(get_option(SLPLUS_PREFIX.'_label_directions' , 'Directions'  ));
        $this->defaults['label_fax'  ]      = esc_attr(get_option(SLPLUS_PREFIX.'_label_fax'        , 'Fax: '       ));
        $this->defaults['label_hours']      = esc_attr(get_option(SLPLUS_PREFIX.'_label_hours'      , 'Hours: '     ));
        $this->defaults['label_phone']      = esc_attr(get_option(SLPLUS_PREFIX.'_label_phone'      , 'Phone: '     ));
        $this->defaults['label_website']    = esc_attr(get_option('sl_website_label'                , 'Website'     ));

        // Options that get passed to the JavaScript
        // loaded from properties
        //
        foreach ($this->options as $name=>$value) {
            if (!empty($this->defaults[$name])) {
                $this->options[$name] = $this->defaults[$name];
            }
        }

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
     * FILTER: wpcsl_loadplugindata__slplus
     * This filter fires much later, only when loadplugindata() methods are called in wpcsl.
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
        $this->dataElements =
            apply_filters('slp_attribute_values',
                array(
                      array(
                        'sl_admin_locations_per_page',
                        'get_option',
                        array('sl_admin_locations_per_page','25')
                      ),
                      array(
                        'sl_map_end_icon'                   ,
                        'get_option'                ,
                        array('sl_map_end_icon'         ,SLPLUS_ICONURL.'bulb_azure.png'    )
                      ),
                      array('sl_map_home_icon'              ,
                          'get_option'              ,
                          array('sl_map_home_icon'      ,SLPLUS_ICONURL.'box_yellow_home.png'  )
                      ),
                      array('sl_map_height'         ,
                          'get_option'              ,
                          array('sl_map_height'         ,'480'                                  )
                      ),
                      array('sl_map_height_units'   ,
                          'get_option'              ,
                          array('sl_map_height_units'   ,'px'                                   )
                      ),
                      array('sl_map_width'          ,
                          'get_option'              ,
                          array('sl_map_width'          ,'100'                                  )
                      ),
                      array('sl_map_width_units'    ,
                          'get_option'              ,
                          array('sl_map_width_units'    ,'%'                                    )
                      ),
                      array('theme'                 ,
                          'get_item'                ,
                          array('theme'                 ,'default'                              )
                      ),
                )
           );
    }

    /**
     * Return true if the named add-on pack is active.
     *
     * @param string $addon_slug
     * @return boolean
     */
    public function is_AddonActive($addon_slug) {
        return (
          array_key_exists($addon_slug,$this->addons) &&
          is_object($this->addons[$addon_slug]) &&
          !empty($this->addons[$addon_slug]->options['installed_version'])
          );
    }

    /**
     * Return true if the Extendo plugin is active.
     */
    public function is_Extended() {
        return $this->is_AddonActive('slp-extendo');
    }

    /**
     * Set valid options from the incoming REQUEST
     *
     * @param mixed $val - the value of a form var
     * @param string $key - the key for that form var
     */
    function set_ValidOptions($val,$key) {
        if (
                array_key_exists($key, $this->options) &&
                !empty($val)
            ) {
            $this->options[$key] = stripslashes_deep($val);
            $this->debugMP('slp.main','msg','',"set options[{$key}]=".stripslashes_deep($val),NULL,NULL,true);
        }
     }

    /**
     * Set valid options from the incoming REQUEST
     *
     * @param mixed $val - the value of a form var
     * @param string $key - the key for that form var
     */
    function set_ValidOptionsNoJS($val,$key) {
        if (
                array_key_exists($key, $this->options_nojs) &&
                !empty($val)
            ) {
            $this->options_nojs[$key] = stripslashes_deep($val);
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
    public function register_addon($slug,$object=null) {
        $slugparts = explode('/', $slug);
        $cleanslug = str_replace('.php','',$slugparts[count($slugparts)-1]);
        if (
            !isset($this->addons[$cleanslug])    ||
            (($this->addons[$cleanslug] == null)  && ($object != null))
        ){
            $this->addons[$cleanslug] = $object;
        }
    }

    /**
     * Register a base plugin module.
     *
     * @param string $name name of the module.
     * @param object $object pointer to the module.
     */
    public function register_module($name,$object=null) {
        $name = 'slp.'.$name;
        if (!isset($this->$name)) { $this->$name = $object; }
        $this->register_addon($name,$object);
    }

    /**
     * Build a query string of the add-on packages.
     *
     * @return string
     */
    public function create_addon_query() {
        return http_build_query(array_keys($this->addons),'addon_');
    }
}
