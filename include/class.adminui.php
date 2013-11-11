<?php
/**
 * Store Locator Plus basic admin user interface.
 *
 * @package StoreLocatorPlus\AdminUI
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 */
class SLPlus_AdminUI {

    //-------------------------------------
    // Properties
    //-------------------------------------

    /**
     * Has the create_InputElement deprecation notice been shown already?
     *
     * @var boolean $depnotice_create_InputElement
     */
    private  $depnotice_create_InputElement = false;

    /**
     *
     * @var \SLPlus_AdminUI_Locations $ManageLocations
     */
    public $ManageLocations;

    /**
     *
     * @var \SLPlus_AdminUI_MapSettings $MapSettings
     */
    public $MapSettings;

    /**
     * The SLPlus Plugin
     * 
     * @var \SLPlus
     */
    public $parent = null;

    /**
     * The SLPlus Plugin
     *
     * @var \SLPlus
     */
    public $plugin = null;

    /**
     *
     * @var string $styleHandle
     */
    public $styleHandle = 'csl_slplus_admin_css';

    /**
     * @var \SLPlus_AdminUI_GeneralSettings $GeneralSettings
     */
    public $GeneralSettings;

    /**
     * The Info object.
     * 
     * @var \SLPlus_AdminUI_Info $Info
     */
    private $Info;

    //----------------------------------
    // Methods
    //----------------------------------

    /**
     * Invoke the AdminUI class.
     *
     */
    function __construct() {

        // Register our admin styleseheet
        //
        if (file_exists(SLPLUS_PLUGINDIR.'css/admin.css')) {
            wp_register_style($this->styleHandle, SLPLUS_PLUGINURL .'/css/admin.css');
        }
    }

    /**
     * Set the parent property to point to the primary plugin object.
     *
     * Returns false if we can't get to the main plugin object.
     *
     * @global wpCSL_plugin__slplus $slplus_plugin
     * @return type boolean true if plugin property is valid
     */
    function setParent() {
        if (!isset($this->parent) || ($this->parent == null)) {
            global $slplus_plugin;
            $this->parent = $slplus_plugin;
            $this->plugin = $slplus_plugin;
        }

        return (isset($this->parent) && ($this->parent != null));
    }

    /**
     * Setup some of the general settings interface elements.
     */
    function build_basic_admin_settings() {
        if (!$this->setParent()) { return; }
        $this->parent->settings->add_section(
            array(
                'name'          => __('Navigation','csa-slplus'),
                'div_id'        => 'navbar_wrapper',
                'description'   => $this->create_Navbar(),
                'innerdiv'      => false,
                'is_topmenu'    => true,
                'auto'          => false,
                'headerbar'     => false
            )
        );

        //-------------------------
        // How to Use Section
        //-------------------------
        require_once(SLPLUS_PLUGINDIR . '/include/class.adminui.info.php');
        $this->Info = new SLPlus_AdminUI_Info();
        $this->parent->settings->add_section(
            array(
                'name' => __('How to Use','csa-slplus'),
                'description' => $this->Info->createstring_HowToUse(),
                'start_collapsed' => false
            )
        );
    }

    /**
     * Initialize variables for the map settings.
     * 
     * @global type $sl_google_map_country
     * @global type $sl_location_table_view
     * @global type $sl_zoom_level
     * @global type $sl_zoom_tweak
     * @global type $sl_use_name_search
     */
    function initialize_variables() {
        global $sl_google_map_country, $sl_location_table_view,$sl_zoom_level, $sl_zoom_tweak, $sl_use_name_search;

        $sl_map_type=get_option('sl_map_type');
        if (isset($sl_map_type)) {
            $sl_map_type='roadmap';
            add_option('sl_map_type', $sl_map_type);
            }
        $sl_remove_credits=get_option('sl_remove_credits');
        if (empty($sl_remove_credits)) {
            $sl_remove_credits="0";
            add_option('sl_remove_credits', $sl_remove_credits);
            }
        $sl_use_name_search=get_option('sl_use_name_search');
        if (empty($sl_use_name_search)) {
            $sl_use_name_search="0";
            add_option('sl_use_name_search', $sl_use_name_search);
            }

        $sl_zoom_level=get_option('sl_zoom_level','4');
        add_option('sl_zoom_level', $sl_zoom_level);

        $sl_zoom_tweak=get_option('sl_zoom_tweak','1');
        add_option('sl_zoom_tweak', $sl_zoom_tweak);

        $sl_location_table_view=get_option('sl_location_table_view');
        if (empty($sl_location_table_view)) {
            $sl_location_table_view="Normal";
            add_option('sl_location_table_view', $sl_location_table_view);
            }
        $sl_google_map_country=get_option('sl_google_map_country');
        if (empty($sl_google_map_country)) {
            $sl_google_map_country="United States";
            add_option('sl_google_map_country', $sl_google_map_country);
        }
    }

