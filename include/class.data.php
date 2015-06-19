<?php
/**
 * The data interface helper.
 *
 * @package StoreLocatorPlus\Data
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 - 2015 Charleston Software Associates, LLC
 *
 */
class SLPlus_Data {

    /**
     * The collate modifier for create table commands.
     *
     * @var string $collate
     */
    public $collate;

	/**
	 * @var bool
	 */
	public $had_where_clause = false;

    /**
     * The global WordPress DB
     *
     * @var \wpdb $db
     */
    public $db;

    /**
     * The extended data object.
     * 
     * @var \SLPlus_Data_Extension $extension
     */
    public $extension;

    /**
     * @var string the SQL group by clause
     */
    public $group_by_clause = '';

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

    /**
     * @var string the SQL order by clause
     */
    public $order_by_clause = '';

	/**
	 * The current SQL statement.
	 *
	 * @var string
	 */
	public $sql_statement = '';

    /**
     * @var string the SQL where clause
     */
    public $where_clause = '';

    //-------------------------------------------------
    // Methods
    //-------------------------------------------------

    /**
     * Initialize a new data object.
     */
    public function __construct( $params = null )
    {
        global $wpdb;
        $this->db = $wpdb;

        // Set parameters.
        //
        if (($params != null) && is_array($params)) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }

        // Collation
        //
        $collate = '';
        if ($this->db->has_cap('collation')) {
            if (!empty($this->db->charset)) {
                $collate .= "DEFAULT CHARACTER SET {$this->db->charset}";
            }
            if (!empty($this->db->collate)) {
                $collate .= " COLLATE {$this->db->collate}";
            }
        }
        $this->collate = $collate;

        // Legacy stuff - replace with data property below.
        //
        $this->info = array(
            'table' => $this->db->prefix . 'store_locator',
            'query' =>
                array(
                    'selectthis' => 'SELECT %s FROM ' . $this->db->prefix . 'store_locator ',
                ),
        );

