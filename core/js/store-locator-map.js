/*****************************************************************************
 * File: store-locator-map.js
 * 
 * Handle map creation, looking up location data, and displaying the store
 * store list.
 *
 *****************************************************************************/

var map;
var geocoder;

var theIcon = new GIcon(G_DEFAULT_ICON);
    theIcon.image = sl_map_end_icon;
if (sl_map_end_icon.indexOf('flag')!='-1') {
    theIcon.shadow = add_base + "/images/flag_shadow.png";
} else if (sl_map_end_icon.indexOf('arrow')!='-1') {
    theIcon.shadow = add_base + "/images/arrow_shadow.png";
} else if (sl_map_end_icon.indexOf('bubble')!='-1') {
    theIcon.shadow = add_base + "/images/bubble_shadow.png";
} else if (sl_map_end_icon.indexOf('marker')!='-1') {
    theIcon.shadow = add_base + "/images/marker_shadow.png";
} else if (sl_map_end_icon.indexOf('sign')!='-1') {
    theIcon.shadow = add_base + "/images/sign_shadow.png";
} else {
    theIcon.shadow = add_base + "/images/blank.png";
}
theIcon.iconSize = new GSize(sl_map_end_icon_width, sl_map_end_icon_height);


/**************************************
 * function: sl_load()
 *
 * Initial map loading, before search is performed.
 *
 */
function sl_load() {
    if (GBrowserIsCompatible()) {
        geocoder = new GClientGeocoder();
        map = new GMap2(document.getElementById('map'));
        if (sl_map_overview_control==1) {
            map.addControl(new GOverviewMapControl());
        }
        map.addMapType(G_PHYSICAL_MAP);
        
        // This is asynchronous, as such we have no idea when it will return
        //
        geocoder.getLatLng(sl_google_map_country, 
            function(latlng) {
                map.setCenter(latlng, sl_zoom_level, sl_map_type);
                
                var customUI = map.getDefaultUI();
                customUI.controls.largemapcontrol3d = slp_largemapcontrol3d;   
                customUI.controls.scalecontrol = slp_scalecontrol;
                customUI.controls.hierarchicalmaptypecontrol = slp_maptypecontrol;                
                map.setUI(customUI);
                
                if (slp_disablescrollwheel) { map.disableScrollWheelZoom(); }
                
                if (sl_load_locations_default) {                    
                    sl_load_locations(map,latlng.lat(),latlng.lng());
                }
            }
        );
    }
}

/**************************************
 * function: sl_load_locations()
 *
 * Show the locations on the map when it is first loaded.
 * Run in the asynchronous map display above if the load locations
 * at startup is set.
 *
 */
function sl_load_locations(map,lat,lng) {
    var bounds = new GLatLngBounds();
    markerOpts = { icon:theIcon };

    // Check if tag searching is enabled/shown
    //
    if (document.getElementById('tag_to_search_for') != null) { 
        taglist = document.getElementById('tag_to_search_for').value; 
    } else {
        taglist = null;
    }

    if (!slp_disableinitialdirectory) {
        var sidebar = document.getElementById('map_sidebar');
        sidebar.innerHTML = '';           
    }
    
    GDownloadUrl(add_base + "/data-xml.php?lat="+lat+"&lng="+lng+"&tags="+taglist,
        function(data, responseCode) {
            var xml = GXml.parse(data);
            var markers = xml.documentElement.getElementsByTagName("marker");
            for (var i = 0; i < markers.length; i++) {
                var name = markers[i].getAttribute('name');
                var address = markers[i].getAttribute('address');
                var distance = parseFloat(markers[i].getAttribute('distance'));
                var description = markers[i].getAttribute('description');
                var url = markers[i].getAttribute('url');
                var email = markers[i].getAttribute('email');
                var hours = markers[i].getAttribute('hours');
                var phone = markers[i].getAttribute('phone');
                var image = markers[i].getAttribute('image');
                var maplat = markers[i].getAttribute('lat');
                var maplong = markers[i].getAttribute('lng');
                var point = new GLatLng(
                    parseFloat(maplat),
                    parseFloat(maplong)
                    );
                var marker = createMarker(point, name, address, "", description, url, email, hours, phone, image);
                                    
                map.addOverlay(marker);
    
                if (!slp_disableinitialdirectory) {
                    var sidebarEntry = createSidebarEntry(marker, name, address, distance, '', url, email, phone);
                    sidebar.appendChild(sidebarEntry);
                }
                                    
                bounds.extend(point);
            }
            map.setCenter(bounds.getCenter(), (map.getBoundsZoomLevel(bounds)-1));
            
            var customUI = map.getDefaultUI();
            customUI.controls.largemapcontrol3d = slp_largemapcontrol3d;   
            customUI.controls.scalecontrol = slp_scalecontrol;
            customUI.controls.hierarchicalmaptypecontrol = slp_maptypecontrol;                
            map.setUI(customUI);
            
            if (slp_disablescrollwheel) { map.disableScrollWheelZoom(); }
        }
    );
 }