    /**
     * Enqueue the admin stylesheet when needed.
     */
    function enqueue_admin_stylesheet($hook) {
        if ($this->plugin->check_isOurAdminPage()) {wp_enqueue_style($this->styleHandle);}
    }

    /**
     * Render the General Settings page.
     *
     */
    function renderPage_GeneralSettings() {
        require_once(SLPLUS_PLUGINDIR . '/include/class.adminui.generalsettings.php');
        $this->GeneralSettings = new SLPlus_AdminUI_GeneralSettings();
        $this->GeneralSettings->render_adminpage();
    }

    /**
     * Render the Info page.
     *
     */
    function renderPage_Info() {
        if (!$this->setParent()) { return; }
        $this->parent->settings->no_save_button = true;
        $this->parent->settings->render_settings_page();
    }

    /**
     * Render the Locations admin page.
     */
    function renderPage_Locations() {
        require_once(SLPLUS_PLUGINDIR . '/include/class.adminui.locations.php');
        $this->ManageLocations = new SLPlus_AdminUI_Locations();
        $this->ManageLocations->render_adminpage();
    }

    /**
     * Render the Map Settings admin page.
     */
    function renderPage_MapSettings() {
        require_once(SLPLUS_PLUGINDIR . '/include/class.adminui.mapsettings.php');
        $this->MapSettings = new SLPlus_AdminUI_MapSettings();
        $this->MapSettings->render_adminpage();
    }

    /**
     * Render the admin page navbar (tabs)
     *
     * @global mixed[] $submenu the WordPress Submenu array
     * @param boolean $addWrap add a wrap div
     * @return string
     */
    function create_Navbar($addWrap = false) {
        if (!$this->setParent()) { return; }

        global $submenu;
        if (!isset($submenu[$this->parent->prefix]) || !is_array($submenu[$this->parent->prefix])) {
            echo apply_filters('slp_navbar','');
        } else {
            $content =
                ($addWrap?"<div id='wpcsl-option-navbar_wrapper'>":'').
                '<div id="slplus_navbar">' .
                    '<div class="about-wrap"><h2 class="nav-tab-wrapper">';

            // Loop through all SLP sidebar menu items on admin page
            //
            foreach ($submenu[$this->parent->prefix] as $slp_menu_item) {

                // Create top menu item
                //
                $selectedTab = ((isset($_REQUEST['page']) && ($_REQUEST['page'] === $slp_menu_item[2])) ? ' nav-tab-active' : '' );
                $content .= apply_filters(
                        'slp_navbar_item_tweak',
                        '<a class="nav-tab'.$selectedTab.'" href="'.menu_page_url( $slp_menu_item[2], false ).'">'.
                            $slp_menu_item[0].
                        '</a>'
                        );
            }
            $content .= apply_filters('slp_navbar_item','');
            $content .='</h2></div></div>'.($addWrap?'</div>':'');
            return apply_filters('slp_navbar',$content);
        }
    }

