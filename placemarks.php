<?php
/*
	Plugin Name: Placemarks
	Plugin URI: http://wordpress.org/extend/plugins/placemarks/
	Description: Allow authors to easily manage placemarks and embed custom maps.
	Version: 1.0.1
	Author: Gabriel Nagmay
	Author URI: http://gabriel@nagmay.com
	License: GPL2
*/

/*  Copyright 2013  Gabriel Nagmay  (email: gabriel@nagmay.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
 
// options page
require(dirname(__FILE__) . "/options.php");
 
/* ==================================================================
 * global variables (outside admin
 * ================================================================== */
						
// get type json from options						
$placemarks_options = (get_option('placemarks_options'));
$placemarks_types_json = json_decode($placemarks_options['placemarks_types_json']);
$placemarks_locations_json = json_decode($placemarks_options['placemarks_locations_json']);

// some defaults
if(!$placemarks_types_json){
	$placemarks_types_json =json_decode('{ "types": [{"name":"Default", "src":"http://www.pcc.edu/about/locations/map/images/icons/defaults/default.png"}]}');
}
if(!$placemarks_locations_json){
		$placemarks_locations_json =json_decode('{ "locations": [{"name":"Default", "slug":"default"}]}');
}

/* ==================================================================
 * Set up custom post type and administrator interface: 
 * ================================================================== */
if (is_admin()) :

/* ====== Set up custom post type: placemarker ====== */
add_action( 'init', 'create_placemarks_post_type' );
function create_placemarks_post_type() {
	
	/* set labels */
	$labels = array(
		'name' => __( 'Placemarks' ),
		'singular_name' => __( 'Placemark' ),
		'add_new' => __( 'Add New' ),
		'all_items' => __( 'All Placemarks' ),
		'add_new_item' => __( 'Add New' ),
		'edit_item' => __( 'Edit Placemark' ),
		'new_item' => __( 'New Placemark' ),
		'view_item' => __( 'View Placemark' ),
		'search_items' => __( 'Search Placemarks' ),
		'not_found' => __( 'No placemarkers found' ),
		'not_found_in_trash' => __( 'No placemarks found in trash' ),
		'parent_item_colon' => __( 'Parent placemark' )
		//'menu_name' => default to 'name'
	);
	
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => false, 	// individual placemarks would show publicly 
		'query_var' => false,			// ditto
		'rewrite' => false,				// ditto
		'capability_type' => 'post',
		'hierarchical' => false,
		'supports' => array(
			'author'
			//'custom-fields',
		),
		'menu_position' => 20, // below pages
		'register_meta_box_cb' => 'add_placemarks_metaboxes' // for custom metaboxes
	);
	register_post_type( 'placemark', $args );
		
	//flush_rewrite_rules();  								// coment out when live
}

/* ==================================================================
 * Register style sheets and scripts for admin area
 * ================================================================== */
add_action( 'admin_enqueue_scripts', 'placemarks_scripts_and_styles', 10, 1 ); // updated from admin_init, need hook
function placemarks_scripts_and_styles($hook) {
	global $post;
	wp_register_style( 'placemarks_style', plugins_url( 'placemarks/style.css' ) );
	wp_enqueue_style( 'placemarks_style' ); 									// needed for icon on all admin pages
	wp_register_script('placemarks_google_maps_v3', 'http'. ( is_ssl() ? 's' : '' ) .'://maps.google.com/maps/api/js?sensor=true');
	wp_register_script('placemarks_scripts', plugins_url( 'placemarks/scripts.js'),array(), false, true );
	
	if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) { // only enqueue js on placemark admin pages //echo $hook;
        if ( 'placemark' === $post->post_type ) { 
			wp_enqueue_script( 'placemarks_google_maps_v3' );
			wp_enqueue_script( 'placemarks_scripts' );
        }
    }
}


function add_admin_scripts( $hook ) {

    global $post;

   
}
add_action( 'admin_enqueue_scripts', 'add_admin_scripts', 10, 1 );




/* ==================================================================
 * No title? Set slug as ID to avoid "auto-draft"
 * ================================================================== */
add_filter('wp_insert_post_data','slug_placemarker',99,2);
function slug_placemarker($data, $postarr) {
	global $post;
	if ($post && $data['post_type'] == 'placemark') {
			$data['post_name'] = $post->ID; 
    } 
    return $data;
}