/**************************************
 * function: searchLocations()
 *
 * Run this when we do a search, first get the lat/long of the address entered
 * then call find locations near that address.
 *
 */
function searchLocations() {
    var address = document.getElementById('addressInput').value;
    
    geocoder.getLatLng(address, 
        function(latlng) {
            if (!latlng) {
                alert(address + ' not found');
            } else {
                searchLocationsNear(latlng, address); 
            }
        }
    );
    
    jQuery('#map_box_image').hide();
    jQuery('#map_box_map').show();
    map.checkResize();
}


/**************************************
 * function: searchLocations()
 *
 * Run this when we do a search, first get the lat/long of the address entered
 * then call find locations near that address.
 *

 */
function searchLocationsNear(center, homeAddress) {
    var radius  = document.getElementById('radiusSelect').value;
    var taglist = '';     
    if (document.getElementById('tag_to_search_for') != null) {     
        taglist = document.getElementById('tag_to_search_for').value;
    }
    
    var searchUrl = add_base + '/generate-xml.php?' + 
        'lat='     + center.lat() + 
        '&lng='    + center.lng() + 
        '&radius=' + radius +
        '&tags='   + taglist +
        '&address=' + homeAddress
        ;
        
    GDownloadUrl(searchUrl, 
        function(data) {
            var xml = GXml.parse(data);
            var markers = xml.documentElement.getElementsByTagName('marker');
            map.clearOverlays();
   
            var homeIcon = new GIcon(G_DEFAULT_ICON);
            homeIcon.image = sl_map_home_icon;
            if (sl_map_home_icon.indexOf('flag')!='-1') {
                homeIcon.shadow = add_base + "/images/icons/flag_shadow.png";
            } else if (sl_map_home_icon.indexOf('arrow')!='-1') {
                homeIcon.shadow = add_base + "/images/icons/arrow_shadow.png";
            } else if (sl_map_home_icon.indexOf('bubble')!='-1') {
                homeIcon.shadow = add_base + "/images/icons/bubble_shadow.png";
            } else if (sl_map_home_icon.indexOf('marker')!='-1') {
                homeIcon.shadow = add_base + "/images/icons/marker_shadow.png";
            } else if (sl_map_home_icon.indexOf('sign')!='-1') {
                homeIcon.shadow = add_base + "/images/icons/sign_shadow.png";
            } else {
                homeIcon.shadow = add_base + "/images/icons/blank.png";
            }
            homeIcon.iconSize = new GSize(sl_map_home_icon_width, sl_map_home_icon_height);

            var bounds = new GLatLngBounds(); 
            markerOpts = { icon:homeIcon };
            point = new GLatLng (center.lat(), center.lng());
            bounds.extend(point); 
            var homeMarker = new GMarker(point, markerOpts);
            var html = '<div id="sl_info_bubble"><span class="your_location_label">Your Location:</span> <br/>' + homeAddress + '</div>';
            GEvent.addListener(homeMarker, 'click', function() {
                homeMarker.openInfoWindowHtml(html);
                }
            );
            map.addOverlay(homeMarker);

            var sidebar = document.getElementById('map_sidebar');
            sidebar.innerHTML = '';
            
            if (markers.length == 0) {
                sidebar.innerHTML = '<div class="no_results_found"><h2>No results found.</h2></div>';
                geocoder = new GClientGeocoder();
                geocoder.getLatLng(sl_google_map_country, 
                    function(latlng) {
                        map.setCenter(point, sl_zoom_level);
                    }
                );
                return;
            }
   
            for (var i = 0; i < markers.length; i++) {
                var name = markers[i].getAttribute('name');
                var address = markers[i].getAttribute('address');
                var distance = parseFloat(markers[i].getAttribute('distance'));
                var description = markers[i].getAttribute('description');
                var url = markers[i].getAttribute('url');
                var email = markers[i].getAttribute('email');
                var hours = markers[i].getAttribute('hours');
                var phone = markers[i].getAttribute('phone');
                var image = markers[i].getAttribute('image');                
                var point = new GLatLng(
                    parseFloat(markers[i].getAttribute('lat')),                
                    parseFloat(markers[i].getAttribute('lng'))
                    );

                var marker = createMarker(point, name, address, homeAddress, description, url, email, hours, phone, image); 
                var sidebarEntry = createSidebarEntry(marker, name, address, distance, homeAddress, url, email, phone);
                
                map.addOverlay(marker);
                sidebar.appendChild(sidebarEntry);
                bounds.extend(point);
            }
          map.setCenter(bounds.getCenter(), (map.getBoundsZoomLevel(bounds)-1)); 
        }
    );  
}

/**************************************
 */
