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
            echo get_string_from_phpexec(SLPLUS_COREDIR . 'templates/search_form.php');
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
                print (ereg("\(.*\)", $selection))? " selected='selected' " : '';
                print ">$clean_selection</option>";
            }
            print "</select>";
        }
    }
}        
     

