<?php
/****************************************************************************
 ** file: core/templates/navbar.php
 **
 ** The top Store Locator Settings navigation bar.
 ***************************************************************************/
 
 global $slplus_plugin;
?>
<script src="<?php echo SLPLUS_COREURL;?>js/functions.js"></script>
<ul>
    <a href="<?php echo SLPLUS_ADMINPAGE;?>view-locations.php"><li class='like-a-button'>Locations: Manage</li></a>
    <a href="<?php echo SLPLUS_ADMINPAGE;?>add-locations.php"><li class='like-a-button'>Locations: Add</li></a>
    <a href="<?php echo SLPLUS_ADMINPAGE;?>map-designer.php"><li class='like-a-button'>Settings: Map</li></a>
    <a href="<?php echo admin_url(); ?>options-general.php?page=csl-slplus-options"><li class='like-a-button'>Settings: General</li></a>    
    <?php 
    //--------------------------------
    // Pro Version : Show Reports Tab
    //
    if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {      
        print '<a href="'.SLPLUS_PLUSPAGE.'reporting.php"><li class="like-a-button">Reports</li></a>';
    }
    ?>    
</ul>


