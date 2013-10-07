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
     * @var object $db
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
    public function __construct() {
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
                    'valid_latlong'         => " sl_latitude REGEXP '^[0-9]|-' AND sl_longitude REGEXP '^[0-9]|-' ",
                ),
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
     * Get an SQL statement for this database.
     *
     * Processed the commandList in order.
     *
     * Usually a select followed by a where and possibly a limit or order by
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
                    $sqlStatement .= 'SELECT * FROM '       .$this->info['table'].' ';
                    break;

                case 'selectslid':
                    $sqlStatement .= 'SELECT sl_id FROM '   .$this->info['table'].' ';
                    break;

                // WHERE
                //
                case 'whereslid':
                    $sqlStatement .= 'WHERE sl_id=%d ';
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
}
