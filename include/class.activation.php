<?php
/**
 * Store Locator Plus Activation handler.
 *
 * Mostly handles data structure changes.
 * Update the plugin version in config.php on every structure change.
 *
 * @package StoreLocatorPlus\Activation
 * @author Lance Cleveland <lance@storelocatorplus.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
 */
class SLPlus_Activation {

    //----------------------------------
    // Properties
    //----------------------------------

    /**
     * Starting DB version of this plugin.
     *
     * @var string $db_version_on_start
     */
    public  $db_version_on_start = '';

    /**
     * Starting Plugin version.
     *
     * @var string $plugin_version_on_start
     */
    public $plugin_version_on_start;

	/**
	 * @var SLPlus
	 */
	private $slplus;


    //----------------------------------
    // Methods
    //----------------------------------

    /**
     * Initialize the object.
     */
    function __construct() {
	    global $slplus_plugin;
	    $this->slplus = $slplus_plugin;
    } 

    /**
     * Update the data structures on new db versions.
     *
     * @global object $wpdb
     * @param type $sql
     * @param type $table_name
     * @return string
     */
    function dbupdater($sql,$table_name) {
        global $wpdb;
        $retval = ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) ? 'new' : 'updated';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $were_showing_errors = $wpdb->show_errors;
        $wpdb->hide_errors();
        dbDelta($sql);
        global $EZSQL_ERROR;
        $EZSQL_ERROR=array();
        if ( $were_showing_errors ) {
            $wpdb->show_errors();
        }
        
