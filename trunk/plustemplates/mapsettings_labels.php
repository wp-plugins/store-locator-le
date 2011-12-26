<?php
global $slplus_plugin;
if ($slplus_plugin->license->packages['Plus Pack']->isenabled) {
?>    
<div class='form_entry'>
    <label for='search_tag_label'><?php _e("Search By Tag Label", SLPLUS_PREFIX); ?>:</label>
    <input name='<?php echo SLPLUS_PREFIX;?>_search_tag_label' value='<?php echo get_option(SLPLUS_PREFIX.'_search_tag_label'); ?>'>
    <?php
    echo slp_createhelpdiv('search_tag_label',
        __("Label for search form tags field.", SLPLUS_PREFIX)
        );
    ?>                  
</div>    
<?php
}
?>
