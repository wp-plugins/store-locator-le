<?php
  global $sl_search_label, $sl_width, $sl_height, $sl_width_units, $sl_height_units,
      $sl_radius, $sl_radius_label, $r_options, $sl_instruction_message, $slplus_state_options, $sl_country_options, 
      $fnvars, $slplus_plugin, $slplus_name_label;

      print "<div id='sl_div'>";

    // Render the search form
    //
    do_action('slp_render_search_form');
  ?>

	<table id='map_table' width='100%' cellspacing='0px' cellpadding='0px'> 
     <tr>
        <td width='100%' valign='top'>
<?php
$sl_starting_image=get_option('sl_starting_image');
if ($sl_starting_image != '') {    
?>
            <div id='map_box_image' style='width:<?php echo $sl_width?><?php echo $sl_width_units?>; height:<?php echo $sl_height?><?php echo $sl_height_units?>'>      
                <img src='<?php 
                        if (preg_match('/^http/',$sl_starting_image) <= 0) {
                            echo SLPLUS_PLUGINURL;
                        }
                        echo $sl_starting_image;                        
                    ?>'>
            </div>
            <div id='map_box_map'>
<?php
}
?>
                <div id='map' style='width:<?php echo $sl_width.$sl_width_units?>; height:<?php echo $sl_height.$sl_height_units?>;'></div>
                <table cellpadding='0'
                       class='sl_footer'
                       width='<?php echo $sl_width.$sl_width_units?>;'
                       <?php
                        echo ((get_option('sl_remove_credits',0)==1)?"style='display:none;'":'');
                       ?>
                       >
                <tr class="slp_map_tagline">
                    <td class='sl_footer_right_column'>
                        <?php echo __('search provided by', SLPLUS_PREFIX); ?> <a href='<?php echo $slplus_plugin->url; ?>' target='_blank'><?php echo $slplus_plugin->name; ?></a>
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
	  <tr id='cm_mapTR' class='slp_map_search_results'>
        <td width='' valign='top' id='map_sidebar_td'>
            <div id='map_sidebar' style='width:<?php echo $sl_width?><?php echo $sl_width_units?>;'>
                <div class='text_below_map'><?php echo $sl_instruction_message?></div>
            </div>
        </td>
    </tr>
  </table>
</div>