/* ==================================================================
 * Adds Custom META pannels
 * ================================================================== */
function add_placemarks_metaboxes() { 
    add_meta_box( 'custom_placemarks_place_metabox', 'Place', 'placemarker_place_metabox', 'placemark', 'normal' );
    add_meta_box( 'custom_placemarks_marker_metabox', 'Mark', 'placemarker_marker_metabox', 'placemark', 'normal' );
	
	// also remove slug meta
	remove_meta_box( 'slugdiv', 'placemark', 'normal' );
}


/* === Adds Custom META pannel placemarker_place_metabox === */
// define new custom meta (name, initial value, label title, tab index)
$placemarker_place_meta = array( "m01" => array( "name" => "placemarks-lat", 		"value" => "", 		"title" => "Latitude",			"tab" => ""),
								 "m02" => array( "name" => "placemarks-lng", 		"value" => "", 		"title" => "longitude",			"tab" => ""),	
								 "m03" => array( "name" => "placemarks-location", 	"value" => "", 		"title" => "Text description of where the marker is located",			"tab" => ""),
								 "m04" => array( "name" => "placemarks-locations", 	"value" => "", 		"title" => "Locations",			"tab" => "")	
					);
				
function placemarker_place_metabox(){
	global $post, $placemarker_place_meta, $placemarker_marker_meta, $placemarks_locations_json, $placemarks_types_json,     $placemarker_locations;
	$tab_index = 3000;
	foreach($placemarker_place_meta as &$meta){
		// is there a recorded value
		$meta_value = get_post_meta($post->ID, $meta['name'], true);
		if($meta_value != ""){ $meta['value'] = $meta_value; }
		// set tab index
		if($meta['tab'] == ""){ $meta['tab'] = $tab_index; }
		$tab_index++;
	}	
	
?>

    <script type="text/javascript">
		// set up js vars that need php	
		var placemarks_locations_json, placemarks_types_json, placemarks_place_meta, placemarks_marker_meta, placemarks_locations_meta, placemarks_nonce;
		placemarks_locations_json 	= <?php echo json_encode($placemarks_locations_json); ?>; 
		placemarks_types_json 		= <?php echo json_encode($placemarks_types_json); ?>; 
		placemarks_locations_meta 	= <?php echo json_encode(get_post_meta($post->ID, "placemarks-locations", false)); ?> ;		 // get saved locations
		placemarks_nonce 			= "<?php echo wp_create_nonce( plugin_basename(__FILE__) ); ?>"; 							// we'll also need this to have js set up inputs
		placemarks_place_meta 		= <?php echo json_encode($placemarker_place_meta); ?>; 
		placemarks_marker_meta 		= <?php echo json_encode($placemarker_marker_meta); ?>; 
	</script>
    
	<?php
	
	// Create inputs
	
	echo'<div id="map_canvas" style="width: 100%; height: 300px"></div>';
	echo'<input type="hidden" name="'.$placemarker_place_meta['m01']['name'].'_noncename" id="'.$placemarker_place_meta['m01']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<input type="hidden" name="'.$placemarker_place_meta['m02']['name'].'_noncename" id="'.$placemarker_place_meta['m02']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_place_meta['m01']['name'].'">'.$placemarker_place_meta['m01']['title'].' , '.$placemarker_place_meta['m02']['title'].':</label>';
	echo'<input type="text" id="'.$placemarker_place_meta['m01']['name'].'" name="'.$placemarker_place_meta['m01']['name'].'" value="'.esc_attr($placemarker_place_meta['m01']['value']).'" style="width:150px;" tabindex="'.$placemarker_place_meta['m01']['tab'].'" />';
	echo' , <input type="text" id="'.$placemarker_place_meta['m02']['name'].'" name="'.$placemarker_place_meta['m02']['name'].'" value="'.esc_attr($placemarker_place_meta['m02']['value']).'" style="width:150px;" tabindex="'.$placemarker_place_meta['m02']['tab'].'" />';
	echo' <input id="mapgps" class="button" type="button" value="Use current location" name="mapgps" /></p>';
	if($placemarks_locations_json){
		echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_place_meta['m04']['name'].'">Location:</label><div id="placemarks-location-selects"></div>';
	}
	echo'<input type="hidden" name="'.$placemarker_place_meta['m03']['name'].'_noncename" id="'.$placemarker_place_meta['m03']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_place_meta['m03']['name'].'">'.$placemarker_place_meta['m03']['title'].':</label>';
	echo'<textarea name="'.$placemarker_place_meta['m03']['name'].'" style="width:50%;" tabindex="'.$placemarker_place_meta['m03']['tab'].'">'.esc_attr($placemarker_place_meta['m03']['value']).'</textarea></p><p style="margin:-15px 0 0 150px; font-style:italic;  color:#777;">For example: "On the south wall, across from room CC123"</p>';
}

