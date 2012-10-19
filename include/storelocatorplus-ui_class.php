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
        
        /*************************************
         * The Constructor
         */
        function __construct($params) {
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