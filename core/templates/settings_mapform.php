<?php 
    global $sl_map_type_options, $sl_num_initial_displayed, $sl_the_domain, $sl_char_enc,
            $sl_zoom, $sl_zoom_adj, $sl_height,$sl_height_units,$sl_width,$sl_width_units,
            $cl_icon_notification_msg,$checked3,$cl_icon,$cl_icon2,$cl_icon_str,$cl_icon2_str;    
?>
<div id='map_settings'>
    <div class='section_column'>   
        <div class='map_designer_settings'>
            <h2><?php _e('Features', SLPLUS_PREFIX); ?></h2>
            <div class='form_entry'>
                <label for='sl_map_type'><?php _e('Default Map Type', SLPLUS_PREFIX);?>:</label>
                <select name='sl_map_type'><?php echo $sl_map_type_options;?></select>
            </div>            
            <div class='form_entry'>
                <label for='sl_map_overview_control'><?php _e('Show Map Inset Box', SLPLUS_PREFIX);?>:</label>    
                <input name='sl_map_overview_control' value='1' type='checkbox' <?php echo (get_option('sl_map_overview_control')==1)?'checked':'';?> >
            </div>
            
            <div class='form_entry'>
                <label for='sl_load_locations_default'><?php _e("Immediately Show Locations", SLPLUS_PREFIX);?>:</label>
                <input name='sl_load_locations_default' value='1' type='checkbox' <?php echo (get_option('sl_load_locations_default')==1)?'checked':'';?> >
            </div>
            
            <div class='form_entry'>
                <label for='sl_num_initial_displayed'><? _e('Immediately show up to', SLPLUS_PREFIX); ?></label>
                <input name='sl_num_initial_displayed' value='<?php echo $sl_num_initial_displayed;?>' class='small'>
                <?php _e('locations.', SLPLUS_PREFIX); ?>
                <?php
                echo slp_createhelpdiv('sl_num_initial_displayed',
                    __('Recommended Max: 50', SLPLUS_PREFIX)
                    );
                ?>                 
            </div>
            
            <?php
             //--------------------------------
             // Pro Pack
             //            
            if (function_exists('execute_and_output_plustemplate')) {
            ?>                
                        <div class='form_entry'>
                            <label for='<?php echo SLPLUS_PREFIX.'_maxreturned'; ?>'><? _e("Return at most", SLPLUS_PREFIX); ?></label>
                            <input name='<?php echo SLPLUS_PREFIX.'_maxreturned'; ?>' 
                                value='<?php 
                                    echo (trim(get_option(SLPLUS_PREFIX.'_maxreturned'))!="")? 
                                        get_option(SLPLUS_PREFIX.'_maxreturned') : 
                                        '25';                    
                                ?>' 
                                class='small'>
                            <? _e("locations when searching.", SLPLUS_PREFIX); ?>
                            <?php
                            echo slp_createhelpdiv(SLPLUS_PREFIX-'_maxreturned',
                                __('Enter a number to limit how many results are returned during a search. The default is 25.', SLPLUS_PREFIX)
                                );
                            ?>                 
                        </div>
            <?php
                execute_and_output_plustemplate('mapsettings_mapfeatures.php');
            }    
            ?>
        </div>
    </div>        

    
    <div class='section_column'>       
        <div class='map_designer_settings'>
            <h2><?php _e('Dimensions', SLPLUS_PREFIX);?></h2>

            <?php
                echo CreatePulldownDiv(
                        'sl_zoom_level',
                        array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                        $label=__('Zoom Level', SLPLUS_PREFIX),
                        $msg=__('Initial zoom level of the map if "immediately show locations" is NOT selected or if only a single location is found.  0 = world view, 19 = house view.', SLPLUS_PREFIX),
                        $prefix='',
                        $default=4
                        );

                echo CreatePulldownDiv(
                        'sl_zoom_tweak',
                        array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19),
                        $label=__('Zoom Adjustment', SLPLUS_PREFIX),
                        $msg=__('Changes how tight auto-zoom bounds the locations shown.  Lower numbers are closer to the lcoations.', SLPLUS_PREFIX),
                        $prefix='',
                        $default=4
                        );
            ?>
            
            <div class='form_entry'>
                <label for='height'><?php _e("Map Height", SLPLUS_PREFIX);?>:</label>
                <input name='height' value='<?php echo $sl_height;?>' class='small'>&nbsp;
                <?php print choose_units($sl_height_units, "height_units"); ?>
            </div>
            
            <div class='form_entry'>
                <label for='height'><?php _e("Map Width", SLPLUS_PREFIX);?>:</label>
                <input name='width' value='<?php echo $sl_width;?>'  class='small'>&nbsp;
                <?php print choose_units($sl_width_units, "width_units"); ?>
            </div>
        </div>
    </div>
    
    <div class='section_column'>       
        <div class='map_designer_settings'>
            <h2><?php _e('Icons', SLPLUS_PREFIX);?></h2>    
            <?php echo $cl_icon_notification_msg;?>
            
            <div class='form_entry'>
                <label for='sl_remove_credits'><?php _e('Remove Credits', SLPLUS_PREFIX);?></label>
                <input name='sl_remove_credits' value='1' type='checkbox' <?php echo $checked3;?> >
            </div>
    
            <div class='form_entry'>
                <label for='icon'><?php _e('Home Icon', SLPLUS_PREFIX);?></label>
                <input name='icon' dir='rtl' size='45' value='<?php echo $cl_icon;?>' onchange="document.getElementById('prev').src=this.value">
                    &nbsp;&nbsp;<img id='prev' src='<?php echo $cl_icon;?>' align='top'><br/>
                <div style='margin-left: 150px;'><?php echo $cl_icon_str;?></div>        
            </div>
    
            <div class='form_entry'>
                <label for='icon2'><?php _e('Destination Icon', SLPLUS_PREFIX);?></label>
                <input name='icon2' dir='rtl' size='45' value='<?php echo $cl_icon2;?>' onchange="document.getElementById('prev2').src=this.value">
                    &nbsp;&nbsp;<img id='prev2' src='<?php echo $cl_icon2;?>'align='top'><br/>
                <div style='margin-left: 150px;'><?php echo $cl_icon2_str;?></div>
            </div>
        </div>
    </div>
    

    <div class='section_column'>   
        <div class='map_interface_settings'> 
            <h2><?php _e('Country', SLPLUS_PREFIX);?></h2>
            <div class='form_entry'>
                <label for='google_map_domain'><?php _e("Select Your Location", SLPLUS_PREFIX);?></label>
                <select name='google_map_domain'>
                <?php
                    foreach ($sl_the_domain as $key=>$sl_value) {
                        $selected=(get_option('sl_google_map_domain')==$sl_value)?" selected " : "";
                        print "<option value='$key:$sl_value' $selected>$key ($sl_value)</option>\n";
                    }
                ?>
                </select>
            </div>
            
            <div class='form_entry'>
                <label for='sl_map_character_encoding'><?php _e('Select Character Encoding', SLPLUS_PREFIX);?></label>
                <select name='sl_map_character_encoding'>
                <?php
                    foreach ($sl_char_enc as $key=>$sl_value) {
                        $selected=(get_option('sl_map_character_encoding')==$sl_value)?" selected " : "";
                        print "<option value='$sl_value' $selected>$key</option>\n";                        
                    }
                ?>
                </select>
            </div>
        </div>
    </div>    
</div>

