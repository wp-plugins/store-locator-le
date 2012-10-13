<?php
/****************************************************************************
 ** file: plus.php
 **
 ** The functions that make up the PLUS in Store Locator Plus
 ***************************************************************************/

/**************************************
 ** function: custom_upload_mimes
 **
 ** Allows WordPress to process csv file types
 **
 **/
function custom_upload_mimes ( $existing_mimes=array() ) {

     // add CSV type     
    $existing_mimes['csv'] = 'text/csv'; 

    // and return the new full result
    return $existing_mimes;

} 

/**************************************
 ** function: execute_and_output_plustemplate()
 ** 
 ** Executes the included php (or html) file and prints out the results.
 ** Makes for easy include templates that depend on processing logic to be
 ** dumped mid-stream into a WordPress page.  A plugin in a plugin sorta.
 **
 ** Parameters:
 **  $file (string, required) - name of the file in the plugin/templates dir
 **/
function execute_and_output_plustemplate($file) {
    $file = SLPLUS_COREDIR.'templates/'.$file;
    print get_string_from_phpexec($file);
}

/**************************************
 ** function: slplus_add_pages_settings()
 ** 
 ** Add store pages settings to the admin interface.
 **
 **/
function slplus_add_pages_settings() {
    global $slplus_plugin;
    
    if ($slplus_plugin->license->AmIEnabled(true, "SLPLUS-PAGES")) {
        $slplus_plugin->settings->add_item(
            'Store Pages', 
            __('Pages Replace Websites', SLPLUS_PREFIX), 
            'use_pages_links', 
            'checkbox', 
            false,
            __('Use the Store Pages local URL in place of the website URL on the map results list.', SLPLUS_PREFIX)
        );           
        $slplus_plugin->settings->add_item(
            'Store Pages', 
            __('Prevent New Window', SLPLUS_PREFIX), 
            'use_same_window', 
            'checkbox', 
            false,
            __('Prevent Store Pages web links from opening in a new window.', SLPLUS_PREFIX)
        );           
    }
}


/**************************************
 ** function: slplus_create_country_pd()
 ** 
 ** Create the county pulldown list, mark the checked item.
 **
 **/
function slplus_create_country_pd() {
    global $wpdb;
    global $slplus_plugin;
    
    // Pro Pack Enabled
    //
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {            
        $myOptions = '';
        
        // If Use Country Search option is enabled
        // build our country pulldown.
        //
        if (get_option('sl_use_country_search')==1) {
            $cs_array=$wpdb->get_results(
                "SELECT TRIM(sl_country) as country " .
                    "FROM ".$wpdb->prefix."store_locator " .
                    "WHERE sl_country<>'' " .
                        "AND sl_latitude<>'' AND sl_longitude<>'' " .
                    "GROUP BY country " .
                    "ORDER BY country ASC", 
                ARRAY_A);
        
            // If we have country data show it in the pulldown
            //
            if ($cs_array) {
                foreach($cs_array as $sl_value) {
                  $myOptions.=
                    "<option value='$sl_value[country]'>" .
                    $sl_value['country']."</option>";
                }
            }
        }    
        return $myOptions;

    // No Pro Pack
    //
    } else {
        return '';
    }        
}

/**************************************
 ** function: slplus_create_state_pd()
 ** 
 ** Create the state pulldown list, mark the checked item.
 **
 **/
function slplus_create_state_pd() {
    global $wpdb;
    global $slplus_plugin;
    
    // Pro Pack Enabled
    //
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {            
        $myOptions = '';
        
        // If Use State Search option is enabled
        // build our state pulldown.
        //
        if (get_option('slplus_show_state_pd')==1) {
            $cs_array=$wpdb->get_results(
                "SELECT TRIM(sl_state) as state " .
                    "FROM ".$wpdb->prefix."store_locator " .
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
        }    
        return $myOptions;

    // No Pro Pack
    //
    } else {
        return '';
    }        
}



/**************************************
 ** function: slpreport_downloads()
 **
 ** Setup the javascript hook for reporting AJAX
 **
 **/
function slpreport_downloads() {
    ?>
    <script type="text/javascript" src="<?php echo SLPLUS_COREURL; ?>js/jquery.tablesorter.min.js"></script>
    <script type="text/javascript" >
    jQuery(document).ready( 
        function($) {
            // Make tables sortable
             var tstts = $("#topsearches_table").tablesorter( {sortList: [[1,1]]} ); 
             var trtts = $("#topresults_table").tablesorter( {sortList: [[5,1]]} ); 
             
            // Export Results Button Click
            //
            jQuery("#export_results").click(
                function(e) {
                    jQuery('<form action="<?php echo SLPLUS_PLUGINURL; ?>/downloadcsv.php" method="post">'+
                            '<input type="hidden" name="filename" value="topresults">' +
                            '<input type="hidden" name="query" value="' + jQuery("[name=topresults]").val() + '">' +
                            '<input type="hidden" name="sort"  value="' + trtts[0].config.sortList.toString() + '">' +                                
                            '<input type="hidden" name="all"   value="' + jQuery("[name=export_all]").is(':checked') + '">' + 
                            '</form>'
                            ).appendTo('body').submit().remove();                    
                }
            );
            
            // Export Searches Button Click
            //
            jQuery("#export_searches").click(
                function(e) {
                    jQuery('<form action="<?php echo SLPLUS_PLUGINURL; ?>/downloadcsv.php" method="post">'+
                            '<input type="hidden" name="filename" value="topsearches">' +
                            '<input type="hidden" name="query" value="' + jQuery("[name=topsearches]").val() + '">' + 
                            '<input type="hidden" name="sort"  value="' + tstts[0].config.sortList.toString() + '">' +                                                            
                            '<input type="hidden" name="all"   value="' + jQuery("[name=export_all]").is(':checked') + '">' + 
                            '</form>'
                            ).appendTo('body').submit().remove();                    
                }
            );
            
        }
    );
    </script>
    <?php
}

/**************************************
 ** function: slplus_shortcode_atts()
 ** 
 ** Set the entire list of accepted attributes.
 ** The shortcode_atts function ensures that all possible
 ** attributes that could be passed are given a value which
 ** makes later processing in the code a bit easier.
 ** This is basically the equivalent of the php array_merge()
 ** function.
 **
 **/
function slplus_shortcode_atts($attributes) {
    global $slplus_plugin;

    // Pro Pack Enabled
    //
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
        $slpAtts =
            array(
                'tags_for_pulldown'=> null, 
                'only_with_tag'    => null,
                'theme'            => null,
                );        
        shortcode_atts($slpAtts,$attributes);
    }
}


