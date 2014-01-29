<?php
/**
 * Helper, non-critical methods to make WordPress plugins easier to manage.
 *
 * Mostly does things like execute and output PHP files as strings or direct
 * output to the "screen" to facilitate PHP template files.  More will come
 * over time.
 *
 * @author Lance Cleveland <lance@lancecleveland.com>
 * @copyright (c) 2013, Lance Cleveland
 *
 * @since 2.0.0
 * @version 2.0.13
 *
 * @package wpCSL\wpCSL_helper
 */


class wpCSL_helper__slplus {

    /**
     * Has the create_SimpleMessage deprecation notice been shown already?
     * 
     * @var boolean $depnotice_create_SimpleMessage
     */
    private  $depnotice_create_SimpleMessage = false;

    /**
     * The parent wpCSL object.
     * 
     * @var \wpCSL_plugin__slplus $parent
     */
    private $parent;

    /**
     *
     * @param mixed[] $params
     */
    function __construct($params=null) {

        // Set by incoming parameters
        //
        foreach ($params as $name => $value) {
            $this->$name = $value;
        }

        // Override incoming parameters

    }

    /**
     * Generate the HTML for a drop down settings interface element.
     *
     * @params mixed[] $params
     * @return string HTML
     */
    function createstring_DropDownDiv($params) {
        return
            "<div class='form_entry'>".
                "<div class='".$this->parent->css_prefix."-input'>" .
                "<label  for='{$params['name']}'>{$params['label']}:</label>".
                $this->createstring_DropDownMenu($params) .
                "</div>".
                $this->CreateHelpDiv($params['name'],$params['helptext']) .
            "</div>"
            ;
        }

    /**
     * Create the bulk actions block for the top-of-table navigation.
     *
     * $params is a named array:
     *
     * The drop down components:
     *
     * string  $params['id'] the ID that goes in the select tag, defaults to 'actionType'
     *
     * string  $params['name'] the name that goes in the select tag, defaults to 'action'
     *
     * string  $params['onchange'] JavaScript to run on select change.
     *
     * string  $params['selectedVal'] if the item value matches this param, mark it as selected
     *
     * mixed[] $params['items'] the named array of drop down elements
     *
     *     $params['items'] is an array of named arrays:
     *
     *         string  $params['items'][0]['label'] the label to put in the drop down selection
     *
     *         string  $params['items'][0]['value'] the value of the option
     *
     *         boolean $params['items'][0]['selected] true of selected
     *
     * @param mixed[] $params a named array of the drivers for this method.
     * @return string the HTML for the drop down with a button beside it
     *
     */
    function createstring_DropDownMenu($params) {
        if (!isset($params['items'      ]) || !is_array($params['items'])) { return; }

        if (!isset($params['id'         ])) { $params['id'          ] = 'actionType'                ; }
        if (!isset($params['name'       ])) { $params['name'        ] = 'action'                    ; }
        if (!isset($params['onchange'   ])) { $params['onchange'    ] = ''                          ; }
        if (!isset($params['selectedVal'])) { $params['selectedVal' ] = ''                          ; }

        // Drop down menu
        //
        $dropdownHTML = '';
        foreach ($params['items'] as $item) {
            if (!isset($item['label'])|| empty($item['label'])) { continue; }
            if (!isset($item['value'])) { $item['value'] = $item['label']; }
            if ($item['value'] === $params['selectedVal']) { $item['selected'] = true; }
            $selected = (isset($item['selected']) && $item['selected']) ? 'selected="selected" ' : '';
            $dropdownHTML .= "<option $selected value='{$item['value']}'>{$item['label']}</option>";
        }
        return
            "<select id='{$params['id']}' name='{$params['name']}' "                .
                (!empty($params['onchange'])?'onChange="'.$params['onchange'].'"':'').
                '>'         .
            $dropdownHTML   .
            '</select>'
            ;
    }

