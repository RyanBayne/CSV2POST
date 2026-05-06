<?php
/**
 * Data Tools [page]   
 *
 * @package CSV 2 POST
 * @subpackage Views
 * @author Ryan Bayne   
 * @since 8.1.3
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class CSV2POST_Advanceddata_View extends CSV2POST_View {

    /**
     * Number of screen columns for post boxes on this screen
     *
     * @since 8.1.3
     *
     * @var int
     */
    protected $screen_columns = 1;
    
    protected $view_name = 'toolsfordata';
    
    public $purpose = 'normal';// normal, dashboard, customdashboard

    /**
    * Array of meta boxes, looped through to register them on views and as dashboard widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function meta_box_array() {
        // array of meta boxes + used to register dashboard widgets (id, title, callback, context, priority, callback arguments (array), dashboard widget (boolean) )   
        return $this->meta_boxes_array = array(

            // array( id, title, callback (usually parent, approach created by Ryan Bayne), context (position), priority, call back arguments array, add to dashboard (boolean), required capability
            array( $this->view_name . '-rechecksourcedirectory', __( 'Re-check Source Directory', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'rechecksourcedirectory' ), true, 'activate_plugins' ),
            array( $this->view_name . '-changecsvfilepath', __( 'Change CSV File Path', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'changecsvfilepath' ), true, 'activate_plugins' ),
            array( $this->view_name . '-urlimporttoexistingsource', __( 'URL Import To Existing Data Source', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'urlimporttoexistingsource' ), true, 'activate_plugins' ),
            array( $this->view_name . '-uploadfiletodatasource', __( 'Upload File To Existing Data Source', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'uploadfiletodatasource' ), true, 'activate_plugins' ),
            array( $this->view_name . '-listwaitingfiles', __( 'Waiting Files', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'listwaitingfiles' ), true, 'activate_plugins' ),
            
            // Still Testing
            array( $this->view_name . '-tooldataimport', __( 'Test Automated Data Import', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'tooldataimport' ), true, 'activate_plugins' ),
            array( $this->view_name . '-tooldataupdate', __( 'Test Automated Data Update', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'tooldataupdate' ), true, 'activate_plugins' ),
                                                     
       );    
    }
            
    /**
     * Set up the view with data and do things that are specific for this view
     *
     * @since 8.1.3
     *
     * @param string $action Action for this view
     * @param array $data Data for this view
     */
    public function setup( $action, array $data ) {
        global $csv2post_settings;
        
        // create constant for view name
        if(!defined( "CSV2POST_VIEWNAME") ){define( "CSV2POST_VIEWNAME", $this->view_name );}
        
        // add view introduction
        $this->add_text_box( 'viewintroduction', array( $this, 'viewintroduction' ), 'normal' );
        
        parent::setup( $action, $data );
  
        // using array register many meta boxes
        foreach( self::meta_box_array() as $key => $metabox ) {
            // the $metabox array includes required capability to view the meta box
            if( isset( $metabox[7] ) && current_user_can( $metabox[7] ) ) {
                $this->add_meta_box( $metabox[0], $metabox[1], $metabox[2], $metabox[3], $metabox[4], $metabox[5] );   
            }               
        }       
    }
     
    /**
    * Outputs the meta boxes
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.3
    * @version 1.1
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
    * @version 1.1
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
    * @since 8.1.32
    * @version 1.0.1
    */
    function parent( $data, $box ) {
        eval( 'self::postbox_' . $this->view_name . '_' . $box['args']['formid'] . '( $data, $box );' );
    }

    /**
    * This views dismissable introduction.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 0.0.1
    * @version 1.0
    */
    public function viewintroduction() {
        $main_title = __( 'Data Tools Introduction', 'csv2post' );
        
        $intro = __( 'These tools can be used to modify existing data sources.
        Take care when doing so and using a feature for the first time. CSV files
        may be deleted or put in directories that are not secure if care is not taking.', 'csv2post' );
        
        $title = false;//__( 'More Information', 'csv2post' );
        
        $info = false;//__( '<ol><li>Tutorials Coming Soon</li></ol>', 'csv2post' );
        
        $foot = false;//__( 'Get your tutorial link added to this list. Video, blog, forum and PDF documents accepted.', 'csv2post' );
        
        $this->UI->intro_box_dismissible( 'data-tools-introduction', $main_title, $intro, $info_area = true, $title, $info, $foot );               
    }
        
    /**
    * form for manual re-check of a sources directory
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_toolsfordata_rechecksourcedirectory( $data, $box ) { 
        $intro = __( 'Manual re-check of source directory will make the plugin switch to the most recent added .csv file.', 'csv2post' );
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], $intro, false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  

            <table class="form-table">
                <?php $this->UI->option_menu_datasources( 'Data Source', 'datasourceidforrecheck', 'datasourceidforrecheck' ); ?> 
            </table>

        <?php 
        $this->UI->postbox_content_footer();
    } 

    /**
    * post box function for testing
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_toolsfordata_changecsvfilepath( $data, $box ) {  
        global $wpdb;
        $query_results = $this->DB->selectwherearray( $wpdb->c2psources, 'sourceid = sourceid', 'sourceid', '*' );
        if(!$query_results){
            $intro = __( 'No sources were found.' );
        }else{
            $intro = __( 'The new .csv file must have identical configuration...' );
        }
          
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], $intro, false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  

        <?php if( $query_results){?>
        
            <table class="form-table">
                <?php $this->UI->option_text_simple( __( 'New Path' ), 'newpath', '', true);?> 
                <?php $this->UI->option_menu_datasources(); ?>
            </table>
        
        <?php }?>
        
        <?php 
        $this->UI->postbox_content_footer();
    } 
        
    /**
    * import a new file via URL to an existing data source directory
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_toolsfordata_urlimporttoexistingsource( $data, $box ) { 
        $intro = __( 'Import a .csv file via URL to an existing data source directory to add newer data.', 'csv2post' );
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], $intro, false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  

            <table class="form-table">

            <?php
            $this->UI->option_text_simple( __( 'URL', 'csv2post' ), 'newdatasourcetheurl','', true, 'URL' );
            $this->UI->option_menu_datasources( 'Data Source', 'newprojectdatasource', 'newprojectdatasource' );
            ?>
            
            </table>

        <?php 
        $this->UI->postbox_content_footer();
    }     
    
    /**
    * upload a new file via form to an existing data source directory
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_toolsfordata_uploadfiletodatasource( $data, $box ) { 
        $intro = __( 'Upload a .csv file to an existing data source directory for adding newer data.', 'csv2post' );
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], $intro, false, true );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  

            <table class="form-table">

            <?php
            $this->UI->option_file( __( 'Select .csv File', 'csv2post' ), 'uploadsinglefile', 'uploadsinglefile' );
            $this->UI->option_menu_datasources( 'Data Source', 'datasourcefornewfile', 'datasourcefornewfile' );
            ?>
            
            </table>

        <?php 
        $this->UI->postbox_content_footer();
    } 

    /**
    * Change the current active project in admin.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.0
    * 
    * @todo change this simple list to a table with ability to quickly import data
    */
    public function postbox_toolsfordata_listwaitingfiles( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'A list of registered datasources that have not been fully imported
        according to the counters stored in the sources table.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );

        $waiting_files_array = $this->DB->find_waiting_files_new();
        
        if( is_array( $waiting_files_array ) ) {
        
            echo '<table class="form-table">';

            $this->FORMS->input_subline( __( 'Database Table', 'csv2post' ), __( 'Source ID', 'csv2post' ) );      
            
            foreach( $waiting_files_array as $waiting ){ 
                $this->FORMS->input_subline( $waiting['tablename'], $waiting['sourceid'] );      
            }
        
            echo '</table>';
            
            //$this->UI->postbox_content_footer();
            
        } else {
            _e( 'All of your registered .csv files have been fully imported
            according to the sources table. If you feel this is incorrect
            and you need more tools to diagnose the problem please come to the
            WebTechGlobal forum.', 'csv2post' );
        }              
    } 
    
    /**
    * Tool for testing the function data import function that is normally called within
    * schedule.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0                                                                
    */
    public function postbox_toolsfordata_tooldataimport( $data, $box ) {
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'Test a new data import function added in 2015 as part of improved
        automation. The function that is tested will only be called per schedule
        settings and automatically but this form allows us to test that it is working.', 'csv2post' ), false );  
              
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        
        echo '<table class="form-table">';

        $waiting_files_array = $this->DB->find_waiting_files_new();
                    
        $count = count( $waiting_files_array );

        $this->FORMS->input_subline( $count,
        __( 'Total New CSV Files Waiting', 'csv2post' ) );      

        echo '</table>'; 
        
        echo '<p>The count is the number of datasources that have not
        had their file fully imported. Creating multiple datasources using the
        same file will increase this counter.</p>';
        
        $this->UI->postbox_content_footer();        
        
    }
    
    /**
    * Tool for testing the data update function that is normally called within
    * schedule.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0                                                                
    */
    public function postbox_toolsfordata_tooldataupdate( $data, $box ) {
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'Use for testing new data update functionality added in 2015. Do not
        use frequently as there are other forms for that. The function in
        question may assume far more has been setup than actually has. Any errors
        may not be a fault but simply because the function designed for automation
        is being fired too early.', 'csv2post' ), false );  
              
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );

        echo '<table class="form-table">';

        // establish files that have changed and are waiting for data updating
        $waiting_files_array = $this->DB->find_waiting_files_old();
                    
        $count = count( $waiting_files_array );

        $this->FORMS->input_subline( $count,
        __( 'Total Old CSV Files Waiting', 'csv2post' ) );      

        echo '</table>'; 
        
        echo '<p>The count is the number of datasources that have not
        had their file fully imported since the file changed. A changed file requires
        the plugin to perform data updating. Controlling this in an automated
        fashion requires the plugin to track file changes and progress of re-import
        since the last change.</p>';
        
        $this->UI->postbox_content_footer();        
        
    }                         
}
?>