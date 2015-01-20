<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Store Locator Plus manage locations admin user interface.
 *
 * @package StoreLocatorPlus\AdminUI\Locations
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
 *
 * @var mixed[] $columns our column headers
 */
class SLPlus_AdminUI_Locations extends WP_List_Table {


    //----------------------------------
    // Properties
    //----------------------------------
    public $addingLocation = false;

    /**
     * The current request URL without order by or sorting parameters.
     *
     * @var string $cleanURL
     */
    private $cleanURL;

    /**
     * The extended column meta data in a stdClass object.
     *
     * Properties:
     * - id = the unique id for this field
     * - field_id = the unique id as a string field_###
     * - label = the proper case label
     * - slug = the "slugified" version of the label
     * - type = the field type varchar(default)/text/int/boolean
     * - options (serialized)
     *
     * @var mixed[] $extended_data_info
     */
    private $extended_data_info;

    /**
     * Array of our Manage Locations interface column names.
     *
     * key is the field name, value is the column title
     * 
     * @var mixed[] $columns
     */
    public $columns = array();

    /**
     * The current action as determined by the incoming $_REQUEST['act'] string.
     *
     * @var string $current_action
     */
    private $current_action;

    /**
     * The id string to show for this location.
     *
     * @var string $idString
     */
    private $idString;

    /**
     * The wpCSL settings object that helps render location settings.
     *
     * @var wpCSL_settings__slplus $settings
     */
    public $settings;

    /**
     * The SLPlus plugin object.
     *
     * @var SLPlus $plugin
     */
    private $slplus;

    /**
     *
     * @var string $baseAdminURL
     */
    public $baseAdminURL = '';

    /**
     *
     * @var string $cleanAdminURl
     */
    public $cleanAdminURL = '';

    /**
     * The order by direction for the order by clause.
     * 
     * @var string
     */
    private $db_orderbydir = '';

    /**
     * Order by field for the order by clause.
     * 
     * @var string
     */
    private $db_orderbyfield = '';

    /**
     * Where clause for the location selections.
     * 
     * @var string
     */
    private $db_where = '';

    /**
     * The manage locations URL with params we like to keep such as page number and sort order.
     * 
     * @var string $hangoverURL
     */
    public $hangoverURL = '';

    /**
     * Start listing locations from this record offset.
     *
     * @var int
     */
    private $start = 0;

    /**
     * Total locations on list.
     * 
     * @var int
     */
    private $totalLocations = 0;

    //------------------------------------------------------
    // METHODS
    //------------------------------------------------------

    /**
     * Called when this object is created.
     *
     * @param mixed[] $params
     */
    function SLPlus_AdminUI_Locations() {
        global $slplus_plugin;
        $this->slplus = $slplus_plugin;
        $this->set_CurrentAction();

        // Extended Data Hooks and Filters
        //
        if ( $this->slplus->database->is_Extended() && $this->slplus->database->extension->has_ExtendedData() ) {

            // SLP Action Hooks
            //
            // slp_location_added : update extendo data when adding a location
            // slp_location_saved : update extendo data when changing a location
            //
            add_action('slp_location_added'             ,array($this,'action_SaveExtendedData'                          )           );
            add_action('slp_location_saved'             ,array($this,'action_SaveExtendedData'                          )           );
            add_action('slp_deletelocation_starting'    ,array($this,'action_DeleteExtendedData'                        )           );

            // SLP Filters
            //
            // slp_edit_location_right_column : add fields to location add/edit form
            // slp_manage_location_columns : show fields on the locations list table
            // slp_column_data : manipulate per-location data when it is rendered in the locations list table
            //
            add_filter('slp_edit_location_right_column'         ,array($this,'filter_AddExtendedDataToEditForm'                 ),05        );
            add_filter('slp_manage_expanded_location_columns'   ,array($this,'filter_AddExtendedDataToLocationColumns'          )           );
            add_filter('slp_column_data'                        ,array($this,'filter_ShowExtendedDataInColumns'                 ),80    ,3  );
        }

        // Set our base Admin URL
        //
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->cleanAdminURL =
                isset($_SERVER['QUERY_STRING'])?
                    str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) :
                    $_SERVER['REQUEST_URI']
                    ;

            $queryParams = array();

            // Base Admin URL = must have params
            //
            if (isset($_REQUEST['page'])) { $queryParams['page'] = $_REQUEST['page']; }
            $this->baseAdminURL = $this->cleanAdminURL . '?' . build_query($queryParams);


            // Hangover URL = params we like to carry around sometimes
            //
            if ( $this->current_action === 'show_all' ){
                $_REQUEST['searchfor'] = '';
            }
            if (isset($_REQUEST['searchfor' ]) && !empty($_REQUEST['searchfor']     )){
                $queryParams['searchfor']  = $_REQUEST['searchfor'];
            }
            if (isset($_REQUEST['start'     ]) && ((int)$_REQUEST['start'] >= 0     )){
                $queryParams['start']      = $_REQUEST['start']    ;
            }
            if (isset($_REQUEST['orderBy'   ]) && !empty($_REQUEST['orderBy']       )) {
                $queryParams['orderBy'] = $_REQUEST['orderBy'];
            }
            if (isset($_REQUEST['sortorder' ]) && !empty($_REQUEST['sortorder']     )) {
                $queryParams['sortorder'] = $_REQUEST['sortorder'];
            }

            $this->hangoverURL = $this->cleanAdminURL . '?' . build_query($queryParams);

            $this->slplus->debugMP('slp.managelocs','msg',__FUNCTION__);
            $this->slplus->debugMP('slp.managelocs','msg','','cleanAdminURL: '.$this->cleanAdminURL);
            $this->slplus->debugMP('slp.managelocs','msg','','baseAdminURL:  '.$this->baseAdminURL);

