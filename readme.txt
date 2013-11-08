=== Plugin Name ===
Contributors: gabrielmcgovern
Donate link: http://www.dreamhost.com/donate.cgi?id=17157
Tags: placemarks, placemark, map, maps, places, mark, marker, google maps
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow authors to easily manage placemarks and embed custom maps.

== Description ==

Adds a new `placemark post type` that allows users to create and update map content. 

The author interface includes:

**Place**

* An interactive map to drop and move pins
* A way to edit GPS by hand and make use of the geolocation on your mobile phone
* An editable set of drop-down lists for picking locations
* An alternative text area to describe the location

**Mark**

* An editable drop-down of marker types and associated icons
* An optional title
* Optional bubble text
* Optional link

The locations and types drop-downs are set by an administrator. This allow the you to customize the types of
markers that authors can drop ... 

To embed the maps a simple short code is used. You can limit which type of placemarks will show up on each map.   

== Installation ==

1. Upload the `placemarks` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create some new placemarks
1. Include the shortcode [placemarks] on any page or post

== Frequently Asked Questions ==

= What can the shortcode do? =

[placemarks types="type name" lat=# lng =# zoom=# width="" height="" alt=true/false]

Everything after `placemark` is optional:

* `types`: String. List of types to include on the map "default, foo bar" (shows all by default)
* `lat`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `lng`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `zoom`: Number. Use lat+lng+zoom to choose an initial map view (defaults to show all pins)
* `width`: String. Change the width of the map (default '100%') 
* `height`: String. Change the height of the map (default '400px')
* `alt`: True/False. A text list of all the markers shows under th map by default. This can be used to turn it off. 

= How do I edit the locations and types drop-downs? =

Go to `Settings` -> `Placemarks`. Here you can use JSON to create custom lists. For example:

**Marker Types (JSON)**: `name` and `src` are required

	{ "types": [
  		{"name":"Default", "src":"http://www.yoursite.com/default.png"},
  		{"name":"Hot", "src":"http://www.yoursite.com/hot.png"}
        ]
    }
    
**Locations (JSON)**: `name` and `slug` are required. slug should always be unique

	{"locations": [
  		{"name":"Oregon","slug":"or"},
    	{"name":"Washington","slug":"wa"}
        ]
    }
    
Optionally, you can also include: `lat`, `lng`, `zoom`. Together, these control the map when selected in the admin interface.

	{"locations": [
  		{"name":"Oregon","slug":"or","lat":45.563282,"lng":-122.673457,"zoom":17},
    	{"name":"Washington","slug":"wa","lat":45.563838,"lng":-122.672342,"zoom":19}
        ]
    }
  
Each location can also include `locations`. This can be used to create hierarchies of select lists!

	{"locations": [
  		{"name":"Oregon","slug":"or", "locations":[
        	{"name":"Portland","slug":"pdx"},
            {"name":"Bend","slug":"bend"}
       		]
        },
    	{"name":"Washington","slug":"wa", "locations":[
        	{"name":"Seattle","slug":"sea"}
      		]
        }
        ]
    }

== Screenshots ==

1. How a map might look on a post page
1. Creating a new 'Placemark'
1. With the settings you can customize the types of placemarks, locations and icons available.
1. And then we embed a map!

== Changelog ==

= 1.0.1 =
* Bug fix: Only enqueue js on placemark admin pages
* Bug fix: Fix error on pages with comments
* Feature: Add edit link to each placemark on map

= 1.0.0 =
* First version to be released. 

== Upgrade Notice ==

= 1.0.1 =
* Bug fixes. New edit links on map.

= 1.0.0 =
Seems stable enough, but only has basic features. 

