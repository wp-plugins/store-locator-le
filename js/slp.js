/**
 * Set via wp_localize_script in the SLPLus.UI class.
 * @type {*}
 */
var slplus = slplus;

/**
 * Setup an SLP namespace to prevent JS conflicts.
 *
 * @type {
 * {
 * LocationServices: LocationServices,
 * Ajax: Ajax,
 * Marker: Marker,
 * Utils: Utils,
 * Info: Info,
 * Map: Map
 * }
 * }
 */
var slp = {

    /***************************************************************************
     *
     * LOCATION SERVICES
     *
     */
    LocationServices: function () {

        // Properties
        this.theService = null;
        this.LocationSupport = true;
        this.Initialized = false;
        this.location_timeout = null;
        this.lat = 0.00;
        this.lng = 0.00;
        this.errorCalled = false;

        /**
         * Constructor
         *
         * @private
         */
        this.__init = function () {
            this.Initialized = true;
            try {
                if (typeof navigator.geolocation === 'undefined') {
                    if (google.gears) {
                        this.theService = google.gears.factory.create('beta.geolocation');
                    } else {
                        this.LocationSupport = false;
                    }
                }
                else {
                    this.theService = navigator.geolocation;
                }
            } catch (e) {
            }
        };

        /**
         * Set the current location.
         *
         * When setting currentLocation the callback and errorCallback functions must be defined.
         * See the sensor.currentLocation call down below for the return-to place for
         * these two functions passed as variables (down around line 1350).
         *
         * @param callback
         * @param errorCallback
         */
        this.currentLocation = function (callback, errorCallback) {

            // If location services are not setup, do it
            //
            if (!this.Initialized) {
                this.__init();
            }

            // If this browser supports location services, use them
            //
            if (this.LocationSupport) {
                if (this.theService) {

                    // In 5 seconds run errorCallback
                    //
                    this.location_timeout = setTimeout(errorCallback, 5000);

                    // Run the browser location service to get the current position
                    //
                    // on success run callback
                    // on failure run errorCallback
                    //
                    this.theService.getCurrentPosition(callback, errorCallback, {
                        maximumAge: 60000,
                        timeout: 5000,
                        enableHighAccuracy: true
                    });
                }

                // Otherwise throw an exception
                //
            } else {
                errorCallback(null);
            }

        };
    },


    /***************************************************************************
     *
     * AJAX
     *
     */
    Ajax: function () {

        /**
         * Send a request to the ajax listener.
         *
         * action.action property is a usable action 'csl_ajax_search', lat: 'start lat', long: 'start long', dist:'distance to search'
         *
         * @argument {object} action
         * @argument {function} callback function with params "success: true, response: {marker list}"
         */
        this.send = function (action, callback) {
            if (window.location.protocol !== slplus.ajaxurl.substring(0, slplus.ajaxurl.indexOf(':') + 1)) {
                slplus.ajaxurl = slplus.ajaxurl.replace(slplus.ajaxurl.substring(0, slplus.ajaxurl.indexOf(':') + 1), window.location.protocol);
            }
            jQuery.post(
                slplus.ajaxurl,
                action,
                function (response) {
                    try {
                        response = JSON.parse(response);
                    }
                    catch (ex) {
                    }
                    callback(response);
                }
            );
        };
    },

    /***************************************************************************
     *
     * MARKERS
     *
     * Creates a Google Maps marker.
     *
     * parameters (properties):
     *
     *    map: the slp.Map type to put it on
     *    title: the title of the marker for mouse over
     *    markerImage: todo: load a custom icon, null for default
     *    position: the lat/long to put the marker at
     *
     */
    Marker: function (map, title, position, markerImage) {

        // Properties
        this.__map = map;
        this.__title = title;
        this.__position = position;
        this.__gmarker = null;
        this.__markerImage = markerImage;
        this.__shadowImage = null;


        /**
         * Constructor.
         *
         * @private
         */
        this.__init = function () {

            // No icon image
            //
            if (this.__markerImage === null) {
                this.__gmarker = new google.maps.Marker(
                    {
                        position: this.__position,
                        map: this.__map.gmap,
                        title: this.__title
                    });

                // Use specified icon
                //
            } else {
                var shadowKey = this.__markerImage;
                if (typeof cslmap.shadows[shadowKey] === 'undefined') {
                    var shadow = this.__markerImage.replace('/_(.*?)\.png/', '_shadow.png');
                    jQuery.ajax(
                        {
                            url: shadow,
                            type: 'HEAD',
                            async: false,
                            error: function () {
                                cslmap.shadows[shadowKey] = slplus.plugin_url + '/images/icons/blank.png';
                            },
                            success: function () {
                                cslmap.shadows[shadowKey] = shadow;
                            }
                        }
                    );
                }
                this.__shadowImage = cslmap.shadows[shadowKey];
                this.buildMarker();
            }
        };

        /*------------------------
         * MARKERS buildMarker
         */
        this.buildMarker = function () {
            this.__gmarker = new google.maps.Marker(
                {
                    position: this.__position,
                    map: this.__map.gmap,
                    shadow: this.__shadowImage,
                    icon: this.__markerImage,
                    zIndex: 0,
                    title: this.__title
                });
        };

        this.__init();
    },

    /***************************************************************************
     *
     * UTILITIES
     *
     */
    Utils: function () {

        /**************************************
         * function: escapeExtended()
         *
         * Escape any extended characters, such as � in f�r.
         * Standard US ASCII characters (< char #128) are unchanged
         *
         */
        this.escapeExtended = function (string) {
            return string;
        };
    },

    /***************************************************************************
     *
     * INFO SUBCLASS
     *
     ***************************
     * Popup info window Object
     * usage:
     * create a google info window
     * parameters:
     *    content: the content to show by default
     */
    Info: function (content) {
        this.__content = content;
        this.__position = position;

        this.__anchor = null;
        this.__gwindow = null;
        this.__gmap = null;

        this.openWithNewContent = function (map, object, content) {
            this.__content = content;
            this.__gwindow = setContent = this.__content;
            this.open(map, object);
        };

        this.open = function (map, object) {
            this.__gmap = map.gmap;
            this.__anchor = object;
            this.__gwindow.open(this.__gmap, this.__anchor);
        };

        this.close = function () {
            this.__gwindow.close();
        };

        this.__init = function () {
            this.__gwindow = new google.maps.InfoWindow(
                {
                    content: this.__content
                });
        };

        this.__init();
    },

    /***************************************************************************
     *
     * MAP
     *
     * Create a google maps object linked to a map/canvas id
     *
     *
     * parameters (properties):
     *    aMapNumber: the id/canvas of the map object to load from php side
     */
    Map: function (aMapCanvas) {

        //private: map number to look up at init
        this.__mapCanvas = aMapCanvas;

        // other variables
        //
        this.shadows = new Object;   // map marker shadows
        this.map_hidden = true;      // map div may be hidden at first, assume it was
        this.default_radius = 40000; // default radius if not set

        //function callbacks
        this.tilesLoaded = null;

        //php passed vars set in init
        this.address = null;
        this.draggable = true;
        this.markers = null;

        //slplus options
        this.usingSensor = false;
        this.disableScroll = null;
        this.mapHomeIconUrl = null;
        this.mapEndIconUrl = null;
        this.mapScaleControl = null;
        this.mapType = null;
        this.mapTypeControl = null;
        this.overviewControl = null;

        //gmap set variables
        this.options = null;
        this.gmap = null;
        this.centerMarker = null;
        this.marker = null;
        this.infowindow = new google.maps.InfoWindow();
        this.bounds = null;
        this.homePoint = null;
        this.lastCenter = null;
        this.lastRadius = null;
        this.loadedOnce = false;

        // AJAX communication
        //
        this.latest_response = null;

        /***************************
         * function: __init()
         * usage:
         * Called at the end of the 'class' due to some browser's quirks
         * parameters: none
         * returns: none
         */
        this.__init = function () {

            if (typeof slplus !== 'undefined') {
                this.mapType = slplus.map_type;
                this.disableScroll = !!slplus.disable_scroll;
                this.mapHomeIconUrl = slplus.map_home_icon;
                this.mapEndIconUrl = slplus.map_end_icon;
                this.mapScaleControl = !!slplus.map_scalectrl;
                this.mapTypeControl = !!slplus.map_typectrl;
                this.overviewControl = !!(parseInt(slplus.overview_ctrl));

                // Setup address
                // Use the entry form value if set, otherwise use the country
                //
                var addressInput = this.getSearchAddress();
                if (typeof addressInput === 'undefined') {
                    this.address = slplus.options.map_center;
                } else {
                    this.address = addressInput;
                }

            } else {
                alert('Store Locator Plus script not loaded properly.');
            }
        };

        /***************************
         * function: __buildMap
         * usage:
         *        Builds the map with the specified center
         * parameters:
         *        center:
         *            the specified center or homepoint
         * returns: none
         */
        this.__buildMap = function (center) {
            if (this.gmap === null) {
                this.options = {
                    center: center,

                    mapTypeControl: this.mapTypeControl,
                    mapTypeId: this.mapType,

                    overviewMapControl: this.overviewControl,
                    overviewMapControlOptions: {opened: this.overviewControl},

                    scaleControl: this.mapScaleControl,
                    scrollwheel: !this.disableScroll,

                    minZoom: 1,
                    zoom: parseInt(slplus.options.zoom_level),
                };

                if ( slplus.options.google_map_style ) {
                    jQuery.extend( this.options , { styles: JSON.parse( slplus.options.google_map_style) } );
                }

                this.gmap = new google.maps.Map( document.getElementById('map') , this.options );

                //this forces any bad css from themes to fix the "gray bar" issue by setting the css max-width to none
                var _this = this;
                google.maps.event.addListener(this.gmap, 'bounds_changed', function () {
                    _this.__waitForTileLoad.call(_this);
                });


                // Location Sensor Is Enabled
                // Or immediate mode and home marker is enabled
                //
                if ( this.show_home_marker() ) {
                    this.homePoint = center;    // Set the home marker location to center lat/long sent in to __buildMap
                    this.addMarkerAtCenter();
                }

                // If immediately show locations is enabled.
                //
                if (slplus.options.immediately_show_locations === '1') {
                    var tag_to_search_for = this.saneValue('tag_to_search_for', '');

                    // Default radius for immediately show locations
                    // uses setting from admin panel first,
                    // then the default from the drop down menu,
                    // then 10000 if neither are working.
                    //
                    var radius = this.default_radius;
                    slplus.options.initial_radius = slplus.options.initial_radius.replace(/\D/g, '');
                    if (/^[0-9]+$/.test(slplus.options.initial_radius)) {
                        radius = slplus.options.initial_radius;
                    } else {
                        radius = this.saneValue('radiusSelect' , this.default_radius );
                    }

                    this.loadMarkers(center, radius, tag_to_search_for);
                }
            }
        };

        /**
         * Should I show the home marker on the map or not?
         *
         * @returns {boolean|*}
         */
        this.show_home_marker = function () {
            return (
            this.usingSensor ||
            ( (slplus.options.immediately_show_locations === '1') && (slplus.options.no_homeicon_at_start !== '1') )
            );
        };

        /***************************
         * function: __waitForTileLoad
         * usage:
         * Notifies as the map changes that we'd like to be nofified when the tiles are completely loaded
         * parameters:
         *    none
         * returns: none
         */
        this.__waitForTileLoad = function () {
            var _this = this;
            if (this.__tilesLoaded === null) {
                this.__tilesLoaded = google.maps.event.addListener(this.gmap, 'tilesloaded', function () {
                    _this.__tilesAreLoaded.call(_this);
                });
            }
        };

        /***************************
         * function: __tilesAreLoaded
         * usage:
         * All the tiles are loaded, so fix their css
         * parameters:
         *    none
         * returns: none
         */
        this.__tilesAreLoaded = function () {
            jQuery('#map').find('img').css({'max-width': 'none'});
            google.maps.event.removeListener(this.__tilesLoaded);
            this.__tilesLoaded = null;
        };

        /***************************
         * function: addMarkerAtCenter
         * usage:
         * Puts a pretty marker right smack in the middle
         * parameters:
         *    none
         * returns: none
         */
        this.addMarkerAtCenter = function () {
            if (this.centerMarker) {
                this.centerMarker.__gmarker.setMap(null);
            }
            if (this.homePoint) {
                this.centerMarker = new slp.Marker(this, '', this.homePoint, this.mapHomeIconUrl);
            }
        };

        /***************************
         * function: clearMarkers
         * usage:
         *        Clears all the markers from the map and releases it for GC
         * parameters:
         *    none
         * returns: none
         */
        this.clearMarkers = function () {
            if (this.markers) {
                for (markerNumber in this.markers) {
                    if (typeof this.markers[markerNumber] !== 'undefined') {
                        if (typeof this.markers[markerNumber].__gmarker !== 'undefined') {
                            this.markers[markerNumber].__gmarker.setMap(null);
                        }
                    }
                }
                this.markers.length = 0;

                // Clear the home marker if the address is blank
                // only if we are not on the first map drawing
                //
                if ( ! this.saneValue( 'addressInput' , '' ) ) {
                    this.centerMarker = null;
                    this.homePoint = null;
                }

            }
        };

        /***************************
         * function: putMarkers
         * usage:
         *        Puts an array of markers on the map
         * parameters:
         *        markerList:
         *            a list of slp.Markers
         * returns: none
         */
        this.putMarkers = function (markerListNatural) {

            // Reset map marker list and the results output HTML
            //
            this.markers = [];
            var sidebar = document.getElementById('map_sidebar');
            if (this.loadedOnce) {
                sidebar.innerHTML = '';
            }

            // No Results
            //
            var markerCount = (markerListNatural) ? markerListNatural.length : 0;
            if (markerCount === 0) {
                if ( this.homePoint ) { this.gmap.panTo(this.homePoint); }
                document.getElementById('map_sidebar').innerHTML = '<div class="no_results_found"><h2>' + slplus.msg_noresults + '</h2></div>';

                // Results Processing
                //
            } else {

                // Set the initial bounds to default (1,180)/(-1,180), include home marker if shown.
                //
                var bounds = new google.maps.LatLngBounds();
                if ( this.homePoint ) { bounds.extend(this.homePoint); }

                var locationIcon;
                var markerList = markerListNatural.reverse();

                for (var markerNumber = 0; markerNumber < markerCount; ++markerNumber) {
                    var position = new google.maps.LatLng(markerList[markerNumber].lat, markerList[markerNumber].lng);
                    bounds.extend(position);

                    locationIcon =
                        (
                            (markerList[markerNumber].icon !== null) &&
                            (typeof markerList[markerNumber].icon !== 'undefined') &&
                            (markerList[markerNumber].icon.length > 4) ?
                                markerList[markerNumber].icon :
                                this.mapEndIconUrl
                        );
                    this.markers.push(new slp.Marker(this, markerList[markerNumber].name, position, locationIcon));
                    _this = this;

                    //create info windows
                    //
                    google.maps.event.addListener(this.markers[markerNumber].__gmarker, 'click',
                        (function (infoData, marker) {
                            return function () {
                                _this.__handleInfoClicks.call(_this, infoData, marker);
                            }
                        })(markerList[markerNumber], this.markers[markerNumber]));

                    //create a sidebar entry
                    //
                    if (sidebar) {
                        var sidebarEntry = this.createSidebar(markerList[markerNumber]);
                        sidebar.insertBefore(sidebarEntry, sidebar.firstChild);
                        jQuery('div#map_sidebar span:empty').hide();

                        // Whenever the location result entry is <clicked> do this...
                        //
                        google.maps.event.addDomListener(sidebarEntry, 'click',
                            (function (infoData, marker) {
                                return function () {
                                    _this.__handleInfoClicks.call(_this, infoData, marker);
                                };
                            })(markerList[markerNumber], this.markers[markerNumber]));
                    }
                }

                // Set zoom
                //
                this.bounds = bounds;
                this.gmap.fitBounds(this.bounds);

                // Searches, use Google Bounds - and adjust by the tweak.
                // Initial Load Only - Use "Zoom Level"
                //
                var newZoom =
                        Math.max(Math.min(
                            (
                                (
                                (slplus.options.no_autozoom !== "1") &&
                                (this.loadedOnce || (markerList.length > 1))
                                ) ?
                                this.gmap.getZoom() - parseInt(slplus.zoom_tweak) :
                                    parseInt(slplus.options.zoom_level)
                            ), 20), 1)
                    ;
                this.gmap.setZoom(newZoom);
            }

            // Fire results output changed trigger
            //
            this.loadedOnce = true;
            jQuery('#map_sidebar').trigger('contentchanged');
        };

        /***************************
         * function: private handleInfoClicks
         * usage:
         *        Sets the content to the info window and builds the sidebar when a user clicks a marker
         * parameters:
         *        infoData:
         *            the information to build the info window from (ajax result)
         *        marker:
         *            the slp.Marker to add the information to
         * returns: none
         */
        this.__handleInfoClicks = function (infoData, marker) {
            this.infowindow.setContent(this.createMarkerContent(infoData));
            this.infowindow.open(this.gmap, marker.__gmarker);
        };

        /**
         * Geocode an address on the search input field and display on map.
         *
         * @return {undefined}
         */
        this.doGeocode = function () {
            var geocoder = new google.maps.Geocoder();
            var _this = this;
            var geocodeParms = new Object();
            geocodeParms['address'] = this.address;
            if (slplus.options.searchnear === 'currentmap') {
                if (cslmap.gmap !== null) {
                    geocodeParms['bounds'] = cslmap.gmap.getBounds();
                }
            }

            geocoder.geocode(
                geocodeParms,
                function (results, status) {
                    if (status === 'OK' && results.length > 0) {

                        // if the map hasn't been created, then create one
                        //
                        if (_this.gmap === null) {
                            _this.__buildMap(results[0].geometry.location);
                        }

                        // the map has been created so shift the center of the map
                        //
                        else {

                            //move the center of the map
                            _this.homePoint = results[0].geometry.location;
                            _this.homeAdress = results[0].formatted_address;

                            _this.addMarkerAtCenter();
                            var tag_to_search_for = _this.saneValue('tag_to_search_for', '');
                            //do a search based on settings
                            var radius = _this.saneValue('radiusSelect' , this.default_radius );
                            _this.loadMarkers(results[0].geometry.location, radius, tag_to_search_for);
                        }
                        //if the user entered an address, replace it with a formatted one
                        var addressInput = _this.saneValue('addressInput', '');
                        if (addressInput !== '') {
                            addressInput = results[0].formatted_address;
                        }
                    } else {
                        //check to see if the map exists, if it doesn't then set the location to nowhere ...
                        //probably not the best, but this should (hopefully) be rare.
                        if (_this.gmap === null) {
                            _this.address = "0,0";
                            _this.doGeocode();
                            return;
                        }

                        //address couldn't be processed, so use the center of the map
                        var tag_to_search_for = _this.saneValue('tag_to_search_for', '');
                        var radius = _this.saneValue('radiusSelect' , this.default_radius );
                        _this.loadMarkers(null, radius, tag_to_search_for);
                    }

                }
            );
        };

        /***************************
         * function: __getMarkerUrl
         * usage:
         *        Builds the url for store pages
         * parameters:
         *        aMarker:
         *            the ajax result to build the information from
         * returns: an url
         */
        this.__getMarkerUrl = function (aMarker) {
            var url = '';

            if (typeof aMarker === "object") {
                //add an http to the url
                if ((slplus.options.use_pages_links === "on") && (aMarker.sl_pages_url !== '')) {
                    url = aMarker.sl_pages_url;
                } else if (aMarker.url !== '') {
                    if ((aMarker.url.indexOf("http://") === -1) &&
                        (aMarker.url.indexOf("https://") === -1)
                    ) {
                        aMarker.url = "http://" + aMarker.url;
                    }
                    if (aMarker.url.indexOf(".") !== -1) {
                        url = aMarker.url;
                    }
                }
            }

            aMarker.web_link = url;

            return url;
        };

        /***************************
         * function: __createAddress
         * usage:
         *        Build a formatted address string
         * parameters:
         *        aMarker:
         *            the ajax result to build the information from
         * returns: a formatted address string
         */
        this.__createAddress = function (aMarker) {

            var address = '';
            if (aMarker.address !== '') {
                address += aMarker.address;
            }

            if (aMarker.address2 !== '') {
                address += ", " + aMarker.address2;
            }

            if (aMarker.city !== '') {
                address += ", " + aMarker.city;
            }

            if (aMarker.state !== '') {
                address += ", " + aMarker.state;
            }

            if (aMarker.zip !== '') {
                address += ", " + aMarker.zip;
            }

            if (aMarker.country !== '') {
                address += ", " + aMarker.country;
            }

            return address;
        };

        /**
         * Create the info bubble for a map location.
         *
         * @param {object} aMarker a map marker object.
         */
        this.createMarkerContent = function (thisMarker) {
            thisMarker['url'] = this.__getMarkerUrl(thisMarker);
            thisMarker['fullAddress'] = this.__createAddress(thisMarker);
            return slplus.options.bubblelayout.replace_shortcodes(thisMarker);
        };

        /**
         * Return a proper search address for directions.
         * Use the address entered if provided.
         * Use the GPS coordinates if not and use location is on and coords available.
         * Otherwise use the center of the country.
         */
        this.getSearchAddress = function (defaultAddress) {
            var searchAddress = jQuery('#addressInput').val();
            if (!searchAddress) {
                if ((slplus.options.use_sensor) && (sensor.lat !== 0.00) && (sensor.lng !== 0.00)) {
                    searchAddress = sensor.lat + ',' + sensor.lng;
                } else {
                    searchAddress = defaultAddress;
                }
            }
            return searchAddress;
        };

        /**
         * Get a sane value from the HTML document.
         *
         * @param {string} id of control to look at
         * @param {string} default value to return
         * @return {undef}
         */
        this.saneValue = function (id, defaultValue) {
            var name = document.getElementById(id);
            if (name === null) {
                name = defaultValue;
            }
            else {
                name = name.value;
            }
            return name;
        };

        /***************************
         * function: loadMarkers
         * usage:
         *        Sends an ajax request and drops the markers on the map
         * parameters:
         *        center:
         *            the center of the map (where to center to)
         * returns: none
         */
        this.loadMarkers = function (center, radius, tags) {

            //determines if we need to invent real variables (usually only done at the beginning)
            //
            if (center === null) {
                center = this.gmap.getCenter();
            }
            if (radius === null) {
                radius = this.default_radius;
            }
            this.lastCenter = center;
            this.lastRadius = radius;
            if (tags === null) {
                tags = '';
            }

            var _this = this;
            var ajax = new slp.Ajax();

            // Setup our variables sent to the AJAX listener.
            //
            var action = {
                address: this.saneValue('addressInput', 'no address entered'),
                formdata: jQuery('#searchForm').serialize(),
                lat: center.lat(),
                lng: center.lng(),
                name: this.saneValue('nameSearch', ''),
                options: slplus.options,
                radius: radius,
                tags: tags
            };

            // On Load
            if (slplus.options.immediately_show_locations === '1') {
                action.action = 'csl_ajax_onload';
                slplus.options.immediately_show_locations = '0';

                // Search
            } else {
                action.action = 'csl_ajax_search';
            }

            // Send AJAX call
            //
            ajax.send( action, _this.process_ajax_response );
        };

        /**
         * Process the AJAX responses for locations.
         */
        this.process_ajax_response = function ( response ) {
            valid_response = (typeof response.response !== 'undefined');
            if ( valid_response ) { valid_response = response.success; }

            if ( valid_response ) {
                cslmap.latest_response = response;
                cslmap.clearMarkers();
                cslmap.putMarkers( response.response );

            } else {
                if (window.console) {
                    console.log('SLP server did not send back a valid JSONP response.');
                    if ( typeof response.response !== 'undefined' ) {
                        console.log( 'Response: ' + response.response );
                    }
                    if ( typeof response.message !== 'undefined' ) {
                        var sidebar = document.getElementById('map_sidebar');
                        sidebar.innerHTML = response.message;
                    }
                }
            }
        };

        /***************************
         * function: tagFilter
         * usage:
         *        Sends an ajax request to only get the tags in the current search results
         * parameters:
         *        none
         * returns: none
         */
        this.tagFilter = function () {

            //repeat last search passing tags
            var tag_to_search_for = this.saneValue('tag_to_search_for', '');
            this.loadMarkers(this.lastCenter, this.lastRadius, tag_to_search_for);
            jQuery('#map_box_image').hide();
            jQuery('#map_box_map').show();
        };

        /***************************
         * function: searchLocations
         * usage:
         *        begins the process of returning search results
         * parameters:
         *        none
         * returns: none
         */
        this.searchLocations = function () {
            var append_this =
                typeof slplus.options.append_to_search !== 'undefined' ?
                    slplus.options.append_to_search :
                    '';

            var address = this.saneValue('addressInput', '') + append_this;

            this.unhide_map();

            google.maps.event.trigger(this.gmap, 'resize');

            // Address was given, use it...
            //
            if (address !== '') {
                this.address = cslutils.escapeExtended(address);
                this.doGeocode();

                // Otherwise use the current map center as the center location
                //
            } else {
                var tag_to_search_for = this.saneValue('tag_to_search_for', '');
                var radius = this.saneValue('radiusSelect', this.default_radius );
                this.loadMarkers( null , radius, tag_to_search_for);
            }
        };

        /**
         * Render a marker in the results section
         *
         * @param {object} aMarker marker data for a single location
         * @returns {string} a html div with the data properly displayed
         */
        this.createSidebar = function (aMarker) {

            // Web Link
            // the anchor link to the website or store pages page if pages replaces websites is on
            //
            aMarker.web_link = '';
            var url = this.__getMarkerUrl(aMarker);
            if (url !== '') {
                aMarker.web_link = "<a href='" + url + "' target='" + ((slplus.options.use_same_window === "on") ? '_self' : '_blank') + "' class='storelocatorlink'><nobr>" + slplus.options['label_website'] + "</nobr></a><br/>";
            }

            //if we are showing tags in the table
            //
            aMarker.pro_tags = '';
            if (jQuery.trim(aMarker.tags) !== '') {
                var tagclass = aMarker.tags.replace(/\W/g, '_');
                aMarker.pro_tags = '<br/><div class="' + tagclass + ' slp_result_table_tags"><span class="tagtext">' + aMarker.tags + '</span></div>';
            }

            // City, State, Zip
            // Formatted US-style
            //
            aMarker.city_state_zip = '';
            if (jQuery.trim(aMarker.city) !== '') {
                aMarker.city_state_zip += aMarker.city;
                if (jQuery.trim(aMarker.state) !== '' || jQuery.trim(aMarker.zip) !== '') {
                    aMarker.city_state_zip += ', ';
                }
            }
            if (jQuery.trim(aMarker.state) !== '') {
                aMarker.city_state_zip += aMarker.state;
                if (jQuery.trim(aMarker.zip) !== '') {
                    aMarker.city_state_zip += ' ';
                }
            }
            if (jQuery.trim(aMarker.zip) !== '') {
                aMarker.city_state_zip += aMarker.zip;
            }

            // Phone and Fax with Labels
            //
            aMarker.phone_with_label = (jQuery.trim(aMarker.phone) !== '') ? slplus.options['label_phone'] + aMarker.phone : '';
            aMarker.fax_with_label = (jQuery.trim(aMarker.fax) !== '') ? slplus.options['label_fax'] + aMarker.fax : '';

            // Search address and formatted location address
            //
            var address = this.__createAddress(aMarker);
            aMarker.location_address = encodeURIComponent(address);
            aMarker.search_address = encodeURIComponent(this.getSearchAddress(this.address));
            aMarker.hours_sanitized = jQuery("<div/>").html(aMarker.hours).text();

            /**
             * Create and entry in the results table for this location.
             */
            var div = document.createElement('div');
            div.innerHTML = slplus.results_string.replace_placeholders(
                aMarker.name,
                parseFloat(aMarker.distance).toFixed(1),
                slplus.distance_unit,
                aMarker.address,
                aMarker.address2,
                aMarker.city_state_zip,
                aMarker.phone_with_label,
                aMarker.fax_with_label,
                aMarker.web_link,
                aMarker.email_link,
                slplus.options.map_domain,
                aMarker.search_address,
                aMarker.location_address,
                slplus.options['label_directions'],
                aMarker.pro_tags,
                aMarker.id,
                aMarker.country,
                aMarker.hours_sanitized,
                aMarker
            ).
                replace_shortcodes(aMarker)
            ;
            div.className = 'results_wrapper';
            div.id = 'slp_results_wrapper_' + aMarker.id;

            return div;
        };

        /**
         * Unhide the map div.
         */
        this.unhide_map = function() {
            if ( this.map_hidden ) {
                jQuery('#map_box_image').hide();
                jQuery('#map_box_map').show();
                this.map_hidden = false;
            }
        }

        //dumb browser quirk trick ... wasted two hours on that one
        this.__init();
    }

}; // End slp namespace


