/**
 * @package Placemarks
 * @author Gabriel Nagmay <gabriel.nagmay.com>
 * @link http://wordpress.org/extend/plugins/placemarks/
 */
 
//<![CDATA[

/**
 * Set Globals
 * Also available via php: placemarks_locations_json, placemarks_types_json, placemarks_place_meta,placemarks_marker_meta, placemarks_locations_meta, placemarks_nonce
 */
var marker, map = null;
var first = true;

/**
 * When the document is ready
 */
jQuery(document).ready(function() {
    placemarks_initialize(); // initalize map
    // build #placemarks-location-selects
    locationJsonToSelect(placemarks_locations_json["locations"]);
    // On change
    jQuery('#placemarks-location-selects').on("change", 'select.placemarks-locations-select', function() { 		// use "delegated on" to relpace ".live"
        var thisLoc = locationJsonFindSlug(placemarks_locations_json["locations"], jQuery(this).val()); 		// find in json
        jQuery(this).nextAll().remove(); 																		// remove extra select inputs
        if (thisLoc) {
            locationJsonToSelect(thisLoc["locations"]); 														// rebuild selects
            if (thisLoc["lat"] && thisLoc["lng"] && !first) { 													// move and zoom map? Don't more the first time
                latlng = new google.maps.LatLng(thisLoc["lat"],thisLoc["lng"]);
                if (thisLoc["zoom"]) {
                    map.setZoom(thisLoc["zoom"]);
                }
                map.panTo(latlng);
            }
        }
    });
	//are there inital values?			
    updateLocationSelect(0);
    //update icon
    jQuery('#placemarks-type').change(function() {
        updateMarkers(jQuery(this).val());
    });
    updateMarkers(jQuery('#placemarks-type').val());
});

/**
 * Function: Update the select boxes starting at index 's'
 * var s: index of select (so that we can do these in order)
 */
function updateLocationSelect(s) {
    if (placemarks_locations_meta && jQuery('#placemarks-location-selects select:eq(' + s + ')').size()) { 		// if select box available
        jQuery('#placemarks-location-selects select:eq(' + s + ') option').each(function() {
            for (var i = 0; i < placemarks_locations_meta.length; i++) {
                if (jQuery(this).val() == placemarks_locations_meta[i]) { 										// we have a match
                    jQuery(this).attr('selected', 'selected'); 													// set option as selected
                    placemarks_locations_meta.splice(i, 1); 													// remove value from array (assumes slugs are unique)
                    jQuery('#placemarks-location-selects select:eq(' + s + ')').change(); 						// trigger change to create next select
                    updateLocationSelect(s + 1); 																// and try to set that one
                    break;
                }
            }
        });
    }
    first = false;
}

/**
 * Function: Take a location json, create a select
 * var l: location JSON
 */
function locationJsonToSelect(l) {
    if (l) {
        var num = jQuery('#placemarks-location-selects select').length + 1; 									// which one will this be (for id)
        var out = '<input type="hidden" name="placemarks-locations-' + num + '_noncename" id="placemarks-locations-' + num + '_noncename" value="' + placemarks_nonce + '" />';
        out += '<select class="placemarks-locations-select" id="placemarks-locations-' + num + '" name="placemarks-locations-' + num + '"><option></option>';
        jQuery.each(l, function(i) {
            out += '<option value="' + l[i]["slug"] + '">' + l[i]["name"] + '</option>';
        });
        out += '</select>';
        jQuery('#placemarks-location-selects').append(out);
    }
}

/**
 * Function: Drill thru json
 * var l: location JSON
 * var slug: teh slug that we are looking for
 */
function locationJsonFindSlug(l, slug) {
    var thisLocation = null;
    jQuery.each(l, function(i) {
        if (l[i]["slug"] == slug) { 												// we found it
            thisLocation = l[i];
            return false;
        } else if (l[i]["locations"]) { 											// or keep looking
			thisLocation = locationJsonFindSlug(l[i]["locations"], slug)
            if (thisLocation) {
                return false;
            }
        }
    });
    return thisLocation;
}

/**
 * Function: Show the marker icon
 * var marker: marker slug
*/
function updateMarkers(marker) {
    jQuery.each(placemarks_types_json["types"], function(j) {
        if (marker == placemarks_types_json["types"][j]["name"]) {
            jQuery('#placemark-marker-image').css("background-image", "url('" + placemarks_types_json["types"][j]["src"] + "')");
            marker = null;
        }
    });
    if (marker !== null) {
        jQuery('#marker-image').css("background-image", ""); 				// none found
    }
} 

/**
 * Function: Map function based on http://www.geocodezip.com/v3_example_click2add_infowindow.html
 * var latlng: Google LatLng 
 */
