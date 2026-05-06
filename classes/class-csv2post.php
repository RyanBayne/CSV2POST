<?php  
/** 
 * This file contains multiple classes.
 * 
 * The core WTG plugin class and other main class functions for CSV 2 POST WordPress plugin 
 * 
 * @package CSV 2 POST
 * @author Ryan Bayne   
 * @since 8.0.0
 */

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
                                               
class CSV2POST extends CSV2POST_Configuration {
    
    use CSV2POST_DBTrait, CSV2POST_OptionsTrait;
    
    /**
     * Page hooks (i.e. names) WordPress uses for the CSV2POST admin screens,
     * populated in add_admin_menu_entry()
     *
     * @since 8.1.3
     *
     * @var array
     */
    protected $page_hooks = array();
    
    /**
     * CSV2POST version
     *
     * Increases everytime the plugin changes
     *
     * @since 8.1.3
     *
     * @const string
     */
    const version = '8.2.3';
    
    public static function init() {
    	global $CSV2POST_Class;
        $class = __CLASS__;
        $CSV2POST_Class = new $class;
    }
    
    /**
    * @version 1.0 
    */
    public function __construct() {
        global $csv2post_settings;

        // load class used at all times
        $this->CONFIG = self::load_class( 'CSV2POST_Configuration', 'class-configuration.php', 'classes' );
        // TODO: remove "DB" as the class is now in use as trait.
        $this->DB = self::load_class( 'CSV2POST_DB', 'class-wpdb.php', 'classes' );
        $this->PHP = self::load_class( 'CSV2POST_PHP', 'class-phplibrary.php', 'classes' );
        $this->Install = self::load_class( 'CSV2POST_Install', 'class-install.php', 'classes' );
        $this->Files = self::load_class( 'CSV2POST_Files', 'class-files.php', 'classes' );
        $this->AUTO = self::load_class( 'CSV2POST_Automation', 'class-automation.php', 'classes' );
        $this->Services = \CSV2POST\ServiceContainer::instance();
  
        $csv2post_settings = self::adminsettings();
  
        // add actions and filters to WP very early
        $this->add_actions(self::actions());
        $this->add_filters(self::filters());

        // Register custom post types.
        self::custom_post_types();
        
        // Register the plugins own schema.
        $install = new CSV2POST_Install(); 
        $install->register_schema();
                
        if( is_admin() )
        {
            // admin globals 
            global $eci_notice_array;
            
            $eci_notice_array = array();// set notice array for storing new notices in (not persistent notices)
            
            // load class used from admin only                   
            $this->UI = self::load_class( 'CSV2POST_UI', 'class-ui.php', 'classes' );
            $this->Helparray = self::load_class( 'CSV2POST_Help', 'class-help.php', 'classes' );
            $this->TABMENU = self::load_class( "CSV2POST_TabMenu", "class-pluginmenu.php", 'classes','pluginmenu' );
            
            // Register modern diagnostics page
            add_action( 'admin_menu', array( $this->diagnostics_page(), 'register' ) );            
        }
      
        /**
        * load current project - new approach to begin reducing the number of times
        * project settings are queried
        * 
        * @since 8.1.3
        */
        $this->current_project_object = false;
        $this->current_project_settings = false;   
        if( isset( $this->settings['currentproject'] ) && $this->settings['currentproject'] !== false ){
            
            $this->current_project_object = $this->DB->get_project( $this->settings['currentproject'] ); 
            
            if( !$this->current_project_object ) {    
                $this->current_project_settings = false;
            } else {          
                $this->current_project_settings = maybe_unserialize( $this->current_project_object->projectsettings );
            }
        }     
    }

    /**
    * Register custom css on admin only.
    * 
    * Must be done before enqueue styles and printing them.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.32
    * @version 1.2
    */
    public function plugin_admin_register_styles() {
        wp_register_style( 'csv2post_css_notification',plugins_url( 'csv-2-post/css/notifications.css' ), array(), '1.0.0', 'screen' );
        wp_register_style( 'csv2post_css_admin',plugins_url( 'csv-2-post/css/admin.css' ), __FILE__);          
    
        // jQuery UI
        wp_register_style( 'csv2post_css_jqueryui',plugins_url( 'csv-2-post/css/jqueryui/jquery-ui.min.css' ), __FILE__ );          
        wp_register_style( 'csv2post_css_jqueryuisructure',plugins_url( 'csv-2-post/css/jqueryui/jquery-ui.structure.min.css' ), __FILE__);          
        wp_register_style( 'csv2post_css_jqueryuitheme',plugins_url( 'csv-2-post/css/jqueryui/jquery-ui.theme.min.css' ), __FILE__);          
        wp_register_style( 'csv2post_css_jqueryuidatatimepicker',plugins_url( 'csv-2-post/css/jqueryui/jquery.datetimepicker.min.css' ), __FILE__);              
    }
    
    /**
    * print admin only .css - the css must be registered first
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.2
    */
    public function plugin_admin_enqueue_styles() {
        wp_enqueue_style( 'csv2post_css_notification' );  
        wp_enqueue_style( 'csv2post_css_admin' );
        wp_enqueue_style( 'wp-pointer' );  
        
        // jQuery UI            
        wp_enqueue_style( 'csv2post_css_jqueryui' );               
        wp_enqueue_style( 'csv2post_css_jqueryuisructure' );               
        wp_enqueue_style( 'csv2post_css_jqueryuitheme' );               
        wp_enqueue_style( 'csv2post_css_jqueryuidatatimepicker' );                     
    }    
    
    /**
    * queues .js that is registered already
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.2
    */
    public function plugin_admin_enqueue_scripts() {
        wp_enqueue_script( 'wp-pointer' ); 
        wp_enqueue_script( 'jquery-ui-sortable' );   
        wp_enqueue_script( 'jquery-ui-selectable' ); 
        wp_enqueue_script( 'jquery-ui-datepicker' );                            
        wp_enqueue_script( 'jquery-ui-datetimepicker', plugins_url( 'csv-2-post/js/datetimepicker/jquery.datetimepicker.full.min.js' ), __FILE__ );          
    }    

    /**
     * Enqueue a CSS file with ability to switch from .min for debug
     *
     * @since 8.1.3
     *
     * @param string $name Name of the CSS file, without extension(s)
     * @param array $dependencies List of names of CSS stylesheets that this stylesheet depends on, and which need to be included before this one
     */
    public function enqueue_style( $name, array $dependencies = array() ) {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $css_file = "css/{$name}{$suffix}.css";
        $css_url = plugins_url( $css_file, CSV2POST__FILE__ );
        wp_enqueue_style( "csv2post-{$name}", $css_url, $dependencies, CSV2POST_VERSION );
    }
    