    /**
     * Return the icon selector HTML for the icon images in saved markers and default icon directories.
     *
     * @param type $inputFieldID
     * @param type $inputImageID
     * @return string
     */
     function CreateIconSelector($inputFieldID = null, $inputImageID = null) {
        if (!$this->setParent()) { return 'could not set parent'; }
        if (($inputFieldID == null) || ($inputImageID == null)) { return ''; }


        $htmlStr = '';
        $files=array();
        $fqURL=array();


        // If we already got a list of icons and URLS, just use those
        //
        if (
            isset($this->parent->data['iconselector_files']) &&
            isset($this->parent->data['iconselector_urls'] )
           ) {
            $files = $this->parent->data['iconselector_files'];
            $fqURL = $this->parent->data['iconselector_urls'];

        // If not, build the icon info but remember it for later
        // this helps cut down looping directory info twice (time consuming)
        // for things like home and end icon processing.
        //
        } else {

            // Load the file list from our directories
            //
            // using the same array for all allows us to collapse files by
            // same name, last directory in is highest precedence.
            $iconAssets = apply_filters('slp_icon_directories',
                    array(
                            array('dir'=>SLPLUS_UPLOADDIR.'saved-icons/',
                                  'url'=>SLPLUS_UPLOADURL.'saved-icons/'
                                 ),
                            array('dir'=>SLPLUS_ICONDIR,
                                  'url'=>SLPLUS_ICONURL
                                 )
                        )
                    );
            $fqURLIndex = 0;
            foreach ($iconAssets as $icon) {
                if (is_dir($icon['dir'])) {
                    if ($iconDir=opendir($icon['dir'])) {
                        $fqURL[] = $icon['url'];
                        while ($filename = readdir($iconDir)) {
                            if (strpos($filename,'.')===0) { continue; }
                            $files[$filename] = $fqURLIndex;
                        };
                        closedir($iconDir);
                        $fqURLIndex++;
                    } else {
                        $this->parent->notifications->add_notice(
                                9,
                                sprintf(
                                        __('Could not read icon directory %s','csa-slplus'),
                                        $directory
                                        )
                                );
                         $this->parent->notifications->display();
                    }
               }
            }
            ksort($files);
            $this->parent->data['iconselector_files'] = $files;
            $this->parent->data['iconselector_urls']  = $fqURL;
        }

        // Build our icon array now that we have a full file list.
        //
        foreach ($files as $filename => $fqURLIndex) {
            if (
                (preg_match('/\.(png|gif|jpg)/i', $filename) > 0) &&
                (preg_match('/shadow\.(png|gif|jpg)/i', $filename) <= 0)
                ) {
                $htmlStr .=
                    "<div class='slp_icon_selector_box'>".
                        "<img class='slp_icon_selector'
                             src='".$fqURL[$fqURLIndex].$filename."'
                             onclick='".
                                "document.getElementById(\"".$inputFieldID."\").value=this.src;".
                                "document.getElementById(\"".$inputImageID."\").src=this.src;".
                             "'>".
                     "</div>"
                     ;
            }
        }

        // Wrap it in a div
        //
        if ($htmlStr != '') {
            $htmlStr = '<div id="'.$inputFieldID.'_icon_row" class="slp_icon_row">'.$htmlStr.'</div>';

        }


        return $htmlStr;
     }

     /**
      * Merge existing options and POST options, then save to the wp_options table.
      *
      * Typically used to merge post options from admin interface changes with
      * existing options in a class.
      *
      * @param string $optionName name of option to update
      * @param mixed[] $currentOptions current options as a named array
      * @param string[] $cbOptionArray array of options that are checkboxes
      * @return mixed[] the updated options
      */
     function save_SerializedOption($optionName,$currentOptions,$cbOptionArray=null) {
        if (!isset($_POST[$optionName])) { return $currentOptions; }
        $optionValue = $_POST[$optionName];

        // Checkbox Pre-processor
        //
        if ($cbOptionArray !== null){
            foreach ($cbOptionArray as $cbname) {
                if (!isset($optionValue[$cbname])) {
                    $optionValue[$cbname] = '0';
                }
            }
        }

        // Merge new options from POST with existing options
        //
        $optionValue = stripslashes_deep(array_merge($currentOptions,$optionValue));
        
        // Make persistent, write back to the wp_options table
        // Only write if something has changed.
        //
        if ($currentOptions != $optionValue) {
            update_option($optionName,$optionValue);
        }

        // Send back the updated options
        //
        return $optionValue;
     }

    /**
     * Check if a URL starts with http://
     *
     * @param type $url
     * @return type
     */
    function url_test($url) {
        return (strtolower(substr($url,0,7))=="http://");
    }

     //------------------------------------------------------------------------
     // DEPRECATED
     //------------------------------------------------------------------------

     /**
      * Do not use, deprecated.
      * 
      * @deprecated 4.0
      * 
      * @var null $addingLocation
      */
     public $addingLocation = null;
     
     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      */
     function create_InputElement() {
         if (!$this->depnotice_create_InputElement) {
            $this->parent->notifications->add_notice(9,$this->plugin->createstring_Deprecated(__FUNCTION__));
            $this->parent->notifications->display();
            $this->depnotice_create_InputElement = true;
         }
     }

}
