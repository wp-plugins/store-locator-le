<?php
/**
 * The data interface helper.
 *
 * @package StoreLocatorPlus\Data
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 - 2014 Charleston Software Associates, LLC
 *
 */
class SLPlus_Data {

    /**
     * The global WordPress DB
     *
     * @var \wpdb $db
     */
    public $db;

    /**
     * The extended data object.
     * 
     * @var \SLPlus_Data_Extended $extension
     */
    public $extension;

    /**
     * Info strings for the database interface.
     *
     * info['table'] = table name
     * info['query'] = array of query strings
     * 
     * @var string[] database info
     */
    public $info;

    /**
     * True if the extended data set is available.
     *
     * @var boolean $is_extended
     */
    public $is_extended = false;

    /**
     * The various order by clauses used by the location selection clause.
     *
     * @var string[]
     */
    public $order_by_array = array();

    //-------------------------------------------------
    // Methods
    //-------------------------------------------------

    /**
     * Initialize a new data object.
     *
     */
    public function SLPlus_Data($params=null) {
        global $wpdb;
        $this->db = $wpdb;

        // Collation
        //
        $collate = '';
        if( $this->db->has_cap( 'collation' ) ) {
            if( ! empty($this->db->charset ) ) { $collate .= "DEFAULT CHARACTER SET {$this->db->charset}"; }
            if( ! empty($this->db->collate ) ) { $collate .= " COLLATE {$this->db->collate}"; }
        }
        $this->collate   = $collate;

        // Legacy stuff - replace with data property below.
        //
        $this->info = array(
            'table'     => $this->db->prefix.'store_locator'   ,
            'query'     =>
                array(
                    'selectthis'            => 'SELECT %s FROM '.$this->db->prefix.'store_locator ' ,
                ),
        );

    }

    /**
     * Extend the database by adding the meta and extended data table helper object.
     */
    public function createobject_DatabaseExtension() {
        if ( ! class_exists( 'SLPlus_Data_Extension' ) ) {
            require_once('class.data.extension.php');
        }
        if ( ! isset( $this->extension ) ) {
            global $slplus_plugin;
            $this->extension = new SLPlus_Data_Extension(
                        array(
                            'slplus' => $slplus_plugin
                        )
                    );
        }
    }

    /**
     * Add new strings to the order by array property.
     *
     * @param $new_string
     */
    function extend_order_array( $new_string ) {
        $new_string = trim( strtolower( $new_string ) );
        if ( ! in_array( $new_string , $this->order_by_array ) ) {
            $this->order_by_array[] = $new_string;
        }
    }

    /**
     * Add elements to the order by clause, adding a comma if needed.
     * 
     * @param string $startwith the starting order by clause
     * @param string $add what to add
     * @return string the extended order by with comma if needed (no ORDER BY prefix)
     */
    function extend_OrderBy( $startwith , $add ) {
        $add = trim( $add );

        // Not adding anything, return starting order by clause
        //
        if ( empty( $add ) ) { return $startwith; }

        // Not starting with anything, return only the add part
        //
        $startwith = trim( $startwith );
        if ( empty( $startwith ) ) { return $add; }

        // Starting text and adding text are both set, put a comma between them.
        //
        return " {$startwith} , {$add}";
    }

    /**
     * Add elements to the where clause, adding AND if needed unless OR specified.
     *
     * @param string $startwith the starting where clause
     * @param string $add what to add
     * @param string $operator which operator to use to join the clause (default: AND)
     * @return string the extended where clause
     */
    function extend_Where($startwith,$add,$operator='AND') {
        $operator = empty($startwith) ? '' : " {$operator} ";
        return $startwith.$operator.$add;
    }

    /**
     * Extend the database WHERE clause with a <field_name>='value' clause.
     *
     * @param $where the current where clause
     * @param $field the field name
     * @param $value the value to compare against
     * @return string the new where clause
     */
    function extend_WhereFieldMatches( $where , $field , $value ) {
        if ( empty( $field ) ) { return $where; }
        return
            $this->extend_Where(
                $where ,
                $this->db->prepare(
                    sprintf('%s=%%s',$field ) ,
                    sanitize_text_field($value)
                )
            );
    }

    /**
     * Add the valid lat/long clause to the where statement.
     *
     * @param string $where the current where clause without WHERE command
     * @return string modified where clause
     */
    function filter_SetWhereValidLatLong($where) {
        return $this->extend_Where($where," sl_latitude REGEXP '^[0-9]|-' AND sl_longitude REGEXP '^[0-9]|-' ");
    }

