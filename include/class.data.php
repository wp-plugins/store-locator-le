<?php
/**
 * The data interface helper.
 *
 * @package StoreLocatorPlus\Data
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2013 Charleston Software Associates, LLC
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
     * Info strings for the database interface.
     *
     * info['table'] = table name
     * info['query'] = array of query strings
     * 
     * @var string[] database info
     */
    public $info;

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
            if( ! empty($this->db->charset ) ) $collate .= "DEFAULT CHARACTER SET {$this->db->charset}";
            if( ! empty($this->db->collate ) ) $collate .= " COLLATE {$this->db->collate}";
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
     * Add elements to the order by clause, adding a comma if needed.
     * 
     * @param string $startwith the starting order by clause
     * @param string $add what to add
     * @return string the extended order by with comma if needed (no ORDER BY prefix)
     */
    function extend_OrderBy($startwith,$add) {
        return $startwith.(empty($startwith)?'':',').$add;
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

                // WHERE
                //
                case 'where_default':
                case 'where_default_validlatlong':
                    if ($command === 'where_default_validlatlong') {
                        add_filter('slp_ajaxsql_where',array($this,'filter_SetWhereValidLatLong'));
                    }

                    // FILTER: slp_ajaxsql_where
                    //
                    $where = apply_filters('slp_ajaxsql_where','');
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
                    // FILTER: slp_ajaxsql_orderby
                    $order = apply_filters('slp_ajaxsql_orderby','');
                    if (!empty($order)) {
                        $sqlStatement .= ' ORDER BY ' . $order . ' ';
                    }
                    break;

                // FILTER: slp_extend_get_SQL
                //
                default:
                    $sqlStatement .= apply_filters('slp_extend_get_SQL',$command);
                    break;
            }
        }
        return $sqlStatement;
    }


    /**
     *Return a record as an array based on a given SQL select statement keys and params list.
     *
     * @param string[] $commandList
     * @param mixed[] $params
     * @param int $offset
     */
    function get_Record($commandList,$params=array(),$offset=0) {
        return
            $this->db->get_row(
                $this->db->prepare(
                    $this->get_SQL($commandList),
                    $params
                    ),
                ARRAY_A,
                $offset
            );
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
}
