<?php
/**
 * Main [section] - Projects [page]
 * 
 * @package CSV 2 POST
 * @subpackage Views
 * @author Ryan Bayne   
 * @since 8.1.3
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * View class for Main [section] - Projects [page]
 * 
 * @package CSV 2 POST
 * @subpackage Views
 * @author Ryan Bayne
 * @since 8.1.3
 */
class CSV2POST_Main_View extends CSV2POST_View {

    /**
     * Number of screen columns for post boxes on this screen
     *
     * @since 8.1.3
     *
     * @var int
     */
    protected $screen_columns = 2;
    
    protected $view_name = 'main';
    
    public $purpose = 'normal';// normal, dashboard
    
    private $schedule = array();

    /**
    * Array of meta boxes, looped through to register them on views and as dashboard widgets
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function meta_box_array() {
        global $csv2post_settings;

        // array of meta boxes + used to register dashboard widgets (id, title, callback, context, priority, callback arguments (array), dashboard widget (boolean) )   
        $this->meta_boxes_array = array(
            // array( id, title, callback (usually parent, approach created by Ryan Bayne), context (position), priority, call back arguments array, add to dashboard (boolean), required capability
            array( $this->view_name . '-mailchimp', __( 'Please Subscribe for Updates', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'mailchimp' ), true, 'activate_plugins' ),                     
            array( $this->view_name . '-computersampledata', __( 'Computer Sample Data', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'computersampledata' ), true, 'activate_plugins' ),
            array( $this->view_name . '-twitterupdates', __( 'Twitter Updates', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'twitterupdates' ), true, 'activate_plugins' ),
            array( $this->view_name . '-facebook', __( 'Facebook', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'facebook' ), true, 'activate_plugins' ),       

            // settings group
            array( $this->view_name . '-globalswitches', __( 'Global Switches', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'globalswitches' ), true, 'activate_plugins' ),
            array( $this->view_name . '-globaldatasettings', __( 'Global Data Settings', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'globaldatasettings' ) , true, 'activate_plugins' ),
            array( $this->view_name . '-pagecapabilitysettings', __( 'Page Capability Settings', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'pagecapabilitysettings' ), true, 'activate_plugins' ),            
            array( $this->view_name . '-setactiveproject', __( 'Set Currently Active Project', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'setactiveproject' ), true, 'activate_plugins' ),
            array( $this->view_name . '-developertoolssetup', __( 'Developer Tools Setup', 'csv2post' ), array( $this, 'parent' ), 'side','default',array( 'formid' => 'developertoolssetup' ), true, 'activate_plugins' ),            
        );
        
        // add meta boxes that have conditions i.e. a global switch
        if( isset( $csv2post_settings['widgetsettings']['dashboardwidgetsswitch'] ) && $csv2post_settings['widgetsettings']['dashboardwidgetsswitch'] == 'enabled' ) {
            $this->meta_boxes_array[] = array( 'main-dashboardwidgetsettings', __( 'Dashboard Widget Settings', 'csv2post' ), array( $this, 'parent' ), 'normal','default',array( 'formid' => 'dashboardwidgetsettings' ), true, 'activate_plugins' );   
        }
        
        return $this->meta_boxes_array;                
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
        global $csv2post_settings, $CSV2POST_Class; 
        
        // create constant for view name
        if(!defined( "CSV2POST_VIEWNAME") ){define( "CSV2POST_VIEWNAME", $this->view_name );}
        
        // a lot of settings require the schedule array
        $this->schedule = $CSV2POST_Class->get_option_schedule_array();
                        
        // set current project values
        if( isset( $csv2post_settings['currentproject'] ) && $csv2post_settings['currentproject'] !== false ) {
            $this->project_object = $this->DB->get_project( $csv2post_settings['currentproject'] ); 
            if( !$this->project_object ) {
                $this->current_project_settings = false;
            } else {
                $this->current_project_settings = maybe_unserialize( $this->project_object->projectsettings ); 
            }
        }
                
        parent::setup( $action, $data );
        
        // only output meta boxes
        if( $this->purpose == 'normal' ) {
            self::metaboxes();// register meta boxes for the current view
        } elseif( $this->purpose == 'dashboard' ) {
            // do nothing - add_dashboard_widgets() in class-ui.php calls dashboard_widgets() from this class
        } elseif( $this->purpose == 'customdashboard' ) {
            return self::meta_box_array();// return meta box array
        } else {
            // do nothing 
        }       
    } 
    
    /**
     * Outputs the meta boxes
     * 
     * @author Ryan R. Bayne
     * @package CSV 2 POST
     * @since 8.1.33
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
    * @package CSV2POST
    * @since 8.1.33
    * @version 1.0.1
    */
    public function dashboard() { 
        // setup() is not called when viewing dashboard so we need to do some loading here
        $this->FORMS = CSV2POST::load_class( 'CSV2POST_FORMS', 'class-forms.php', 'classes' );
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
    * Mailchimp subscribers list form.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_main_mailchimp( $data, $box ) {  
    ?>
        <!-- Begin MailChimp Signup Form -->
        <link href="//cdn-images.mailchimp.com/embedcode/classic-081711.css" rel="stylesheet" type="text/css">
        <style type="text/css">
            #mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
            /* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
               We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
        </style>
        <div id="mc_embed_signup">
        <form action="//webtechglobal.us9.list-manage.com/subscribe/post?u=99272fe1772de14ff2be02fe6&amp;id=8e2bb719b3" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
            <div id="mc_embed_signup_scroll">
            <h2>Subscribe for Updates</h2>
        <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
        <div class="mc-field-group">
            <label for="mce-EMAIL">Email Address  <span class="asterisk">*</span>
        </label>
            <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
        </div>
        <div class="mc-field-group">
            <label for="mce-FNAME">First Name </label>
            <input type="text" value="" name="FNAME" class="" id="mce-FNAME">
        </div>
        <div class="mc-field-group">
            <label for="mce-LNAME">Last Name </label>
            <input type="text" value="" name="LNAME" class="" id="mce-LNAME">
        </div>
        <p>Powered by <a href="http://eepurl.com/2W_2n" title="MailChimp - email marketing made easy and fun">MailChimp</a></p>
            <div id="mce-responses" class="clear">
                <div class="response" id="mce-error-response" style="display:none"></div>
                <div class="response" id="mce-success-response" style="display:none"></div>
            </div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
            <div style="position: absolute; left: -5000px;"><input type="text" name="b_99272fe1772de14ff2be02fe6_8e2bb719b3" tabindex="-1" value=""></div>
            <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
            </div>
        </form>
        </div>
        <script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
        <!--End mc_embed_signup-->   
    <?php   

    }       
        
    /**
    * post box function for testing
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_main_computersampledata( $data, $box ) {    
        ?>  
 
        <p><?php _e( "Sample files of PC sales data is available. The file is stored on Google Docs and is public. Feel free
        to download and test the plugin using my sample data which I also use in tutorials."); ?>
        </p>
        
        <ol>
            <li><a href="https://docs.google.com/spreadsheets/d/1J140c85Kl5fE_9dQAIgSq-uATtD-Jtwje4G1WQFgBbU/edit?usp=sharing" id="csv2postviewmainfile" target="_blank">View Main File</a></li>
        </ol>        
         
        <h2>Advanced Multi-File Samples</h2> 
        
        <p><?php _e( "To push the plugin I've saved information from PC World, split into three separate files.
        This will be used to test multi-file projects and the data engine within CSV 2 POST."); ?>
        </p>
                  
        <h3><?php _e( 'View Original Spreadsheets' );?></h3>
        <ol>
            <li><a href="https://docs.google.com/spreadsheet/ccc?key=0An6BbeiXPNK0dHM5Q043QzBtTGw4QU9SeXNYdEM1UHc&usp=sharing" target="_blank">File 1: Main PC Details</a></li>
            <li><a href="https://docs.google.com/spreadsheet/ccc?key=0An6BbeiXPNK0dHlFZUx1V3p6bHJrOHJZMUNmcGRyUWc&usp=sharing" target="_blank">File 2: Specifications</a></li>
            <li><a href="https://docs.google.com/spreadsheet/ccc?key=0An6BbeiXPNK0dDhEdHZIYVJ4YkViUkQ3MTFESFdUR2c&usp=sharing" target="_blank">File 3: Descriptions and Images</a></li>
        </ol>
        
        <h3><?php _e( 'Download CSV files from Google' );?></h3>
        <p><?php _e( 'Warning: Google does not seem to handle the third file with text well, often adding line breaks in different places each time the file is downloaded. Simply correct them before using.' );?></p>

        <ol>
            <li><a href="https://docs.google.com/spreadsheet/pub?key=0An6BbeiXPNK0dHM5Q043QzBtTGw4QU9SeXNYdEM1UHc&output=csv" target="_blank">File 1: Main PC Details</a></li>
            <li><a href="https://docs.google.com/spreadsheet/pub?key=0An6BbeiXPNK0dHlFZUx1V3p6bHJrOHJZMUNmcGRyUWc&output=csv" target="_blank">File 2: Specifications</a></li>
            <li><a href="https://docs.google.com/spreadsheet/pub?key=0An6BbeiXPNK0dDhEdHZIYVJ4YkViUkQ3MTFESFdUR2c&output=csv" target="_blank">File 3: Descriptions and Images</a></li>
        </ol>             
            
        <?php                             
    }
    
    /**
    * post box function for testing
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_main_globalswitches( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'These switches disable or enable systems. Disabling systems you do not require will improve the plugins performance.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        
        global $csv2post_settings;
        ?>  

            <table class="form-table">
            <?php        
            $this->UI->option_switch( __( 'WordPress Notice Styles', 'csv2post' ), 'uinoticestyle', 'uinoticestyle', $csv2post_settings['noticesettings']['wpcorestyle'] );
            $this->UI->option_switch( __( 'Systematic Post Updating', 'csv2post' ), 'systematicpostupdating', 'systematicpostupdating', $csv2post_settings['standardsettings']['systematicpostupdating'] );
            $this->UI->option_switch( __( 'Dashboard Widgets Switch', 'csv2post' ), 'dashboardwidgetsswitch', 'dashboardwidgetsswitch', $csv2post_settings['widgetsettings']['dashboardwidgetsswitch'], 'Enabled', 'Disabled', 'disabled' );      
            ?>
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
    public function postbox_main_globaldatasettings( $data, $box ) {    
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'These are settings related to data management and apply to all projects.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        
        global $csv2post_settings;
        ?>  

            <table class="form-table">
            <?php
            $this->UI->option_text( 'Import/Insert Limit', 'importlimit', 'importlimit', $csv2post_settings['datasettings']['insertlimit'], false );
            ?>
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
    public function postbox_main_iconsexplained( $data, $box ) {    
        ?>  
        <p class="about-description"><?php _e( 'The plugin has icons on the UI offering different types of help...' ); ?></p>
        
        <h3>Help Icon<?php echo $this->UI->helpicon( 'http://www.webtechglobal.co.uk/csv-2-post' )?></h3>
        <p><?php _e( 'The help icon offers a tutorial or indepth description on the WebTechGlobal website. Clicking these may open
        take a key page in the plugins portal or post in the plugins blog. On a rare occasion you will be taking to another users 
        website who has published a great tutorial or technical documentation.' )?></p>        
        
        <h3>Discussion Icon<?php echo $this->UI->discussicon( 'http://www.webtechglobal.co.uk/csv-2-post' )?></h3>
        <p><?php _e( 'The discussion icon open an active forum discussion or chat on the WebTechGlobal domain in a new tab. If you see this icon
        it means you are looking at a feature or area of the plugin that is a hot topic. It could also indicate the
        plugin author would like to hear from you regarding a specific feature. Occasionally these icons may take you to a discussion
        on other websites such as a Google circles, an official page on Facebook or a good forum thread on a users domain.' )?></p>
                          
        <h3>Info Icon<img src="<?php echo CSV2POST_IMAGES_URL;?>info-icon.png" alt="<?php _e( 'Icon with an i click it to read more information in a popup.' );?>"></h3>
        <p><?php _e( 'The information icon will not open another page. It will display a pop-up with extra information. This is mostly used within
        panels to explain forms and the status of the panel.' )?></p>        
        
        <h3>Video Icon<?php echo $this->UI->videoicon( 'http://www.webtechglobal.co.uk/csv-2-post' )?></h3>
        <p><?php _e( 'clicking on the video icon will open a new tab to a YouTube video. Occasionally it may open a video on another
        website. Occasionally a video may even belong to a user who has created a good tutorial.' )?></p> 
               
        <h3>Trash Icon<?php echo $this->UI->trashicon( 'http://www.webtechglobal.co.uk/csv-2-post' )?></h3>
        <p><?php _e( 'The trash icon will be shown beside items that can be deleted or objects that can be hidden.
        Sometimes you can hide a panel as part of the plugins configuration. Eventually I hope to be able to hide
        notices, especially the larger ones..' )?></p>      
      <?php     
    }
    
    /**
    * post box function for testing
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_main_twitterupdates( $data, $box ) {    
        ?>
        <p class="about-description"><?php _e( 'If you are using the plugin it makes sense to support the project. Every tweet helps to promote it...', 'csv2post' ); ?></p>    
        <a class="twitter-timeline" href="https://twitter.com/CSV2POST" data-widget-id="478813344225189889">Tweets by @CSV2POST</a>
        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id) ){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document, "script", "twitter-wjs");</script>                                                   
        <?php     
    }    

    /**
    * post box function for testing
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function postbox_main_facebook( $data, $box ) {  
        $introduction = __( 'Get free and automatic entry into giveaways by
        Liking or commenting on the WTG Facebook page. You could be giving free
        hosting, domains, premium plugins and themes.', 'wtgeci' );       
        echo "<p class=\"csv2post_boxes_introtext\">". $introduction ."</p>";        
        ?>       
        <iframe src="//www.facebook.com/plugins/likebox.php?href=https%3A%2F%2Fwww.facebook.com%2FWebTechGlobal1&amp;width=350&amp;height=290&amp;colorscheme=light&amp;show_faces=true&amp;header=true&amp;stream=false&amp;show_border=true" scrolling="no" frameborder="0" style="padding: 10px 0 0 0;border:none; overflow:hidden; width:100%; height:290px;" allowTransparency="true"></iframe>                                                                             
        <?php     
    }

    /**
    * Form for setting which captability is required to view the page
    * 
    * By default there is no settings data for this because most people will never use it.
    * However when it is used, a new option record is created so that the settings are
    * independent and can be accessed easier.  
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.32
    * @version 1.1
    */
    public function postbox_main_pagecapabilitysettings( $data, $box ) {
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'Set the capability a user requires to view any of the plugins pages. This works independently of role plugins such as Role Scoper.', 'csv2post' ), false );        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        
        // get the tab menu 
        $pluginmenu = $this->TABMENU->menu_array();
        ?>
        
        <table class="form-table">
        
        <?php 
        // get stored capability settings 
        $saved_capability_array = get_option( 'csv2post_capabilities' );
        
        // add a menu for each page for the user selecting the required capability 
        foreach( $pluginmenu as $key => $page_array ) {
            
            // do not add the main page to the list as a strict security measure
            if( $page_array['name'] !== 'main' ) {
                $current = null;
                if( isset( $saved_capability_array['pagecaps'][ $page_array['name'] ] ) && is_string( $saved_capability_array['pagecaps'][ $page_array['name'] ] ) ) {
                    $current = $saved_capability_array['pagecaps'][ $page_array['name'] ];
                }
                
                $this->UI->option_menu_capabilities( $page_array['menu'], 'pagecap' . $page_array['name'], 'pagecap' . $page_array['name'], $current );
            }
        }?>
        
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
    public function postbox_main_dashboardwidgetsettings( $data, $box ) { 
        global $csv2post_settings;
           
        $this->UI->postbox_content_header( $box['title'], $box['args']['formid'], __( 'This panel is new and is advanced.   
        Please seek my advice before using it.
        You must be sure and confident that it operates in the way you expect.
        It will add widgets to your dashboard. 
        The capability menu allows you to set a global role/capability requirements for the group of wigets from any giving page. 
        The capability options in the "Page Capability Settings" panel are regarding access to the admin page specifically.', 'csv2post' ), false );   
             
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );

        echo '<table class="form-table">';

        // now loop through views, building settings per box (display or not, permitted role/capability  
        $CSV2POST_TabMenu = CSV2POST::load_class( 'CSV2POST_TabMenu', 'class-pluginmenu.php', 'classes' );
        $menu_array = $CSV2POST_TabMenu->menu_array();
        foreach( $menu_array as $key => $section_array ) {

            /*
                'groupname' => string 'main' (length=4)
                'slug' => string 'csv2post_generalsettings' (length=24)
                'menu' => string 'General Settings' (length=16)
                'pluginmenu' => string 'General Settings' (length=16)
                'name' => string 'generalsettings' (length=15)
                'title' => string 'General Settings' (length=16)
                'parent' => string 'main' (length=4)
            */
            
            // get dashboard activation status for the current page
            $current_for_page = '123nocurrentvalue';
            if( isset( $csv2post_settings['widgetsettings'][ $section_array['name'] . 'dashboardwidgetsswitch'] ) ) {
                $current_for_page = $csv2post_settings['widgetsettings'][ $section_array['name'] . 'dashboardwidgetsswitch'];   
            }
            
            // display switch for current page
            $this->UI->option_switch( $section_array['menu'], $section_array['name'] . 'dashboardwidgetsswitch', $section_array['name'] . 'dashboardwidgetsswitch', $current_for_page, 'Enabled', 'Disabled', 'disabled' );
            
            // get current pages minimum dashboard widget capability
            $current_capability = '123nocapability';
            if( isset( $csv2post_settings['widgetsettings'][ $section_array['name'] . 'widgetscapability'] ) ) {
                $current_capability = $csv2post_settings['widgetsettings'][ $section_array['name'] . 'widgetscapability'];   
            }
                            
            // capabilities menu for each page (rather than individual boxes, the boxes will have capabilities applied in code)
            $this->UI->option_menu_capabilities( __( 'Capability Required', 'csv2post' ), $section_array['name'] . 'widgetscapability', $section_array['name'] . 'widgetscapability', $current_capability );
        }

        echo '</table>';
                    
        $this->UI->postbox_content_footer();
    }    

    /**
    * post box function
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 8.1.33
    * @version 1.1
    */
    public function postbox_main_setactiveproject( $data, $box ) {    
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
    * Activate developers and other primary developer settings.
    * 
    * @author Ryan Bayne
    * @package CSV 2 POST
    * @since 0.0.3
    * @version 1.2
    */
    public function postbox_main_developertoolssetup( $data, $box ) { 
        global $wp_roles, $CSV2POST_Class;
        
        $intro = __( 'WebTechGlobal plugins have built in tools to
        aid development. The tools are better understood by WTG staff
        but any good developer can learn to use them.
        To get started you must first add the Developer role and assign
        a user as a developer. The Developer role should include all the 
        capabilities of an Administrator and some custom capabilities
        built into this plugin. I recommend you check all of the boxes
        below as a developer would normally have full access to every
        aspect of your website.', 'csv2post' );
        
        $this->UI->postbox_content_header( 
            $box['title'], 
            $box['args']['formid'],
            $intro, 
            false 
        );        
        
        $this->FORMS->form_start( $box['args']['formid'], $box['args']['formid'], $box['title'] );
        
        $caps = $CSV2POST_Class->capabilities();
        
        // build array of WP roles
        $custom_roles_array = array();
        foreach( $caps as $c => $bool ) {
            $custom_roles_array[ $c ] = $c;    
        }         
        ?>
                
        <table class="form-table">

        <?php 
        // option to install developer role
        $developer_role_status = __( 'Not Installed', 'csv2post' );
        foreach( $wp_roles->roles as $role_name => $role_array ) {
            if( $role_name == 'developer' ) {
                $developer_role_status = __( 'Installed', 'csv2post' );    
            }            
        }

        $this->FORMS->input_subline(
            $developer_role_status,
            __( 'Developer Role Status', 'csv2post')     
        );        

        if( $developer_role_status == 'Not Installed' ) {
            $this->FORMS->checkboxesgrouped_basic( 
                $box['args']['formid'], 
                'addrolecapabilities', 
                'addrolecapabilities', 
                __( 'Select Capabilities', 'csv2post' ), 
                $custom_roles_array, 
                array(), 
                true, 
                array()
            );
        }
        ?>
        
        </table> 
    
        <?php   
        $this->UI->postbox_content_footer();
    }
                   
}?>