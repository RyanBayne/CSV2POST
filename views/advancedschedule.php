<?php
/**
 * Example of WordPress core table with all possible uses. Update it to
 * reflect WP standards and do not do anything clever with it.
 * 
 * Use another tab for a more clever data table please. 
 * 
 * @todo Follow this to check for better results
 * http://www.sitepoint.com/using-wp_list_table-to-create-wordpress-admin-tables/ 
 *
 * @package CSV 2 POST
 * @subpackage Views
 * @author Ryan Bayne   
 * @since 1.0.2
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class CSV2POST_Advancedschedule_View extends CSV2POST_View {

	use CSV2POST_DBTrait;
	
    /**
     * Number of screen columns for post boxes on this screen
     *
     * @since 0.0.1
     *
     * @var int
     */
    protected $screen_columns = 1;
    
    protected $view_name = 'advancedschedule';
    
    public $purpose = 'normal';// normal, dashboard  
                        
    /**
    * Array of meta boxes, looped through to register them on views and as dashboard widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.0
    */
    public function meta_box_array() {
        // array of meta boxes + used to register dashboard widgets (id, title, callback, context, priority, callback arguments (array), dashboard widget (boolean) )   
        return $this->meta_boxes_array = array(
            // array( id, title, callback (usually parent, approach created by Ryan Bayne), context (position), priority, call back arguments array, add to dashboard (boolean), required capability
            array( $this->view_name . '-scheduleandautomationswitch', __( 'Schedule and Automation Switch', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'scheduleandautomationswitch' ), true, 'activate_plugins' ),          
            array( $this->view_name . '-registerpluginsautomation', __( 'Register Plugins Automation', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'registerpluginsautomation' ), true, 'activate_plugins' ),          
            array( $this->view_name . '-executeallactions', __( 'Execute All Actions', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'executeallactions' ), true, 'activate_plugins' ),          
            array( $this->view_name . '-createnewevent', __( 'Create New Event', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'createnewevent' ), true, 'activate_plugins' ),                  
        );    
    }
            
    /**
     * Set up the view with data and do things that are specific for this view
     *
     * @since 0.0.1
     *
     * @param string $action Action for this view
     * @param array $data Data for this view
     */
    public function setup( $action, array $data ) {
        global $csv2post_settings;

		if( !isset( $this->AUTO ) )
		{
			$this->AUTO = new CSV2POST_Automation();
		}
		  
        // create constant for view name
        if(!defined( "CSV2POST_VIEWNAME") ){define( "CSV2POST_VIEWNAME", $this->view_name );}

        parent::setup( $action, $data );

        // create a data table ( use "head" to position before any meta boxes and outside of meta box related divs)
        $this->add_text_box( 'head', array( $this, 'thetablesform' ), 'normal' );
                            
        // using array register many meta boxes
        foreach( self::meta_box_array() as $key => $metabox ) {
            // the $metabox array includes required capability to view the meta box
            if( isset( $metabox[7] ) && current_user_can( $metabox[7] ) ) {
                $this->add_meta_box( $metabox[0], $metabox[1], $metabox[2], $metabox[3], $metabox[4], $metabox[5] );   
            }               
        }                  
    }
   
    /**
    * Displays one or more tables of data at the top of the page before post boxes
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.0
    */
    public function thetablesform( $data, $box ) {       
        $WPTableObject = new WORDPRESSPLUGINCSV2POST_WPTable_Example();
        $WPTableObject->prepare_items();  

        if( !get_option( 'csv2post_auto_switch' ) )
		{
			// TODO 3 -o Ryan Bayne -c Help: Add a button for activating automation to this notice. 
        	echo $this->UI->notice_return( 
        		'info', 
        		'Tiny', 
        		__( 'Automation Not Active', 'csv2post' ),
        		__( 'You have not yet activated automation for CSV 2 POST. You
        		can do that using a form on this page.', 'csv2post' ), 
        		false, 
        		true 
        	);
		}
		else 
		{
			// TODO 4 -o Ryan Bayne -c Help: Add a button for deactivating automation.
        	echo $this->UI->notice_return( 
        		'success', 
        		'Tiny', 
        		__( 'Plugin Automated', 'csv2post' ),
        		__( 'CSV 2 POST has been automated. All active events listed in the
        		table below will be processed whenever they are due while WordPress
        		is loading.', 'csv2post' ), 
        		false, 
        		true 
        	);			
		}
        ?>
        
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
            <?php $WPTableObject->search_box( 'search', 't1' /* -search-input gets appended */ ); ?> 
            <?php $WPTableObject->display(); ?>
        </form>
 
        <?php               
    }
    
    /**
    * Outputs the meta boxes
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.3
    * @version 1.0
    */
    public function metaboxes() {
        parent::register_metaboxes( self::meta_box_array() );     
    }

    /**
    * This function is called when on WP core dashboard and it adds widgets to the dashboard using
    * the meta box functions in this class. 
    * 
    * @uses dashboard_widgets() in parent class CSV2POST_View which loops through meta boxes and registeres widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.2
    * @version 1.0
    */
    public function dashboard() { 
        parent::dashboard_widgets( self::meta_box_array() );  
    }
    
    /**
    * All add_meta_box() callback to this function to keep the add_meta_box() call simple.
    * 
    * This function also offers a place to apply more security or arguments.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.0
    */
    function parent( $data, $box ) {
        eval( 'self::postbox_' . $this->view_name . '_' . $box['args']['formid'] . '( $data, $box );' );
    } 
    
         
    /**
    * Options for controlling all plugins automation switch and 
    * individual method switches. 
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function postbox_advancedschedule_scheduleandautomationswitch( $data, $box ) { 
        global $csv2post_settings;
        $formid = $box['args']['formid'];
        include( CSV2POST_DIR_PATH . 'inc/fields/automationsettings.php' );
        $this->UI->postbox_content_footer();
    } 
    
    /**
    * Add the current plugin to the automation system. I refer to this
    * as registering the plugin.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function postbox_advancedschedule_registerpluginsautomation( $data, $box ) { 
		global $csv2post_settings;
		              
		$intro = __( 'Submit to register CSV 2 POST as part of the
		schedule and automation system. Multiple plugins by WebTechGlobal
		will integrate when registered. The goal is always balancing 
		automation and giving administrators full control of all
		scheduled activity.', 'csv2post' );

		$this->UI->postbox_content_header( 
		    $box['title'], 
		    $box['args']['formid'],
		    $intro, 
		    false 
		);       

		if( !isset( $this->AUTO ) )
		{
			$this->AUTO = new CSV2POST_Automation();
		}
				
		if( $this->AUTO->is_current_plugin_registered( CSV2POST_BASENAME ) )
		{
			echo $this->UI->notice_return( 
				'info', 
				'Small', 
				false,/* title */ 
				__( 'CSV 2 POST has been registered for automation and further
				configuration options are now available. You can setup
				individual actions (PHP methods/functions) to run in the 
				WebTechGlobal schedule system.', 'csv2post' ), 
				false, 
				true 
			);
		}
		else
		{
			$this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        	$this->UI->postbox_content_footer();
		}
    }
          
    /**
    * Submit to execute every active action in the schedule table.
    * 
    * Originally used for testing the plugins abilities to run methods but
    * it can be a great tool to initiate massive amounts of administration.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.1
    */
    public function postbox_advancedschedule_executeallactions( $data, $box ) { 
        global $csv2post_settings;
        $formid = $box['args']['formid'];
		$intro = __( 'Submit this form with care. It will execute every active
		action in the schedule table. You can view stored actions on the Scheduled
		Actions Table view. Use this to test the plugin, test your actions or catchup with
		administration. Just remember that it may be demanding on your server if
		you have many actions setup.', 'csv2post' );

		$this->UI->postbox_content_header( 
		    $box['title'], 
		    $box['args']['formid'],
		    $intro, 
		    false 
		);        

		// TODO 5 -o Ryan Bayne -c Information: Display some active actions here.
		
		$this->FORMS->form_start( $formid, $formid, $box['title'] );
        $this->UI->postbox_content_footer();
    }  
             
    /**
    * Form for scheduling a new event.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo display actions from all registered plugins.
    */
    public function postbox_advancedschedule_createnewevent( $data, $box ) { 
        global $csv2post_settings;
        $formid = $box['args']['formid'];
		$intro = __( 'Schedule a new event that involves running any
		available action. New actions can be setup in the class-schedule.php by
		a developer who knows WordPress and PHP.', 'csv2post' );

		$this->UI->postbox_content_header( 
		    $box['title'], 
		    $box['args']['formid'],
		    $intro, 
		    false 
		);        

		// Build array of methods from class-schedule.php which are setup as auto actions.
		$auto_actions = array();// populate directly from schedule-class.php

		// Array for passing to input function i.e. menu or checkboxes.
		$items_array = array();
		    	
		// Get automated actions settings, this option allows integration.
		// All actions must be registered here first to use at all.
		$actionsettings = get_option( 'csv2post_auto_actionsettings' );

		$this->FORMS->form_start( $formid, $formid, $box['title'] );
		
		echo '<table class="form-table">';
		
		// Get the plugins that have been registered in the automation system.
		$auto_plugins = get_option( 'csv2post_auto_plugins' );
		   
		if( !is_array( $auto_plugins ) ) 
		{   // TODO 3 -o Ryan Bayne -c Setting: Add link for registering plugin.
			$message = __( 'No plugins have been registered for automation. You must
			register CSV 2 POST for automation so that this plugins auto actions can
			be displayed on this form. You will find a form for registering the
			plugin on this view.', 'csv2post' );	
			
			$this->FORMS->input_subline(
			    $message,
			    __( 'No Automated Plugins Registered', 'csv2post')     
			);			
		}
		else 
		{          
		    $i = 0;
		    foreach( $auto_plugins as $pluginname => $plugindetails )
		    {   
		    	// Clean hyphens from plugin name, pointless hyphens! 
		    	$pluginname = str_replace( '-', '', $pluginname );
		    	
			    // Build array which will be seralized and become item values.
			    $val = array( 
			    	'name' => $pluginname, 
			    	'title' => $plugindetails['title'],
			    	'class' => strtoupper( str_replace( '-', '', $pluginname ) ) . "_Schedule"
			    ); 

		        if( $plugindetails['status'] === true )
		        {
					// Get auto_ methods from schedule array for this plugin.
					$new_actions = $this->AUTO->get_plugins_schedule_methods( $pluginname, $plugindetails );
					
					if( !empty( $new_actions ) ) 
					{         					
						foreach( $new_actions as $key => $method_name )
						{                   
							// Do not add this method (auto action) if it has not been registered.
							if( !isset( $actionsettings[$pluginname][$val['class']][$method_name]['status'] ) ) 
							{   
								continue;
							}
							
							// The method has been registered but if it is disabled do not display it in menu.
							if( !$actionsettings[$pluginname][$val['class']][$method_name]['status'] )
							{				
								continue;	
							}
								
                            $val['method'] = $method_name;
					
							// Item label.
							$lab = $plugindetails['title'] . ' ' . $val['class'] . ' ' . $method_name;
			
				            // Serialize and encode to create unique key.
				            $uniquekey = base64_encode( serialize( $val ) );
		
							$items_array[ $uniquekey ] = $lab;
						}
					}                                                            
				}	
				unset($this->AUTO);
			}
		}   
		
		// Display list of actions for all plugins.		           
		if( !is_array( $items_array ) || empty( $items_array ) ) 
		{
			$message = __( 'No automated actions were found. A 
			developer can add methods to the schedule-class.php 
			file and that will setup new automated actions.', 'csv2post' );	
			
			$this->FORMS->input_subline(
			    $message,
			    __( 'Automated Actions List', 'csv2post')     
			);			
		}
		else 
		{			
			// Menu of actions to be fired in the scheduled event.
			$this->FORMS->menu_basic( 
				$formid, 
				'selectanaction', 
				'selectanaction', 
				__( 'Select Action', 'csv2post' ), 
				$items_array, 
				true, 
				'', 
				array() 
			);
		} 
			
		// jQuery UI Date and Time picker.		
		$current_value = array();
		$this->FORMS->datatimepicker_basic( 
			$formid, 
			'eventdatetime', 
			'eventdatetime', 
			__( 'Data and Time', 'csv2post' ), 
			$current_value, 
			true, 
			array() 
		);
			
		// Menu of actions to be fired in the scheduled event.
		$this->FORMS->menu_basic( 
			$formid, 
			'recurrencetype', 
			'recurrencetype', 
			__( 'Recurrence', 'csv2post' ), 
			array( 'repeat' => __( 'Repeat', 'csv2post' ), 'once' => __( 'Once', 'csv2post' ) ), 
			true, 
			'', 
			array() 
		);
						
		/*
			The WebTechGlobal Cron system applies a delay in seconds.
			When re-scheduling, the delay stored in the schedule table
			is added to time() to set the next event. 
			
			WordPress cron would use hourly, twicedaily and daily. The
			column that holds those values (recurrence) is not used in
			the WebTechGlobal version of a cron system. 
		*/
		
		// TODO: apply range validation ensure weekly (monthly and annual will be in premium products)	
		$this->FORMS->text_basic( 
			$formid, 
			'eventdelay', 
			'eventdelay', 
			__( 'Recurrence Delay', 'csv2post' ), 
			'3600', 
			false, 
			array() 
		);
			
		$this->FORMS->menu_basic( 
			$formid, 
			'eventweight', 
			'eventweight', 
			__( 'Events Weight', 'csv2post' ), 
			array(  '1' => __( '1', 'csv2post' ), 
					'2' => __( '2', 'csv2post' ), 
					'3' => __( '4', 'csv2post' ), 
					'5' => __( '5', 'csv2post' ), 
					'6' => __( '6', 'csv2post' ), 
					'7' => __( '7', 'csv2post' ), 
					'8' => __( '8', 'csv2post' ), 
					'9' => __( '9', 'csv2post' ), 
					'10' => __( '10', 'csv2post' )
			), 
			true, 
			'', 
			array() 
		);
									
		echo '</table>';
		
        $this->UI->postbox_content_footer();
    }                    
}
   
/**
* Example of WP List Table class for display on this view only.
* 
* @author Ryan R. Bayne
* @package CSV 2 POST
* @version 2.0
*/
class WORDPRESSPLUGINCSV2POST_WPTable_Example extends WP_List_Table {
    
    private $bulkid = 'schedulebulk';// ID for checkboxes.
    private $perPage_option = 'items_per_page';// Limits number of records.

    /**
    * WTG approach to managing actions is a little quicker to configure.
    * 
    * @var mixed
    */
    private $full_actions = array(
        'dump' => array( 'label' => 'Dump', 'rowaction' => true, 'capability' => 'activate_plugins' ),
        'delete' => array( 'label' => 'Delete', 'rowaction' => true, 'capability' => 'activate_plugins' ),            
    );
    
    // Column Display Capability Requirements $colcap_
    private $colcap_rating = 'developer';
    
    /** 
    * Class constructor 
    * 
    * @version 1.0
    */
    public function __construct() 
    {
        parent::__construct( [
            'singular' => __( 'Action', 'csv2post' ), //singular name of the listed records
            'plural'   => __( 'Actions', 'csv2post' ), //plural name of the listed records
            'ajax'     => false //should this table support ajax?
        ] );

    }
    
    /**
     * Prepare the items for the table to process.
     *
     * @return Void
     * @version 1.0
     */
    public function prepare_items()
    {
        // Process bulk action.
        $this->process_bulk_action();
        
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );
        $perPage = $this->get_items_per_page( $this->perPage_option, 5 );
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    
    /**
     * Override the parent columns method. Defines the 
     * columns to use in your listing table.
     *
     * @version 1.0
     * @return Array
     */
    public function get_columns()
    {
        // Add all columns to this array.
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'rowid'           => 'ID',
            'timesapplied'        => 'Times Applied',
            'plugin'  => 'Plugin',
            'method'         => 'Method/Function',
            'lastupdate'     => 'Updated',
            'recurrence'       => 'Recurrence',
            'basepath'       => 'Basepath',
            'active'       => 'Active',
        );

        // Columns not permitted to be seen by current user will be removed here.
        foreach( $columns as $the_column => $the_label ) {
            
            // Check for class private variable holding capabilitiy.
            $one = 'colcap_' . $the_column;
            eval( '$cap = $this->$one;' );
             
            if( $cap != null && !current_user_can( $cap ) ) {
                unset( $columns[ $the_column ] );
            } 
  
        }
                
        return $columns;
    }
    
    /**
     * Define which columns are hidden.
     *
     * @version 1.0
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
        
    /**
     * Define the sortable columns.
     *
     * @version 1.0
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }
    
    /**
     * Get the table data. 
     * 
     * In example we use an array but a live table would query data or use
     * a cache. Keep in mind a cache must be destroyed if changes to the original
     * source or bulk actions on the table. 
     * 
     * @version 1.0
     * @return Array
     */
    private function table_data()
    {
    	global $wpdb;
    	$DB = new CSV2POST_DB();
    	return $DB->selectwherearray( 
    		$wpdb->webtechglobal_schedule, 
    		'active = 1',
    		'rowid',  
    		'*', 
    		'ARRAY_A', 
    		'ASC' 
    	);
    }
    
    /**
    * Display message when no items are available.
    * 
    * @version 1.0 
    */
    public function no_items() {
      _e( 'No actions have been scheduled.', 'csv2post' );
    }
    
    /**
     * Define what data to show on each column of the table.
     *
     * @version 1.0
     * 
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'cb':
            case 'rowid':
            case 'timesapplied':
            case 'plugin':
            case 'method':
            case 'lastupdate':
            case 'recurrence':
                return $item[ $column_name ];
            case 'basepath':
                return $item[ $column_name ];
            case 'active':
            	// TODO 3 -o Ryan Bayne -c UI: Convert boolean to Yes and No.
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
    
    /**
     * Allows you to sort the data by the variables set in the $_GET
     * 
     * @version 1.0
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'rowid';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }
    
    /**
    * Setup bulk actions. Customized by WebTechGlobal to use a more global
    * array which is needed when applying capabilities per action throughout
    * the class.
    * 
    * @version 1.1 
    */
    function get_bulk_actions( $return = 'normal' ) 
    {
        // Bulk actions not permitted by current user will be removed here.
        foreach( $this->full_actions as $the_action => $a ) {
            if( !current_user_can( $a['capability'] ) ) {
                unset( $this->full_actions[ $the_action ] );
            } 
        }
        
        // Build the standard actions array.
        foreach( $this->full_actions as $the_action => $a ) {
            $this->full_actions[ $the_action ] = $a['label'];
        }        
                
        // Return the standard array needed by WP core approach.  
        return $this->full_actions;
    }
    
    /**
    * Checkboxes for bulk actions.
    * 
    * @version 1.0
    * 
    * @param mixed $item
    */
    function column_cb( $item ) 
    {                 
    	return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->bulkid,
            /*$2%s*/ $item['rowid'] //The value of the checkbox should be the record's id
        );   
    }
    
    /**
    * ID Column Method - often the first column and the one with row actions.
    *
    * @version 1.0
    * @param array $item an array of DB data
    * @return string
    */
    function column_id( $item ) 
    {
        $title = '<strong>' . $item['id'] . '</strong>';

        return $title . $this->row_actions( $this->build_row_actions( $item['rowid'] ) );
    }
    
    /**
    * Builds the <a href for the main row actions - usually the first column. 
    * 
    * This method is an addition by Ryan R. Bayne to work alongside the actions
    * array which was also added by Ryan. You will not find this approach in most
    * examples of the WP_List_Table use. However it is a totally acceptable 
    * approach as security is still applied.
    * 
    * @version 1.0
    * @param mixed $item_id
    */
    function build_row_actions( $item_id ) 
    {
        // Final Actions Array
        $final_actions = array();

        foreach( $this->full_actions as $the_action => $a ) {

            // Does current user have permission to view and use this action?
            if( !current_user_can( $a['capability'] ) ) {
                continue;
            } 
                        
            // Create a nonce for this action.
            $nonce = wp_create_nonce( 'csv2post_' . $the_action . '_items' );
                                     
            // Build action link.        
            $final_actions[ $the_action ] = 
                    sprintf( '<a href="?page=%s&action=%s&item=%s&_wpnonce=%s">' . $a['label'] . ' - ' . $the_action . '</a>', 
                    esc_attr( $_REQUEST['page'] ), 
                    $the_action, 
                    absint( $item_id ), 
                    $nonce 
                );
                        
        } 
                            
        return $final_actions;
                
    }
    
    /**
    * Process bulk actions.
    * 
    * @version 1.1
    */
    public function process_bulk_action() 
    {
        if( !$this->current_action() ) { return; }
               
        // User must have permission or die! 
        if( !current_user_can( $this->full_actions[ $this->current_action() ]['capability'] ) ) {
            die( __( 'You do not have permission to perform this action.', 'csv2post' ) );
        } 

        switch ( $this->current_action() ) {
            case 'dump':
                
                if( isset( $_POST[ $this->bulkid ] ) ) {
                    // Operated using checkboxes.
                    var_dump( $_POST[ $this->bulkid ] );    
                }
                else {
                    // Operated using a single link often styled like a button.
                    var_dump( $_GET[ 'item' ] );    
                }
                
                break;
            case 'delete':
 
                // If the delete bulk action is submitted.
                if ( isset( $_POST[ $this->bulkid ]  ) )
                {
                    $delete_ids = esc_sql( $_POST[ $this->bulkid ] );

                    foreach ( $delete_ids as $id ) {
                        self::delete_item( $id );
                    }

                    // TODO 7 -o Ryan Bayne -c UI: replace wp_die with a notice. 
                    wp_die( __( 'Your items have been deleted.', 'csv2post' ) );
                }

                break;
           default;
                wp_die( __( 'Bulk action not added to process_bulk_action() please report this.', 'csv2post' ) );
                break;
        }

    }

    /**
    * Delete an item.
    *
    * @version 1.0
    * @param int $id item ID
    */
    public static function delete_item( $rowid ) {
        global $wpdb;
        return CSV2POST_DBTrait::delete( $wpdb->webtechglobal_schedule, 'rowid = ' . $rowid );
    }
  
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list.
     * 
     * This function can be removed.
     * 
     * @version 1.0
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
          //The code that goes before the table is here
          //echo"Hello, I'm before the table";
       }
       if ( $which == "bottom" ){
          //The code that goes after the table is there
          //echo"Hi, I'm after the table";
       }
    }

}
?>