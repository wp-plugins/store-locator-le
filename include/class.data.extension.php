<?php

/**
 * The extended data interface helper.  Managed the extended data columns when needed.
 *
 * @package StoreLocatorPlus\Data\Extension
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2014 Charleston Software Associates, LLC
 *
 */
class SLPlus_Data_Extension {
    //-------------------------------------------------
    // Properties
    //-------------------------------------------------

    /**
     * The properties of the meta table.
     *
     * metatable['name']
     *
     * metatable['records'][<slug>][id|field_id|label|slug|type|options]
     *
     * - name = the name of the meta table.
     * - records = a named array, keys are field slugs => values are named arrays of the properties
     *
     *   - <slug> the field slug is the key
     *
     *       - id = the unique id for this field
     *       - field_id = the unique id as a string field_###
     *       - label = the proper case label
     *       - slug = the "slugified" version of the label
     *       - type = the field type varchar(default)/text/int/boolean
     *       - options (serialized)     *
     *
     * @var string[] $metatable
     */
    var $metatable;

    /**
     * The SLP plugin.
     *
     * @var \SLPlus $plugin
     */
    private $slplus;

    /**
     * Properties of the plugin data table.
     *
     * 'name'   = table name
     * 'fields' = key/value pair key = field name, value = field format
     *
     * @var string[] $plugintable
     */
    public $plugintable;

    //-------------------------------------------------
    // Methods
    //-------------------------------------------------

    /**
     * Invoke a new \SLPlus_Data_Extended object.
     */
    function __construct($params) {

        // Set properties based on constructor params,
        // if the property named in the params array is well defined.
        //
        if ($params !== null) {
            foreach ($params as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }
        }

        // Set the plugin details table properties
        //
        $this->metatable['name'] = $this->slplus->db->prefix . 'slp_extendo_meta';
        $this->metatable['records'] = array();
        $this->plugintable['name'] = $this->slplus->db->prefix . 'slp_extendo';
        $this->plugintable['fields'] = array(
            'id' => '%u',
            'sl_id' => '%u',
            'value' => '%s'
        );


        // Filters To Extend Data Queries
        //
        if ($this->has_ExtendedData()) {
            add_filter('slp_extend_get_SQL', array($this, 'filter_ExtendedDataQueries'));
            add_filter('slp_extend_get_SQL_selectall', array($this, 'filter_ExtendSelectAll'));
        }
    }

    /**
     * Adds a field to the data table
     *
     * mode parameter
     * - 'immediate' = default, run create table command when adding the field
     * - 'wait' = do not run the create table command when adding this field
     *
     * @param $label string The label to create
     * @param $type string The type of the label to create
     * @param $options mixed[] wpdb insert options
     * @param $mode string operating mode
     *
     * @return string the slug of the field that was added.
     */
    function add_field($label, $type = 'text', $options = array(), $mode = 'immediate') {
        $nextval = $this->slplus->options_nojs['next_field_id'] ++;
        $nextval = str_pad($nextval, 3, "0", STR_PAD_LEFT);
        update_option(SLPLUS_PREFIX . '-options_nojs', $this->slplus->options_nojs);


        // Check whether slug is provided in $options
        //
        add_filter('sanitize_title', array($this, 'filter_SanitizeTitleForMySQLField'), 10, 3);
        if (isset($options['slug']) && (trim($options['slug']) !== '')) {
            $slug = $options['slug'];
        } else {
            $slug = sanitize_title($label, '', 'save');
        }
        remove_filter('sanitize_title', array($this, 'filter_SanitizeTitleForMySQLField'));

        // Check if slug already exists before adding it.
        //
        if (!$this->has_field($slug)) {

            $this->slplus->db->insert(
                $this->metatable['name'],
                array(
                    'field_id'  => 'field_' . $nextval,
                    'label'     => $label,
                    'slug'      => $slug,
                    'options'   => maybe_serialize($options),
                    'type'      => $type
                )
            );

            if ($mode === 'immediate') {
                $this->update_data_table(array('mode' => 'force'));
            }

        }

        return $slug;
    }