    /**
     * Create the bulk actions block for the top-of-table navigation.
     * 
     * $params is a named array:
     *
     * The drop down components:
     *
     * string  $params['id'] the ID that goes in the select tag, defaults to 'actionType'
     * string  $params['name'] the name that goes in the select tag, defaults to 'action'
     * string  $params['onchange'] JavaScript to run on select change.
     * mixed[] $params['items'] the named array of drop down elements
     *     $params['items'] is an array of named arrays:
     *         string  $params['items'][0]['label'] the label to put in the drop down selection
     *         string  $params['items'][0]['value'] the value of the option
     *         boolean $params['items'][0]['selected] true of selected
     *
     * string  $params['buttonLabel'] the text that goes on the accompanying button, defaults to 'Apply'
     * string  $params['onclick'] JavaScript to run on button click.
     *
     * @param mixed[] $params a named array of the drivers for this method.
     * @return string the HTML for the drop down with a button beside it
     *
     */
    function createstring_DropDownMenuWithButton($params) {
        if (!isset($params['items'      ]) || !is_array($params['items'])) { return; }

        if (!isset($params['id'         ])) { $params['id'          ] = 'actionType'                ; }
        if (!isset($params['name'       ])) { $params['name'        ] = 'action'                    ; }
        if (!isset($params['buttonlabel'])) { $params['buttonlabel' ] = __('Apply'      ,'wpcsl')   ; }
        if (!isset($params['onchange'   ])) { $params['onchange'    ] = ''                          ; }
        if (!isset($params['onclick'    ])) { $params['onclick'     ] = ''                          ; }

        // Drop down menu
        //
        $dropdownHTML = $this->createstring_DropDownMenu($params);

        // Button
        //
        $submitButton =
                '<input id="doaction_'.$params['id'].'" class="button action" type="submit" '         .
                    'value="'.$params['buttonlabel'].'" name="" '                       .
                    (!empty($params['onclick'])?'onClick="'.$params['onclick'].'"':'')  .
                    '/>'
                    ;

        // Render The Div
        //
        return
            '<div class="alignleft actions">'   .
                $dropdownHTML                   .
                $submitButton                   .
            '</div>'
            ;
    }

        /**
         * Create a help div next to a settings entry.
         *
         * @param string $divname - name of the div
         * @param string $msg - the message to dislpay
         * @return string - the HTML
         */
        function CreateHelpDiv($divname,$msg) {
            $jqDivName = str_replace(']','\\\\]',str_replace('[','\\\\[',$divname));
            $moreInfoText = esc_html($msg);
            return
                "<a class='wpcsl-helpicon' ".
                    "onclick=\"jQuery('div#{$this->parent->css_prefix}-help{$jqDivName}').toggle('slow');\" ".
                    "href=\"javascript:;\" ".
                    "alt='{$moreInfoText}' title='{$moreInfoText}'" .
                    '>'.
                "</a>".
                "<div id='{$this->parent->css_prefix}-help{$divname}' class='input_note wpcsl_helptext' style='display: none;'>".
                    $msg.
                "</div>"
                ;

            }

        /**
         * Generate the HTML for a sub-heading label in a settings panel.
         *
         * @param string $label
         * @return string HTML
         */
        function create_SubheadingLabel($label) {
            return "<p class='slp_admin_info'><strong>$label</strong></p>";
        }

        /**
         * Generate the HTML for a checkbox settings interface element.
         *
         * @param string $boxname - the name of the checkbox (db option name)
         * @param string $label - default '', the label to go in front of the checkbox
         * @param string $msg - default '', the help message
         * @param string $prefix - defaults to SLPLUS_PREFIX, can be ''
         * @param boolean $disabled - defaults to false
         * @param mixed $default
         * @param mixed $checkOption - if present, test this variable == 1 to mark as checked otherwise get the boxname option.
         * @return type
         */
        function CreateCheckboxDiv($boxname,$label='',$msg='',$prefix=null, $disabled=false, $default=0, $checkOption = null) {
            if ($prefix === null) { $prefix = $this->parent->prefix; }
            $whichbox = $prefix.$boxname;
            if ($checkOption === null) { $checkOption = get_option($whichbox,$default); }
            return
                "<div class='form_entry'>".
                    "<div class='".$this->parent->css_prefix."-input'>" .
                    "<label  for='$whichbox' ".
                        ($disabled?"class='disabled '":' ').
                        ">$label:</label>".
                    "<input name='$whichbox' value='1' ".
                        "type='checkbox' ".
                        (($checkOption ==1)?' checked ':' ').
                        ($disabled?"disabled='disabled'":' ') .
                    ">".
                    "</div>".
                    $this->CreateHelpDiv($boxname,$msg) .
                "</div>"
                ;
            }

    /**
     * function: SavePostToOptionsTable
     */


    function SavePostToOptionsTable($optionname,$default=null,$cboptions=null) {
        if ($default != null) {
            if (!isset($_POST[$optionname])) {
                $_POST[$optionname] = $default;
            }
        }

        // Save the option
        //
        if (isset($_POST[$optionname])) {
            $optionValue = $_POST[$optionname];

            // Checkbox Pre-processor
            //
            if ($cboptions !== null){
                foreach ($cboptions as $cbname) {
                    if (!isset($optionValue[$cbname])) {
                        $optionValue[$cbname] = '0';
                    }
                }
            }

            $optionValue = stripslashes_deep($optionValue);
            update_option($optionname,$optionValue);
        }
    }

