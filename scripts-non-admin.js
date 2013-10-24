/**
 * @package Placemarks
 * @author Gabriel Nagmay <gabriel.nagmay.com>
 * @link http://wordpress.org/extend/plugins/placemarks/
 */
 
//<![CDATA[

/**
 * Set Globals
 * Also available via php: placemarks_locations_json, placemarks_types_json, placemarks_marker_array, default_center, default_zoom
 */
var marker, map, infoWindow = null;
var bounds = new google.maps.LatLngBounds();


/**
 * When the document is ready
 */
jQuery(document).ready(function() {
    placemarks_initialize(); 	// initalize map
	
	// Create a single instance of the InfoWindow object which will be shared by all Map objects to display information to the user.
	infoWindow = new google.maps.InfoWindow();
	
	// Make the info window close when clicking anywhere on the map.
	google.maps.event.addListener(map, 'click', closeInfoWindow);
	
	
	setMarkers();				// set markers
	// and fit (if lat, lng and zoom were not specified
	if(!default_center || !default_zoom){
		map.fitBounds(bounds);
	}
	//console.log(bounds.getNorthEast());
	
});




/**
 * Set up the markers
 */	
function setMarkers() {
	//for (var i = 0; i < placemarks_marker_array.length; i++) {
	  if(placemarks_marker_array.length > 0){
		// Data for the markers [z-index, lat, lng, title, content, icon scr, link href ] 
		var marker = placemarks_marker_array.pop();
		var latlng = new google.maps.LatLng(marker[1],marker[2]);
		bounds.extend(latlng); // add to bounds
		var url = "";
		if(marker[6]){
			url = '<a href="'+marker[6]+'">Learn more ...</a>';
		}
		var thisMark = new google.maps.Marker({
			position: latlng, 
			map: map,
			zIndex: marker[0],
			title:marker[3],
			html: "<strong>"+marker[3]+"</strong><br />"+marker[4]+"<p>"+url+"</p>",
			icon: marker[5]
			//shadow: '<?php bloginfo('template_directory'); ?>/images/marker-shadow.png'
		});  
	
		google.maps.event.addListener(thisMark, 'click', function() {
			openInfoWindow(thisMark);
		});
		
		// and on to the next
		//setTimeout("setMarkers();",100);
		setMarkers();
	  }
	
}

/* Called when clicking anywhere on the map and closes the info window */
closeInfoWindow = function() {
  infoWindow.close();
};

/* Opens the shared info window, anchors it to the specified marker, and displays the marker's position as its content. */
openInfoWindow = function(marker) {
  var markerLatLng = marker.getPosition();
  infoWindow.setOptions({maxWidth:320}); 
  infoWindow.setContent(marker.html);
  infoWindow.open(map, marker);
};

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

     var   myOptions = { // brand new map
            zoom: default_zoom,
            center: default_center, 
            mapTypeControl: true,
            mapTypeControlOptions: {
                mapTypeIds: ['placemarks-map', google.maps.MapTypeId.SATELLITE]
            },
            navigationControl: true,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
			zoomControl: true,
			panControl: false,
    		zoomControlOptions: {
      			style: google.maps.ZoomControlStyle.SMALL
    			}

        	}
    
    // create the map
    var placemarks_map_type = new google.maps.StyledMapType(placemarks_style, styledMapOptions); 	// create styles
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions); 					// create map	
    map.mapTypes.set('placemarks-map', placemarks_map_type); 										// hook em up
    map.setMapTypeId('placemarks-map'); // here too	
    map.setTilt(0);
   
   
}
//]]>	