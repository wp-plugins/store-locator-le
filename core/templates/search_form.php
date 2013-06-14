<?php

global $cs_options, $slplus_state_options, $sl_country_options;

/**
 * The plugin object.
 * 
 * @var SLPlus $slplus_plugin
 */
global $slplus_plugin;

global $slp_SearchDivs;

//------------------------------------------------
// Show City Pulldown Is Enabled
//
if ($cs_options != '') {
    ob_start();
    ?>
<div id='addy_in_city'>
    <select id='addressInput2' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
        <option value=''><?php print get_option(SLPLUS_PREFIX.'_search_by_city_pd_label',__('--Search By City--','csa-slplus')); ?></option>
        <?php echo $cs_options?>
    </select>
</div>
<?php
  global $slp_thishtml_10;
  $slp_thishtml_10 = ob_get_clean();
  add_filter('slp_search_form_divs',array($slp_SearchDivs,'buildDiv10'),10);
}

//------------------------------------------------
// Show State Pulldown Is Enabled
//
if ($slplus_state_options != '') {
ob_start();
?>
<div id='addy_in_state'>
    <label for='addressInputState'><?php 
        print get_option(SLPLUS_PREFIX.'_state_pd_label');
        ?></label>
    <select id='addressInputState' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
        <option value=''><?php print get_option(SLPLUS_PREFIX.'_search_by_state_pd_label',__('--Search By State--','csa-slplus')); ?></option>
        <?php echo $slplus_state_options?>
    </select>
</div>

<?php
  global $slp_thishtml_20;
  $slp_thishtml_20 = ob_get_clean();
  add_filter('slp_search_form_divs',array($slp_SearchDivs,'buildDiv20'),20);
}

//------------------------------------------------
// Show Country Pulldown Is Enabled
//
if ($sl_country_options != '') {
    ob_start();
?>
<div id='addy_in_country'>
    <select id='addressInput3' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
    <option value=''><?php print get_option(SLPLUS_PREFIX.'_search_by_country_pd_label',__('--Search By Country--','csa-slplus')); ?></option>
    <?php echo $sl_country_options?>
    </select>
</div>
<?php

  global $slp_thishtml_30;
  $slp_thishtml_30 = ob_get_clean();
  add_filter('slp_search_form_divs',array($slp_SearchDivs,'buildDiv30'),30);
}