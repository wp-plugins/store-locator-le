<?php

/***********************************************************************
* Class: SLPlus_UI
*
* The Store Locator Plus UI class.
*
* Provides various UI functions when someone is surfing the site.
*
************************************************************************/

if (! class_exists('SLPlus_UI')) {
    class SLPlus_UI {
        
        /******************************
         * PUBLIC PROPERTIES & METHODS
         ******************************/
        private $usingThemeForest = false;

        /*************************************
         * The Constructor
         */
        function __construct($params = null) {
            $this->usingThemeForest = function_exists('webtreats_formatter');

            // Do the setting override or initial settings.
            //
            if ($params != null) {
                foreach ($params as $name => $sl_value) {
                    $this->$name = $sl_value;
                }
            }
        }
        
        /**
         * Do not texturize our shortcodes.
         * 
         * @param array $shortcodes
         * @return array
         */
        function no_texturize_shortcodes($shortcodes) {
           return array_merge($shortcodes,
                    array(
                     'STORE-LOCATOR',
                     'SLPLUS',
                     'slplus',
                    )
                   );
        }


        /**************************************
         ** function: render_shortcode
         **
         ** Process the store locator shortcode.
         **
         **/
         function render_shortcode($attributes, $content = null) {
            // Variables this function uses and passes to the template
            // we need a better way to pass vars to the template parser so we don't
            // carry around the weight of these global definitions.
            // the other option is to unset($GLOBAL['<varname>']) at then end of this
            // function call.
            //
            // Let's start using a SINGLE named array called "fnvars" to pass along anything
            // we want.
            //
            global  $wpdb,
                $sl_search_label, $sl_width, $sl_height, $sl_width_units, $sl_height_units,
                $sl_radius_label, $r_options, $sl_instruction_message, $cs_options, $slplus_name_label,
                $sl_country_options, $slplus_state_options, $fnvars;
            $fnvars = array();

            //----------------------
            // Attribute Processing
            //
            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $this->parent->shortcode_was_rendered = true;
                slplus_shortcode_atts($attributes);
            }

            $sl_height         = get_option('sl_map_height','500');
            $sl_height_units   = get_option('sl_map_height_units','px');
            $sl_search_label   = get_option('sl_search_label',__('Address',SLPLUS_PREFIX));
            $unit_display   = get_option('sl_distance_unit','mi');
            $sl_width          = get_option('sl_map_width','100');
            $sl_width_units    = get_option('sl_map_width_units','%');
            $slplus_name_label = get_option('sl_name_label');
            $r_array        = explode(",",get_option('sl_map_radii','1,5,10,(25),50,100,200,500'));

            $sl_instruction_message = get_option('sl_instruction_message',__('Enter Your Address or Zip Code Above.',SLPLUS_PREFIX));


            $r_options      =(isset($r_options)         ?$r_options      :'');
            $cs_options     =(isset($cs_options)        ?$cs_options     :'');
            $sl_country_options=(isset($sl_country_options)   ?$sl_country_options:'');
            $slplus_state_options=(isset($slplus_state_options)   ?$slplus_state_options:'');

            foreach ($r_array as $sl_value) {
                $selected=(preg_match('/\(.*\)/', $sl_value))? " selected='selected' " : "" ;

                // Hiding Radius?
                if (get_option(SLPLUS_PREFIX.'_hide_radius_selections') == 1) {
                    if ($s == " selected='selected' ") {
                        $sl_value=preg_replace('/[^0-9]/', '', $sl_value);
                        $r_options = "<input type='hidden' id='radiusSelect' name='radiusSelect' value='$sl_value'>";
                    }

                // Not hiding radius, build pulldown.
                } else {
                    $sl_value=preg_replace('/[^0-9]/', '', $sl_value);
                    $r_options.="<option value='$sl_value' $selected>$sl_value $unit_display</option>";
                }
            }

            //-------------------
            // Show City Search option is checked
            // setup the pulldown list
            //
            if (get_option('sl_use_city_search',0)==1) {
                $cs_array=$wpdb->get_results(
                    "SELECT CONCAT(TRIM(sl_city), ', ', TRIM(sl_state)) as city_state " .
                        "FROM ".$wpdb->prefix."store_locator " .
                        "WHERE sl_city<>'' AND sl_state<>'' AND sl_latitude<>'' " .
                            "AND sl_longitude<>'' " .
                        "GROUP BY city_state " .
                        "ORDER BY city_state ASC",
                    ARRAY_A);

                if ($cs_array) {
                    foreach($cs_array as $sl_value) {
                $cs_options.="<option value='$sl_value[city_state]'>$sl_value[city_state]</option>";
                    }
                }
            }

            //----------------------
            // Create Country Pulldown
            //
            if ($this->parent->license->packages['Pro Pack']->isenabled) {
                $sl_country_options = slplus_create_country_pd();
                $slplus_state_options = slplus_create_state_pd();
            } else {
                $sl_country_options = '';
                $slplus_state_options = '';
            }

            $columns = 1;
            $columns += (get_option('sl_use_city_search',0)!=1) ? 1 : 0;
            $columns += (get_option('sl_use_country_search',0)!=1) ? 1 : 0;
            $columns += (get_option('slplus_show_state_pd',0)!=1) ? 1 : 0;
            $sl_radius_label=get_option('sl_radius_label','');

            // Prep fnvars for passing to our template
            //
            $fnvars = array_merge($fnvars,(array) $attributes);       // merge in passed attributes


            //todo: make sure map type gets set to a sane value before getting here. Maybe not...

            //todo: if we allow map setting overrides via shortcode attributes we will need
            // to re-localize the script.  It was moved to the actions class so we can
            // localize prior to enqueue in the header.
            //

            // Setup the style sheets
            //
            setup_stylesheet_for_slplus();


            // Set our flag for later processing
            // of JavaScript files
            //
            if (!defined('SLPLUS_SHORTCODE_RENDERED')) {
                define('SLPLUS_SHORTCODE_RENDERED',true);
            }

            // Search / Map Actions
            //
            add_action('slp_render_search_form',array('SLPlus_UI','slp_render_search_form'));

            return $this->rawDeal($this->parent->helper->get_string_from_phpexec(SLPLUS_COREDIR . 'templates/search_and_map.php'));
        }

        /**
         * Wraps a string in the [raw][/raw] tags if Theme Forest themes are in use
         *
         * @param string $inStr
         * @return string
         */
        function rawDeal($inStr) {

            // Re-check this because constructor is often too early for this
            //
            if(!$this->usingThemeForest) { $this->usingThemeForest = function_exists('webtreats_formatter'); }

            if ( $this->usingThemeForest ) {
                return '[raw]'.$inStr.'[/raw]';
            } else {
                return $inStr;
            }
        }


        /*************************************
         * method: slp_render_search_form()
         *
         * Render the search form for the map.
         */
        function slp_render_search_form() {
            echo apply_filters('slp_search_form_html',get_string_from_phpexec(SLPLUS_COREDIR . 'templates/search_form.php'));
        }


        /*************************************
         * method: slp_render_search_form_tag_list()
         *
         * Puts the tag list on the search form for users to select tags.
         */
        function slp_render_search_form_tag_list($tags,$showany = false) {
            print "<select id='tag_to_search_for' >";

            // Show Any Option (blank value)
            //
            if ($showany) {
                print "<option value=''>".
                    __('Any',SLPLUS_PREFIX).
                    '</option>';
            }

            foreach ($tags as $selection) {
                $clean_selection = preg_replace('/\((.*)\)/','$1',$selection);
                print "<option value='$clean_selection' ";
                print (preg_match('#\(.*\)#', $selection))? " selected='selected' " : '';
                print ">$clean_selection</option>";
            }
            print "</select>";
        }
    }
}        
     


