<?php
/** 
* Install, uninstall, repair
* 
* The section array can be used to prevent installation of per section elements before activation of the plugin.
* Once activation has been done, section switches can be used to change future activation. This is early stuff
* so not sure if it will be of use.
* 
* @package CSV 2 POST
* @author Ryan Bayne   
* @since 8.0.0
*/

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
* Handles install, uninstall, repair, modification of installation state, database table creation included
* 
* @author Ryan R. Bayne
* @package CSV 2 POST
* @since 8.1.32
* @version 1.0.3
*/
class CSV2POST_Install {
    
    use CSV2POST_DBTrait, CSV2POST_OptionsTrait; 
    
    public $csv2post_database_tables = array(
        'c2pprojects',
        'c2psources',
        'webtechglobal_schedule',  		
    );
        
    /**
    * Install __construct persistently registers database tables and is the
    * first point to monitoring installation state 
    */
    public function __construct() {    
    	// TODO 3 -o Ryan Bayne -c Objects: Stop using $this-DB and replace with DBTrait.       
        $this->DB = CSV2POST::load_class( 'CSV2POST_DB', 'class-wpdb.php', 'classes' );
        $this->PHP = CSV2POST::load_class( 'CSV2POST_PHP', 'class-phplibrary.php', 'classes' );
    }

    /**
    * Register database tables in $wpdb for proper WordPress core integration.
    *     
    * @version 1.0
    */
    function register_webtechglobal_tables() {
        global $wpdb;
        $wpdb->c2pprojects = "{$wpdb->prefix}c2pprojects";
        $wpdb->c2psources = "{$wpdb->prefix}c2psources";
        $wpdb->webtechglobal_schedule = "{$wpdb->prefix}webtechglobal_schedule";
    }
    
	/**
	* Registers this plugins database tables in $wpdb for full integration.
	* This is called within construct in class-csv2post.php as the action
	* is required during every load.
	* 
	* @version 1.0
	*/
	public function register_schema()
	{                     
        // register webtechglobal_scheduele table
        add_action( 'init', array( $this, 'register_webtechglobal_tables' ) );
        add_action( 'switch_blog', array( $this, 'register_webtechglobal_tables' ) );
        
        // register tables manually as the hook may have been missed 
        $this->register_webtechglobal_tables();                                        
    }
             
    /**
    * Creates the plugins database tables
    * 
    * @uses dbDelta()
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.6
    * 
    * @todo put all tables into their own function as done with log table
    * @todo merge CSV 2 POST projects table with WTG global projects table, this will allow CSV 2 POST to integrate with a range of plugins easier
    */
    function create_tables() {       
        global $charset_collate, $wpdb;
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $installresult = self::webtechglobal_schedule();

        // TODO 2 Task: after problems with this query I put it on a single line
        // then the problems stopped. But it should be able to go over multiple
        // lines. Improve the layout of the query and ensure it still works.
        
        // c2pprojects
        $sql_create_table = "CREATE TABLE " . $wpdb->prefix . "c2pprojects ( 
projectid bigint(5) unsigned NOT NULL AUTO_INCREMENT,
projectname varchar(45) DEFAULT NULL,
status int(1) unsigned DEFAULT 1,
timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
source1 int(5) unsigned DEFAULT '0',
lockcontent tinyint(1) unsigned DEFAULT '0',
lockmeta tinyint(1) unsigned DEFAULT '0',
datatreatment varchar(50) DEFAULT 'single',
projectsettings longtext DEFAULT NULL,
settingschange DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
UNIQUE KEY projectid (projectid)
 ) $charset_collate; ";
        dbDelta( $sql_create_table );
        // projectid - users will see this and use it to identify their project
        // projectname - optional and not initially requested to make things simple
        // status (added sep 2015)
        // timestamp
        // source1 - sourceid from the c2psources table
        // lockcontent (0|1) - true(1) will prevent the content being changed during updating
        // lockmeta (0|1) - true(1) will prevent ALL custom field values being changed during updating
        // datatreatment (single | append | join | individual)
        // projectsettings (serialized array ) - tags, post type, post status, dates and more
        // categories (serialized array ) - an array of category columns and settings
        // customfields (serialized array ) - array of custom fields and related settings
        // taxonomies (serialized array ) - array of project taxonomy settings, including custom taxonomy and meta for taxonomies
        // postcontent (serialized array ) - array of content designs linked to project and applicable settings/rules per design

