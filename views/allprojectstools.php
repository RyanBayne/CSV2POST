<?php
/**
 * All projects tools view.   
 *
 * @package CSV 2 POST
 * @subpackage Views
 * @author Ryan Bayne   
 * @since 8.2.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class CSV2POST_allprojectstools_View extends CSV2POST_View {

    /**
     * Number of screen columns for post boxes on this screen
     *
     * @since 8.1.3
     *
     * @var int
     */
    protected $screen_columns = 2;
    
    protected $view_name = 'allprojectstools';
    
    public $purpose = 'normal';// normal, dashboard, customdashboard

    /**
    * Array of meta boxes, looped through to register 
    * them on views and as dashboard widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function meta_box_array() {
        // array of meta boxes + used to register dashboard widgets (id, title, callback, context, priority, callback arguments (array), dashboard widget (boolean) )   
        return $this->meta_boxes_array = array(

            array( $this->view_name . '-newprojectusingexistingsource', __( 'Create New Project', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'newprojectusingexistingsource' ), true, 'activate_plugins' ),
            array( $this->view_name . '-deleteproject', __( 'Delete Project', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'deleteproject' ), true, 'activate_plugins' ),
            array( $this->view_name . '-setactiveproject', __( 'Set Currently Active Project', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'setactiveproject' ), true, 'activate_plugins' ),
            array( $this->view_name . '-listwaitingfiles', __( 'Waiting Files', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'listwaitingfiles' ), true, 'activate_plugins' ),
        
            // tools
            array( $this->view_name . '-toolpostcreation', __( 'Test Automated Post Creation', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'toolpostcreation' ), true, 'activate_plugins' ),
            array( $this->view_name . '-toolpostupdate', __( 'Test Automated Post Updating', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'toolpostupdate' ), true, 'activate_plugins' ),
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
        
        // create class objects
        $this->CONFIG = new CSV2POST_Configuration();
        // TODO Task: stop using $this->CSV2POST if possible
        $this->CSV2POST = CSV2POST::load_class( 'CSV2POST', 'class-csv2post.php', 'classes' );
        $this->UI = CSV2POST::load_class( 'CSV2POST_UI', 'class-ui.php', 'classes' );// extended by CSV2POST_Forms
        $this->DB = CSV2POST::load_class( 'CSV2POST_DB', 'class-wpdb.php', 'classes' );
        $this->PHP = CSV2POST::load_class( 'CSV2POST_PHP', 'class-phplibrary.php', 'classes' );
        $this->FORMS = CSV2POST::load_class( 'CSV2POST_FORMS', 'class-forms.php', 'classes' );

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
        $main_title = __( 'All Projects Tools Introduction', 'csv2post' );
        $intro = __( 'This view is a space for custom tools, report generating forms and quickly viewable statistics. More work is to be done here and users need to request what forms they need. We create new projects here but as the plugin grows we may need a single view for creation of projects.', 'csv2post' );
        $title = __( 'More Information', 'csv2post' );
        $info = __( '<ol><li>Tutorials Coming Soon</li></ol>', 'csv2post' );
        $foot = __( 'Get your tutorial link added to this list. Video, blog, forum and PDF documents accepted.', 'csv2post' );
        $this->UI->intro_box_dismissible( 'allprojectstools-introduction', $main_title, $intro, $info_area = true, $title, $info, $foot );               
    }
        
    /**
    * post box function
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_allprojectstools_newprojectusingexistingsource( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'A project holds the settings that configure the posts you wish to create. We can make multiple projects, even using the same imported data, but each creating different posts in different ways.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  

            <table class="form-table">
                <?php
                $this->UI->option_text( 'Project Name', 'newprojectname', 'newprojectname', '' );
                $this->UI->option_switch( 'Apply Defaults', 'applydefaults', 'applydefaults', false, 'Yes', 'No', 'disabled' );
                $this->UI->option_menu_datasources( 'Data Source', 'newprojectdatasource', 'newprojectdatasource', null, true );
                ?>
            </table>
        
        <?php 
        $this->UI->postbox_content_footer();               
    }
         
    /**
    * Delete any project.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_allprojectstools_deleteproject( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'Delete a project. This will not delete posts created by a project or effect the data source the project is using.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  
                   
            <table class="form-table">
            <?php
                $rand = rand(100000,999999);
                $this->UI->option_text( 'Project ID', 'projectid', 'projectid', '' );
                $this->UI->option_text( 'Code', 'randomcode', 'randomcode', $rand, true);
                $this->UI->option_text( 'Confirm Code', 'confirmcode', 'confirmcode', '' );
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
    */
    public function postbox_allprojectstools_setactiveproject( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'Most of this plugins pages display the settings for a single project. This is called the Current Project. Enter a projects ID here to set it as the Current Project.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        ?>  
                   
            <table class="form-table">
            <?php
            $this->UI->option_text( 'Project ID', 'setprojectid', 'setprojectid', '' );
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
    public function postbox_allprojectstools_listwaitingfiles( $data, $box ) {    
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
    * Tool for testing the post creation function that is normally called within
    * schedule.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0                                                                
    */
    public function postbox_allprojectstools_toolpostcreation( $data, $box ) {
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'This is not your standard post creation tool. This fires a function
        specifically created for use within the automated system and it will be
        called in-line with your schedule settings. The purpose of this form is
        to test that post creation function. This is mostly a development tool and
        may be removed once the new automated system introduced in 2015 is known
        to work fine.', 'csv2post' ), false );  
              
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );

        $this->UI->postbox_content_footer();        
    }
    
    /**
    * Tool for testing the post updating function that is normally called within
    * schedule.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0                                                                
    */
    public function postbox_allprojectstools_toolpostupdate( $data, $box ) {
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], 
        __( 'Confirm the post updating function within the new automation system
        works. Do not use this tool or similar tools for testing automation too
        frequently.', 'csv2post' ), false );  
              
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );

        $this->UI->postbox_content_footer();        
    }
    
    /**
    * Tool for testing the function data import function that is normally called within
    * schedule.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @version 1.0                                                                
    */
    public function postbox_allprojectstools_tooldataimport( $data, $box ) {
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
    public function postbox_allprojectstools_tooldataupdate( $data, $box ) {
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
  
    
}?>