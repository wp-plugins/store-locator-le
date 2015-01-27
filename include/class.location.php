<?php
/**
 * Store Locator Plus location interface and management class.
 *
 * Make a location an in-memory object and handle persistence via data I/O to the MySQL tables.
 *
 * @package StoreLocatorPlus\Location
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2012-2015 Charleston Software Associates, LLC
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
 * @property mixed[] $exdata - the extended data fields
 * @property mixed[] $settings - the deserialized option_value field
 *
 * @property mixed[] $pageData - the related store_page custom post type properties.
 * @property-read string $pageType - the custom WordPress page type of locations
 * @property-read string $pageDefaultStatus - the default page status
 *
 * @property-read string $dbFieldPrefix - the database field prefix for locations
 * @property-read string[] $dbFields - an array of properties that are in the db table
 *
 * @property SLPlus $slplus - the parent plugin object
 */
class SLPlus_Location {

    const StartingDelay = 2000000;

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
     * How many locations have processed for geocoding this session.
     *
     * @var int $count
     */
    private $count = 0;

    /**
     * How long to wait between geocoding requests.
     *
     * @var int $delay
     */
    private $delay = SLPlus_Location::StartingDelay;

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
     *
     * @var boolean $geocodeIssuesRendered
     */
    private $geocodeIssuesRendered = false;

    /**
     * If true do not show valid geocodes.
     *
     * @var boolean $geocodeSkipOKNotices
     */
    public $geocodeSkipOKNotices = false;

    /**
     * The URL of the geocoding service.
     *
     * @var string $geocodeURL
     */
    private $geocodeURL;

    /**
     * How many times to retry an address.
     *
     * @var int $iterations
     */
    private $iterations;

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

    /**
     * Maxmium delay in milliseconds.
     *
     * @var $retry_maximum_delayms
     */
    private $retry_maximum_delayms = 5000000;

