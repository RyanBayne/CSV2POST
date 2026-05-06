<?php  
/** 
 * Configuration for CSV 2 POST. Created September 2015 after
 * scheduling and automation system improved.
 * 
 * @package CSV 2 POST
 * @author Ryan Bayne   
 * @version 1.0.
 */

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
                                               
class CSV2POST_Configuration {
  
    // decrease or increase based on page name, only needs changed once
    public $loadsubstr = 8;
    
    /**
    * Plugins main actions.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function actions() {
        // add_action() controller
        // Format: array( event | function in this class(in an array if optional arguments are needed) | loading circumstances)
        // Other class requiring WordPress hooks start here also, with a method in this main class that calls one or more methods in one or many classes
        // create a method in this class for each hook required plugin wide
        return array( 
            // WTG own security gets applied to all POST and GET requests
            array( 'admin_init',                     'process_admin_POST_GET',                                 'all' ),

            // during the posts loop, checks for CSV 2 POST created posts and updates the post when applicable
            array( 'the_posts',                      'systematicpostupdate',                                   'systematicpostupdating' ),
                        
            // plugins main menu (not in-page tab menu)
            array( 'admin_menu',                     'admin_menu',                                             'all' ),
            
            // this method adds callbacks for specific admin pages, add callbacks to the method
            array( 'admin_init',                     'add_adminpage_actions',                                  'all' ), 
            
            // adds widgets to the dashboard, WTG plugins can add the forms for any view to the dashboard
            array( 'wp_dashboard_setup',             'add_dashboard_widgets',                                  'all' ),

            // adds script for media button
            array( 'admin_footer',                   'pluginmediabutton_popup',                                'pluginscreens' ),
            // adds the HTML media button 
            array( 'media_buttons_context',          'pluginmediabutton_button',                               'pluginscreens' ),
            array( 'admin_enqueue_scripts',          'plugin_admin_enqueue_scripts',                           'pluginscreens' ),
            array( 'admin_enqueue_scripts',          'plugin_admin_register_styles',                           'pluginscreens' ),
            array( 'admin_print_styles',             'plugin_admin_enqueue_styles',                            'pluginscreens' ),
            array( 'admin_notices',                  'admin_notices',                                          'admin_notices' ),
            array( 'upgrader_process_complete',      'complete_plugin_update',                                 'adminpages' ),
            array( 'wp_before_admin_bar_render',     array('toolbars',999),                                    'pluginscreens' ),
            array( 'init',                           'debugmode',                                              'administrator' ),
            // AUTOMATION AND SCHEDULING
            array( 'init',                           'webtechglobal_hourly_cron_function',                    'cron'  ),          
            array( 'admin_init',                     'administrator_triggered_automation',                    'administrator' ),                 
        );    
    }

    /**
    * Array of filters to be used during __construct of main class.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.2
    */
    public function filters() {
        return array(
            array( 'set-screen-option',                     array( 'set_screen', 1, 3),           'all' ),
            array( 'plugin_action_links_' . plugin_basename( CSV2POST_DIR_PATH . 'csv2post.php' ), array( 'plugin_action_links', 1, 3),  'all' ),
        );    
    }  

    /**
    * Error display and debugging 
    * 
    * When request will display maximum php errors including WordPress errors 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 0.1
    * 
    * @todo is this function in use? It may be in another file.
    */
    public function debugmode() {

        $debug_status = get_option( 'webtechglobal_displayerrors' );
        if( !$debug_status ){ return false; }

        // times when this error display is normally not  required
        if ( ( 'wp-login.php' === basename( $_SERVER['SCRIPT_FILENAME'] ) )
                || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
                || ( defined( 'DOING_CRON' ) && DOING_CRON )
                || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
                    return;
        }
                
        global $wpdb;
        
        // package
        if(!defined( "WEBTECHGLOBAL_DEBUG_DISPLAY") ){define( "WEBTECHGLOBAL_DEBUG_DISPLAY", true);}
        
        // standard php
        ini_set( 'display_errors',1);
        error_reporting(E_ALL); 
             
        // WordPress core
        if(!defined( "WP_DEBUG_DISPLAY" ) ){define( "WP_DEBUG_DISPLAY", true );}
        if(!defined( "WP_DEBUG_LOG") ){define( "WP_DEBUG_LOG", true);}
        //add_action( 'all', create_function( '', 'var_dump( current_filter() );' ) );
        //define( 'SAVEQUERIES', true );
        //define( 'SCRIPT_DEBUG', true );
        $wpdb->show_errors();
        $wpdb->print_error();
   }

    /**
    * Create a new instance of the $class, which is stored in $file in the $folder subfolder
    * of the plugin's directory.
    * 
    * One bad thing about using this is suggestive code does not work on the object that is returned
    * making development a little more difficult. This behaviour is experienced in phpEd 
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.0.1
    *
    * @param string $class Name of the class
    * @param string $file Name of the PHP file with the class
    * @param string $folder Name of the folder with $class's $file
    * @param mixed $params (optional) Parameters that are passed to the constructor of $class
    * @return object Initialized instance of the class
    */
    public static function load_class( $class, $file, $folder, $params = null ) {
        $class = apply_filters( 'csv2post_load_class_name', $class );
        if ( ! class_exists( $class ) ) {   
            self::load_file( $file, $folder );
        }
        
        // we can avoid creating a new object, we can use "new" after the load_class() line
        // that way functions in the lass are available in code suggestion
        if( is_array( $params ) && in_array( 'noreturn', $params ) ){
            return true;   
        }
        
        $the_class = new $class( $params );
        return $the_class;
    }   

    /**
     * Load a file with require_once(), after running it through a filter
     * 
     * @param string $file Name of the PHP file with the class
     * @param string $folder Name of the folder with $class's $file
     */
    public static function load_file( $file, $folder ) {   
        $full_path = CSV2POST_ABSPATH . $folder . '/' . $file;
        $full_path = apply_filters( 'csv2post_load_file_full_path', $full_path, $file, $folder );
        if ( $full_path ) {   
            require_once $full_path;
        }
    }  
    
}
?>
