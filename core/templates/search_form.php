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

          //------------------------------------------------
          // Show Tag Search Is Enabled
          //
          /**
           * @see http://goo.gl/UAXly - only_with_tag - filter map results to only those locations with the tag provided
           * @see http://goo.gl/UAXly - tags_for_pulldown - list of tags to use in the search form pulldown, overrides admin map settings
           *
           */
          if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
              if ((get_option(SLPLUS_PREFIX.'_show_tag_search',0) ==1) || isset($slplus_plugin->data['only_with_tag'])) {

                  ob_start();
          ?>
                  <div id='search_by_tag' class='search_item' <?php if (isset($slplus_plugin->data['only_with_tag'])) { print "style='display:none;'"; }?>>
                      <label for='tag_to_search_for'><?php
                          print get_option(SLPLUS_PREFIX.'_search_tag_label');
                          ?></label>
                      <?php
                          // Tag selections
                          //
                          if (isset($slplus_plugin->data['tags_for_pulldown'])) {
                              $tag_selections = $slplus_plugin->data['tags_for_pulldown'];
                          }
                          else {
                              $tag_selections = get_option(SLPLUS_PREFIX.'_tag_search_selections');
                          }

                          // Tag selections
                          //
                          if (isset($slplus_plugin->data['only_with_tag'])) {
                              $tag_selections = '';
                          }

                          // No pre-selected tags, use input box
                          //
                          if ($tag_selections == '') {
                              print "<input type='". (isset($slplus_plugin->data['only_with_tag']) ? 'hidden' : 'text') . "' ".
                                      "id='tag_to_search_for' size='50' " .
                                      "value='" . (isset($slplus_plugin->data['only_with_tag']) ? $slplus_plugin->data['only_with_tag'] : '') . "' ".
                                      "/>";

                          // Pulldown for pre-selected list
                          //
                          } else {
                              $tag_selections = explode(",", $tag_selections);
                              add_action('slp_render_search_form_tag_list',array($slplus_plugin->UI,'slp_render_search_form_tag_list'),10,2);
                              do_action('slp_render_search_form_tag_list',$tag_selections,(get_option(SLPLUS_PREFIX.'_show_tag_any')==1));
                          }
                      ?>
                      </div>
              <?php
                    global $slp_thishtml_40;
                    $slp_thishtml_40 = ob_get_clean();
                    add_filter('slp_search_form_divs',array($slp_SearchDivs,'buildDiv40'),40);
                }

/*
 * Name Search
 */
global $slp_thishtml_50;
$slp_thishtml_50 = $slplus_plugin->UI->create_input_div(
        'nameSearch',
        get_option('sl_name_label',__('Name of Store','csa-slplus')),
        '',
        (get_option(SLPLUS_PREFIX.'_show_search_by_name',0) == 0),
        'div_nameSearch'
        );
add_filter('slp_search_form_divs',array($slp_SearchDivs,'buildDiv50'),50);
}
