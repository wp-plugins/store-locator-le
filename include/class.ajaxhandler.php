<?php

/**
 * Store Locator Plus Ajax Handler
 *
 * Manage the AJAX calls that come in from our admin and frontend UI.
 * Currently only holds new AJAX calls, all calls need to go in here.
 *
 * @package StoreLocatorPlus\AjaxHandler
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
 */
class SLPlus_AjaxHandler {

    //-------------------------------------
    // Properties
    //-------------------------------------

    /**
     * The formdata variables.
     *
     * @var string[] $formdata
     */
    public $formdata;

    /**
     * Default formdata values.
     *
     * @var mixed[] $formdata_defaults
     */
    private $formdata_defaults = array(
        'addressInput'      => '',
        'addressInputState' => '',
        'nameSearch'        => '',
    );

    /**
     * Metadata placeholder for register_addon, never used.
     *
     * @var mixed $metadata
     */
    public $metadata;

    /**
     * Name of this module.
     *
     * @var string $name
     */
    private $name;

    /**
     * Options needed for register_addon, never used.
     *
     * @var
     */
    public $options;

    /**
     * Needed for backwards compatibility with add-on packs. :/
     * @var
     */
    public $plugin;

    /**
     * Set the query parameters received.
     *
     * @var array
     */
    private $query_params = array();
    
    /**
     * The plugin object.
     * 
     * @var \SLPlus $slplus
     */
    public $slplus;

    /**
     * The database query string.
     *
     * @var string $dbQuery
     */
    private $dbQuery;

    /**
     * The basic query string before the prepare.
     * @var string
     */
    private $basic_query;

    /**
     * The query limit.
     *
     * @var int
     */
    public $query_limit;

    //----------------------------------
    // Methods
    //----------------------------------
    
    /**
     * The Constructor
     */
    function __construct($params=null) {

        $this->name = 'AjaxHandler';

        // Set slplus property
        //
        if (!isset($this->slplus) || ($this->slplus == null)) {
            global $slplus_plugin;
            $this->slplus = $slplus_plugin;
            $this->slplus->register_module($this->name,$this);
            $this->plugin = $this->slplus;
        }
        $this->slplus->notifications->enabled = false;

        // Set incoming params
        //
        $this->set_QueryParams();
    }

    /**
     * Set incoming query and request parameters into object properties.
     */
    function set_QueryParams() {
        if (isset($_REQUEST['formdata'])) {
            $this->formdata = wp_parse_args($_REQUEST['formdata'],$this->formdata_defaults);
        }

        if (isset($_REQUEST['options'])) {
            $this->slplus->options = wp_parse_args($_REQUEST['options'],$this->slplus->options);
        }

        $this->query_params['QUERY_STRING'] = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '' ;

        // Set the valid keys
        //
        $valid_keys = array(
            'address',
            'lat',
            'lng',
            'radius',
            'tags',
        );
        foreach ( $valid_keys as $key ) {
            $this->query_params[$key] = isset( $_POST[$key] ) ? $_POST[$key] : '';
        }
    }

    /**
     * Format the result data into a named array.
     *
     * We will later use this to build our JSONP response.
     *
     * @param null mixed[] $row
     * @return mixed[]
     */
    function slp_add_marker($row = null) {
        if ($row == null) {
            return '';
        }
        $marker = array(
              'name'            => esc_attr($row['sl_store']),
              'address'         => esc_attr($row['sl_address']),
              'address2'        => esc_attr($row['sl_address2']),
              'city'            => esc_attr($row['sl_city']),
              'state'           => esc_attr($row['sl_state']),
              'zip'             => esc_attr($row['sl_zip']),
              'country'         => esc_attr($row['sl_country']),
              'lat'             => $row['sl_latitude'],
              'lng'             => $row['sl_longitude'],
              'description'     => html_entity_decode($row['sl_description']),
              'url'             => esc_attr($row['sl_url']),
              'sl_pages_url'    => esc_attr($row['sl_pages_url']),
              'email'           => esc_attr($row['sl_email']),
              'email_link'      => esc_attr($row['sl_email']),
              'hours'           => esc_attr($row['sl_hours']),
              'phone'           => esc_attr($row['sl_phone']),
              'fax'             => esc_attr($row['sl_fax']),
              'image'           => esc_attr($row['sl_image']),
              'distance'        => $row['sl_distance'],
              'tags'            => esc_attr($row['sl_tags']),
              'option_value'    => esc_js($row['sl_option_value']),
              'attributes'      => maybe_unserialize($row['sl_option_value']),
              'id'              => $row['sl_id'],
              'linked_postid'   => $row['sl_linked_postid'],
              'neat_title'      => esc_attr( $row['sl_neat_title'] ),
          );

        $this->slplus->currentLocation->set_PropertiesViaArray($row);

        // FILTER: slp_results_marker_data
        // Modify the map marker object that is sent back to the UI in the JSONP response.
        //
        $marker = apply_filters('slp_results_marker_data',$marker);

        return $marker;
    }