    /**
     * Enqueue a JavaScript file, can switch from .min for debug,
     * possibility with dependencies and extra information
     *
     * @since 8.1.3
     *
     * @param string $name Name of the JS file, without extension(s)
     * @param array $dependencies List of names of JS scripts that this script depends on, and which need to be included before this one
     * @param bool|array $localize_script (optional) An array with strings that gets transformed into a JS object and is added to the page before the script is included
     * @param bool $force_minified Always load the minified version, regardless of SCRIPT_DEBUG constant value
     */
    public function enqueue_script( $name, array $dependencies = array(), $localize_script = false, $force_minified = false ) {
        $suffix = ( ! $force_minified && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $js_file = "js/{$name}{$suffix}.js";
        $js_url = plugins_url( $js_file, CSV2POST__FILE__ );
        wp_enqueue_script( "csv2post-{$name}", $js_url, $dependencies, CSV2POST_VERSION, true );
    }  
        
    /**
    * returns the CSV2POST_WPMain class object already created in this CSV2POST class
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function class_wpmain() {
        return $this->wpmain;
    }

    /**
    * Return the modernization service container.
    *
    * @return \CSV2POST\ServiceContainer
    */
    public function services() {
        return $this->Services;
    }

    /**
    * Return the guided setup flow that currently acts as the import wizard.
    *
    * @return array
    */
    public function get_setup_wizard_steps() {
        return $this->services()->wizard_flow()->get_setup_steps();
    }

    /**
    * Return the project diagnostics service (read-only inspection).
    *
    * @return \CSV2POST\Services\ProjectDiagnostics
    */
    public function diagnostics() {
        return $this->services()->project_diagnostics();
    }

    /**
    * Return the diagnostics admin page handler.
    *
    * @return \CSV2POST\Admin\ProjectDiagnosticsPage
    */
    public function diagnostics_page() {
        return $this->services()->diagnostics_page();
    }

    /**
    * Return the schema builder service.
    *
    * @return \CSV2POST\Services\SchemaBuilder
    */
    public function schema_builder() {
        return $this->services()->schema_builder();
    }

    /**
    * Return the import progress tracker service.
    *
    * @return \CSV2POST\Services\ImportProgress
    */
    public function import_progress() {
        return $this->services()->import_progress();
    }

    /**  
     * Set up actions for each page
     *
     * @since 8.1.3
     */
    public function add_adminpage_actions() {
        // register callbacks to trigger load behavior for admin pages
        foreach ( $this->page_hooks as $page_hook ) {
            add_action( "load-{$page_hook}", array( $this, 'load_admin_page' ) );
        }
    }
    
    /**
     * Render the view that has been initialized in load_admin_page() (called by WordPress when the actual page content is needed)
     *
     * @since 8.1.3
     */
    public function show_admin_page() {   
        $this->view->render();
    }    
    
    /**
     * Create a new instance of the $view, which is stored in the "views" subfolder, and set it up with $data
     * 
     * Requires a main view file to be stored in the "views" folder, unlike the original view approach.
     * 
     * Do not move this to another file not even interface classes 
     *
     * @since 8.1.3
     * @uses load_class()
     *
     * @param string $view Name of the view to load
     * @param array $data (optional) Parameters/PHP variables that shall be available to the view
     * @return object Instance of the initialized view, already set up, just needs to be render()ed
     */
    public static function load_draggableboxes_view( $page_slug, array $data = array() ) {
        // include the view class
        require_once( CSV2POST_ABSPATH . 'classes/class-view.php' );
        
        // make first letter uppercase for a better looking naming pattern
        $ucview = ucfirst( $page_slug );// this is page name 
        
        // get the file name using $page and $tab_number
        $dir = 'views';
        
        // include the view file and run the class in that file                                
        $the_view = self::load_class( "CSV2POST_{$ucview}_View", "{$page_slug}.php", $dir );
                       
        $the_view->setup( $page_slug , $data );
        
        return $the_view;
    }

    /**
     * Generate the complete nonce string, from the nonce base, the action and an item
     *
     * @since 8.1.3
     *
     * @param string $action Action for which the nonce is needed
     * @param string|bool $item (optional) Item for which the action will be performed, like "table"
     * @return string The resulting nonce string
     */
    public static function nonce( $action, $item = false ) {
        $nonce = "csv2post_{$action}";
        if ( $item ) {
            $nonce .= "_{$item}";
        }
        return $nonce;
    }

    /**
    * Administrator Triggered Automation.
    * 
    * This is an easy way to run tasks normally scheduled but with a user
    * who is monitoring the blog and can respond to any problems or
    * evidence that an automated task is over demanding and its activation
    * by CRON needs to be reviewed.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.0
    * @version 1.0
    * 
    * @todo Add field for user to set a delay.
    * @todo Add options fields for activating individual functions within this method.
    */
    public function administrator_triggered_automation() {

    	// Has administration triggered automation been activated?
    	if( !get_option( 'csv2post_adm_trig_auto') ) 
    	{        
			return false;// User has not activated admin triggered automation.	
    	}
        
        // Get the time of the last admin triggered event, ensure 15 minute delay. 
        $last_auto_time = get_option( 'webtechglobal_autoadmin_lasttime');
        
        // Might need to initiate the value.
        if( !$last_auto_time )
        {      
			update_option( 'webtechglobal_autoadmin_lasttime', time() );
			return;
        }
        
        $next_earliest_time = $last_auto_time + 900;
        if( $next_earliest_time > time() )
        {
        	
		    $message = sprintf( __( 'Administrator triggered automation did not run as it has 
        				run within the last 15 minutes. The last time administration
        				automation was run was at %s and it cannot run again until %s or
        				later. The current time is %s', 'csv2post' ), 
        				date( "Y-m-d H:i:s", $last_auto_time ),
        				date( "Y-m-d H:i:s", $next_earliest_time ),
        				date( "Y-m-d H:i:s", time() )
		    );
        				
        	csv2post_trace_primary( 
        		'admintrigauto', 
        		$message, 
        		array(), 
        		false, 
        		false, 
        		true 
        	);
        	
			return false;// 15 minutes have not passed since the last event.
        }
        
        // Update the last auto admin time. 
        update_option( 'webtechglobal_autoadmin_lasttime', time() );
    }
                  
    /**
     * Begin render of admin screen
     * 1. determining the current action
     * 2. load necessary data for the view
     * 3. initialize the view
     * 
     * @uses load_draggableboxes_view() which includes class-view.php
     * 
     * @author Ryan Bayne
     * @package CSV 2 POST
     * @since 8.1.3
     * @version 1.0.1
     */
    public function load_admin_page() {        
        // load tab menu class which contains help content array
        $CSV2POST_TabMenu = self::load_class( 'CSV2POST_TabMenu', 'class-pluginmenu.php', 'classes' );
        
        // call the menu_array
        $menu_array = $CSV2POST_TabMenu->menu_array();        

        // remove "csv2post_" from page, resulting string then matches various 
        // uses of the page name in view files.
        $page = 'main';
        if( isset( $_GET['page'] ) && $_GET['page'] !== 'csv2post' ){    
            $page = substr( $_GET['page'], count('csv2post') + $this->loadsubstr );
        }

        // pre-define data for passing to views
        $data = array( 'datatest' => 'A value for testing' );

        // depending on page load extra data
        switch ( $page ) {
            case 'dataimport':
                break;           
            case 'updateplugin':
                break;
            case 'categories':
                break;
            case 'meta':
                break;
            case 'design':
                break;
            case 'postcreation':
                break;            

        }
          
        // prepare and initialize draggable panel view for prepared pages
        // if this method is not called the plugin uses the old view method
        $this->view = $this->load_draggableboxes_view( $page, $data );
    }   
                   
    public function add_actions( $actions ) {          
        foreach( $actions as $actionArray ) {        
            list( $action, $details, $whenToLoad) = $actionArray;
                                   
            if(!$this->filteraction_should_beloaded( $whenToLoad) ) {      
                continue;
            }
                 
            switch(count( $details) ) {         
                case 3:
                    add_action( $action, array( $this, $details[0] ), $details[1], $details[2] );     
                break;
                case 2:
                    add_action( $action, array( $this, $details[0] ), $details[1] );   
                break;
                case 1:
                default:
                    add_action( $action, array( $this, $details) );
            }
        }    
    }
    
    public function add_filters( $filters ) {
        foreach( $filters as $filterArray ) {
            list( $filter, $details, $whenToLoad) = $filterArray;
                           
            if(!$this->filteraction_should_beloaded( $whenToLoad) ) {
                continue;
            }
            
            switch(count( $details) ) {
                case 3:
                    add_filter( $filter, array( $this, $details[0] ), $details[1], $details[2] );
                break;
                case 2:
                    add_filter( $filter, array( $this, $details[0] ), $details[1] );
                break;
                case 1:
                default:
                    add_filter( $filter, array( $this, $details) );
            }
        }    
    }    
    
    /**            
    * Should the giving action or filter be loaded?
    * 1. we can add security and check settings per case, the goal is to load on specific pages/areas
    * 2. each case is a section and we use this approach to load action or filter for specific section
    * 3. In early development all sections are loaded, this function is prep for a modular plugin
    * 4. addons will require core functions like this to be updated rather than me writing dynamic functions for any possible addons
    *  
    * @param mixed $whenToLoad
    */
    private function filteraction_should_beloaded( $whenToLoad) {
        $csv2post_settings = $this->adminsettings();
        switch( $whenToLoad) {
            case 'all':    
                return true;
            break;
            case 'adminpages':
                // load when logged into admin and on any admin page
                if( is_admin() ){return true;}
                return false;    
            break;
            case 'pluginscreens':
       
                // load when on a CSV 2 POST admin screen
                if( isset( $_GET['page'] ) && strstr( $_GET['page'], 'csv2post' ) ){return true;}
                
                return false;    
            break;            
            case 'pluginanddashboard':

                if( self::is_dashboard() ) {
                    return true;    
                }

                if( isset( $_GET['page'] ) && strstr( $_GET['page'], 'csv2post' ) ){
                    return true;
                }
                
                return false;    
            break;
            case 'projects':
                return true;    
            break;            
            case 'systematicpostupdating':  
                if(!isset( $csv2post_settings['standardsettings']['systematicpostupdating'] ) || $csv2post_settings['standardsettings']['systematicpostupdating'] != 'enabled' ){
                    return false;    
                }      
                return true;
            break;
            case 'admin_notices':                         

                if( self::is_dashboard() ) {
                    return true;    
                }
                                                           
                if( isset( $_GET['page'] ) && strstr( $_GET['page'], 'csv2post' ) ){
                    return true;
                }
                                                                                                   
                return false;
            break;
            case 'administrator':

                require_once( ABSPATH . '/wp-load.php' );
                if( !function_exists( 'wp_get_current_user' ) ){
                    require_once( ABSPATH . WPINC . '/formatting.php' );
                    require_once( ABSPATH . WPINC . '/capabilities.php' );
                    require_once( ABSPATH . WPINC . '/user.php' );
                    require_once( ABSPATH . WPINC . '/meta.php' );
                    require_once( ABSPATH . WPINC . '/pluggable.php' );
                    wp_cookie_constants( );
                }
            
                if( is_user_logged_in() ) { return true; }
                if( current_user_can( 'activate_plugins' ) ) { return true; }
                
                return false;
            break;                
        }

        return true;
    }   
    
    /**
    * Determine if on the dashboard page. 
    * 
    * $current_screen is not set early enough for calling in some actions. So use this
    * function instead.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function is_dashboard() {
        global $pagenow;
        if( isset( $pagenow ) && $pagenow == 'index.php' ) { return true; }
        return strstr( $this->PHP->currenturl(), 'wp-admin/index.php' );
    }
    
    /**
    * Admin toolbars. All methods called should contain their own security
    * incase they are also called elsewhere.
    * 
    * Security is performed when deciding if the hook should be loaded or not.
    * Currently (by default) is_user_logged_in() requires true. Then within this
    * function we apply more security to the more sensitive menus.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.3
    */
    function toolbars() {  
        global $wp_admin_bar;
        
        $user_id = get_current_user_id();

    	// Developers (they have some extra capabilities)
    	if( user_can( $user_id, 'developerfeatures' ) ) {      
			self::developer_toolbar();			
		}
    	
    	// Administrators
    	if( user_can( $user_id, 'activate_plugins' ) ) {      
    		// Display developer menu for keyholder
    		if( $user_id === 1 )
    		{      
				self::developer_toolbar();
    		}    	
		}

        // Editors     
        // Authors     
        // Subscribers        
    }
 
    /**
    * Returns an array of WordPress core capabilities.
    * 
    * @author Ryan R. Bayne
    * @since 0.0.1
    * @version 1.2
    */
    public function capabilities() {
        global $wp_roles; 
        $capabilities_array = array();
        foreach( $wp_roles->roles as $role => $role_array ) { 
            
            if( !is_array( $role_array['capabilities'] ) ) { continue; }
            
            $capabilities_array = array_merge( $capabilities_array, $role_array['capabilities'] );    
        }
        return $capabilities_array;
    }
     
    /**
    * The developer toolbar items for admin side only.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * 
    * @version 1.6
    */
    function developer_toolbar() {
    	// This toolbar is for the developer or main key holder.
    	if( !user_can( get_current_user_id(), 'developerfeatures' ) && get_current_user_id() !== 1 ) {
    		return false;
		}
		    	
        global $wp_admin_bar;
               
        // Top Level/Level One
        $args = array(
            'id'     => 'csv2post-toolbarmenu-developers',
            'title'  => __( 'CSV 2 POST Developers', 'text_domain' ),          
        );
        $wp_admin_bar->add_menu( $args );
        
	        // Group - Debug Tools
	        $args = array(
	            'id'     => 'csv2post-toolbarmenu-debugtools',
	            'parent' => 'csv2post-toolbarmenu-developers',
	            'title'  => __( 'Debug Tools', 'text_domain' ), 
	            'meta'   => array( 'class' => 'first-toolbar-group' )         
	        );        
	        $wp_admin_bar->add_menu( $args );

		        // error display switch        
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'debugmodeswitch'  . '', 'debugmodeswitch' );
		        $debug_status = get_option( 'webtechglobal_displayerrors' );
		        if($debug_status){
		            $error_display_title = __( 'Hide Errors', 'csv2post' );
		        } else {
		            $error_display_title = __( 'Display Errors', 'csv2post' );
		        }
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-errordisplay',
		            'parent' => 'csv2post-toolbarmenu-debugtools',
		            'title'  => $error_display_title,
		            'href'   => $href,            
		        );
        	    
        	    $wp_admin_bar->add_menu( $args );
                            
		        // $_POST data display switch        
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'postdumpswitch'  . '', 'postdumpswitch' );
		        $switch = CSV2POST_Options::get_option( 'postdump', false );
		        if( $switch ){
		            $title = __( 'Hide $_POST', 'csv2post' );
		        } else {
		            $title = __( 'Display $_POST', 'csv2post' );
				}
				
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-postdisplay',
		            'parent' => 'csv2post-toolbarmenu-debugtools',
		            'title'  => $title,
		            'href'   => $href,            
		        );
        	    
        	    $wp_admin_bar->add_menu( $args );
        	                                 
		        // Trace display.        
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'tracedisplay'  . '', 'tracedisplay' );
		        $switch = CSV2POST_Options::get_option( 'debugtracedisplay', false );
		        if( $switch ){
		            $title = __( 'Hide Trace', 'csv2post' );
		        } else {
		            $title = __( 'Display Trace', 'csv2post' );
				}
				
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-tracedisplay',
		            'parent' => 'csv2post-toolbarmenu-debugtools',
		            'title'  => $title,
		            'href'   => $href,            
		        );
        	    
        	    $wp_admin_bar->add_menu( $args );
        	            	                                 
		        // Trace log.        
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'tracelog'  . '', 'tracelog' );
		        $switch = CSV2POST_Options::get_option( 'debugtracelog', false );
		        if( $switch ){
		            $title = __( 'Start Trace Log', 'csv2post' );
		        } else {
		            $title = __( 'Stop Trace Log', 'csv2post' );
				}
				
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-tracelog',
		            'parent' => 'csv2post-toolbarmenu-debugtools',
		            'title'  => $title,
		            'href'   => $href,            
		        );
        	    
        	    $wp_admin_bar->add_menu( $args );
        	        
	        // Group - Configuration Options
	        $args = array(
	            'id'     => 'csv2post-toolbarmenu-configurationoptions',
	            'parent' => 'csv2post-toolbarmenu-developers',
	            'title'  => __( 'Configuration Options', 'text_domain' ), 
	            'meta'   => array( 'class' => 'second-toolbar-group' )         
	        );        
	        $wp_admin_bar->add_menu( $args );        
		        
		        // reinstall plugin settings array     
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'csv2postactionreinstallsettings'  . '', 'csv2postactionreinstallsettings' );
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-reinstallsettings',
		            'parent' => 'csv2post-toolbarmenu-configurationoptions',
		            'title'  => __( 'Re-Install Settings', 'trainingtools' ),
		            'href'   => $href,            
		        );
		        
		        $wp_admin_bar->add_menu( $args );
		        
		        // reinstall all database tables
		        $thisaction = 'csv2postreinstalltables';
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . $thisaction, $thisaction );
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-reinstallalldatabasetables',
		            'parent' => 'csv2post-toolbarmenu-configurationoptions',
		            'title'  => __( 'Re-Install Tables', 'csv2post' ),
		            'href'   => $href,            
		        );
        	    
        	    $wp_admin_bar->add_menu( $args );
        	    
        	    // Add an delete option item for each individual package option.
        	    $single_options = $this->get_option_information( 'single', 'keys' ); 
                if( $single_options )
                {
	                foreach( $single_options as $key => $option_name )
	                {          
        			    $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&option=csv2post_' . $key . '&csv2postaction=csv2postactiondeleteoption', 'csv2postactiondeleteoption' );
				        $args = array(
				            'id'     => 'csv2post-toolbarmenu-individualoption-' . $key,
				            'parent' => 'csv2post-toolbarmenu-configurationoptions',
				            'title'  => 'Delete ' . $key,
				            'href'   => $href,            
				        );
				        $wp_admin_bar->add_menu( $args );
					}
				}
           	             	    
        	    // Add an delete option item for each individual global webtechglobal option.         
        	    $webtechglobal_options = $this->get_option_information( 'webtechglobal', 'keys' );
                if( $webtechglobal_options )
                {
	                foreach( $webtechglobal_options as $key => $option_name )
	                {          
        			    $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&option=' . $key . '&csv2postaction=csv2postactiondeleteoption', 'csv2postactiondeleteoption' );
				        $args = array(
				            'id'     => 'csv2post-toolbarmenu-individualoption-' . $key,
				            'parent' => 'csv2post-toolbarmenu-configurationoptions',
				            'title'  => 'Delete ' . $key,
				            'href'   => $href,            
				        );
				        $wp_admin_bar->add_menu( $args );
					}
				}
        	        
	        // Group - Scheduling and Automation Controls
	        $args = array(
	            'id'     => 'csv2post-toolbarmenu-schedulecontrols',
	            'parent' => 'csv2post-toolbarmenu-developers',
	            'title'  => __( 'Schedule Controls', 'text_domain' ), 
	            'meta'   => array( 'class' => 'third-toolbar-group' )         
	        );        
	        $wp_admin_bar->add_menu( $args );        
		        
		        // Bypass the main delay and run due events now.    
		        $href = wp_nonce_url( admin_url() . 'admin.php?page=' . $_GET['page'] . '&csv2postaction=' . 'csv2postactionautodelayall'  . '', 'csv2postactionautodelayall' );
		        $args = array(
		            'id'     => 'csv2post-toolbarmenu-autodelayall',
		            'parent' => 'csv2post-toolbarmenu-schedulecontrols',
		            'title'  => __( 'Bypass Main Delay', 'trainingtools' ),
		            'href'   => $href,            
		        );
		        $wp_admin_bar->add_menu( $args );
	}    
       
    /**
    * Gets option value for csv2post _adminset or defaults to the file version of the array if option returns invalid.
    * 1. Called in the main csv2post.php file.
    * 2. Installs the admin settings option record if it is currently missing due to the settings being required by all screens, this is to begin applying and configuring settings straighta away for a per user experience 
    */
    public function adminsettings() {
        $result = $this->option( 'csv2post_settings', 'get' );
        $result = maybe_unserialize( $result); 
        if(is_array( $result) ){
            return $result; 
        }else{     
            return $this->install_admin_settings();
        }  
    }
    
    /**
    * Control WordPress option functions using this single function.
    * This function will give us the opportunity to easily log changes and some others ideas we have.
    * 
    * @param mixed $option
    * @param mixed $action add, get, wtgget (own query function) update, delete
    * @param mixed $value
    * @param mixed $autoload used by add_option only
    */
    public function option( $option, $action, $value = 'No Value', $autoload = 'yes' ){
        if( $action == 'add' ){  
            return add_option( $option, $value, '', $autoload );            
        }elseif( $action == 'get' ){
            return get_option( $option);    
        }elseif( $action == 'update' ){        
            return update_option( $option, $value );
        }elseif( $action == 'delete' ){
            return delete_option( $option);        
        }
    }
                      
    /**
     * Add a widget to the dashboard.
     *
     * This function is hooked into the 'wp_dashboard_setup' action below.
     */
     
    /**
    * Hooked by wp_dashboard_setup
    * 
    * @uses CSV2POST_UI::add_dashboard_widgets() which has the widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.32
    * @version 1.1
    */
    public function add_dashboard_widgets() {
        $this->UI->add_dashboard_widgets();            
    }  
            
    /**
    * Determines if the plugin is fully installed or not
    * 
    * NOT IN USE - I've removed a global and a loop pending a new class that will need to be added to this function
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 6.0.0
    * @version 1.0.1
    */       
    public function is_installed() {
        return true;        
    }                       

    public function screen_options() {
        global $pippin_sample_page;
        $screen = get_current_screen();

        // toplevel_page_csv2post (main page)
        if( $screen->id == 'toplevel_page_csv2post' ){
            $args = array(
                'label' => __( 'Members per page' ),
                'default' => 1,
                'option' => 'csv2post_testoption'
            );
            add_screen_option( 'per_page', $args );
        }     
    }

    public function save_screen_option( $status, $option, $value ) {
        if ( 'csv2post_testoption' == $option ) return $value;
    }
      
    /**
    * WordPress Help tab content builder
    * 
    * uses help text from the main menu array to build help content
    * function must not be moved, keep it close to where it is called in the menu function
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.0.2
    */
    public function help_tab () {
                               
        // get the current screen array
        $screen = get_current_screen();
        
        // load help class which contains help content array
        $CSV2POST_Help = self::load_class( 'CSV2POST_Help', 'class-help.php', 'classes' );

        // call the array
        $help_array = $CSV2POST_Help->get_help_array();
        
        // load tab menu class which contains help content array
        $CSV2POST_TabMenu = self::load_class( 'CSV2POST_TabMenu', 'class-pluginmenu.php', 'classes' );
        
        // call the menu_array
        $menu_array = $CSV2POST_TabMenu->menu_array();
             
        // get page name i.e. csv-2-post_page_csv2post_affiliates would return affiliates
        $page_name = $this->PHP->get_string_after_last_character( $screen->id, '_' );
        
        // if on main page "csv2post" then set tab name as main
        if( $page_name == 'csv2post' ){$page_name = 'main';}
     
        // does the page have any help content? 
        if( !isset( $menu_array[ $page_name ] ) ){
            return false;
        }
        
        // set view name
        $view_name = $page_name;

        // does the view have any help content
        if( !isset( $help_array[ $page_name ][ $view_name ] ) ){
            return false;
        }
              
        // build the help content for the view
        $help_content = '<p>' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewabout' ] . '</p>';

        // add a link encouraging user to visit site and read more OR visit YouTube video
        if( isset( $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewreadmoreurl' ] ) ){
            $help_content .= '<p>';
            $help_content .= __( 'You are welcome to visit the', 'csv2post' ) . ' ';
            $help_content .= '<a href="' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewreadmoreurl' ] . '"';
            $help_content .= 'title="' . __( 'Visit the CSV 2 POST website and read more about', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ] . '"';
            $help_content .= 'target="_blank"';
            $help_content .= '>';
            $help_content .= __( 'CSV 2 POST Website', 'csv2post' ) . '</a> ' . __( 'to read more about', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ];           
            $help_content .= '.</p>';
        }  
        
        // add a link to a Youtube
        if( isset( $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewvideourl' ] ) ){
            $help_content .= '<p>';
            $help_content .= __( 'There is a', 'csv2post' ) . ' ';
            $help_content .= '<a href="' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewvideourl' ] . '"';
            $help_content .= 'title="' . __( 'Go to YouTube and watch a video about', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ] . '"';
            $help_content .= 'target="_blank"';
            $help_content .= '>';            
            $help_content .= __( 'YouTube Video', 'csv2post' ) . '</a> ' . __( 'about', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ];           
            $help_content .= '.</p>';
        }

        // add a link to a Youtube
        if( isset( $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewdiscussurl' ] ) ){
            $help_content .= '<p>';
            $help_content .= __( 'We invite you to take discuss', 'csv2post' ) . ' ';
            $help_content .= '<a href="' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewdiscussurl' ] . '"';
            $help_content .= 'title="' . __( 'Visit the WebTechGlobal forum to discuss', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ] . '"';
            $help_content .= 'target="_blank"';
            $help_content .= '>';            
            $help_content .= $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ] . '</a> ' . __( 'on the WebTechGlobal Forum', 'csv2post' );           
            $help_content .= '.</p>';
        }         

        // finish by adding the first tab which is for the view itself (soon to become registered pages) 
        $screen->add_help_tab( array(
            'id'    => $page_name,
            'title'    => __( 'About', 'csv2post' ) . ' ' . $help_array[ $page_name ][ $view_name ][ 'viewinfo' ][ 'viewtitle' ] ,
            'content'    => $help_content,
        ) );
  
        // add a tab per form
        $help_content = '';
        foreach( $help_array[ $page_name ][ $view_name ][ 'forms' ] as $form_id => $value ){
                                
            // the first content is like a short introduction to what the box/form is to be used for
            $help_content .= '<p>' . $value[ 'formabout' ] . '</p>';
                         
            // add a link encouraging user to visit site and read more OR visit YouTube video
            if( isset( $value[ 'formreadmoreurl' ] ) ){
                $help_content .= '<p>';
                $help_content .= __( 'You are welcome to visit the', 'csv2post' ) . ' ';
                $help_content .= '<a href="' . $value[ 'formreadmoreurl' ] . '"';
                $help_content .= 'title="' . __( 'Visit the CSV 2 POST website and read more about', 'csv2post' ) . ' ' . $value[ 'formtitle' ] . '"';
                $help_content .= 'target="_blank"';
                $help_content .= '>';
                $help_content .= __( 'CSV 2 POST Website', 'csv2post' ) . '</a> ' . __( 'to read more about', 'csv2post' ) . ' ' . $value[ 'formtitle' ];           
                $help_content .= '.</p>';
            }  
            
            // add a link to a Youtube
            if( isset( $value[ 'formvideourl' ] ) ){
                $help_content .= '<p>';
                $help_content .= __( 'There is a', 'csv2post' ) . ' ';
                $help_content .= '<a href="' . $value[ 'formvideourl' ] . '"';
                $help_content .= 'title="' . __( 'Go to YouTube and watch a video about', 'csv2post' ) . ' ' . $value[ 'formtitle' ] . '"';
                $help_content .= 'target="_blank"';
                $help_content .= '>';            
                $help_content .= __( 'YouTube Video', 'csv2post' ) . '</a> ' . __( 'about', 'csv2post' ) . ' ' . $value[ 'formtitle' ];           
                $help_content .= '.</p>';
            }

            // add a link to a Youtube
            if( isset( $value[ 'formdiscussurl' ] ) ){
                $help_content .= '<p>';
                $help_content .= __( 'We invite you to discuss', 'csv2post' ) . ' ';
                $help_content .= '<a href="' . $value[ 'formdiscussurl' ] . '"';
                $help_content .= 'title="' . __( 'Visit the WebTechGlobal forum to discuss', 'csv2post' ) . ' ' . $value[ 'formtitle' ] . '"';
                $help_content .= 'target="_blank"';
                $help_content .= '>';            
                $help_content .= $value[ 'formtitle' ] . '</a> ' . __( 'on the WebTechGlobal Forum', 'csv2post' );           
                $help_content .= '.</p>';
            } 
                               
            // loop through options
            foreach( $value[ 'options' ] as $key_two => $option_array ){  
                $help_content .= '<h3>' . $option_array[ 'optiontitle' ] . '</h3>';
                $help_content .= '<p>' . $option_array[ 'optiontext' ] . '</p>';
                            
                if( isset( $option_array['optionurl'] ) ){
                    $help_content .= ' <a href="' . $option_array['optionurl'] . '"';
                    $help_content .= ' title="' . __( 'Read More about', 'csv2post' )  . ' ' . $option_array['optiontitle'] . '"';
                    $help_content .= ' target="_blank">';
                    $help_content .= __( 'Read More', 'csv2post' ) . '</a>';      
                }
      
                if( isset( $option_array['optionvideourl'] ) ){
                    $help_content .= ' - <a href="' . $option_array['optionvideourl'] . '"';
                    $help_content .= ' title="' . __( 'Watch a video about', 'csv2post' )  . ' ' . $option_array['optiontitle'] . '"';
                    $help_content .= ' target="_blank">';
                    $help_content .= __( 'Video', 'csv2post' ) . '</a>';      
                }
            }
            
            // add the tab for this form and its help content
            $screen->add_help_tab( array(
                'id'    => $page_name . $view_name,
                'title'    => $help_array[ $page_name ][ $view_name ][ 'forms' ][ $form_id ][ 'formtitle' ],
                'content'    => $help_content,
            ) );                
                
        }
  
    }  

    /**
    * Gets the required capability for the plugins page from the page array
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.32
    * @version 1.1
    *  
    * @param mixed $csv2post_page_name
    * @param mixed $default
    */
    public function get_page_capability( $page_name ){
        $capability = 'administrator';// script default for all outcomes

        // get stored capability settings 
        $saved_capability_array = get_option( 'csv2post_capabilities' );
                
        if( isset( $saved_capability_array['pagecaps'][ $page_name ] ) && is_string( $saved_capability_array['pagecaps'][ $page_name ] ) ) {
            $capability = $saved_capability_array['pagecaps'][ $page_name ];
        }
                   
        return $capability;   
    }   

    /**
 	* Hooked in class-configuration.php and via class-csv2post.php
 	* 
 	* It is this function that checks the schedule table and executes a task
 	* that is due.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function webtechglobal_hourly_cron_function( $args ){    	
		$this->AUTO->webtechglobal_hourly_cron_function( $args );
    }
    
    /**
    * Adds links to the plugins row on the main plugins view.
    * 
    * @param mixed $actions
    * 
    * @version 2.0
    */
	function plugin_action_links( $actions ) {
 
		$actions['csv2post-donate'] = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.patreon.com/ryanbayne', __( 'Donate', 'csv2post' ) );
		$actions['csv2post-forum'] = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/csv-2-post', __( 'Forum', 'csv2post' ) );
        $actions['csv2post-settings'] = sprintf( '<a href="%s">%s</a>', CSV2POST_UI::admin_url( 'page=csv2post' ), __( 'Settings', 'csv2post' ) );
		$actions['csv2post-twitter'] = sprintf( '<br><a href="%s" target="_blank">%s</a>', 'http://www.twitter.com/Ryan_R_Bayne/', __( 'Authors Twitter', 'csv2post' ) );
		$actions['csv2post-facebook'] = sprintf( '<br><a href="%s" target="_blank">%s</a>', 'https://www.facebook.com/ryanrbayne/', __( 'Authors Facebook', 'csv2post' ) );
		$actions['csv2post-github'] = sprintf( '<br><a href="%s" target="_blank">%s</a>', 'https://github.com/RyanBayne', __( 'Authors GitHub', 'csv2post' ) );

		return $actions;
	}
	
    /**
    * Determine if further changes needed to the plugin or entire WP installation
    * straight after the plugin has been updated.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.1
    */
    public function complete_plugin_update() {
        // determine if manual update required or not 
        // if not then update installed version to the file package
        $manual_update_require = false;
        $this->UpdatePlugin = self::load_class( 'CSV2POST_UpdatePlugin', 'class-updates.php', 'classes' );        
       
        if( isset( $package_version_cleaned ) && !$package_version_cleaned )
        {
            // does new version have an update method
            if( method_exists( $this->UpdatePlugin, 'patch_' . str_replace( '.', '', self::version ) ) )
            { 
                // run the new packages update method - these methods can take a command for special situations
                eval( '$update_result_array = $this->Updates->patch_' . self::version .'( "update" );' );
                
                // update stored version
                if( $update_result_array['failed'] !== true){           
                    global $csv2post_filesversion;        
                    update_option( 'csv2post_installedversion', $csv2post_filesversion);        
                }                                                                                        
            }
        }
    }
    
    /**
    * WordPress plugin menu
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.9
    */
    public function admin_menu() { 
        global $csv2post_filesversion, $CSV2POST_Menu, $csv2post_settings;
         
        $CSV2POST_TabMenu = CSV2POST::load_class( 'CSV2POST_TabMenu', 'class-pluginmenu.php', 'classes' );
        $CSV2POST_Menu = $CSV2POST_TabMenu->menu_array();
 
        // set the callback, we can change this during the loop and call methods more dynamically
        // this approach allows us to call the same function for all pages
        $subpage_callback = array( $this, 'show_admin_page' );

        // add menu
        $this->page_hooks[] = add_menu_page( $CSV2POST_Menu['main']['title'], 
        'CSV 2 POST', 
        'administrator', 
        'csv2post',  
        $subpage_callback ); 
        
        // help tab                                                 
        add_action( 'load-toplevel_page_csv2post', array( $this, 'help_tab' ) );

        // track which group has already been displayed using the parent name
        $groups = array();
        
        // remove arrayinfo from the menu array
        unset( $CSV2POST_Menu['arrayinfo'] );
        
        // get all group menu titles
        $group_titles_array = array();
        foreach( $CSV2POST_Menu as $key_pagename => $page_array ){ 
            if( $page_array['parent'] === 'parent' ){                
                $group_titles_array[ $page_array['groupname'] ]['grouptitle'] = $page_array['menu'];
            }
        }          
        
        // loop through sub-pages - remove pages that are not to be registered
        foreach( $CSV2POST_Menu as $key_pagename => $page_array ){                 

            // if not visiting this plugins pages, simply register all the parents
            if( !isset( $_GET['page'] ) || !strstr( $_GET['page'], 'csv2post' ) ){
                
                // remove none parents
                if( $page_array['parent'] !== 'parent' ){    
                    unset( $CSV2POST_Menu[ $key_pagename ] ); 
                }        
            
            }elseif( isset( $_GET['page'] ) && strstr( $_GET['page'], 'csv2post' ) ){
                
                // remove pages that are not the main, the current visited or a parent
                if( $key_pagename !== 'main' && $page_array['slug'] !== $_GET['page'] && $page_array['parent'] !== 'parent' ){
                    unset( $CSV2POST_Menu[ $key_pagename ] );
                }     
                
            } 
            
            // remove the parent of a group for the visited page
            if( isset( $_GET['page'] ) && $page_array['slug'] === $_GET['page'] ){
                unset( $CSV2POST_Menu[ $CSV2POST_Menu[ $key_pagename ]['parent'] ] );
            }
                    
            // if no current project is set remove all but the "1. Projects" page
            if( !isset( $csv2post_settings['currentproject'] ) && $page_array['groupname'] !== 'projects' ) {
                unset( $CSV2POST_Menu[ $key_pagename ] );
            }
            
            // remove update page as it is only meant to show when new version of files applied
            if( $page_array['slug'] == 'csv2post_pluginupdate' ) {
                unset( $CSV2POST_Menu[ $key_pagename ] );
            }
        }

        foreach( $CSV2POST_Menu as $key_pagename => $page_array ){ 
            
            $this->page_hooks[] = add_submenu_page( 'csv2post', 
                   $group_titles_array[ $page_array['groupname'] ]['grouptitle'], 
                   $group_titles_array[ $page_array['groupname'] ]['grouptitle'], 
                   self::get_page_capability( $key_pagename ), 
                   $CSV2POST_Menu[ $key_pagename ]['slug'], 
                   $subpage_callback );     

                // help tab                                                 
                add_action( 'load-csv-2-post_page_csv2post_' . $key_pagename, array( $this, 'help_tab' ) );       
                          
        }
    }
    
    /**
     * Tabs menu loader - calls function for css only menu or jquery tabs menu
     * 
     * @param string $thepagekey this is the screen being visited
     */
    public function build_tab_menu( $current_page_name ){           
        // load tab menu class which contains help content array
        $CSV2POST_TabMenu = CSV2POST::load_class( 'CSV2POST_TabMenu', 'class-pluginmenu.php', 'classes' );
        
        // call the menu_array
        $menu_array = $CSV2POST_TabMenu->menu_array();
                
        echo '<h2 class="nav-tab-wrapper">';
        
        // get the current pages viewgroup for building the correct tab menu
        $view_group = $menu_array[ $current_page_name ][ 'groupname'];
            
        foreach( $menu_array as $page_name => $values ){
                                                         
            if( $values['groupname'] === $view_group ){
                
                $activeclass = 'class="nav-tab"';
                if( $page_name === $current_page_name ){                      
                    $activeclass = 'class="nav-tab nav-tab-active"';
                }
                
                echo '<a href="' . self::create_adminurl( $values['slug'] ) . '" '.$activeclass.'>' . $values['pluginmenu'] . '</a>';       
            }
        }      
        
        echo '</h2>';
    }   
        
    /**
    * $_POST and $_GET request processing procedure.
    * 
    * function was reduced to two lines, the contents mode to CSV2POST_Requests itself.
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.0.4
    */
    public function process_admin_POST_GET() {  
        // include the class that processes form submissions and nonce links
        $CSV2POST_REQ = self::load_class( 'CSV2POST_Requests', 'class-requests.php', 'classes' );
        $CSV2POST_REQ->process_admin_request();
    }  

    /**
    * Used to display this plugins notices on none plugin pages i.e. dashboard.
    * 
    * filteraction_should_beloaded() decides if the admin_notices hook is called, which hooks this function.
    * I think that check should only check which page is being viewed. Anything more advanced might need to
    * be performed in display_users_notices().
    * 
    * @uses display_users_notices()
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function admin_notices() {
        $this->UI->display_users_notices();
    }

    public function set_screen( $status, $option, $value ) {  
        return $value;
    }
                                    
    /**
    * Popup and content for media button displayed just above the WYSIWYG editor 
    */
    public function pluginmediabutton_popup() {
        global $csv2post_settings;
        ?>
        <div id="csv2post_popup_container" style="display:none;">
            <h2>Column Replacement Tokens</h2>
            <?php 
            if(!isset( $csv2post_settings['currentproject'] ) || !is_numeric( $csv2post_settings['currentproject'] ) ){
                echo '<p>' . __( '' ) . '</p>';
            }else{
                $projectcolumns = $this->DB->get_project_columns_from_db( $csv2post_settings['currentproject'], true );
                unset( $projectcolumns['arrayinfo'] ); 
                $tokens = '';
                foreach( $projectcolumns as $table_name => $columnfromdb ){
                    foreach( $columnfromdb as $key => $acol){
                        $tokens .= "#$acol#&#13;&#10;";
                    }  
                }     
                
                echo '<textarea rows="35" cols="70">' . $tokens . ' </textarea>';
            }
            ?>
        </div><?php
    }    
    
    /**
    * ACTION Method: detect new .csv files and handle it. 
    * 
    * Checks all data source directories for new files (not updated,changed files).
    * 
    * On finding new file will process, adding information about the file to the
    * database and may begin data import. 
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo incomplete function, I think there is a manual function that handles
    * this. If not then something has gone wrong in controlling version because
    * this ability was already in the plugin and I find it strange this function
    * had multiple empty rows as if accidental deletion. Check recent versions.
    */
    public function detectnewfiles() {
        global $csv2post_settings;
        if( !isset( $csv2post_settings['standardsettings']['detectnewfiles'] ) || $csv2post_settings['standardsettings']['detectnewfiles'] !== true ) {
            return;    
        }
        
        // get all csv source ID and directory
        $sources_result = $this->DB->selectwherearray( $wpdb->c2psources, 'sourcetype = "localcsv"', 'timestamp', 'sourceid, directory', 'ARRAY_A', null );
        if( !$sources_result ){ return; }
    }
            
    /**
    * HTML for a media button that displays above the WYSIWYG editor
    * 
    * @param mixed $context
    */
    public function pluginmediabutton_button( $context )   
                                                          {
        //append the icon
        $context = "<a class='button thickbox' title='CSV 2 POST Column Replacement Tokens (CTRL + C then CTRL + V)'
        href='#TB_inline?width=400&inlineId=csv2post_popup_container'>CSV 2 POST</a>";
        
        return $context;
    }
    /**
    * Used in admin page headers to constantly check the plugins status while administrator logged in 
    */
    public function diagnostics_constant() {
        if( is_admin() && current_user_can( 'manage_options' ) ){
            
            // avoid diagnostic if a $_POST, $_GET or Ajax request made (it is installation state diagnostic but active debugging)                                          
            if( self::request_made() ){
                return;
            }
                              
        }
    } 
    
    /**
    * DO NOT CALL DURING FULL PLUGIN INSTALL
    * This function uses update. Do not call it during full install because user may be re-installing but
    * wishing to keep some existing option records.
    * 
    * Use this function when installing admin settings during use of the plugin. 
    */
    public function install_admin_settings() {
        require_once( CSV2POST_ABSPATH . 'arrays/settings_array.php' );
        return $this->option( 'csv2post_settings', 'update', $csv2post_settings );# update creates record if it does not exist   
    } 
     
    /**
    * includes a file per custom post type, we can customize this 
    * to include or exclude based on settings
    * 
    * @version 1.2
    */
    public function custom_post_types() { 
        global $csv2post_settings;                                                                               
        require_once( CSV2POST_ABSPATH . 'posttypes/posts.php' );
    }

    /**
    * Returns array holding the headers of the giving filename
    * It also prepares the array to hold other formats of the column headers in prepartion for the plugins various uses
    */
    public function get_headers_formatted( $filename, $separator = ', ', $quote = '"', $fields = 0){
        $header_array = array();
        
        // read and loop through the first row in the csv file  
        $handle = fopen( $filename, "r");
        while (( $row = fgetcsv( $handle, 10000, $separator, $quote) ) !== FALSE) {
       
            for ( $i = 0; $i < $fields; $i++ ){
                $header_array[$i]['original'] = $row[$i];
                $header_array[$i]['sql'] = self::clean_sqlcolumnname( $row[$i] );// none adapted/original sql version of headers, could have duplicates with multi-file jobs             
            }           
            break;
        }
                            
        return $header_array;    
    }
    
    /**
    * Creates a data source row, the data is used to track and manage the source
    * 
    * @param mixed $path
    * @param mixed $parentfile_id
    * @param mixed $tablename
    * @param mixed $sourcetype
    * @param mixed $csvconfig_array
    * @param mixed $idcolumn - important for relationship between source and database table plus between multiple tables in a multi-file project
    */
    public function insert_data_source( $path, $parentfile_id, $tablename, $sourcetype = 'localcsv', $csvconfig_array = array(), $idcolumn = '', $monitorfilechange = 1, $monitordirchange = 1, $rows = 0 ){
        global $wpdb;
        return $this->DB->insert( $wpdb->c2psources,
            array(              
                'name' => $csvconfig_array['sourcename'],   
                'path' => $path,
                'sourcetype' => $sourcetype,
                'datatreatment' => $csvconfig_array['datatreatment'],
                'parentfileid' => $parentfile_id,
                'tablename' => $tablename,
                'thesep' => $csvconfig_array['sep'],
                'theconfig' => maybe_serialize( $csvconfig_array ),
                'idcolumn' => $idcolumn,
                'monitorfilechange' => $monitorfilechange,
                'directory' => trailingslashit( dirname ( $path ) ),// used for handling multiple files and switching from an old file to new
                'rows' => $rows,
            ) );
    } 
    
    /**
    * inserts a new project, merges a larger projects settings array into the fields_array
    * 
    * @param string $project_name 
    * @param mixed $sourceid_array - usually will be a single source with the key being "source1" however this increments for further sources
    * @param string $data_treatment (single | append | join | individual )
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.0.2
    */
    public function insert_project( $project_name, $sourceid_array, $data_treatment = 'single' ){
        global $wpdb;
        $fields_array = array( 'projectname' => $project_name, 'datatreatment' => $data_treatment );
        $fields_array = array_merge( $fields_array, $sourceid_array );        
        return $this->DB->insert( $wpdb->c2pprojects, $fields_array );
    }
    
    public function query_projects( $sort = null ) {
        global $wpdb;
        return $this->DB->selectwherearray( $wpdb->c2pprojects, 'projectid = projectid', 'timestamp', '*' );
    }
    
    public function create_project_table( $table_name, $columns_array ){
        global $wpdb;
 
        $query = "CREATE TABLE `{$table_name}` (
        `c2p_rowid` int(10) unsigned NOT NULL auto_increment,
        `c2p_postid` int(10) unsigned default 0,
        `c2p_use` tinyint(1) unsigned default 1,
        `c2p_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
        `c2p_applied` datetime NOT NULL,
        `c2p_changecounter` int(8) unsigned default 0, 
        ";                               
            
        // loop through jobs files
        foreach( $columns_array as $key => $column){
            $query .= "`" . $key . "` text default NULL, ";                                                                                                              
        }
      
        $query .= "PRIMARY KEY  (`c2p_rowid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table created by CSV 2 POST';";
        
        $createresult1 = $wpdb->query( $query );
    }
    
    /**
    * Updates giving project with a compatible array of pre-defined project settings
    * 
    * this should be called as early as possible in project creation however if a user wanted to use it to
    * re-set a project after editing it manually that is fine.
    * 
    * @param mixed $project_id
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.0
    * @version 1.0.2
    */
    public function apply_project_defaults( $project_id ){
        global $csv2post_settings;     
        if( !isset( $csv2post_settings['projectdefaults'] ) ) { return false; }
        self::update_project( $project_id, array( 'projectsettings' => maybe_serialize( $csv2post_settings['projectdefaults'] ) ), true);
    }
    
    /**
    * Function is called to update posts before the page is rendered, the visitor
    * sees the new data. This function is called using add_action('the_posts'...  
    * 
    * @parameter $post array 
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function systematicpostupdate( $post ){
        global $wpdb, $csv2post_settings;
        
        // set a limit when updating multiple posts
        // default is to one avoid updating posts when not in single post view
        $multiple_posts_limit = 1;
        
        if( count( $post ) > $multiple_posts_limit ){
            return $post;    
        }
                         
        // avoid doing this if user is on the post trash view
        if( isset( $_GET['post_status'] ) && $_GET['post_status'] == 'trash' ){
            return $post;
        }
        
        // return $post now if systematic post updating is not active
        if(!isset( $csv2post_settings['standardsettings']['systematicpostupdating'] ) || $csv2post_settings['standardsettings']['systematicpostupdating'] != 'enabled' ){
            return $post;    
        }
            
        // do we have a post - this should be the case due to the add_action but just to be sure
        if( isset( $post) && is_array( $post ) ){
            
            // loop through all post objects
            foreach( $post as $key => $thepost ){
                
                // check if post is owned by CSV 2 POST by getting the project ID from post meta                                                   
                $project_id = get_post_meta( $thepost->ID, 'c2p_project', true);
                
                // ensure we have three valid ID's else nothing can be done
                if( !is_numeric( $project_id) ){
                    continue;// moves on to next item in loop
                }
   
                // get the project row
                $project_array = $this->DB->get_project( $project_id );
                
                // continue if there is no project
                if(!$project_array){
                    continue;
                }
                 
                // change this to true if a reason for updating the post is found
                // keep in mind updating is a lot like re-building the post
                $update_this_post = false;
                
                // get the posts import data            
                $tables_array = $this->DB->get_dbtable_sources( $project_id );                 
                $row = $this->DB->query_multipletables( $tables_array, $this->get_project_idcolumn( $project_id), 'c2p_postid = ' . $thepost->ID, 1 );
                         
                // if no row of data there is nothing more to be done continue to next item
                if( !$row ){
                    continue;
                }
                
                // take the data out of [0]
                $row = $row[0];
                
                // check if c2p_updated > c2p_applied, if so the post will need to be updated
                if( isset( $row->c2p_updated ) && isset( $row->c2p_applied ) && $row->c2p_updated > c2p_applied ){
                    $update_this_post = true;    
                }

                // if still no reason to update, check if project settings changed since post was updated  
                if( !$update_this_post ){      
                    
                    // we will need the $project_array for updating but we need it right now for the settingschanged value  
                    if( isset( $project_array->settingschange ) ){ 
                        
                        // select meta row that matches our criteria
                        $outofdate = $this->DB->selectrow( $wpdb->postmeta,
                            "meta_key = 'c2p_updated'
                             AND meta_value < '".$project_array->settingschange."' 
                             AND post_id = '".$thepost->ID."'" );
                             
                        // if a row is returned then this CSV 2 POST post has not been updated since project changed 
                        if( $outofdate ){   
                            $update_this_post = true;
                        }
                    }
                }

                // update the post if a reason is found
                if( $update_this_post ){        
                    
                    // perform update on current post
                    $updatepost = new CSV2POST_UpdatePost();
                    $updatepost->settings = $csv2post_settings;
                    $updatepost->currentproject = $csv2post_settings['currentproject'];// dont automatically use by default, request may be for specific project
                    $updatepost->project = $project_array;// gets project row, includes sources and settings
                    $updatepost->projectid = $project_id;
                    $updatepost->maintable = $this->DB->get_project_main_table( $project_id );// main table holds post_id and progress statistics
                    $updatepost->projectsettings = maybe_unserialize( $updatepost->project->projectsettings );// unserialize settings
                    $updatepost->projectcolumns = $this->DB->get_project_columns_from_db( $project_id );
                    $updatepost->requestmethod = 'systematic';             
                    
                    // pass row to $autob
                    $updatepost->row = $row; 
                    
                    // pass the current post array for editing then returning and displaying the changes to the visitor
                    $updatepost->thepost = $thepost;   
                    
                    // update post - start method is the beginning of many nested functions
                    $updatepost->start();     
                            
                    // if a single post we can refresh - not going to do this if form submitted
                    // if for any reason the posts update status is not updated, this would cause a looping refresh
                    // should that happen, corrections need to be applied to custom field value c2p_updated
                    if( count( $post ) && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                        
                        $page = $this->PHP->currenturl();
                        header("Refresh: 0; url=$page");   
                             
                    }                                 
                }                                   
            }
        }  
                     
        return $post;
    }  

    /**
    * gets the giving posts row of imported data if the row has been updated since the post was created
    * 
    * can use this when checking if a post needs updated, rather than returning the posts row to another argument
    * 
    * @param mixed $project_id
    * @param mixed $post_id
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.1 
    */
    public function get_posts_row_ifoutdated( $project_id, $post_id, $idcolumn = false ){
        $tables_array = $this->DB->get_dbtable_sources( $project_id );                       
        return self::query_multipletables( $tables_array, $idcolumn, 'c2p_postid = '.$post_id.' AND c2p_updated > c2p_applied', 1 );
    }
        
    /**
    * gets the id column column from project array and returns the colum name 
    * 
    * @returns boolean false on failure or no idcolumn set
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.1 
    */
    public function get_project_idcolumn( $project_id ){
        global $wpdb;
        
        // get the project row
        $project_array = $this->DB->get_project( $project_id );   
        
        // unserialize settings vlaue
        $project_settings_array = maybe_unserialize( $project_array->projectsettings );
        
        // ensure idcolumn value exists
        if( isset( $project_settings_array['idcolumn'] ) ){
            return $project_settings_array['idcolumn'];
        }
        
        // get sources ID - we only need the first/main table
        $source_id_array = $this->DB->get_project_sourcesid( $project_id );
        
        if( !$source_id_array ){
            return false;
        }

        $row = $this->DB->selectrow( $wpdb->c2psources, 'sourceid = "' . $source_id_array[0] . '"', 'idcolumn' );   
        
        if( !$row ){
            return false;
        }
        
        if( empty( $row->idcolumn ) ){
            return false;
        }
         
        return $row->idcolumn;
    }
    
    /**
    * gets the specific row/s for a giving post ID
    * 
    * UPDATE: "c2p_postid != $post_id" was in use but this is wrong. I'm not sure how this has gone
    * undetected considering where the function has been used. 
    *
    * @param mixed $project_id
    * @param mixed $total
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.2
    */
    public function get_posts_rows( $project_id, $post_id, $idcolumn = false ){
        $tables_array = $this->DB->get_dbtable_sources( $project_id );
        return $this->DB->query_multipletables( $tables_array, $idcolumn, 'c2p_postid = '.$post_id );
    }
    
    /**
    * Gets one or more rows from imported data for specific 
    * post created by specific project.
    * 
    * @uses get_posts_rows() which does a join query 
    * 
    * @param mixed $project_id
    * @param mixed $post_id
    * @param mixed $idcolumn
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1 
    */
    public function get_posts_record( $project_id, $post_id, $idcolumn = false ){
        return self::get_posts_rows( $project_id, $post_id, $idcolumn );
    } 
    
    /**
    * Gets the MySQL version of column
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    * 
    * @returns false if no column set
    */
    public function get_category_column( $project_id, $level ) {
        if( isset( $this->current_project_settings['categories']['data'][$level]['column'] ) ){
            return $this->current_project_settings['categories']['data'][$level]['column'];    
        }           
        
        return false;
    } 

    /**
    * gets values from set category columns where the row has been used to create a post already
    * the intention is to use this to update existing posts where category settings or data changes
    * since the posts were created.
    * 
    * @returns OBJECT from $wpdb->get_results() unless $postid_as_key = true and array returned
    * @uses CSV2POST_DB::selectorderby()
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.0.1` 
    * 
    * @param mixed $project_id
    * @param mixed $total
    * @param mixed $idcolumn
    */
    public function get_category_data_used( $project_id, $total = 1, $postid_as_key = false, $post_id = false ){
        global $csv2post_settings;
        
        $select = 'c2p_postid';
        $from = 'notsetyet';

        if( isset( $csv2post_settings['currentproject'] ) && $csv2post_settings['currentproject'] !== false ){
            $this->current_project_object = $this->DB->get_project( $csv2post_settings['currentproject'] ); 
        }
        
        if( !$this->current_project_object ) {    
            $this->current_project_settings = false;
        } else {          
            $this->current_project_settings = maybe_unserialize( $this->current_project_object->projectsettings );
        }

        // loop through category data columns, their order is assumed to be the intended heirarchy order
        foreach( $this->current_project_settings['categories']['data'] as $level => $catarray ){
                                         
            $column = $catarray['column'];
            $from = $catarray['table'];
            
            if( $select === '' ){
                $select .= $column;
            }else{
                $select .= ', ' . $column;
            }

        }      
        
        // set condition to get all rows that have a post ID or get a specific post
        $condition = 'c2p_postid != 0';
        if( is_numeric( $post_id ) && $post_id != 0 ){
            $condition = 'c2p_postid = ' . $post_id;
        }
        
        $result = $this->DB->selectorderby( $from, $condition, $column, $select, $total, ARRAY_A );
        
        if( !$postid_as_key ) {
            return $result;
        }
        
        // arriving here means $postid_as_key has been requested
        // this allows a loop on post related data and then a loop on the category terms 
        $rebuilt = array();
        foreach( $result as $key => $row ){
            foreach( $row as $column => $value ){
                if( $column !== 'c2p_postid' ){
                    $rebuilt[ $row['c2p_postid'] ][] = $value;
                }
            } 
        }
        
        return $rebuilt;
    }

    /**
    * Determines if process request of any sort has been requested
    * 1. used to avoid triggering automatic processing during proccess requests
    * 
    * @returns true if processing already requested else false
    */
    public function request_made() {
        // ajax
        if(defined( 'DOING_AJAX' ) && DOING_AJAX){
            return true;    
        } 
        
        // form submissions - if $_POST is set that is fine, providing it is an empty array
        if( isset( $_POST) && !empty( $_POST) ){
            return true;
        }
        
        // CSV 2 POST own special processing triggers
        if( isset( $_GET['c2pprocsub'] ) || isset( $_GET['csv2postaction'] ) || isset( $_GET['nonceaction'] ) ){
            return true;
        }
        
        return false;
    } 
       
    /**
     * Checks existing plugins and displays notices with advice or informaton
     * This is not only for code conflicts but operational conflicts also especially automated processes
     *
     * $return $critical_conflict_result true or false (true indicatesd a critical conflict found, prevents installation, this should be very rare)
     */
    function conflict_prevention( $outputnoneactive = false ){
        // track critical conflicts, return the result and use to prevent installation
        // only change $conflict_found to true if the conflict is critical, if it only effects partial use
        // then allow installation but warn user
        $conflict_found = false;
            
        // we create an array of profiles for plugins we want to check
        $plugin_profiles = array();

        // Tweet My Post (javascript conflict and a critical one that breaks entire interface)
        $plugin_profiles[0]['switch'] = 1;//used to use or not use this profile, 0 is no and 1 is use
        $plugin_profiles[0]['title'] = __( 'Tweet My Post', 'csv2post' );
        $plugin_profiles[0]['slug'] = 'tweet-my-post/tweet-my-post.php';
        $plugin_profiles[0]['author'] = 'ksg91';
        $plugin_profiles[0]['title_active'] = __( 'Tweet My Post Conflict', 'csv2post' );
        $plugin_profiles[0]['message_active'] = __( 'Please deactivate Twitter plugins before performing mass post creation. This will avoid spamming Twitter and causing more processing while creating posts.', 'csv2post' );
        $plugin_profiles[0]['message_inactive'] = __( 'If you activate this or any Twitter plugin please ensure the plugins options are not setup to perform mass tweets during post creation.', 'csv2post' );
        $plugin_profiles[0]['type'] = 'info';//passed to the message function to apply styling and set type of notice displayed
        $plugin_profiles[0]['criticalconflict'] = true;// true indicates that the conflict will happen if plugin active i.e. not specific settings only, simply being active has an effect
                             
        // loop through the profiles now
        if( isset( $plugin_profiles) && $plugin_profiles != false ){
            foreach( $plugin_profiles as $key=>$plugin){   
                if( is_plugin_active( $plugin['slug'] ) ){ 
                   
                    // recommend that the user does not use the plugin
                    $this->notice_depreciated( $plugin['message_active'], 'warning', 'Small', $plugin['title_active'], '', 'echo' );

                    // if the conflict is critical, we will prevent installation
                    if( $plugin['criticalconflict'] == true){
                        $conflict_found = true;// indicates critical conflict found
                    }
                    
                }elseif(is_plugin_inactive( $plugin['slug'] ) ){
                    
                    if( $outputnoneactive)
                    {   
                        $this->n_incontent_depreciated( $plugin['message_inactive'], 'warning', 'Small', $plugin['title'] . ' Plugin Found' );
                    }
        
                }
            }
        }

        return $conflict_found;
    }     
        
    public function send_email( $recipients, $subject, $content, $content_type = 'html' ){     
                           
        if( $content_type == 'html' )
        {
            add_filter( 'wp_mail_content_type', 'csv2post_set_html_content_type' );
        }
        
        $result = wp_mail( $recipients, $subject, $content );
                
        if( $content_type == 'html' )
        {    
            remove_filter( 'wp_mail_content_type', 'csv2post_set_html_content_type' );  
        }   
        
        return $result;
    }    
	    
    /**
    * Creates url to an admin page
    *  
    * @param mixed $page, registered page slug i.e. csv2post_install which results in wp-admin/admin.php?page=csv2post_install   
    * @param mixed $values, pass a string beginning with & followed by url values
    */
    public function url_toadmin( $page, $values = '' ){                                  
        return get_admin_url() . 'admin.php?page=' . $page . $values;
    }
    
    /**
    * Adds <button> with jquerybutton class and </form>, for using after a function that outputs a form
    * Add all parameteres or add none for defaults
    * @param string $buttontitle
    * @param string $buttonid
    */
    public function formend_standard( $buttontitle = 'Submit', $buttonid = 'notrequired' ){
            if( $buttonid == 'notrequired' ){
                $buttonid = 'csv2post_notrequired'.rand(1000,1000000);# added during debug
            }else{
                $buttonid = $buttonid.'_formbutton';
            }?>

            <p class="submit">
                <input type="submit" name="csv2post_wpsubmit" id="<?php echo $buttonid;?>" class="button button-primary" value="<?php echo $buttontitle;?>">
            </p>

        </form><?php
    }
    
    /**
     * Echos the html beginning of a form and beginning of widefat post fixed table
     * 
     * @param string $name (a unique value to identify the form)
     * @param string $method (optional, default is post, post or get)
     * @param string $action (optional, default is null for self submission - can give url)
     * @param string $enctype (pass enctype="multipart/form-data" to create a file upload form)
     */
    public function formstart_standard( $name, $id = 'none', $method = 'post', $class = '', $action = '', $enctype = '' ){
        if( $class){
            $class = 'class="'.$class.'"';
        }else{
            $class = '';         
        }
        echo '<form '.$class.' '.$enctype.' id="'.$id.'" method="'.$method.'" name="csv2post_request_'.$name.'" action="'.$action.'">
        <input type="hidden" id="csv2post_admin_action" name="csv2post_admin_action" value="true">';
    } 
    
    /**
    * Assumes $project_id has been validated. However there is situations where
    * the active projects data has been deleted. Eventually the active project
    * will be reset automatically to prevent that.
    * 
    * @version 1.1
    * 
    * @param mixed $project_id
    * @return mixed
    */
    public function get_project_name( $project_id ){
        global $wpdb;
        $row = $this->DB->selectrow( $wpdb->c2pprojects, 'projectid = ' . $project_id, 'projectname' );
        
        if( $row === null ){
        	return __( 'No Project Data Exists', 'wpdimp' );
        }
        
        if(!isset( $row->projectname) ){
        	return 'Project Has No Name';
        }
        
        return $row->projectname;
    }   
    
    /**
    * Adds Script Start and Stylesheets to the beginning of pages
    */
    public function pageheader( $pagetitle, $layout ){
        global $current_user, $csv2post_settings;

        // get admin settings again, all submissions and processing should update settings
        // if the interface does not show expected changes, it means there is a problem updating settings before this line
        $csv2post_settings = self::adminsettings(); ?>
                    
        <div id="csv2post-page" class="wrap">
            <?php self::diagnostics_constant();?>
        
            <div id="icon-options-general" class="icon32"><br /></div>
            
            <?php 
            // build page H2 title
            $h2_title = '';
            
            // if not "CSV 2 POST" set this title
            if( $pagetitle !== 'CSV 2 POST' ) {
                $h2_title = 'CSV 2 POST' . ': ' . $pagetitle;    
            }           
            ?>
            
            <h2><?php echo $h2_title;?></h2>

            <?php 
            // if not on main/about/news view show the project/campaign information
            if( $_GET['page'] !== 'csv2post' && $_GET['page'] !== 'csv2post_pluginupdate' ){
                $this->UI->display_current_project();
            }

            // check existing plugins and give advice or warnings
            self::conflict_prevention();
                     
            // display form submission result notices
            $this->UI->output_depreciated();// now using display_all();
            $this->UI->display_all();              
          
            // Constantly check the environment for missing requirements.
            // There is a seperate process during activation.
            self::check_requirements_after_activation(true);
    }                          
    
    /**
    * Checks if the plugins minimum requirements are met. 
    * 
    * Performed on every page load to ensure the environment does not 
    * change.
    * 
    * @version 1.2
    */
    public function check_requirements_after_activation( $display ){
    	global $wp_version;
    	
        // variable indicates message being displayed, we will only show 1 message at a time
        $requirement_missing = false;

        // PHP
        if( defined( CSV2POST_PHPVERSIONMINIMUM ) ){
            if( CSV2POST_PHPVERSIONMINIMUM > PHP_VERSION ){
                $requirement_missing = true;
                if( $display == true ){
                	$message = sprintf( __( 'Your PHP version is too low. We
                	recommend an upgrade on your hosting. Do this by contacting
                	your hosting service and explaining this notice.', 'csv2post' ) );
                    self::notice_depreciated(
                    	$message, 
                    	CSV2POST_PHPVERSIONMINIMUM, 
                    	'warning', 
                    	'Large', 
                    	__( 'CSV 2 POST Requires PHP ', 'csv2post' ) . CSV2POST_PHPVERSIONMINIMUM
                    );                
                }
            }
        }

        // WP
        if( defined( CSV2POST_WPVERSIONMINIMUM ) ){
            if( CSV2POST_WPVERSIONMINIMUM > $wp_version ){
                $requirement_missing = true;
                if( $display == true ){
                	$message = sprintf( __( 'Your WP version is too low for 
                	CSV 2 POST plugin. It means there are improvements in the
                	more recent versions of WordPress that this plugin requires.
                	It is always in your best interest to keep WP updated and we
                	can help.', 'csv2post' ) );
                    self::notice_depreciated(
                    	$message, 
                    	CSV2POST_WPVERSIONMINIMUM, 
                    	'warning', 
                    	'Large', 
                    	__( 'CSV 2 POST Requires WP ', 'csv2post' ) . CSV2POST_WPVERSIONMINIMUM
                    );                
                }
            }
        }
        
        return $requirement_missing;
    }               
    
    /**       
     * Generates a username using a single value by incrementing an appended number until a none used value is found
     * @param string $username_base
     * @return string username, should only fail if the value passed to the function causes so
     * 
     * @todo log entry functions need to be added, store the string, resulting username
     */
    public function create_username( $username_base ){
        $attempt = 0;
        $limit = 500;// maximum trys - would we ever get so many of the same username with appended number incremented?
        $exists = true;// we need to change this to false before we can return a value

        // clean the string
        $username_base = preg_replace( '/([^@]*).*/', '$1', $username_base );

        // ensure giving string does not already exist as a username else we can just use it
        $exists = username_exists( $username_base );
        if( $exists == false )
        {
            return $username_base;
        }
        else
        {
            // if $suitable is true then the username already exists, increment it until we find a suitable one
            while( $exists != false )
            {
                ++$attempt;
                $username = $username_base.$attempt;

                // username_exists returns id of existing user so we want a false return before continuing
                $exists = username_exists( $username );

                // break look when hit limit or found suitable username
                if( $attempt > $limit || $exists == false ){
                    break;
                }
            }

            // we should have our login/username by now
            if ( $exists == false ) 
            {
                return $username;
            }
        }
    }
    
    /**
    * Wrapper, uses csv2post_url_toadmin to create local admin url
    * 
    * @param mixed $page
    * @param mixed $values 
    */
    public function create_adminurl( $page, $values = '' ){
        return self::url_toadmin( $page, $values);    
    }
    
    public function get_installed_version() {
        return get_option( 'csv2post_installedversion' );    
    }  
    
    /**
    * Use to start a new result array which is returned at the end of a function. It gives us a common set of values to work with.

    * @uses self::arrayinfo_set()
    * @param mixed $description use to explain what array is used for
    * @param mixed $line __LINE__
    * @param mixed $function __FUNCTION__
    * @param mixed $file __FILE__
    * @param mixed $reason use to explain why the array was updated (rather than what the array is used for)
    * @return string
    */                                   
    public function result_array( $description, $line, $function, $file ){
        $array = self::arrayinfo_set(array(), $line, $function, $file );
        $array['description'] = $description;
        $array['outcome'] = true;// boolean
        $array['failreason'] = false;// string - our own typed reason for the failure
        $array['error'] = false;// string - add php mysql wordpress error 
        $array['parameters'] = array();// an array of the parameters passed to the function using result_array, really only required if there is a fault
        $array['result'] = array();// the result values, if result is too large not needed do not use
        return $array;
    }         
    
    /**
    * Get arrays next key (only works with numeric key )
    * 
    * @version 0.2 - return 0 if not array, used to return 1 but no longer a reason to do that
    * @author Ryan Bayne
    */
    public function get_array_nextkey( $array ){
        if(!is_array( $array ) || empty( $array ) ){
            return 0;   
        }
        
        ksort( $array );
        end( $array );
        return key( $array ) + 1;
    }
    
    /**
    * Gets the schedule array from wordpress option table.
    * Array [times] holds permitted days and hours.
    * Array [limits] holds the maximum post creation numbers 
    * 
    * @deprecated part of the old schedule system.
    */
    public static function get_option_schedule_array() {
        $csv2post_schedule_array = get_option( 'csv2post_schedule' );
        return maybe_unserialize( $csv2post_schedule_array );    
    }
    
    /**
    * Builds text link, also validates it to ensure it still exists else reports it as broken.
    * 
    * The idea of this function is to ensure links used throughout the plugins interface
    * are not broken. Over time links may no longer point to a page that exists, we want to 
    * know about this quickly then replace the url.
    * 
    * @return $link, return or echo using $response parameter
    * 
    * @param mixed $text
    * @param mixed $url
    * @param mixed $htmlentities, optional (string of url passed variables)
    * @param string $target, _blank _self etc
    * @param string $class, css class name (common: button)
    * @param strong $response [echo][return]
    */
    public function link( $text, $url, $htmlentities = '', $target = '_blank', $class = '', $response = 'echo', $title = '' ){
        // add ? to $middle if there is no proper join after the domain
        $middle = '';
                                 
        // decide class
        if( $class != '' ){$class = 'class="'.$class.'"';}
        
        // build final url
        $finalurl = $url . $middle . htmlentities( $htmlentities);
        
        // check the final result is valid else use a default fault page
        $valid_result = self::validate_url( $finalurl );
        
        if( $valid_result ){
            $link = '<a href="'.$finalurl.'" '.$class.' target="'.$target.'" title="'.$title.'">'.$text.'</a>';
        }else{
            $linktext = __( 'Invalid Link, Click To Report' );
            $link = '<a href="http://csv2post.wordpress.com/issues/invalid-application-link/" target="_blank">'.$linktext.'</a>';        
        }
        
        if( $response == 'echo' ){
            echo $link;
        }else{
            return $link;
        }     
    }     
    
    /**
    * Updates the schedule array from wordpress option table.
    * Array [times] holds permitted days and hours.
    * Array [limits] holds the maximum post creation numbers 
    * 
    * @deprecated part of the old schedule system.
    */
    public function update_option_schedule_array( $schedule_array ){
        $schedule_array_serialized = maybe_serialize( $schedule_array );
        return update_option( 'csv2post_schedule', $schedule_array_serialized);    
    }
    
    public function update_settings( $csv2post_settings ){
        $admin_settings_array_serialized = maybe_serialize( $csv2post_settings );
        return update_option( 'csv2post_settings', $admin_settings_array_serialized);    
    }
    
    /**
    * Returns WordPress version in short
    * 1. Default returned example by get_bloginfo( 'version' ) is 3.6-beta1-24041
    * 2. We remove everything after the first hyphen
    */
    public function get_wp_version() {
        $longversion = get_bloginfo( 'version' );
        return strstr( $longversion , '-', true );
    }
    
    /**
    * Determines if the giving value is a CSV 2 POST page or not
    */
    public function is_plugin_page( $page){
        return strstr( $page, 'csv2post' );  
    } 
    
    /**
    * Determines if giving tab for the giving page should be displayed or not based on current user.
    * 
    * Checks for reasons not to display and returns false. If no reason found to hide the tab then true is default.
    * 
    * @param mixed $page
    * @param mixed $tab
    * 
    * @return boolean
    */
    public function should_tab_be_displayed( $page, $tab){
        global $CSV2POST_Menu;

        if( isset( $CSV2POST_Menu[$page]['tabs'][$tab]['permissions']['capability'] ) ){
            $boolean = current_user_can( $CSV2POST_Menu[$page]['tabs'][$tab]['permissions']['capability'] );
            if( $boolean ==  false ){
                return false;
            }
        }

        // if screen not active
        if( isset( $CSV2POST_Menu[$page]['tabs'][$tab]['active'] ) && $CSV2POST_Menu[$page]['tabs'][$tab]['active'] == false ){
            return false;
        }    
        
        // if screen is not active at all (used to disable a screen in all packages and configurations)
        if( isset( $CSV2POST_Menu[$page]['tabs'][$tab]['active'] ) && $CSV2POST_Menu[$page]['tabs'][$tab]['active'] == false ){
            return false;
        }
                     
        return true;      
    } 
    
    /**
    * Builds a nonced admin link styled as button by WordPress
    *
    * @package CSV 2 POST
    * @since 8.0.0
    *
    * @return string html a href link nonced by WordPress  
    * 
    * @param mixed $page - $_GET['page']
    * @param mixed $action - examplenonceaction
    * @param mixed $title - Any text for a title
    * @param mixed $text - link text
    * @param mixed $values - begin with & followed by values
    * 
    * @deprecated this method has been moved to the CSV2POST_UI class
    */
    public function linkaction( $page, $action, $title = 'CSV 2 POST admin link', $text = 'Click Here', $values = '' ){
        return '<a href="'. wp_nonce_url( admin_url() . 'admin.php?page=' . $page . '&csv2postaction=' . $action  . $values, $action ) . '" title="' . $title . '" class="button c2pbutton">' . $text . '</a>';
    }
    
    /**
    * Get POST ID using post_name (slug)
    * 
    * @param string $name
    * @return string|null
    */
    public function get_post_ID_by_postname( $name){
        global $wpdb;
        // get page id using custom query
        return $wpdb->get_var( "SELECT ID 
        FROM $wpdb->posts 
        WHERE post_name = '".$name."' 
        AND post_type='page' ");
    }       
    
    /**
    * Returns all the columns in giving database table that hold data of the giving data type.
    * The type will be determined with PHP not based on MySQL column data types. 
    * 1. Table must have one or more records
    * 2. 1 record will be queried 
    * 3. Each columns values will be tested by PHP to determine data type
    * 4. Array returned with column names that match the giving type
    * 5. If $dt is false, all columns will be returned with their type however that is not the main purpose of this function
    * 6. Types can be custom, using regex etc. The idea is to establish if a value is of the pattern suitable for intended use.
    * 
    * @param string $tableName table name
    * @param string $dataType data type URL|IMG|NUMERIC|STRING|ARRAY
    * 
    * @returns false if no record could be found
    */
    public function cols_by_datatype( $tableName, $dataType = false ){
        global $wpdb;
        
        $ra = array();// returned array - our array of columns matching data type
        $matchCount = 0;// matches
        $ra['arrayinfo']['matchcount'] = $matchCount;

        $rec = $wpdb->get_results( 'SELECT * FROM '. $tableName .'  LIMIT 1',ARRAY_A);
        if(!$rec){return false;}
        
        $knownTypes = array();
        foreach( $rec as $id => $value_array ){
            foreach( $value_array as $column => $value ){     
                             
                $isURL = self::is_url( $value );
                if( $isURL){++$matchCount;$ra['matches'][] = $column;}
           
            }       
        }
        
        $ra['arrayinfo']['matchcount'] = $matchCount;
        return $ra;
    }  
    
    public function querylog_bytype( $type = 'all', $limit = 100){
        global $wpdb;

        // where
        $where = '';
        if( $type != 'all' ){
          $where = 'WHERE type = "'.$type.'"';
        }

        // limit
        $limit = 'LIMIT ' . $limit;
        
        // get_results
        $rows = $wpdb->get_results( 
        "
        SELECT * 
        FROM csv2post_log
        ".$where."
        ".$limit."

        ",ARRAY_A);

        if(!$rows){
            return false;
        }else{
            return $rows;
        }
    }  
    
    /**
    * Determines if all tables in a giving array exist or not
    * @returns boolean true if all table exist else false if even one does not
    */
    public function tables_exist( $tables_array ){
        if( $tables_array && is_array( $tables_array ) ){         
            // foreach table in array, if one does not exist return false
            foreach( $tables_array as $key => $table_name){
                $table_exists = $this->DB->does_table_exist( $table_name);  
                if(!$table_exists){          
                    return false;
                }
            }        
        }
        return true;    
    } 
        
    /**
    * Uses wp-admin/includes/image.php to store an image in WordPress files and database
    * from HTTP
    * 
    * @uses wp_insert_attachment()
    * @param mixed $imageurl
    * @param mixed $postid
    * @return boolean false on fail else $thumbid which is stored in post meta _thumbnail_id
    */
    public function create_localmedia_fromhttp( $url, $postid ){ 
        $photo = new WP_Http();
        $photo = $photo->request( $url );
     
        if(is_wp_error( $photo) ){  
            return false;
        }
           
        $attachment = wp_upload_bits( basename( $url ), null, $photo['body'], date( "Y-m", strtotime( $photo['headers']['last-modified'] ) ) );
               
        $file = $attachment['file'];
                
        // get filetype
        $type = wp_check_filetype( $file, null );
                
        // build attachment object
        $att = array(
            'post_mime_type' => $type['type'],
            'post_content' => '',
            'guid' => $url,
            'post_parent' => null,
            'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $attachment['file'] ) ),
        );
       
        // action insert attachment now
        $attach_id = wp_insert_attachment( $att, $file, $postid);
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        wp_update_attachment_metadata( $attach_id,  $attach_data );
        
        return $attach_id;
    }
    
    public function create_localmedia_fromlocalimages( $file_url, $post_id ){           
        require_once(ABSPATH . 'wp-load.php' );
        require_once(ABSPATH . 'wp-admin/includes/image.php' );
        global $wpdb, $csv2post_settings;
               
        if(!$post_id ) {
            return false;
        }

        //directory to import to 
        if( isset( $csv2post_settings['create_localmedia_fromlocalimages']['destinationdirectory'] ) ){   
            $artDir = $csv2post_settings['create_localmedia_fromlocalimages']['destinationdirectory'];
        }else{
            $artDir = 'wp-content/uploads/importedmedia/';
        }

        //if the directory doesn't exist, create it    
        if(!file_exists(ABSPATH . $artDir) ) {
            mkdir(ABSPATH . $artDir);
        }
        
        // get extension
        $ext = pathinfo( $file_url, PATHINFO_EXTENSION);
        
        // do we need to change the new filename to avoid existing files being overwritten?
        $new_filename = basename( $file_url); 

        if (@fclose(@fopen( $file_url, "r") )) { //make sure the file actually exists
            copy( $file_url, ABSPATH . $artDir . $new_filename);

            $siteurl = get_option( 'siteurl' );
            $file_info = getimagesize(ABSPATH . $artDir . $new_filename);

            //create an array of attachment data to insert into wp_posts table
            $artdata = array(
                'post_author' => 1, 
                'post_date' => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql' ),
                'post_title' => $new_filename, 
                'post_status' => 'inherit',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_name' => sanitize_title_with_dashes(str_replace( "_", "-", $new_filename) ),                                            
                'post_modified' => current_time( 'mysql' ),
                'post_modified_gmt' => current_time( 'mysql' ),
                'post_parent' => $post_id,
                'post_type' => 'attachment',
                'guid' => $siteurl.'/'.$artDir.$new_filename,
                'post_mime_type' => $file_info['mime'],
                'post_excerpt' => '',
                'post_content' => ''
            );

            $uploads = wp_upload_dir();
            $save_path = $uploads['basedir'] . '/importedmedia/' . $new_filename;

            //insert the database record
            $attach_id = wp_insert_attachment( $artdata, $save_path, $post_id );

            //generate metadata and thumbnails
            if ( $attach_data = wp_generate_attachment_metadata( $attach_id, $save_path) ) {
                wp_update_attachment_metadata( $attach_id, $attach_data);
            }

            //optional make it the featured image of the post it's attached to
            $rows_affected = $wpdb->insert( $wpdb->prefix.'postmeta', array( 'post_id' => $post_id, 'meta_key' => '_thumbnail_id', 'meta_value' => $attach_id) );
        }else {
            return false;
        }

        return true;        
    }    
    
    /**
    * First function to adding a post thumbnail
    * 
    * @todo create_localmedia_fromlocalimages() needs to be used when image is already local
    * @param mixed $overwrite_existing, if post already has a thumbnail do we want to overwrite it or leave it
    */
    public function create_post_thumbnail( $post_id, $image_url, $overwrite_existing = false ){
        global $wpdb;

        if(!file_is_valid_image( $image_url) ){  
            return false;
        }
             
        // if post has existing thumbnail
        if( $overwrite_existing == false ){
            if ( get_post_meta( $post_id, '_thumbnail_id', true) || get_post_meta( $post_id, 'skip_post_thumb', true ) ) {
                return false;
            }
        }
        
        // call action function to create the thumbnail in wordpress gallery 
        $thumbid = self::create_localmedia_fromhttp( $image_url, $post_id );
        // or from create_localmedia_fromlocalimages()  
        
        // update post meta with new thumbnail
        if ( is_numeric( $thumbid) ) {
            update_post_meta( $post_id, '_thumbnail_id', $thumbid );
        }else{
            return false;
        }
    }
    
    /**
    * builds a url for form action, allows us to force the submission to specific tabs
    */
    public function form_action( $values_array = false ){
        $get_values = '';

        // apply passed values
        if(is_array( $values_array ) ){
            foreach( $values_array as $varname => $value ){
                $get_values .= '&' . $varname . '=' . $value;
            }
        }
        
        echo self::url_toadmin( $_GET['page'], $get_values);    
    }
    
    /**
    * count the number of posts in the giving month for the giving post type
    * 
    * @param mixed $month
    * @param mixed $year
    * @param mixed $post_type
    */
    public function count_months_posts( $month, $year, $post_type){                    
        $countposts = get_posts( "year=$year&monthnum=$month&post_type=$post_type");
        return count( $countposts);    
    }     
    
    /**
    * adds project id to sources for that project 
    */
    public function update_sources_withprojects( $project_id, $sourcesid_array ){
        global $wpdb;
        foreach( $sourcesid_array as $key => $soid ){
            $this->DB->update( $wpdb->c2psources, 'sourceid = ' . $soid, array( 'projectid' => $project_id) );
        }    
    }
    
    /**
    * Update data source in c2psources table: updates path value and the config array which holds path
    * 
    * @uses get_source
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.0.2
    *
    * @param mixed $path
    * @param mixed $sourceid
    * @param mixed $theconfig_array
    */
    public function update_source_path( $new_path, $sourceid ){
        global $wpdb;
        
        // get the source data
        $source_array = $this->DB->get_source( $sourceid );
                
        // update the configuration array also 
        $theconfig_array = maybe_unserialize( $source_array->theconfig );
        $theconfig_array['submittedpath'] = $new_path;
        $theconfig_array['fullpath'] = $new_path;
                
        return $this->DB->update( $wpdb->c2psources, "sourceid = $sourceid", array( 'path' => $new_path, 'theconfig' => maybe_serialize( $theconfig_array ) ) );            
    }        
    /**
    * update specific columns in project table
    * 
    * @param mixed $project_id
    * @param mixed $fields an array of columns and new value i.e. array( 'categories' => maybe_serialize( $categories_array ) )
    */
    public function update_project( $project_id, $fields, $settingschange = false ){
        global $wpdb;
        
        // general timestamp, do not use this field for triggering post updating as it changes for the slightest thing
        $autofields = array( 'timestamp' => current_time( 'mysql' ) );
        
        // if project settings are being changed we store the time so the plugin knows which posts need to be updated
        if( $settingschange === true){$autofields['settingschange'] = current_time( 'mysql' );}
        
        $fields = array_merge( $fields, $autofields);
        return $this->DB->update( $wpdb->c2pprojects, "projectid = $project_id", $fields);   
    }       

    /**
    * Get the rules array for a projects datasource.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.2
    */    
    public function get_rules_array( $project_id ){
        global $wpdb;
        $query_results = $this->DB->selectwherearray( $wpdb->c2psources, 'projectid = ' . $project_id, 'sourceid', 'rules' );
        $unser = maybe_unserialize( $query_results['rules'] );
        if(!is_array( $unser ) ){
            return array();
        }else{
            return $unser;
        }
    }    
    
    /**
    * adds a new rule to the rules array in giving sources row in c2psources table
    * 
    * @param mixed $source_id
    * @param mixed $rule_type datatype,uppercaseall,lowercaseall,roundnumberup,roundnumberdown,captalizefirstletter
    * @param mixed $rule may be a single value i.e. a data type for the "datatype" $ruletype or it may be an array for a more complex rule
    */
    public function add_new_data_rule( $source_id, $rule_type, $rule, $column ){
        global $wpdb;
        $rules_array = $this->get_data_rules_source( $source_id );
        
        // add rule to array                                                          
        $rules_array[$rule_type][$column] = $rule;   
        
        // serialize 
        $rules_array = maybe_serialize( $rules_array );
                                               
        $this->DB->update( $wpdb->c2psources, "sourceid = $source_id", array( 'rules' => $rules_array ) );
    }
    
    public function delete_data_rule( $source_id, $rule_type, $column){
        global $wpdb;
        $rules_array = $this->get_data_rules_source( $source_id );
        unset( $rules_array[$rule_type][$column] );
        // serialize 
        $rules_array = maybe_serialize( $rules_array );
                                               
        $this->DB->update( $wpdb->c2psources, "sourceid = $source_id", array( 'rules' => $rules_array ) );            
    }   
    
    /**
    * returns an array of rules if any, can return empty array
    * 
    * @param numeric $source_id
    */
    public function get_data_rules_source( $source_id ){
        global  $wpdb;
        $row = $this->DB->selectrow( $wpdb->c2psources, "sourceid = $source_id", 'rules' );
        
        // initiate the rules array
        if(is_null( $row->rules) ){
            return array();
        }else{
            return maybe_unserialize( $row->rules );
        }                      
    }    
    
    /**
    * gets the rules column value (null or array ) for the parent source
    * 
    * use for join and append projects that have multiple sources going into one database table.
    * This is because the rule forms will submit only one table. In procedures where we loop through the 2nd and 3rd rules
    * we must compare columns to the rules array in the parent source.
    * 
    * @param mixed $project_id
    */
    public function get_parent_rulesarray( $project_id ){
        global $wpdb;                                                                                       
        $rules_array = $this->get_value( 'rules', $wpdb->c2psources, "projectid = $project_id AND parentfileid = 0" );
        return maybe_unserialize( $rules_array );
    }
    
    /**
    * applies data rules to a single array imported from csv file
    * 1. data source is used to get row from csv file, so we know we are working with the rules for that file + table
    * 2. rules array is for a single data source and a single data source is for a single file
    * 
    * @param mixed $insertready_array
    * @param mixed $rules_array
    */
    public function apply_rules( $insertready_array, $rules_array, $row_id){
        global $wpdb;    

        foreach( $insertready_array as $column => $value ){

            if( isset( $rules_array['splitter'] )
            && isset( $rules_array['splitter']['datasplitertable'] ) 
            && $rules_array['splitter']['datasplitercolumn'] == $column){
                
                $table = $rules_array['splitter']['datasplitertable'];
                
                $exploded = explode( $rules_array['splitter']['separator'], $value,5);
                
                for( $i=1;$i<=count( $exploded);$i++){
                    $receiving_column = $rules_array['splitter']["receivingcolumn$i"];
                    $x = $i - 1;
                    $category_array[$receiving_column] = $exploded[$x];
                }              

                $this->DB->update( $table, "c2p_rowid = $row_id", $category_array );
            } 
                        
            // round number up (roundnumberupcolumns)
            if( isset( $rules_array['roundnumberupcolumns'] ) && isset( $rules_array['roundnumberupcolumns'][$column] ) ){
                if(is_numeric( $value ) ){
                    $value = ceil( $value ); 
                }    
            }
            
            // round number (roundnumbercolumns)
            if( isset( $rules_array['roundnumbercolumns'] ) && isset( $rules_array['roundnumbercolumns'][$column] ) ){
                if(is_numeric( $value ) ){
                    $value = round( $value ); 
                }                
            }
                        
            // make first character uppercase (captalizefirstlettercolumns) 
            if( isset( $rules_array['captalizefirstlettercolumns'] ) && isset( $rules_array['captalizefirstlettercolumns'][$column] ) ){
                $value = ucwords( $value );    
            }
                    
            // make entire string lower case (lowercaseallcolumns)
            if( isset( $rules_array['lowercaseallcolumns'] ) && isset( $rules_array['lowercaseallcolumns'][$column] ) ){
                $value = strtolower( $value );    
            }
                        
            // make entire string upper case (uppercaseallcolumns)
            if( isset( $rules_array['lowercaseallcolumns'] ) && isset( $rules_array['lowercaseallcolumns'][$column] ) ){
                $value = strtoupper( $value );        
            }
            
            if( isset( $rules_array['pluralizecolumns'] ) && isset( $rules_array['pluralizecolumns'][$column] ) ){
                $value = self::pluralize(2, $value, false );            
            }
           
            // update the main array
            $insertready_array[$column] = $value;
        }
       
        return $insertready_array;
    }

    public function get_parentsource_id( $project_id ){
        global $wpdb;                                                                                       
        return $this->DB->get_value( 'sourceid', $wpdb->c2psources, "projectid = $project_id AND parentfileid = 0");
    }        
    
    public function does_project_table_exist( $project_id ){
        $table_name = $this->DB->get_project_main_table( $project_id );   
        return $this->DB->does_table_exist( $table_name );     
    }   
    
    /**
    * get specific number of updated rows that have not been applied to their post since
    * a projects settings has been changed 
    *        
    * @param mixed $project_id
    * @param mixed $total
    * @param mixed $idcolumn
    */
    public function get_outdatedpost_rows( $project_id, $total = 1, $idcolumn = false ){
        $tables_array = $this->DB->get_dbtable_sources( $project_id); 
        $settingschange = $this->DB->get_project( $project_id, 'settingschange' );   
        return $this->DB->query_multipletables( $tables_array[0], $idcolumn, "c2p_postid != 0 AND '".$settingschange."' > c2p_applied", $total);
    }
    
    /**
    * Create new posts/pages
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.3
    * 
    * @param mixed $project_id
    * @param mixed $total - apply a limit for this import (global settings can offer a default limit suitable for server also)
    * @param mixed $row_ids - only use if creating posts using specific row ID's 
    */
    public function create_posts( $project_id, $total = 1 ){
        global $csv2post_settings;
        
        $autoblog = new CSV2POST_InsertPost();
        $autoblog->settings = $csv2post_settings;
        $autoblog->currentproject = $csv2post_settings['currentproject'];
        $autoblog->project = $this->DB->get_project( $project_id );// gets project row, includes sources and settings
        $autoblog->projectid = $project_id;
        $autoblog->maintable = $this->DB->get_project_main_table( $project_id );// main table holds post_id and progress statistics
        $autoblog->projectsettings = maybe_unserialize( $autoblog->project->projectsettings );// unserialize settings
        $autoblog->projectcolumns = $this->DB->get_project_columns_from_db( $project_id );
        $autoblog->requestmethod = 'manual';
        $sourceid_array = $this->DB->get_project_sourcesid( $project_id );
        $autoblog->mainsourceid = $sourceid_array[0];// update the main source database table per post with new post ID
        unset( $autoblog->project->projectsettings);// just simplifying the project object by removing the project settings

        $idcolumn = false;
        if( isset( $autoblog->projectsettings['idcolumn'] ) ){
            $idcolumn = $autoblog->projectsettings['idcolumn'];    
        }
        
        // get rows not used to create posts
        $unused_rows = $this->DB->get_unused_rows( $project_id, $total, $idcolumn );  
        if(!$unused_rows){
            $this->UI->create_notice( __( 'You have used all imported rows to create posts. Please ensure you have imported all of your data if you expected more posts than CSV 2 POST has already created.' ), 'info', 'Small', 'No Rows Available' );
            return;
        }
        
        // we will control how and when we end the operation
        $autoblog->finished = false;// when true, output will be complete and foreach below will discontinue, this can be happen if maximum execution time is reached
        
        $foreach_done = 0;
        foreach( $unused_rows as $key => $row){
            ++$foreach_done;
                    
            // to get the output at the end, tell the class we are on the final post, only required in "manual" requestmethod
            if( $foreach_done == $total){    
                $autoblog->finished = true;// not completely finished, indicates this is the last post
            }
            
            // pass row to $autob
            $autoblog->row = $row;    
            // create a post - start method is the beginning of many nested functions
            $autoblog->start();
        }
    }
    
    /**
    * Update one or more posts
    * 1. can pass a post ID and force update even if imported row has not changed
    * 2. Do not pass a post ID and query is done to get changed imported rows only to avoid over processing
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.0.2
    * 
    * @param integer $project_id
    * @param integer $total
    * @param mixed $post_id boolean false or integer post ID
    * @param array $atts
    */
    public function update_posts( $project_id, $total = 1, $post_id = false, $atts = array() ){
        global $csv2post_settings;
        
        extract( shortcode_atts( array( 
            'rows' => false
        ), $atts ) );
                
        $autoblog = new CSV2POST_UpdatePost();
        $autoblog->settings = $csv2post_settings;
        $autoblog->currentproject = $csv2post_settings['currentproject'];// dont automatically use by default, request may be for specific project
        $autoblog->project = $this->DB->get_project( $project_id);// gets project row, includes sources and settings
        $autoblog->projectid = $project_id;
        $autoblog->maintable = $this->DB->get_project_main_table( $project_id);// main table holds post_id and progress statistics
        $autoblog->projectsettings = maybe_unserialize( $autoblog->project->projectsettings);// unserialize settings
        $autoblog->projectcolumns = $this->DB->get_project_columns_from_db( $project_id);
        $autoblog->requestmethod = 'manual';
        $sourceid_array = $this->DB->get_project_sourcesid( $project_id);
        $autoblog->mainsourceid = $sourceid_array[0];// update the main source database table per post with new post ID
        unset( $autoblog->project->projectsettings );// simplifying the object by removing the project settings

        // we will control how and when we end the operation
        $autoblog->finished = false;// when true, output will be complete and foreach below will discontinue, this can be happen if maximum execution time is reached
        
        $idcolumn = false;
        if( isset( $autoblog->projectsettings['idcolumn'] ) ){
            $idcolumn = $autoblog->projectsettings['idcolumn'];    
        }
                               
        // get rows updated and not yet applied, this is a default query
        // pass a query result to $updated_rows to use other rows
        if( $post_id === false ){
            $updated_rows = $this->DB->get_updated_rows( $project_id, $total, $idcolumn);
        }else{
            $updated_rows = self::get_posts_record( $project_id, $post_id, $idcolumn);
        }
        
        if( !$updated_rows ){
            $this->UI->create_notice( __( 'None of your imported rows have been updated since their original import.' ), 'info', 'Small', 'No Rows Updated' );
            return;
        }
            
        $foreach_done = 0;
        foreach( $updated_rows as $key => $row){
            ++$foreach_done;
                        
            // to get the output at the end, tell the class we are on the final post, only required in "manual" requestmethod
            if( $foreach_done == $total){
                $autoblog->finished = true;
            }            
            // pass row to $autob
            $autoblog->row = $row;    
            // create a post - start method is the beginning of many nested functions
            $autoblog->start();
        }                  
    }
    
    /**
    * determines if the giving term already exists within the giving level
    * 
    * this is done first by checking if the term exists in the blog anywhere at all, if not then it is an instant returned false.
    * if a match term name is found, then we investigate its use i.e. does it have a parent and does that parent have a parent. 
    * we count the number of levels and determine the existing terms level
    * 
    * if term exists in level then that terms ID is returned so that we can make use of it
    * 
    * @param mixed $term_name
    * @param mixed $level                           
    * 
    * @deprecated Categories class created
    */
    public function term_exists_in_level( $term_name = 'No Term Giving', $level = 0){                 
        global $wpdb;
        $all_terms_array = $this->DB->selectwherearray( $wpdb->terms, "name = '$term_name'", 'term_id', 'term_id' );
        if(!$all_terms_array ){return false;}

        $match_found = false;
                
        foreach( $all_terms_array as $key => $term_array ){
                     
            $term = get_term( $term_array['term_id'], 'category',ARRAY_A);

            // if level giving is zero and the current term does not have a parent then it is a match
            // we return the id to indicate that the term exists in the level
            if( $level == 0 && $term['parent'] === 0){      
                return $term['term_id'];
            }
             
            // get the current terms parent and the parent of that parent
            // keep going until we reach level one
            $toplevel = false;
            $looped = 0;    
            $levels_counted = 0;
            $parent_termid = $term['parent'];
            while(!$toplevel){    
                                
                // we get the parent of the current term
                $category = get_category( $parent_termid );  

                if( is_wp_error( $category )|| !isset( $category->category_parent ) || $category->category_parent === 0){
                    
                    $toplevel = true;
                    
                }else{ 
                    
                    // term exists and must be applied as a parent for the new category
                    $parent_termid = $category->category_parent;
                    
                }
                      
                ++$looped;
                if( $looped == 20){break;}
                
                ++$levels_counted;
            }  
            
            // so after the while we have a count of the number of levels above the "current term"
            // if that count + 1 matches the level required for the giving term term then we have a match, return current term_id
            $levels_counted = $levels_counted;
            if( $levels_counted == $level){
                return $term['term_id'];
            }       
        }
                  
        // arriving here means no match found, either create the term or troubleshoot if there really is meant to be a match
        return false;
    }
    
    /**
    * Deletes a datasource from the wp_c2psources table
    * 
    * @returns the result from WP delete query
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function delete_datasource( $source_id ) {
        global $wpdb;
        return $this->DB->delete( $wpdb->c2psources, 'sourceid = ' . $source_id );    
    }
    
    /**
    * checks for a new .csv file and can switch to that file for import.
    * 
    * This function is called during a manual request. Another method named detect_new_files()
    * is now being written. It will be a simplier version of this that focuses on dealing with new
    * files, not dealing with potential issues detected. I will start putting those checks into
    * individual methods.
    * 
    * @returns array $result_array outcome is boolean, false means no changes detected, true means there was new file or existing file modified
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function recheck_source_directory( $source_id, $switch = false ) {       
        
        $result_array = array( 'outcome' => false,// boolean, change to true to indicate a change detected  
                               'error' => false,// boolean, not PHP error, this is for a problem with the directory 
                               'message' => __( 'No message.', 'csv2post'),
        );
        
        // get source data
        $source_row = $this->DB->get_source( $source_id );
        if( !$source_row ) { 
            $result_array['outcome'] = false;
            $result_array['error'] = true;
            $result_array['message'] = __( 'Source data not found, this should be reported to WebTechGlobal.', 'csv2post' );
            return $result_array;
        }
        
        // ensure we have a clean path
        $directory_path = trailingslashit( $source_row->directory );
        
        // it would be best to check this before now, for the users attention
        // but lets ensure the directory column has a value
        if( !isset( $directory_path ) || !is_string( $directory_path ) ) {
            $result_array['outcome'] = false;
            $result_array['error'] = true;
            $result_array['message'] = __( 'Source data does not include a directory value. This would happen in versions before 8.1.32 and is a minor issue WebTechGlobal can help you with.', 'csv2post' );
            return $result_array; 
        }
        
        // get the newest file in the directory - currently this is done based on last change
        // time, which means changing an old file would cause a switch, should not happen though
        // but it might serve someone
        $newest_file = $this->Files->directories_newest_file( $directory_path, 'csv' );
                   
        // get basename for the current main file, that is just the file in the "path" value now
        $current_main_file = basename( $source_row->path );
          
        // if current main file is still the newest nothing needs to be done          
        if( $newest_file === $current_main_file ) {
            $result_array['outcome'] = false;  
            $result_array['error'] = false;// normal operation should lead to a green notice  
            $result_array['message'] = __( 'Your sources directory does not have a new file since CSV 2 POST last checked.', 'csv2post' );  
                
            return $result_array;          
        }

        // arriving here indicates there is a newer file in play, do we switch to the new file ?
        if( $switch ) {
            // before the switch, must ensure newer file configuration matches the old
            $comparison_results = $this->Files->compare_csv_files( $directory_path . $newest_file, $directory_path . $current_main_file );
        
            if( $comparison_results['outcome'] = true ) { 
                // apply the new path as the current/primary document for processing
                self::update_source_path( $directory_path . $newest_file, $source_id ); 
                $result_array['outcome'] = true;  
                $result_array['error'] = false;// normal operation should lead to a green notice  
                $result_array['message'] = __( 'A new .csv file was found in your sources directory. The plugin has switched to the new file', 'csv2post' );                                                                
            }
        }    

        return $result_array;
    }              

    /**
    * Get two or more projects - use get_project() for a single project.
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.2.0
    * @version 1.0
    */
    public function get_projects() {
        global $wpdb;
        return $this->DB->selectorderby( $wpdb->c2pprojects, null, 'projectid', '*', null, 'ARRAY_A' );
    } 
    
    /**
    * Process column replacement tokens.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.2.1
    * @version 1.0
    */
    public function replace_tokens( $subject, $imported_row, $project_columns ){
        foreach( $imported_row as $columnfromquery => $usersdata ){ 
            foreach( $project_columns as $table_name => $columnfromdb ){   
                $subject = str_replace( '#'. $columnfromquery . '#', $usersdata, $subject);
            }    
        }         
        return $subject;
    }   
    
    /**
    * Updates projects main table with post ID - creating
    * a relationship with post and record that can be used
    * in future for reliable post updating.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.2.1
    * @version 1.0
    */
    public function pair_record_with_post( $table, $post_id, $row_id ) {        
        global $wpdb;
        
        $query = "UPDATE " . $table; 
        $query .= " SET c2p_postid = " . $post_id . ", " . "c2p_applied = '" . current_time( 'mysql' ) . "'";
        $query .= " WHERE c2p_rowid = " . $row_id;
        return $wpdb->query( $query ); 
    }
    
    /**
    * Add the default post meta to posts - required for even basic operations.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.0
    */
    public function insert_plugins_postmeta( $project_id, $post_id, $mainsource_id, $row_id ) {
        // add data source ID for querying source
        add_post_meta( $post_id, 'c2p_project', $project_id );
                
        // add data source ID for querying source
        add_post_meta( $post_id, 'c2p_datasource', $mainsource_id );
                                                   
        // add meta to hold the data row ID for querying row that was used to create post
        add_post_meta( $post_id, 'c2p_rowid', $row_id );        
        
        // update date and time will be compared against the row time and projects timestamp
        add_post_meta( $post_id, 'c2p_updated',current_time( 'mysql' ) );    
    }
                
}// end CSV2POST class 

if(!class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* Use to insert a new post, class systematically calls all methods one after the other building the $my_post object
* 
* 1. methods are in alphabetical order
* 2. each method calls the next one in the list
* 3. eventually a method creates the post using the $my_post object built along the way
* 4. $my_post is then used to add custom fields, thumbnail/featured image and other attachments or meta
* 5. many methods check for their target value to exist in $my_post already and instead alter it (meaning we can re-call the class on an object)
* 6. some methods check for values in the $my_post object and perform procedures based on the values found or not found
* 
* @author Ryan Bayne
* @package CSV 2 POST
* @since 8.0.0
* @version 2.1.10
*/
class CSV2POST_InsertPost{
    public $my_post = array();
    public $report_array = array();// will include any problems detected, can be emailed to user    
    
    public function replace_tokens( $subject ){
        unset( $this->projectcolumns['arrayinfo'] );
        foreach( $this->row as $columnfromquery => $usersdata ){ 
            foreach( $this->projectcolumns as $table_name => $columnfromdb ){   
                $subject = str_replace( '#'. $columnfromquery . '#', $usersdata, $subject);
            }    
        }         
        return $subject;
    } 
    
    /**
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function __construct() {
        $this->CSV2POST = new CSV2POST();
    }
    
    /**
    * This is not like a __construct() - this called to create a single post,
    * often while looping through data
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.1
    */
    public function start() { 
        $this->my_post = array();  
        $this->replacevaluerules();
    }
    
    /**
    * rules for replacing specific values in selected columns with other content 
    */
    public function replacevaluerules() {
      
        if( isset( $this->projectsettings['content']['valuerules'] ) && is_array( $this->projectsettings['content']['valuerules'] ) ){
            
            foreach( $this->projectsettings['content']['valuerules'] as $key => $rule_array ){
                               
                // if we do not have all the values we require in this rule just continue to the next item
                if(!isset( $rule_array['column'] ) ){continue;}
                if(!isset( $rule_array['vrvdatavalue'] ) ){continue;}             
                if(!isset( $rule_array['vrvreplacementvalue'] ) ){continue;}                         
                       
                // ensure the column is set in the $row
                if( isset( $this->row[ $rule_array['column'] ] ) ){     
                    // does our value match the data         
                    if( $this->row[ $rule_array['column'] ] == $rule_array['vrvdatavalue'] ){       
                        $this->row[ $rule_array['column'] ] = $rule_array['vrvreplacementvalue'];
                    }
                }    
            }              
        }        

        $this->author();
    }
    
    /**
    * decide author (from data or a default author)
    */
    public function author() {      
        $author_set = false;
        
        // check projects author settings for author data          
        if( isset( $this->projectsettings['authors']['email']['column'] ) && !empty( $this->projectsettings['authors']['email']['column'] )
        && isset( $this->projectsettings['authors']['username']['column'] ) && !empty( $this->projectsettings['authors']['username']['column'] ) ){
            // check data $row for a numeric value
            if( isset( $this->row[ $this->projectsettings['authors']['email']['column'] ] ) && is_numeric( $this->row[ $this->projectsettings['authors']['email']['column'] ] ) ){
                $this->my_post['post_author'] = $this->row[ $this->projectsettings['authors']['email']['column'] ];
                $author_set = true;
            }
        }
                                   
        // if not $author_set check for a project default author
        if( !$author_set && isset( $this->projectsettings['basicsettings']['defaultauthor'] ) && is_numeric( $this->projectsettings['basicsettings']['defaultauthor'] ) ){
            $this->my_post['post_author'] = $this->projectsettings['basicsettings']['defaultauthor']; 
        }
        
        // call next method
        $this->categories();
    } 
    
    /**
    * Category creator
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.1
    */
    public function categories() {
        if( isset( $this->projectsettings['categories'] ) ){  
            if( isset( $this->projectsettings['categories']['data'] ) ){
                
                $projection_array = array();
                $group_array = array();// we will store each cat ID in this array (use to apply parent and store in project table)
                $level = 0;// in theory this should increment, so that is a good check to do when debugging
                
                // loop through all values in $row
                unset( $this->projectcolumns['arrayinfo'] );
                
                // loop through all columns/values in the row of data
                foreach( $this->row as $columnfromquery => $my_term ){ 
                    
                    // loop through all project columns
                    foreach( $this->projectcolumns as $table_name => $columnfromdb ){
               
                        // determine if $columnfromquery and $table_name are selected category data columns
                        // also establish the level by using array of category column data 
                        foreach( $this->projectsettings['categories']['data'] as $thelevel => $catarray ){
                            
                            if( $catarray['table'] == $table_name && $catarray['column'] == $columnfromquery ){
                                $level = $thelevel;
                    
                                // is post manually mapped to this table + column + term
                                if( isset( $categories_array['categories']['mapping'][$table_name][$columnfromdb][ $my_term ] ) ){
                                    
                                    // we apply $level here, this is important in this procedure because the order we encounter category terms
                                    // within data may not be in the level order, possibly!
                                    $group_array[$level] = $categories_array['categories']['mapping'][$table_name][$columnfromdb][ $my_term ];
                                
                                }else{
                                    
                                    // does term exist within the current level?  
                                    $existing_term_id = $this->CSV2POST->term_exists_in_level( $my_term, $level );                                              
                                    if(is_numeric( $existing_term_id) ){
                                        
                                        $group_array[$level] = $existing_term_id;
                                        
                                    }else{
                                        
                                        // set parent id, by deducting one from the current $level and getting the previous category from $group_array
                                        $parent_id = 0;
                                        if( $level > 0 ){
                                            $parent_keyin_group = $level - 1;
                                            
                                            if( !isset( $group_array[$parent_keyin_group] ) ) {
                                                $parent_id = 0;// $parent_keyin_group encounter no in array, possibly level one or third or fourth when second level no selected    
                                            } else {
                                                $parent_id = $group_array[$parent_keyin_group];    
                                            }
                                               
                                        }
                                        
                                        // create a new category term with a parent (if first category it parent is 0)
                                        $new_cat_id = wp_create_category( $my_term, $parent_id );
                                          
                                        if( isset( $new_cat_id ) && is_numeric( $new_cat_id ) ){
                                            $group_array[$level] = $new_cat_id;    
                                        }
                                    }
                                }                             
                            }
                        }
                    }    
                }
            }
            
            if( isset( $group_array ) && is_array( $group_array ) ){
                $this->my_post['post_category'] = $group_array;
            }    
                     
        } 
        
        // apply default category if post has none
        if( empty( $this->my_post['post_category'] ) && isset( $this->projectsettings['basicsettings']['defaultcategory'] ) && is_numeric( $this->projectsettings['basicsettings']['defaultcategory'] ) ) {
            $this->my_post['post_category'] = array( $this->projectsettings['basicsettings']['defaultcategory'] );
        }  
   
        // call next method
        $this->commentstatus();
    }  
      
    public function commentstatus() {
        
        if( isset( $this->projectsettings['basicsettings']['commentstatus'] ) && is_numeric( $this->projectsettings['basicsettings']['commentstatus'] ) ){
            $this->my_post['comment_status'] = $this->projectsettings['basicsettings']['commentstatus']; 
        }
                
        // call next method
        $this->content();        
    }   
     
    public function content() {
        
        if( isset( $this->projectsettings['content']['wysiwygdefaultcontent'] ) ){
            $this->my_post['post_content'] = $this->replace_tokens( $this->projectsettings['content']['wysiwygdefaultcontent'] );    
        }    
        
        if(!isset( $this->my_post['post_content'] ) || empty( $this->my_post['post_content'] ) ){
            $this->my_post['post_content'] = __( 'No Post Content Setup' );    
        }
                
        // call next method
        $this->customfields();        
    } 
           
    /**
    * does not create meta data, only establishes what meta data is to be created and it is created later
    * it is done this way so that rules can take meta into consideration and meta can be adjusted prior to post creation 
    */
    public function customfields() {
        $this->my_post['newcustomfields'] = array();// we will add keys and values to this, the custom fields are created later    
        $i = 0;// count number of custom fields, use it as array key
        
        // seo title meta
        if( isset( $this->projectsettings['customfields']['seotitletemplate'] ) && !empty( $this->projectsettings['customfields']['seotitletemplate'] )
        && isset( $this->projectsettings['customfields']['seotitlekey'] ) && !empty( $this->projectsettings['customfields']['seotitlekey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seotitletemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seotitlekey']; 
            ++$i; 
        }
        
        // seo description meta
        if( isset( $this->projectsettings['customfields']['seodescriptiontemplate'] ) && !empty( $this->projectsettings['customfields']['seodescriptiontemplate'] )
        && isset( $this->projectsettings['customfields']['seodescriptionkey'] ) && !empty( $this->projectsettings['customfields']['seodescriptionkey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seodescriptiontemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seodescriptionkey'];  
            ++$i; 
        }
                
        // seo keywords meta
        if( isset( $this->projectsettings['customfields']['seokeywordstemplate'] ) && !empty( $this->projectsettings['customfields']['seokeywordstemplate'] )
        && isset( $this->projectsettings['customfields']['seokeywordskey'] ) && !empty( $this->projectsettings['customfields']['seokeywordskey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seokeywordstemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seokeywordskey']; 
            ++$i;       
        }       
        
        // now add all other custom fields
        if( isset( $this->projectsettings['customfields']['cflist'] ) ){
            foreach( $this->projectsettings['customfields']['cflist'] as $key => $cf){
                $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $cf['value'] );
                $this->my_post['newcustomfields'][$i]['name'] = $cf['name'];                
                ++$i;     
            }
        }

        // call next method
        $this->poststatus();        
    }
    
    public function poststatus() {
        
        if( isset( $this->projectsettings['basicsettings']['poststatus'] ) ){
            $this->my_post['post_status'] = $this->projectsettings['basicsettings']['poststatus'];
        }else{
            $this->my_post['post_status'] = 'draft';
        }
        
        // call next method
        $this->excerpt();        
    }
    
    public function excerpt() {
        
        // call next method
        $this->format();         
    }  
      
    public function format() {
        
       if( isset( $this->projectsettings['postformat'] ) ){
            if( isset( $this->projectsettings['postformat']['title_table'] ) && isset( $this->projectsettings['postformat']['title_column'] ) ){
                if( isset( $record_array[ $this->projectsettings['postformat']['title_column'] ] ) && is_string( $record_array[ $this->projectsettings['postformat']['title_column'] ] ) ){
                    wp_set_post_terms( $this->my_post['ID'], 'post-format-'.$record_array[ $this->projectsettings['postformat']['title_column'] ], 'post_format' ); 
                }   
            }elseif( isset( $this->projectsettings['postformat']['default'] ) ){
                wp_set_post_terms( $this->my_post['ID'], 'post-format-'.$this->projectsettings['postformat']['default'], 'post_format' );    
            }
        }   

        // call next method
        $this->permalink();        
    }   
    
    public function permalink() {

        if( isset( $this->projectsettings['permalinks']['column'] ) && is_string( $this->projectsettings['permalinks']['column'] ) && !empty( $this->projectsettings['permalinks']['column'] ) ){
            $this->my_post['post_name'] = $this->row[ $this->projectsettings['permalinks']['column'] ]; 
        }
                
        // call next method
        $this->pingstatus();        
    }  
      
    public function pingstatus() {
        
        if( isset( $this->projectsettings['basicsettings']['pingstatus'] ) ){
            $this->my_post['ping_status'] = $this->projectsettings['basicsettings']['pingstatus']; 
        }
        
        // call next method
        $this->posttype();        
    }
    
    /**
    * applys post type based on rules
    * 1. if multiple rules apply to the $row the last rule and post type is the one used 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.0.0
    * @version 1.1
    */
    public function posttype() {
        if( isset( $this->projectsettings['posttypes'] ) ){
            for( $i=1;$i<=3;$i++){
                if( isset( $this->projectsettings['posttypes']["posttyperule$i"]['column'] )
                && isset( $this->projectsettings['posttypes']["posttyperuletrigger$i"] )
                && isset( $this->projectsettings['posttypes']["posttyperuleposttype$i"] )
                && isset( $this->row[ $this->projectsettings['posttypes']["posttyperule$i"]['column'] ] )
                && $this->row[ $this->projectsettings['posttypes']["posttyperule$i"]['column'] ] === $this->projectsettings['posttypes']["posttyperuletrigger$i"] ){
                    $my_post['post_type'] = $this->projectsettings['posttypes']["posttyperuleposttype$i"];            
                }
            }            
        }    

        if( !isset( $this->my_post['post_type'] ) && isset( $this->projectsettings['basicsettings']['defaultposttype'] ) ) {
            $this->my_post['post_type'] = $this->projectsettings['basicsettings']['defaultposttype'];
        }
                
        // call next method
        $this->publishdate();         
    }  
      
    public function publishdate() {
        if( isset( $this->projectsettings['dates']['publishdatemethod'] ) && is_string( $this->projectsettings['dates']['publishdatemethod'] ) ){
            switch ( $this->projectsettings['dates']['publishdatemethod'] ) {
               case 'data':
                    if( isset( $this->projectsettings['dates']['column'] ) && is_string( $this->projectsettings['dates']['column'] ) ){
                        $strtotime = strtotime( $this->row[$this->projectsettings['dates']['column']] );
                        $this->my_post['post_date'] = date( "Y-m-d H:i:s", $strtotime);
                    }
                 break;  
               case 'incremental':
            
                // establish minutes increment
                $increment = rand( $this->projectsettings['dates']['naturalvariationlow'], $this->projectsettings['dates']['naturalvariationhigh'] );    

                // get start date/time - this is updated per post so that the next post increments properly
                $start_time = strtotime( $this->projectsettings['dates']['incrementalstartdate'] );
       
                // add increment to start time to establish publish time
                $publish_seconds = $start_time + $increment; 
        
                $this->my_post['post_date'] =  date( "Y-m-d H:i:s", $publish_seconds );
                $this->my_post['post_date_gmt'] = gmdate( "Y-m-d H:i:s", $publish_seconds ); 
                
                // update project array with latest date
                $this->projectsettings['dates']['incrementalstartdate'] = date( 'Y-m-d H:i:s', $publish_seconds);               

                 break;
               case 'random':
                    
                    // establish start time and end time
                    $start_time = strtotime( $this->projectsettings['dates']['randomdateearliest'] );
                    $end_time = strtotime( $this->projectsettings['dates']['randomdatelatest'] );            
                    
                    // make random time between our start and end
                    $publish_time = rand( $start_time, $end_time );
                    
                    $this->my_post['post_date'] =  date( "Y-m-d H:i:s", $publish_time );
                    $this->my_post['post_date_gmt'] = gmdate( "Y-m-d H:i:s", $publish_time );
              
                 break;
            }        
        }                

        // call next method
        $this->tags();        
    }  
     
    public function tags() {
    	
        if( isset( $this->projectsettings['tags'] ) && is_array( $this->projectsettings['tags'] ) ){
            
            // set excluded tags array
            if( isset( $this->projectsettings['tags']['excludedtags'] ) ){
                if( is_array( $this->projectsettings['tags']['excludedtags'] ) ){
                    $excluded_tags = $this->projectsettings['tags']['excludedtags'];
                }else{
                    $excluded_tags = explode( ', ', $this->projectsettings['tags']['excludedtags'] );
                }
            }
            
            $final_tags_array = array();
            // get tags data, break down into array using comma
            if( $this->projectsettings['tags']['column'] ){
                $exploded_tags = explode( ', ', $this->row[ $this->projectsettings['tags']['column'] ] );
                $final_tags_array = array_merge( $final_tags_array, $exploded_tags);
            }
            
            // generate tags from text data
            if( isset( $this->projectsettings['tags']['textdata']['column'] ) && !is_string( $this->projectsettings['tags']['textdata']['column'] ) ){
                // remove multiple spaces, returns, tabs, etc.
                $text = preg_replace( "/[\n\r\t\s ]+/i", " ", $this->row[$this->projectsettings['tags']['textdata']['column']] );                
                // replace full stops and spaces with a comma (command required in explode)
                $text = str_replace(array( "   ", "  ", " ", ".", '"' ), ", ", $text );
                $exploded_tags = explode( ', ', $text);
                $final_tags_array = array_merge( $final_tags_array, $exploded_tags);                        
            }
            
            // cleanup tags
            foreach( $final_tags_array as $key => $tag){
                // remove numeric values
                if( isset( $this->projectsettings['tags']['defaultnumerics'] ) 
                && $this->projectsettings['tags']['defaultnumerics'] == 'disallow'
                && is_numeric( $tag) ){
                    unset( $final_tags_array[$key] );
                }
                // remove exclusions
                if(in_array( $tag, $excluded_tags) ){
                    unset( $final_tags_array[$key] );
                }   
            }
            
            // remove extra tags  
            if( isset( $this->projectsettings['tags']['maximumtags'] ) && is_numeric( $this->projectsettings['tags']['maximumtags'] ) ){
                $final_tags_array = array_slice( $final_tags_array, 0, $this->projectsettings['tags']['maximumtags'] );
            }
            
            $this->my_post['tags_input'] = implode( ", ", $final_tags_array );                        
        }
        
        // call next method
        $this->title();        
    }  
         
    public function title() {

        if( isset( $this->projectsettings['titles']['defaulttitletemplate'] ) ){
            $this->my_post['post_title'] = $this->replace_tokens( $this->projectsettings['titles']['defaulttitletemplate'] );    
        }       
        
        if(!isset( $this->my_post['post_title'] ) || empty( $this->my_post['post_title'] ) ){
            $this->my_post['post_title'] = __( 'No Post Content Title' );    
        }
                
        // call next method
        $this->wpautop();         
    }
    
    /**
    * change double line breaks into paragraphs
    * 
    * @link https://codex.wordpress.org/Function_Reference/wpautop
    */
    public function wpautop() {
        
        /*  need a setting to apply this or not, it changes double line breaks into <p>
        if( isset( $this->my_post['post_content'] ) ){
            $this->my_post['post_content'] = wpautop( $this->my_post['post_content'] );
        }
        */

        // call next method
        $this->insert_post();        
    }
    
    public function insert_post() {                      
        $this->my_post['ID'] = wp_insert_post( $this->my_post );
        // call next method
        $this->localimagegroupimport();        
    }
    
    /**
    * imports groups of images from a local directory, per post using a single term and not full paths
    */
    public function localimagegroupimport() {
        if( isset( $this->projectsettings['content']['groupedimagesdir'] ) && is_string( $this->projectsettings['content']['groupedimagesdir'] ) ){   
            $continue = true;// do checks and change to false if problem detected
            if( !file_exists( ABSPATH . $this->projectsettings['content']['groupedimagesdir'] ) ){
                $continue = false;
            }
            
            if( isset( $this->projectsettings['content']["localimages"] ) ){
                if(!isset( $this->projectsettings['content']['enablegroupedimageimport'] ) || $this->projectsettings['content']['enablegroupedimageimport'] !== 'enabled' ){
                    $continue = false;
                    if(!isset( $project_array['content']["localimages"]['column'] ) ){
                        $continue = false;
                        if( isset( $this->row[ $this->projectsettings['content']["localimages"]['column'] ] ) && !empty( $this->row[ $this->projectsettings['content']["localimages"]['column'] ] ) ){
                            $continue = false;
                        }                 
                    }
                }    
            }else{
                $continue = false;
            }
            
            if( $continue ){
                //setting not yet used = $project_array['content']["incrementalimages"];
                // create an array to hold directory list
                $results = array();

                // create a handler for the directory
                $handler = opendir( ABSPATH . $this->projectsettings['content']['groupedimagesdir'] );
                  
                // open directory and walk through the filenames
                while ( $file = readdir( $handler ) ) {

                    // if file isn't this directory or its parent, add it to the results
                    if ( $file != "." && $file != "..") {

                        // check with regex that the file format is what we're expecting and not something else
                        // BRO_HOLM_7_1 to BRO-HOLM-7-1
                        if( strstr( $file, $this->row[ $this->projectsettings['content']["localimages"]['column'] ] ) ) {
        
                            $fullpath = ABSPATH . $this->projectsettings['content']['groupedimagesdir'] . '/' . $file;
                           
                            // import image to WordPress media
                            $thumbid = $CSV2POST->create_localmedia_fromlocalimages( $fullpath, $this->my_post['ID'] );  
                              
                            // add to our file array for later use
                            $results[] = $file;
                        }
                    }
                }            
                
                // build list or gallery of images based on settings
                $image_list_or_gallery = '';
                 
                // replace applicable token in post content if it exists, right now we are using one token no custom ones
                if( isset( $this->my_post['post_content'] ) && strstr( $this->my_post['post_content'], '#localimagelist#' ) ){
                    $this->my_post['post_content'] = str_replace( '#localimagelist#', $image_list_or_gallery, $this->my_post['post_content'] );    
                }
            }
        }
        
        // call next method
        $this->externalimagegroupimport();           
    } 
    
    /**
    * external (HTTP) image import
    * 
    * @todo this method was copied and has not been worked on yet
    */
    public function externalimagegroupimport() {
        
        // call next method
        $this->insert_customfields();           
    }   
        
    /**
    * insert users custom fields, the values are determined before this method
    */
    public function insert_customfields() {
        if( isset( $this->my_post['ID'] ) && is_numeric( $this->my_post['ID'] ) ){
            foreach( $this->my_post['newcustomfields'] as $key => $cf){
                add_post_meta( $this->my_post['ID'], $cf['name'], $cf['value'] );
            }
        }
        
        // call next method
        $this->insert_plugins_postmeta();    
    } 
       
    /**
    * insert plugins custom fields, used to track and mass manage posts
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.1
    */
    public function insert_plugins_postmeta() {

        $this->CSV2POST->insert_plugins_postmeta( $this->projectid, $this->my_post['ID'], $this->mainsourceid, $this->row['c2p_rowid'] );
        
        // call next method
        $this->update_project();    
    }
    
    /**
    * update project statistics
    * 1. save the last post created for reference
    * 2. store the entire $my_post object for debugging
    * 3. update the projectsettings value itself (incremental publish date is one example value that needs tracked per post)    
    */
    public function update_project() {
        $this->CSV2POST->update_project( $this->projectid, array( 'projectsettings' => maybe_serialize( $this->projectsettings) ), false );
        // call next method
        $this->update_row();        
    }
    
    /**
    * updates the users data row, add post ID to create a 
    * relationship between imported row and the new post. 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.2
    */
    public function update_row() {      
        global $wpdb;
        
        $this->CSV2POST->pair_record_with_post( $this->maintable, $this->my_post['ID'], $this->row['c2p_rowid'] );

        // call next method
        $this->output();        
    }
    
    /**
    * output results, method is used for manual post creation request only
    */
    public function output() {
        // $autoblog->finished == true indicates procedure just done the final post or the time limit is reached
        if( $this->requestmethod == 'manual' && $this->finished === true ){
            //$this->UI->create_notice( __( 'Sorry no report setup yet more information will be added later.' ), 'success', 'Extra', 'Posts Creation Report' );
        }
    }
}// end CSV2POST_InsertPost

/**
* Use to update a post created by or adopted by CSV 2 POST, class systematically calls all methods one after the other building the $my_post 
* and making changes to meta or media

* $this->requestmethod - systematic|manual|schedule
* 
* Systematic: this method happens while posts are being opened, it means the post object
* 
* @author Ryan Bayne
* @package CSV 2 POST
* @since 8.0.0
* @version 2.0 - removed method update_row() I see no reason to update the record in the way it was
*/
class CSV2POST_UpdatePost{
    public $my_post = array();
    public $report_array = array();// will include any problems detected, can be emailed to user 
       
    private function replace_tokens( $subject){
        unset( $this->projectcolumns['arrayinfo'] );
        foreach( $this->row as $columnfromquery => $usersdata){ 
            foreach( $this->projectcolumns as $table_name => $columnfromdb){  
                $subject = str_replace( '#'. $columnfromquery . '#', $usersdata, $subject);
            }    
        }
        return $subject;
    } 
    
    public function start() {
        $this->CSV2POST = new CSV2POST();
        $this->my_post['ID'] = $this->row['c2p_postid'];
        $this->replacevaluerules();
    }
    
    /**
    * rules for replacing specific values in selected columns with other content 
    */
    public function replacevaluerules() {
        if( isset( $this->projectsettings['content']['valuerules'] ) && is_array( $this->projectsettings['content']['valuerules'] ) ){
            foreach( $this->projectsettings['content']['valuerules'] as $key => $rule_array ){
                
                // if we do not have all the values we require in this rule just continue to the next item
                if(!isset( $rule_array['column'] ) ){continue;}
                if(!isset( $rule_array['vrvdatavalue'] ) ){continue;}
                if(!isset( $rule_array['vrvreplacementvalue'] ) ){continue;}
                
                // ensure the column is set in the $row
                if( isset( $this->row[ $rule_array['column'] ] ) ){
                    // does our value match the data
                    if( $this->row[ $rule_array['column'] ] === $rule_array['vrvdatavalue'] ){
                        // we have exact match to the rule so we replace the value
                        $this->row[ $rule_array['column'] ] = $rule_array['vrvreplacementvalue'];
                    }
                }    
            }              
        }        
        $this->title();
    }
    
    public function title() {

        if( isset( $this->projectsettings['titles']['defaulttitletemplate'] ) ){
            $this->my_post['post_title'] = $this->replace_tokens( $this->projectsettings['titles']['defaulttitletemplate'] );    
        }       
        
        if(!isset( $this->my_post['post_title'] ) || empty( $this->my_post['post_title'] ) ){
            $this->my_post['post_title'] = __( 'No Post Content Title' );    
        }
                
        // call next method
        $this->categories();         
    }            

    public function categories() { 
        if( isset( $this->projectsettings['categories'] ) ){
            if( isset( $this->projectsettings['categories']['data'] ) ){ 
            
                // load categories class
                CSV2POST_Configuration::load_class( 'CSV2POST_Categories', 'class-categories.php', 'classes',array( 'noreturn' ) );
                $CSV2POST_Categories = new CSV2POST_Categories();
                
                // establish if pre-set parent in use
                $preset_parent = false;
                if( isset(  $this->project_settings_array['categories']['presetcategoryid'] ) ){
                    $preset_parent = $this->project_settings_array['categories']['presetcategoryid'];
                }
                
                // create $posts_array which consists of a post ID as key and array of category terms  
                $posts_array = array();      
                foreach( $this->projectsettings['categories']['data'] as $level => $catarray ){  
                    $posts_array[ $this->my_post['ID'] ][] = $this->row[ $catarray['column'] ];
                }   
                        
                // $posts_array must have post ID has key and each item an array of terms in order as they are to be in WP
                $CSV2POST_Categories->mass_update_posts_categories( $posts_array, $preset_parent );
            }
        }
        
        // call next method
        $this->content();
    }
      
    public function content() {
        
        if( isset( $this->projectsettings['content']['wysiwygdefaultcontent'] ) ){
            $this->my_post['post_content'] = $this->replace_tokens( $this->projectsettings['content']['wysiwygdefaultcontent'] );    
        }    
        
        // call next method
        $this->customfields();        
    }    
    
    /**
    * does not create meta data, only establishes what meta data is to be created and it is created later
    * it is done this way so that rules can take meta into consideration and meta can be adjusted prior to post creation 
    */
    public function customfields() {
        $this->my_post['newcustomfields'] = array();// we will add keys and values to this, the custom fields are created later    
        $i = 0;// count number of custom fields, use it as array key
        
        // seo title meta
        if( isset( $this->projectsettings['customfields']['seotitletemplate'] ) && !empty( $this->projectsettings['customfields']['seotitletemplate'] )
        && isset( $this->projectsettings['customfields']['seotitlekey'] ) && !empty( $this->projectsettings['customfields']['seotitlekey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seotitletemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seotitlekey']; 
            ++$i; 
        }
        
        // seo description meta
        if( isset( $this->projectsettings['customfields']['seodescriptiontemplate'] ) && !empty( $this->projectsettings['customfields']['seodescriptiontemplate'] )
        && isset( $this->projectsettings['customfields']['seodescriptionkey'] ) && !empty( $this->projectsettings['customfields']['seodescriptionkey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seodescriptiontemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seodescriptionkey'];  
            ++$i; 
        }
                
        // seo keywords meta
        if( isset( $this->projectsettings['customfields']['seokeywordstemplate'] ) && !empty( $this->projectsettings['customfields']['seokeywordstemplate'] )
        && isset( $this->projectsettings['customfields']['seokeywordskey'] ) && !empty( $this->projectsettings['customfields']['seokeywordskey'] ) ){
            $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $this->projectsettings['customfields']['seokeywordstemplate'] );
            $this->my_post['newcustomfields'][$i]['name'] = $this->projectsettings['customfields']['seokeywordskey']; 
            ++$i;       
        }       
        
        // now add all other custom fields
        if( isset( $this->projectsettings['customfields']['cflist'] ) ){
            foreach( $this->projectsettings['customfields']['cflist'] as $key => $cf){
                $this->my_post['newcustomfields'][$i]['value'] = $this->replace_tokens( $cf['value'] );
                $this->my_post['newcustomfields'][$i]['name'] = $cf['name'];                
                ++$i;     
            }
        }

        // call next method
        $this->excerpt();        
    }
    
    public function excerpt() {
        
        // call next method
        $this->wpautop();         
    }   
               
    /**
    * change double line breaks into paragraphs
    * 
    * @link https://codex.wordpress.org/Function_Reference/wpautop
    */
    public function wpautop() {
        
        /*  need a setting to apply this or not, it changes double line breaks into <p>
        if( isset( $this->my_post['post_content'] ) ){
            $this->my_post['post_content'] = wpautop( $this->my_post['post_content'] );
        }
        */

        // call next method
        $this->update_post();        
    }
    
    public function update_post() {                   
        wp_update_post( $this->my_post );
        // call next method
        $this->update_customfields();        
    }
    
    /**
    * insert users custom fields, the values are determined before this method
    */
    public function update_customfields() {
        if( isset( $this->my_post['ID'] ) && is_numeric( $this->my_post['ID'] ) ){
            foreach( $this->my_post['newcustomfields'] as $key => $cf){
                update_post_meta( $this->my_post['ID'], $cf['name'], $cf['value'] );
            }
        }
        
        // call next method
        $this->update_plugins_customfields();    
    } 
       
    /**
    * insert plugins custom fields, used to track and mass manage posts
    */
    public function update_plugins_customfields() {
        // update date and time will be compared against the row time and projects timestamp
        update_post_meta( $this->my_post['ID'], 'c2p_updated',current_time( 'mysql' ) );
                
        // call next method
        $this->then_update_project();    
    }
    
    /**
    * update project statistics
    * 1. save the last post created for reference
    * 2. store the entire $my_post object for debugging
    * 3. update the projectsettings value itself (incremental publish date is one example value that needs tracked per post)    
    */
    public function then_update_project() {
        $this->CSV2POST->update_project( $this->projectid, array( 'projectsettings' => maybe_serialize( $this->projectsettings) ) );
        
        // call next method
        $this->output();        
    }
    
    /**
    * output results, method is used for manual post creation request only
    */
    public function output() {
        // $autoblog->finished == true indicates procedure just done the final post or the time limit is reached
        if( $this->requestmethod == 'manual' && $this->finished === true){
            //$this->UI->create_notice( __( 'Sorry a report is not available yet, more information coming later.' ), 'info', 'Small', 'Posts Update Report' );
        }
        
        return $this->my_post;
    }

}// end CSV2POST_UpdatePost  
?>