/***************************************************************************
 *
 * CSL Main Execution
 *
 */
var cslmap;
var cslutils;

/**
 * Set various functions and methods to help manage the map.
 *
 * @returns {undefined}
 */
function setup_Helpers() {

    /**
     * Replace shortcodes in a string with current marker data as appropriate.
     *
     * The "new form" shortcode placeholders.
     *
     * Shortcode format:
     *    [<shortcode> <attribute> <modifier> <modifier argument>]
     *
     *    [slp_location <field_slug> <modifier>]
     *
     * Marker data is expected to be passed in the first argument as an object.
     *
     * @returns {string}
     */
    String.prototype.replace_shortcodes = function () {
        var args = arguments;
        var thisMarker = args[0];
        var shortcode_complex_regex = /\[(\w+)\s+(\w+)\s*(\w*)(?:[\s="]*)(\w*)(?:[\s"]*)\]/g;

        return this.replace(
            shortcode_complex_regex,
            function (match, shortcode, attribute, modifier, modarg) {

                switch (shortcode) {
                    // SHORTCODE: slp_location
                    // processes the location data
                    //
                    case 'slp_location':
                        if (attribute === 'latitude') {
                            attribute = 'lat';
                        }
                        if (attribute === 'longitude') {
                            attribute = 'lng';
                        }

                        // Output NOTHING if attribute is empty
                        //
                        if (!thisMarker[attribute]) {
                            return '';
                        }

                        // Set prefix/suffix according to the modifier
                        //
                        var prefix = '';
                        var suffix = '';
                        var raw_output = true;
                        var value = thisMarker[attribute];

                        // Special Field Processing
                        switch (attribute) {
                            case 'hours':
                                raw_output = false;
                                break;

                            default:
                                break;
                        }

                        // Modifier Processing
                        //
                        switch (modifier) {

                            // MODIFIER: suffix
                            //
                            case 'suffix':
                                switch (modarg) {
                                    case 'br':
                                        suffix = '<br/>';
                                        break;
                                    case 'comma':
                                        suffix = ',';
                                        break;
                                    case 'comma_space':
                                        suffix = ', ';
                                        break;
                                    case 'space':
                                        suffix = ' ';
                                        break;
                                    default:
                                        break;
                                }
                                break;

                            // MODIFIER: wrap
                            //
                            case 'wrap':
                                switch (modarg) {
                                    case 'img':
                                        prefix = '<img src="';
                                        suffix = '" class="sl_info_bubble_main_image">';
                                        break;
                                    case 'mailto':
                                        prefix = '<a href="mailto:';
                                        suffix = '" target="_blank" id="slp_marker_email" class="storelocatorlink">';
                                        break;
                                    case 'website':
                                        prefix = '<a href="';
                                        suffix = '" ' +
                                        'target="' + ((slplus.options.use_same_window === "on") ? '_self' : '_blank') + '" ' +
                                        'id="slp_marker_website" ' +
                                        'class="storelocatorlink" ' +
                                        '>';
                                        break;

                                    case 'fullspan':
                                        prefix = '<span class="results_line location_' + attribute + '">';
                                        suffix = '</span>';
                                        break;

                                    default:
                                        break;
                                }
                                break;

                            // MODIFIER: format
                            //
                            case 'format':
                                switch (modarg) {
                                    case 'decimal1':
                                        value = parseFloat(thisMarker[attribute]).toFixed(1);
                                        break;
                                    case 'decimal2':
                                        value = parseFloat(thisMarker[attribute]).toFixed(2);
                                        break;
                                    case 'sanitize':
                                        value = thisMarker[attribute].replace(/\W/g, '_');
                                        break;
                                    case 'text':
                                        value = jQuery("<div/>").html(thisMarker[attribute]).text();
                                        break;
                                    default:
                                        break;
                                }
                                break;

                            case 'raw':
                                raw_output = true;
                                break;

                            // MODIFIER: Unknown, do nothing
                            //
                            default:
                                break;
                        }
                        var newOutput =
                            (raw_output) ?
                                value :
                                jQuery("<div/>").html(value).text();
                        return prefix + newOutput + suffix;

                    // SHORTCODE: slp_option
                    // processes the option settings
                    //
                    case 'slp_option' :
                        // Output NOTHING if attribute is empty
                        //
                        if (!slplus.options[attribute]) {
                            return '';
                        }

                        // Set prefix/suffix according to the modifier
                        //
                        prefix = '';
                        suffix = '';
                        switch (modifier) {

                            // MODIFIER: ifset
                            // if the marker attribute specified by modarg is empty, don't output anything.
                            //
                            case 'ifset':
                                if (!thisMarker[modarg]) {
                                    return '';
                                }
                                break;

                            case 'wrap':
                                switch (modarg) {

                                    case 'directions':
                                        prefix = '<a href="http://' + slplus.options.map_domain +
                                        '/maps?saddr=' + encodeURIComponent(cslmap.getSearchAddress(cslmap.address)) +
                                        '&daddr=' + encodeURIComponent(thisMarker['fullAddress']) +
                                        '" target="_blank" class="storelocatorlink">';
                                        suffix = '</a> ';
                                        break;

                                    case 'fullspan':
                                        prefix = '<span class="results_line location_' + attribute + '">';
                                        suffix = '</span>';
                                        break;

                                    default:
                                        break;
                                }
                                break;

                            default:
                                break;
                        }
                        return prefix + jQuery('<div/>').html(slplus.options[attribute]).text() + suffix;

                    // SHORTCODE: HTML
                    //
                    case 'html':

                        // Doing something a little different, process the modifier FIRST
                        // so a short circuit can happen.
                        //
                        switch (modifier) {

                            // MODIFIER: ifset
                            case 'ifset':
                                if (!thisMarker[modarg]) {
                                    return '';
                                }
                                break;
                            default:
                                break;
                        }

                        switch (attribute) {

                            // ATTRIBUTE: br
                            case 'br':
                                return '<br/>';

                            // ATTRIBUTE: closing_anchor
                            case 'closing_anchor':
                                return '</a>';

                            default:
                                break;
                        }
                        break;

                    // Unknown Shortcode
                    //
                    default:
                        return match + ' not supported';
                }
            }
        );
    }

    /**
     * String Formatting, JavaScript sprintf
     *
     * The original shortcode replacement for results using {#} placeholders.
     *
     * @returns {String.prototype@call;replace}
     */
    String.prototype.replace_placeholders = function () {
        var args = arguments;
        return this.replace(
            /{(\d+)(\.(\w+)\.?(\w+)?)?}/g,
            function (match, number, dotsubname, subname, subsubname) {

                // aMarker[#] is defined
                return typeof args[number] !== 'undefined'

                    // aMarker[#] is not a complex object - just return the value of that field number
                    //
                    ? typeof args[number] !== 'object'
                    ? args[number]

                    // aMarker[#] is a complex oject,
                    // check aMarker[#][subname] to see if it is an object, if not just return the value we find there
                    //
                    : typeof args[number][subname] !== 'object'
                    ? typeof args[number][subname] !== 'undefined' ? args[number][subname] : ''

                    // aMarker[#][subname] is a complex oject,
                    // check aMarker[#][subname][subsubname] to see if it is an object, if not just return the value we find there
                    //
                    : (args[number][subname] !== null)
                    ? typeof args[number][subname][subsubname] !== 'undefined' ? args[number][subname][subsubname] : ''

                    // Ran out of possibilities, just return an empty string.
                    //
                    : ''

                    // aMarker[#] not defined, return blank
                    : ''
                    ;
            });
    };


}