    /**
     * Removes a field from the data table
     *
     * mode parameter
     * - 'immediate' = default, run update table command when removing the field
     * - 'wait' = do not run the update table command when removing this field
     *
     * @param $label string The label to remove
     * @param $options mixed[] wpdb options
     * @param $mode string operating mode
     *
     * @return string slug of the removed field.
     */
    function remove_field($label, $options = array(), $mode = 'immediate') {

        // Check whether a slug is provided in $options
        add_filter('sanitize_title', array($this, 'filter_SanitizeTitleForMySQLField'), 10, 3);
        if (isset($options['slug']) && (trim($options['slug']) !== '')) {
            $slug = $options['slug'];
        } else {
            $slug = sanitize_title($label, '', 'save');
        }
        remove_filter('sanitize_title', array($this, 'filter_SanitizeTitleForMySQLField'));

        // Check if slug exists before removing it.
        //
            if ($this->has_field($slug)) {
            $this->slplus->db->delete($this->metatable['name'], array('slug' => $slug));
            if ($mode === 'immediate') {
                $this->update_data_table(array('mode' => 'force'));
            }
        }

        return $slug;
    }

    /**
     * Extend the SQL query set for extended data queries.
     *
     * @param string $command
     * @return string
     */
    function filter_ExtendedDataQueries($command) {
        switch ($command) {
            // SELECT
            //
            case 'select_all_from_extendo':
                return "SELECT * FROM {$this->metatable['name']}";

            // JOIN
            //
            case 'join_extendo':
                return ' LEFT JOIN ' . $this->plugintable['name'] . ' USING(sl_id) ';

            // WHERE
            //
            case 'where_slugis':
                return ' WHERE slug = %s ';

            // DEFAULT
            //
            default:
                return $command;
        }
    }

    /**
     * Add the join clause to the base plugin select all clause.
     *
     * @param string $sqlStatement the existing SQL command for Select All
     * @return string
     */
    function filter_ExtendSelectAll($sqlStatement) {
        if (false !== strpos('LEFT JOIN ' . $this->metatable['name'], $sqlStatement)) {
            return $sqlStatement;
        }
        return $sqlStatement . $this->filter_ExtendedDataQueries('join_extendo');
    }

    /**
     * Replace hyphens with underscore to make "titles" MySQL field name appropriate.
     *
     * @param string $title party cleaned up title
     * @param string $raw_title original title
     * @param string $context mode that sanitize_title was called with such as 'query' or 'save'
     * @return string sanitized title string with no hyphens in it
     */
    function filter_SanitizeTitleForMySQLField($title, $raw_title, $context) {
        return str_replace('-', '_', $title);
    }

    /**
     * Reads the metadata from the slp_extendo_meta table as OBJECTS and stores it in metatable['records'][<slug>]
     *
     * @param boolean $force = set true to force reloading of data.
     */
    function set_cols($force = false) {
        if (( count($this->metatable['records']) === 0 ) || $force) {
            $meta_data = $this->slplus->db->get_results("SELECT * FROM {$this->metatable['name']}", OBJECT);
            foreach ($meta_data as $field) {
                $this->metatable['records'][$field->slug] = $field;
            }
        }
    }

    /**
     * Return an array of the meta data field properties.
     *
     * @param boolean $force force a re-read of the meta data from disk.
     * @return array an array of arrays containing the meta data field values.
     */
    function get_cols($force = false) {
        $this->set_cols($force);
        return array_values($this->metatable['records']);
    }

    /**
     * Gets data for a store id, useful in cases when a join isn't required
     * @param $sl_id int The id to lookup
     * @param $field_id string (optional) The field id to return when only one field is needed
     * @return mixed The column (string) or an array of all the columns
     */
    function get_data($sl_id, $field_id = null) {
        global $wpdb;
        $query = $wpdb->prepare("select * from {$this->plugintable['name']} where sl_id = %s", $sl_id);
        $cols = $wpdb->get_results($query, ARRAY_A);
        if ($cols === null) {
            return;
        }
        if (count($cols) < 1) {
            return;
        }

        if (isset($field_id)) {
            return $cols[0][$field_id];
        }

        return $cols[0];
    }

    /**
     * Return true if the database is extended and has an extended data table.
     *
     * @return boolean
     */
    public function has_ExtendedData() {
	    if ( $this->slplus->is_CheckTrue( $this->slplus->options_nojs['extended_data_tested'] ) ) {
		    return $this->slplus->is_CheckTrue( $this->slplus->options_nojs['has_extended_data'] );
	    }

	    return $this->set_extended_data_tested();
    }


