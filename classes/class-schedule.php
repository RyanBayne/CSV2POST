<?php
/**                  
* Custom Schedule functionality for this package. 
* 
* Used by the CSV2POST_Automation class, which is being designed to give
* WP users greater control over the things they do not see.  
* 
* @package CSV 2 POST
* @author Ryan Bayne   
* @version 1.0
*/

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class CSV2POST_Schedule {

    public function __construct() {
    	$CONFIG = new CSV2POST_Configuration();
        $this->UI = $CONFIG->load_class( 'CSV2POST_UI', 'class-ui.php', 'classes' ); # interface, mainly notices
        $this->PHP = $CONFIG->load_class( 'CSV2POST_PHP', 'class-phplibrary.php', 'classes' ); # php library by Ryan R. Bayne
        $this->DB = $CONFIG->load_class( 'CSV2POST_DB', 'class-wpdb.php', 'classes' );
        $this->DATA = $CONFIG->load_class( 'CSV2POST_Data', 'class-data.php', 'classes' );    
    }    
    
    /**
    * Import data from any waiting file. 
    * 
    * This is used within automated systems and so no part requires
    * a user and it takes into consideration that between each event
    * things may have changed about data or settings.
    * 
    * Remember create_notice() will not work if user ID cannot be applied.
    * So use of create_notice() in automation functions servers for testing
    * right now and doubles as a method to log.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function auto_dataimport( $args ) {
    	$method_config = array(
    		'type' => 'wtgcron',// wtgcron, wpcron (wp core), admintrigger (only when admin logged in)
    		'recurrance' => 'repeat',// once, repeat (WTG Cron) OR (WP Cron) hourly, twicedaily, daily, weekly, monthly, yearly
    		'description' => __( 'This is an example action that outputs a notice.', 'csv2post' ),
    		'active' => false,// boolean
    		'weight' => 2,// prevent two heavy tasks running together (lighest 1 - 10 heaviest)
    		'delay' => 3600,// number of seconds between each event for this method 
    	);                 
    	
    	// Request config only for entering into schedule table as an action.s
    	if( $args == 'getconfig' ) 
    	{
			return $method_config;	
    	}
    	    	
        // get all datasources with new files that have not been 100% imported
        $waiting_files_array = $this->DB->find_waiting_files_new();
        if( !array( $waiting_files_array ) ) { 
            return false;
        }
        
        // get any sources ID from those returned
        foreach( $waiting_files_array as $array_key => $data_source ) {
            if( !isset( $waiting_files_array[$array_key]['sourceid'] )
                || !isset( $waiting_files_array[$array_key]['sourceid'] ) ) {
                continue;
            }
            
            $source_id = $waiting_files_array[$array_key]['sourceid'];
            $project_id = $waiting_files_array[$array_key]['projectid']; 
            
            break;
        }
        
        if( !$waiting_files_array ) {            
            return false;
        }
                
        // import data
        // TODO 3 c automation task: add control over the import limit
        $this->DATA->import_from_csv_file( $source_id, $project_id, 'import', 100 );        
    }

    /**
    * Update data using any new file.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function auto_dataupdate( $args ) {
    	$method_config = array(
    		'type' => 'wtgcron',// wtgcron, wpcron (wp core), admintrigger (only when admin logged in)
    		'recurrance' => 'repeat',// once, repeat (WTG Cron) OR (WP Cron) hourly, twicedaily, daily, weekly, monthly, yearly
    		'description' => __( 'This is an example action that outputs a notice.', 'csv2post' ),
    		'active' => false,// boolean
    		'weight' => 2,// prevent two heavy tasks running together (lighest 1 - 10 heaviest)
    		'delay' => 3600,// number of seconds between each event for this method 
    	);                 
    	
    	// Request config only for entering into schedule table as an action.s
    	if( $args == 'getconfig' ) 
    	{
			return $method_config;	
    	}
    	
        // get datasources with old files that still need their update complete
        $waiting_files_array = $this->DB->find_waiting_files_old();
        if( !array( $waiting_files_array ) ) {
            return false;
        }
        
        if( !$waiting_files_array ) {
            // notice for testing and log            
            return false;
        }
        
        // get any sources ID from those returned
        foreach( $waiting_files_array as $array_key => $data_source ) {
            
            if( !isset( $waiting_files_array[$array_key]['sourceid'] )
                || !isset( $waiting_files_array[$array_key]['sourceid'] ) ) {
                continue;
            }
            
            $source_id = $waiting_files_array[$array_key]['sourceid'];
            $project_id = $waiting_files_array[$array_key]['projectid']; 
            
            break;
        }       
                
        // import data
        // TODO 3 c automation task: add control over the import limit
        $this->DATA->import_from_csv_file( $source_id, $project_id, 'update', 100 );   
    }                                            

    /**
    * Create post using any available data for any project.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo this process queries for sources records twice because it needs to
    * establish what source can be used, then get it's project and then work
    * from project forwards as per usual. So I have left get_unused_rows()
    * in use. Instead get_sources_with_updated_rows() could have a parameter 
    * to return the actual data. I also think by default it should return data
    * if only 1 source has been requested.
    */
    public function auto_postcreation( $args ) {
    	$method_config = array(
    		'type' => 'wtgcron',// wtgcron, wpcron (wp core), admintrigger (only when admin logged in)
    		'recurrance' => 'repeat',// once, repeat (WTG Cron) OR (WP Cron) hourly, twicedaily, daily, weekly, monthly, yearly
    		'description' => __( 'This is an example action that outputs a notice.', 'csv2post' ),
    		'active' => false,// boolean
    		'weight' => 2,// prevent two heavy tasks running together (lighest 1 - 10 heaviest)
    		'delay' => 3600,// number of seconds between each event for this method 
    	);                 
    	
    	// Request config only for entering into schedule table as an action.s
    	if( $args == 'getconfig' ) 
    	{
			return $method_config;	
    	}
    	    	
        global $csv2post_settings;
        
        // get a source with unused rows, get it's project ID
        // this function gets active projects first
        $source = $this->DB->get_sources_with_unused_rows( 1 );
        
        if( !$source ) {            
            return false; 
        }
        
        $autoblog = new CSV2POST_InsertPost();
        $autoblog->settings = $csv2post_settings;
        $autoblog->currentproject = $csv2post_settings['currentproject'];
        $autoblog->project = $this->DB->get_project( $source[0]['projectid'] );// gets project row, includes sources and settings
        $autoblog->projectid = $source[0]['projectid'];
        $autoblog->maintable = $this->DB->get_project_main_table( $source[0]['projectid'] );// main table holds post_id and progress statistics
        $autoblog->projectsettings = maybe_unserialize( $autoblog->project->projectsettings );// unserialize settings
        $autoblog->projectcolumns = $this->DB->get_project_columns_from_db( $source[0]['projectid'] );
        $autoblog->requestmethod = 'automatic';
        $autoblog->mainsourceid = $source[0]['sourceid'];// update the main source database table per post with new post ID
        unset( $autoblog->project->projectsettings);// just simplifying the project object by removing the project settings

        $idcolumn = false;
        if( isset( $autoblog->projectsettings['idcolumn'] ) ){
            $idcolumn = $autoblog->projectsettings['idcolumn'];    
        }
        
        // get rows not used to create posts
        // TODO 2 Task: apply users setting for automation event limit (replace 10)
        $unused_rows = $this->DB->get_unused_rows( $source[0]['projectid'], 10, $idcolumn );  
        if(!$unused_rows){
            return;
        }
        
        // we will control how and when we end the operation
        // when true, output will be complete and foreach below will discontinue, 
        // this can be happen if maximum execution time is reached
        $autoblog->finished = false;
        
        $foreach_done = 0;
        foreach( $unused_rows as $key => $row ){
            ++$foreach_done;
                    
            // to get the output at the end, tell the class we are on the final post,
            // only required in "manual" requestmethod
            // TODO 2 Task: replace "10" with the users automation limit.
            if( $foreach_done == 10 ){    
                $autoblog->finished = true;// not completely finished, indicates this is the last post
            }
            
            // pass row to $autob
            $autoblog->row = $row; 
               
            // create a post
            $autoblog->start();
        }                
    } 
    
    /**
    * Automation function for updating posts per schedule.
    * 
    * Includes notices for users who
    * are testing the function directly.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo this process queries for sources records twice because it needs to
    * establish what source can be used, then get it's project and then work
    * from project forwards as per usual. So I have left get_updated_rows()
    * in use. Instead get_sources_with_updated_rows() could have a parameter 
    * to return the actual data. I also think by default it should return data
    * if only 1 source has been requested.  
    */
    public function auto_postupdate( $args ) {
        global $csv2post_settings;

    	$method_config = array(
    		'type' => 'wtgcron',// wtgcron, wpcron (wp core), admintrigger (only when admin logged in)
    		'recurrance' => 'repeat',// once, repeat (WTG Cron) OR (WP Cron) hourly, twicedaily, daily, weekly, monthly, yearly
    		'description' => __( 'This is an example action that outputs a notice.', 'csv2post' ),
    		'active' => false,// boolean
    		'weight' => 2,// prevent two heavy tasks running together (lighest 1 - 10 heaviest)
    		'delay' => 3600,// number of seconds between each event for this method 
    	);                 
    	
    	// Request config only for entering into schedule table as an action.
    	if( $args == 'getconfig' ) 
    	{
			return $method_config;	
    	}
    	        
        // TODO 2 Task: apply users setting for automated event limit here (replace 5)    
        $total_posts = 5;
        
        $source = $this->DB->get_sources_with_updated_rows( 1 );

        if( !$source ) {             
            return false; 
        }
                    
        $autoblog = new CSV2POST_UpdatePost();
        $autoblog->settings = $csv2post_settings;
        $autoblog->currentproject = $csv2post_settings['currentproject'];// dont automatically use by default, request may be for specific project
        $autoblog->project = $this->DB->get_project( $source[0]['projectid'] );// gets project row, includes sources and settings
        $autoblog->projectid = $source[0]['projectid'];
        $autoblog->maintable = $this->DB->get_project_main_table( $source[0]['projectid'] );// main table holds post_id and progress statistics
        $autoblog->projectsettings = maybe_unserialize( $autoblog->project->projectsettings);// unserialize settings
        $autoblog->projectcolumns = $this->DB->get_project_columns_from_db( $source[0]['projectid'] );
        $autoblog->requestmethod = 'manual';
        $sourceid_array = $this->DB->get_project_sourcesid( $source[0]['projectid'] );
        $autoblog->mainsourceid = $sourceid_array[0];// update the main source database table per post with new post ID
        unset( $autoblog->project->projectsettings );// simplifying the object by removing the project settings

        // we will control how and when we end the operation
        $autoblog->finished = false;// when true, output will be complete and foreach below will discontinue, this can be happen if maximum execution time is reached
        
        $idcolumn = false;
        if( isset( $autoblog->projectsettings['idcolumn'] ) ){
            $idcolumn = $autoblog->projectsettings['idcolumn'];    
        }
                               
        $updated_rows = $this->DB->get_updated_rows( $source[0]['projectid'], $total_posts, $idcolumn);
        
        if( !$updated_rows ){
            return;
        }
            
        $foreach_done = 0;
        foreach( $updated_rows as $key => $row){
            ++$foreach_done;
                        
            // to get the output at the end, tell the class we are on the final post, only required in "manual" requestmethod
            if( $foreach_done == $total_posts ){
                $autoblog->finished = true;
            }            
            // pass row to $autob
            $autoblog->row = $row;    
            // create a post - start method is the beginning of many nested functions
            $autoblog->start();
        } 
                     
        return false;
    }
        
}
?>
