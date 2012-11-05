<?php

/***********************************************************************
* Class: SLPlus_AdminUI
*
* The Store Locator Plus admin UI class.
*
* Provides various UI functions when someone is an admin on the WP site.
*
************************************************************************/

if (! class_exists('SLPlus_AdminUI')) {
    class SLPlus_AdminUI {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        public $parent = null;
       
        public $styleHandle = 'csl_slplus_admin_css';

        /*************************************
         * The Constructor
         */
        function __construct($params=null) {

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
            }
            return (isset($this->parent) && ($this->parent != null));
        }

        
        /**
         * Setup some of the general settings interface elements.
         */
        function build_basic_admin_settings() {
            if (!$this->setParent()) { return; }

            //-------------------------
            // Navbar Section
            //-------------------------
            $this->parent->settings->add_section(
                array(
                    'name' => 'Navigation',
                    'div_id' => 'slplus_navbar',
                    'description' => $this->parent->helper->get_string_from_phpexec(SLPLUS_COREDIR.'/templates/navbar.php'),
                    'is_topmenu' => true,
                    'auto' => false,
                    'headerbar'     => false
                )
            );

            //-------------------------
            // How to Use Section
            //-------------------------
             $this->parent->settings->add_section(
                array(
                    'name' => 'How to Use',
                    'description' => $this->parent->helper->get_string_from_phpexec(SLPLUS_PLUGINDIR.'/how_to_use.txt'),
                    'start_collapsed' => false
                )
            );

            //-------------------------
            // Google Communication
            //-------------------------
             $this->parent->settings->add_section(
                array(
                    'name'        => 'Google Communication',
                    'description' => 'These settings affect how the plugin communicates with Google to create your map.'.
                                        '<br/><br/>'
                )
            );

             $this->parent->settings->add_item(
                'Google Communication',
                'Google API Key',
                'api_key',
                'text',
                false,
                'Your Google API Key.  You will need to ' .
                '<a href="http://code.google.com/apis/console/" target="newinfo">'.
                'go to Google</a> to get your Google Maps API Key.'
            );


             $this->parent->settings->add_item(
                'Google Communication',
                'Geocode Retries',
                'goecode_retries',
                'list',
                false,
                'How many times should we try to set the latitude/longitude for a new address. ' .
                'Higher numbers mean slower bulk uploads ('.
                '<a href="http://www.charlestonsw.com/product/store-locator-plus/">plus version</a>'.
                '), lower numbers makes it more likely the location will not be set during bulk uploads.',
                array (
                      'None' => 0,
                      '1' => '1',
                      '2' => '2',
                      '3' => '3',
                      '4' => '4',
                      '5' => '5',
                      '6' => '6',
                      '7' => '7',
                      '8' => '8',
                      '9' => '9',
                      '10' => '10',
                    )
            );

            //--------------------------
            // Store Pages
            //
            $slp_rep_desc = __('These settings affect how the Store Pages add-on behaves. ', SLPLUS_PREFIX);
            if (!$this->parent->license->AmIEnabled(true, "SLPLUS-PAGES")) {
                $slp_rep_desc .= '<br/><br/>'.
                    __('This is a <a href="http://www.charlestonsw.com/product/store-locator-plus-store-pages/">Store Pages</a>'.
                    ' feature.  It provides a way to auto-create individual WordPress pages' .
                    ' for each of your locations. ', SLPLUS_PREFIX);
            } else {
                $slp_rep_desc .= '<span style="float:right;">(<a href="#" onClick="'.
                        'jQuery.post(ajaxurl,{action: \'license_reset_pages\'},function(response){alert(response);});'.
                        '">'.__('Delete license',SLPLUS_PREFIX).'</a>)</span>';
            }
            $slp_rep_desc .= '<br/><br/>';
            $this->parent->settings->add_section(
                array(
                    'name'        => 'Store Pages',
                    'description' => $slp_rep_desc
                )
            );
            if ($this->parent->license->AmIEnabled(true, "SLPLUS-PAGES")) {
                slplus_add_pages_settings();
            }

            //-------------------------
            // Pro Pack
            //
            $proPackMsg = (
                    $this->parent->license->packages['Pro Pack']->isenabled            ?
                    '' :
                    __('This is a <a href="http://www.charlestonsw.com/product/store-locator-plus/">Pro Pack</a>  feature. ', SLPLUS_PREFIX)
                    );
            $slp_rep_desc = __('These settings affect how the Pro Pack add-on behaves. ', SLPLUS_PREFIX);
            if (!$this->parent->license->AmIEnabled(true, "SLPLUS-PRO")) {
                $slp_rep_desc .= '<br/><br/>'.$proPackMsg;
            } else {
                $slp_rep_desc .= '<span style="float:right;">(<a href="#" onClick="'.
                        'jQuery.post(ajaxurl,{action: \'license_reset_propack\'},function(response){alert(response);});'.
                        '">'.__('Delete license',SLPLUS_PREFIX).'</a>)</span>';
            }
            $slp_rep_desc .= '<br/><br/>';
            $this->parent->settings->add_section(
                array(
                    'name'        => 'Pro Pack',
                    'description' => $slp_rep_desc
                )
            );
            if ($this->parent->license->AmIEnabled(true, "SLPLUS-PRO")) {
                $this->parent->settings->add_item(
                    'Pro Pack',
                    __('Enable reporting', SLPLUS_PREFIX),
                    'reporting_enabled',
                    'checkbox',
                    false,
                    __('Enables tracking of searches and returned results.  The added overhead ' .
                    'can increase how long it takes to return location search results.', SLPLUS_PREFIX)
                );
            }
            // Custom CSS Field
            //
            $this->parent->settings->add_item(
                    'Pro Pack',
                    __('Custom CSS',SLPLUS_PREFIX),
                    'custom_css',
                    'textarea',
                    false,
                    __('Enter your custom CSS, preferably for SLPLUS styling only but it can be used for any page element as this will go in your page header.',SLPLUS_PREFIX)
                    .$proPackMsg
                        ,
                    null,
                    null,
                    !$this->parent->license->packages['Pro Pack']->isenabled
                    );
        }

        /**
         * Enqueue the admin stylesheet when needed.
         */
        function enqueue_admin_stylesheet() {
            wp_enqueue_style($this->styleHandle);
        }

        /**
         * Setup the stylesheet only when needed.
         */
        function set_style_as_needed() {
            $slugPrefix = 'store-locator-plus_page_';

            // Add Locations
            //
            add_action(
                   'admin_print_styles-' . $slugPrefix . 'slp_add_locations',
                    array($this,'enqueue_admin_stylesheet')
                    );

            // General Settings
            //
           add_action(
                   'admin_print_styles-'  . $slugPrefix . 'slp_general_settings',
                    array($this,'enqueue_admin_stylesheet')
                    );
           add_action(
                   'admin_print_styles-'  . 'settings_page_csl-slplus-options',
                    array($this,'enqueue_admin_stylesheet')
                    );


            // Manage Locations
            //
            add_action(
                   'admin_print_styles-' . 'store-locator-le/core/view-locations.php',
                    array($this,'enqueue_admin_stylesheet')
                    );

            // Map Settings
            //
            add_action(
                   'admin_print_styles-' . 'store-locator-le/core/map-designer.php',
                    array($this,'enqueue_admin_stylesheet')
                    );

            // Reporting
            //
            add_action(
                   'admin_print_styles-' . 'store-locator-le/reporting.php',
                    array($this,'enqueue_admin_stylesheet')
                    );

        }

        /*************************************
         * method: slpRenderCreatePageButton()
         *
         * Render The Create Page Button
         */
        function slpRenderCreatePageButton($locationID=-1,$storePageID=-1) {
            if ($locationID < 0) { return; }            
            $slpPageClass = (($storePageID>0)?'haspage_icon' : 'createpage_icon');
            print "<a   class='action_icon $slpPageClass' 
                        alt='".__('create page',SLPLUS_PREFIX)."' 
                        title='".__('create page',SLPLUS_PREFIX)."' 
                        href='".
                            preg_replace('/&createpage=/'.(isset($_GET['createpage'])?$_GET['createpage']:''), "",$_SERVER['REQUEST_URI']).
                            "&act=createpage&sl_id=$locationID&slp_pageid=$storePageID#a$locationID'
                   ></a>";            
        }  
        
        /*****************************************************
         * method: slpCreatePage()
         *
         * Create a new store pages page.
         */
         function slpCreatePage($locationID=-1)  {
            global $slplus_plugin, $wpdb;
            
            // If not licensed or incorrect location ID get out of here
            //
            if (
                !$slplus_plugin->license->packages['Store Pages']->isenabled ||
                ($locationID < 0)
                ) {
                return -1;
            } 

            // Get The Store Data
            //
            if ($store=$wpdb->get_row('SELECT * FROM '.$wpdb->prefix."store_locator WHERE sl_id = $locationID", ARRAY_A)) {            
                
                $slpStorePage = get_post($store['sl_linked_postid']);
                if (empty($slpStorePage->ID)) {
                    $store['sl_linked_postid'] = -1;
                }


                // Create the page
                //
                $slpNewListing = array(
                    'ID'            => (($store['sl_linked_postid'] > 0)?$store['sl_linked_postid']:''),
                    'post_type'     => 'store_page',
                    'post_status'   => 'publish',
                    'post_title'    => $store['sl_store'],
                    'post_content'  => call_user_func(array('SLPlus_AdminUI','slpCreatePageContent'),$store),
                    );
                
                // Update the row
                //
                $wpdb->update($wpdb->prefix."store_locator", $store, array('sl_id' => $locationID));

                return wp_insert_post($slpNewListing);
             }                
         }
         
        /*****************************************************
         * method: slpCreatePageContent()
         *
         * Creates the content for the page.  If plus pack is installed
         * it uses the plus template file, otherwise we use the hard-coded 
         * layout.
         *
         */         
         function slpCreatePageContent($store) {
             $content = '';

             // Default Content
             //
             $content .= "<span class='storename'>".$store['sl_store']."</span>\n";
             if ($store['sl_image']         !='') { 
                 $content .= '<img class="alignright size-full" title="'.$store['sl_store'].'" src="'.$store['sl_image'].'"/>'."\n"; 
             }
             if ($store['sl_address']       !='') { $content .= $store['sl_address'] . "\n"; }
             if ($store['sl_address2']      !='') { $content .= $store['sl_address2'] . "\n"; }
             
             if ($store['sl_city']          !='') { 
                $content .= $store['sl_city']; 
                if ($store['sl_state'] !='') { $content .= ', '; }
             }
             if ($store['sl_state']         !='') { $content .= $store['sl_state']; }
             if ($store['sl_zip']           !='') { $content .= " ".$store['sl_zip']."\n"; }
             if ($store['sl_country']       !='') { $content .= " ".$store['sl_country']."\n"; }
             if ($store['sl_description']   !='') { $content .= "<h1>Description</h1>\n<p>". html_entity_decode($store['sl_description']) ."</p>\n"; }
             
             $slpContactInfo = '';
             if ($store['sl_phone'] !='') { $slpContactInfo .= __('Phone: ',SLPLUS_PREFIX).$store['sl_phone'] . "\n"; }
             if ($store['sl_fax'] !='') { $slpContactInfo .= __('Fax: ',SLPLUS_PREFIX).$store['sl_fax'] . "\n"; }
             if ($store['sl_email'] !='') { $slpContactInfo .= '<a href="mailto:'.$store['sl_email'].'">'.$store['sl_email']."</a>\n"; }
             if ($store['sl_url']   !='') { $slpContactInfo .= '<a href="'.$store['sl_url'].'">'.$store['sl_url']."</a>\n"; }
             if ($slpContactInfo    != '') { 
                $content .= "<h1>Contact Info</h1>\n<p>".$slpContactInfo."</p>\n"; 
             }

             return $content;             
         }

         /**
          * method: slp_add_search_form_settings_panel
          *
          * Add the search form panel to the map settings page on the admin UI.
          */
         function slp_add_search_form_settings_panel() {
            global $slpMapSettings;
            $slpDescription = get_string_from_phpexec(SLPLUS_COREDIR.'/templates/settings_searchform.php');
            $slpMapSettings->add_section(
                array(
                        'name'          => __('Search Form',SLPLUS_PREFIX),
                        'description'   => $slpDescription,
                        'auto'          => true
                    )
             );
         }

         /**
          * method: slp_add_map_settings_panel
          *
          * Add the map panel to the map settings page on the admin UI.
          */
         function slp_add_map_settings_panel() {
            global $slpMapSettings;
            $slpDescription = get_string_from_phpexec(SLPLUS_COREDIR.'/templates/settings_mapform.php');
            $slpMapSettings->add_section(
                array(
                        'name'          => __('Map',SLPLUS_PREFIX),
                        'description'   => $slpDescription,
                        'auto'          => true
                    )
             );

         }

         /**
          * 
          * @global type $wpdb
          * @param type $fields
          * @param type $sl_values
          * @param type $theaddress
          */
        function add_this_addy($fields,$sl_values,$theaddress) {
            global $wpdb;
            $fields=substr($fields, 0, strlen($fields)-1);
            $sl_values=substr($sl_values, 0, strlen($sl_values)-1);
            $wpdb->query("INSERT into ". $wpdb->prefix . "store_locator ($fields) VALUES ($sl_values);");
            do_geocoding($theaddress);

        }

        /*****************************
         * function: url_test()
         *
         */
        function url_test($url) {
            return (strtolower(substr($url,0,7))=="http://");
        }

        /*****************************
        * function: slpCreateColumnHeader()
        *
        * Create the column headers for sorting the table.
        *
        */
        function slpCreateColumnHeader($theURL,$fldID='sl_store',$fldLabel='ID',$opt='sl_store',$dir='ASC') {
            if ($opt == $fldID) {
                $curDIR = (($dir=='ASC')?'DESC':'ASC');
            } else {
                $curDIR = $dir;
            }
            return "<th><a href='$theURL&o=$fldID&sortorder=$curDIR'>$fldLabel</a></th>";
        }

        /**
         * method: redirectTo_GeneralSettings
         * 
         * Bring users to the main SLP settings page.
         * 
         */
        function renderPage_GeneralSettings() {
            global $slplus_plugin;
            $slplus_plugin->settings->render_settings_page();
        }


        /**
         * method: renderPage_AddLocations()
         *
         * Draw the add locations page.  Use to be a separate script ./core/add-locations.php
         *
         * @global type $wpdb
         */
         function renderPage_AddLocations() {
                global $slplus_plugin,$wpdb;

                print "<div class='wrap'>
                            <div id='icon-add-locations' class='icon32'><br/></div>
                            <h2>".
                            __('Store Locator Plus - Add Locations', SLPLUS_PREFIX).
                            "</h2>";

                initialize_variables();

                //-------------------------
                // Navbar Section
                //-------------------------
                print '<div id="slplus_navbar">';
                print get_string_from_phpexec(SLPLUS_COREDIR.'/templates/navbar.php');
                print '</div>';


                //Inserting addresses by manual input
                //
                $notpca = isset($_GET['mode']) ? ($_GET['mode']!="pca") : true;
                if ( isset($_POST['sl_store']) && $_POST['sl_store'] && $notpca ) {
                    $fieldList = '';
                    $sl_valueList = '';
                    foreach ($_POST as $key=>$sl_value) {
                        if (preg_match('#sl_#', $key)) {
                            $fieldList.="$key,";
                            $sl_value=comma($sl_value);
                            $sl_valueList.="\"".stripslashes($sl_value)."\",";
                        }
                    }

                    $this_addy = $_POST['sl_address'].', '.
                              $_POST['sl_city'].', '.$_POST['sl_state'].' '.
                              $_POST['sl_zip'];
                    $slplus_plugin->AdminUI->add_this_addy($fieldList,$sl_valueList,$this_addy);
                    print "<div class='updated fade'>".
                            $_POST['sl_store'] ." " .
                            __("Added Succesfully",SLPLUS_PREFIX) . '.</div>';

                /** Bulk Upload
                 **/
                } elseif ( isset($_FILES['csvfile']['name']) &&
                       ($_FILES['csvfile']['name']!='')  &&
                        ($_FILES['csvfile']['size'] > 0)
                    ) {
                    add_filter('upload_mimes', 'custom_upload_mimes');

                    // Get the type of the uploaded file. This is returned as "type/extension"
                    $arr_file_type = wp_check_filetype(basename($_FILES['csvfile']['name']));
                    if ($arr_file_type['type'] == 'text/csv') {

                                // Save the file to disk
                                //
                                $updir = wp_upload_dir();
                                $updir = $updir['basedir'].'/slplus_csv';
                                if(!is_dir($updir)) {
                                    mkdir($updir,0755);
                                }
                                if (move_uploaded_file($_FILES['csvfile']['tmp_name'],
                                        $updir.'/'.$_FILES['csvfile']['name'])) {
                                        $reccount = 0;

                                        $adle_setting = ini_get('auto_detect_line_endings');
                                        ini_set('auto_detect_line_endings', true);
                                        if (($handle = fopen($updir.'/'.$_FILES['csvfile']['name'], "r")) !== FALSE) {
                                            $fldNames = array('sl_store','sl_address','sl_address2','sl_city','sl_state',
                                                            'sl_zip','sl_country','sl_tags','sl_description','sl_url',
                                                            'sl_hours','sl_phone','sl_email','sl_image','sl_fax');
                                            $maxcols = count($fldNames);
                                            while (($data = fgetcsv($handle)) !== FALSE) {
                                                $num = count($data);
                                                if ($num <= $maxcols) {
                                                    $fieldList = '';
                                                    $sl_valueList = '';
                                                    $this_addy = '';
                                                    for ($fldno=0; $fldno < $num; $fldno++) {
                                                        $fieldList.=$fldNames[$fldno].',';
                                                        $sl_valueList.="\"".stripslashes(comma($data[$fldno]))."\",";
                                                        if (($fldno>=1) && ($fldno<=6)) {
                                                            $this_addy .= $data[$fldno] . ', ';
                                                        }
                                                    }
                                                    $this_addy = substr($this_addy, 0, strlen($this_addy)-2);
                                                    $slplus_plugin->AdminUI->add_this_addy($fieldList,$sl_valueList,$this_addy);
                                                    sleep(0.5);
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


                                        if ($reccount > 0) {
                                            print "<div class='updated fade'>".
                                                    sprintf("%d",$reccount) ." " .
                                                    __("locations added succesfully.",SLPLUS_PREFIX) . '</div>';
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


                $base=get_option('siteurl');

                // Show the manual location entry form
                execute_and_output_template('add_locations.php');
         }


         /**
          * Render an icon selector for the icon images store in the SLP plugin icon directory.
          * 
          * @param string $elementToUpate - the name of the input ID to update on click
          * @return string - the html of the icon selector
          */
         function rendorIconSelector($inputFieldID = null, $inputImageID = null) {
            if (($inputFieldID == null) || ($inputImageID == null)) { return ''; }
            $htmlStr = '';

            $directories = apply_filters('slp_icon_directories',array(SLPLUS_ICONDIR, SLPLUS_UPLOADDIR."/custom-icons/"));
            foreach ($directories as $directory) {
                $iconDir=opendir($directory);
                $iconURL = (($directory === SLPLUS_ICONDIR)?SLPLUS_ICONURL:SLPLUS_UPLOADURL.'/custom-icons/');
                while (false !== ($an_icon=readdir($iconDir))) {
                    if (
                        (preg_match('/\.(png|gif|jpg)/i', $an_icon) > 0) &&
                        (preg_match('/shadow\.(png|gif|jpg)/i', $an_icon) <= 0)
                        ) {
                        $htmlStr .=
                            "<div class='slp_icon_selector_box'>".
                                "<img class='slp_icon_selector'
                                     src='".$iconURL.$an_icon."'
                                     onclick='".
                                        "document.getElementById(\"".$inputFieldID."\").value=this.src;".
                                        "document.getElementById(\"".$inputImageID."\").src=this.src;".
                                     "'>".
                             "</div>"
                             ;
                    }
                }
            }
            if ($htmlStr != '') {
                $htmlStr = '<div id="'.$inputFieldID.'_icon_row" class="slp_icon_row">'.$htmlStr.'</div>';

            }
            return $htmlStr;
         }

    }
}        
     

