<?php
/**
 * Plugin Name: Store Locator Plus : Pro Pack
 * Plugin URI: http://www.charlestonsw.com/product/store-locator-plus/
 * Description: A premium add-on pack for Store Locator Plus that provides more admin power tools for wrangling locations.
 * Version: 3.9.6
 * Author: Charleston Software Associates
 * Author URI: http://charlestonsw.com/
 * Requires at least: 3.3
 * Test up to : 3.5.1
 *
 * Text Domain: csa-slplus
 * Domain Path: /languages/
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// No SLP? Get out...
//
include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
if ( !function_exists('is_plugin_active') ||  !is_plugin_active( 'store-locator-le/store-locator-le.php')) {
    return;
}

/**
 * Store Locator Plus Pro Pack Premium Add-On.
 *
 * @package StoreLocatorPlus\ProPack
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 */
class SLPPro {

    //-------------------------
    // Properties
    //-------------------------
    private $dir;
    private $metadata = null;
    private $slug = null;
    private $url;
    private $adminMode = false;

    /**
     * The main plugin object.
     * 
     * @var SLPPro $plugin
     */
    public  $plugin;

    /**
     * A toggle to let us know the Pro Pack package has been added.
     *
     * TODO: remove on separation from main product
     *
     * @var boolean $packageAdded
     */
    private $packageAdded = false;

    /**
     * The Pro Pack Settings Object
     *
     * @var wpCSL_settings__slplus $settings
     */
    private $ProPack_Settings;

    /**
     * The Pro Pack settings page slug.
     */
    private $ProPack_SettingsSlug = 'slp_propack';


    /**
     * Is the Pro Pack enabled?
     *
     * @var boolean $enabled
     */
    public $enabled = false;

    //------------------------------------------------------
    // METHODS
    //------------------------------------------------------

