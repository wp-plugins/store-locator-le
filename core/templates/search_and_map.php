<?php
global $sl_search_label, $sl_width, $sl_height, $sl_width_units, $sl_height_units, $sl_radius_label, $slplus_plugin;
print "<div id='sl_div'>";

// Render the search form
//
do_action('slp_render_search_form');
?>

    <table id='map_table' width='100%' cellspacing='0px' cellpadding='0px'>
        <tbody id='map_table_body'>
            <tr id='map_table_row'>
                <td id='map_table_cell' width='100%' valign='top'>
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
                <?php
                if (!(get_option('sl_remove_credits',0)==1)) {
                    echo "<div id='slp_tagline'style='width:$sl_width$sl_width_units;'>" .
                            __('search provided by', SLPLUS_PREFIX) .
                            "<a href='".$slplus_plugin->url."' target='_blank'>".
                                $slplus_plugin->name.
                            "</a>".
                        '</div>'
                        ;
                }
if ($sl_starting_image != '') {    
                echo '</div>';
}
?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Results Table -->
    <table id='results_table'>
        <tr id='cm_mapTR' class='slp_map_search_results'>
            <td width='' valign='top' id='map_sidebar_td'>
                <div id='map_sidebar' style='width:<?php echo $sl_width?><?php echo $sl_width_units?>;'>
                    <div class='text_below_map'><?php echo get_option('sl_instruction_message',__('Enter Your Address or Zip Code Above.','csl-slplus')); ?></div>
                </div>
            </td>
        </tr>
    </table>
</div>
