<?php
/**
 * Store Locator Plus location interface and management class.
 *
 * Make a location an in-memory object and handle persistence via data I/O to the MySQL tables.
 *
 * @package StoreLocatorPlus\Location
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2013 Charleston Software Associates, LLC
 *
 * @property int $id
 * @property string $store          the store name
 * @property string $address
 * @property string $address2
 * @property string $city
 * @property string $state
 * @property string $zip
 * @property string $country
 * @property string $latitude
 * @property string $longitude
 * @property string $tags
 * @property string $description
 * @property string $email
 * @property string $url
 * @property string $hours
 * @property string $phone
 * @property string $fax
 * @property string $image
 * @property boolean $private
 * @property string $neat_title
 * @property int $linked_postid
 * @property string $pages_url
 * @property boolean $pages_on
 * @property string $option_value
 * @property datetime $lastupdated
 * @property mixed[] $settings - the deserialized option_value field
 *
 * @property mixed[] $pageData - the related store_page custom post type properties.
 * @property-read string $pageType - the custom WordPress page type of locations
 * @property-read string $pageDefaultStatus - the default page status
 *
 * @property-read string $dbFieldPrefix - the database field prefix for locations
 * @property-read string[] $dbFields - an array of properties that are in the db table
 *
 * @property SLPlus $plugin - the parent plugin object
 */
class SLPlus_Location {

    //-------------------------------------------------
    // Properties
    //-------------------------------------------------

    // Our database fields
    //

    /**
     * Unique location ID.
     * 
     * @var int $id
     */
    private $id;
    private $store;
    private $address;
    private $address2;
    private $city;
    private $state;
    private $zip;
    private $country;
    private $latitude;
    private $longitude;
    private $tags;
    private $description;
    private $email;
    private $url;
    private $hours;
    private $phone;
    private $fax;
    private $image;
    private $private;
    private $neat_title;
    private $linked_postid;
    private $pages_url;
    private $pages_on;
    private $option_value;
    private $lastupdated;
    
    /**
     * Extended data values.
     * 
     * @var mixed[] $exdata
     */
    private $exdata;

    /**
     * The WordPress database connection.
     * 
     * @var \wpdb $db
     */
    private $db;

    // The database map
    //
    private $dbFields = array(
            'id',
            'store',
            'address',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'latitude',
            'longitude',
            'tags',
            'description',
            'email',
            'url',
            'hours',
            'phone',
            'fax',
            'image',
            'private',
            'neat_title',
            'linked_postid',
            'pages_url',
            'pages_on',
            'option_value',
            'lastupdated'
        );

    /**
     * The deserialized option_value field. This can be augmented by multiple add-on packs.
     *
     * Tagalong adds:
     *  array[] ['store_categories']
     *       int[] ['stores']
     *
     * @var mixed[] $attributes
     */
    private $attributes;

    /**
     * True if the location data has changed.
     *
     * Used to manage the MakePersistent method, if false do not write to disk.
     * 
     * @var boolean $dataChanged
     */
    public $dataChanged = true;

    /**
     * Remember the last location data array passed into set properties via DB.
     *
     * @var mixed[] $locationData
     */
    private $locationData;

    /**
     * The related store_page custom post type properties.
     *
     * WordPress Standard Custom Post Type Features:
     *   int    ['ID']          - the WordPress page ID
     *   string ['post_type']   - always set to this.PageType
     *   string ['post_status'] - current post status, 'draft', 'published'
     *   string ['post_title']  - the title for the page
     *   string ['post_content']- the page content, defaults to blank
     *
     * Store Pages adds:
     *    post_content attribute is loaded with auto-generated HTML content
     *
     * Tagalong adds:
     *    mixed[] ['tax_input'] - the custom taxonomy values for this location
     *
     * @var mixed[] $pageData
     */
    private $pageData;

    // Assistants for this class
    //
    private $dbFieldPrefix      = 'sl_';
    private $pageType           = 'store_page';
    private $pageDefaultStatus;
    private $plugin;

    //-------------------------------------------------
    // Methods
    //-------------------------------------------------

    /**
     * Initialize a new location
     *
     * @param mixed[] $params - a named array of the plugin options.
     */
    public function __construct($params) {
        foreach ($params as $property=>$value) {
            $this->$property = $value;
        }
        global $wpdb;
        $this->db = $wpdb;

        // Set gettext default properties.
        //
        $this->pageDefaultStatus = __('draft','csa-slplus');
    }