/* === Adds Custom META pannel placemarker_marker_metabox === */

// define new custom meta (name, initial value, label title, description, tab index)
$placemarker_marker_meta = array( "m01" => array( "name" => "placemarks-title", 		"value" => "", 		"title" => "Optional title",	"tab" => "" ),
								  "m02" => array( "name" => "placemarks-bubble", 		"value" => "", 		"title" => "Optional text",		"tab" => ""),
								  "m03" => array( "name" => "placemarks-type", 		"value" => "", 		"title" => "Mark type",			"tab" => ""),
								  "m04" => array( "name" => "placemarks-link", 		"value" => "", 		"title" => "Optional Link",		"tab" => "")
					);	
					
function placemarker_marker_metabox(){
	global $post, $placemarker_marker_meta, $placemarks_types_json;
	$tab_index = 4000;
	foreach($placemarker_marker_meta as &$meta){
		// is there a recorded value
		$meta_value = get_post_meta($post->ID, $meta['name'], true);
		if($meta_value != "") $meta['value'] = $meta_value;
		// set tab index
		if($meta['tab'] == "") $meta['tab'] = $tab_index;
		$tab_index++;
	}
	
	// Create inputs
	echo'<input type="hidden" name="'.$placemarker_marker_meta['m03']['name'].'_noncename" id="'.$placemarker_marker_meta['m03']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_marker_meta['m03']['name'].'">'.$placemarker_marker_meta['m03']['title'].':</label>';
	echo "<select id=\"{$placemarker_marker_meta['m03']['name']}\" name=\"{$placemarker_marker_meta['m03']['name']}\" title=\"{$placemarker_marker_meta['m03']['title']}\">";
	//echo "<option value=\"\">Default</option>";
	foreach ( $placemarks_types_json->types as $type ) {
		if($type->name == $placemarker_marker_meta['m03']['value']){
			echo "<option value=\"{$type->name}\" selected=\"selected\">{$type->name}</option>";
		}
		else{
		   echo "<option value=\"{$type->name}\">{$type->name}</option>";
		}
	}
	echo '</select><span id="placemark-marker-image"></span></p>';
	
	echo'<input type="hidden" name="'.$placemarker_marker_meta['m01']['name'].'_noncename" id="'.$placemarker_marker_meta['m01']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_marker_meta['m01']['name'].'">'.$placemarker_marker_meta['m01']['title'].':</label>';
	echo'<input type="text" name="'.$placemarker_marker_meta['m01']['name'].'" style="width:30%;" value="'.esc_attr($placemarker_marker_meta['m01']['value']).'" style="width:100px;" tabindex="'.$placemarker_marker_meta['m01']['tab'].'" /></p>';
	
	echo'<input type="hidden" name="'.$placemarker_marker_meta['m02']['name'].'_noncename" id="'.$placemarker_marker_meta['m02']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_marker_meta['m02']['name'].'">'.$placemarker_marker_meta['m02']['title'].':</label>';
	echo'<textarea name="'.$placemarker_marker_meta['m02']['name'].'" style="width:50%;" tabindex="'.$placemarker_marker_meta['m02']['tab'].'">'.esc_attr($placemarker_marker_meta['m02']['value']).'</textarea></p><p style="margin:-15px 0 0 150px; font-style:italic;  color:#777;">For example: "Keypad access code available at various locations, sign on door."</p>';

	echo'<input type="hidden" name="'.$placemarker_marker_meta['m04']['name'].'_noncename" id="'.$placemarker_marker_meta['m04']['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
	echo'<p><label style="color:#777; width:150px; display:block; float:left; clear:left;" for="'.$placemarker_marker_meta['m04']['name'].'">'.$placemarker_marker_meta['m04']['title'].':</label>';
	echo'<input type="text" name="'.$placemarker_marker_meta['m04']['name'].'" style="width:50%;" value="'.esc_attr($placemarker_marker_meta['m04']['value']).'" style="width:100px;" tabindex="'.$placemarker_marker_meta['m04']['tab'].'" /></p>';

}

// save all
function save_placemark_marker_metabox( $post_id ){
	global $post, $placemarker_place_meta, $placemarker_marker_meta;
	
	if ($_POST) {  
		// save basic place meta
		foreach($placemarker_marker_meta as $meta){
			// Verify
			//if ( !$_POST[$meta['name']] ) { return $post_id; }
			if ( !wp_verify_nonce( $_POST[$meta['name'].'_noncename'], plugin_basename(__FILE__) )){
				return $post_id;
			}
			if ( 'page' == $_POST['post_type'] ){
				if ( !current_user_can( 'edit_page', $post_id )) return $post_id;
			}
			else{
				if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
			}
			
			$data = trim($_POST[$meta['name']]); // trim any leading ws and escape
			
			if(get_post_meta($post_id, $meta['name']) == "") add_post_meta($post_id, $meta['name'], $data, true);
			elseif($data == "") delete_post_meta($post_id, $meta['name'], get_post_meta($post_id, $meta['name'], true));
			elseif($data != get_post_meta($post_id, $meta['name'], true)) update_post_meta($post_id, $meta['name'], $data);
			
		}
		
		// save "placemarks-locations-" data if available
		// do it this way to avoid select-name[] tomfoolery!
		$locations_key = "placemarks-locations";
		delete_post_meta($post_id, $locations_key); // start by removing all old
		foreach($_POST as $key => $val) {
			if(preg_match('/^placemarks-locations-\d+$/', $key)){
				// Verify
				if ( !wp_verify_nonce( $_POST[$key.'_noncename'], plugin_basename(__FILE__) )){
					return $post_id;
				}
				if ( 'page' == $_POST['post_type'] ){
					if ( !current_user_can( 'edit_page', $post_id )) return $post_id;
				}
				else{
					if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
				}
				
				$data = trim($_POST[$key]); // trim any leading ws and escape
				if($data != ""){ add_post_meta($post_id, $locations_key, $data, false); }
			}
	
		}
	
		//save marker meta
		foreach($placemarker_place_meta as $meta){
			// Verify
			if ( !wp_verify_nonce( $_POST[$meta['name'].'_noncename'], plugin_basename(__FILE__) )){
				return $post_id;
			}
			if ( 'page' == $_POST['post_type'] ){
				if ( !current_user_can( 'edit_page', $post_id )) return $post_id;
			}
			else{
				if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
			}
			
			$data = trim($_POST[$meta['name']]); // trim any leading ws and escape
			
			if(get_post_meta($post_id, $meta['name']) == "") add_post_meta($post_id, $meta['name'], $data, true);
			elseif($data == "") delete_post_meta($post_id, $meta['name'], get_post_meta($post_id, $meta['name'], true));
			elseif($data != get_post_meta($post_id, $meta['name'], true)) update_post_meta($post_id, $meta['name'], $data);
			
		}
	}
}
add_action('save_post', 'save_placemark_marker_metabox');


/* === Show these columns when editing post type: placemarker  === */
add_filter("manage_edit-placemark_columns", "placemark_columns");
function placemark_columns($columns){
		$columns = array(			
		
			"cb" => "<input type=\"checkbox\" />",  	// built-in
			"id" => "Placemark ID",					// custom 
			"marker" => "Type",							// custom 
			"locations" =>	"Locations",				// custom
			"description" => "Location Description",	// custom
			//"campus" => "Campus",						// custom 
			//"building" => "Building",					// custom 
			//"floor" => "Floor",							// custom 
			/*"latlng" => "Place",						// custom */
			"author" => "Author",						// built-in
			"date" => "Date"							// built-in
			
		);

		return $columns;
}
/* === Here we define what each custom edit column should do (all post types)  === */
add_action("manage_posts_custom_column",  "placemark_custom_columns");
function placemark_custom_columns($column){
		global $post,$placemarker_path, $placemarks_types_json;
		$custom = get_post_custom();
		switch ($column){
			case "id":
				echo edit_post_link($post->ID,'','',$post->ID);
				break;
			case "latlng":
				echo edit_post_link( $custom["placemarks-lat"][0].", ".$custom["placemarks-lng"][0],'','',$post->ID);
				$status = get_post_status($post->ID);
				if($status == "pending"){
					echo " - Pending";
				}
				echo "</strong>";
				break;	
			case "marker":
				// find icon
				foreach($placemarks_types_json->types as $type){
					if($type->name == $custom["placemarks-type"][0]){
						$src = $type->src;
						break;
					}
				}
				echo '<img src="'.$src.'" style="vertical-align:text-top;" alt="'.$custom["placemarks-type"][0].' Icon" /> '.$custom["placemarks-type"][0];
				break;
			case "description":
				echo isset($custom["placemarks-location"]) ? $custom["placemarks-location"][0] : "";
				break;			
			case "locations":
				echo implode(", ",$custom["placemarks-locations"]);
				/* I should really look up the real names!
				foreach($custom["placemarks-locations"] as $loc){
					echo $loc;
				}*/
				break;
			
		}
}

/* === Here we make them sortable  === */
add_filter( 'manage_edit-placemark_sortable_columns', 'placemark_sortable_columns' );  
function placemark_sortable_columns( $columns ) {  
		$columns = array(			
			"id" => "id",								// custom 
			"marker" => "marker",						// custom 
			"campus" => "campus",						// custom 
			"building" => "building",					// custom 
			"floor" => "floor",							// custom 
		);
		  
        //To make a column 'un-sortable' remove it from the array  
        //unset($columns['date']);  
      
        return $columns;   
    }  
add_action( 'pre_get_posts', 'placemarker_orderby' );
function placemarker_orderby( $query ) {
	if( ! is_admin() )
		return;

	$orderby = $query->get( 'orderby');

	switch ($orderby){
			case "marker":
				$query->set('meta_key','placemarks-type');
				$query->set('orderby','meta_value'); // alpha
				break;
			case "campus":
				$query->set('meta_key','placemarks-campus');
				$query->set('orderby','meta_value'); // alpha
				break;
			case "building":
				$query->set('meta_key','placemarks-building');
				$query->set('orderby','meta_value'); // alpha
				break;
			case "floor":
				$query->set('meta_key','placemarks-floor');
				$query->set('orderby','meta_value_num'); // num
				break;
			case "author":
				$query->set('meta_key','placemarks-building');
				$query->set('orderby','author'); // author
				break;
	
	}
}

/* ==================================================================
 * End admin only
 * ================================================================== */
endif;

if (!is_admin()) :


/* ==================================================================
 * Register style sheets and scripts for regular posts area
 * ================================================================== */
add_action( 'init', 'placemarks_non_admin_scripts_and_styles' );
function placemarks_non_admin_scripts_and_styles() {
	wp_register_style( 'placemarks_style', plugins_url( 'placemarks/style.css' ) );
	wp_enqueue_style( 'placemarks_style' );
	wp_register_script('placemarks_google_maps_v3', 'http'. ( is_ssl() ? 's' : '' ) .'://maps.google.com/maps/api/js?sensor=false');
	wp_enqueue_script( 'placemarks_google_maps_v3' );
	wp_register_script('placemarks_scripts', plugins_url( 'placemarks/scripts-non-admin.js'),array(), false, true );
	wp_enqueue_script( 'placemarks_scripts' );
}

// [placemarks types="type name" lat=# lng =# zoom=# width="" height="" alt=tru/false]  ToDo: locations="location slug" 
function placemarks_shortcode( $atts ) {
	global $post,$wpdb,$placemarks_types_json,$placemarks_locations_json;
	$temp_post = $post;
	$can_edit = current_user_can('edit_posts') ? 1:0; // can the user edit placemarks?
	
	extract( shortcode_atts( array(
		'types' 	=> '',
		//'locations'	=> '',
		'lat'		=> '',
		'lng'		=> '',
		'zoom'		=> '',
		'width'		=> '100%',
		'height'	=> '400px',
		'alt'		=> 'true'
	), $atts ) );
		
	$alt = (strtoupper(trim($alt)) === strtoupper("true")) ? TRUE : FALSE; // convert alt to bool
	$out ="";
	$alt_text ="";
	$perpage 	= 9999;	// get them all

	$types_array = array_map('trim', explode(',',$types)); // create trimmed array
	
	if(!$types){  	// all
		$placmarks_query = new WP_Query(array(	
											'post_type'=>'placemark',
											'posts_per_page'=>$perpage,
											'post_status' => 'publish'
											));
	}
	else{ 			// just some
		$placmarks_query = new WP_Query(array(	
											'post_type'=>'placemark',
											'posts_per_page'=>$perpage,
											'post_status' => 'publish',
											'meta_query' => array(
												array(
													'key' => 'placemarks-type',
													'value' => $types_array,
													'compare' => 'IN'
												)/*,
												array(
													'key' => 'pcc-placemarker-building',
													'value' => $building,
												),
												array(
													'key' => 'pcc-placemarker-floor',
													'value' => $floor,
												)*/
											)
									));				
	}
				
			  $z = 100;
		      while ($placmarks_query->have_posts()) : $placmarks_query->the_post(); 
			  
		  		// what we know
				$p_id = 		$post->ID;
				$p_lat = 		attribute_escape( get_post_meta($p_id,"placemarks-lat",true));
				$p_lng = 		attribute_escape( get_post_meta($p_id,"placemarks-lng",true));
				$p_location = 	attribute_escape( get_post_meta($p_id,"placemarks-location",true));
				$p_locations = 	attribute_escape(  implode(", ", get_post_meta($p_id,"placemarks-locations",false)));  // make list for now
				$p_title = 		attribute_escape( get_post_meta($p_id,"placemarks-title",true));
				$p_bubble = 	attribute_escape( get_post_meta($p_id,"placemarks-bubble",true));
				$p_type = 		attribute_escape( get_post_meta($p_id,"placemarks-type",true));
				$p_link = 		attribute_escape( get_post_meta($p_id,"placemarks-link",true));
				
			
				
				// which title to use
				$p_title = $p_title!="" ? $p_title : $p_type; 
				
				// edit link?
				if($can_edit){
					$p_title .= ' | <a href="'.admin_url()."post.php?post=$p_id&action=edit".'">Edit</a>'; 
				}
				
				// icon
				$p_icon = "";
				foreach($placemarks_types_json->types as $t){
					if($t->name == $p_type){
						$p_icon  = $t->src;
						break;
					}
				}
				
				// Data for the markers [z-index, lat, lng, title, content, icon scr, link href ] 
				$out .= "\n\t\t [$z, $p_lat, $p_lng, '$p_title', '$p_bubble', '$p_icon', '$p_link'],";
				if($alt){	
					$alt_text .= "<dt><img src=\"$p_icon\"/> $p_title<dt><dd><em>Location description:</em> $p_location</dd><dd>$p_bubble";
					if($p_link){
						$alt_text .= "<p><a href=\"$p_link\">Learn more ...</a></p>";
					}
					$alt_text .= "</dd>";
				}
				$z++;
		      endwhile;
			  			  
			  $post = $temp_post;  // back to your regularly scheduled program
?>

    <script type="text/javascript">
		// set up js vars that need php	
		var placemarks_locations_json, placemarks_types_json, placemarks_marker_array, default_center, default_zoom;
		placemarks_locations_json 	= <?php echo json_encode($placemarks_locations_json); ?>; 
		placemarks_types_json 		= <?php echo json_encode($placemarks_types_json); ?>; 
		placemarks_marker_array = [ <?php echo substr($out, 0, -1); ?> ];
		<?php
		if($lat && $lng){
			echo "default_center = new google.maps.LatLng($lat,$lng);";
		}
		if($zoom){
			echo "default_zoom = $zoom;";
		}
		?>
	</script>
    
	<?php	
	
	return "<div class=\"placemarks\"/><div id=\"map_canvas\" style=\"width:$width; height:$height;\"></div><dl class=\"placemarks-alt\">$alt_text</dl></div>";
}
add_shortcode( 'placemarks', 'placemarks_shortcode' );


endif;
?>