        $this->add_filters();
        $this->set_properties();
    }

    /**
     * Override to add custom data filters.
     */
    protected function add_filters() {

    }

    /**
     * Override to set custom properties.
     */
    protected function set_properties() {

    }

    /**
     * Create a proper group by clause, adding the starting GROUP BY when necessary.
     *
     * @param $new_clause
     * @return string
     */
    public function add_group_by_clause( $new_clause ) {
        if ( ! empty( $new_clause ) ) {
            if (empty($this->group_by_clause)) {
                $this->group_by_clause = ' GROUP BY ';
            }
            $this->group_by_clause .= ' ' . $new_clause . ' ';
        }
        return $this->group_by_clause;
    }

    /**
     * Create a proper order by clause, adding the starting ORDER BY when necessary.
     *
     * @param $new_clause
     * @return string
     */
    public function add_order_by_clause( $new_clause = null ) {

        // Build new clause from the order by array
        // if not passed in
        //
        if ( $new_clause === null) {
            $this->order_by_clause = '';
            $new_clause = empty( $this->order_by_array ) ? '' : join( ',' , $this->order_by_array );
        }

        // New clause has a order string, build the order by SQL
        //
        if ( ! empty( $new_clause ) ) {
            if ( empty( $this->order_by_clause ) ) {
                $this->order_by_clause = ' ORDER BY ';
            }
            $this->order_by_clause .= ' ' . $new_clause . ' ';
        }
        return $this->order_by_clause;
    }

    /**
     * Create a proper where clause, adding the starting WHERE when necessary.
     *
     * @param $new_clause
     * @return string
     */
    public function add_where_clause( $new_clause , $joiner = 'AND' ) {
        if ( ! empty( $new_clause ) ) {
            if ( empty( $this->where_clause ) ) {
                $this->where_clause = ' WHERE ';
            } else {
                $this->where_clause = ' '. $joiner . ' ';
            }
            $this->where_clause .= ' ' . $new_clause . ' ';
        }
        return $this->where_clause;
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

                //------------------- DELETE
                //
                case 'delete':
                    $sqlStatement .= 'DELETE FROM '         .$this->info['table'].' ';
                    break;

                //------------------- SELECT
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

	            case 'select_country':
		            $sqlStatement .=
			            'SELECT trim(sl_country) as country ' .
			            'FROM ' . $this->info['table'] . ' '
		            ;
		            break;


                // select_states
                //
                case 'select_states':
                    $sqlStatement .=
                        'SELECT trim(sl_state) as state ' .
                        'FROM ' . $this->info['table'] . ' '
                    ;
                    break;

                //------------------- WHERE
                //
                case 'where_default':
                case 'where_default_validlatlong':
                    if ($command === 'where_default_validlatlong') {
                        $where = $this->filter_SetWhereValidLatLong( '' );
                    } else {
                        $where = '';
                    }

                    // FILTER: slp_location_where
                    // FILTER: slp_ajaxsql_where
                    //
                    $where = apply_filters('slp_ajaxsql_where', $where);
                    $where = apply_filters('slp_location_where', $where );
                    $sqlStatement .= $this->add_where_clause( $where );
                    break;

                case 'where_not_private':
                    $sqlStatement .= $this->add_where_clause( "( NOT sl_private OR sl_private IS NULL)" );
                    break;

	            case 'where_valid_country':
		            $sqlStatement .= $this->add_where_clause( "(sl_country<>'') AND (sl_country IS NOT NULL)" );
		            break;

                case 'where_valid_state':
                    $sqlStatement .= $this->add_where_clause( "(sl_state<>'') AND (sl_state IS NOT NULL)" );
                    break;


                case 'whereslid':
                    $sqlStatement .=  $this->add_where_clause('sl_id=%d');
                    break;

                //------------------- ORDER
                //
                case 'orderby_default':

                    // HOOK: slp_orderby_default
                    // Allows processes to extend the oder by string array
                    //
                    // Default AJAX order by distance is priority 100
                    // Tagalong order by category count is priority 5 (if enabled)
                    //
                    do_action( 'slp_orderby_default' , $this->order_by_array );
                    $order_by_string = empty( $this->order_by_array ) ? '' : join( ',' , $this->order_by_array );

                    // FILTER: slp_ajaxsql_orderby
                    $order = apply_filters('slp_ajaxsql_orderby', $order_by_string );
                    if ( ! empty( $order ) ) {
                        $sqlStatement .= ' ORDER BY ' . $order . ' ';
                    }
                    break;

	            case 'order_by_country':
		            $this->extend_order_array( 'sl_country ASC' );
		            $sqlStatement .=
			            $this->add_order_by_clause();
		            break;

                case 'order_by_state':
                    $this->extend_order_array( 'sl_state ASC' );
                    $sqlStatement .=
                        $this->add_order_by_clause();
                    break;

                //------------------- GROUP
                //
	            case 'group_by_country':
		            $sqlStatement .=
			            $this->add_group_by_clause( 'sl_country' );
		            break;

	            case 'group_by_state':
                    $sqlStatement .=
                        $this->add_group_by_clause( 'sl_state' );
                    break;

                //------------------- LIMIT
                //
                case 'limit_one':
                    $sqlStatement .= ' LIMIT 1 ';
                    break;

                //------------------- OFFSET
                //
                case 'manual_offset':
                    $sqlStatement .= ' OFFSET %d ';
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

	    $this->had_where_clause = ! empty( $this->where_clause );
	    $this->sql_statement = $sqlStatement;

	    $this->reset_clauses();

	    return $this->sql_statement;
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
     * @param string $mode = get_row or get_col to fetch a single row or multiple rows of a single column
     * @return mixed the database array_a or object.
     */
    function get_Record($commandList,$params=array(),$offset=0, $type = ARRAY_A , $mode = 'get_row' ) {
        $this->is_Extended();
        $query = $this->get_SQL($commandList);

        // No placeholders, just call direct with no prepare
        //
        if ( strpos( $query, '%' ) !== false ) {
            $query = $this->db->prepare( $query , $params );
	        $this->sql_statement = $query;
        }

        // get_row (default) or get_col based on the mode
        if ( $mode === 'get_col' ) {
            $results = $this->db->get_col( $query, $offset );
        } else {
            $results = $this->db->get_row($query, $type, $offset);
        }

        return $results;
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

	/**
	 * Reset the SQL additive clauses.
	 */
    public function reset_clauses() {
        $this->order_by_clause = '';
        $this->group_by_clause = '';
        $this->where_clause = '';
    }
}