function createMarker(point, name, address, homeAddress, description, url, email, hours, phone, image) { 
  markerOpts = { icon:theIcon };
  var marker = new GMarker(point, markerOpts);
  
  var more_html="";
  if(url.indexOf("http://")==-1) {
    url="http://"+url;
  }
  
  if (url.indexOf("http://")!=-1 && url.indexOf(".")!=-1) {
    more_html+="| <a href='"+url+"' target='_blank' class='storelocatorlink'><nobr>" + sl_website_label +"</nobr></a>"
  } else {
    url="";
  }
  
  if (email.indexOf("@")!=-1 && email.indexOf(".")!=-1) {
    if (!slp_use_email_form) { 
      more_html+="| <a href='mailto:"+email+"' target='_blank' class='storelocatorlink'><nobr>" + email +"</nobr></a>";
    } else {
      more_html+="| <a href='javascript:slp_show_email_form("+'"'+email+'"'+");' class='storelocatorlink'><nobr>" + email +"</nobr></a><br/>";
    }                    
  }
  
  if (image.indexOf(".")!=-1) {more_html+="<br/><img src='"+image+"' class='sl_info_bubble_main_image'>"} else {image=""}
  if (description!="") {more_html+="<br/>"+description+"";} else {description=""}
  if (hours!="") {more_html+="<br/><span class='location_detail_label'>Hours:</span> "+hours;} else {hours=""}
  if (phone!="") {more_html+="<br/><span class='location_detail_label'>Phone:</span> "+phone;} else {phone=""}
  
    var street    = address.split(',')[0]; 
        if (street.split(' ').join('')!=""){
            street+='<br/>';
        }else{
            street="";
        }
    var street2   = address.split(',')[1]; 
        if (street2.split(' ').join('')!=""){
            street2+='<br/>';
        }else{
            street2="";
        }
    var city      = address.split(',')[2]; 
        if (city.split(' ').join('')!=""){
            city+=', ';
        }else{
            city="";
        }
    var state_zip = address.split(',')[3]; 	  
  
  if (homeAddress.split(" ").join("")!="") {
    var html = '<div id="sl_info_bubble"><!--tr><td--><strong>' + name + '</strong><br>' + street + street2 + city + state_zip + '<br/> <a href="http://' + sl_google_map_domain + '/maps?saddr=' + encodeURIComponent(homeAddress) + '&daddr=' + encodeURIComponent(address) + '" target="_blank" class="storelocatorlink">Directions</a> ' + more_html + '<br/><!--/td></tr--></div>'; 
  } else {
    var html = '<div id="sl_info_bubble"><!--tr><td--><strong>' + name + '</strong><br>' + street + street2 + city + state_zip + '<br/> <a href="http://' + sl_google_map_domain + '/maps?q=' + encodeURIComponent(address) + '" target="_blank" class="storelocatorlink">Map</a> ' + more_html + '<!--/td></tr--></div>';
  }
  GEvent.addListener(marker, 'click', function() {
    marker.openInfoWindowHtml(html);
  });
  return marker;
}

var resultsDisplayed=0;
var bgcol="white";

/**************************************
 */
function createSidebarEntry(marker, name, address, distance, homeAddress, url, email, phone) { 
    document.getElementById('map_sidebar_td').style.display='block';
      var div = document.createElement('div');
      var street = address.split(',')[0]; 
      var street2 = address.split(',')[1]; 
      var city = address.split(',')[2]; 
        if (city.split(' ').join('')!=""){
            city+=', ';
        }else{
            city="";
        }
      var state_zip = address.split(',')[3];
      
      var link = '';
      if(url.indexOf("http://")==-1) {url="http://"+url;} 
      if (url.indexOf("http://")!=-1 && url.indexOf(".")!=-1) {link="<a href='"+url+"' target='_blank' class='storelocatorlink'><nobr>" + sl_website_label +"</nobr></a><br/>"} else {url="";}

      var elink = "";
      if (email.indexOf("@")!=-1 && email.indexOf(".")!=-1) {
          if (!slp_use_email_form) { 
              elink="<a href='mailto:"+email+"' target='_blank' class='storelocatorlink'><nobr>" + email +"</nobr></a><br/>";
          } else {
              elink="<a href='javascript:slp_show_email_form("+'"'+email+'"'+");' class='storelocatorlink'><nobr>" + email +"</nobr></a><br/>";
          }              
      }

      
      var html = '<center><table width="96%" cellpadding="4px" cellspacing="0" class="searchResultsTable">' +
                 '<tr>' +
                    '<td class="results_row_left_column">' +
                        '<span class="location_name">' + name + '</span><br>' + 
                        distance.toFixed(1) + ' ' + sl_distance_unit + '</td>' +
                    '<td class="results_row_center_column">' + 
                        street + '<br/>' + 
                        street2 + '<br/>' + 
                        city + state_zip +'<br/>'+
                        phone +
                    '</td>' +
                    '<td class="results_row_right_column">' + 
                        link + 
                        elink +
                        '<a href="http://' + sl_google_map_domain + 
                        '/maps?saddr=' + encodeURIComponent(homeAddress) + 
                        '&daddr=' + encodeURIComponent(address) + 
                        '" target="_blank" class="storelocatorlink">Directions</a>'+
                        '</td>' +
                        '</tr></table></center>'; 
      div.innerHTML = html;
      div.className='results_entry';
      resultsDisplayed++;
      GEvent.addDomListener(div, 'click', function() {
        GEvent.trigger(marker, 'click');
      }); 
    return div;
}
