<?php
/****************************************************************************
 ** file: view-locations.php
 **
 ** Manage the view locations admin panel action.
 ***************************************************************************/

$hidden='';
foreach($_GET as $key=>$val) {
	//hidden keys to keep same view after form submission
	if ($key!="q" && $key!="o" && $key!="d" && $key!="changeView" && $key!="start") {
		$hidden.="<input type='hidden' value='$val' name='$key'>\n"; 
	}
}

// Header Text
//
print "<div class='wrap'>
            <div id='icon-edit-locations' class='icon32'><br/></div>
            <h2>".
            __('Store Locator Plus - Manage Locations', SLPLUS_PREFIX).
            "</h2>";

            
//-------------------------
// Navbar Section
//-------------------------    
print '<div id="slplus_navbar">';
print get_string_from_phpexec(SLPLUS_COREDIR.'/templates/navbar.php');
print '</div>';            

// Check Google API Key
// Not present : show message
//
$slak=$slplus_plugin->driver_args['api_key'];
if (!$slak) {
	print '<a href="'.get_option('siteurl').'/wp-admin/options-general.php?page=csl-slplus-options">';
	_e('Google API Key needs to be set to activate this feature.', SLPLUS_PREFIX);
	print '</a>';

// Got key - show forms and listing
//
} else {
    
    // Initialize Variables
    //
    initialize_variables();  

	// If delete link is clicked
	if (isset($_GET['delete']) && ($_GET['delete']!='')) {
		$wpdb->query("DELETE FROM ".$wpdb->prefix."store_locator ".
		    "WHERE sl_id='".$_GET['delete']."'");
	}

    // Edit, any form
    //
	if ($_POST                                                  && 
	    (isset($_GET['edit']) && $_GET['edit'])                 &&
	    (!isset($_POST['act']) || (isset($_POST['act']) && ($_POST['act']!="delete"))) 
	    ) {
		$field_value_str = '';
		foreach ($_POST as $key=>$value) {
			if (ereg("\-$_GET[edit]", $key)) {
				$field_value_str.="sl_".ereg_replace("\-$_GET[edit]", "", $key)."='".
                    trim(comma($value))."', ";
                    
                // strip off number at the end 
                $key=ereg_replace("\-$_GET[edit]", "", $key); 
				$_POST["$key"]=$value; 
			}
		}
		$field_value_str=substr($field_value_str, 0, strlen($field_value_str)-2);
		$edit=$_GET['edit']; extract($_POST);
		$the_address="$address $address2, $city, $state $zip";
		
		$old_address=$wpdb->get_results("SELECT * FROM ".
                        $wpdb->prefix."store_locator WHERE sl_id=$_GET[edit]", ARRAY_A);
		$wpdb->query("UPDATE ".$wpdb->prefix."store_locator SET $field_value_str " .
                        "WHERE sl_id=$_GET[edit]");
                
                if (!isset($old_address['sl_address'])) { $old_address['sl_address'] = ''; 	} 
                if (!isset($old_address['sl_address2'])){ $old_address['sl_address2'] = ''; 	} 
                if (!isset($old_address['sl_city'])) 	{ $old_address['sl_city'] = ''; 	} 
                if (!isset($old_address['sl_state'])) 	{ $old_address['sl_state'] = ''; 	} 
                if (!isset($old_address['sl_zip'])) 	{ $old_address['sl_zip'] = ''; 		} 
                
		if ($the_address!=
		    "$old_address[sl_address] $old_address[sl_address2], $old_address[sl_city], " .
		    "$old_address[sl_state] $old_address[sl_zip]" || 
		    ($old_address['sl_latitude']=="" || $old_address['sl_longitutde']=="")
            	) {
			do_geocoding($the_address,$_GET['edit']);
		}
		
		print "<script>location.replace('".ereg_replace("&edit=$_GET[edit]", "", 
                    $_SERVER['REQUEST_URI'])."');</script>";
	}
	
    //If post action is set
	if (isset($_POST['act'])) {

        // Delete Action	    
        if ($_POST['act']=="delete") {
            include_once(SLPLUS_COREDIR   . 'deleteLocations.php'       );            
        }        
        
        // Tagging Action
        if (eregi("tag", $_POST['act'])) {
            include_once(SLPLUS_COREDIR   . 'tagLocations.php'       );            
        }
        
        // Locations Per Page Action
        if ($_POST['act']=="locationsPerPage") {
            //If bulk delete is used
            update_option('sl_admin_locations_per_page', $_POST['sl_admin_locations_per_page']);
            extract($_POST);
        }
    }
    
    
	// Changing Updater
	//
	if (isset($_GET['changeUpdater']) && ($_GET['changeUpdater']==1)) {
		if (get_option('sl_location_updater_type')=="Tagging") {
			update_option('sl_location_updater_type', 'Multiple Fields');
			$updaterTypeText="Multiple Fields";
		} else {
			update_option('sl_location_updater_type', 'Tagging');
			$updaterTypeText="Tagging";
		}
		$_SERVER['REQUEST_URI']=ereg_replace("&changeUpdater=1", "", $_SERVER['REQUEST_URI']);
		print "<script>location.replace('".$_SERVER['REQUEST_URI']."');</script>";
	}

    // Changing View
    //
    $tabViewText = get_option('sl_location_table_view');
	if (isset($_GET['changeView']) && ($_GET['changeView']==1)) {
		if ($tabViewText=="Normal") {
			update_option('sl_location_table_view', 'Expanded');
			$tabViewText=__('Expanded',SLPLUS_PREFIX);
		} else {
			update_option('sl_location_table_view', 'Normal');
			$tabViewText=__('Normal',SLPLUS_PREFIX);
		}
		$_SERVER['REQUEST_URI']=ereg_replace("&changeView=1", "", $_SERVER['REQUEST_URI']);
		print "<script>location.replace('".$_SERVER['REQUEST_URI']."');</script>";
	} else {
	    $tabViewText = ($tabViewText == 'Normal') ? 
	        __('Normal',SLPLUS_PREFIX) :
	        __('Expanded',SLPLUS_PREFIX);
	}
		

    // Form Output  
    print "
    <div class='top_listing_bar'>        
        <div class='viewtype'>".
            __("View", SLPLUS_PREFIX).':'.
            "<a href='".ereg_replace("&changeView=1", "", $_SERVER['REQUEST_URI'])."&changeView=1'>".                    
            $tabViewText.                        
       "</a>
       </div>
       
       <div class='searchlocations'>
        <form>
            <input value='".(isset($_GET['q'])?$_GET['q']:'')."' name='q'>
            <input type='submit' value='".__("Search Locations", SLPLUS_PREFIX)."'>
            $hidden
        </form>
       </div>
      
       <div class='perpage'>
        <form name='locationForm' method='post'>".
        __("Locations Per Page", SLPLUS_PREFIX).": 
        <select name='sl_admin_locations_per_page'
           onchange=\"LF=document.forms['locationForm'];".
                     "LF.act.value='locationsPerPage';LF.submit();\">                
            >
            <option value=''>".__("Choose", SLPLUS_PREFIX)."</option>";
    $opt_arr=array(10,25,50,100,200,300,400,500,1000,2000,4000,5000,10000);
    foreach ($opt_arr as $value) {
        $selected=($sl_admin_locations_per_page==$value)? " selected " : "";
        print "<option value='$value' $selected>$value</option>";
    }
    print "</select>
       </div>
        <div style='clear:both;'></div>
    </div>";
    

    print "<table width='100%' cellpadding='5px' cellspacing='0' style='border:solid silver 1px' id='rightnow' class='widefat'>
    <thead><tr>
    <td style='/*background-color:#000;*/ width:20%'><input class='button' type='button' value='".__("Delete Selected", SLPLUS_PREFIX)."' onclick=\"if(confirm('".__("You sure", SLPLUS_PREFIX)."?')){LF=document.forms['locationForm'];LF.act.value='delete';LF.submit();}else{return false;}\"></td>";
    print "<td style='/*background-color:#000;*/ width:73%; text-align:right; color:white'>";
    print "<strong>".__("Tags", SLPLUS_PREFIX)."</strong>&nbsp;<input name='sl_tags'>&nbsp;<input class='button' type='button' value='".__("Tag Selected", SLPLUS_PREFIX)."' onclick=\"LF=document.forms['locationForm'];LF.act.value='add_tag';LF.submit();\">&nbsp;<input class='button' type='button' value='".__("Remove Tag From Selected", SLPLUS_PREFIX)."' onclick=\"if(confirm('".__("You sure", SLPLUS_PREFIX)."?')){LF=document.forms['locationForm'];LF.act.value='remove_tag';LF.submit();}else{return false;}\">";
    print "</td></tr></thead></table>
    ";
    set_query_defaults();

    //for search links
    $numMembers=$wpdb->get_results(
        "SELECT sl_id FROM " . $wpdb->prefix . "store_locator $where");
    $numMembers2=count($numMembers); 
    $start=(isset($_GET['start'])&&(trim($_GET['start'])!=''))?$_GET['start']:0;
    //edit this to determine how many locations to view per page of 'Manage Locations' page
    $num_per_page=$sl_admin_locations_per_page; 
    if ($numMembers2!=0) {include(SLPLUS_COREDIR.'search-links.php');}

$opt = isset($_GET['o']) ? $_GET['o'] : '';
$dir = isset($_GET['d']) ? $_GET['d'] : '';
print "<br>
<table class='widefat' cellspacing=0>
<thead><tr >
<th colspan='1'><input type='checkbox' onclick='checkAll(this,document.forms[\"locationForm\"])' class='button'></th>
<th colspan='1'>".__("Actions", SLPLUS_PREFIX)."</th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_id&d=$d'>".__("ID", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_store&d=$d'>".__("Name", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_address&d=$d'>".__("Street", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_address2&d=$d'>".__("Street2", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_city&d=$d'>".__("City", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_state&d=$d'>".__("State", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_zip&d=$d'>".__("Zip", SLPLUS_PREFIX)."</a></th>
<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI'])."&o=sl_tags&d=$d'>".__("Tags", SLPLUS_PREFIX)."</a></th>";

if (get_option('sl_location_table_view')!="Normal") {
    print "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_description&d=$d'>".__("Description", SLPLUS_PREFIX)."</a></th>".
            
        "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_url&d=$d'>".__("URL", SLPLUS_PREFIX)."</a></th>".
            
        "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_email&d=$d'>".__("Email", SLPLUS_PREFIX)."</a></th>".
            
        "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_hours&d=$d'>".__("Hours", SLPLUS_PREFIX)."</th>".
            
        "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_phone&d=$d'>".__("Phone", SLPLUS_PREFIX)."</a></th>".
            
        "<th><a href='".ereg_replace("&o=$opt&d=$dir", "", $_SERVER['REQUEST_URI']).
            "&o=sl_image&d=$d'>".__("Image", SLPLUS_PREFIX)."</a></th>";
}

print "<th>(Lat, Lon)</th></tr></thead>";

if ($locales=$wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 
        "store_locator  $where ORDER BY $o $d LIMIT $start,$num_per_page", ARRAY_A)) {
		
        $bgcol = '#eee';
		foreach ($locales as $value) {
		    $locID = $value['sl_id'];
			$bgcol=($bgcol=="#eee")?"#fff":"#eee";			
			$bgcol=($value['sl_latitude']=="" || $value['sl_longitude']=="")? "salmon" : $bgcol;			
			$value=array_map("trim",$value);
			
			// EDIT MODE
			//
			if (isset($_GET['edit']) && ($locID==$_GET['edit'])) {
				print "<tr style='background-color:$bgcol'>";
	            $colspan=(get_option('sl_location_table_view')!="Normal")? 	16 : 11;	
				
                print "<td colspan='$colspan'><form name='manualAddForm' method=post>
                <a name='a".$locID."'></a>
                <table cellpadding='0' class='manual_update_table'>
                <!--thead><tr><td>".__("Type&nbsp;Address", SLPLUS_PREFIX)."</td></tr></thead-->
                <tr>
                    <td valign='top'>";
                
                execute_and_output_template('edit_location_address.php');
                
                print "<br>
                        <nobr><input type='submit' value='".__("Update", SLPLUS_PREFIX)."' class='button-primary'><input type='button' class='button' value='".__("Cancel", SLPLUS_PREFIX)."' onclick='location.href=\"".ereg_replace("&edit=$_GET[edit]", "",$_SERVER['REQUEST_URI'])."\"'></nobr>
                    </td><td>
                        <b>".__("Additional Information", SLPLUS_PREFIX)."</b><br>
                        <textarea name='description-$locID' rows='5' cols='17'>$value[sl_description]</textarea>&nbsp;<small>".__("Description", SLPLUS_PREFIX)."</small><br>
                        <input name='tags-$locID' value='$value[sl_tags]'>&nbsp;<small>".__("Tags (seperate with commas)", SLPLUS_PREFIX)."</small><br>		
                        <input name='url-$locID' value='$value[sl_url]'>&nbsp;<small>".__("URL", SLPLUS_PREFIX)."</small><br>
                        <input name='email-$locID' value='$value[sl_email]'>&nbsp;<small>".__("Email", SLPLUS_PREFIX)."</small><br>
                        <input name='hours-$locID' value='$value[sl_hours]'>&nbsp;<small>".__("Hours", SLPLUS_PREFIX)."</small><br>
                        <input name='phone-$locID' value='$value[sl_phone]'>&nbsp;<small>".__("Phone", SLPLUS_PREFIX)."</small><br>
                        <input name='image-$locID' value='$value[sl_image]'>&nbsp;<small>".__("Image URL (shown with location)", SLPLUS_PREFIX)."</small><br><br>
                    </td>
                        </tr>
                    </table>
                </form></td>
                </tr>";
                
			// DISPLAY MODE
			//
			} else {
                $value['sl_url']=(!url_test($value['sl_url']) && trim($value['sl_url'])!="")? 
                    "http://".$value['sl_url'] : 
                    $value['sl_url'] ;
                $value['sl_url']=($value['sl_url']!="")? 
                    "<a href='$value[sl_url]' target='blank'>".__("View", SLPLUS_PREFIX)."</a>" : 
                    "" ;
                $value['sl_email']=($value['sl_email']!="")? 
                    "<a href='mailto:$value[sl_email]' target='blank'>".__("Email", SLPLUS_PREFIX)."</a>" : 
                    "" ;
                $value['sl_image']=($value['sl_image']!="")? 
                    "<a href='$value[sl_image]' target='blank'>".__("View", SLPLUS_PREFIX)."</a>" : 
                    "" ;
                $value['sl_description']=($value['sl_description']!="")? 
                    "<a onclick='alert(\"".comma($value['sl_description'])."\")' href='#'>".
                    __("View", SLPLUS_PREFIX)."</a>" : 
                    "" ;
                
                print "<tr style='background-color:$bgcol'>
                <th><input type='checkbox' name='sl_id[]' value='$locID'></th>
                <th><a href='".ereg_replace("&edit=".(isset($_GET['edit'])?$_GET['edit']:''), "",$_SERVER['REQUEST_URI']).
                "&edit=" . $locID ."#a$locID'>".__("Edit", SLPLUS_PREFIX).
                "</a>&nbsp;|&nbsp;<a href='".$_SERVER['REQUEST_URI']."&delete=$locID' " .
                "onclick=\"confirmClick('Sure?', this.href); return false;\">".
                __("Delete", SLPLUS_PREFIX)."</a></th>
                <th> $locID </th>
                <td> $value[sl_store] </td>
                <td>$value[sl_address]</td>
                <td>$value[sl_address2]</td>
                <td>$value[sl_city]</td>
                <td>$value[sl_state]</td>
                <td>$value[sl_zip]</td>
                <td>$value[sl_tags]</td>";
                
                if (get_option('sl_location_table_view')!="Normal") {
                    print "<td>$value[sl_description]</td>
                    <td>$value[sl_url]</td>
                    <td>$value[sl_email]</td>
                    <td>$value[sl_hours]</td>
                    <td>$value[sl_phone]</td>
                    <td>$value[sl_image]</td>";
                }
                
                print "<td>(".$value['sl_latitude'].",&nbsp;".$value['sl_longitude'].")</td> </tr>";
			}
		}
} else {
		$notice=( isset($_GET['q']) && ($_GET['q']!="") )? 
                __("No Locations Showing for this Search of ", SLPLUS_PREFIX).
                    "<b>\"$_GET[q]\"</b>. $view_link" : 
                __("No Locations Currently in Database", SLPLUS_PREFIX);
		print "<tr><td colspan='5'>$notice | <a href='admin.php?page=$sl_dir/core/add-locations.php'>".
            __("Add Locations", SLPLUS_PREFIX)."</a></td></tr>";
	}
	print "</table>
	<input name='act' type='hidden'><br>";
if ($numMembers2!=0) {include(SLPLUS_COREDIR.'/search-links.php');}

print "</form>";
	
}
print "</div>";