            // Create a standard wpCSL settings interface.
            // It has better UI management features than the custom versions prevelant in legacy code.
            //
            $this->settings = new wpCSL_settings__slplus(
                array(
                        'parent'            => $this->slplus,
                        'prefix'            => $this->slplus->prefix,
                        'css_prefix'        => $this->slplus->prefix,
                        'url'               => $this->slplus->url,
                        'name'              => $this->slplus->name . __(' - Locations','csa-slplus'),
                        'plugin_url'        => $this->slplus->plugin_url,
                        'render_csl_blocks' => false,
                        'form_action'       => $this->baseAdminURL,
                        'no_save_button'    => true,
                        'form_name'         => 'locationForm'
                    )
             );
        }
    }

    /**
     * Set the columns we will render on the manage locations page.
     */
    function set_Columns() {

        // For all views
        //
        $this->columns = array(
                'sl_store'      =>  __('Name'     ,'csa-slplus'),
                'address'       =>  __('Address'  ,'csa-slplus'),
            );

        // FILTER: slp_manage_normal_location_columns - add columns to normal view on manage locations
        //
        $this->columns = apply_filters('slp_manage_priority_location_columns', $this->columns);

        // Expanded View
        //
        if (get_option('sl_location_table_view')!="Normal") {
            $this->columns = array_merge($this->columns,
                        array(
                            'sl_description'=> __('Description'  ,'csa-slplus'),

                            'sl_email'      =>
                                $this->slplus->WPML->getWPMLText(
                                    'label_email' ,
                                    $this->slplus->options['label_email']
                                ) ,
                            'sl_url'        =>
                                $this->slplus->WPML->getWPMLText(
                                    'sl_website_label' ,
                                     get_option( 'sl_website_label', __('Website','csa-slplus') )
                                     ) ,
                            'sl_hours'      =>
                                $this->slplus->WPML->getWPMLText(
                                    'label_hours' ,
                                    $this->slplus->settings->get_item( 'label_hours' , __('Hours','csa-slplus') , '_' )
                                    ) ,
                            'sl_phone'      => 
                                $this->slplus->WPML->getWPMLText(
                                    'label_phone' ,
                                    $this->slplus->settings->get_item( 'label_phone' , __('Phone','csa-slplus') , '_' )
                                    ) ,
                            'sl_fax'        =>
                                $this->slplus->WPML->getWPMLText(
                                    'label_fax' ,
                                    $this->slplus->settings->get_item( 'label_fax'  , __('Fax','csa-slplus')  , '_' )
                                    ) ,
                        )
                    );
            // FILTER: slp_manage_expanded_location_columns - add columns to expanded view on manage locations
            //
            $this->columns = apply_filters('slp_manage_expanded_location_columns', $this->columns);

        }

        // For all views, add-ons go on the end by default.
        // FILTER: slp_manage_location_columns - add columns to normal and expanded view on manage locations
        //
        $this->columns = apply_filters('slp_manage_location_columns', $this->columns);
    }

    /**
     * Set the current action being executed by the plugin.
     */
    function set_CurrentAction() {
        if ( !isset( $_REQUEST['act'] ) ) { $this->current_action = '';                               }
        else                              { $this->current_action = strtolower( $_REQUEST['act'] );   }

        // Special Processing of Actions
        //
        switch ($this->current_action) {
            case 'edit':
                if ( ! $this->slplus->currentLocation->isvalid_ID( null, 'id' ) ) {
                    $this->current_action = 'manage';
                }
                break;

            default:
                break;
        }
    }

    /**
     * Set all the properties that manage the location query.
     * 
     */
    function set_LocationQueryProperties() {
        $this->slplus->debugMP('slp.managelocs','msg', get_class() . ':: ' . __FUNCTION__ );

        // Where Clause
        //
        $this->db_where = '';
         if ( isset( $_REQUEST['searchfor'] ) ) {
            $clean_search_for = trim($_REQUEST['searchfor']);
            if ( ! empty ( $clean_search_for ) ) {
                $this->db_where = " CONCAT_WS(';',sl_store,sl_address,sl_address2,sl_city,sl_state,sl_zip,sl_country,sl_tags) LIKE '%{$clean_search_for}%'";
            }
        }

        // FILTER: slp_manage_location_where
        //
        $this->db_where = apply_filters('slp_manage_location_where',$this->db_where);

        if (trim($this->db_where) != '') { $this->db_where = "WHERE {$this->db_where}"; }

        // Sort Direction
        //
        $this->db_orderbyfield  =
            (isset($_REQUEST['orderBy'   ]) && !empty($_REQUEST['orderBy'     ])) ?
            $_REQUEST['orderBy'  ]                                                :
            'sl_store'                                                            ;
        $this->db_orderbydir    =
            (isset($_REQUEST['sortorder' ]) && !empty($_REQUEST['sortorder'   ])) ?
            $_REQUEST['sortorder']                                                :
            'asc'                                                                 ;

        // Get the sort order and direction out of our URL
        //
        $this->cleanURL = preg_replace('/&orderBy=\w*&sortorder=\w*/i','',$_SERVER['REQUEST_URI']);

        $dataQuery =
            $this->slplus->database->get_SQL('selectall') .
            $this->db_where;
        $dataQuery = str_replace('*','count(sl_id)',$dataQuery);
        $this->totalLocations = $this->slplus->db->get_var($dataQuery);	


        // Starting Location (Page)
        //
        // Search Filter, no actions, start from beginning
        //
        if (isset($_POST['searchfor']) && !empty($_POST['searchfor']) && ( $this->current_action === '' ) ) {
            $this->start = 0;

        // Set start to selected page..
        // Adjust start if past end of location count.
        //
        } else {
            $this->start =
                (isset($_REQUEST['start']) && ctype_digit($_REQUEST['start']) && ((int)$_REQUEST['start']>=0))  ?
                (int)$_REQUEST['start']                                                                         :
                0                                                                                               ;

            if ($this->start > ($this->totalLocations-1)) {
                $this->start = max($this->totalLocations - 1,0);
                $this->hangoverURL = str_replace('&start=','&prevstart=',$this->hangoverURL);
            }
        }
    }

    /**
     * Escape a string to match WordPress display conventions.
     * 
     * @param string $a
     * @return string
     */
    function slp_escape($a) {
        $a=preg_replace("/'/"     , '&#39;'   , $a);
        $a=preg_replace('/"/'     , '&quot;'  , $a);
        $a=preg_replace('/>/'     , '&gt;'    , $a);
        $a=preg_replace('/</'     , '&lt;'    , $a);
        $a=preg_replace('/,/'     , '&#44;'   , $a);
        $a=preg_replace('/ & /'   , ' &amp; ' , $a);
        return $a;
    }


    /**
     * Delete extended data records.
     */
    function action_DeleteExtendedData() {
        $this->slplus->db->delete(
            $this->slplus->database->extension->plugintable['name'],
            array( 'sl_id' => $this->slplus->currentLocation->id )
         );
    }

    /**
     * Save the extended data.
     */
    function action_SaveExtendedData() {

        $action = isset( $_REQUEST['act'] ) ? $_REQUEST['act'] : '';

        // Check our extended column info and see if there is a matching property in exdata in currentLocation
        //
        $this->get_ExtendedDataInfo();
        $newValues = array();
        foreach($this->extended_data_info as $extraColumn) {
            $slug = $extraColumn->slug;

            // Boolean force (off bools are not sent in request)
            //
            if ( $extraColumn->type === 'boolean' ) {
                $boolREQField = $slug . '-' . ( ( $action === 'add' ) ? '' : $this->slplus->currentLocation->id );
                $newValues[$slug] = empty($_REQUEST[$boolREQField]) ? 0 : 1;
                $this->slplus->currentLocation->$slug = $newValues[$slug];
            } else {
                $newValues[$slug] =
                    isset($this->slplus->currentLocation->exdata[$slug]) ?
	                    $this->slplus->currentLocation->exdata[$slug]    :
                        ''                                               ;
            }
        }

        // New values?  Write them to disk...
        //
        if (count($newValues) > 0){
            $this->slplus->database->extension->update_data($this->slplus->currentLocation->id, $newValues);
        }
    }


     /**
      * Returns the string that is the Location Info Form guts.
      *
      * @param bool $addform - true if rendering add locations form
      */
     function create_LocationAddEditForm($addform=false) {
        $this->slplus->debugMP('slp.managelocs','msg',__FUNCTION__,($addform?'add':'edit').' mode.');
        $this->addingLocation = $addform;

        // Add form
        //
        if ($addform) {
            $this->slplus->debugMP('slp.managelocs','msg','set location data to blank...','',NULL,NULL,true);
            $this->slplus->currentLocation->reset();
            $this->idString = '';
            
        // Setup current location based in incoming request data
        //
        } else {
            $this->idString =
                    $this->slplus->currentLocation->id .
                    (!empty($this->slplus->currentLocation->linked_postid)?
                     ' - '. $this->slplus->currentLocation->linked_postid :
                     ''
                     );
            if (
                    is_numeric($this->slplus->currentLocation->latitude) &&
                    is_numeric($this->slplus->currentLocation->longitude)
               ) {
                $this->idString .= __(' at ','csa-slplus').$this->slplus->currentLocation->latitude.','.$this->slplus->currentLocation->longitude;
            }
        }

        // Hook in our filters that generate the form.
        //
        add_filter('slp_edit_location_left_column'  ,array($this,'filter_EditLocationLeft_Address')   , 5);
        add_filter('slp_edit_location_left_column'  ,array($this,'filter_EditLocationLeft_Submit')    ,99);
        add_filter('slp_edit_location_right_column' ,array($this,'filter_EditLocationRight_Address')  , 5);
        add_filter('slp_edit_location_right_column' ,array($this,'filter_EditLocationLeft_Submit')    ,99);

        // Create the form.
        //
        $content  =
           "<form id='manualAddForm' name='manualAddForm' method='post'>" .
           ($addform?'<input type="hidden" id="act" name="act" value="add" />':'<input type="hidden" id="act" name="act" value="edit" />') .
           "<input type='hidden' name='id' "                                                            .
                "id='id' value='{$this->slplus->currentLocation->id}' />"                               .
           "<input type='hidden' name='locationID' "                                                    .
                "id='locationID' value='{$this->slplus->currentLocation->id}' />"                       .
           "<input type='hidden' name='linked_postid-{$this->slplus->currentLocation->id}' "            .
                "id='linked_postid-{$this->slplus->currentLocation->id}' value='"                       .
                $this->slplus->currentLocation->linked_postid                                           .
                "' />"                                                                                  .
           ( isset($_REQUEST['start'])  ? "<input type='hidden' name='start' id='start' value='{$_REQUEST['start']}' />" : '' ).
           "<a name='a{$this->slplus->currentLocation->id}'></a>"                                       .
           "<table cellpadding='0' class='slp_locationinfoform_table'>"                                 .
           "<tr>"                                                                                       .

           // Left Cell
           "<td id='slp_manual_update_table_left_cell' valign='top'>"                                   .
                "<div id='slp_edit_left_column' class='add_location_form'>"                             .

                    // FILTER: slp_edit_location_left_column
                    apply_filters('slp_edit_location_left_column','')                                   .

                    // FILTER: slp_edit_location_right_column
                    apply_filters('slp_edit_location_right_column','')                                  .

                '</div>'                                                                                .
           '</td>'                                                                                      .
           '</tr></table>'                                                                              .

            // FILTER: slp_add_location_form_footer
            //
            ($this->addingLocation?apply_filters('slp_add_location_form_footer', ''):'')                .

            '</form>'
            ;

           // FILTER: slp_locationinfoform
           //
          return apply_filters('slp_locationinfoform',$content);
     }

     /**
      * Create an inner content panel wrapped in proper WPCSL subnav divs.
      *
      * @param string $cleanLabel
      * @param string $content
      * @param string $display
      * @return string
      */
    function createstring_SubContentPanel($cleanLabel,$content,$display='none') {
        $divID = 'wpcsl-option-'.strtolower($cleanLabel);
        return
                "<div id='{$divID}' class='group' style='display:{$display};'>"   .
                    '<div class="inside section">'                                                  .
                        $content                                                                    .
                    '</div>'                                                                        .
                '</div>'
                ;
    }

    /**
     * Add a sidebar tab entry.
     * 
     * @param string $label
     * @param string $moreclass
     * @return string
     */
    function create_SubTab($label,$moreclass='') {
        $cleanLabel = strtolower(str_replace(' ','_',$label));
        return "<li class='top-level general {$moreclass}'>".
                "<a title='$label' href='#wpcsl-option-{$cleanLabel}'>{$label}</a>".
               '</li>';
    }

    //-------------------------------------
    // createstring Methods
    //-------------------------------------

    /**
     * Create HTML string for hidden inputs we need to keep track of filters, etc.
     *
     * @return string $HTML
     */
    function createstring_HiddenInputs() {
        $html = '';
        $onlyHide = array('start');
        foreach($_REQUEST as $key=>$val) {
            if (!in_array($key,$onlyHide,true)) { continue; }
            $html.="<input type='hidden' value='$val' id='$key' name='$key'>\n";
        }
        return $html;
    }

    /**
     * Create the add/edit form field.
     *
     * Leave fldLabel blank to eliminate the leading <label>
     *
     * inType can be 'input' (default) or 'textarea'
     *
     * @param string $fldName name of the field, base name only
     * @param string $fldLabel label to show ahead of the input
     * @param string $fldValue
     * @param string $inputclass class for input field
     * @param boolean $noBR skip the <br/> after input
     * @param string $inType type of input field (default:input)
     * @param string $placeholder the placeholder for the input field (default: blank)
     * @return string the form HTML output
     */
    function createstring_InputElement($fldName,$fldLabel,$fldValue, $inputClass='', $noBR = false, $inType='input', $placeholder = '') {
        $matches = array();
        $matchStr = '/(.+)\[(.*)\]/';
        if (preg_match($matchStr,$fldName,$matches)) {
            $fldName = $matches[1];
            $subFldName = '['.$matches[2].']';
        } else {
            $subFldName='';
        }
        return
            (empty($fldLabel)?'':"<label  for='{$fldName}-{$this->slplus->currentLocation->id}{$subFldName}'>{$fldLabel}</label>").
            "<{$inType} "                                                                   .
                "id='edit-{$fldName}-{$this->slplus->currentLocation->id}{$subFldName}' "   .
                "data-field='{$fldName}' ".
                "name='{$fldName}-{$this->slplus->currentLocation->id}{$subFldName}' "      .
                ( empty ( $placeholder ) ? '' : " placeholder='{$placeholder}' " )          .
                (($inType==='input')?
                        "value='".esc_html($fldValue)."' "  :
                        "rows='5' cols='17'  "
                 )                                                          .
                (empty($inputClass)?'':"class='{$inputClass}' ")            .
            '>'                                                             .
            (($inType==='textarea')?esc_textarea($fldValue):'')             .
            (($inType==='textarea')?'</textarea>'   :'')                    .
            ($noBR?'':'<br/>')
            ;
    }

    /**
     * Simplify the plugin debugMP interface.
     *
     * @param string $type
     * @param string $hdr
     * @param string $msg
     */
    function debugMP($type,$hdr,$msg='') {
        $this->slplus->debugMP('slp.managelocs',$type,$hdr,$msg,NULL,NULL,true);
    }

    /**
     * Add the extra information to the edit/add form
     * @param $theform
     */
    function filter_AddExtendedDataToEditForm($theform) {
        $theform .= "<p class='slp_admin_info'>
        <strong>Extended Information</strong></p>
        ";
        $this->get_ExtendedDataInfo();

	    $this->extended_data_info = apply_filters( 'slp_edit_location_change_extended_data_info' , $this->extended_data_info );

        $data =
            ((int)$this->slplus->currentLocation->id > 0)               ?
            $this->slplus->database->extension->get_data($this->slplus->currentLocation->id)   :
            null
            ;

        foreach($this->extended_data_info as $col) {
            $theform .= "<label for='{$col->slug}-{$this->slplus->currentLocation->id}'>{$col->label}</label>";
            $value =
                ( $data == null )               ?
                    ''                          :
                    ( isset( $data[$col->slug] )    ?
                        $data[$col->slug]           :
                        ''
                    )
                ;

            switch ($col->type) {
                case 'boolean':
                    $checked = $this->slplus->is_CheckTrue($value) ? 'checked':'';
                    $theform .=
                        "<input type='checkbox' "                                       .
                            "id='edit-{$col->slug}' "                                   .
                            "name='{$col->slug}-{$this->slplus->currentLocation->id}' " .
                            "$checked "                                                 .
                            "/>";
                    break;


                case 'int'      :
                case 'varchar'  :
                    $value=esc_html($value);
                    $theform .=
                        "<input type='text' "                                           .
                        "id='edit-{$col->slug}' "                                   .
                        "name='{$col->slug}-{$this->slplus->currentLocation->id}' " .
                        "value='$value' "                                           .
                        (($col->type ==='int')?'class="shortfield" ':'') .
                        "/>";
                    break;

                case 'text'      :
                    $value = esc_textarea($value);
                    $theform .=
                        '<textarea '                                                        .
                        "cols='17' "                                                    .
                        "rows='5' "                                                     .
                        "id='edit-{$col->slug}' "                                     .
                        "name='{$col->slug}-{$this->slplus->currentLocation->id}' "   .
                        "data-field='{$col->slug}' "                                  .
                        '>'                                                             .
                        $value                                                          .
                        '</textarea>'                                                       ;
                    break;
            }
            $theform .= "<br>";
        }

        return $theform;
    }

    /**
     * Adds the new columns created by extendo
     * @param $current_cols array The current columns
     */
    function filter_AddExtendedDataToLocationColumns($current_cols) {
        $this->get_ExtendedDataInfo();
        foreach ($this->extended_data_info as $col) {
            $current_cols[$col->slug] = $col->label;
        }
        return $current_cols;
    }

    /**
     * Add the images column header to the manage locations table.
     *
     * SLP Filter: slp_manage_location_columns
     *
     * @param mixed[] $currentCols column name + column label for existing items
     * @return mixed[] column name + column labels, extended with our extra fields data
     */
    function filter_AddFieldHeadersToManageLocations($currentCols) {
        return array_merge($currentCols,
            array(
                'sl_image'       => __('Image'        ,'csa-slplus'),
            )
        );
    }

    /**
     * Render the extra fields on the manage location table.
     *
     * SLP Filter: slp_column_data
     *
     * @param string $theData  - the option_value field data from the database
     * @param string $theField - the name of the field from the database (should be sl_option_value)
     * @param string $theLabel - the column label for this column (should be 'Categories')
     * @return type
     */
    function filter_AddImageToManageLocations($theData,$theField,$theLabel) {
        if (
            ($theField === 'sl_image') &&
            ($theLabel === __('Image'        ,'csa-slplus')
            )
        ) {
            $theData =
                ( $this->slplus->currentLocation->image != '' )
                    ?
                    "<a href='{$this->slplus->currentLocation->image}' target='blank'>" .
                    sprintf('<img src="%s" alt="%s" title="%s" class="location_image manage_locations" />',
                        $this->slplus->currentLocation->image ,
                        $this->slplus->currentLocation->store . __(' image ' , 'csa-slplus'),
                        $this->slplus->currentLocation->store . __(' image ' , 'csa-slplus')
                    ) .
                    '</a>'
                    :
                    '' ;
        }
        return $theData;
    }

    /**
     * Add the lat/long under the store name.
     * 
     * @param string $field_value
     * @param type $field
     * @param type $label
     * @return type
     */
    function filter_AddLatLongUnderName( $field_value, $field, $label) {
        if ($field === 'sl_store') {
            $commaOrSpace = ($this->slplus->currentLocation->latitude . $this->slplus->currentLocation->longitude!=='')? ',':' ';
            
            if ($commaOrSpace != ' ') {
                $latlong_url = 
                    sprintf('https://%s?saddr=%f,%f', 
                        $this->slplus->options['map_domain'],
                        $this->slplus->currentLocation->latitude,
                        $this->slplus->currentLocation->longitude
                        );
                $latlong_text =
                    sprintf('<a href="%s" target="csa_map">@ %f %s %f</a></span>',
                        $latlong_url,
                        $this->slplus->currentLocation->latitude,
                        $commaOrSpace,
                        $this->slplus->currentLocation->longitude
                        );
            } else {
                $latlong_text = __('Inactive. Geocode to activate.','csa-slplus');
            }
            
            $field_value =
                sprintf('<span class="store_name">%s</span>'.                        
                        '<span class="store_latlong">%s</span>',
                        $field_value,
                        $latlong_text
                        );
        }
        return $field_value;
    }            
    

    /**
     * Add the left column to the add/edit locations form.
     *
     * @param string $HTML the html of the base form.
     * @return string HTML of the form inputs
     */
    function filter_EditLocationLeft_Address($HTML) {
        return
            $this->slplus->helper->create_SubheadingLabel(__('Address','csa-slplus')).
            $this->createstring_InputElement(
                'store',
                __('Name', 'csa-slplus'),
                $this->slplus->currentLocation->store
                ).
            $this->createstring_InputElement(
                'address',
                __('Street - Line 1', 'csa-slplus'),
                $this->slplus->currentLocation->address
                ).
            $this->createstring_InputElement(
                'address2',
                __('Street - Line 2', 'csa-slplus'),
                $this->slplus->currentLocation->address2
                ).
            $this->createstring_InputElement(
                'city',
                __('City, State, ZIP', 'csa-slplus'),
                $this->slplus->currentLocation->city,
                'mediumfield',
                true
                ).
            $this->createstring_InputElement(
                'state',
                '',
                $this->slplus->currentLocation->state,
                'shortfield',
                true
                ).
            $this->createstring_InputElement(
                'zip',
                '',
                $this->slplus->currentLocation->zip,
                'shortfield'
                ).
            $this->createstring_InputElement(
                'country',
                __('Country','csa-slplus'),
                $this->slplus->currentLocation->country
                ).
            $this->createstring_InputElement(
                    'latitude',
                    __('Latitude (N/S)', 'csa-slplus'),
                    $this->slplus->currentLocation->latitude,
                    '', false, 'input',
                    __('Leave blank to have Google look up the latitude.', 'csa-slplus')
                    ).
            $this->createstring_InputElement(
                    'longitude',
                    __('Longitude (E/W)', 'csa-slplus'),
                    $this->slplus->currentLocation->longitude,
                    '', false, 'input',
                    __('Leave blank to have Google look up the longitude.', 'csa-slplus')
                    ).
            '<p class="text_info">' .
                sprintf('<a href="%s" target="csa" alt="%s" title="%s">%s</a>',
                        'http://www.latlong.net',
                        __('Latitude/Longitude lookup.','csa-slplus') ,
                        __('Latitude/Longitude lookup.','csa-slplus') ,
                        __('The LatLong.net website can help you locate an exact latitude/longitude.','csa-slplus') 
                    ) .
            '</p>' .                
            $HTML
            ;
    }

    /**
     * Put the add/cancel button on the add/edit locations form.
     *
     * This is rendered AFTER other HTML stuff.
     *
     * @param string $HTML the html of the base form.
     * @return string HTML of the form inputs
     */
    function filter_EditLocationLeft_Submit($HTML) {
        $edCancelURL = isset( $_REQUEST['id'] ) ?
            preg_replace('/&id='.$_REQUEST['id'].'/', '',$_SERVER['REQUEST_URI']) :
            $_SERVER['REQUEST_URI']
            ;
        $alTitle =
            ($this->addingLocation?
                __('Add Location','csa-slplus'):
                sprintf("%s #%d",__('Update Location', 'csa-slplus'),$this->slplus->currentLocation->id)
            );

        $value   =
                ($this->addingLocation)    ?
                __('Add'   ,'csa-slplus')  :
                __('Update','csa-slplus')  ;

        $onClick =
                ($this->addingLocation)                                       ?
                "AdminUI.doAction('add' ,'','manualAddForm');"    :
                "AdminUI.doAction('save','','locationForm' );"    ;

        return
            $HTML .
            ($this->addingLocation? '' : "<span class='slp-edit-location-id'>Location # $this->idString</span>") .
            "<div id='slp_form_buttons'>"                                                           .
            "<input "                                                                               .
                "type='submit'        "                                                             .
                'value="'  .$value  .'" '                                                           .
                'onClick="'.$onClick.'" '                                                           .
                "' alt='$alTitle' title='$alTitle' class='button-primary'"                          .
                ">"                                                                                 .
            "<input type='button' class='button' "                                                  .
                "value='".__('Cancel', 'csa-slplus')."' "                                           .
                "onclick='location.href=\"".$edCancelURL."\"'>"                                     .
            "<input type='hidden' name='option_value-{$this->slplus->currentLocation->id}' "        .
                "value='".($this->addingLocation?'':$this->slplus->currentLocation->option_value)   .
                "' />"                                                                              .
            "</div>"
            ;
    }

    /**
     * Add the right column to the add/edit locations form.
     *
     * @param string $HTML the html of the base form.
     * @return string HTML of the form inputs
     */
    function filter_EditLocationRight_Address($HTML) {
        return
            $this->slplus->helper->create_SubheadingLabel(__('Additional Information','csa-slplus')).
            $this->createstring_InputElement(
                    'description',
                    __('Description', 'csa-slplus'),
                    $this->slplus->currentLocation->description,
                    '',
                    false,
                    'textarea'
                    ).
            $this->createstring_InputElement(
                    'url',
                    get_option('sl_website_label',__('Website','csa-slplus')),
                    $this->slplus->currentLocation->url
                    ).
            $this->createstring_InputElement(
                    'email',
                    __('Email', 'csa-slplus'),
                    $this->slplus->currentLocation->email
                    ).
            $this->createstring_InputElement(
                    'hours',
                    $this->slplus->settings->get_item('label_hours',__('Hours','csa-slplus'),'_'),
                    $this->slplus->currentLocation->hours,
                    '',
                    false,
                    'textarea'
                    ).
            $this->createstring_InputElement(
                    'phone',
                    $this->slplus->settings->get_item('label_phone',__('Phone','csa-slplus'),'_'),
                    $this->slplus->currentLocation->phone
                    ).
            $this->createstring_InputElement(
                    'fax',
                    $this->slplus->settings->get_item('label_fax',__('Fax','csa-slplus'),'_'),
                    $this->slplus->currentLocation->fax
                    ).
            $this->createstring_InputElement(
                    'image',
                    __('Image URL', 'csa-slplus'),
                    $this->slplus->currentLocation->image
                    ) .
            $HTML
            ;
    }

    /**
     * Set the invalid highlighting class.
     * 
     * @param string $class
     * @return string the new class name for invalid rows
     */
    function filter_InvalidHighlight($class) {

        if (($this->slplus->currentLocation->latitude == '') ||
            ($this->slplus->currentLocation->longitude == '')
            ) {
            $class .= ' invalid ';
        }

        // FILTER: slp_invalid_highlight
        //
        return apply_filters('slp_invalid_highlight',$class);
    }

    /**
     * Allows editing of the extendo data for the location
     *
     * @param $thedata - option value field
     * @param $thefield - The name of the field
     * @param $thelabel - The column label
     */
    function filter_ShowExtendedDataInColumns($thedata, $thefield, $thelabel) {
        $this->get_ExtendedDataInfo();
        return $thedata;
    }

    /**
     * Get the extended columns meta data and remember them within this class.
     */
    function get_ExtendedDataInfo() {
        if (!isset($this->extended_data_info)) {
            $this->extended_data_info = $this->slplus->database->extension->get_cols();
        }
    }

    // Add a locations
    //
    function location_Add() {
        $this->debugMP('msg','SLPlus_AdminUI_Locations::'.__FUNCTION__);
        
        //Inserting addresses by manual input
        //
        $locationData = array();
        if ( isset($_POST['store-']) && !empty($_POST['store-'])) {
            foreach ($_POST as $key=>$sl_value) {
                if (preg_match('#\-$#', $key)) {
                    $fieldName='sl_'.preg_replace('#\-$#','',$key);
                    $locationData[$fieldName]=(!empty($sl_value)?$sl_value:'');
                }
            }

            $this->slplus->debugMP('slp.managelocs','pr','location_Add locationData',$locationData,NULL,NULL,true);

            $skipGeocode =
                ( $this->current_action === 'add'     ) &&
                ( isset($locationData['sl_latitude'  ]) && is_numeric($locationData['sl_latitude'  ])    ) &&
                ( isset($locationData['sl_longitude' ]) && is_numeric($locationData['sl_longitude' ])    )
                ;
            $response_code =
                $this->location_AddToDatabase(
                    $locationData,
                    'none',
                    $skipGeocode
                    );

            print "<div class='updated fade'>".
                    stripslashes_deep($_POST['store-']) . ' ' .
                    __('added successfully','csa-slplus') . ' ' .
                    $response_code .
                '.</div>'
                ;

        } else {
            $this->slplus->debugMP('slp.managelocs','pr','location_Add no POST[store-]',$locationData,NULL,NULL,true);
            print "<div class='updated fade'>".
                    __('Location not added.','csa-slplus') . ' ' .
                    __('The add location form on your server is not rendering properly.','csa-slplus') . 
                    '</div>';
        }
    }

    /**
     * Add an address into the SLP locations database.
     *
     * Moved into location class.
     *
     * @param array[] $locationData
     * @param string $duplicates_handling
     * @param boolean $skipGeocode
     * @return string 
     *
     */
    function location_AddToDatabase($locationData,$duplicates_handling='none',$skipGeocode=false) {
        $this->debugMP('msg','SLPlus_AdminUI_Locations::'.__FUNCTION__,
                             "duplicates handling mode: {$duplicates_handling} " . ($skipGeocode?' skip geocode':'')
                      );
        return $this->slplus->currentLocation->add_to_database( $locationData , $duplicates_handling , $skipGeocode );
    }

    /**
     * Save a location.
     */
    function location_save() {
        if ( ! $this->slplus->currentLocation->isvalid_ID( null, 'locationID' ) ) { return; }
        $this->debugMP('msg','SLPlus_AdminUI_Locations::'.__FUNCTION__ . " location # {$_REQUEST['locationID']}");
        $this->slplus->notifications->delete_all_notices();

        // Get our original address first
        //
        $this->slplus->currentLocation->set_PropertiesViaDB($_REQUEST['locationID']);
        $priorIsGeocoded=
            is_numeric($this->slplus->currentLocation->latitude) &&
            is_numeric($this->slplus->currentLocation->longitude)
            ;
        $priorAddress   =
                $this->slplus->currentLocation->address . ' '  .
                $this->slplus->currentLocation->address2. ', ' .
                $this->slplus->currentLocation->city    . ', ' .
                $this->slplus->currentLocation->state   . ' '  .
                $this->slplus->currentLocation->zip
                ;

        // Update The Location Data
        //
        foreach ($_POST as $key=>$value) {
            if (preg_match('#\-'.$this->slplus->currentLocation->id.'#', $key)) {
                $slpFieldName = preg_replace('#\-'.$this->slplus->currentLocation->id.'#', '', $key);
                if (($slpFieldName === 'latitude') || ($slpFieldName === 'longitude')) {
                    if (!is_numeric($value)) { continue; }
                }

                // Has the data changed?
                //
                $stripped_value = stripslashes_deep($value);
                if ($this->slplus->currentLocation->$slpFieldName !== $stripped_value) {
                    $this->slplus->currentLocation->$slpFieldName = $stripped_value;
                    $this->slplus->currentLocation->dataChanged = true;
                }
            }
        }

        // RE-geocode if the address changed
        // or if the lat/long is not set
        //
        $newAddress   =
                $this->slplus->currentLocation->address . ' '  .
                $this->slplus->currentLocation->address2. ', ' .
                $this->slplus->currentLocation->city    . ', ' .
                $this->slplus->currentLocation->state   . ' '  .
                $this->slplus->currentLocation->zip
                ;
        if (   ($newAddress!=$priorAddress) || !$priorIsGeocoded) {
            $this->debugMP('msg','',
                    "Geocoding location # {$this->slplus->currentLocation->id} address: {$newAddress}");
            $this->slplus->currentLocation->do_geocoding($newAddress);
        }

        // Make persistent
        //
        // HOOK: slp_location_save
        //
        do_action('slp_location_save');
        if ($this->slplus->currentLocation->dataChanged) {
            $this->slplus->currentLocation->MakePersistent();
        }
        
        // HOOK: slp_location_saved
        // Stuff that is done after a location has been saved.
        //
        do_action('slp_location_saved');

        // Show Notices
        //
        $this->slplus->notifications->display();
    }
    
    /**
     * Build the action buttons HTML string on the first column of the manage locations panel.
     * 
     * Applies the slp_manage_locations_actionbuttons filter.
     * 
     * @return string
     */
    function createstring_ActionButtons() {
        $buttons_HTML =
            sprintf(
                '<a class="action_icon edit_icon" alt="%s" title="%s" href="%s" onclick="%s"></a>'   ,
                __('edit','csa-slplus')                                                 ,
                __('edit','csa-slplus')                                                 ,
                '#'                                                                     ,
                "jQuery('#id').val('{$this->slplus->currentLocation->id}');".
                "AdminUI.doAction('edit','');"
            ).
            sprintf(
                '<a class="action_icon delete_icon" alt="%s" title="%s" href="%s" onclick="%s"></a>'   ,
                __('delete','csa-slplus')                                                              ,
                __('delete','csa-slplus')                                                              ,
                '#'                                                                                    ,
                "jQuery('#id').val('{$this->slplus->currentLocation->id}');".
                "AdminUI.doAction('delete','".sprintf(__('Delete %s?','csa-slplus'),$this->slplus->currentLocation->store)."'); "                    
            )
            ;

        // FILTER: slp_manage_locations_actionbuttons
        // Build the action buttons HTML string on the first column of the manage locations panel.
        //
        return apply_filters('slp_manage_locations_actionbuttons',$buttons_HTML, (array) $this->slplus->currentLocation->locationData);
        
    }

    /**
     * Create the bulk actions drop down for the top-of-table navigation.
     * 
     */
    function createstring_BulkActionsBlock() {
        
        // Setup the properties array for our drop down.
        //
        $dropdownItems = array(
                array(
                    'label'     =>  __('Bulk Actions','csa-slplus') ,
                    'value'     => '-1'                             ,
                    'selected'  => true
                ),
                array(
                    'label'     =>  __('Delete Permanently','csa-slplus')   ,
                    'value'     => 'delete'                                 ,
                )            
            );

        // FILTER: slp_locations_manage_bulkactions
        //
        $dropdownItems = apply_filters('slp_locations_manage_bulkactions',$dropdownItems);

        // Loop through the action boxes content array
        //
        $baExtras = '';
        foreach ($dropdownItems as $item) {
            if (isset($item['extras']) && !empty($item['extras'])) {
                $baExtras .= $item['extras'];
            }
        }
        
        // Create the box div string.
        //
        $morebox = "'#extra_'+jQuery('#actionType').val()"        ;
        $filter_dialog_title=__('Options','csa-slplus');
        $dialog_options = 
            "appendTo: '#locationForm'      , " .
            "minHeight: 50                  , " .
            "minWidth: 450                  , " .
            "title: jQuery('#actionType option:selected').text()+' $filter_dialog_title'  , " .
            "position: { my: 'left top', at: 'left bottom', of: '#actionType' } "
            ;
        
        // Action confirmation.
        //
        $confirmPretext = __('Are you sure you want to ','csa-slplus');
        
        
        return
            $this->slplus->helper->createstring_DropDownMenuWithButton(
                array(
                        'id'            => 'actionType'             ,
                        'name'          => 'action'                 ,
                        'items'         => $dropdownItems           ,
                        'onchange'      => "jQuery({$morebox}).dialog({ $dialog_options });",
                        'buttonlabel'   => __('Apply','csa-slplus') ,
                        'onclick'       => 
                            'AdminUI.doAction(jQuery(\'#actionType\').val(),\''.
                                $confirmPretext .
                                '\'+jQuery(\'#actionType option:selected\').text()+\'?\');'
                    )
                ).
                $baExtras 
            ;
    }

    /**
     * Create the edit panel form.
     * 
     * @return string
     */
    private function createstring_EditPanel() {
        $this->slplus->currentLocation->set_PropertiesViaDB($_REQUEST['id']);
        return $this->create_LocationAddEditForm(false);
    }

    /**
     * Create the fitlers drop down for the top-of-table navigation.
     *
     */
    function createstring_FiltersBlock() {

        // Setup the properties array for our drop down.
        //
        $dropdownItems = array(
                array(
                    'label'     =>  __('Show All','csa-slplus') ,
                    'value'     => 'show_all'                   ,
                    'selected'  => true
                ),
            );

        // FILTER: slp_locations_manage_filters
        //
        $dropdownItems = apply_filters('slp_locations_manage_filters',$dropdownItems);

        // Loop through the action boxes content array
        //
        $baExtras = '';
        foreach ($dropdownItems as $item) {
            if (isset($item['extras']) && !empty($item['extras'])) {
                $baExtras .= $item['extras'];
            }
        }

        // Create the box div string.
        //
        $morebox = "'#extra_'+jQuery('#filterType').val()"        ;
        $filter_dialog_title = __('Filter Locations By','csa-slplus');
        $dialog_options = 
            "appendTo: '#locationForm'      , " .
            "minWidth: 450                  , " .
            "title: '$filter_dialog_title'  , " .
            "position: { my: 'left top', at: 'left bottom', of: '#filterType' } "
            ;
            
        
        return
            $this->slplus->helper->createstring_DropDownMenuWithButton(
                array(
                        'id'            => 'filterType'             ,
                        'name'          => 'filter'                 ,
                        'items'         => $dropdownItems           ,
                        'onchange'      => "jQuery({$morebox}).dialog({ $dialog_options });" ,                 
                        'buttonlabel'   => __('Filter','csa-slplus') ,
                        'onclick'       => 'AdminUI.doAction(jQuery(\'#filterType\').val(),\'\');'
                    )
                ).
                $baExtras 
            ;
    }


    /**
     * Create the column headers for sorting the table.
     *
     * @param string $theURL
     * @param string $fldID
     * @param string $fldLabel
     * @param string $dir
     * @param boolean $showTH
     * @return string
     */
    function createstring_ColumnHeader($fldID='sl_store',$fldLabel='ID',$dir='ASC',$showTH=True) {

        // Set Sort Marker
        //
        $newDir = 'asc';
        $sortindicatorClass = '';
        $siStyle = '';
        if (isset($_REQUEST['orderBy']) && ($_REQUEST['orderBy'] === $fldID)) {
            $sortindicatorClass = 'sorted ' . (($_REQUEST['sortorder']=='asc') ? 'asc':'desc');
            $newDir = (($_REQUEST['sortorder']=='asc') ? 'desc':'asc');
            if (!$showTH) {
                $siStyle = 
                    "style='".
                        'background-position: '.(($_REQUEST['sortorder']=='asc') ? '0':'-7px').' 0; '.
                        'display: block;' .
                    "' ";
            }
        }

        return ($showTH?"<th class='manage-column sortable $sortindicatorClass'>":'') .
                "<a href='{$this->cleanURL}&orderBy=$fldID&sortorder=$newDir'>" .
                "<span>$fldLabel</span>".
                "<span class='sorting-indicator' {$siStyle}></span>".
                "</a>" .
                ($showTH?'</th>':'')
                ;
    }

    /**
     * Create the display drop down for the top-of-table navigation.
     *
     */
    function createstring_DisplayBlock() {
         $currentDisplayMode    = get_option('sl_location_table_view'       , 'Normal'  );
         $current_page_size     = get_option('sl_admin_locations_per_page'  , '10'      );

        // Setup the properties array for our drop down.
        //
        $dropdownItems = array(
                array(
                    'label'     =>  
                        sprintf(
                             '%s (%d %s)',
                             __('Normal','csa-slplus'),
                             $current_page_size,
                             __('locations', 'csa-slplus')
                            ), 
                    'value'     => 'displaynormal'                      ,
                    'selected'  => ($currentDisplayMode == 'Normal')
                ),
                array(
                    'label'     => 
                        sprintf(
                             '%s (%d %s)',
                             __('Expanded','csa-slplus'),
                             $current_page_size,
                             __('locations', 'csa-slplus')
                            ),                     
                    'value'     => 'displayexpanded'                    ,
                    'selected'  => ($currentDisplayMode == 'Expanded')
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'10')   ,
                    'value'     => 'locationsPerPage'                               ,
                    'selected'  => false
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'100')  ,
                    'value'     => 'locationsPerPage'                               ,
                    'selected'  => false
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'500')  ,
                    'value'     => 'locationsPerPage'                               ,
                    'selected'  => false
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'1000')  ,
                    'value'     => 'locationsPerPage'                               ,
                    'selected'  => false
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'5000')  ,
                    'value'     => 'locationsPerPage'                                ,
                    'selected'  => false
                ),
                array(
                    'label'     =>  sprintf(__('%d locations','csa-slplus'),'10000')  ,
                    'value'     => 'locationsPerPage'                                 ,
                    'selected'  => false
                )
            );

        // FILTER: slp_locations_manage_display
        //
        $dropdownItems = apply_filters('slp_locations_manage_display',$dropdownItems);

        return
            $this->slplus->helper->createstring_DropDownMenuWithButton(
                array(
                        'id'            => 'displayType'                ,
                        'name'          => 'display'                    ,
                        'items'         => $dropdownItems               ,
                        'buttonlabel'   => __('Display','csa-slplus')   ,
                        'onclick'       => 
                            'jQuery(\'#displaylimit\').val(jQuery(\'#displayType option:selected\').text());' .
                            'AdminUI.doAction(jQuery(\'#displayType\').val(),\'\');'
                    )
                )
            ;
    }

    /**
     * Create location details based on current location.
     */
    function createstring_location_details() {
        $HTML = '';

        // SLPlus location properties and the span class to use when rendering them
        //
        $interesting_fields = array(
            'description' => 'textblock',
            'email'       => 'text',
            'url'         => 'text',
            'hours'       => 'textblock',
            'phone'       => 'text',
            'fax'         => 'text',
            'image'       => 'text',
            'private'     => 'boolean',
            'neat_title'  => 'text',
            'lastupdated' => 'text',
            'id'          => 'text'

            );

        // FILTER: slp_location_details_fields
        // gets the array of fields to output and the span CSS in the details expansion on the location manager table
        // return a modified array of SLPlus location property names => span CSS class
        //
        $interesting_fields = apply_filters( 'slp_location_details_fields' , $interesting_fields );

        foreach ( $interesting_fields as $field_name => $span_class ) {
            $HTML .=
                "<span class='location_details_field {$span_class}'>"               .
                    "<span class='label  {$span_class}'>{$field_name}</span>"     .
                    "<span class='content  {$span_class}'>{$this->slplus->currentLocation->$field_name}</span>" .
                "</span>"
                ;
        }

        // FILTER: slp_location_details
        // gets the HTML that is output in the details expansion on the location manager table
        // return  modified HTML
        //
        $HTML = apply_filters( 'slp_location_details' , $HTML );
        return $HTML;
    }

    /**
     * Create the manage locations pagination block
     *
     * @param int $totalLocations
     * @param int $num_per_page
     * @param int $start
     * @return string
     */
    function createstring_PaginationBlock($totalLocations = 0, $num_per_page = 10, $start = 0) {

        // Variable Init
        $pos=0;
        $prev = min(max(0,$start-$num_per_page),$totalLocations);
        $next = min(max(0,$start+$num_per_page),$totalLocations);
        $num_per_page = max(1,$num_per_page);
        $qry = isset($_GET['q'])?$_GET['q']:'';
        $cleared=preg_replace('/q=$qry/', '', $this->hangoverURL);

        $extra_text=(trim($qry)!='')    ?
            __("for your search of", 'csa-slplus').
                " <strong>\"$qry\"</strong>&nbsp;|&nbsp;<a href='$cleared'>".
                __("Clear&nbsp;Results", 'csa-slplus')."</a>" :
            "" ;

        // URL Regex Replace
        //
        if (preg_match('#&start='.$start.'#',$this->hangoverURL)) {
            $prev_page=str_replace("&start=$start","&start=$prev",$this->hangoverURL);
            $next_page=str_replace("&start=$start","&start=$next",$this->hangoverURL);
        } else {
            $prev_page=$this->hangoverURL ."&start=$prev";
            $next_page=$this->hangoverURL."&start=$next";
        }

        // Pages String
        //
        $pagesString = '';
        if ($totalLocations>$num_per_page) {
            if ((($start/$num_per_page)+1)-5<1) {
                $beginning_link=1;
            } else {
                $beginning_link=(($start/$num_per_page)+1)-5;
            }
            if ((($start/$num_per_page)+1)+5>(($totalLocations/$num_per_page)+1)) {
                $end_link=(($totalLocations/$num_per_page)+1);
            } else {
                $end_link=(($start/$num_per_page)+1)+5;
            }
            $pos=($beginning_link-1)*$num_per_page;
            for ($k=$beginning_link; $k<$end_link; $k++) {
                if (preg_match('#&start='.$start.'#',$_SERVER['QUERY_STRING'])) {
                    $curr_page=str_replace("&start=$start","&start=$pos",$_SERVER['QUERY_STRING']);
                }
                else {
                    $curr_page=$_SERVER['QUERY_STRING']."&start=$pos";
                }
                if (($start-($k-1)*$num_per_page)<0 || ($start-($k-1)*$num_per_page)>=$num_per_page) {
                    $pagesString .= "<a class='page-button' href=\"{$this->hangoverURL}&$curr_page\" >";
                } else {
                    $pagesString .= "<a class='page-button thispage' href='#'>";
                }


                $pagesString .= "$k</a>";
                $pos=$pos+$num_per_page;
            }
        }

        $prevpages =
            "<a class='prev-page page-button" .
                ((($start-$num_per_page)>=0) ? '' : ' disabled' ) .
                "' href='".
                ((($start-$num_per_page)>=0) ? $prev_page : '#' ).
                "'>‹</a>"
            ;
        $nextpages =
            "<a class='next-page page-button" .
                ((($start+$num_per_page)<$totalLocations) ? '' : ' disabled') .
                "' href='".
                ((($start+$num_per_page)<$totalLocations) ? $next_page : '#').
                "'>›</a>"
            ;

        $pagesString =
            $prevpages .
            $pagesString .
            $nextpages
            ;

        return
                '<div id="slp_pagination_pages" class="tablenav-pages">'    .
                    '<span class="displaying-num">'                         .
                            "<span id='total_locations'>{$totalLocations}</span>" .
                            ' '.__('locations','csa-slplus')                .
                        '</span>'                                           .
                        '<span class="pagination-links">'                   .
                        $pagesString                                        .
                        '</span>'                                           .
                    '</div>'                                                .
                    $extra_text                                             
            ;
    }

    /**
     * Attach the HTML for the add location panel to the settings object as a new section.
     *
     * This will be rendered via the render_adminpage method via the standard wpCSL Settings object display method.
     */
    public function create_settings_section_Add() {
        // Edit Mode
        //
        if ( $this->current_action === 'edit' ) {
            $panel_name        =  __('Edit','csa-slplus');
            $panel_description = $this->createstring_EditPanel();
            $panel_div_id      = 'edit_location';
            
        // Add Mode
        //
        } else {    
            $panel_name        =  __('Add','csa-slplus');
            $panel_description = $this->create_LocationAddEditForm(true);            
            $panel_div_id      = 'add_location';
        }
        
        $this->settings->add_section(
            array(
                    'name'          => $panel_name,
                    'div_id'        => $panel_div_id,
                    'description'   => $panel_description,
                    'auto'          => true,
                    'innerdiv'      => true
                )
         );
    }

    /**
     * Attach the HTML for the manage locations panel to the settings object as a new section.
     *
     * This will be rendered via the render_adminpage method via the standard wpCSL Settings object display method.
     */
    public function create_settings_section_Manage() {
        $this->settings->add_section(
            array(
                    'name'          => __('Manage','csa-slplus'),
                    'div_id'        => 'current_locations',
                    'description'   => $this->createstring_PanelManageTable(),
                    'auto'          => true,
                    'innerdiv'      => true
                )
         );
    }

    /**
     * Build the HTML string for the locations table.
     */
    private function createstring_PanelManageTable() {
        $this->set_LocationQueryProperties();

        return
                '<input id="displaylimit" '         .
                    'name="displaylimit" '          .
                    'type="hidden" '                .
                    'value="'.get_option('sl_admin_locations_per_page','10').'" ' .
                    '/>' .
                $this->createstring_HiddenInputs()              .
                $this->createstring_PanelManageTableTopActions().
                $this->createstring_PanelManageTableLocations() .
            '<div class="tablenav bottom">'                 .
                $this->createstring_PanelManageTablePagination().
            '</div>'
            ;
    }


    /**
     * Build the content of the manage locations table.
     *
     * TODO: convert this to a proper WP_List_Table query and items configuration.
     *
     * @return string
     */
    private function createstring_PanelManageTableLocations() {
        $this->debugMP('msg',__FUNCTION__);

        // Set the data query
        //
        $dataQuery =
            $this->slplus->database->get_SQL('selectall') .
            $this->db_where .
            " ORDER BY {$this->db_orderbyfield} {$this->db_orderbydir} ".
            " LIMIT {$this->start},".$this->slplus->data['sl_admin_locations_per_page'] . ' ';
        $this->debugMP('msg','',"SQL Query: {$dataQuery}");

        // Add custom column headers
        //
        add_filter('slp_manage_location_columns'        ,array($this,'filter_AddFieldHeadersToManageLocations'      )           );


        // Get the locations into the array
        //
        if ($slpLocations=$this->slplus->db->get_results($dataQuery,ARRAY_A)) {
            $this->set_Columns();
            $content['pagination_block'] =
                $this->createstring_PaginationBlock(
                    $this->totalLocations,
                    $this->slplus->data['sl_admin_locations_per_page'],
                    $this->start
                    );

            // Get the manage locations table header
            //
            $tableHeaderString = $this->createstring_TableHeader($this->columns,$this->db_orderbyfield,$this->db_orderbydir);

            // Manage
            //
            $content['locationstable'] =
                "<table id='manage_locations_table' ".
                    "class='slplus wp-list-table widefat posts' cellspacing=0>" .
                        $tableHeaderString;

            $colorClass = '';

            // Highlight invalid locations
            //
            add_filter('slp_locations_manage_cssclass',array($this,'filter_InvalidHighlight'));
            
            // Add lat/long to the name field
            //
            add_filter( 'slp_column_data' , array($this, 'filter_AddLatLongUnderName'           ) , 10, 3 );

            // Add Image to the output columns
            //
            add_filter( 'slp_column_data' , array($this, 'filter_AddImageToManageLocations'     ), 90 ,  3  );


            // Loop through the locations list and render table rows.
            //
            foreach ($slpLocations as $location) {

                // Set current location
                //
                $this->slplus->currentLocation->set_PropertiesViaArray($location);

                // Row color
                //
                $colorClass = (($colorClass==='alternate')?'':'alternate');

                // FILTER: slp_locations_manage_cssclass
                //
                $extraCSSClasses = apply_filters('slp_locations_manage_cssclass','');

                // Clean Up Data with trim()
                //
                $location=array_map("trim",$location);

                // Custom Filters to set the links on special data like URLs and Email
                //
                $location['sl_url']=(!$this->url_test($location['sl_url']) && trim($location['sl_url'])!="")?
                    "http://".$location['sl_url'] :
                    $location['sl_url'] ;

                $location['sl_url']=($location['sl_url']!="")?
                    "<a href='{$location['sl_url']}' target='blank' ".
                            "alt='{$location['sl_url']}' title='{$location['sl_url']}'>".
                            $this->slplus->options['label_website'] .
                            '</a>' :
                    '';

                $location['sl_email'] =
                    ! empty( $location['sl_email'] )        ?
                        sprintf('<a href="mailto:%s" target="blank" alt="%s" title="%s">%s</a>' ,
                                $location['sl_email'],
                                $location['sl_email'],
                                $location['sl_email'],
                                $this->slplus->options['label_email']
                                )                           :
                        ''                                  ;

                $location['sl_description']=($location['sl_description']!="")?
                    "<a onclick='alert(\"".$this->slp_escape($location['sl_description'])."\")' href='#'>".
                    __("View", 'csa-slplus')."</a>" :
                    "" ;

                $cleanName = urlencode($this->slplus->currentLocation->store);

                // Location Row Start
                //
                $content['locationstable'] .=
                    "<tr "                                                                                  .
                        "data-id='{$this->slplus->currentLocation->id}' "                                   .
                        "data-type='base' "                                                                 .
                        "id='location-{$this->slplus->currentLocation->id}' "                               .
                        "name='{$cleanName}' "                                                              .
                        "class='slp_managelocations_row $colorClass $extraCSSClasses' "                     .
                        ">"                                                                                 .
                    "<th class='th_checkbox'>"                                                              .
                        "<input type='checkbox' class='slp_checkbox' name='sl_id[]' value='{$this->slplus->currentLocation->id}'>"        .
                    '</th>'                                                                                 .
                    "<th class='thnowrap'><div class='action_buttons'>"                                     .
                        $this->createstring_ActionButtons()                                                 .
                    "</div></th>"
                    ;

                // Create Address Block
                //
                $location['address'] = '';
                $newData = false;
                foreach (array('address','address2','city','state','zip','country') as $property) {
                    // Added something and need formatting?
                    //
                    if ($newData) {
                        switch ($property) {
                            case 'address2':
                            case 'city':
                            case 'country':
                                $location['address'] .= '<br/>';
                                break;
                            case 'state':
                                $location['address'] .= ' , ';
                                break;
                            case 'zip':
                                $location['address'] .= ' ';
                                break;
                            default:
                                break;
                        }
                        $newData = false;
                    }

                    // Location property is not empty
                    //
                    $propVal = $this->slplus->currentLocation->$property;
                    if (!empty($propVal)) {
                        $location['address'] .= $this->slplus->currentLocation->$property;
                        $newData = true;
                    }
                }

                // Data Columns
                // FILTER: slp_column_data
                //
                foreach ($this->columns as $slpField => $slpLabel) {
                    $labelAsClass = sanitize_title($slpLabel);
                    if ( ! isset( $location[$slpField] ) ) { $location[$slpField] = ''; }
                    $content['locationstable'] .=
                        "<td class='slp_manage_locations_cell {$labelAsClass}'>"                                           .
                            apply_filters('slp_column_data',stripslashes($location[$slpField]), $slpField, $slpLabel)     .
                         '</td>';                       
                }

                $content['locationstable'] .= '</tr>';

                // Details Block
                //
                $column_count = count( $this->columns );
                $content['locationstable'] .=
                    "<tr "  .
                        "data-id='{$this->slplus->currentLocation->id}' "                               .
                        "data-type='details' "                                                          .
                        "id='location-details-{$this->slplus->currentLocation->id}' "                   .
                        "name='{$cleanName} Details' "                                                  .
                        "class='slp_managelocations_row collapsed $colorClass $extraCSSClasses' "       .
                        ">"                                                                             .
                        "<td colspan='2'               >&nbsp;</td>"                                    .
                        "<td colspan='{$column_count}' >"                                               .
                                $this->createstring_location_details()                                  .
                        '</td>'                                                                         .
                    '</tr>'
                    ;

            }

            // Close Out Table
            //
            $content['locationstable'] .= $tableHeaderString .'</table>';

        // No Locations Found
        //
        } else {
            $content['pagination_block'] = '';
            $content['locationstable'] =
                "<div class='csa_info_msg' id='manage_locations_msg'>".
                    (
                     (empty($_REQUEST['searchfor']))                                  ?
                            __("No locations have been created yet.", 'csa-slplus')   :
                            __("Search Locations returned no matches.", 'csa-slplus')
                    ) .
                    ($this->slplus->is_CheckTrue($this->slplus->options_nojs['extended_admin_messages'])?'<br/><br/>'.__('Where: ','csa-slplus').$this->db_where : '').

                "</div>";
        }
        
        return $content['locationstable'];
    }
    
    /**
     * Create the pagination string for the manage locations table.
     * 
     * @return string
     */
    private function createstring_PanelManageTablePagination() {
        if ($this->totalLocations >0) {
            return $this->createstring_PaginationBlock(
                $this->totalLocations,
                $this->slplus->data['sl_admin_locations_per_page'],
                $this->start
                );
        } else {
            return '';
        }
    }

    /**
     * Build the HTML for the top-of-table navigation interface.
     *
     * @return string
     */
    private function createstring_PanelManageTableTopActions() {
        $HTML = 
            $this->createstring_BulkActionsBlock()           .
            $this->createstring_FiltersBlock()               .
            $this->createstring_DisplayBlock()               .
            $this->createstring_SearchBlock()                .
            $this->createstring_PanelManageTablePagination() ;
            
        return 
            '<div class="tablenav top">'                            .                            
            apply_filters( 'slp_manage_locations_actionbar_ui', $HTML ) .
            '</div>'                                                ;
    }

    /**
     * Create the display drop down for the top-of-table navigation.
     *
     */
    function createstring_SearchBlock() {
        $currentSearch = ((isset($_REQUEST['searchfor'])&&!empty($_REQUEST['searchfor']))?$_REQUEST['searchfor']:'');
        return
            '<div class="alignleft actions">'                                                                               .
                "<input id='searchfor' value='{$currentSearch}' type='text' name='searchfor' "                              .
                    ' onkeypress=\'if (event.keyCode == 13) { event.preventDefault();AdminUI.doAction("search",""); } \' '              .
                    ' />'                                                                                                   .
                "<input id='doaction_search' class='button action' type='submit' "                                          .
                    "value='".__('Search','csa-slplus')."' "                                                                .
                    'onClick="AdminUI.doAction(\'search\',\'\');" '                                             .
                    ' />'                                                                                                   .
            '</div>'
            ;
    }

    /**
     * Create the manage locations table header string.
     *
     * @param array $slpManageColumns the manage locations columns pre-filter
     * @return string the HTML string
     */
    function createstring_TableHeader($slpManageColumns,$opt,$dir) {
        $tableHeaderString =
            '<thead>'                                                                                                           .
                '<tr >'                                                                                                         .
                    "<th id='top_of_checkbox_column'>"                                                                          .
                        '<input type="checkbox" class="button" '                                                                .
                            'onclick="'                                                                                         .
                                "jQuery('.slp_checkbox').prop('checked',jQuery(this).prop('checked'));"                                                 .
                                '" '                                                                                            .
                            '>'                                                                                                 .
                    '</th>'                                                                                                     .
                    "<th class='manage-column sortable address'>"                                                                           .
                        $this->createstring_ColumnHeader(
                                'sl_id'  ,__('Actions'   ,'csa-slplus'),
                                $dir,false) .
                                ' ' .
                   '</th>'
                ;
        foreach ($slpManageColumns as $slpField => $slpLabel) {
            switch ($slpField) {
                case 'address':
                    $tableHeaderString .=
                        "<th class='manage-column sortable address'>" .
                            $this->createstring_ColumnHeader(
                                    'sl_address'  ,__('Address'   ,'csa-slplus'),
                                    $dir,false) .
                                    ' ' .
                            $this->createstring_ColumnHeader(
                                    'sl_city'     ,__('City'      ,'csa-slplus'),
                                    $dir,false) .
                                    ' ' .
                            $this->createstring_ColumnHeader(
                                    'sl_state'    ,__('State'     ,'csa-slplus'),
                                    $dir,false) .
                                    ' ' .
                            $this->createstring_ColumnHeader(
                                    'sl_zip'      ,__('Zip'       ,'csa-slplus'),
                                    $dir,false) .
                                    ' ' .
                            $this->createstring_ColumnHeader(
                                    'sl_country'  ,__('Country'       ,'csa-slplus'),
                                    $dir,false)
                                    .
                        "</th>"
                        ;
                    break;

                default:
                    $tableHeaderString .= $this->createstring_ColumnHeader($slpField,$slpLabel,$dir);
                    break;
            }
        }
        $tableHeaderString .= '</tr></thead>';
        return $tableHeaderString;
    }

    /**
     * Process any incoming actions.
     */
    function process_Actions() {
        $this->debugMP('msg',__FUNCTION__,"Action: {$this->current_action}");
        if ( $this->current_action === '' ) { return; }

        switch ($this->current_action) {

            // ADD
            //
            case 'add' :
                $this->location_Add();
                $this->slplus->notifications->display();
                break;

            // SAVE
            //
            case 'edit':
                if ( $this->slplus->currentLocation->isvalid_ID( null, 'id' ) ) {
                    $this->location_save();
                }
               $_REQUEST['selected_nav_element'] = '#wpcsl-option-edit_location';
                break;

            case 'save':
               $this->location_save();
               $_REQUEST['selected_nav_element'] = '#wpcsl-option-current_locations';
                break;

            // DELETE
            //
            case 'delete':
                if ( ! isset($_REQUEST['sl_id'] ) && isset( $_REQUEST['id'] ) ) { 
                    $locationList = (array) $_REQUEST['id'];                     
                } else {
                    $locationList = is_array($_REQUEST['sl_id'])?$_REQUEST['sl_id']:array($_REQUEST['sl_id']);
                }
                foreach ($locationList as $locationID) {
                    $this->slplus->currentLocation->set_PropertiesViaDB($locationID);
                    $this->slplus->currentLocation->DeletePermanently();
                }
                break;

            // Locations Per Page Action
            //   - update the option first,
            //   - then reload the
            case 'locationsperpage':
                $newLimit = preg_replace('/\D/','',$_REQUEST['displaylimit']);
                if (ctype_digit($newLimit) && (int)$newLimit > 9) {
                    update_option('sl_admin_locations_per_page', $newLimit);
                    $this->slplus->settings->get_item('sl_admin_locations_per_page','get_option',null,'10',true);
                }
                break;

            // Display Expanded
            //
            case 'displayexpanded':
                if (get_option('sl_location_table_view') != 'Expanded') {
                    update_option('sl_location_table_view', 'Expanded');
                }
                break;

            // Display Expanded
            //
            case 'displaynormal':
                if (get_option('sl_location_table_view') != 'Normal') {
                    update_option('sl_location_table_view', 'Normal');
                }
                break;


            // All other things do nothing here...
            // But they may be handled by an add-on pack via the
            // FILTER: slp_manage_locations_action
            //
            default:
                break;
        }
        do_action('slp_manage_locations_action');
    }
    
    /**
     * Render the manage locations admin page.
     *
     */
    function render_adminpage() {
        $this->slplus->debugMP('slp.managelocs','msg',__FUNCTION__);
        $this->slplus->helper->loadPluginData();
        $this->slplus->AdminUI->initialize_variables();
        $this->process_Actions();

        //------------------------------------------------------------------------
        // CHANGE UPDATER
        // Changing Updater
        //------------------------------------------------------------------------
        if (isset($_GET['changeUpdater']) && ($_GET['changeUpdater']==1)) {
            if (get_option('sl_location_updater_type')=="Tagging") {
                update_option('sl_location_updater_type', 'Multiple Fields');
                $updaterTypeText="Multiple Fields";
            } else {
                update_option('sl_location_updater_type', 'Tagging');
                $updaterTypeText="Tagging";
            }
            $_SERVER['REQUEST_URI']=preg_replace('/&changeUpdater=1/', '', $_SERVER['REQUEST_URI']);
            print "<script>location.replace('".$_SERVER['REQUEST_URI']."');</script>";
        }

        //------------------------------------------------------------------------
        // Reload Variables - anything that my have changed
        //------------------------------------------------------------------------
        $this->slplus->helper->getData('sl_admin_locations_per_page','get_option',null,'10',true,true);

        //--------------------------------------
        // Setup the Location panel navigation
        //--------------------------------------
        $subtabs = apply_filters('slp_locations_subtabs',
                 array(
                     __('Manage','csa-slplus'),
                     __('Add','csa-slplus')
                 )
                );


        //------------------------------------
        // Create Location Panels
        //
        add_action('slp_build_locations_panels',array($this,'create_settings_section_Manage'  ),10);
        add_action('slp_build_locations_panels',array($this,'create_settings_section_Add'     ),20);

        //-------------------------
        // Setup Navigation Bar
        //
        $this->settings->add_section(
            array(
                'name'          => 'Navigation',
                'div_id'        => 'navbar_wrapper',
                'description'   => $this->slplus->AdminUI->create_Navbar(),
                'innerdiv'      => false,
                'is_topmenu'    => true,
                'auto'          => false,
                'headerbar'     => false
            )
        );

        //------------------------------------
        // Render It
        //
        do_action('slp_build_locations_panels');
        $this->settings->render_settings_page();
    }


    /**
     * Check if a URL starts with http://
     *
     * @param type $url
     * @return type
     */
    private function url_test($url) {
        return (strtolower(substr($url,0,7))=="http://");
    }

}

// Dad. Explorer. Rum Lover. Code Geek. Not necessarily in that order.