	/**
	 * Do a deeper test for extended data and return true if there is extended data.
	 *
	 * @return bool
	 */
	private function set_extended_data_tested() {
	    global $wpdb;
	    $extended_data_count = 0;
	    if ( $wpdb->get_var("show tables like '{$this->plugintable['name']}'") === $this->plugintable['name'] ) {
		    $extended_data_count = $wpdb->get_var( "select count(*) from {$this->plugintable['name']} limit 1" );
	    }

	    $really_has_extended_data = ( $extended_data_count > 0 );
	    $this->slplus->options_nojs['has_extended_data']  =  $really_has_extended_data;
		$this->slplus->options_nojs['extended_data_tested'] = '1';
		update_option(SLPLUS_PREFIX . '-options_nojs', $this->slplus->options_nojs);

        return $really_has_extended_data;
    }

    /**
     * Tell people if the extended data contains a field identified by slug.
     *
     * @param string $slug the field slug
     * @return boolean true if the field exists, false if not.
     */
    function has_field($slug) {
        if (!isset($this->metatable['records'][$slug])) {
            $slug_data = $this->slplus->database->get_Record(array('select_all_from_extendo', 'where_slugis'), $slug, 0 , OBJECT);
            if ( is_object( $slug_data ) && ( $slug_data->slug == $slug ) ) {
                $this->metatable['records'][$slug] = $slug_data;
            }
        }
        $this->slplus->debugMP('slp.main', 'pr', 'SLPlus_Data_Extension::' . __FUNCTION__ . ' ' . $slug, $this->metatable);
        return ( isset($this->metatable['records'][$slug]) );
    }

    /**
     * Update an sl_id's data
     * @param $sl_id int The id of the location
     * @param $data mixed The col => value pairs to update
     */
    function update_data($sl_id, $data) {
        global $wpdb;

        $currentData = $this->get_data($sl_id);

        // No Current Data?  Insert
        //
        if ($currentData === null) {
            $data['sl_id'] = $sl_id;
            $wpdb->insert($this->plugintable['name'], $data);
        } else {
            $data = array_merge($currentData, $data);
            $replacementCount = $wpdb->update($this->plugintable['name'], $data, array('sl_id' => $data['sl_id']));
        }
    }

    /**
     * Updates the meta data table used to control the field info in the extension data table.
     *
     * Table is created or modified whenever a new data field is added.
     *
     * Accepted $params values
     *
     * - 'mode' determines which mode to operate in:
     *
     *  - 'force' = force re-read of metadata
     *
     *  - null    = default, use in-memory cache of metadata to build create SQL string
     *
     * @global array $EZSQL_ERROR
     * @param array $params
     *
     */
    function update_data_table($params = array()) {
        $extended_fields = $this->get_cols(isset($params['mode']) && ( $params['mode'] = 'force'));

        // If we have some extended data fields...
        //
        if (count($extended_fields) > 0) {
            $create = "CREATE TABLE {$this->plugintable['name']} (
            id mediumint(8) NOT NULL AUTO_INCREMENT,
            sl_id mediumint(8) UNSIGNED NOT NULL,
            ";
            foreach ($extended_fields as $field) {
                if ( is_object( $field ) ) {
                    switch ($field->type) {
                        case 'text':
                            $type = 'longtext';
                            break;
                        case 'varchar':
                            $type = 'varchar(250)';
                            break;
                        default:
                            $type = $field->type;
                            break;
                    }

                    $create .= $field->slug . " $type" . ",\n";
                }
            }

            $create .=
                    "KEY sl_id (sl_id),
                KEY id (id),
                KEY slid_id (sl_id,id)
                ) {$this->slplus->database->collate}";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($create);
            global $EZSQL_ERROR;
            $EZSQL_ERROR = array();

            // Set the plugin "has extended data" property.
            //
            $this->slplus->options_nojs['has_extended_data'] = '1';
            update_option(SLPLUS_PREFIX . '-options_nojs', $this->slplus->options_nojs);

            // No extended data fields
        //
        } else {
            $this->slplus->options_nojs['has_extended_data'] = '0';
            update_option(SLPLUS_PREFIX . '-options_nojs', $this->slplus->options_nojs);
        }
    }

}