/**
 * Setup the map settings and get it rendered.
 *
 * @returns {undefined}
 */
function setup_Map() {

    // Initialize Utilities
    //
    cslutils = new slp.Utils();

    // Initialize the map based on sensor activity
    //
    // There are 4 possibilities, and we set the cslmap object as
    // late as possible for each...
    //
    // 1) Sensor Active, Location Service OK
    // 2) Sensor Active, Location Service FAIL
    // 3) Sensor Active, But No Location Support
    // 4) Sensor Inactive
    //
    if (slplus.options.use_sensor) {
        sensor = new slp.LocationServices();
        if (sensor.LocationSupport) {
            sensor.currentLocation(
                // 1) Success on Location
                //
                function (loc) {
                    clearTimeout(sensor.location_timeout);
                    cslmap = new slp.Map();
                    cslmap.usingSensor = true;
                    sensor.lat = loc.coords.latitude;
                    sensor.lng = loc.coords.longitude;
                    cslmap.__buildMap(new google.maps.LatLng(loc.coords.latitude, loc.coords.longitude));
                },
                // 2) Failed on location
                //
                function (error) {
                    clearTimeout(sensor.location_timeout);
                    if (!sensor.errorCalled) {
                        sensor.errorCalled = true;
                        slplus.options.use_sensor = false;
                        cslmap = new slp.Map();
                        cslmap.usingSensor = false;
                        cslmap.doGeocode();
                    }
                }
            );

            // 3) GPS Sensor Not Working (like IE8)
            //
        } else {
            slplus.options.use_sensor = false;
            cslmap = new slp.Map();
            cslmap.usingSensor = false;
            cslmap.doGeocode();
        }

        // 4) No Sensor
        //
    } else {
        slplus.options.use_sensor = false;
        cslmap = new slp.Map();
        cslmap.usingSensor = false;
        // If set id attr
        //
        if (slplus.options.id_addr != null) {
            cslmap.address = slplus.options.id_addr;
        }
        cslmap.doGeocode();
    }
}