function setMarker(latlng) {
    if (!marker) { 															//console.log("new marker");
        // create marker
        marker = new google.maps.Marker({
            position: latlng,
            draggable: true,
            map: map,
            zIndex: Math.round(latlng.lat() * -100000) << 5
        });
        // listen for drag 
        google.maps.event.addListener(marker, 'dragend', function(event) {
            marker = setMarker(event.latLng); 								//console.log("drag");
        });
    } else {
        marker.setPosition(latlng); 										//console.log("position changed");
    }
    // edit GPS on form
    jQuery('#' + placemarks_place_meta['m01']['name']).val(latlng.lat());
    jQuery('#' + placemarks_place_meta['m02']['name']).val(latlng.lng());
    return marker;
}

/**
 * Function: Set up the map
 */
function placemarks_initialize() {
    // some style for the map
    var placemarks_style = [
        {
        "featureType": "all",
        "elementType": "labels",
        "stylers": [{
            "visibility": "off"
        }]
    },
        {
        "featureType": "poi.park",
        "elementType": "geometry",
        "stylers": [{
            "visibility": "on"
        }, {
            "saturation": -34
        }, {
            "lightness": 21
        }]
    },
        {
        "featureType": "poi.park",
        "elementType": "labels.text",
        "stylers": [{
            "visibility": "on"
        }]
    },
        {
        "featureType": "poi.park",
        "elementType": "labels.text.fill",
        "stylers": [{
            "color": "#93a294"
        }]
    },
        {
        "featureType": "poi.park",
        "elementType": "labels.icon",
        "stylers": [{
            "visibility": "on"
        }, {
            "saturation": -34
        }, {
            "lightness": 21
        }]
    },
        {
        "featureType": "poi.school",
        "elementType": "geometry",
        "stylers": [{
            "visibility": "off"
        }]
    },
        {
        "featureType": "road",
        "elementType": "geometry",
        "stylers": [{
            "visibility": "on"
        }, {
            "saturation": -36
        }, {
            "lightness": 19
        }]
    },
        {
        "featureType": "road",
        "elementType": "labels",
        "stylers": [{
            "visibility": "on"
        }]
    },
        {
        "featureType": "road",
        "elementType": "labels.text.fill",
        "stylers": [{
            "color": "#808080"
        }]
    },
        {
        "featureType": "road",
        "elementType": "labels.text.stroke",
        "stylers": [{
            "saturation": -36
        }, {
            "lightness": 19
        }]
    },
        {
        "featureType": "road",
        "elementType": "labels.icon",
        "stylers": [{
            "visibility": "on"
        }, {
            "saturation": -36
        }, {
            "lightness": 19
        }]
    },
        {
        "featureType": "water",
        "elementType": "geometry",
        "stylers": [{
            "visibility": "on"
        }]
    },
        {
        "featureType": "water",
        "elementType": "labels",
        "stylers": [{
            "visibility": "on"
        }]
    }
    ];
    var styledMapOptions = {
        name: "Map View"
    };
    var lat = placemarks_place_meta['m01']['value'];
    var lng = placemarks_place_meta['m02']['value'];
    var myOptions = null;
	if (lat !== "" & lng !== "") { 							// we have a marker already!
        myOptions = {
            zoom: 20,
            center: new google.maps.LatLng(lat, lng),
            mapTypeControl: true,
            mapTypeControlOptions: {
                mapTypeIds: ['placemarks-map', google.maps.MapTypeId.SATELLITE]
            },
            navigationControl: true,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            draggableCursor: 'crosshair'
        }
    } else {
        myOptions = { // brand new map
            zoom: 10,
            center: new google.maps.LatLng(45.48372492603276, -122.73582458496094),
            mapTypeControl: true,
            mapTypeControlOptions: {
                mapTypeIds: ['placemarks-map', google.maps.MapTypeId.SATELLITE]
            },
            navigationControl: true,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            draggableCursor: 'crosshair'
        }
    }
    // create the map
    var placemarks_map_type = new google.maps.StyledMapType(placemarks_style, styledMapOptions); 	// create styles
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions); 					// create map	
    map.mapTypes.set('placemarks-map', placemarks_map_type); 										// hook em up
    map.setMapTypeId('placemarks-map'); // here too	
    map.setTilt(0);
    if (lat !== "" && lng !== "") {
        marker = setMarker(new google.maps.LatLng(lat, lng));
    }
    // on click
    google.maps.event.addListener(map, 'click', function(event) {
        marker = setMarker(event.latLng);
    });
    // html5 location
    jQuery("#mapgps").hide();
    if ( !! navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            jQuery("#mapgps").show().click(function() {
                var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                marker = setMarker(latlng);
                map.panTo(latlng); // set center
            });
        });
    } else {
        // no support do nothing
    }
}
//]]>	