        // c2psources
        $sql_create_table = "CREATE TABLE " . $wpdb->prefix . "c2psources (
sourceid bigint(20) unsigned NOT NULL AUTO_INCREMENT,
projectid int(5) unsigned DEFAULT 0,
name varchar(250) DEFAULT NULL,
sourcetype varchar(45) DEFAULT NULL,
progress int(12) DEFAULT 0,
rows int(12) DEFAULT 0,
parentfileid int(8) DEFAULT 0,
tablename varchar (50) NOT NULL DEFAULT '0',
filesarray text DEFAULT NULL,
timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
path varchar(500) DEFAULT NULL,
directory varchar(500) DEFAULT NULL,
idcolumn varchar(50) DEFAULT NULL,
monitorfilechange tinyint(1) unsigned DEFAULT 1,
changecounter int(8) unsigned DEFAULT '0',
datatreatment varchar (50) NOT NULL DEFAULT 'single',
rules longtext DEFAULT NULL,
thesep varchar(1) DEFAULT NULL,
theconfig text DEFAULT NULL,PRIMARY KEY (sourceid)
) $charset_collate; ";
        dbDelta( $sql_create_table );  
   
        // sourceid
        // projectid - may not always be populated by populating it will help avoid querying project table just to display project id
        // sourcetype (localcsv)
        // progress - number of rows previously processed, we will reset this when a new file is detected
        // rows - total rows counted in file, may change during updates
        // parentfileid - if file is part of a group it will have a parent file.
        // tablename - database table the source is to be imported into (could be a table name based on parent source)        
        // filesarray - holds all file names applicable to source, within the source directory, requires "directory" to be in use        
        // timestamp
        // path - path to current file, can change this manually or automatically to use new file
        // idcolumn - required for updating
        // monitorfilechange (0|1) - detect changes to e
        // changecounter (numeric) - each time plugin detects newer source file, changecounter is increased telling us how many times source has been update but this is also used in each row to indicate the update status
        // datatreatment (single | append | join | individual) - single means a one file project, all others are multi-file project treatments                                       
        // rules - serialized array of rules that are applied to each row before it is inserted to the database
        // thesep - although this will be stored in theconfig array it will be easier for hackers by putting it in a column of its own
        // theconfig - this is a serialized array, it should not be relied on where we can easily use the source files or re-establish the information
        // rules - array of rules to prepare the data
        // directory - directory for sources with multiple files at once or new files being added to directory and being switched to
    }
                  
    /**
    * Reinstall all database tables.
    * 
    * @version 2.0
    */
    public function reinstalldatabasetables() {
        global $wpdb;

        foreach( $this->csv2post_database_tables as $key => $table ){
            if( $this->DB->does_table_exist( $table ) ){         
                $wpdb->query( 'DROP TABLE '. $table );
            }                                                             
        }
   
        return $this->create_tables();
    } 
    
    /**
    * Install all options.
    * 
    * @version 1.2
    * 
    * @todo Make use of the new options class or remove this method.
    */
    function install_options() {
        // installation state values
        update_option( 'csv2post_installedversion', CSV2POST_VERSION );# will only be updated when user prompted to upgrade rather than activation
        update_option( 'csv2post_installeddate', time() );# update the installed date, this includes the installed date of new versions
        
        // notifications array (persistent notice feature)
        add_option( 'csv2post_notifications', serialize( array() ) ); 
    }

    /**
    * Main plugin installation method.
    * 
    * @version 1.3
    */
    function install_plugin() {   
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}      	
        self::minimum_wp_version();
        self::minimum_php_version();
    	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        self::create_tables();
        self::install_options();
         // if this gets installed we know we arrived here in the installation procedure
         // TODO 5 -o Ryan Bayne -c Installation: This option can probably be removed now.
        update_option( 'csv2post_is_installed', true );       
    	// Flush WP re-write rules for custom post types.
        flush_rewrite_rules();        
    } 
        
    /**
    * WP version check with strict enforcement. 
    * 
    * This is only done on activation. There is another check that is
    * performed on every page load and displays a notice. That check
    * is to ensure the environment does not change.
    * 
    * @version 1.0
    */
	function minimum_wp_version() {
		global $wp_version;
		if ( version_compare( $wp_version, CSV2POST_WPVERSIONMINIMUM, '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die(
				'<p>' .
				sprintf(
					__( 'This plugin can not be activated because it 
					requires a WordPress version greater than %1$s. Please 
					go to Dashboard &#9656; Updates to get the latest 
					version of WordPress.', 'csv2post' ),
					CSV2POST_WPVERSIONMINIMUM
				)
				. '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'go back', 'csv2post' ) . '</a>'
			);
		}
	}
	
    /**
    * PHP version check with strict enforcement. 
    * 
    * This is only done on activation. There is another check that is
    * performed on every page load and displays a notice. That check
    * is to ensure the environment does not change.
    * 
    * @version 1.0
    */
	function minimum_php_version() {
		if ( version_compare( PHP_VERSION, CSV2POST_PHPVERSIONMINIMUM, '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die(
				'<p>' .
				sprintf(
					__( 'This plugin cannot be activated because it 
					requires a PHP version greater than %1$s. Your 
					PHP version can be updated by your hosting 
					company.', 'csv2post' ),
					CSV2POST_PHPVERSIONMINIMUM
				)
				. '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'go back', 'csv2post' ) . '</a>'
			);
		}
	}  
	    
    /**
    * Deactivate plugin - can use it for uninstall but usually not
    * 1. can use to cleanup WP CRON schedule, remove plugins scheduled events
    * 
    * @version 1.2
    */
    function deactivate_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}    	
    	// Flush WP re-write rules for custom post types.
        flush_rewrite_rules();

		// TODO 1 -o Ryan Bayne -c CRON: Remove WordPress cron jobs when the plugin is deactivated.
        // TODO 3 -o Ryan Bayne -c CRON: Report to the user that their cron jobs were removed if they activate the plugin again.
        // TODO 5 -o Ryan Bayne -c CRON: Store deleted jobs and offer quick re-instate of all deleted jobs.
    }  
    
    /**
    * Uninstall all database tables with no backup. 
    * 
    * @version 1.0
    */
	public function uninstalldatabasetables() {   
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}		
        foreach( $csv2post_database_tables as $key => $table_name )
        {
			eval( '$wpdb->query("DROP TABLE IF EXISTS ' . $table_name . '");' );	
        } 
		return;
	} 
	
    /**
    * Schedule tables for holding schedule methods. They can be fired
    * by admin actions and CRON jobs.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.9
    * @version 1.3
    */
    public function webtechglobal_schedule() {
        global $charset_collate,$wpdb;  
                 
$sql_create_table = "CREATE TABLE " . $wpdb->prefix . "webtechglobal_schedule (
rowid bigint(20) unsigned NOT NULL AUTO_INCREMENT,
timesapplied bigint(20) unsigned NOT NULL DEFAULT 0,
plugin varchar(125) DEFAULT NULL,
pluginname varchar(125) DEFAULT NULL,
class varchar(250) DEFAULT NULL,
method varchar(30) DEFAULT NULL,
lastupdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
recurrence varchar(20) DEFAULT NULL,
basepath varchar(250) DEFAULT NULL,
active tinyint(1) unsigned NOT NULL DEFAULT 1,
lastexecuted timestamp NULL,
lastcron timestamp NULL,    
weight int(2) NOT NULL DEFAULT 5, 
delay int(4) NOT NULL DEFAULT 3600,
firsttime timestamp NULL,
UNIQUE KEY rowid (rowid) ) $charset_collate; ";

/*
timesapplied - number of times executed or applied to cron
plugin       - plugins title
pluginname   - plugin lowercase name used in ID
class        - the class the method can be found in
method       - PHP method/function
lastupdate   - timestamp
recurrence   - (WTG Cron) once, repeat (WP Cron only) hourly, twicedaily, daily, 15min (custom), 2hours (custom)  
basepath     - csv2post/csv2post.php and used to access plugin files
active       - boolean 
lastexecuted - last time the action was executed
lastcron     - last time a cron job was created specifically for this action
weight       - task weights apply a secondary limit to the number of actions executed
delay        - recurrence delay for repeat actions used in WTG Cron system.
firsttime    - targetted first time for the event to happen, delay is applied after this if on repeat.

*/                                                                    
        return dbDelta( $sql_create_table );
    }	            
}
?>