    /**
     * Handle AJAX request for OnLoad action.
     *
     */
    function csl_ajax_onload() {

        // Return How Many?
        //
        $response=array();
        $this->query_limit = $this->slplus->options['initial_results_returned'];
        $locations = $this->execute_LocationQuery();
        foreach ($locations as $row){
            $response[] = $this->slp_add_marker($row);
        }

        // Output the JSON and Exit
        //
        $this->renderJSON_Response(
                array(
                        'count'         => count($response) ,
                        'type'          => 'load',
                        'response'      => $response
                    )
                );
    }

    /**
     * Handle AJAX request for Search calls.
     */
    function csl_ajax_search() {

        // Get Locations
        //
		$response = array();
		$search_results_location_ids = array();
        $this->query_limit = $this->slplus->options_nojs['max_results_returned'];
        $locations = $this->execute_LocationQuery();
        foreach ($locations as $row){
            $thisLocation = $this->slp_add_marker($row);
            if (!empty($thisLocation)) {
				$response[] = $thisLocation;
				$search_results_location_ids[] = $row['sl_id'];
            }
		}

		// Do report work
		//
		do_action('slp_report_query_result', $this->query_params, $search_results_location_ids);

        // Output the JSON and Exit
        //
        $this->renderJSON_Response(
                array(  
                        'count'         => count($response),
                        'option'        => $this->query_params['address'],
                        'type'          => 'search',
                        'response'      => $response
                    )
                );
     }