        return $retval;
    }

    /**
     * Setup the extended data tables.
     */
    function install_ExtendedDataTables() {
        global $wpdb;
        $charset_collate = '';
        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        // Meta Data Table
        // Contains the architecture of the extended data fields.
        //
        $table_name = $wpdb->prefix . 'slp_extendo_meta';
        $sql = "CREATE TABLE $table_name (
                        id mediumint(8) not null auto_increment,
                        field_id varchar(15),
                        label varchar(250),
                        slug varchar(250),
                        type varchar(55),
                        options text,
                        KEY id (id),
                        KEY field_id (field_id),
                        KEY label (label),
                        KEY slug (slug)
                )
                $charset_collate
                ";
        $table_status = $this->dbupdater($sql,$table_name);

        // Extendo Table Was Already Here
        // Set the next field id based on the extendo options and write it back out.
        //
        if ($table_status === 'updated') {

            // Check that we did not import the next field ID first.
            //
            $slplus_options = get_option(SLPLUS_PREFIX.'-options_nojs');
            if ( ! isset($slplus_options['next_field_ported']) || empty($slplus_options['next_field_ported']) ) {

                // Get the next field ID From Extendo
                //
                $extendo_options = get_option('slplus-extendo-options');
                if ( isset ( $extendo_options['next_field_id'] ) ) {
                    if ( ! is_array( $slplus_options )                   ) { $slplus_options = array();                    }
                    if ( isset ( $extendo_options['installed_version'] ) ) { unset($extendo_options['installed_version']); }
                    array_merge($slplus_options, $extendo_options);
                }

                // Set the ported flag and write that along with next field ID back to database.
                //
                $slplus_options['next_field_ported'] = '1';
                update_option(SLPLUS_PREFIX.'-options_nojs', $slplus_options);
            }
        }
    }

    /*************************************
     * Update main table
     *
     * As of version 3.5, use sl_option_value to store serialized options
     * related to a single location.
     *
     * Update the plugin version in config.php on every structure change.
     *
     */
    function install_main_table() {

        global $wpdb;

        $charset_collate = '';
        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";	
        $table_name = $wpdb->prefix . "store_locator";
        $sql = "CREATE TABLE $table_name (
                sl_id mediumint(8) unsigned NOT NULL auto_increment,
                sl_store varchar(255) NULL,
                sl_address varchar(255) NULL,
                sl_address2 varchar(255) NULL,
                sl_city varchar(255) NULL,
                sl_state varchar(255) NULL,
                sl_zip varchar(255) NULL,
                sl_country varchar(255) NULL,
                sl_latitude varchar(255) NULL,
                sl_longitude varchar(255) NULL,
                sl_tags mediumtext NULL,
                sl_description text NULL,
                sl_email varchar(255) NULL,
                sl_url varchar(255) NULL,
                sl_hours varchar(255) NULL,
                sl_phone varchar(255) NULL,
                sl_fax varchar(255) NULL,
                sl_image varchar(255) NULL,
                sl_private varchar(1) NULL,
                sl_neat_title varchar(255) NULL,
                sl_linked_postid int(11) NULL,
                sl_pages_url varchar(255) NULL,
                sl_pages_on varchar(1) NULL,
                sl_option_value longtext NULL,
                sl_lastupdated timestamp NOT NULL default CURRENT_TIMESTAMP,
                PRIMARY KEY  (sl_id),
                KEY sl_store (sl_store),
                KEY sl_longitude (sl_longitude),
                KEY sl_latitude (sl_latitude)
                ) 
                $charset_collate
                ";

        // If we updated an existing DB, do some mods to the data
        //
        if ($this->dbupdater($sql,$table_name) === 'updated') {
            // We are upgrading from something less than 2.0
            //
            if (floatval($this->db_version_on_start) < 2.0) {
                dbDelta("UPDATE $table_name SET sl_lastupdated=current_timestamp " . 
                    "WHERE sl_lastupdated < '2011-06-01'"
                    );
            }   
            if (floatval($this->db_version_on_start) < 2.2) {
                dbDelta("ALTER $table_name MODIFY sl_description text ");
            }
        }         

        //set up google maps v3
        $old_option = get_option('sl_map_type','roadmap');
        if (!in_array($old_option,array('roadmap','satellite','hybrid','terrain'))) {
            $new_option = 'roadmap';
            switch ($old_option) {
                case 'G_NORMAL_MAP':
                    $new_option = 'roadmap';
                    break;
                case 'G_SATELLITE_MAP':
                    $new_option = 'satellite';
                    break;
                case 'G_HYBRID_MAP':
                    $new_option = 'hybrid';
                    break;
                case 'G_PHYSICAL_MAP':
                    $new_option = 'terrain';
                    break;
                default:
                    $new_option = 'roadmap';
                    break;
            }
            update_option('sl_map_type', $new_option);
        }
    }

    /*************************************
     * Add roles and caps
     */
    function add_splus_roles_and_caps() {
        $role = get_role('administrator');
        if (is_object($role) && !$role->has_cap('manage_slp_admin')) {
            $role->add_cap('manage_slp');
			$role->add_cap('manage_slp_admin');
			$role->add_cap('manage_slp_user');
        }
    }


	/**
	 * Recursively copy source directory (or file) into destination directory.
	 *
	 * @param $source can be a file or a directory
	 * @param $dest can be a file or a directory
	 */
    private function copyr($source, $dest) {
	    if ( ! file_exists( $source ) ) { return; }

	    // Make destination directory if necessary
	    //
	    if ( ! is_dir( $dest ) ) {
		    wp_mkdir_p( $dest );
	    }

	    // Loop through the folder
	    $dir = dir($source);
	    if ( is_a( $dir , 'Directory' ) ) {
		    while ( false !== $entry = $dir->read() ) {

			    // Skip pointers
			    if ( $entry == '.' || $entry == '..' ) {
				    continue;
			    }

			    $source_file = "{$source}/{$entry}";
			    $dest_file = "{$dest}/{$entry}";

			    // Copy Files
			    //
			    if ( is_file( $source_file ) ) {
				    $this->copy_newer_files( $source_file , $dest_file );
			    }

			    // Copy Symlinks
			    //
			    if ( is_link( $source_file ) ) {
				    symlink( readlink( $source_file ), $dest_file );
			    }

			    // Directories, go deeper
			    //
			    if ( is_dir( $source_file ) ) {
				    $this->copyr( $source_file , $dest_file );
			    }
		    }

		    // Clean up
		    $dir->close();
	    }
    }

	/**
	 * Copy non-empty, readable files to destination if they are newer than the destination file.
	 * OR if the destination file does not exist.
	 *
	 * @param $source_file
	 * @param $destination_file
	 */
	public function copy_newer_files( $source_file , $destination_file ) {
		if ( empty( $source_file) ) { return; }
		if ( ! is_readable( $source_file ) ) { return; }
		if (
		    ! file_exists( $destination_file ) ||
		    (
			    file_exists( $destination_file ) &&
			    ( filemtime( $source_file ) >  filemtime( $destination_file ) )
		    )
		) {
			copy( $source_file, $destination_file );
		}
	}

	/**
	 * Update the plugin.
	 */
    public function update() {

        // New Installation
        //
        $this->db_version_on_start     = get_option( SLPLUS_PREFIX."-db_version" , '' );
        if ($this->db_version_on_start == '') {
            add_option(SLPLUS_PREFIX."-db_version", SLPLUS_VERSION);
            add_option(SLPLUS_PREFIX.'_disable_find_image','1');                // Disable the image find locations on new installs

        // Updating previous install
        //
        } else {
            $options_changed = false;
            $this->plugin_version_on_start = $this->slplus->installed_version;

	        // Restore Custom CSS Files
	        $this->copyr( SLPLUS_UPLOADDIR . "css", SLPLUS_PLUGINDIR . "css" );

            // Core Icons Moved
            // 3.8.6
            //
            if (is_dir(SLPLUS_PLUGINDIR.'core/images/icons/')) {

                // Change home and end icon if it was in core/images/icons
                //
                update_option('sl_map_home_icon', $this->iconMapper(get_option('sl_map_home_icon')));
                update_option('sl_map_end_icon' , $this->iconMapper(get_option('sl_map_end_icon') ));
            }

            // Upgrading to version 4.0.033
            //
            if ( version_compare($this->plugin_version_on_start,'4.0.033','<') ) {

                // Max Search Results Setting
                //
                $max_results = get_option(SLPLUS_PREFIX.'_maxreturned','25');
                $this->slplus->options_nojs['max_results_returned'] = empty( $max_results ) ? '25' : $max_results;
                delete_option(SLPLUS_PREFIX.'_maxreturned');
                $options_changed = $options_changed || ($max_results !== $this->slplus->options_nojs['max_results_returned']);

                // Number To Show Initially Setting
                //
                $initial_results = get_option('sl_num_initial_displayed','25');
                $this->slplus->options['initial_results_returned'] = empty( $initial_results ) ? '25' : $initial_results;
                delete_option('sl_num_initial_displayed');
                $options_changed = $options_changed || ($initial_results !== $this->slplus->options['initial_results_returned']);

            }
            
            // Upgrading to version 4.1.13
            //
            if ( version_compare($this->plugin_version_on_start,'4.1.13','<') ) {

                // Force Load JS serialization
                //
                $option_name  = SLPLUS_PREFIX.'-force_load_js';
                $option_value = get_option( $option_name , false );
                $serial_key   = 'force_load_js';
                $this->slplus->options_nojs[$serial_key] = $option_value;
                delete_option( $option_name );
                $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[$serial_key] );
                
                // Immediately Show Locations serialization
                //
                $option_name  = 'sl_load_locations_default';
                $option_value = get_option( $option_name , true );
                $serial_key   = 'immediately_show_locations';
                $this->slplus->options_nojs[$serial_key] = $option_value;
                delete_option( $option_name );
                $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[$serial_key] );
            }

            // Upgrading to version 4.2.04
            //
            if ( version_compare($this->plugin_version_on_start,'4.2.04','<') ) {

                // sl_distance_unit option => slplus->options['distance_unit']
                //
                $option_name  = 'sl_distance_unit';
                $serial_key   = 'distance_unit';
                $option_value = get_option( $option_name , false );
                $this->slplus->options[$serial_key] = $option_value;
                delete_option( $option_name );
                $options_changed = true; // new setting always write the options array to disk

                // sl_google_map_domain => slplus->options['map_domain']
                //
                $option_name  = 'sl_google_map_domain';
                $serial_key   = 'map_domain';
                $option_value = get_option( $option_name , 'maps.google.com' );
                if ($option_value === 'maps.googleapis.com') { $option_value = 'maps.google.com'; }
                $this->slplus->options[$serial_key] = $option_value;
                delete_option( $option_name );
                $options_changed = true; // new setting always write the options array to disk
            }

            // Upgrading to version 4.2.26
            //
            if ( version_compare($this->plugin_version_on_start,'4.2.26','<') ) {
                $option_name  = SLPLUS_PREFIX.'_use_email_form';
                delete_option($option_name);
            }

	        // Upgrading to version 4.2.63
	        //
	        if ( version_compare($this->plugin_version_on_start,'4.2.63','<') ) {

		        // Force Load JS serialization
		        //
		        $option_name                               = 'sl_map_height';
		        $option_value                              = get_option( $option_name, '480' );
		        $serial_key                                = 'map_height';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'sl_map_height_units';
		        $option_value                              = get_option( $option_name, 'px' );
		        $serial_key                                = 'map_height_units';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'sl_map_width';
		        $option_value                              = get_option( $option_name, '640' );
		        $serial_key                                = 'map_width';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'sl_map_width_units';
		        $option_value                              = get_option( $option_name, 'px' );
		        $serial_key                                = 'map_width_units';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'sl_map_type';
		        $option_value                              = get_option( $option_name, 'roadmap' );
		        $serial_key                                = 'map_type';
		        $this->slplus->options[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options[ $serial_key ] );

		        $option_name                               = 'sl_map_end_icon';
		        $option_value                              = get_option( $option_name, SLPLUS_ICONURL . 'box_yellow_home.png' );
		        $serial_key                                = 'map_end_icon';
		        $this->slplus->options[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options[ $serial_key ] );

		        $option_name                               = 'sl_map_home_icon';
		        $option_value                              = get_option( $option_name, SLPLUS_ICONURL . 'bulb_azure.png' );
		        $serial_key                                = 'map_home_icon';
		        $this->slplus->options[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options[ $serial_key ] );

		        $option_name                               = 'sl_remove_credits';
		        $option_value                              = get_option( $option_name, '0' );
		        $serial_key                                = 'remove_credits';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'sl_admin_locations_per_page';
		        $option_value                              = get_option( $option_name, '10' );
		        $serial_key                                = 'admin_locations_per_page';
		        $this->slplus->options_nojs[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options_nojs[ $serial_key ] );

		        $option_name                               = 'csl-slplus_map_center';
		        $option_value                              = get_option( $option_name, '' );
		        $serial_key                                = 'map_center';
		        $this->slplus->options[ $serial_key ] = $option_value;
		        delete_option( $option_name );
		        $options_changed = $options_changed || ( $option_value !== $this->slplus->options[ $serial_key ] );

	        }

            // Upgrading to version 4.1.XX+
            // Always re-load theme details data.
            //
            if ( version_compare($this->plugin_version_on_start,'4.99.99','<') ) {
                delete_option(SLPLUS_PREFIX.'-api_key');
                delete_option(SLPLUS_PREFIX.'-theme_details');
                delete_option(SLPLUS_PREFIX.'-theme_array');
                delete_option(SLPLUS_PREFIX.'-theme_lastupdated');
                
                // Deactivate Super Extendo
                //
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                if ( function_exists('deactivate_plugins') ) {
                    deactivate_plugins('slp-super-extendo/slp-extendo.php');
                }

            }

            // Save Serialized Options
            //
            if ($options_changed) {
                update_option(SLPLUS_PREFIX.'-options_nojs' , $this->slplus->options_nojs);
                update_option(SLPLUS_PREFIX.'-options'      , $this->slplus->options     );
            }

            // Set DB Version
            //
            update_option(SLPLUS_PREFIX."-db_version", SLPLUS_VERSION);
        }
	    update_option(SLPLUS_PREFIX . '-installed_base_version', SLPLUS_VERSION);
        update_option(SLPLUS_PREFIX.'-theme_lastupdated','2006-10-05');

        // Update Tables, Setup Roles
        //
        $this->install_main_table();
        $this->install_ExtendedDataTables();
        $this->add_splus_roles_and_caps();
    }

    /**
     * Updates specific to 3.8.6
     *
     * @param string $iconFile
     * @return string icon file
     */
    function iconMapper($iconFile) {
        $newIcon = $iconFile;

        // Azure Bulb Name Change (default destination marker)
        //
        $newIcon =
            str_replace(
                '/store-locator-le/core/images/icons/a_marker_azure.png',
                '/store-locator-le/images/icons/bulb_azure.png',
                $iconFile
            );
        if ($newIcon != $iconFile) { return $newIcon; }

        // Box Yellow Home (default home marker)
        //
        $newIcon =
            str_replace(
                '/store-locator-le/core/images/icons/sign_yellow_home.png',
                '/store-locator-le/images/icons/box_yellow_home.png',
                $iconFile
            );
        if ($newIcon != $iconFile) { return $newIcon; }

        // General core/images/icons replaced with images/icons
        $newIcon =
            str_replace(
                '/store-locator-le/core/images/icons/',
                '/store-locator-le/images/icons/',
                $iconFile
            );
        if ($newIcon != $iconFile) { return $newIcon; }

        return $newIcon;
    }
	
}

// Dad. Explorer. Rum Lover. Code Geek. Not necessarily in that order.