/*
 * When the document has been loaded...
 *
 */
jQuery(document).ready(
    function () {

        // Regular Expression Test Patterns
        //
        var radioCheck = /radio|checkbox/i,
            keyBreaker = /[^\[\]]+/g,
            numberMatcher = /^[\-+]?[0-9]*\.?[0-9]+([eE][\-+]?[0-9]+)?$/;

        // isNumber Test
        //
        var isNumber = function (value) {
            if (typeof value === 'number') {
                return true;
            }

            if (typeof value !== 'string') {
                return false;
            }

            return value.match(numberMatcher);
        };

        // Form Parameters Processor
        //
        jQuery.fn.extend({
            // Get the form parameters
            //
            formParams: function (convert) {
                if (this[0].nodeName.toLowerCase() == 'form' && this[0].elements) {

                    return jQuery(jQuery.makeArray(this[0].elements)).getParams(convert);
                }
                return jQuery("input[name], textarea[name], select[name]", this[0]).getParams(convert);
            },
            // Get a specific form element
            //
            getParams: function (convert) {
                var data = {},
                    current;

                convert = convert === undefined ? true : convert;

                this.each(function () {
                    var el = this,
                        type = el.type && el.type.toLowerCase();
                    //if we are submit, ignore
                    if ((type == 'submit') || !el.name) {
                        return;
                    }

                    var key = el.name,
                        value = jQuery.data(el, "value") || jQuery.fn.val.call([el]),
                        isRadioCheck = radioCheck.test(el.type),
                        parts = key.match(keyBreaker),
                        write = !isRadioCheck || !!el.checked,
                    //make an array of values
                        lastPart;

                    if (convert) {
                        if (isNumber(value)) {
                            value = parseFloat(value);
                        } else if (value === 'true' || value === 'false') {
                            value = Boolean(value);
                        }

                    }

                    // go through and create nested objects
                    current = data;
                    for (var i = 0; i < parts.length - 1; i++) {
                        if (!current[parts[i]]) {
                            current[parts[i]] = {};
                        }
                        current = current[parts[i]];
                    }
                    lastPart = parts[parts.length - 1];

                    //now we are on the last part, set the value
                    if (lastPart in current && type === "checkbox") {
                        if (!jQuery.isArray(current[lastPart])) {
                            current[lastPart] = current[lastPart] === undefined ? [] : [current[lastPart]];
                        }
                        if (write) {
                            current[lastPart].push(value);
                        }
                    } else if (write || !current[lastPart]) {
                        current[lastPart] = write ? value : undefined;
                    }

                });
                return data;
            }
        });

        // Our map initialization
        //
        if (jQuery('div#sl_div').is(":visible")) {
            if (typeof slplus !== 'undefined') {
                if (typeof google !== 'undefined') {
                    setup_Helpers();
                    setup_Map();
                } else {
                    jQuery('#sl_div').html('Looks like you turned off SLP Maps under General Settings but need them here.');
                }
            } else {
                jQuery('#sl_div').html('Store Locator Plus did not initialize properly.');
            }
        }
    }
);
