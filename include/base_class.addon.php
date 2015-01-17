<?php
if (! class_exists('SLP_BaseClass_Addon')) {

    /**
     * A base class that consolidates common add-on pack methods.
     *
     * Add on packs should base based on and extend this class.
     * 
     * Setting the following properties will activate hooks to various
     * classes that will be instantiated as objects when needed:
     * 
     * o 'admin_class_name' => 'Your_SLP_Admin_Class' 
     * o 'user_class_name'  => 'Your_SLP_UI_Class'
     * 
     * The admin class definition needs to go in your add-on pack
     * under the ./include directory and be named 'class.admin.php'.
     * The name of the class needs to match the provided string.
     * The admin object will only be instantiated when WordPress is
     * rendering the admin interface.    
     * 
     * The user class definition needs to go in your add-on pack
     * under the ./include directory and be named 'class.userinterface.php'.
     * The name of the class needs to match the provided string.
     * The user object will only be instantiated when WordPress is
     * rendering the WordPress front end.
     * 
     * This methodology provides a standard construct for coding admin-only
     * and user-interface-only elements of a WordPress add-on pack.   This
     * will mean less code is loaded into active ram, avoiding loading UI
     * only code when on the admin panel and vice versa.
     *
     * @package StoreLocatorPlus\BaseClass\Addon
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class SLP_BaseClass_Addon {

        //-------------------------------------
        // Properties
        //-------------------------------------

        /**
         * This addon pack.
         *
         * @var mixed $addon
         */
        protected $addon;
        
        /**
         * The admin object.
         * 
         * @var \SLP_BaseClass_Admin
         */
        public $admin;

        /**
         * The ajax object.
         *
         * @var
         */
        public $ajax;
        
        /**
         * The name of the activation class for this add on.
         * 
         * If empty there is not activation (upgrade an install) for the add on pack.
         * 
         * @var string
         */
        public $activation_class_name;
        
        /**
         * The name of the admin class for this add on.
         * 
         * If empty the admin interface is not activated.
         * 
         * @var string
         */
        protected $admin_class_name;

        /**
         * The name of the AJAX class for this add on.
         *
         * If empty the AJAX processing interface is not activated.
         *
         * @var string
         */
        protected $ajax_class_name;
        
        /**
         * The directory the add-on pack resides in.
         * 
         * @var string
         */
        public $dir;

        /**
         * SLP Menu Entries
         *
         * Should be in a key=>value array where key = the menu text and value = the function or PHP file to execute.
         *
         * @var mixed[] array of menu entries.
         */
        public $admin_menu_entries;
        
        /**
         * WordPress data about this plugin read from the php headstone.
         * 
         * @var mixed[]
         */
        public $metadata;
        
        /**
         * Minimum version of SLP required to run this add-on pack in x.y.zz format.
         * 
         * @var string
         */
        protected $min_slp_version;
        
        /**
         * Text name for this add on pack.
         * 
         * @var string
         */
        public $name;       
                
        /**
         * The name of the wp_option to store serialized add-on pack settings.
         * 
         * @var string
         */
        public $option_name;

        /**
         * Settable options for this plugin. (Does NOT go into main plugin JavaScript)
         *
         * @var mixed[]
         */        
        public $options = array(
            'installed_version'             => ''           ,
        );

        /**
         * Default options.
         *
         * @var array
         */
        public $options_defaults = array(

        );

        /**
         * The base SLPlus object.
         *
         * @var \SLPlus $slplus
         */
        public $slplus;
        
        /**
         * The slug for this plugin, usually matches the plugin subdirectory name.
         * 
         * @var string
         */
        public $slug;
        
        /**
         * The url for this plugin admin features.
         * 
         * @var string
         */
        public $url;
                
        /**
         * The name of the user class for this add on.
         * 
         * If empty the user interface is not activated.
         * 
         * @var string
         */
        protected $userinterface_class_name;     
        
        /**
         * The user interface object.
         * 
         * @var \SLP_BaseClass_UserInterface
         */        
        public $userinterface;
        
        /**
         * Current version of this add-on pack in x.y.zz format.
         * 
         * @var string
         */
        public $version;

        //-------------------------------------
        // Methods
        //-------------------------------------

        /**
         * Instantiate the admin panel object.
         *
         * @param mixed[] $params
         */
        function __construct($params) {
            // Set properties based on constructor params,
            // if the property named in the params array is well defined.
            //
            if ($params !== null) {
                foreach ($params as $property=>$value) {
                    if (property_exists($this,$property)) { $this->$property = $value; }
                }
            }
                
            // When SLP finished initializing do this
            //
            add_action('slp_init_complete', array($this, 'slp_init'));
        }
        
        /**
         * Things to do once SLP is alive.
         */
        function slp_init() {
            global $slplus_plugin;
            $this->addon  = $this;
            $this->slplus = $slplus_plugin;
            
            // Check the base plugin minimum version requirement.
            //
            $this->slplus->VersionCheck(array(
                'addon_name' => $this->name,
                'addon_slug' => $this->slug,
                'min_required_version' => $this->min_slp_version
            ));

            // Tell SLP we are here
            //
            $this->slplus->register_addon($this->slug, $this);

            // Initialize The Options
            //
            $this->init_options();

	        // Add Hooks and Filters
	        //
	        $this->add_hooks_and_filters();

             // Admin Interface?
             //
             if ( ! empty( $this->admin_class_name ) ) {
                 add_filter( 'slp_menu_items'          , array( $this , 'filter_AddMenuItems'   ) );
                 add_action( 'slp_admin_menu_starting' , array( $this , 'admin_menu'            ) );
             }

             // User Interface?
             //
             if ( ! empty( $this->userinterface_class_name ) ) { 
                 add_action( 'wp_enqueue_scripts', array( $this, 'userinterface_init' ) );
             }

            // AJAX Processing
            //
            if ( defined('DOING_AJAX') && DOING_AJAX && ! empty( $this->ajax_class_name ) ) {
                $this->createobject_AJAX();
            }
        }

        /**
         * Add the items specified in the menu_entries property to the SLP menu.
         *
         * @param mixed[] $menuItems
         * @return mixed[]
         */
        function filter_AddMenuItems( $menuItems ) {
            if ( ! isset( $this->admin_menu_entries) ) { return $menuItems; }
            return array_merge( $menuItems, $this->admin_menu_entries );
        }

	    /**
	     * Add the plugin specific hooks and filter configurations here.
	     *
	     * The hooks & filters that go here are cross-interface element hooks/filters needed in 2+ locations:
	     * - AJAX
	     * - Admin Interface
	     * - User Interface
	     *
	     * For example, custom taxonomy hooks and filters.
	     *
	     * Should include WordPress and SLP specific hooks and filters.
	     */
	    function add_hooks_and_filters() {
		    // Add your hooks and filters in the class that extends this base class.
	    }

        /**
         * WordPress admin_init hook.
         */
        function admin_init() {
            $this->createobject_Admin();
        }
        
        /**
         * WordPress admin_menu hook.
         * 
         * Only fires when WordPress is in the admin panel interface.
         */
        function admin_menu() {
            add_action( 'admin_init' , array( $this, 'admin_init' ), 20 );
        }
        
        /**
         * Run these things when running front-end (Non-Admin or AJAX) stuff.
         */
        function userinterface_init() {
            $this->createobject_UserInterface();
        }
        
        /**
         * Create the admin interface object and attach to this->admin
         */
        function createobject_Admin() {
            if ( !isset( $this->admin ) ) {
                require_once($this->dir . 'include/class.admin.php');
                $this->admin = new $this->admin_class_name(
                    array(
                        'addon'     => $this,
                        'slplus'    => $this->slplus,
                    )
                );
            }
        }

        /**
         * Create the AJAX procssing object and attach to this->ajax
         */
        function createobject_AJAX() {
            if ( !isset( $this->ajax ) ) {
                require_once($this->dir . 'include/class.ajax.php');
                $this->ajax = new $this->ajax_class_name(
                    array(
                        'addon'     => $this,
                        'slplus'    => $this->slplus,
                    )
                );
            }
        }

        /**
         * Create the user interface object and attach to this->UserInterface
         */
        function createobject_UserInterface() {
            if ( !isset( $this->userinterface ) ) {
                require_once($this->dir . 'include/class.userinterface.php');
                $this->userinterface = new $this->userinterface_class_name(
                    array(
                        'addon'     => $this,
                        'slplus'    => $this->slplus,
                    )
                );
            }
        }

        /**
         * Initialize the options properties from the WordPress database.
         *
         */
        function init_options() {
            if ( isset( $this->option_name) ) {
                $dbOptions = get_option($this->option_name);
                if (is_array($dbOptions)) {
                    $this->options = array_merge( $this->options, $this->options_defaults );
                    $this->options = array_merge( $this->options, $dbOptions );
                }
            }
        }
    }
}