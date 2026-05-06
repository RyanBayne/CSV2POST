<?php
/**                  
* Schedule and Automation System.
* 
* @package CSV 2 POST
* @author Ryan Bayne   
* @version 1.0
* 
* @todo Add method to register multiple class per plugin for inclusion on the interface (only auto_ methods)
* @todo Enhance class registration with ability to add specific methods to the registration information (even without auto_)
*/

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class CSV2POST_Automation {
	
	use CSV2POST_DBTRAIT, CSV2POST_OptionsTrait;
	
	/**
	* Used to determine if automated system is active or not.
	* This does not apply to administrator triggered automation as
	* that is required to run on its own.
	* 
	* @var mixed
	*/
	public $auto_switch = false;
	
	/**
	* Force a delay on all automatic activity. Use this to prevent WTG plugins
	* being too active in short periods of time.
	* 
	* @var mixed
	*/
	public $auto_delay_all = 900;// 900 seconds default is 15 minutes.                                  
			          
	/**
	* Schedule maintenance delay.
	* 
	* @var mixed
	*/
	public $auto_delay_maintenance = 86400;// seconds                                      

	/**
	* Allow more than one task to be actioned. The maximum
	* weight total then comes into affect. By default if the weight of a task
	* is 10 then only one task will run. If the weight of a task is 5 and another
	* is 6 then only one task will run. This is because the default weight limit
	* is 10. 
	*/
	public $auto_tasks_limit = 3;
	
	/**
	* Total the weight of actions. Each action stored in the schedule table
	* has a weight. The weight is set by a developer, based on how big the task
	* to be performed is. 
	* 
	* Weight range is 1 - 10. Most tasks should have around 1-4. Tasks that 
	* involve REST or SOAP calls or file write, should be 5 - 8. Tasks which
	* involve loops on data with an undertimed size should be 9 or 10.
	*/
	public $auto_weight_limit = 10;// increase this with care
	
	/**
	* Array of automated plugins.
	* 
	* @var mixed
	*/
	public $auto_plugins = array();
	
	/**
	* Add up the weights of each action in this variable. 
	*/
	public $auto_weight_total = 0;
	
	public function __construct() {
		// Add our own schedule delays to WordPress. 
		add_filter( 'cron_schedules', array( $this, 'csv2post_custom_cron_schedule' ) );
		
		// Get the automation switch status.
		$this->auto_switch = get_option( 'csv2post_auto_switch' );
		
		// Get the last time any automatic action was taking.
    	$this->last_auto_time = get_option( 'csv2post_auto_lasttime' );
    	
    	// Get automated plugins. 
    	$this->auto_plugins = get_option( 'csv2post_auto_plugins' );
    	               
    	// The developer menu in toolbar allows $auto_delay_all to be over-ridden.
    	if( isset( $_GET['csv2postaction'] ) && $_GET['csv2postaction'] === 'csv2postactionautodelayall' )
    	{      
    		if( !wp_verify_nonce( $_GET['_wpnonce'], 'csv2postactionautodelayall' ) ) {    
				return false;
    		}	

    		// Set the delay to zero so that it runs now.
    		$this->auto_delay_all = 0;		
    	}	
	}

	/**
	* Check if user has activated automation using the main switch.
	* 
	* @returns boolean
	* @version 1.0
	*/
	public function is_automation_active() {
        if( $this->auto_plugins === true )
        {
			return true;
        }
        
	    return false;
	}
	
	/**
	* Check if the current plugin as been registered
	* for inclusion in the WebTechGlobal schedule system
	* for WordPress.
	* 
	* @version 1.0 
	*/
	public function is_current_plugin_registered() {
	    if( !$this->auto_plugins )
	    {
			return false;
	    }
	    
	    foreach( $this->auto_plugins as $pluginname => $plugindetails )
	    {
			if( $plugindetails['basename'] === CSV2POST_BASENAME )
			{
				return true;	
			}	
	    }
	    
	    return false;
	}
	
	/**
	* Check if the current plugin is active.
	* 
	* @version 1.0 
	*/
	public function is_current_plugin_active() {
	    if( !$this->auto_plugins )
	    {
			return false;
	    }
	    
	    foreach( $this->auto_plugins as $pluginname => $plugindetails )
	    {
			if( $plugindetails['basename'] === CSV2POST_BASENAME )
			{
				if( $plugindeails['active'] === true )
				{
					return true;
				}              
				else
				{
					return false;
				}
			}	
	    }
	    
	    return false;
	}
	
 	/**
 	* Hooked in class-configuration.php and via class-csv2post.php
 	* 
 	* It is this function that checks the schedule table and executes a task
 	* that is due.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.1
    */
    public function webtechglobal_hourly_cron_function( $args ) {
    	global $wpdb;    

    	// Automation must be switched on.
    	if( !$this->auto_switch )
    	{                       
			return false;
    	}  
    	
        // Apply an event delay to prevent flooding.
    	if( $this->last_auto_time ) {    
    		$seconds_past = time() - $this->last_auto_time;
			if( $seconds_past < $this->auto_delay_all ) {    
				// Automation administration was performed not long ago.
				return;	
			}		
    	}
	    
	    // Set the last automated event time before attempting to run actions.
	    // If they fail or return early, we still have the time of attempt. 
        update_option( 'csv2post_auto_lasttime', time() );	
	    
    	/*
    		Perform administration on the schedule table. 
    		
    		Users have the option of either running events directly from the
    		schedule table or applying them to cron jobs first. This gives them
    		the option of using WP scheduling functions more and server cron more.
    		
    		The alternative is simply allowing a single hourly cron to SELECT 
    		due tasks and run them based on data in the schedule table. 
    	*/
    	
    	//self::automation_administration();

    	$result = $this->selectorderby( 
    		$wpdb->webtechglobal_schedule, 
    		'active = 1', 
    		'lastexecuted', 
    		'*', 
    		$this->auto_tasks_limit, 
    		'OBJECT' 
    	);
    	
    	// Loop through returned schedule records, loading each plugins schedule class.
    	$args = array();
    	foreach( $result as $key => $action ) 
    	{             
    		foreach( $this->auto_plugins as $pluginname => $plugindetails )
    		{                                
				if( $plugindetails['basename'] !== $action->basepath )
				{
					continue;		  
				}
    		}

    		// Do not execute if the weight limit would be reached.
    		$newweight = $action->weight + $this->auto_weight_total;
    		if( $newweight > $this->auto_weight_limit )
    		{
				continue;
    		} 
    		
    		// If the action recurrence is "once" we change the action active value to 0.
    		$active = 1;
    		if( $action->recurrence === 'once' )
    		{
    			$active = 0;	
			}
    		
    		// Increase the timesapplied counter
    		++$action->timesapplied;
    		
    		// Update changes to the scheduled action.
    		$condition = 'rowid = ' . $action->rowid . ' AND timesapplied = ' . $action->timesapplied;
			$this->update( 
				$wpdb->webtechglobal_schedule, 
				$condition, 
				array( 'active' => $active ) 
			);
  
    		// Create class object.
			eval( '$this->scheduleclass = new ' . $action->class . '();' );
			
			// Run the method in our new object.
			eval( '$this->scheduleclass->' . $action->method . '( $args );' );	
    	}	
	}
			
	/**
	* Array of custom cron intervals.
	* 
	* @version 1.0
	* 
	* @todo Only add second and minute when in developer mode. 
	*/
	function csv2post_custom_cron_schedule( $schedules ) {

		/*   Custom ones are causing errors when displaying cron job information
		because the trigger value does not exist in cron jobs i.e. oncehourly will have
		a trigger value of "hourly".
		
		$schedules['second'] = array(
		    'interval' => 1,
		    'display'  => __( 'Every Second (not recommended)' ),
		);
		    		
		$schedules['minute'] = array(
		    'interval' => 60,
		    'display'  => __( 'Every Minute (not recommended)' ),
		);
		    			    		
		$schedules['hourly'] = array(
		    'interval' => 3600,
		    'display'  => __( 'Once Hourly' ),
		);
		    		
		$schedules['twicedaily'] = array(
		    'interval' => 43200,
		    'display'  => __( 'Twice Daily' ),
		);		
		    	    		
		$schedules['weekly'] = array(
		    'interval' => 604800,
		    'display'  => __( 'Once Weekly' ),
		);			
		
		$schedules['monthly'] = array(
		    'interval' => 2628000,
		    'display'  => __( 'Once Monthly' ),
		);
        */
        
		return $schedules;
	}

 	/**
 	* Focuses on updating the schedule table so that it reflects the users
 	* requirements i.e. if a plugin is updated, new schedule methods are
 	* added or existing ones changed.
 	* 
 	* Can also create cron jobs for WP Cron but this is not fully in use 
 	* at this time. It works but it does not offer great enough benefits over
 	* the WTG Cron system.
 	* 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo add option to control auto admin manually only. 
    */
    public function automation_administration() {
    	     
        // Force a wait for any automation administration.
    	if( $this->last_auto_time ) {
    		$seconds_past = time() - $this->last_auto_time;
			if( $seconds_past < $this->auto_delay_maintenance ) {
				return;	
			}		
    	}                                                  
    	
    	// Store when administration on automation was performed.
    	update_option( 'csv2post_auto_lasttime', time() );
    	
    	/*
    		Perform some schedule and automation administration. Perform the
    		next task in-line, taking the previous into consideration.
    		
    		1. Register Actions (registeractions) - registers methods for display on forms i.e. user activation.
    		2. Scheduled Record (schedulerecords) - check for changes to records, delete CRON jobs that no longer match.
    		3. Create CRON jobs (makecronjobs) - for new or changed schedule records, create a CRON job.
    	*/
    	
    	$next_task = 'registeractions';// Default to a plugin refresh.
    	$last_task = get_option( 'webtechglobal_auto_lasttask' );
    	if( $last_task == 'registeractions' ) 
    	{
			$next_task = 'schedulerecords';	
    	} 
    	elseif( $last_task == 'schedulerecords' ) 
    	{
			$next_task == 'makecronjobs';
    	}
    	switch ($next_task) {
    	   case 'registeractions':    
    	     self::registeractions();
    	     break;
    	   case 'schedulerecords':
    	     self::schedulerecords();
    	     break;
    	   case 'makecronjobs':
    	     //self::makecronjobs();
    	     break;
    	}
 
    }    

 	/**
 	* Refresh stored information for a single giving plugin.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function registeractions_singleplugin( $plugin_name ) {
    	self::registeractions( array( $plugin_name ) );    		
	}
	
 	/**
 	* Refresh stored information for this plugin.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function registeractions_currentplugin() {        
    	self::registeractions( array( CSV2POST_NAME ) );		
	}
	
 	/**
 	* Used to mass register auto methods for one giving plugin 
 	* or ALL registered plugins.
 	* 
 	* Do not confuse registering actions (within the WTG global scheduling and
 	* automation system) with scheduling actions which adds them to the schedule
 	* database table. Registration involves adding methods to an option in the 
 	* WP core options table. This sort of authorizes each WTG plugin to display
 	* information about the other. Making the entire ability optional.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @uses ReflectionClass()
    */
    public function registeractions( $plugins_array = array() ) {
    	// Build array of all schedule methods for processing into schedule table.
    	$schedule_methods_array = array();
    	
		// Get registered plugins array.
    	if( !is_array( $this->auto_plugins ) || empty( $this->auto_plugins ) ) 
    	{ 
    		return false; 
    	}	

    	// If no plugin name passed process ALL plugins.
    	if( empty( $plugins_array ) || !is_array( $plugins_array ) ) 
    	{
    		foreach( $this->auto_plugins as $plugin_name => $plugin_info )
    		{
    			$methods = self::get_plugins_schedule_methods( $plugin_name, $plugin_info );
    			$schedule_methods_array[ $plugin_name ] = $methods;
    		}	
    	}
    	else
    	{
    		foreach( $plugins_array as $plugin_name )
    		{
    			if( isset( $this->auto_plugins[ $plugin_name ] ) )
    			{
    				$info = $this->auto_plugins[ $plugin_name ];
					$methods = self::get_plugins_schedule_methods( $plugin_name, $info );
					$schedule_methods_array[ $plugin_name ] = $methods;
				}              
			}
    	}
    	
    	if( empty( $schedule_methods_array ) ) { return false; }
    	        
    	// Loop through methods for all plugins, ignore none auto_ methods and process each auto_.
    	foreach( $schedule_methods_array as $plugin_name => $arrayofreflections )
    	{          
    		foreach( $arrayofreflections as $key => $object )
    		{              
    			if( strpos( $object->name, 'auto_' ) === false )
    			{        
					continue;
    			}
    			
    			// Load each schedule class.
    			eval( '$this->scheduleclass = new ' . $object->class . '();' );
    			
    			// Skip this method if $object->name does not begin with auto_
				eval( '$method_config = $this->scheduleclass->' . $object->name . '( "getconfig" );' );	
				
				// Register the method, do not set it to active, users must always activate methods.
				// TODO: finish registering the method

    		}	
		}
    		
	}
	
 	/**
 	* Returns all auto_ methods from CSV 2 POST.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */	
	public function get_schedule_methods() {
    	// Establish current plugins folder, we must consider custom names.s
		$arr = explode( "/", CSV2POST_BASENAME, 2 );
		$plugin_folder_name = $arr[0];
		$giving_plugin_folder = WP_CONTENT_DIR . "/plugins/" . $plugin_folder_name;

		// Schedule class path.
		$schedule_path = $giving_plugin_folder . '/classes/class-schedule.php';
		
    	// Get the schedule class for the current plugin.
		include_once( $schedule_path );
		
		// Now get a list of all methods in the current plugins schedule class.
		$class = new ReflectionClass( 'CSV2POST_Schedule');
		
		// Collect methods with "auto_" prepend.
		$auto_methods = array();
		foreach( $class->getMethods() as $method )
		{
			if( strstr( $method->name, 'auto_' ) )
			{
				$auto_methods[] = $method->name;	
			}                                
		}
		
		return $auto_methods;		
	}
			
 	/**
 	* Returns all auto_ methods for the giving plugin.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */	
	public function get_plugins_schedule_methods( $plugin_name, $plugin_info ) {
		
    	// Establish current plugins folder, we must consider custom names.s
		$arr = explode( "/", $plugin_info['basename'], 2 );
		$plugin_folder_name = $arr[0];
		$giving_plugin_folder = WP_CONTENT_DIR . "/plugins/" . $plugin_folder_name;

		// Schedule class path.
		$schedule_path = $giving_plugin_folder . '/classes/class-schedule.php';
		
    	// Get the schedule class for the current plugin.
		include_once( $schedule_path );
		
		// Build class name we expect to have auto methods in.
		// CSV 2 POST has hyphens in name, lets not use hyphens in future plugins eh!
		$cleaner_plugin_name = str_replace( '-', '', $plugin_name );
		$class_name = strtoupper( $cleaner_plugin_name ) . '_Schedule';
	        
		// Avoid errors should the class not exist.
		if( !class_exists( $class_name ) )
		{
			return false;
		}
		
		// Now get a list of all methods in the current plugins schedule class.
		$class = new ReflectionClass( $class_name );
		
		// Collect those with auto_
		$auto_methods = array();
		foreach( $class->getMethods() as $method )
		{
			if( strstr( $method->name, 'auto_' ) )
			{
				$auto_methods[] = $method->name;	
			}                                
		}
		
		return $auto_methods;		
	}	
		
 	/**
 	* Process schedule records for all plugins.
 	* 
 	* This does not include creating cron jobs. It focuses on ensuring
 	* records match their related cron, deletes the existing cron job if
 	* it no longer matches the schedule record and sets the record to be used
 	* to create a cron job in the "makecronjobs" process.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */	
	public function schedulerecords_all() {
		
	}

 	/**
 	* Create cron jobs using scheduled records for all plugins.
 	* 
 	* This is called last as part of automation administration. The schedulerecords()
 	* method may change records to indicate that a cron job is required. To avoid
 	* over-processing that method does not create the cron jobs, this method does. 
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */	
	public function makecronjobs_all() {
		// not in use - will only be in use when the option to use 
		// WordPress CRON/Server CRON becomes available.	
	}

 	/**
 	* Add a plugin to the list of plugins that are to be included
 	* in automation and scheduling system.
 	*
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */	
	public function register_plugin( $name, $basename, $title, $status = true ) {
		if( !is_string( $name ) || !is_string( $basename ) ) {
			return false;
		}
		
		// Initialize the plugins array.
		if( !is_array( $this->auto_plugins ) ) {
			$plugins = array();
		}
		    
		// We cannot assume the plugins array does not already exist, so update $plugins array this way.
		$plugins[ $name ]['title']= $title;
		$plugins[ $name ]['basename'] = $basename;
		$plugins[ $name ]['status'] = $status; /* boolean switch to enable/disable */ 
		$plugins[ $name ]['registered'] = time(); 
  
		update_option( 'csv2post_auto_plugins', $plugins );
	} 
	
	/**
	* Returns an array of boolean indicating if 
	* all actions matching cron job exists or not.
	* 
	* @return array() boolean
	* @version 1.0
	*/
	public function actions_cron_exists() {
		$methods = get_schedule_methods();
	    return;
	}
	
	/**
	* Inserts a new record (as an event) into the schedule table.
	* 
	* @param mixed $plugin
	* @param mixed $pluginname
	* @param mixed $class
	* @param mixed $method
	* @param mixed $firsttime
	* @param mixed $delay set to zero to perform the event once.
	* @param mixed $weight
	*/
	public function new_wtgcron_job( $plugin, $pluginname, $class, $method, $recurrence, $basepath, $active = 1, $weight = 5, $delay = 3600, $firsttime = false ) {
    	global $wpdb;

    	// Query table for due cron jobs with limit using $this->auto_tasks_limit;
    	$fields = array(
    		'plugin' => $plugin,
    		'pluginname' => $pluginname,
    		'class' => $class,
    		'method' => $method,
    		'recurrence' => $recurrence,
    		'basepath' => $basepath,
    		'active' => 1,
    		'weight' => $weight,
    		'delay' => $delay,
    		'firsttime' => $firsttime,
    	);

    	$this->insert(
    		$wpdb->webtechglobal_schedule,
    		$fields
    	);
    		
	    return;
	}
	
	/**
	* Delete a record in schedule table using row ID.
	* 
	* @param mixed $rowid
	* @version 1.0
	*/
	public function delete_wtgcron_job_byrowid( $rowid ) {
		global $wpdb;
		return CSV2POST_DB::delete( 
			$wpdb->webtechglobal_schedule, 
			'rowid = ' . $rowid 
		);
	}
			
	/**
	* Get a registered (for automation) plugin by name (not title).
	* 
	* @version 1.0
	* 
	* @returns array of plugin data as regisered in 
	* "csv2post_auto_plugins" option. 
	* 
	* @returns null if the plugins array does not exist.
	*
	* @param mixed $plugin_name 
	* @param mixed $field return a single field
	*/
	public function get_plugin_by_name( $plugin_name, $field = false ) {
		
		// Get registered plugins array.
    	$plugins = get_option( 'csv2post_auto_plugins' );

    	if( !is_array( $plugins ) || empty( $plugins ) ) 
    	{ 
    		return null; 
    	}	
             
    	if( $field === false )
    	{
			return $plugins[ $plugin_name ];	
    	}
    	else
    	{                                   
			return $plugins[ $plugin_name ][ $field ];
    	}
    	
	    return null;
	}	
	
	/**
	* Set a scheduled method (action to the user) active status to 0.
	* 
	* @version 1.1
	*/
	public function disable_action( $class, $method ) {
		return $this->update( 
			$wpdb->webtechglobal_schedule, 
			'class = ' . $class . ' AND method = ' . $method, 
			array( 'active' => 0 ) 
		);
	}
}
?>