if (! class_exists('SLPlus_UI_DivManager')) {
    class SLPlus_UI_DivManager {

        function DivStr($str1, $str2) {
            return $str1.$str2;
        }

        function buildDiv10($blank) {
            global $slp_thishtml_10;
            return $this->DivStr($blank,$slp_thishtml_10);
        }

        function buildDiv20($blank) {
            global $slp_thishtml_20;
            return $this->DivStr($blank,$slp_thishtml_20);
        }

        function buildDiv30($blank) {
            global $slp_thishtml_30;
            return $this->DivStr($blank,$slp_thishtml_30);
        }

        function buildDiv40($blank) {
            global $slp_thishtml_40;
            return $this->DivStr($blank,$slp_thishtml_40);
        }

        function buildDiv50($blank) {
            global $slp_thishtml_50;
            return $this->DivStr($blank,$slp_thishtml_50);
        }

        function buildDiv60($blank) {
            global $slp_thishtml_60;
            return $this->DivStr($blank,$slp_thishtml_60);
        }

        function buildDiv70($blank) {
            global $slp_thishtml_70;
            return $this->DivStr($blank,$slp_thishtml_70);
        }

        function buildDiv80($blank) {
            global $slp_thishtml_80;
            return $this->DivStr($blank,$slp_thishtml_80);
        }

        function buildDiv90($blank) {
            global $slp_thishtml_90;
            return $this->DivStr($blank,$slp_thishtml_90);
        }
    }
}