    /**
     * Make the Pro Pack plugin a singleton.
     *
     * @static
     */
    public static function init() {
        static $instance = false;
        if ( !$instance ) {
            load_plugin_textdomain( 'csa-slplus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            $instance = new SLPPro;
        }
        return $instance;
    }

    /**
     * Constructor
     *
     */
    function SLPPro() {
        $this->url  = plugins_url('',__FILE__);
        $this->dir  = plugin_dir_path(__FILE__);
        $this->slug = plugin_basename(__FILE__);

        // Store Locator Plus invocation complete
        //
        add_action('slp_init_complete'              ,array($this,'slp_init'                     ));

        // Admin / Nav Menus (start of admin stack)
        //
        add_action('slp_admin_menu_starting'        ,array($this,'admin_menu'                   ));
        add_action('slp_admin_init_complete'        ,array($this,'loadJSandCSS'                 ));

        // Filters
        //
        add_filter('slp_add_location_form_footer'   ,array($this,'bulk_upload_form')            );
        add_filter('slp_shortcode_atts'             ,array($this,'extend_main_shortcode')       );
        add_filter('slp_action_boxes'               ,array($this,'manage_locations_actionbar')  );
        add_filter('slp_menu_items'                 ,array($this,'add_menu_items')              ,90);
    }


    /**
     * WordPress admin_init hook for Pro Pack.
     */
    function admin_init(){

        // WordPress Update Checker - if this plugin is active
        //
        if (is_plugin_active($this->slug)) {
            $this->metadata = get_plugin_data(__FILE__, false, false);
            $this->Updates = new SLPlus_Updates(
                    $this->metadata['Version'],
                    $this->plugin->updater_url,
                    $this->slug
                    );
        }

        // Manage Location Fields
        // - tweak the add/edit form
        // - tweak the manage locations column headers
        // - tweak the manage locations column data
        //
        add_filter('slp_edit_location_right_column'         ,array($this,'filter_AddFieldsToEditForm'                   ),11        );
        add_filter('slp_manage_expanded_location_columns'   ,array($this,'filter_AddFieldHeadersToManageLocations'      )           );
        add_filter('slp_column_data'                        ,array($this,'filter_AddFieldDataToManageLocations'         ),90    ,3  );

        // Map Settings Page
        //
        add_filter('slp_map_features_settings'              ,array($this,'filter_MapFeatures_AddSettings'               ),10        );
        add_filter('slp_map_settings_searchform'            ,array($this,'filter_MapSettings_AddTagsBox'                )           );
        add_filter('slp_settings_results_locationinfo'      ,array($this,'filter_MapResults_LocationAddSettings'        )           );
        add_filter('slp_settings_search_features'           ,array($this,'filter_MapSettings_Search_FeaturesAddSettings'),11        );
        add_filter('slp_settings_search_labels'             ,array($this,'filter_MapSettings_SearchLabel_AddSettings'   ),11        );

        // Data Saving
        //
        add_filter('slp_save_map_settings_checkboxes'       ,array($this,'filter_SaveMapCBSettings'                     )           );
        add_filter('slp_save_map_settings_inputs'           ,array($this,'filter_SaveMapInputSettings'                  )           );
    }

    /**
     * This is adding some settings to the Map Settings / Results / Location Info Panel
     */
    function filter_MapResults_LocationAddSettings($HTML) {
        $HTML .=
            $this->plugin->helper->create_SubheadingLabel(__('Pro Pack','csa-slp-em')) .
            $this->plugin->helper->CreateCheckboxDiv(
                '_show_tags',
                __('Show Tags In Output','csa-slplus'),
                __('Show the tags in the location output table and bubble.', 'csa-slplus')
                );

        $HTML .= $this->plugin->helper->CreateCheckboxDiv(
                '_use_email_form',
                __('Use Email Form','csa-slplus'),
                __('Use email form instead of mailto: link when showing email addresses.', 'csa-slplus')
                );

        return $HTML;
    }

    /**
     * Add Pro Pack settings to the search form features section.
     *
     * @param string $HTML incoming HTML
     * @return string augmented HTML
     */
    function filter_MapSettings_Search_FeaturesAddSettings($HTML) {
        $HTML .=
            $this->plugin->helper->create_SubheadingLabel(__('Pro Pack','csa-slp-em')) .
            $this->plugin->helper->create_SimpleMessage('These features will move to the Enhanced Search add-on pack in a future release.').
            $this->plugin->helper->CreateCheckboxDiv(
                '_hide_radius_selections',
                __('Hide radius selection','csa-slplus'),
                __('Hides the radius selection from the user, the default radius will be used.', 'csa-slplus'),
                SLPLUS_PREFIX
                ) .
            $this->plugin->helper->CreateCheckboxDiv(
                '_hide_address_entry',
                __('Hide address entry box','csa-slplus'),
                __('Hides the address entry box from the user.', 'csa-slplus'),
                SLPLUS_PREFIX
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_use_location_sensor',
                __('Use location sensor', 'csa-slplus'),
                __('This turns on the location sensor (GPS) to set the default search address.  This can be slow to load and customers are prompted whether or not to allow location sensing.', 'csa-slplus'),
                SLPLUS_PREFIX
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'sl_use_city_search',
                __('Show City Pulldown','csa-slplus'),
                __('Displays the city pulldown on the search form. It is built from the unique city names in your location list.','csa-slplus'),
                ''
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'sl_use_country_search',
                __('Show Country Pulldown','csa-slplus'),
                __('Displays the country pulldown on the search form. It is built from the unique country names in your location list.','csa-slplus'),
                ''
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                'slplus_show_state_pd',
                __('Show State Pulldown','csa-slplus'),
                __('Displays the state pulldown on the search form. It is built from the unique state names in your location list.','csa-slplus'),
                ''
                ) .

            $this->plugin->helper->CreateCheckboxDiv(
                '_disable_search',
                __('Hide Find Locations button','csa-slplus'),
                __('Remove the "Find Locations" button from the search form.', 'csa-slplus'),
                SLPLUS_PREFIX
                );

        return $HTML;
    }

    /**
     * Add Pro Pack settings to the search label features section.
     *
     * @param string $HTML incoming HTML
     * @return string augmented HTML
     */
    function filter_MapSettings_SearchLabel_AddSettings($HTML) {
        $HTML .=
            $this->plugin->helper->create_SubheadingLabel(__('Pro Pack','csa-slp-em')) .
            $this->plugin->helper->create_SimpleMessage('These features will move to the Enhanced Search add-on pack in a future release.').
            $this->plugin->AdminUI->MapSettings->CreateInputDiv(
                '_search_tag_label',
                __('Tags', 'csa-slplus'),
                __('Search form label to prefix the tag selector.','csa-slplus')
                ) .
            $this->plugin->AdminUI->MapSettings->CreateInputDiv(
                '_state_pd_label',
                __('State Label', 'csa-slplus'),
                __('Search form label to prefix the state selector.','csa-slplus')
                ).
            $this->plugin->AdminUI->MapSettings->CreateInputDiv(
                '_find_button_label',
                __('Find Button', 'csa-slplus'),
                __('The label on the find button, if text mode is selected.','csa-slplus'),
                SLPLUS_PREFIX,
                __('Find Locations','csa-slplus')
                );
        return $HTML;
    }

    /**
     * Save our Pro Pack checkboxes from the map settings page.
     *
     * @param string[] $cbArray array of checkbox names to be saved
     * @return string[] augmented list of inputs to save
     */
    function filter_SaveMapCBSettings($cbArray) {
        return array_merge($cbArray,
                array(
                        SLPLUS_PREFIX.'_show_tags'                  ,
                        SLPLUS_PREFIX.'_use_email_form'             ,
                        SLPLUS_PREFIX.'_hide_radius_selections'     ,
                        SLPLUS_PREFIX.'_hide_address_entry'         ,
                        SLPLUS_PREFIX.'_use_location_sensor'        ,
                        'sl_use_city_search'                        ,
                        'sl_use_country_search'                     ,
                        'slplus_show_state_pd'                      ,
                        SLPLUS_PREFIX.'_disable_search'            
                    )
                );
    }

    /**
     * Add the Pro Pack input settings to be saved on the map settings page.
     *
     * @param string[] $inArray names of inputs already to be saved
     * @return string[] modified array with our Pro Pack inputs added.
     */
    function filter_SaveMapInputSettings($inArray) {
        return array_merge($inArray,
                array(
                        SLPLUS_PREFIX.'_search_tag_label'       ,
                        SLPLUS_PREFIX.'_state_pd_label'         ,
                        SLPLUS_PREFIX.'_find_button_label'      ,
                    )
                );
    }

    /**
     * WordPress admin_menu hook for Tagalong.
     */
    function admin_menu(){
        $this->adminMode = true;
        if (!$this->enabled) { return ''; }

        $slugPrefix = 'store-locator-plus_page_';

       // Admin Styles
        //
        add_action(
                'admin_print_styles-' . $slugPrefix .$this->ProPack_SettingsSlug,
                array($this,'action_EnqueueAdmin_CSS')
                );

        // Admin Actions
        //
        add_action('admin_init'             ,array($this,'admin_init'));
    }

    /**
     * Do this stuff after SLP has started up.
     */
    function slp_init() {
        if (!$this->setPlugin()) { return; }
        $this->plugin->register_addon($this->slug);

        add_filter('slp_search_form_divs',array($this,'filter_SearchForm_AddCityPD'     ),10);
        add_filter('slp_search_form_divs',array($this,'filter_SearchForm_AddStatePD'    ),20);
        add_filter('slp_search_form_divs',array($this,'filter_SearchForm_AddCountryPD'  ),30);
        add_filter('slp_search_form_divs',array($this,'filter_SearchForm_AddTagSearch'  ),40);
    }


    /**
     * Add the Pro Pack menu
     *
     * @param mixed[] $menuItems
     * @return mixed[]
     */
    function add_menu_items($menuItems) {
        if (!$this->enabled) { return $menuItems; }
        return array_merge(
                    $menuItems,
                    array(
                        array(
                            'label'     => __('Pro Pack','csa-slp-propack'),
                            'slug'      => 'slp_propack',
                            'class'     => $this,
                            'function'  => 'renderPage_ProPack_Settings'
                        ),
                        array(
                            'label' => __('Reports','csa-slplus'),
                            'url'   => SLPLUS_PLUGINDIR.'reporting.php'
                        )
                    )
                );
    }

    /**
     * Add the Pro Pack package to the main plugin.
     * 
     * @return null
     */
    function add_package() {
        if ($this->packageAdded) { return; }
        $this->plugin->ProPack = $this;

        // Setup metadata
        //
        $myPurl = 'http://www.charlestonsw.com/product/store-locator-plus/';
        $this->plugin->license->add_licensed_package(
                array(
                    'name'              => 'Pro Pack',
                    'help_text'         => 'A variety of enhancements are provided with this package.  ' .
                                           'See the <a href="'.$myPurl.'" target="newinfo">product page</a> for details.  If you purchased this add-on ' .
                                           'come back to this page to enter the license key to activate the new features.',
                    'sku'               => 'SLPLUS-PRO',
                    'paypal_button_id'  => '59YT3GAJ7W922',
                    'paypal_upgrade_button_id' => '59YT3GAJ7W922',
                    'purchase_url'      => $myPurl
                )
            );

        $this->packageAdded = true;
        $this->enabled = $this->plugin->license->packages['Pro Pack']->isenabled;
    }

    /**
     * Convert an array to CSV.
     *
     * @param array[] $data
     * @return string
     */
    static function array_to_CSV($data)
    {
        $outstream = fopen("php://temp", 'r+');
        fputcsv($outstream, $data, ',', '"');
        rewind($outstream);
        $csv = fgets($outstream);
        fclose($outstream);
        return $csv;
    }

    /**
     * Debug for action hooks.
     * 
     * @param type $tagname
     * @param type $parm1
     * @param type $parm2
     */
    function debug($tagname,$parm1=null,$parm2=null) {
        print "$tagname<br/>\n".
              "<pre>".print_r($parm1,true)."</pre>".
              "<pre>".print_r($parm2,true)."</pre>"
                ;
        die($this->slug . ' debug hooked.');
    }


    /**
     * Set the plugin property to point to the primary plugin object.
     *
     * Returns false if we can't get to the main plugin object or
     * PRO PACK IS NOT LICENSED
     *
     * TODO: REMOVE the Pro Pack license check when this becomes an independent plugin.
     * TODO: this->add_package() as well.
     *
     * @global wpCSL_plugin__slplus $this->plugin
     * @return boolean true if plugin property is valid
     */
    function setPlugin() {
        if (!isset($this->plugin) || ($this->plugin == null)) {
            global $slplus_plugin;
            $this->plugin = $slplus_plugin;
        }

        // We only need this while we are doing licensed packages
        //
        $this->add_package();

        return (
            isset($this->plugin)    &&
            ($this->plugin != null) &&
            $this->enabled
            );
    }

    /**
     * Add the bulk upload form to add locations.
     *
     * @param string $HTML - html of the existing add locations form suffix, typcially ''
     * @return string - complete HTML to put in the footer.
     */
    function bulk_upload_form($HTML) {
        if (!$this->enabled) { return ''; }
        return ( $HTML .
                    '<div class="slp_bulk_upload_div section_column">' .
                    '<h2>'.__('Bulk Upload', 'csa-slplus').'</h2>'.
                    '<div class="section_description">'.
                    '<p>'.
                        sprintf(__('See the %s for more details on the import format.','csa-slplus'),
                                '<a href="http://www.charlestonsw.com/support/documentation/store-locator-plus/pro-pack-add-on/bulk-data-import/">' .
                                __('online documentation','csa-slplus') .
                                '</a>'
                                ).
                    '</p>' .
                    '<input type="file" name="csvfile" value="" id="bulk_file" size="50">' .
                    $this->plugin->helper->CreateCheckboxDiv(
                        '-bulk_skip_first_line',
                        __('Skip First Line','csa-slplus'),
                        __('Skip the first line of the import file.','csa-slplus'),
                        SLPLUS_PREFIX,
                        false,
                        0
                    ).
                    $this->plugin->helper->CreateCheckboxDiv(
                        '-bulk_skip_duplicates',
                        __('Skip Duplicates','csa-slplus'),
                        __('Checks the name, street, city, state, zip, and country for duplicate entries.  Skips if already in database.  Takes longer to load locations.','csa-slplus'),
                        SLPLUS_PREFIX,
                        false,
                        0
                    ).

                    "<div class='form_entry'><input type='submit' value='".__('Upload Locations', 'csa-slplus')."' class='button-primary'></div>".
                    '</div>' .
                    '</div>'
                );
    }

    /**
     * Process the bulk upload files.
     *
     */
    function bulk_upload_processing() {
        if (!$this->enabled) { return ''; }
        
        // Reset the notification message to get a clean message stack.
        //
        $this->plugin->notifications->delete_all_notices();

        add_filter('upload_mimes', array($this,'custom_upload_mimes'));
        $this->plugin->helper->SaveCheckboxToDB('bulk_skip_first_line');
        $this->plugin->helper->SaveCheckboxToDB('bulk_skip_duplicates');

        // Get the type of the uploaded file. This is returned as "type/extension"
        $arr_file_type = wp_check_filetype(basename($_FILES['csvfile']['name']));

        // File is CSV
        //
        if ($arr_file_type['type'] == 'text/csv') {

            // Save the file to disk
            //
            $updir = wp_upload_dir();
            $updir = $updir['basedir'].'/slplus_csv';
            if(!is_dir($updir)) {
                mkdir($updir,0755);
            }

            // If we can put in the uploads directory, continue...
            //
            if (move_uploaded_file($_FILES['csvfile']['tmp_name'],$updir.'/'.$_FILES['csvfile']['name'])) {
                $reccount = 0;
                $adle_setting = ini_get('auto_detect_line_endings');
                ini_set('auto_detect_line_endings', true);

                // If we can open the file...
                //
                if (($handle = fopen($updir.'/'.$_FILES['csvfile']['name'], "r")) !== FALSE) {

                    // Array #s for Fields
                    //'sl_store'   [ 0],'sl_address'  [ 1],'sl_address2'[ 2],'sl_city'       [ 3],'sl_state'[ 4],
                    //'sl_zip'     [ 5],'sl_country'  [ 6],'sl_tags'    [ 7],'sl_description'[ 8],'sl_url'  [ 9],
                    //'sl_hours'   [10],'sl_phone'    [11],'sl_email'   [12],'sl_image'      [13],'sl_fax'  [14],
                    //'sl_latitude'[15],'sl_longitude'[16],'sl_private' [17],'sl_neat_title' [18]
                    //
                    $fldNames = array(
                            'sl_store','sl_address','sl_address2','sl_city','sl_state',
                            'sl_zip','sl_country','sl_tags','sl_description','sl_url',
                            'sl_hours','sl_phone','sl_email','sl_image','sl_fax',
                            'sl_latitude','sl_longitude','sl_private','sl_neat_title'
                        );
                    $maxcols = count($fldNames);
                    $skippedFirst = false;
                    $skipDupes    = ($_POST['csl-slplus-bulk_skip_duplicates'] == 1);
                    $dupeCount    = 0;

                    // Loop through all records
                    //
                    while (($data = fgetcsv($handle)) !== FALSE) {

                        // Skip First Line
                        //
                        if (!$skippedFirst &&
                            ($_POST['csl-slplus-bulk_skip_first_line'] == 1)
                            ){
                            $skippedFirst = true;
                            continue;
                        }

                        $num = count($data);
                        $locationData = array();
                        if ($num <= $maxcols) {
                            for ($fldno=0; $fldno < $num; $fldno++) {
                                $locationData[$fldNames[$fldno]] = stripslashes($this->plugin->AdminUI->slp_escape($data[$fldno]));
                            }
                            $resultOfAdd = $this->plugin->AdminUI->add_this_addy(
                                    $locationData,
                                    $skipDupes,
                                    stripslashes($this->plugin->AdminUI->slp_escape($data[0])),
                                    (is_numeric($data[15]) && is_numeric($data[16]))
                                    );
                            sleep(0.5);
                            if ($resultOfAdd == 'duplicate') { $dupeCount++; }
                            $reccount++;
                        } else {
                             print "<div class='updated fade'>".
                                __('The CSV file has too many fields.',
                                    SLPLUS_PREFIX
                                    );
                             print ' ';
                             printf(__('Got %d expected less than %d.', SLPLUS_PREFIX),
                                $num,$maxcols);
                             print '</div>';
                        }
                    }
                    fclose($handle);
                }
                ini_set('auto_detect_line_endings', $adle_setting);

                // Tell them how many locations were added
                //
                if ($reccount > 0) {
                    print "<div class='updated fade'>".
                            sprintf("%d",$reccount) ." " .
                            __("locations processed.",SLPLUS_PREFIX) .
                            '</div>';
                }
                if ($dupeCount > 0) {
                    print "<div class='updated fade'>".
                            sprintf("%d",$dupeCount) ." " .
                            __("duplicates not added.",SLPLUS_PREFIX) .
                            '</div>';
                }

            // Could not save
            } else {
                print "<div class='updated fade'>".
                    __("File could not be saved, check the plugin directory permissions:",SLPLUS_PREFIX) .
                        "<br/>" . $updir.
                    '.</div>';
            }

        // Not CSV Format Warning
        } else {
            print "<div class='updated fade'>".
                __("Uploaded file needs to be in CSV format.",SLPLUS_PREFIX) .
                " Type was " . $arr_file_type['type'] .
                '.</div>';
        }
    }

    /**
     * Generate the HTML to build the city pulldown UI element.
     * 
     * @return string
     */
    private function create_CityPD() {
        $pdOptions = '';
        $cs_array=$this->plugin->db->get_results(
            "SELECT CONCAT(TRIM(sl_city), ', ', TRIM(sl_state)) as city_state " .
                "FROM ".$this->plugin->db->prefix."store_locator " .
                "WHERE sl_city<>'' AND sl_state<>'' AND sl_latitude<>'' AND sl_longitude<>'' " .
                "GROUP BY city_state " .
                "ORDER BY city_state ASC",
            ARRAY_A);
        if ($cs_array) {
            foreach($cs_array as $sl_value) {
                $pdOptions.="<option value='$sl_value[city_state]'>$sl_value[city_state]</option>";
            }
        }
        return $pdOptions;
    }

    /**
     * Create the country pulldown list, mark the checked item.
     * 
     * @return string
     */
    private function create_CountryPD() {
        $myOptions = '';
        $cs_array=$this->plugin->db->get_results(
            "SELECT TRIM(sl_country) as country " .
                "FROM ".$this->plugin->db->prefix."store_locator " .
                "WHERE sl_country<>'' " .
                    "AND sl_latitude<>'' AND sl_longitude<>'' " .
                "GROUP BY country " .
                "ORDER BY country ASC",
            ARRAY_A);
        if ($cs_array) {
            foreach($cs_array as $sl_value) {
              $myOptions.="<option value='{$sl_value['country']}'>{$sl_value['country']}</option>";
            }
        }
        return $myOptions;
    }

    /**
     * Create the state pulldown list, mark the checked item.
     *
     * @return string
     */
    function create_StatePD() {
        $myOptions = '';
        $cs_array=$this->plugin->db->get_results(
            "SELECT TRIM(sl_state) as state " .
                "FROM ".$this->plugin->db->prefix."store_locator " .
                "WHERE sl_state<>'' " .
                    "AND sl_latitude<>'' AND sl_longitude<>'' " .
                "GROUP BY state " .
                "ORDER BY state ASC",
            ARRAY_A);

        // If we have country data show it in the pulldown
        //
        if ($cs_array) {
            foreach($cs_array as $sl_value) {
              $myOptions.=
                "<option value='$sl_value[state]'>" .
                $sl_value['state']."</option>";
            }
        }
        return $myOptions;
    }

    /**
     * Allows WordPress to process csv file types
     *
     * @param array $existing_mimes
     * @return string
     */
    function custom_upload_mimes ( $existing_mimes=array() ) {
        $existing_mimes['csv'] = 'text/csv';
        return $existing_mimes;
    }


    /**
     * Extends the main SLP shortcode approved attributes list, setting defaults.
     * 
     * This will extend the approved shortcode attributes to include the items listed.
     * The array key is the attribute name, the value is the default if the attribute is not set.
     * 
     * @param array $valid_atts - current list of approved attributes
     */
    function extend_main_shortcode($valid_atts) {
        if (!$this->enabled) { return array(); }

        return array_merge(
                array(
                    'endicon'          => null,
                    'homeicon'         => null,
                    'only_with_tag'    => null,
                    'tags_for_pulldown'=> null,
                    'theme'            => null,
                    ),
                $valid_atts
            );
    }

    /**
     * Enqueue the style sheet when needed.
     */
    function action_EnqueueAdmin_CSS() {
        wp_enqueue_style($this->ProPack_SettingsSlug.'_style');
        wp_enqueue_style($this->plugin->AdminUI->styleHandle);
    }

    /**
     * Add extra fields that show in results output to the edit form.
     *
     * SLP Filter: slp_edit_location_right_column
     *
     * @param string $theForm the original HTML form for the manage locations edit (right side)
     * @return string the modified HTML form
     */
    function filter_AddFieldsToEditForm($theHTML) {
        $addform = $this->plugin->AdminUI->addingLocation;

        $theHTML .=
            '<div id="slp_pro_fields" class="slp_editform_section">'.
            $this->plugin->helper->create_SubheadingLabel(__('Pro Pack','csa-slplus'))
            ;

        // Add or Edit
        //
        $theHTML .=
                '<br/>'.
                "<input ".
                    "id='tags-edit' "   .
                    "name='tags-"       .$this->plugin->currentLocation->id     ."' ".
                    "value='".($addform?'':$this->plugin->currentLocation->tags)."' ".
                    '>'.
                '<small>'.
                   __("Tags (seperate with commas)", 'csa-slplus').
                '</small>'
                ;
        
        // Edit Location Only
        //
        if ($this->plugin->AdminUI->addingLocation === false) {
            $theHTML .=
                '<br/>'.
                "<input ".
                    "id='latitude-edit' ".
                    "name='latitude-".$this->plugin->currentLocation->id."' ".
                    "value='".$this->plugin->currentLocation->latitude  ."' ".
                    '>'.
                '<small>'.
                    __('Latitude (N/S)', 'csa-slplus').
                '</small>'.
                '<br/>'.
                '<input ' .
                    "id='longitude-edit' ".
                    "name='longitude-".$this->plugin->currentLocation->id."' ".
                    "value='".$this->plugin->currentLocation->longitude  ."' ".
                    '>'.
                '<small>'.
                    __('Longitude (E/W)', 'csa-slplus').
                '</small>'
                ;
        }
        
        $theHTML .=
            '</div>'
            ;

        return $theHTML;
    }

    /**
     * Add map feature settings.
     *
     * @param string $html starting html for map feature settings
     * @return string modified HTML
     */
    function filter_MapFeatures_AddSettings($html) {
        return
            $html .
            $this->plugin->helper->create_SubheadingLabel(__('Pro Pack','csa-slplus')).
            $this->plugin->helper->create_SimpleMessage('These features will move to the Enhanced Map add-on pack in a future release.').
            $this->plugin->AdminUI->MapSettings->CreateInputDiv(
                'sl_starting_image',
                __('Starting Image','csa-slplus'),
                __('If set, this image will be displayed until a search is performed.  Enter the full URL for the image.','csa-slplus'),
                ''
                ) .
            $this->plugin->helper->CreateCheckboxDiv(
                '_disable_initialdirectory',
                __('Disable Initial Directory','csa-slplus'),
                __('Do not display the listings under the map when "immediately show locations" is checked.', 'csa-slplus')
                )
            ;
    }

    /**
     * Add tags box to the search form section of map settings.
     *
     * @param string $html starting html
     * @return string modified HTML
     */
    function filter_MapSettings_AddTagsBox($html) {
        $html .=
            "<div class='section_column'>" .
            '<h2>'.__('Pro Pack Tags', 'csa-slplus').'</h2>' .
            '<div class="section_column_content">' .
            $this->plugin->helper->CreateCheckboxDiv(
                '_show_tag_search',
                __('Tag Input','csa-slplus'),
                __('Show the tag entry box on the search form.', 'csa-slplus')
                ).
            $this->plugin->AdminUI->MapSettings->CreateInputDiv(
                    '_tag_search_selections',
                    __('Preselected Tag Searches', 'csa-slplus'),
                    __("Enter a comma (,) separated list of tags to show in the search pulldown, mark the default selection with parenthesis '( )'. This is a default setting that can be overriden on each page within the shortcode.",'csa-slplus')
                    ).
            $this->plugin->helper->CreateCheckboxDiv(
                '_show_tag_any',
                __('Add "any" to tags pulldown','csa-slplus'),
                __('Add an "any" selection on the tag pulldown list thus allowing the user to show all locations in the area, not just those matching a selected tag.', 'csa-slplus')
                )
            ;

            ob_start();
            do_action('slp_add_search_form_tag_setting');
            $html .= ob_get_clean() .
                    '</div></div>';
            
            return $html;
    }

    /**
     * Add tag search to search form.
     */
    function filter_SearchForm_AddTagSearch($HTML) {
        if ((get_option(SLPLUS_PREFIX.'_show_tag_search',0) ==1) || isset($this->plugin->data['only_with_tag'])) {
            $newHTML .=
                  "<div id='search_by_tag' class='search_item' ".(isset($this->plugin->data['only_with_tag'])?"style='display:none;'":'').">".
                      "<label for='tag_to_search_for'>".
                          get_option(SLPLUS_PREFIX.'_search_tag_label','').
                       "</label>"
                    ;


            // Tag selections
            // only_with_tag - don't use them
            // otherwise get it from the option setting
            //
            if (isset($this->plugin->data['only_with_tag'])) {
                $tag_selections = '';
            } else {
                if (isset($this->plugin->data['tags_for_pulldown'])) {
                    $tag_selections = $this->plugin->data['tags_for_pulldown'];
                } else {
                    $tag_selections = get_option(SLPLUS_PREFIX.'_tag_search_selections','');
                }
            }

            // No pre-selected tags, use input box
            //
            if ($tag_selections == '') {
                $newHTML .= "<input type='". (isset($this->plugin->data['only_with_tag']) ? 'hidden' : 'text') . "' ".
                        "id='tag_to_search_for' size='50' " .
                        "value='" . (isset($this->plugin->data['only_with_tag']) ? $this->plugin->data['only_with_tag'] : '') . "' ".
                        "/>";

            // Pulldown for pre-selected list
            //
            } else {
                ob_start();
                $tag_selections = explode(",", $tag_selections);
                add_action('slp_render_search_form_tag_list',array($this->plugin->UI,'slp_render_search_form_tag_list'),10,2);
                do_action('slp_render_search_form_tag_list',$tag_selections,(get_option(SLPLUS_PREFIX.'_show_tag_any')==1));
                $newHTML .= ob_get_clean();
            }
            $newHTML .= '</div>';
            return $HTML.$newHTML;
        }
        return $HTML;
    }

    /**
     * Add City pulldown to search form.
     * 
     * @param string $HTML the initial pulldown HTML, typically empty.
     */
    function filter_SearchForm_AddCityPD($HTML) {
        if (!$this->enabled) { return $HTML; }
        if (get_option('sl_use_city_search',0)=='0') { return $HTML; }

        $onChange = 'aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}';
        $HTML .=
            "<div id='addy_in_city'>".
                "<select id='addressInput2' onchange='$onChange'>".
                    "<option value=''>".
                        get_option(SLPLUS_PREFIX.'_search_by_city_pd_label',__('--Search By City--','csa-slplus')).
                     '</option>'.
                    $this->create_CityPD().
                '</select>'.
            '</div>'
            ;
        return $HTML;
    }

    /**
     * Add Country pulldown to search form.
     *
     * @param string $HTML the initial pulldown HTML, typically empty.
     */
    function filter_SearchForm_AddCountryPD($HTML) {
        if (!$this->enabled) { return $HTML; }
        if (get_option('sl_use_country_search',0)==0) { return $HTML; }

        $onChange = 'aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}';
        $HTML .=
            "<div id='addy_in_country'>".
                "<select id='addressInput3' onchange='$onChange'>".
                    "<option value=''>".
                        get_option(SLPLUS_PREFIX.'_search_by_country_pd_label',__('--Search By Country--','csa-slplus')).
                     '</option>'.
                    $this->create_CountryPD().
                '</select>'.
            '</div>'
            ;

        return $HTML;
    }

    /**
     * Add State pulldown to search form.
     *
     * @param string $HTML the initial pulldown HTML, typically empty.
     */
    function filter_SearchForm_AddStatePD($HTML) {
        if (!$this->enabled) { return $HTML; }
        if (get_option('slplus_show_state_pd',0)==0) { return $HTML; }

        $onChange = 'aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}';
        $HTML .=
            "<div id='addy_in_state'>".
                "<label for='addressInputState'>".
                    get_option(SLPLUS_PREFIX.'_state_pd_label','').
                '</label>'.
                "<select id='addressInputState' onchange='$onChange'>".
                    "<option value=''>".
                        get_option(SLPLUS_PREFIX.'_search_by_state_pd_label',__('--Search By State--','csa-slplus')).
                     '</option>'.
                    $this->create_StatePD().
                '</select>'.
            '</div>'
            ;

        return $HTML;
    }

    /**
     * Add the images column header to the manage locations table.
     *
     * SLP Filter: slp_manage_location_columns
     *
     * @param mixed[] $currentCols column name + column label for existing items
     * @return mixed[] column name + column labels, extended with our extra fields data
     */
    function filter_AddFieldHeadersToManageLocations($currentCols) {
        $this->plugin->debugMP('pr','SLP Pro Column Headers',$currentCols,__FILE__,__LINE__);
        return array_merge($currentCols,
                array(
                    'sl_tags'       => __('Tags'     ,'csa-slplus'),
                )
            );
    }

    /**
     * Render the extra fields on the manage location table.
     *
     * SLP Filter: slp_column_data
     *
     * @param string $theData  - the option_value field data from the database
     * @param string $theField - the name of the field from the database (should be sl_option_value)
     * @param string $theLabel - the column label for this column (should be 'Categories')
     * @return type
     */
    function filter_AddFieldDataToManageLocations($theData,$theField,$theLabel) {
        if (
            ($theField === 'sl_tags') &&
            ($theLabel === __('Tags'        ,'csa-slplus'))
           ) {
            $theData =($this->plugin->currentLocation->tags!='')?
                $this->plugin->currentLocation->tags :
                "" ;
        }
        return $theData;
    }

    /**
     * Load the JavaScript and CSS on ony our pages.
     */
    function loadJSandCSS() {
        if (!$this->enabled) { return; }

        // Reporting
        //
        add_action(
               'admin_print_styles-store-locator-le/reporting.php',
                array($this->plugin->AdminUI,'enqueue_admin_stylesheet')
                );
        add_action(
                'admin_print_scripts-store-locator-le/reporting.php',
                array($this,'enqueueReportingJS')
                );
    }

    /** 
     * Process incoming AJAX request to download the CSV file.
     */
    static function downloadReportCSV() {
        // CSV Header
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=slplus_' . $_REQUEST['filename'] . '.csv' );
        header( 'Content-Type: application/csv;');
        header( 'Pragma: no-cache');
        header( 'Expires: 0');

        // Setup our processing vars
        //
        global $wpdb;
        $query = $_REQUEST['query'];

        // All records - revise query
        //
        if (isset($_REQUEST['all']) && ($_REQUEST['all'] == 'true')) {
            $query = preg_replace('/\s+LIMIT \d+(\s+|$)/','',$query);
        }

        $slpQueryTable     = $wpdb->prefix . 'slp_rep_query';
        $slpResultsTable   = $wpdb->prefix . 'slp_rep_query_results';
        $slpLocationsTable = $wpdb->prefix . 'store_locator';

        $expr = "/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
        $parts = preg_split($expr, trim(html_entity_decode($query, ENT_QUOTES)));
        $parts = preg_replace("/^\"(.*)\"$/","$1",$parts);

        // Return the address in CSV format from the reports
        //
        if ($parts[0] === 'addr') {
            $slpReportStartDate = $parts[1];
            $slpReportEndDate = $parts[2];

            // Only Digits Here Please
            //
            $slpReportLimit = preg_replace('/[^0-9]/', '', $parts[3]);

            $query =
            "SELECT slp_repq_address, count(*)  as QueryCount FROM $slpQueryTable " .
                "WHERE slp_repq_time > %s AND " .
                "      slp_repq_time <= %s " .
                "GROUP BY slp_repq_address ".
                "ORDER BY QueryCount DESC " .
                "LIMIT %d"
                ;
            $queryParms = array(
                $slpReportStartDate,
                $slpReportEndDate,
                $slpReportLimit
                );

        // Return the locations searches in CSV format from the reports
        //
        } else if ($parts[0] === 'top') {
            $slpReportStartDate = $parts[1];
            $slpReportEndDate = $parts[2];

            // Only Digits Here Please
            //
            $slpReportLimit = preg_replace('/[^0-9]/', '', $parts[3]);

            $query =
            "SELECT sl_store,sl_city,sl_state, sl_zip, sl_tags, count(*) as ResultCount " .
                "FROM $slpResultsTable res ".
                    "LEFT JOIN $slpLocationsTable sl ".
                        "ON (res.sl_id = sl.sl_id) ".
                    "LEFT JOIN $slpQueryTable qry ".
                        "ON (res.slp_repq_id = qry.slp_repq_id) ".
                    "WHERE slp_repq_time > %s AND slp_repq_time <= %s ".
                "GROUP BY sl_store,sl_city,sl_state,sl_zip,sl_tags ".
                "ORDER BY ResultCount DESC ".
                "LIMIT %d"
                ;
            $queryParms = array(
                $slpReportStartDate,
                $slpReportEndDate,
                $slpReportLimit
                );

        // Not Locations (top) or addresses entered in search
        // short circuit...
        //
        } else {
            die(__("Cheatin' huh+",'csa-slplus'));
        }

        // No parms array?  GTFO
        //
        if (!is_array($queryParms)) {
            die(__("Cheatin' huh!",'csa-slplus'));
        }


        // Run the query & output the data in a CSV
        $thisDataset = $wpdb->get_results($wpdb->prepare($query,$queryParms),ARRAY_N);


        // Sorting
        // The sort comes in based on the display table column order which
        // matches the query output column order listed here.
        //
        // It is a paired array, first number is the column number (zero offset)
        // second number is the sort order [0=ascending, 1=descending]
        //
        // The sort needs to happen AFTER the select.
        //

        // Get our sort array
        //
        $thisSort = explode(',',$_REQUEST['sort']);

        // Build our array_multisort command and our sort index/sort order arrays
        // we will need this later for helping do a multi-dimensional sort
        //
        $sob = 'sort';
        $amsstring='';
        $sortarrayindex = 0;
        foreach($thisSort as $sl_value) {
            if ($sob == 'sort') {
                $sort[] = $sl_value;
                $amsstring .= '$s[' . $sortarrayindex++ . '], ';
                $sob='order';
            } else {
                $order[] = $sl_value;
                $amsstring .= ($sl_value == 0) ? 'SORT_ASC, ' : 'SORT_DESC, ';
                $sob='sort';
            }
        }
        $amsstring .= '$thisDataset';

        // Now that we have our sort arrays and commands,
        // build the indexes that will be used to do the
        // multi-dimensional sort
        //
        foreach ($thisDataset as $key => $row) {
            $sortarrayindex = 0;
            foreach ($sort as $column) {
                $s[$sortarrayindex++][$key] = $row[$column];
            }
        }

        // Now do the multidimensional sort
        //
        // This will sort using the first array ($s[0] we built in the above 2 steps)
        // to determine what order to put the "records" (the outter array $thisDataSet)
        // into.
        //
        // If there are secondary arrays ($s[1..n] as built above) we then further
        // refine the sort using these secondary arrays.  Think of them as the 2nd
        // through nth columns in a multi-column sort on a spreadsheet.
        //
        // This exactly mimics the jQuery sorts that manage our tables on the HTML
        // page.
        //

        //array_multisort($amsstring);
        // Output the sorted CSV strings
        // This simply iterates through our newly sorted array of records we
        // got from the DB and writes them out in CSV format for download.
        //
        foreach ($thisDataset as $thisDatapoint) {
            print SLPPro::array_to_CSV($thisDatapoint);
        }

        // Get outta here
        die();
    }

    /**
     * Enqueue the reporting JavaScript.
     */
    function enqueueReportingJS() {
        wp_enqueue_script('jquery_tablesorter', SLPLUS_COREURL  .'js/jquery.tablesorter.js', array('jquery'             ));
        wp_enqueue_script('slp_reporting_js'  , SLPLUS_PLUGINURL.'/slp-pro/reporting.js'   , array('jquery_tablesorter' ));

        // Lets get some variables into our script
        //
        $scriptData = array(
            'plugin_url'        => SLPLUS_PLUGINURL,
            'core_url'          => SLPLUS_COREURL,
            );
        wp_localize_script('slp_reporting_js','slp_pro',$scriptData);
    }

     /**
      * Add the create pages button to box "C" on the action bar
      *
      * @param array $actionBoxes - the existing action boxes, 'A'.. each named array element is an array of HTML strings
      * @return string
      */
     function manage_locations_actionbar($actionBoxes) {
            if (!$this->enabled) { return $actionBoxes; }
            $actionBoxes['A'][] =
                   '<p class="centerbutton">' .
                       '<a class="like-a-button" href="#" ' .
                           'onclick="doAction(\'recode\',\''.__('Recode selected?',SLPLUS_PREFIX).'\');" '.
                           'name="recode_selected">'.__("Recode Selected", SLPLUS_PREFIX).
                       '</a>' .
                    '</p>'
                    ;
            $actionBoxes ['B'][] =
                '<div id="tag_actions">' .
                        '<a  class="like-a-button" href="#" name="tag_selected"    '.
                            'onclick="doAction(\'add_tag\',\''.__('Tag selected?',SLPLUS_PREFIX).'\');">'.
                            __('Tag Selected', SLPLUS_PREFIX).
                        '</a>'.
                        '<a  class="like-a-button" href="#" name="untag_selected"  ' .
                            'onclick="doAction(\'remove_tag\',\''. __('Remove tag from selected?',SLPLUS_PREFIX).'\');">'.
                            __('Untag Selected', SLPLUS_PREFIX).
                        '</a>'.
                '</div>' .
                '<div id="tagentry">'.
                    '<label for="sl_tags">'.__('Tags', SLPLUS_PREFIX).'</label><input name="sl_tags">'.
                '</div>'
                ;

            $actionBoxes ['P'][] =
                    '<p class="centerbutton">' .
                        '<a class="like-a-button" href="#"  name="show_uncoded" '.
                            'onclick="doAction(\'show_uncoded\',\'\');" >' .
                             __('Show Uncoded', 'csa-slplus') .
                         '</a>' .
                    '</p>' .
                    '<p class="centerbutton">' .
                        '<a class="like-a-button" href="#" name="show_all" '.
                              'onclick="doAction(\'show_all\',\'\');" >' .
                              __('Show All', 'csa-slplus') .
                        '</a>'.
                    '</p>'
                ;

            return $actionBoxes;
    }

    /**
     * Render the Pro Pack settings page.
     */
    function renderPage_ProPack_Settings() {
        if (!$this->enabled) { return __('Pro Pack has not been activated.','csa-slplus'); }

        // If we are updating settings...
        //
        if (isset($_REQUEST['action']) && ($_REQUEST['action']==='update')) {
            $this->updateSettings();
        }

        // Setup and render settings page
        //
        $this->ProPack_Settings = new wpCSL_settings__slplus(
            array(
                    'no_license'        => true,
                    'prefix'            => $this->plugin->prefix,
                    'css_prefix'        => $this->plugin->prefix,
                    'url'               => $this->plugin->url,
                    'name'              => $this->plugin->name . ' - Pro Pack',
                    'plugin_url'        => $this->plugin->plugin_url,
                    'render_csl_blocks' => true,
                    'form_action'       => admin_url().'admin.php?page='.$this->ProPack_SettingsSlug
                )
         );

        //-------------------------
        // Navbar Section
        //-------------------------
        $this->ProPack_Settings->add_section(
            array(
                'name'          => 'Navigation',
                'div_id'        => 'slplus_navbar',
                'description'   => $this->plugin->AdminUI->create_Navbar(),
                'is_topmenu'    => true,
                'auto'          => false,
                'headerbar'     => false
            )
        );

        //-------------------------
        // General Settings
        //-------------------------
        $sectName = __('General Settings','csa-slplus');
        $this->ProPack_Settings->add_section(
            array(
                    'name'          => $sectName,
                    'description' =>
                        __('These settings affect how the Pro Pack add-on behaves. ', 'csa-slplus') .
                        '<span style="float:right;">(<a href="#" onClick="'.
                        'jQuery.post(ajaxurl,{action: \'license_reset_propack\'},function(response){alert(response);});'.
                        '">'.__('Delete license','csa-slplus').'</a>)</span>',
                    'auto'          => true
                )
         );

        $this->ProPack_Settings->add_item(
            $sectName,
            __('Enable reporting', 'csa-slplus'),
            'reporting_enabled',
            'checkbox',
            false,
            __('Enables tracking of searches and returned results.  The added overhead ' .
            'can increase how long it takes to return location search results.', 'csa-slplus')
        );

        // Custom CSS Field
        //
        $this->ProPack_Settings->add_item(
                $sectName,
                __('Custom CSS','csa-slplus'),
                'custom_css',
                'textarea',
                false,
                __('Enter your custom CSS, preferably for SLPLUS styling only but it can be used for any page element as this will go in your page header.','csa-slplus')
                );

        //------------------------------------------
        // RENDER
        //------------------------------------------
        $this->ProPack_Settings->render_settings_page();
    }

    /**
     * Handle updating Pro Pack settings on the custom settings page.
     */
    function updateSettings() {
        if (!isset($_REQUEST['page']) || ($_REQUEST['page']!=$this->ProPack_SettingsSlug)) { return; }
        if (!isset($_REQUEST['_wpnonce'])) { return; }

        // Save Checkboxes
        //
        $BoxesToHit = array(
            'reporting_enabled',
        );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SaveCheckBoxToDB($JustAnotherBox);
        }

        // Save Inputs
        //
        $BoxesToHit = array(
            SLPLUS_PREFIX.'-custom_css',
        );
        foreach ($BoxesToHit as $JustAnotherBox) {
            $this->plugin->helper->SavePostToOptionsTable($JustAnotherBox);
        }

        $this->plugin->debugMP('pr','Pro.updateSettings()',$_REQUEST,__FILE__,__LINE__);
    }
}

// Make a Pro Pack singleton
//
add_action( 'init', array( 'SLPPro', 'init' ) );

// AJAX Listeners
//
add_action('wp_ajax_slp_download_report_csv', array('SLPPro','downloadReportCSV'));

// Dad. Husband. Rum Lover. Code Geek. Not necessarily in that order.