    /**************************************
     ** function: SaveCheckboxToDB
     **
     ** Update the checkbox setting in the database.
     **
     ** Parameters:
     **  $boxname (string, required) - the name of the checkbox (db option name)
     **  $prefix (string, optional) - defaults to SLPLUS_PREFIX, can be ''
     **/
    function SaveCheckboxToDB($boxname,$prefix = null, $separator='-') {
        if ($prefix === null) { $prefix = $this->parent->prefix; }
        $whichbox = $prefix.$separator.$boxname;
        $_POST[$whichbox] = (isset($_POST[$whichbox])&&!empty($_POST[$whichbox]))?1:0;
        $this->SavePostToOptionsTable($whichbox,0);
    }

    /**
     * Check if an item exists out there in the "ether".
     *
     * @param string $url - preferably a fully qualified URL
     * @return boolean - true if it is out there somewhere
     */
    function webItemExists($url) {
        if (($url == '') || ($url === null)) { return false; }
        $response = wp_remote_head( $url, array( 'timeout' => 5 ) );
        $accepted_status_codes = array( 200, 301, 302 );
        if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes ) ) {
            return true;
        }
        return false;
    }

    /**
     * Set an extended data attribute if it is not already set.
     *
     * Puts info in the data[] named array for the object base on
     * the results returned by the passed function.
     *
     * $function must be:
     *    'get_option'  - where the element is the data name, params[0] or params = FULL database option name
     *    'get_item'    - where the element is the data name, params[0] or params = base option name (prefix & hyphen are prepended)
     *    anon function - anon function returns a value which goes into data[]
     *
     * If $params is null and function is get_item the param will fetch the option = to the element name.
     *
     * @param string $element - the key for the data named array
     * @param mixed $function - the string 'get_option','get_item' or a pointer to anon function
     * @param mixed $params - an array of parameters to pass to get_option or the anon, note: get_option can receive an array of option_name, default value
     * @param mixed $default - default value for 'get_item' calls
     * @param boolean $forceReload - if set, reload the data element from the options table
     * @param boolean $cantBeEmpty - if set and the data is empty, set it to default
     * @return the value
     */
    function getData($element = null, $function = null, $params=null, $default=null, $forceReload = false, $cantBeEmpty = false) {
        if ($element  === null) { return; }
        if ($function === null) { return; }
        if (!isset($this->parent->data[$element] ) || $forceReload) {

           // get_option shortcut, fetch the option named by params
           //
           if ($function === 'get_option') {
               if (is_array($params)) {
                    $this->parent->data[$element] = get_option($params[0],$params[1]);
                } else {
                    if ($params === null) { $params = $element; }
                    $this->parent->data[$element] =
                        ($default == null) ?
                            get_option($params) :
                            get_option($params,$default);
                }

           // get_item shortcut
           //
           } else if ($function === 'get_item') {
               if (is_array($params)) {
                    $this->parent->data[$element] = $this->parent->settings->get_item($params[0],$params[1],'-',$forceReload);
                } else {
                    if ($params === null) { $params = $element; }
                    $this->parent->data[$element] = $this->parent->settings->get_item($params,$default,'-',$forceReload);
                }


           // If not using get_option, assume $function is an anon and run it
           //
           } else {
                $this->parent->data[$element] = $function($params);
           }
       }

       // Cant Be Empty?
       //
       if (($cantBeEmpty) && empty($this->parent->data[$element])) {
           $this->parent->data[$element] = $default;
       }

       return esc_html($this->parent->data[$element]);
    }

    /**
     * Initialize the plugin data.
     *
     * Loop through the getData() method passing in each element of the plugin dataElements array.
     * Each entry of dataElements() must contain 3 parts:
     *    [0] = key name for the plugin data element
     *    [1] = function type 'get_option' or 'get_item'
     *    [2] = the name of the option/item as a single string
     *            OR
     *          an array with the name of the option/item first, the default value second
     *
     */
    function loadPluginData() {
        if (!isset($this->parent->dataElements)) {
            $this->parent->dataElements = array();
        }
        $this->parent->dataElements = apply_filters('wpcsl_loadplugindata__slplus',$this->parent->dataElements);
        if (count($this->parent->dataElements) > 0) {
            foreach ($this->parent->dataElements as $element) {
                $this->getData($element[0],$element[1],$element[2]);
            }
        }
    }

     //------------------------------------------------------------------------
     // DEPRECATED
     //------------------------------------------------------------------------

     /**
      * Do not use, deprecated.
      *
      * @deprecated 4.0
      */
     function create_SimpleMessage() {
        if (!$this->depnotice_create_SimpleMessage) {
            $this->parent->notifications->add_notice(9,$this->parent->createstring_Deprecated(__FUNCTION__));
            $this->parent->notifications->display();
            $this->depnotice_create_SimpleMessage = true;
        }
     }

}
