<?php
class MySettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Placemarks', 
            'manage_options', 
            'placemarks-setting', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'placemarks_options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Placemarks Settings</h2>           
            <form action="options.php" method="post">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'placemarks_group' );   
                do_settings_sections( 'placemarks-setting' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'placemarks_group', // Option group
            'placemarks_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'My Custom Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'placemarks-setting' // Page
        );  
  	    /*add_settings_field(
            'title', 
            'Title', 
            array( $this, 'title_callback' ), 
            'placemarks-setting', 
            'setting_section_id'
        );*/  
        add_settings_field(
            'placemarks_types_json', // ID
            'Marker Types (JSON)', // Title 
            array( $this, 'placemarks_types_callback' ), // Callback
            'placemarks-setting', // Page
            'setting_section_id' // Section           
        );   
		add_settings_field(
            'placemarks_locations_json', // ID
            'Locations (JSON)', // Title 
            array( $this, 'placemarks_locations_callback' ), // Callback
            'placemarks-setting', // Page
            'setting_section_id' // Section           
        );       

        add_settings_section(
            'setting_thanks_id', // ID
            'Thanks', // Title
            array( $this, 'print_thanks_info' ), // Callback
            'placemarks-setting' // Page
        );   
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        if( !is_numeric( $input['id_number'] ) )
            $input['id_number'] = '';  

        if( !empty( $input['title'] ) )
            $input['title'] = sanitize_text_field( $input['title'] );

        return $input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }
	
	 /** 
     * Print the thanks text
     */
    public function print_thanks_info()
    {
        print 'Special thanks to <a href="http://p.yusukekamiyamane.com/" target="_blank">Yusuke Kamiyamane</a> for the custom post type icons!';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function placemarks_types_callback()
    {
        printf(
            '<textarea id="placemarks_types_json" name="placemarks_options[placemarks_types_json]" style="width:100%%;">%s</textarea>',
            esc_attr( $this->options['placemarks_types_json'])
        );
    }
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function placemarks_locations_callback()
    {
        printf(
            '<textarea id="placemarks_locations_json" name="placemarks_options[placemarks_locations_json]" style="width:100%%;">%s</textarea>',
            esc_attr( $this->options['placemarks_locations_json'])
        );
    }
	    /** 
     * Get the settings option array and print one of its values
     */
    public function title_callback()
    {
        printf(
            '<input type="text" id="title" name="placemarks_options[title]" value="%s" />',
            esc_attr( $this->options['title'])
        );
    }
}

if( is_admin() )
    $my_settings_page = new MySettingsPage();
	
	?>