    /**
     * Get an SQL statement for this database.
     *
     * Processed the commandList in order.
     *
     * Usually a select followed by a where and possibly a limit or order by
     *
     * DELETE
     * o delete - delete from store locator table
     *
     * SELECT
     * o selectall - select from store locator table with additional slp_extend_get_SQL_selectall filter.
     * o selectall_with_distance - select from store locator table with additional slp_extend_get_SQL_selectall filter and distance calculation math, requires extra parm passing on get record.
     * o selectslid - select only the store id from store locator table.
     * o select_state_list - fetch a list of all states in the location table with a valid lat/long and state is not empty.
     *
     * WHERE
     * o where_default - the default where clause that is built up by the slp_ajaxsql_where filters.
     * o where_default_validlatlong - the default with valid lat/long check check added.
     * o whereslid - add where + slid selector, get record requires slid to be passed
     *
     * ORDER BY
     * o orderby_default - add order by if the results of the slp_ajaxsql_orderby filter returns order by criteria.  AJAX listener default is by distance asc.
     *
     * @param string[] $commandList a comma separated array of commands or a single command
     * @return string
     */
    function get_SQL($commandList) {
        // Make all commands an array
        //
        if (!is_array($commandList)){ $commandList = array($commandList); }

        // Build up a single SQL command from the command list array
        //
        $sqlStatement = '';
        foreach ($commandList as $command) {
            switch ($command) {

                // DELETE
                //
                case 'delete':
                    $sqlStatement .= 'DELETE FROM '         .$this->info['table'].' ';
                    break;

                // SELECT
                //
                case 'selectall':
                    // FILTER: slp_extend_get_SQL_selectall
                    $sqlStatement .= apply_filters(
                        'slp_extend_get_SQL_selectall',
                        'SELECT * FROM '       .$this->info['table'].' '
                        );
                    break;

                case 'selectall_with_distance':
                    // FILTER: slp_extend_get_SQL_selectall
                    $sqlStatement .= apply_filters(
                        'slp_extend_get_SQL_selectall',
                        'SELECT *,' .
                        "( %s * acos( cos( radians('%s') ) * cos( radians( sl_latitude ) ) * cos( radians( sl_longitude ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( sl_latitude ) ) ) ) AS sl_distance " .
                        ' FROM ' . $this->info['table'] .' '
                        );
                    break;

                case 'selectslid':
                    $sqlStatement .= 'SELECT sl_id FROM '   .$this->info['table'].' ';
                    break;

                // select_country_list
                // Fetch a list of all countries in the location table where state is not empty.
                //
                case 'select_country_list':
                    $sqlStatement .=
                        'SELECT trim(sl_country) as country ' .
                        ' FROM ' . $this->info['table'] . ' ' .
                        "WHERE sl_country<>'' " .
                        'GROUP BY sl_country ' .
                        'ORDER BY sl_country ASC '
                        ;
                    break;

                // select_state_list
                // Fetch a list of all states in the location table where state is not empty.
                //
                case 'select_state_list':
                    $sqlStatement .=
                        'SELECT trim(sl_state) as state ' .
                        ' FROM ' . $this->info['table'] . ' ' .
                        "WHERE sl_state<>'' " .
                        'GROUP BY sl_state ' .
                        'ORDER BY sl_state ASC '
                        ;
                    break;

                // WHERE
                //
                case 'where_default':
                case 'where_default_validlatlong':
                    if ($command === 'where_default_validlatlong') {
                        add_filter('slp_ajaxsql_where',array($this,'filter_SetWhereValidLatLong'));
                    }

                    // FILTER: slp_location_where
                    // FILTER: slp_ajaxsql_where
                    //
                    $where = apply_filters('slp_ajaxsql_where','');
                    $where = apply_filters('slp_location_where', $where );
                    if (!empty($where)) {
                        $sqlStatement .= ' WHERE ' . $where . ' ';
                    }
                    break;

                case 'whereslid':
                    $sqlStatement .= 'WHERE sl_id=%d ';
                    break;

                // ORDER BY
                //
                case 'orderby_default':

                    // HOOK: slp_orderby_default
                    // Allows processes to extend the oder by string array
                    //
                    do_action( 'slp_orderby_default' , $this->order_by_array );
                    $order_by_string = empty( $this->order_by_array ) ? '' : join( ',' , $this->order_by_array );

                    // FILTER: slp_ajaxsql_orderby
                    $order = apply_filters('slp_ajaxsql_orderby', $order_by_string );
                    if ( ! empty( $order ) ) {
                        $sqlStatement .= ' ORDER BY ' . $order . ' ';
                    }
                    break;

                // FILTER: slp_extend_get_SQL
                //
                default:
                    $sql_from_filter = apply_filters('slp_extend_get_SQL',$command);
                    if ( $sql_from_filter !== $command ) {
                        $sqlStatement .= $sql_from_filter;
                    }
                    break;
            }
        }
        return $sqlStatement;
    }


    /**
     * Return a record as an array based on a given SQL select statement keys and params list.
     *
     * Executes wpdb get_row using the specified SQL statement.
     * If more than one row is returned by the query, only the specified row is returned by the function, but all rows are cached for later use.
     * Returns NULL if no result is found
     *
     * @link https://codex.wordpress.org/Class_Reference/wpdb WordPress WPDB Class
     *
     * @param string[] $commandList
     * @param mixed[] $params
     * @param int $offset
     * @param mixed $type the type of object/array/etc. to return see wpdb get_row.
     * @return mixed the database array_a or object.
     */
    function get_Record($commandList,$params=array(),$offset=0, $type = ARRAY_A ) {
        $this->is_Extended();
        $query = $this->get_SQL($commandList);

        // No placeholders, just call direct with no prepare
        //
        if ( strpos( $query, '%' ) !== false ) {
            $query = $this->db->prepare( $query , $params );
        }

        return $this->db->get_row( $query , $type ,$offset );
    }

    /**
     * Return a single field as a value given SQL select statement keys and params list.
     *
     * If more than one record is returned it will only fetch the result of the first record.
     *
     * @param string[] $commandList
     * @param mixed[] $params
     */
    function get_Value($commandList,$params) {
        return
            $this->db->get_var(
                $this->db->prepare(
                    $this->get_SQL($commandList),
                    $params
                    )
            );
    }

    /**
     * Return true if the Extendo plugin is active.
     */
    public function is_Extended() {
        if ( ! $this->is_extended ) {
            $this->createobject_DatabaseExtension();
            if ( is_a( $this->extension , 'SLPlus_Data_Extension' ) ) {
                $this->is_extended = true;
            }
        }
        return $this->is_extended;
    }
}
