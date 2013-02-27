<?php

/***********************************************************************
* Class: SLPlus_AdminUI_ManageLocations
*
* The Store Locator Plus admin UI Manage Locations class.
*
* Provides various UI functions when someone is an admin on the WP site.
*
************************************************************************/

if (! class_exists('SLPlus_AdminUI_ManageLocations')) {
    class SLPlus_AdminUI_ManageLocations {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        public $parent = null;
        public $settings = null;
        public $baseAdminURL = '';
        public $cleanAdminURL = '';
        public $hangoverURL = '';
        public $hiddenInputs = '';

        /**
         * Called when this object is created.
         *
         * @param type $params
         */
        function __construct($params=null) {
            if (!$this->setParent()) {
                die('could not set parent');
                return;
                }
                
            // Set our base Admin URL
            //
            if (isset($_SERVER['REQUEST_URI'])) {
                $this->cleanAdminURL =
                    isset($_SERVER['QUERY_STRING'])?
                        str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) :
                        $_SERVER['REQUEST_URI']
                        ;

                $queryParams = array();

                // Base Admin URL = must have params
                //
                if (isset($_REQUEST['page'])) {
                    $queryParams['page'] = $_REQUEST['page'];
                }
                $this->baseAdminURL = $this->cleanAdminURL . '?' . build_query($queryParams);


                // Hangover URL = params we like to carry around sometimes
                //
                if (isset($_REQUEST['searchfor'])) {
                    $queryParams['searchfor'] = $_REQUEST['searchfor'];
                }
                $this->hangoverURL = $this->cleanAdminURL . '?' . build_query($queryParams);

                $this->plugin->helper->bugout('<pre>'.
                        'cleanAdminURL: '.$this->cleanAdminURL."\n".
                        'baseAdminURL:  '.$this->baseAdminURL ."\n".
                        'hangoverURL:   '.$this->hangoverURL.
                        '</pre>',
                        '','Manage Locations UI',__FILE__,__LINE__
                        );
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
         * Create the hidden inputs HTML string from the REQUEST variable.
         *
         * Skips some REQUEST keys we wish to ignore.
         */
        function create_HiddenInputs() {
            $this->hiddenInputs = '';
            $donotHide = array('searchfor','o','sortorder','start','act','sl_tags','sl_id','delete');
            foreach($_REQUEST as $key=>$val) {
                if (!in_array($key,$donotHide,true)) {
                    $this->hiddenInputs.="<input type='hidden' value='$val' name='$key'>\n";
                }
            }
        }

        /**
         * Delete a location.
         * 
         * @global type $wpdb - the WP database connection
         * @param type $locationID - the ID of the location to delete
         */
        function location_delete($locationID=null) {
            global $wpdb;
            if ($locationID === null) { return; }

            $delQueries = array();
            $idList = array();

            // Multiple locations
            //
            if (is_array($locationID)) {
                $id_string='';
                $idCount = 0;
                foreach ($locationID as $sl_value) {
                    $idCount++;
                    $id_string.="$sl_value,";
                    array_push($idList,$locationID);

                    // Got 100?  Push a delete string on the stack
                    //
                    if ($idCount == 100) {
                        $idCount = 0;
                        $id_string=substr($id_string, 0, strlen($id_string)-1);
                        array_push($delQueries,'DELETE'.$this->plugin->database['query']['fromslp']."WHERE sl_id IN ($id_string)");
                        $id_string='';
                    }
                }

                // Clean up any stragglers
                //
                $id_string=substr($id_string, 0, strlen($id_string)-1);

            // Single Item Delete
            //
            } else {
                $id_string=$locationID;
                array_push($idList,$locationID);
            }

            // push the last one on the stack
            //
            if ($id_string != ''){
                array_push($delQueries,'DELETE'.$this->plugin->database['query']['fromslp']."WHERE sl_id IN ($id_string)");
            }

            // Fire any action hooks on location delete
            //
            do_action('slp_deletelocation_starting',$idList);

            // Run deletions
            //
            $errorMessage = '';
            foreach ($delQueries as $delQuery) {
                $delete_result = $wpdb->query($delQuery);
                $this->parent->helper->bugout("<pre>Delete Instruction:\n$delQuery\nResult:".print_r($delete_result,true)."</pre>",'','Delete Queries',__FILE__,__LINE__);
                if ($delete_result == 0) {
                    $errorMessage .= __("Could not delete the locations.  ", 'csa-slplus');
                    $theDBError = htmlspecialchars(mysql_error($wpdb->dbh),ENT_QUOTES);
                    if ($theDBError != '') {
                        $errorMessage .= sprintf(
                                                __("Error: %s.", 'csa-slplus'),
                                                $theDBError
                                                );
                    } elseif ($delete_result === 0) {
                        $errorMessage .=  __("It appears the delete was for no records.", 'csa-slplus');
                    } else {
                        $errorMessage .=  __("No error logged.", 'csa-slplus');
                        $errorMessage .= "<br/>\n" . __('Query: ', 'csa-slplus');
                        $errorMessage .= print_r($wpdb->last_query,true);
                        $errorMessage .= "<br/>\n" . "Results: " . gettype($delete_result) . ' '. $delete_result;
                    }

                }

            }            
        }

        /**
         * Save a location.
         */
        function location_save() {
            if (!is_numeric($_REQUEST['locationID'])) { return; }
            $this->plugin->notifications->delete_all_notices();


            // Get our original address first
            //
            global $wpdb;
            $old_address=$wpdb->get_results($this->plugin->database['query']['selectall']."WHERE sl_id={$_REQUEST['locationID']}", ARRAY_A);
            if (!isset($old_address[0]['sl_address']))  { $old_address[0]['sl_address'] = '';}
            if (!isset($old_address[0]['sl_address2'])) { $old_address[0]['sl_address2']= '';}
            if (!isset($old_address[0]['sl_city']))     { $old_address[0]['sl_city']    = '';}
            if (!isset($old_address[0]['sl_state']))    { $old_address[0]['sl_state']   = '';}
            if (!isset($old_address[0]['sl_zip'])) 	    { $old_address[0]['sl_zip']     = '';}

            // Update The Location Data
            //
            $field_value_str = '';
            foreach ($_POST as $key=>$sl_value) {
                if (preg_match('#\-'.$_REQUEST['locationID'].'#', $key)) {
                    $slpFieldName = preg_replace('#\-'.$_REQUEST['locationID'].'#', '', $key);
                    if (($slpFieldName === 'latitude') || ($slpFieldName === 'longitude')) {
                        if (!$this->plugin->license->packages['Pro Pack']->isenabled) { continue; }
                        if (!is_numeric(trim($this->parent->AdminUI->slp_escape($sl_value)))) { continue; }
                    }
                    $field_value_str.="sl_".$slpFieldName."='".trim($this->parent->AdminUI->slp_escape($sl_value))."', ";
                    $_POST[$slpFieldName]=$sl_value;
                }
            }
            $field_value_str = substr($field_value_str, 0, strlen($field_value_str)-2);
            $field_value_str = apply_filters('slp_update_location_data',$field_value_str,$_REQUEST['locationID']);
            $wpdb->query("UPDATE ".$wpdb->prefix."store_locator SET $field_value_str WHERE sl_id={$_REQUEST['locationID']}");

            // Run the Location updated Action
            //
            do_action('slp_location_updated',$_REQUEST['locationID'], $field_value_str);

            // Check our address
            //
            if (!isset($_POST['address'])   ) { $_POST['address'] = '';     }
            if (!isset($_POST['address2'])  ) { $_POST['address2'] = '';    }
            if (!isset($_POST['city'])      ) { $_POST['city'] = '';        }
            if (!isset($_POST['state'])     ) { $_POST['state'] = '';       }
            if (!isset($_POST['zip'])       ) { $_POST['zip'] = '';         }
            $the_address=
                    $_POST['address']   .' '    .
                    $_POST['address2']  .', '   .
                    $_POST['city']      .', '   .
                    $_POST['state']     .' '    .
                    $_POST['zip'];

            // RE-geocode if the address changed
            // or if the lat/long is not set
            //
            if (   ($the_address!=
                    $old_address[0]['sl_address'].' '.$old_address[0]['sl_address2'].', '.$old_address[0]['sl_city'].', '.
                    $old_address[0]['sl_state'].' '.$old_address[0]['sl_zip']
                    ) ||
                    ($old_address[0]['sl_latitude']=="" || $old_address[0]['sl_longitude']=="")
                    ) {
                $this->parent->AdminUI->do_geocoding($the_address,$_REQUEST['locationID'], true);
            }

            $this->plugin->notifications->display();
        }

        /**
         * Tag a location
         * 
         * @param mixed $locationID - a single location ID (int) or an array of them.
         */
        function location_tag($locationID) {
            global $wpdb;
            
            //adding or removing tags for specified a locations
            //
            if (is_array($locationID)) {
                $id_string='';
                foreach ($locationID as $sl_value) {
                    $id_string.="$sl_value,";
                }
                $id_string=substr($id_string, 0, strlen($id_string)-1);
            } else {
                $id_string=$locationID;
            }

            // If we have some store IDs
            //
            if ($id_string != '') {
                //adding tags
                if ($_REQUEST['act']=="add_tag") {
                    $wpdb->query("UPDATE ".$wpdb->prefix."store_locator SET ".
                            "sl_tags=CONCAT_WS(',',sl_tags,'".strtolower($_REQUEST['sl_tags'])."') ".
                            "WHERE sl_id IN ($id_string)"
                            );

                //removing tags
                } elseif ($_REQUEST['act']=="remove_tag") {
                    if (empty($_REQUEST['sl_tags'])) {
                        //if no tag is specified, all tags will be removed from selected locations
                        $wpdb->query("UPDATE ".$wpdb->prefix."store_locator SET sl_tags='' WHERE sl_id IN ($id_string)");
                    } else {
                        $wpdb->query("UPDATE ".$wpdb->prefix."store_locator SET sl_tags=REPLACE(sl_tags, ',{$_REQUEST['sl_tags']},', '') WHERE sl_id IN ($id_string)");
                    }
                }
            }
        }


        /**
         * Render the manage location action bar
         * 
         */
        function render_actionbar() {
            if (!$this->setParent()) { return; }

            $this->plugin->helper->loadPluginData();


             if (get_option('sl_location_table_view') == 'Expanded') {
                 $altViewText = __('Switch to normal view?','csa-slplus');
                 $viewText = __('Normal View','csa-slplus');
             } else {
                 $altViewText = __('Switch to expanded view?','csa-slplus');
                 $viewText = __('Expanded View','csa-slplus');
             }

             $actionBoxes = array();

            print 
                '<div id="slplus_actionbar">'             .
                    '<div id="action_buttons">'.
                        '<div id="action_bar_header">'.
                            '<h3>'.__('Actions and Filters','csa-slplus').'</h3>'.
                        '</div>'.
                        '<div class="boxbar">'
                ;

            // Basic Delete Icon
            //
            $actionBoxes['A'][] =
                    '<p class="centerbutton">' .
                        '<a class="like-a-button" href="#" ' .
                                'onclick="doAction(\'delete\',\''.__('Delete selected?','csa-slplus').'\');" ' .
                                'name="delete_selected">'.__("Delete Selected", 'csa-slplus').
                        '</a>'.
                    '</p>'
                    ;

            // Search Locations Button
            //
            $actionBoxes['N'][] =
                    '<p class="centerbutton">'.
                        "<input class='like-a-button' type='submit' ".
                            "value='".__('Search Locations', 'csa-slplus')."'>".
                    '</p>'.
                    "<input id='searchfor' " .
                        "value='".(isset($_REQUEST['searchfor'])?$_REQUEST['searchfor']:'')."' name='searchfor'>" .
                    $this->hiddenInputs
                ;

            // Expanded/Normal View
            //
            $pdString = '';
            $opt_arr=array(10,25,50,100,200,300,400,500,1000,2000,4000,5000,10000);
            foreach ($opt_arr as $sl_value) {
                $selected=($this->plugin->data['sl_admin_locations_per_page']==$sl_value)? " selected " : "";
                $pdString .= "<option value='$sl_value' $selected>$sl_value</option>";
            }
            $actionBoxes['O'][] =
                    '<p class="centerbutton">' .
                        '<a class="like-a-button" href="#" ' .
                            'onclick="doAction(\'changeview\',\''.$altViewText.'\');">'.
                            $viewText .
                        '</a>'.
                    '</p>' .
                    __('Show ', 'csa-slplus') .
                    '<select id="sl_admin_locations_per_page" name="sl_admin_locations_per_page" onchange="doAction(\'locationsPerPage\',\'\');">' .
                        $pdString .
                    '</select>'.
                    __(' locations', 'csa-slplus') . '.'
                    ;

            // Loop through the action boxes content array
            //
            $actionBoxes = apply_filters('slp_action_boxes',$actionBoxes);
            ksort($actionBoxes);
            foreach ($actionBoxes as $boxNumber => $actionBoxLine) {
                print "<div id='box_$boxNumber' class='actionbox'>";
                foreach ($actionBoxLine as $LineHTML) {
                    print $LineHTML;
                }
                print '</div>';
            }

            do_action('slp_add_manage_locations_action_box');

            print
                    '</div>' .
                '</div>' .
              '</div>'
            ;
        }

        /**
         * Render the JavaScript for the manage locations page.
         */
        function render_JavaScript() {
            ?>
            <script language="JavaScript">
                function confirmClick(message,href) {
                    if (confirm(message)) {	location.href=href; }
                    else  { return false; }
                }
                function checkAll(cbox,formObj) {
                    var i=0;
                    if (cbox.checked==true)
                        cbox.checked==false;
                    else
                        cbox.checked==true;
                    while (formObj.elements[i]!=null) {
                        formObj.elements[i].checked=cbox.checked;
                        i++;
                    }
                }
                function doAction(theAction,thePrompt) {
                    if((thePrompt == '') || confirm(thePrompt)){
                        LF=document.forms['locationForm'];
                        LF.act.value=theAction;
                        LF.submit();
                    }else{
                        return false;
                    }
                }
            </script>
            <?php

        }
        
        /**
         * Render the manage locations admin page.
         *
         */
        function render_adminpage() {
            if (!$this->setParent()) { return; }
            $this->plugin->helper->loadPluginData();
            global $wpdb;

            //--------------------------------
            // Debug Output : Post/Server Vars
            //--------------------------------
            $this->parent->helper->bugout("<pre>REQUEST\n".print_r($_REQUEST,true)."</pre>",'','REQUEST',__FILE__,__LINE__);
            // $this->parent->helper->bugout("<pre>POST\n".print_r($_POST,true)."</pre>",'','POST',__FILE__,__LINE__);
            // $this->parent->helper->bugout("<pre>GET".print_r($_GET,true)."</pre>",'','GET',__FILE__,__LINE__);
            $this->parent->helper->bugout("<pre>SERVER\n".print_r($_SERVER,true)."</pre>",'','SERVER',__FILE__,__LINE__);


            //--------------------------------
            // Create the hidden inputs string
            //--------------------------------            
            $this->create_HiddenInputs();

            //--------------------------------
            // Render: JavaScript
            //--------------------------------
            $this->render_JavaScript();

            //--------------------------------
            // Render: Header Div & Nav Tabs
            //--------------------------------
            print "<div class='wrap'>
                        <div id='icon-edit-locations' class='icon32'><br/></div>
                        <h2>".
                        __('Store Locator Plus - Manage Locations', 'csa-slplus').
                        "</h2>" .
                  $this->parent->AdminUI->create_Navbar()
                  ;

            $this->parent->AdminUI->initialize_variables();

            //------------------------------------------------------------------------
            // ACTION HANDLER
            // If post action is set
            //------------------------------------------------------------------------
            if (isset($_REQUEST)) { extract($_REQUEST); }

            if (isset($_REQUEST['act'])) {

                // SAVE
                if ($_REQUEST['act']=='save') {
                    $this->location_save();

                // DELETE
                //
                // location ID is either a single int coming in via REQUEST[delete]
                // or
                // a single int coming in from a lone checkbox in POST[sl_id]
                // or
                // an array of ints coming in from multiple checkboxes in POST[sl_id]
                //
                } elseif ($_REQUEST['act']=='delete'){
                   $locationID =
                        (isset($_REQUEST['delete'])&& is_numeric($_REQUEST['delete'])) ?
                           $_REQUEST['delete']  :
                           (isset($_POST['sl_id']) ? $_POST['sl_id'] : null)
                        ;
                   $this->location_delete($locationID);

                // TAG
                //
                }  elseif (preg_match('#tag#i', $_REQUEST['act'])) {
                    if (isset($_REQUEST['sl_id'])) { $this->location_tag($_REQUEST['sl_id']); }

                // Locations Per Page Action
                //   - update the option first,
                //   - then reload the
                } elseif ($_REQUEST['act']=="locationsPerPage") {
                    if (
                         isset($_REQUEST['sl_admin_locations_per_page']) &&
                        !empty($_REQUEST['sl_admin_locations_per_page'])
                        ) {
                        update_option('sl_admin_locations_per_page', $_REQUEST['sl_admin_locations_per_page']);
                        $this->plugin->settings->get_item('sl_admin_locations_per_page','get_option',null,'10',true);
                    }

                // Change View Action
                //
                } elseif ($_REQUEST['act']=='changeview') {
                    if (get_option('sl_location_table_view') == 'Expanded') {
                        update_option('sl_location_table_view', 'Normal');
                    } else {
                        update_option('sl_location_table_view', 'Expanded');
                    }

                // Recode The Address
                //
                } elseif ($_REQUEST['act']=='recode') {
                    $this->plugin->notifications->delete_all_notices();
                    if (isset($_REQUEST['sl_id'])) {
                        if (!is_array($_REQUEST['sl_id'])) {
                            $theLocations = array($_REQUEST['sl_id']);
                        } else {
                            $theLocations = $_REQUEST['sl_id'];
                        }

                        // Process SL_ID Array
                        //
                        foreach ($theLocations as $thisLocation) {
                                $address=$wpdb->get_row($this->plugin->database['query']['selectall']."WHERE sl_id=$thisLocation", ARRAY_A);

                                if (!isset($address['sl_address'])) { $address['sl_address'] = '';  print 'BLANK<br/>';	}
                                if (!isset($address['sl_address2'])){ $address['sl_address2'] = ''; }
                                if (!isset($address['sl_city'])) 	{ $address['sl_city'] = ''; 	}
                                if (!isset($address['sl_state'])) 	{ $address['sl_state'] = ''; 	}
                                if (!isset($address['sl_zip'])) 	{ $address['sl_zip'] = ''; 		}
                                if (!isset($address['sl_country'])) 	{ $address['sl_country'] = ''; 		}

                                $this->plugin->AdminUI->do_geocoding("$address[sl_address] $address[sl_address2], $address[sl_city], $address[sl_state] $address[sl_zip] $address[sl_country]",$thisLocation,true);
                        }
                        $this->plugin->notifications->display();
                    }
                }

                do_action('slp_manage_locations_action');
                
            } //--- REQUEST['act'] is set


            //------------------------------------------------------------------------
            // CHANGE UPDATER
            // Changing Updater
            //------------------------------------------------------------------------
            if (isset($_GET['changeUpdater']) && ($_GET['changeUpdater']==1)) {
                if (get_option('sl_location_updater_type')=="Tagging") {
                    update_option('sl_location_updater_type', 'Multiple Fields');
                    $updaterTypeText="Multiple Fields";
                } else {
                    update_option('sl_location_updater_type', 'Tagging');
                    $updaterTypeText="Tagging";
                }
                $_SERVER['REQUEST_URI']=preg_replace('/&changeUpdater=1/', '', $_SERVER['REQUEST_URI']);
                print "<script>location.replace('".$_SERVER['REQUEST_URI']."');</script>";
            }

            //------------------------------------------------------------------------
            // Reload Variables - anything that my have changed
            //------------------------------------------------------------------------
            $this->plugin->helper->getData('sl_admin_locations_per_page','get_option',null,'10',true,true);

            //------------------------------------------------------------------------
            // QUERY BUILDING
            //------------------------------------------------------------------------
            $qry = isset($_REQUEST['searchfor']) ? $_REQUEST['searchfor'] : '';
            $where=($qry!='')?
                    " CONCAT_WS(';',sl_store,sl_address,sl_address2,sl_city,sl_state,sl_zip,sl_country,sl_tags) LIKE '%$qry%'" :
                    '' ;

            /* Uncoded items */
            if (isset($_REQUEST['act'])) {
                if ($_REQUEST['act'] == 'show_uncoded') {
                    $where .= " sl_latitude NOT REGEXP '^[0-9]|-' or sl_longitude NOT REGEXP '^[0-9]|-'";
                }
            }


            $opt= (isset($_GET['o']) && (trim($_GET['o']) != ''))
            ? $_GET['o'] : "sl_store";
            $dir= (isset($_GET['sortorder']) && (trim($_GET['sortorder'])=='DESC'))
            ? 'DESC' : 'ASC';

            // Get the sort order and direction out of our URL
            //
            $slpCleanURL = str_replace("&o=$opt&sortorder=$dir", '', $_SERVER['REQUEST_URI']);

            //------------------------------------------------------------------------
            // UI
            //------------------------------------------------------------------------

            // Pagination
            //
            if (trim($where) != false) { $where = "WHERE $where"; }
            $totalLocations=$wpdb->get_var("SELECT count(sl_id) FROM ".$wpdb->prefix."store_locator $where");
            $this->parent->helper->bugout("SELECT count(sl_id) FROM ".$wpdb->prefix."store_locator $where : returns $totalLocations", '', 'SQL Count', __FILE__, __LINE__);
            $start=(isset($_GET['start'])&&(trim($_GET['start'])!=''))?$_GET['start']:0;
            if ($totalLocations>0) {
                $this->parent->AdminUI->manage_locations_pagination(
                        $totalLocations,
                        $this->plugin->data['sl_admin_locations_per_page'],
                        $start
                        );
            }


            //--------------------------------
            // Render: Start of Form
            //--------------------------------
            print "\n".
                    '<form id="manage_locations_actionbar_form" name="locationForm" method="post" action="'.$this->baseAdminURL.'">'.
                    '<input name="act" type="hidden">'                         
                    ;

            //--------------------------------
            // Render: Action Bar
            //--------------------------------
            $this->render_actionbar();

            // Search Filter, no actions
            // Clear the start, we want all records
            //
            if (isset($_POST['searchfor']) && ($_POST['searchfor'] != '') && ($_POST['act'] == '')) {
                $start = 0;
            }


            // We have matching locations
            //
            $dataQuery =
                $this->plugin->database['query']['selectall'] .
                    "$where ORDER BY $opt $dir ".
                     "LIMIT $start,".$this->plugin->data['sl_admin_locations_per_page']
                ;
            $this->parent->helper->bugout($dataQuery, '', 'SQL Data', __FILE__, __LINE__);
            if ($slpLocations=$wpdb->get_results($dataQuery,ARRAY_A)) {

                // Setup Table Columns
                //
                $slpManageColumns = array(
                        'sl_id'         =>  __('ID'       ,'csa-slplus'),
                        'sl_store'      =>  __('Name'     ,'csa-slplus'),
                        'sl_address'    =>  __('Street'   ,'csa-slplus'),
                        'sl_address2'   =>  __('Street2'  ,'csa-slplus'),
                        'sl_city'       =>  __('City'     ,'csa-slplus'),
                        'sl_state'      =>  __('State'    ,'csa-slplus'),
                        'sl_zip'        =>  __('Zip'      ,'csa-slplus'),
                        'sl_country'    =>  __('Country'  ,'csa-slplus'),
                    );

                // Expanded View
                //
                if (get_option('sl_location_table_view')!="Normal") {
                    $slpManageColumns = array_merge($slpManageColumns,
                                array(
                                    'sl_tags'       => __('Tags'     ,'csa-slplus'),
                                    'sl_description'=> __('Description'  ,'csa-slplus'),
                                    'sl_url'        => get_option('sl_website_label','Website'),
                                )
                            );

                    $slpManageColumns = array_merge($slpManageColumns,
                                array(
                                    'sl_email'       => __('Email'        ,'csa-slplus'),
                                    'sl_hours'       => $this->parent->settings->get_item('label_hours','Hours','_'),
                                    'sl_phone'       => $this->parent->settings->get_item('label_phone','Phone','_'),
                                    'sl_fax'         => $this->parent->settings->get_item('label_fax'  ,'Fax'  ,'_'),
                                    'sl_image'       => __('Image'        ,'csa-slplus'),
                                )
                            );

                }
                $slpManageColumns = apply_filters('slp_manage_location_columns', $slpManageColumns);
                
                // Get the manage locations table header
                //
                $tableHeaderString = $this->parent->AdminUI->manage_locations_table_header($slpManageColumns,$slpCleanURL,$opt,$dir);
                print  "<div id='location_table_wrapper'>" .
                            "<table id='manage_locations_table' class='slplus wp-list-table widefat fixed posts' cellspacing=0>" .
                                $tableHeaderString;

                // Render The Data
                //
                $bgcol = '#eee';
                foreach ($slpLocations as $sl_value) {

                    // Row color
                    //
                    $bgcol=($bgcol=="#eee")?"#fff":"#eee";
                    $bgcol=($sl_value['sl_latitude']=="" || $sl_value['sl_longitude']=="")? "salmon" : $bgcol;

                    // Clean Up Data with trim()
                    //
                    $locID = $sl_value['sl_id'];
                    $sl_value=array_map("trim",$sl_value);

                    // EDIT MODE
                    // Show the edit form in a new row for the location that was selected.
                    //
                    if (isset($_GET['edit']) && ($locID==$_GET['edit'])) {
                        print
                            "<tr id='slp_location_edit_row'>"               .
                            "<td class='slp_locationinfoform_cell' colspan='".(count($slpManageColumns)+4)."'>".
                            '<input type="hidden" id="act" name="act" value="save"/>'. 
                            $this->parent->AdminUI->createString_LocationInfoForm($sl_value, $locID) .
                            '</td></tr>';

                    // DISPLAY MODE
                    //
                    } else {

                        // Custom Filters to set the links on special data like URLs and Email
                        //
                        $sl_value['sl_url']=(!$this->parent->AdminUI->url_test($sl_value['sl_url']) && trim($sl_value['sl_url'])!="")?
                            "http://".$sl_value['sl_url'] :
                            $sl_value['sl_url'] ;
                        $sl_value['sl_url']=($sl_value['sl_url']!="")?
                            "<a href='$sl_value[sl_url]' target='blank'>".__("View", 'csa-slplus')."</a>" :
                            "" ;
                        $sl_value['sl_email']=($sl_value['sl_email']!="")?
                            "<a href='mailto:$sl_value[sl_email]' target='blank'>".__("Email", 'csa-slplus')."</a>" :
                            "" ;
                        $sl_value['sl_image']=($sl_value['sl_image']!="")?
                            "<a href='$sl_value[sl_image]' target='blank'>".__("View", 'csa-slplus')."</a>" :
                            "" ;
                        $sl_value['sl_description']=($sl_value['sl_description']!="")?
                            "<a onclick='alert(\"".$this->parent->AdminUI->slp_escape($sl_value['sl_description'])."\")' href='#'>".
                            __("View", 'csa-slplus')."</a>" :
                            "" ;

                        // create Action Buttons
                        $actionButtonsHTML =
                            "<a class='action_icon edit_icon' alt='".__('edit','csa-slplus')."' title='".__('edit','csa-slplus')."'
                                href='".$this->hangoverURL."&act=edit&edit=$locID#a$locID'></a>".
                            "&nbsp;" .
                            "<a class='action_icon delete_icon' alt='".__('delete','csa-slplus')."' title='".__('delete','csa-slplus')."'
                                href='".$this->hangoverURL."&act=delete&delete=$locID' " .
                                "onclick=\"confirmClick('".sprintf(__('Delete %s?','csa-slplus'),$sl_value['sl_store'])."', this.href); return false;\"></a>"
                                ;

                        $actionButtonsHTML = apply_filters('slp_manage_locations_actionbuttons',$actionButtonsHTML, $sl_value);

                        print "<tr style='background-color:$bgcol'>" .
                            "<th><input type='checkbox' name='sl_id[]' value='$locID'></th>" .
                            "<th class='thnowrap'>".
                                $actionButtonsHTML . 
                            "</th>"
                            ;

                        // Data Columns
                        //
                        foreach ($slpManageColumns as $slpField => $slpLabel) {
                            print '<td>' . apply_filters('slp_column_data',$sl_value[$slpField], $slpField, $slpLabel) . '</td>';
                        }

                        // Lat/Long Columns
                        //
                        print
                                '<td>'.$sl_value['sl_latitude'] .'</td>' .
                                '<td>'.$sl_value['sl_longitude'].'</td>' .
                            '</tr>';
                    }
                }

                // Close Out Table
                //
                print $tableHeaderString .'</table></div>';

            // No Locations Found
            //
            } else {

                    print "<div class='csa_info_msg'>".
                            (
                             ($qry!='')?
                                    __("Search Locations returned no matches.", 'csa-slplus') :
                                    __("No locations have been created yet.", 'csa-slplus')
                            ) .
                          "</div>";
            }


            if ($totalLocations!=0) {
                $this->parent->AdminUI->manage_locations_pagination(
                        $totalLocations,
                        $this->plugin->data['sl_admin_locations_per_page'],
                        $start
                        );
            }
            print "</form></div>";
        }

    }
}        
     