    /**
     * Run a database query to fetch the locations the user asked for.
     *
     * @return object a MySQL result object
     */
    function execute_LocationQuery() {

        // SLP options that tweak the query
        //
        $this->slplus->database->createobject_DatabaseExtension();

        // Distance Unit (KM or MI) Modifier
        // Since miles is default, if kilometers is selected, divide by 1.609344 in order to convert the kilometer value selection back in miles
        //
        $multiplier=($this->slplus->options['distance_unit']==__('km', 'csa-slplus'))? 6371 : 3959;

        //........
        // Post options that tweak the query
        //........

        // Add all the location filters together for SQL statement.
        // FILTER: slp_location_filters_for_AJAX
        //
        $filterClause = '';
        foreach (apply_filters('slp_location_filters_for_AJAX',array()) as $filter) {
            $filterClause .= $filter;
        }

        // ORDER BY
        //
        add_action( 'slp_orderby_default' , array( $this , 'add_distance_sort_to_orderby') , 100 );

        // Having clause filter
        // Do filter after sl_distance has been calculated
        //
        // FILTER: slp_location_having_filters_for_AJAX
        // append new having clause logic to the array and return the new array
        // to extend/modify the having clause.
        //
        $havingClauseElements =
            apply_filters(
                'slp_location_having_filters_for_AJAX',
                array(
                    '(sl_distance < %f) ', 
                    'OR (sl_distance IS NULL) '
                )
             );

        // If there are element for the having clause set it
        // otherwise leave it as a blank string
        //
        $having_clause = '';
        if ( count($havingClauseElements) > 0 ) {
            foreach ($havingClauseElements as $filter) {
                $having_clause .= $filter;
            }
            $having_clause = trim( $having_clause );
            $having_clause = preg_replace( '/^OR /', '' , $having_clause );

            if ( ! empty( $having_clause ) ) {
                $having_clause = 'HAVING ' . $having_clause;
            }

        }

        // WHERE clauses
        //
        add_filter( 'slp_ajaxsql_where' , array( $this , 'filter_out_private_locations' ) );

        // FILTER: slp_ajaxsql_fullquery
        //
        $this->basic_query =
            apply_filters(
                'slp_ajaxsql_fullquery',
                $this->slplus->database->get_SQL(
                    array(
                        'selectall_with_distance',
                        'where_default_validlatlong',
                    )
                 )                                                      .
                "{$filterClause} "                                      .
                "{$having_clause} "                                      .
                $this->slplus->database->get_SQL('orderby_default')     .
                'LIMIT %d'
            );

        // Set the query parameters
        //
        $default_query_parameters = array();
        $default_query_parameters[] = $multiplier;
        $default_query_parameters[] = $this->query_params['lat'];
        $default_query_parameters[] = $this->query_params['lng'];
        $default_query_parameters[] = $this->query_params['lat'];
        if ( ! empty( $having_clause ) ) {
            $default_query_parameters[] = $this->query_params['radius'];
        }
        $default_query_parameters[] = $this->query_limit;

        // FILTER: slp_ajaxsql_queryparams
        $queryParams = apply_filters( 'slp_ajaxsql_queryparams' , $default_query_parameters );

        // Run the query
        //
        // First convert our placeholder basic_query into a string with the vars inserted.
        // Then turn off errors so they don't munge our JSONP.
        //
        global $wpdb;
        $this->dbQuery =
            $wpdb->prepare(
                $this->basic_query,
                $queryParams
                );
        $wpdb->hide_errors();
        $result = $wpdb->get_results($this->dbQuery, ARRAY_A);

        // Problems?  Oh crap.  Die.
        //
        if ($result === null) {
            die(json_encode(array(
                'success'       => false, 
                'response'      => 'Invalid query: ' . $wpdb->last_error,
                'message'       => $this->slplus->options_nojs['invalid_query_message'],
                'basic_query'   => $this->basic_query ,
                'default_params'=> $default_query_parameters,
                'query_params'  => $queryParams,
                'dbQuery'       => $this->dbQuery
            )));
        }

        // Return the results
        // FILTER: slp_ajaxsql_results
        //
        return apply_filters('slp_ajaxsql_results',$result);
    }

    /**
     * Add sort by distance ASC as default order.
     */
    function add_distance_sort_to_orderby() {
        $this->slplus->database->extend_order_array( 'sl_distance ASC' );
    }

    /**
     * Do not return private locations by default.
     *
     * @param string the current where clause
     * @return string the extended where clause
     */
    function filter_out_private_locations( $where ) {
        return $this->slplus->database->extend_Where( $where , ' ( NOT sl_private OR sl_private IS NULL) ' );
    }

    /**
     * Output a JSON response based on the incoming data and die.
     *
     * Used for AJAX processing in WordPress where a remote listener expects JSON data.
     *
     * @param mixed[] $data named array of keys and values to turn into JSON data
     * @return null dies on execution
     */
    function renderJSON_Response($data) {

        // What do you mean we didn't get an array?
        //
        if (!is_array($data)) {
            $data = array(
                'success'       => false,
                'count'         => 0,
                'message'       => __('renderJSON_Response did not get an array()','csa-slplus')
            );
        }

        // Add our SLP Version and DB Query to the output
        //
        $data = array_merge(
                    array(
                        'success'       => true,
                        'slp_version'   => SLPLUS_VERSION,
                        'dbQuery'       => $this->dbQuery
                    ),
                    $data
                );
        $data = apply_filters('slp_ajax_response' , $data );

        // Tell them what is coming...
        //
        header( "Content-Type: application/json" );

        // Go forth and spew data
        //
        echo json_encode($data);

        // Then die.
        //
        die();
    }
}