    /**
     * Create or update the custom store_page page type for this location.
     *
     * Set MakePersistent to false if you are going to manage the persistent store later.
     * You can check $this->dataChanged to see if the data is dirty to determine whether or not persistence might be needed.
     *
     * @param boolean $MakePersistent if true will write the location to disk if the linked_postid was changed.
     * @return int return the page ID linked to this location.
     */
    public function crupdate_Page($MakePersistent=true) {
        $this->debugMP('msg',__FUNCTION__);
        
        $crupdateOK = false;

        // Setup the page properties.
        //
        $this->set_PageData();

        // Update an existing page.
        //
        if ($this->linked_postid > 0) {
            $touched_pageID = wp_update_post($this->pageData);
            $crupdateOK = ($touched_pageID > 0);
            $this->debugMP('msg','','update page '.$touched_pageID);


        // Create a new page.
        } else {
            $touched_pageID = wp_insert_post($this->pageData, true);
            $crupdateOK = !is_wp_error($touched_pageID);
            if ($crupdateOK) {
                $this->debugMP('msg','','added page '.$touched_pageID);
            } else {
                $this->debugMP('msg','','Error Creating Page: '.$touched_pageID->get_error_message() . '<br/>' .'Page data: ');
                $this->debugMP('pr','',$this->pageData);
            }
        }

        // Ok - we are good...
        //
        if ($crupdateOK) {
           $this->debugMP('msg','','create or update is OK, no error');

            // If we created a page or changed the page ID,
            // set it in our location property and make it
            // persistent.
            //
            if ($touched_pageID != $this->linked_postid) {
                $this->linked_postid = $touched_pageID;
                $this->pages_url = get_permalink($this->linked_postid);
                $this->dataChanged = true;
                if ($MakePersistent) {
                    $this->debugMP('msg','Make new linked post ID ' . $this->linked_postid . ' persistent.');
                    $this->MakePersistent();
                }
            }


        // We got an error... oh shit...
        //
        } else {
            $this->plugin->notifications->add_notice('error',
                    __('Could not create or update the custom page for this location.','csa-slplus')
                    );
            $this->debugMP('pr','location.crupdate_Page() failed',(is_object($touched_pageID)?$touched_pageID->get_error_messages():''));
        }


        return $this->linked_postid;
    }

    /**
     * Fetch a location property from the valid object properties list.
     *
     * $currentLocation = new SLPlus_Location();
     * print $currentLocation->id;
     * 
     * @param mixed $property - which property to set.
     * @return null
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        if ($this->plugin->is_Extended() && isset($this->exdata[$property])) {
            return $this->exdata[$property];
        }
        return null;
    }

    /**
     * Simplify the plugin debugMP interface.
     *
     * @param string $type
     * @param string $hdr
     * @param string $msg
     */
    function debugMP($type,$hdr,$msg='') {
        $this->plugin->debugMP('slp.location',$type,$hdr,$msg,NULL,NULL,true);
    }

    /**
     * Put out the dump of the current location to DebugMP slp.main panel.
     * 
     */
    public function debugProperties() {
        $this->debugMP('msg',__FUNCTION__);
        $output = array();
        foreach ($this->dbFields as $property) {
            $output[$property] = $this->$property;
        }
        $output['attributes'] = $this->attributes;
        $this->debugMP('pr','',$output);
    }


    /**
     * Delete this location permanently.
     */
    public function DeletePermanently() {
        $this->debugMP('msg',__FUNCTION__,"Location: {$this->id}, Linked Post: {$this->linked_postid}");
        if (!ctype_digit($this->id) || ($this->id<0)) { return; }

        // ACTION: slp_deletelocation_starting
        //
        do_action('slp_deletelocation_starting');

        // Attached Post ID?  Delete it permanently (bypass trash).
        //
        if (ctype_digit($this->linked_postid) && ($this->linked_postid>0)) {
            $post = get_post($this->linked_postid);
            if ($post->post_type === SLPLUS::locationPostType) {
                wp_delete_post($this->linked_postid,true);
            }
        }

        $this->plugin->db->delete(
            $this->plugin->database->info['table'],
            array('sl_id' => $this->id)
            );
    }

    /**
     * Return the values for each of the persistent properties of this location.
     *
     * @param string $property name of the persistent property to get, defaults to 'all' = array of all properties
     * @return mixed the value the property or a named array of all properties (default)
     */
    public function get_PersistentProperty($property='all') {
        $this->debugMP('msg',__FUNCTION__);
        $persistentData = array_reduce($this->dbFields,array($this,'mapPropertyToField'));
        return (($property==='all')?$persistentData:(isset($persistentData[$property])?$persistentData[$property]:null));
    }