    /**
     * @var \SLPlus
     */
    private $slplus;


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
     * Add an address into the SLP locations database.
     *
     * duplicates_handling can be:
     * o none = ignore duplicates
     * o skip = skip duplicates
     * o update = update duplicates
     *
     * Returns:
     * o added = new location added
     * o location_exists = store id provided and not in update mode
     * o not_updated = existing location not updated
     * o skipped = duplicate skipped
     * o updated = existing location updated
     *
     * @param array[] $locationData
     * @param string $duplicates_handling
     * @param boolean $skipGeocode
     * @return string
     *
     */
    function add_to_database($locationData,$duplicates_handling='none',$skipGeocode=false) {
        $this->debugMP('msg', get_class() . '::' .__FUNCTION__,
            "duplicates handling mode: {$duplicates_handling} " . ($skipGeocode?' skip geocode':'')
        );

        // Make sure locationData['sl_id'] is set to SOMETHING.
        //
        if (!isset($locationData['sl_id'])) { $locationData['sl_id'] = null; }

        // If the incoming location ID is of a valid format...
        // Go fetch that location record.
        // This also ensures that ID actually exists in the database.
        //
        if ($this->slplus->currentLocation->isvalid_ID($locationData['sl_id'])) {
            $this->debugMP('msg','',"location ID {$locationData['sl_id']} being loaded");
            $this->slplus->currentLocation->set_PropertiesViaDB($locationData['sl_id']);
            $locationData['sl_id'] = $this->slplus->currentLocation->id;

            // Not a valid incoming ID, reset current location.
            //
        } else {
            $this->slplus->currentLocation->reset();
        }

        // If the location ID is not valid either because it does not exist
        // in the database or because it was not provided in a valid format,
        // Go see if the location can be found by name + address
        //
        if (!$this->slplus->currentLocation->isvalid_ID()) {
            $this->debugMP('msg','','location ID not provided or invalid.');
            $locationData['sl_id'] = $this->slplus->db->get_var(
                $this->slplus->db->prepare(
                    $this->slplus->database->get_SQL('selectslid') .
                    'WHERE ' .
                    'sl_store   = %s AND '.
                    'sl_address = %s AND '.
                    'sl_address2= %s AND '.
                    'sl_city    = %s AND '.
                    'sl_state   = %s AND '.
                    'sl_zip     = %s AND '.
                    'sl_country = %s     '
                    ,
                    $this->val_or_blank($locationData,'sl_store')    ,
                    $this->val_or_blank($locationData,'sl_address')  ,
                    $this->val_or_blank($locationData,'sl_address2') ,
                    $this->val_or_blank($locationData,'sl_city')     ,
                    $this->val_or_blank($locationData,'sl_state')    ,
                    $this->val_or_blank($locationData,'sl_zip')      ,
                    $this->val_or_blank($locationData,'sl_country')
                )
            );
        }

        // Location ID exists, we have a duplicate entry...
        //
        if ( $this->slplus->currentLocation->isvalid_ID( $locationData['sl_id'] ) ) {
            $this->debugMP('msg','',"location ID {$locationData['sl_id']} found or provided is valid.");
            if ($duplicates_handling === 'skip') { return 'skipped'; }

            // array ID and currentLocation ID do not match,
            // must have found ID via address lookup, go load up the currentLocation record
            //
            if ($locationData['sl_id'] != $this->slplus->currentLocation->id) {
                $this->slplus->currentLocation->set_PropertiesViaDB($locationData['sl_id']);
            }

            // TODO: if mode = 'add' force currentLocation->id to blank and set return code to 'added'.
            //

            $return_code = 'updated';

            // Location ID does not exist, we are adding a new record.
            //
        } else {
            $this->debugMP('msg','',"location {$locationData['sl_id']} not found via address lookup, original handling mode {$duplicates_handling}.");
            $duplicates_handling = 'add';
            $return_code = 'added';
        }

        // Update mode and we are NOT skipping the geocode process,
        // check that the address has changed first.
        //
        if ( ! $skipGeocode && ( $duplicates_handling === 'update' ) ) {
            $skipGeocode =
                ($this->val_or_blank($locationData,'sl_address')   == $this->slplus->currentLocation->address ) &&
                ($this->val_or_blank($locationData,'sl_address2')  == $this->slplus->currentLocation->address2) &&
                ($this->val_or_blank($locationData,'sl_city')      == $this->slplus->currentLocation->city    ) &&
                ($this->val_or_blank($locationData,'sl_state')     == $this->slplus->currentLocation->state   ) &&
                ($this->val_or_blank($locationData,'sl_zip')       == $this->slplus->currentLocation->zip     ) &&
                ($this->val_or_blank($locationData,'sl_country')   == $this->slplus->currentLocation->country )  ;
            $this->debugMP('msg','','Address does '.($skipGeocode?'NOT ':'').'need to be recoded via location update mode.');
        }

        // Set the current location data
        //
        // In update duplicates mode this will not obliterate existing settings
        // it will augment them.  To set a value to blank for an existing record
        // it must exist in the column data and be set to blank.
        //
        // Non-update mode, it starts from a blank slate.
        //
        $this->debugMP('msg','',"set location properties via array in {$duplicates_handling} duplicates handling mode");
        $this->slplus->currentLocation->set_PropertiesViaArray( $locationData, $duplicates_handling );

        // HOOK: slp_location_add
        //
        do_action('slp_location_add');

        // Geocode the location
        //
        if ( ! $skipGeocode ) { $this->do_geocoding(); }

        // Write to disk
        //
        if ( $this->slplus->currentLocation->dataChanged ) {
            $this->slplus->currentLocation->MakePersistent();

            // Set not updated return code.
            //
        } else {
            $return_code = 'not_updated';
        }

        // HOOK: slp_location_added
        //
        do_action('slp_location_added');

        return $return_code;
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
            $this->slplus->notifications->add_notice('error',
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
        if (
            $this->slplus->database->is_Extended()                  &&
            $this->slplus->database->extension->has_ExtendedData()  &&
            isset($this->exdata[$property])
            ) {
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
        $this->slplus->debugMP('slp.location',$type,$hdr,$msg,NULL,NULL,true);
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
     * Decode a string from URL-safe base64.
     *
     * @param $value
     * @return string
     */
    private function decode_Base64UrlSafe( $value ) {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $value));
    }

    /**
     * GeoCode a given location, updating the slplus_plugin currentLocation object lat/long.
     *
     * Writing to disk is to be handled by the calling function.
     *
     * slplus_plugin->currentLocation->dataChanged is set to true if the lat/long is updated.
     *
     * @param string $address the address to geocode, if not set use currentLocation
     */
    function do_geocoding($address=null) {
        $this->debugMP('msg', get_class() . '::' . __FUNCTION__,$address);
        $this->count++;
        if ($this->count === 1) {
            $this->retry_maximum_delayms = (int) $this->slplus->options_nojs['retry_maximum_delay'] * 1000000;
            $this->iterations = max(1,(int) get_option(SLPLUS_PREFIX.'-geocode_retries','3'));
        }

        // Null address, build from current location
        //
        if ($address === null) {
            $address =
                $this->slplus->currentLocation->address  . ' ' .
                $this->slplus->currentLocation->address2 . ' ' .
                $this->slplus->currentLocation->city     . ' ' .
                $this->slplus->currentLocation->state    . ' ' .
                $this->slplus->currentLocation->zip      . ' ' .
                $this->slplus->currentLocation->country
            ;
        }

        $errorMessage = '';

        // Get lat/long from Google
        //
        $this->debugMP('msg','',$address);
        $json_response = $this->get_LatLong($address);
        if ( ! empty( $json_response ) ) {

            // Process the data based on the status of the JSON response.
            //
            $json = json_decode($json_response);
            if ( $json === null ) {
                $json = json_decode( json_encode( array( 'status' => 'ERROR' , 'message' => $json_response ) ) );
            }

            $this->debugMP('pr','',$json);
            switch ($json->{'status'}) {

                // OK
                // Geocode completed successfully
                // Update the lat/long if it has changed.
                //
                case 'OK':
                    $this->slplus->currentLocation->set_LatLong($json->results[0]->geometry->location->lat,$json->results[0]->geometry->location->lng);
                    $this->delay = SLPlus_Location::StartingDelay;
                    break;

                // OVER QUERY LIMIT
                // Google is getting to many requests from this IP block.
                // Loop through for X retries.
                //
                case 'OVER_QUERY_LIMIT':
                    $errorMessage .= sprintf(__("Address %s (%d in current series) hit the Google query limit.\n", 'csa-slplus'),
                            $address,
                            $this->count
                        ) . '<br/>'
                    ;
                    $attempts = 1;
                    $totalDelay = 0;

                    // Keep trying up until the user-selected number of retries.
                    // Increase the wait between each try by 1 second.
                    // Wait no more than 10 seconds between attempts.
                    //
                    while( $attempts++ < $this->iterations){
                        if ($this->delay <= $this->retry_maximum_delayms+1) {
                            $this->delay += 1000000;
                        }
                        $totalDelay += $this->delay;
                        usleep($this->delay);
                        $json = $this->get_LatLong($address);
                        if ($json!==null) {
                            $json = json_decode($json);
                            if ($json->{'status'} === 'OK') {
                                $this->slplus->currentLocation->set_LatLong($json->results[0]->geometry->location->lat,$json->results[0]->geometry->location->lng);
                            }
                        } else {
                            break;
                        }
                    }
                    $errorMessage .= sprintf(
                            __('Waited up to %4.2f seconds between request, total wait for this location was %4.2f seconds.', 'csa-slplus'),
                            $this->delay/1000000,
                            $totalDelay/1000000
                        ).
                        "\n<br>";
                    $errorMessage .= sprintf(
                            __('%d total attempts for this location.', 'csa-slplus'),
                            $attempts-1
                        ).
                        "\n<br>";
                    break;

                // ZERO RESULTS
                // Bad address provided or nothing found on Google end.
                //
                case 'ZERO_RESULTS':
                    $errorMessage .= sprintf(__("Address #%d : %s <font color=red>failed to geocode</font>.", 'csa-slplus'),
                            $this->slplus->currentLocation->id,
                            $address
                        ) . "<br />\n";
                    $errorMessage .= sprintf(__("Unknown Address! Received status %s.", 'csa-slplus'),$json->{'status'})."\n<br>";
                    $this->delay = SLPlus_Location::StartingDelay;
                    break;

                // GENERIC
                // Could not geocode.
                //
                default:
                    $errorMessage .=
                        sprintf(__("Address #%d : %s <font color=red>failed to geocode</font>."  , 'csa-slplus'),
                            $this->slplus->currentLocation->id,
                            $address)    .
                        "<br/>\n"                   .
                        sprintf(__("Received status %s."                      , 'csa-slplus'),
                            $json->{'status'})            .
                        "<br/>\n"                   .
                        sprintf(__("Received data %s."                                           , 'csa-slplus'),
                            '<pre>'.print_r($json,true).'</pre>')
                    ;
                    $this->delay = SLPlus_Location::StartingDelay;
                    break;
            }


            // No raw json
            //
        } else {
            $json = '';
            $errorMessage .= __('Geocode service non-responsive','csa-slplus') .
                "<br/>\n" .
                $this->geocodeURL . urlencode($address)
            ;
        }

        // Show Error Messages
        //
        if ($errorMessage != '') {
            if (!$this->geocodeIssuesRendered) {
                $errorMessage =
                    '<strong>'.
                    sprintf(
                        __('Read <a href="%s">this</a> if you are having geocoding issues.','csa-slplus'),
                        'http://www.charlestonsw.com/support/documentation/store-locator-plus/troubleshooting/geocoding-errors/'
                    ).
                    "</strong><br/>\n" .
                    $errorMessage
                ;
                $this->geocodeIssuesRendered = true;
            }
            $this->slplus->notifications->add_notice(6,$errorMessage);

            // Good encoding
            //
        } elseif (!$this->geocodeSkipOKNotices) {
            $this->slplus->notifications->add_notice(
                9,
                sprintf(
                    __('Google thinks %s is at <a href="%s" target="_blank">lat: %s long %s</a>','csa-slplus'),
                    $address,
                    sprintf('http://%s/?q=%s,%s',
                        $this->slplus->options['map_domain'],
                        $this->slplus->currentLocation->latitude,
                        $this->slplus->currentLocation->longitude),
                    $this->slplus->currentLocation->latitude, $this->slplus->currentLocation->longitude
                )
            );
        }

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

        $this->slplus->db->delete(
            $this->slplus->database->info['table'],
            array('sl_id' => $this->id)
            );
    }

    /**
     * Encode a string to URL-safe base64
     *
     * @param $value
     * @return mixed
     */
    private function encode_Base64UrlSafe($value) {
        return str_replace(array('+', '/'), array('-', '_'), base64_encode($value));
    }

    /**
     * Get the latitude/longitude for a given address.
     *
     * Google Server-Side API geocoding is documented here:
     * https://developers.google.com/maps/documentation/geocoding/index
     *
     * Required Google Geocoding API Params:
     * address
     * sensor=true|false
     *
     * Optional Google Geocoding API Params:
     * bounds
     * language
     * region
     * components
     *
     * @param string $address the address to geocode
     * @return string $response the JSON response string
     */
    function get_LatLong($address) {
        $this->set_geocoding_baseURL();
        $fullURL = $this->geocodeURL . urlencode($address);

        // Client ID in use?   Sign the request.
        //
        if ( ! empty ( $this->slplus->options_nojs['google_client_id'] ) ) {
            $fullURL = $this->sign_url( $fullURL , $this->slplus->options_nojs['google_private_key'] );
        }

        $request_args = array(
                'timeout'   => $this->slplus->options_nojs['http_timeout'],
            );
        $response = wp_remote_get( $fullURL , $request_args );
        $raw_json = is_wp_error( $response ) ? null : $response['body'];

        return $raw_json;
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
     * Set the geocoding base URL.
     */
    private function set_geocoding_baseURL() {
        if ( isset( $this->geocodeURL ) ) { return; }


        // Google Maps API for Work client ID
        //
        $client_id =
            ! empty ( $this->slplus->options_nojs['google_client_id'] )           ?
                '&client=' . $this->slplus->options_nojs['google_client_id'] . '&v=3' :
                ''                                                                    ;

        // Set the map language
        //
        $language = '&language='.$this->slplus->helper->getData('map_language','get_item',null,'en');

        // Base Google API URL
        //
        $google_api_url =
            'https://'    .
            'maps.googleapis.com'                       .
            '/maps/api/'                                .
            'geocode/json'                              .
            '?sensor=false'                             ;

        // Build the URL with all the params
        //
        $this->geocodeURL =
            $google_api_url     .
            $client_id          .
            $language           .
            '&address='         ;
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
     * Sign a URL with a given crypto key.
     *
     * Note that this URL must be properly URL-encoded.
     *
     * @param $myUrlToSign
     * @param $privateKey
     * @return string
     */
    private function sign_url( $myUrlToSign, $privateKey ) {
        // parse the url
        $url = parse_url($myUrlToSign);

        $urlPartToSign = $url['path'] . "?" . $url['query'];

        // Decode the private key into its binary format
        $decodedKey = $this->decode_Base64UrlSafe($privateKey);

        // Create a signature using the private key and the URL-encoded
        // string using HMAC SHA1. This signature will be binary.
        $signature = hash_hmac("sha1",$urlPartToSign, $decodedKey,  true);

        $encodedSignature = $this->encode_Base64UrlSafe($signature);

        return $myUrlToSign."&signature=".$encodedSignature;
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
            if(!$this->slplus->db->update($this->slplus->database->info['table'],$dataToWrite,array('sl_id' => $this->id))) {
                $dataWritten = false;
                $this->debugMP('msg','',"Update location {$this->id} DID NOT update core data.");
            }

        // No location, add it.
        //
        } else {
            $this->debugMP('msg','','Adding new location since no ID was provided.');
            if (!$this->slplus->db->insert($this->slplus->database->info['table'],$dataToWrite)) {
                $this->slplus->notifications->add_notice(
                        'warning',
                        sprintf(__('Could not add %s as a new location','csa-slplus'),$this->store)
                        );
                $dataWritten = false;
                $this->id = '';

            // Set our location ID to be the newly inserted record!
            //
            } else {
                $this->id = $this->slplus->db->insert_id;
            }

        }

        // Reset the data changed flag, used to manage MakePersistent calls.
        // Stops MakePersistent from writing data to disk if it has not changed.
        //
        $this->slplus->currentLocation->dataChanged = false;

        return $dataWritten;
    }

    /**
     * Return true of the given string is an int greater than 0.
     *
     * If not id is presented, check the current location ID.
     *
     * request_param is used if ID is set to null to try to set the value from a request variable of that name.
     *
     * @param string $id
     * @param string $request_param
     * @return boolean
     */
    function isvalid_ID($id=null, $request_param=null ) {

        if ( isset( $_REQUEST[$request_param] ) ) { $id = $_REQUEST[$request_param];    }
        if ( $id === null                       ) { $id = $this->id;                    }

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
        if (
            $this->slplus->database->is_Extended()                  &&
            $this->slplus->database->extension->has_ExtendedData()  &&
            ! property_exists($this,$property)
            ) {
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
                    $this->$property = $ssd_value;
                    $this->slplus->currentLocation->dataChanged = true;
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
                $this->slplus->database->get_Record(
                    array('selectall','whereslid'),
                    $locationID
                );
            if (is_array($locData)) { $this->set_PropertiesViaArray($locData,'dbreset'); }
        }

        // Reset the data changed flag, used to manage MakePersistent calls.
        // Stops MakePersistent from writing data to disk if it has not changed.
        //
        $this->slplus->currentLocation->dataChanged = false;

        return $this;
    }

    /**
     * Update the location attributes, merging existing attributes with new attributes.
     *
     * @param mixed[] $newAttributes
     */
    public function update_Attributes($newAttributes) {
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

    /**
     * Return the value of the specified location data element or blank if not set.
     *
     * @param mixed[] $locationdata the location data array
     * @param string $dataElement store locator plus location data array key
     * @return mixed - the data element value or a blank string
     */
    private function val_or_blank($data,$key) {
        return isset($data[$key]) ? $data[$key] : '';
    }
}
