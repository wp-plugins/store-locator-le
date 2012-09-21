<?php
  global $sl_search_label, $sl_width, $sl_height, $sl_width_units, $sl_height_units, $sl_hide,
      $sl_radius, $sl_radius_label, $r_options, $button_style,
      $sl_instruction_message, $cs_options, $slplus_state_options, $sl_country_options,
      $fnvars, $slplus_plugin, $slplus_name_label;

      $prefix = $slplus_plugin->prefix;
?>

<form onsubmit='cslmap.searchLocations(); return false;' id='searchForm' action=''>
  <table  id='search_table' border='0' cellpadding='3px' class='sl_header'><tr>
  <td valign='top'>
      <div id='address_search'>


          <?php
          //------------------------------------------------
          // Show City Pulldown Is Enabled
          //
          if ($cs_options != '') { 
          ?>
          <div id='addy_in_city'>
              <select id='addressInput2' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
                  <option value=''>--Search By City--</option>
                  <?php echo $cs_options?>
              </select>
          </div>
          <?php } ?>


          <?php
          //------------------------------------------------
          // Show State Pulldown Is Enabled
          //
          if ($slplus_state_options != '') { 
          ?>
          <div id='addy_in_state'>
              <label for='addressInputState'><?php 
                  print get_option($prefix.'_state_pd_label');
                  ?></label>
              <select id='addressInputState' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
                  <option value=''>--Search By State--</option>
                  <?php echo $slplus_state_options?>
              </select>
          </div>
          <?php } ?>            

          <?php
          //------------------------------------------------
          // Show Country Pulldown Is Enabled
          //
          if ($sl_country_options != '') { 
          ?>
          <div id='addy_in_country'>
              <select id='addressInput3' onchange='aI=document.getElementById("searchForm").addressInput;if(this.value!=""){oldvalue=aI.value;aI.value=this.value;}else{aI.value=oldvalue;}'>
              <option value=''>--Search By Country--</option>
              <?php echo $sl_country_options?>
              </select>
          </div>
          <?php } 

          //------------------------------------------------
          // Show Tag Search Is Enabled
          //
          if ($slplus_plugin->license->packages['Pro Pack']->isenabled) {
              if (get_option(SLPLUS_PREFIX.'_show_tag_search',0) ==1) {
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
                              $tag_selections = explode(",", $tag_selections);
                              add_action('slp_render_search_form_tag_list',array('SLPlus_UI','slp_render_search_form_tag_list'),10,2);
                              do_action('slp_render_search_form_tag_list',$tag_selections,(get_option(SLPLUS_PREFIX.'_show_tag_any')==1));
                          }
                      ?>
                      </div>
              <?php
              }

              // ----------------------------------------------
              // We are showing the name search
              //
              if (get_option(SLPLUS_PREFIX.'_show_search_by_name') == 1) {
                  ?>
                  <div id='name_search_div' class='search_item'>
                      <label for='nameSearch'><?php echo $slplus_name_label?></label>
                      <input type='text' id='nameSearch' size='50' />
                  </div>
                  <?php
              }
          }
          ?>

          <?php
          //------------------------------------------------
          // We are not hiding the address input
          //
          if (get_option(SLPLUS_PREFIX.'_hide_address_entry') == 0) {                
          ?>    	   
          <div id='addy_in_address' class='search_item'>
              <label for="addressInput"><?php echo $sl_search_label?></label>
              <input type='text' id='addressInput' size='50' />
         </div>
         <?php
          } else {
              ?>
          <div id='addy_in_address' class='search_item'>
          <input type='hidden' id='addressInput' value='' />
         </div>
         <?php
          }
          ?>

          <?php
          //------------------------------------------------
          // We are not hiding the radius selection
          //
          if (get_option(SLPLUS_PREFIX.'_hide_radius_selections') == 0) {                
          ?>           
              <div id='addy_in_radius'>
                  <label for='radiusSelect'><?php _e($sl_radius_label, SLPLUS_PREFIX);?></label>
                  <select id='radiusSelect'><?php echo $r_options;?></select>
              </div>

          <?php
          } else {
              echo $r_options;
          }

          //------------------------------------------------
          // We are not hiding the submit button
          //
          if (get_option(SLPLUS_PREFIX.'_disable_search') == 0) {                
          ?>               
          <div id='radius_in_submit'>
              <input <?php echo $button_style?> value='Search Locations' id='addressSubmit'/>
          </div>
          <?php
          }
          ?>


      </div>
    </td>
  </tr>
  </table>
  </form>
