<?php
  global $search_label, $width, $height, $width_units, $height_units, $hide,
      $sl_radius, $sl_radius_label, $text_domain, $r_options, $button_style,
      $sl_instruction_message, $cs_options, $country_options, $prefix, $fnvars;
?>
<div id='sl_div'>
  <form onsubmit='searchLocations(); return false;' id='searchForm' action=''>
    <table border='0' cellpadding='3px' class='sl_header'><tr>
	<td valign='top'>
	    <div id='address_search'>

              
            <?php
            //------------------------------------------------
            // Show City Pulldown Is Enabled
            //
            if ($cs_options != '') { 
            ?>
            <div id='addy_in_city'>
                <select id='addressInput2' 
                    onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
                    <option value=''>--Search By City--</option>
                    <?php echo $cs_options?>
                </select>
            </div>
            <?php } ?>
            
            
            <?php
            //------------------------------------------------
            // Show Country Pulldown Is Enabled
            //
            if ($country_options != '') { 
            ?>
            <div id='addy_in_country'>
                <select id='addressInput3' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
                <option value=''>--Search By Country--</option>
                <?php echo $country_options?>
                </select>
            </div>
            <?php } ?>

            
            <?php
            //------------------------------------------------
            // Show Tag Search Is Enabled
            //
            if (get_option($prefix.'_show_tag_search') ==1) {                
            ?>
            <div id='search_by_tag' class='search_item' <?php if (isset($fnvars['only_with_tag'])) { print "style='display:none;'"; }?>>   
                <label for='tag_to_search_for'><?php 
                	print get_option($prefix.'_search_tag_label');                
                	?></label>
                <?php       
                    // Tag selections
                    //
                    if (isset($fnvars['tags_for_pulldown'])) {
                        $tag_selections = $fnvars['tags_for_pulldown'];
                    }
                    else {
                        $tag_selections = get_option($prefix.'_tag_search_selections');
                    }
                    
                    // Tag selections
                    //
                    if (isset($fnvars['only_with_tag'])) {
                        $tag_selections = '';
                    }
                    
                    // No pre-selected tags, use input box
                    //
                    if ($tag_selections == '') {
                        print "<input type='". (isset($fnvars['only_with_tag']) ? 'hidden' : 'text') . "' ". 
                                "id='tag_to_search_for' size='50' " .
                                "value='" . (isset($fnvars['only_with_tag']) ? $fnvars['only_with_tag'] : '') . "' ".                                
                                "/>";
                        
                    // Pulldown for pre-selected list
                    //
                    } else {
                        print "<select id='tag_to_search_for'>";
                        
                        // Show Any Option (blank value)
                        //
                        if (get_option($prefix.'_show_tag_any')==1) {
                            print "<option value=''>".
                                        __('Any',$text_domain).
                                        '</option>';
                        }
                        
                        $tag_selections = explode(",", $tag_selections);
                        foreach ($tag_selections as $selection) {
                            $clean_selection = preg_replace('/\((.*)\)/','$1',$selection);
                            print "<option value='$clean_selection' ";
                            print (ereg("\(.*\)", $selection))? " selected='selected' " : '';
                            print ">$clean_selection</option>";                            
                        }
                        print "</select>";
                    }                        
                ?>
            </div>	   
	   <?php } ?>
	   
            <div id='addy_in_address' class='search_item'>
                <label for="addressInput"><?php echo $search_label?></label>
                <input type='text' id='addressInput' size='50' />
           </div>
            
	        <div id='addy_in_radius'>
	            <label for='radiusSelect'><?php _e($sl_radius_label, $text_domain);?></label>
	            <select id='radiusSelect'><?php echo $r_options?></select>
            </div>
            
            <div id='radius_in_submit'>
                <input <?php echo $button_style?> value='Search Locations' id='addressSubmit'/>
            </div>
        </div>
	  </td>
	</tr></table>
	<table width='100%' cellspacing='0px' cellpadding='0px'> 
     <tr>
        <td width='100%' valign='top'>
<?php
$sl_starting_image=get_option('sl_starting_image');
if ($sl_starting_image != '') {    
?>
            <div id='map_box_image' style='width:<?php echo $width?><?php echo $width_units?>; height:<?php echo $height?><?php echo $height_units?>'>      
                <img src='<?php echo SLPLUS_PLUGINURL."$sl_starting_image"; ?>'>
            </div>
            <div id='map_box_map'>
<?php
}
?>
                <div id='map' style='width:<?php echo $width?><?php echo $width_units?>; height:<?php echo $height?><?php echo $height_units?>'></div>
                <table cellpadding='0px' class='sl_footer' width='<?php echo $width?><?php echo $width_units?>;' <?php echo $hide?>>
                <tr>
                    <td class='sl_footer_left_column'>
                        <a href='http://www.cybersprocket.com/products/store-locator-plus/' target='_blank'>Store Locator Plus</a>
                    </td>
                    <td class='sl_footer_right_column'>
                        <a href='http://www.cybersprocket.com' target='_blank' title='by Cyber Sprocket Labs'>by Cyber Sprocket Labs</a>
                    </td>
                </tr>                
                </table>
<?php
if ($sl_starting_image != '') {    
?>
            </div>
<?php
}
?>
		</td>
      </tr>
	  <tr id='cm_mapTR'>
        <td width='' valign='top' id='map_sidebar_td'>
            <div id='map_sidebar' style='width:<?php echo $width?><?php echo $width_units?>;'>
                <div class='text_below_map'><?php echo $sl_instruction_message?></div>
            </div>
        </td>
    </tr>
  </table></form>
<p><script type="text/javascript">if (document.getElementById("map")){setTimeout("sl_load()",1000);}</script></p>
</div>