    /**
     * Set all the db field properties to blank.
     */
    public function reset() {
        foreach ($this->dbFields as $property) {
            $this->$property = '';
        }
        $this->pageData = null;
        $this->attributes = null;
    }

    /**
     * Set latitude & longitude for this location.
     *
     * @param float $lat
     * @param float $lng
     */
    public function set_LatLong($lat,$lng) {
        $this->debugMP('msg',__FUNCTION__,"$lat , $lng");
        if($this->latitude  != $lat) {
            $this->latitude  = $lat;
            $this->dataChanged = true;
        }
        if ($this->longitude != $lng) {
            $this->longitude = $lng;
            $this->dataChanged = true;
        }
    }

    /**
     * Setup the data for the current page, run through augmentation filters.
     *
     * This method applies the slp_location_page_attributes filter.
     *
     * Using that filter allows other parts of the system to change or augment
     * the data before we create or update the page in the WP database.
     *
     * @return mixed[] WordPress custom post type property array
     */
    public function set_PageData() {

        // We have an existing page
        // should feed a wp_update_post not wp_insert_post
        //
        if ($this->linked_postid > 0) {
            $this->pageData = array(
                'ID'            => $this->linked_postid,
                'slp_notes'     => 'pre-existing page'
            );

        // No page yet, default please.
        //
        } else {
            $this->pageData = array(
                'ID'            => '',
                'post_type'     => $this->pageType,
                'post_status'   => $this->pageDefaultStatus,
                'post_title'    => (empty($this->store)? 'SLP Location' : $this->store),
                'post_content'  => '',
                'slp_notes'     => 'new page'
            );
        }

        // Apply our location page data filters.
        // This is what allows add-ons to tweak page data.
        //
        // FILTER: slp_location_page_attributes
        //
        $this->pageData = apply_filters('slp_location_page_attributes', $this->pageData);

        return $this->pageData;
    }

    /**
     * Make the location data persistent.
     *
     * @return boolean data write OK
     */
    public function MakePersistent() {
        $this->debugMP('msg',__FUNCTION__);

        $dataWritten = true;
        $dataToWrite = array_reduce($this->dbFields,array($this,'mapPropertyToField'));

        // sl_id int field blank, unset it we will insert a new auto-int record
        //
        if (empty($dataToWrite['sl_id'])) {
            unset($dataToWrite['sl_id']);
        }
        
        // sl_last_upated is blank, unset to get auto-date value
        //
        if (empty($dataToWrite['sl_lastupdated'])) {
            unset($dataToWrite['sl_lastupdated']);
        }

        // sl_linked_postid is blank, set it to 0
        //
        if (empty($dataToWrite['sl_linked_postid'])) {
            $dataToWrite['sl_linked_postid'] = 0;
        }

        // Location is set, update it.
        //
        if ($this->id > 0) {
            $this->debugMP('msg','',"Update location {$this->id}");
            if(!$this->plugin->db->update($this->plugin->database->info['table'],$dataToWrite,array('sl_id' => $this->id))) {
                $this->plugin->notifications->add_notice(
                        'warning',
                        sprintf(__('%s (location id %d) did not need to update the core location properties.','csa-slplus'),$this->store,$this->id)
                        );
                $dataWritten = false;
            }

        // No location, add it.
        //
        } else {
            $this->debugMP('msg','','Adding new location since no ID was provided.');
            if (!$this->plugin->db->insert($this->plugin->database->info['table'],$dataToWrite)) {
                $this->plugin->notifications->add_notice(
                        'warning',
                        sprintf(__('Could not add %s as a new location','csa-slplus'),$this->store)
                        );
                $dataWritten = false;
                $this->id = '';

            // Set our location ID to be the newly inserted record!
            //
            } else {
                $this->id = $this->plugin->db->insert_id;
            }

        }

        // Reset the data changed flag, used to manage MakePersistent calls.
        // Stops MakePersistent from writing data to disk if it has not changed.
        //
        $this->plugin->currentLocation->dataChanged = false;

        return $dataWritten;
    }

    /**
     * Return true of the given string is an int greater than 0.
     *
     * If not id is presented, check the current location ID.
     *
     * @param string $id
     * @return boolean
     */
    function isvalid_ID($id=null) {
        if ($id===null) { $id = $this->id; }
        return ( ctype_digit( $id ) && ( $id > 0 ) );
    }

    /**
     * Return a named array that sets key = db field name, value = location property
     *
     * @param string $property - name of the location property
     * @return mixed[] - key = string of db field name, value = location property value
     */
    private function mapPropertyToField($result, $property) {
        // Map attributes back into option_value
        //
        if ($property == 'option_value') {
            $this->$property = maybe_serialize($this->attributes);
        }

        // Set field to property
        //
        $result[$this->dbFieldPrefix.$property]=$this->$property;
        return $result;
    }

    /**
     * Set a location property in the valid object properties list to the given value.
     *
     * $currentLocation = new SLPlus_Location();
     * $currentLocation->store = 'My Place';
     *
     * @param mixed $property
     * @param mixed $value
     * @return \SLPlus_Location
     */
    public function __set($property,$value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
        
        // Extended Data, allow property as long as it does not conflict
        // with a built-in property.
        //
        if ($this->plugin->is_Extended() && !property_exists($this,$property)) {
            $this->exdata[$property] = $value;
        }
        return $this;
    }

    /**
     * Set location properties via a named array containing the field data.
     *
     * Used to set properties based on the MySQL SQL fetch to ARRAY_A method
     * or on a prepped named array where the field names are keys and
     * field values are the values.
     *
     * Mode parameter:
     * o dbreset  = reset location data to blank before loading it up
     * o reset = reset location data to blank before loading it up
     * o update = do NOT reset location data to blank before updating
     *
     * Assumes the field names start with 'sl_'.
     *
     * @param mixed[] $locationData
     * @param string $mode which mode?  'reset' or 'update' defaults to reset;
     * @return boolean
     */
    public function set_PropertiesViaArray($locationData,$mode='reset') {
        $this->debugMP('msg',__FUNCTION__,"Mode: {$mode}");

        // If we have an array, assume we are on the right track...
        if (is_array($locationData)) {

            // Do not set the data if it is unchanged from the last go-around
            //
            if ($locationData === $this->locationData) {
                return true;
            }

            // Process mode.
            // Ensures any value other than 'dbreset' or 'update' resets the location data.
            //
            switch ($mode) {
                case 'dbreset':
                case 'update':
                    break;
                default:
                    $this->debugMP('msg','','data reset');
                    $this->reset();
                    break;
            }

            // Go through the named array and extract properties.
            //
            foreach ($locationData as $field => $value) {

                // TODO: This is probably wrong and can be deleted.  Should be sl_id, but that causes duplicate entries.
                if ($field==='id') { continue; }

                // Get rid of the leading field prefix (usually sl_)
                //
                $property = str_replace($this->dbFieldPrefix,'',$field);

                // Set our property value
                //
                $ssd_value = stripslashes_deep($value);
                if ($this->$property != $ssd_value ) {
                    $debug_message = empty($this->property)?"set to {$value}":"changed {$this->$property} to {$value} ";
                    $this->debugMP('msg','',"{$property}: {$debug_message}");
                    $this->$property = $ssd_value;
                    $this->plugin->currentLocation->dataChanged = true;
                }
            }

            // Deserialize the option_value field
            //
            $this->attributes = maybe_unserialize($this->option_value);

            $this->locationData = $locationData;

            return true;
        }

        $this->debugMP('msg','','ERROR: location data not in array format.');
        return false;
    }


    /**
     * Load a location from the database.
     *
     * Only re-reads database if the location ID has changed.
     *
     * @param int $locationID - ID of location to be loaded
     * @return SLPlus_Location $this - the location object
     */
    public function set_PropertiesViaDB($locationID) {
        $this->debugMP('msg',__FUNCTION__);
        
        // Reset the set_PropertiesViaArray tracker.
        //
        $this->locationData = null;

        // Our current ID does not match, load new location data from DB
        //
        if ($this->id != $locationID) {
            $this->debugMP('msg','',"Location {$locationID} loaded from disk");
            $this->reset();

            $locData =
                $this->plugin->database->get_Record(
                    array('selectall','whereslid'),
                    $locationID
                );
            if (is_array($locData)) { $this->set_PropertiesViaArray($locData,'dbreset'); }
        }

        // Reset the data changed flag, used to manage MakePersistent calls.
        // Stops MakePersistent from writing data to disk if it has not changed.
        //
        $this->plugin->currentLocation->dataChanged = false;

        return $this;
    }

    /**
     * Update the location attributes, merging existing attributes with new attributes.
     *
     * @param mixed[] $newAttributes
     */
    function update_Attributes($newAttributes) {
        $this->debugMP('pr',__FUNCTION__,$newAttributes);
        if (is_array($newAttributes)) { 
            $this->attributes =
                is_array($this->attributes)                     ?
                array_merge($this->attributes,$newAttributes)   :
                $newAttributes
                ;
            $this->dataChanged = true;
        }
    }
}
