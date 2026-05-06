<?php
/**
* Beta testing only (check if in use yet) - phasing array files into 
* classes of their own then calling into the main class
*/
class CSV2POST_TabMenu {
    public function menu_array() {
        $menu_array = array();
        
        ######################################################
        #                                                    #
        #                        MAIN                        #
        #                                                    #
        ######################################################
        // can only have one view in main right now until WP allows pages to be hidden from showing in
        // plugin menus. This may provide benefit of bringing user to the latest news and social activity
        // main page
        $menu_array['main']['groupname'] = 'main';        
        $menu_array['main']['slug'] = 'csv2post';// home page slug set in main file
        $menu_array['main']['menu'] = 'CSV 2 POST Dashboard';// plugin admin menu
        $menu_array['main']['pluginmenu'] = __( 'CSV 2 POST Dashboard' ,'csv2post' );// for tabbed menu
        $menu_array['main']['name'] = "main";// name of page (slug) and unique
        $menu_array['main']['title'] = 'Dashboard';// title at the top of the admin page
        $menu_array['main']['parent'] = 'parent';// either "parent" or the name of the parent - used for building tab menu         
        $menu_array['main']['tabmenu'] = false;// boolean - true indicates multiple pages in section, false will hide tab menu and show one page 
                                  
        ######################################################
        #                                                    #
        #                   Setup Section                    #
        #                                                    #
        ###################################################### 
        // setupcsv 
        $menu_array['setupcsv']['groupname'] = 'setupsection';
        $menu_array['setupcsv']['slug'] = 'csv2post_setupcsv'; 
        $menu_array['setupcsv']['menu'] = __( 'Setup', 'csv2post' );
        $menu_array['setupcsv']['pluginmenu'] = __( '1: CSV File', 'csv2post' );
        $menu_array['setupcsv']['name'] = "setupcsv";
        $menu_array['setupcsv']['title'] = __( 'Add CSV Files', 'csv2post' ); 
        $menu_array['setupcsv']['parent'] = 'parent'; 
        $menu_array['setupcsv']['tabmenu'] = true;

        // setupcampaign 
        $menu_array['setupproject']['groupname'] = 'setupsection';
        $menu_array['setupproject']['slug'] = 'csv2post_setupproject'; 
        $menu_array['setupproject']['menu'] = __( 'Project', 'csv2post' );
        $menu_array['setupproject']['pluginmenu'] = __( '2: Project', 'csv2post' );
        $menu_array['setupproject']['name'] = "setupproject";
        $menu_array['setupproject']['title'] = __( 'Setup New Project', 'csv2post' ); 
        $menu_array['setupproject']['parent'] = 'setupcsv'; 
        $menu_array['setupproject']['tabmenu'] = true;
    
        // Setup Data Rules  
        $menu_array['setuprules']['groupname'] = 'setupsection';
        $menu_array['setuprules']['slug'] = 'csv2post_setuprules'; 
        $menu_array['setuprules']['menu'] = __( 'Data Rules', 'csv2post' );
        $menu_array['setuprules']['pluginmenu'] = __( '3: Data Rules', 'csv2post' );
        $menu_array['setuprules']['name'] = "setuprules";
        $menu_array['setuprules']['title'] = __( 'Data Rules', 'csv2post' ); 
        $menu_array['setuprules']['parent'] = 'setupcsv';  
        $menu_array['setuprules']['tabmenu'] = true;
        
        // Import Data (current project) 
        $menu_array['setupimport']['groupname'] = 'setupsection';
        $menu_array['setupimport']['slug'] = 'csv2post_setupimport'; 
        $menu_array['setupimport']['menu'] = __( 'Import Data', 'csv2post' );
        $menu_array['setupimport']['pluginmenu'] = __( '4: Import Data', 'csv2post' );
        $menu_array['setupimport']['name'] = "setupimport";
        $menu_array['setupimport']['title'] = __( 'Import Data', 'csv2post' ); 
        $menu_array['setupimport']['parent'] = 'setupcsv'; 
        $menu_array['setupimport']['tabmenu'] = true;

        // Setup Design  
        $menu_array['setupdesign']['groupname'] = 'setupsection';
        $menu_array['setupdesign']['slug'] = 'csv2post_setupdesign'; 
        $menu_array['setupdesign']['menu'] = __( 'Setup Design', 'csv2post' );
        $menu_array['setupdesign']['pluginmenu'] = __( '5: Design', 'csv2post' );
        $menu_array['setupdesign']['name'] = "setupdesign";
        $menu_array['setupdesign']['title'] = __( 'Setup Design', 'csv2post' ); 
        $menu_array['setupdesign']['parent'] = 'setupcsv'; 
        $menu_array['setupdesign']['tabmenu'] = true;
        
        // Setup Posts (early posts, will be updated by later form submission)
        $menu_array['setupposts']['groupname'] = 'setupsection'; 
        $menu_array['setupposts']['slug'] = 'csv2post_setupposts';// home page slug set in main file
        $menu_array['setupposts']['menu'] = '6. Posts';// main menu title
        $menu_array['setupposts']['pluginmenu'] = '6. Posts';// main menu title        
        $menu_array['setupposts']['name'] = "setupposts";// name of page (slug) and unique
        $menu_array['setupposts']['title'] = 'Create Basic Posts';// page title seen once page is opened
        $menu_array['setupposts']['parent'] = 'setupcsv';// either "parent" or the name of the parent - used for building tab menu    
        $menu_array['setupposts']['tabmenu'] = true;
        
        ######################################################
        #                                                    #
        #                 POST OPTIONS SECTION               #
        #                                                    #
        ######################################################
        
        // postcategories  
        $menu_array['postcategories']['groupname'] = 'postoptions';
        $menu_array['postcategories']['slug'] = 'csv2post_postcategories'; 
        $menu_array['postcategories']['menu'] = __( 'Basic Options', 'csv2post' );
        $menu_array['postcategories']['pluginmenu'] = __( 'Categories', 'csv2post' );
        $menu_array['postcategories']['name'] = "postcategories";
        $menu_array['postcategories']['title'] = __( 'Basic Categories', 'csv2post' ); 
        $menu_array['postcategories']['parent'] = 'parent'; 
        $menu_array['postcategories']['tabmenu'] = true;        
        
        // postsettings  
        $menu_array['postsettings']['groupname'] = 'postoptions';
        $menu_array['postsettings']['slug'] = 'csv2post_postsettings'; 
        $menu_array['postsettings']['menu'] = __( '3. Design', 'csv2post' );
        $menu_array['postsettings']['pluginmenu'] = __( 'Post Settings', 'csv2post' );
        $menu_array['postsettings']['name'] = "postsettings";
        $menu_array['postsettings']['title'] = __( 'Post Settings', 'csv2post' ); 
        $menu_array['postsettings']['parent'] = 'postcategories';         
        $menu_array['postsettings']['tabmenu'] = true;
               
        // dates  
        $menu_array['dates']['groupname'] = 'postoptions';
        $menu_array['dates']['slug'] = 'csv2post_dates'; 
        $menu_array['dates']['menu'] = __( 'Dates', 'csv2post' );
        $menu_array['dates']['pluginmenu'] = __( 'Dates', 'csv2post' );
        $menu_array['dates']['name'] = "dates";
        $menu_array['dates']['title'] = __( 'Dates', 'csv2post' ); 
        $menu_array['dates']['parent'] = 'postcategories';     
        $menu_array['dates']['tabmenu'] = true;

        // customfields
        $menu_array['customfields']['groupname'] = 'postoptions'; 
        $menu_array['customfields']['slug'] = 'csv2post_customfields';// home page slug set in main file
        $menu_array['customfields']['menu'] = '4. Meta';// main menu title
        $menu_array['customfields']['pluginmenu'] = 'Custom Fields';// main menu title        
        $menu_array['customfields']['name'] = "customfields";// name of page (slug) and unique
        $menu_array['customfields']['title'] = 'Custom Fields';// page title seen once page is opened
        $menu_array['customfields']['parent'] = 'postcategories';// either "parent" or the name of the parent - used for building tab menu    
        $menu_array['customfields']['tabmenu'] = true;
                     
        ######################################################
        #                                                    #
        #                   ADVANCED SECTION                 #
        #                                                    #
        ###################################################### 
    
        // advanceddata  
        $menu_array['advanceddata']['groupname'] = 'advancedsection';
        $menu_array['advanceddata']['slug'] = 'csv2post_advanceddata'; 
        $menu_array['advanceddata']['menu'] = __( 'Advanced Options', 'csv2post' );
        $menu_array['advanceddata']['pluginmenu'] = __( 'Data Tools', 'csv2post' );
        $menu_array['advanceddata']['name'] = "advanceddata";
        $menu_array['advanceddata']['title'] = __( 'Advanced Data Tools', 'csv2post' ); 
        $menu_array['advanceddata']['parent'] = 'parent'; 
        $menu_array['advanceddata']['tabmenu'] = true;
       
        // replacevaluerules  
        $menu_array['advancedreplacevaluerules']['groupname'] = 'advancedsection';
        $menu_array['advancedreplacevaluerules']['slug'] = 'csv2post_advancedreplacevaluerules'; 
        $menu_array['advancedreplacevaluerules']['menu'] = __( 'Replace Value Rules', 'csv2post' );
        $menu_array['advancedreplacevaluerules']['pluginmenu'] = __( 'Replace Value Rules', 'csv2post' );
        $menu_array['advancedreplacevaluerules']['name'] = "advancedreplacevaluerules";
        $menu_array['advancedreplacevaluerules']['title'] = __( 'Advanced Replace Value Rules', 'csv2post' ); 
        $menu_array['advancedreplacevaluerules']['parent'] = 'advanceddata';
        $menu_array['advancedreplacevaluerules']['tabmenu'] = true;
             
        // taxonomies
        $menu_array['advancedtaxonomies']['groupname'] = 'advancedsection';
        $menu_array['advancedtaxonomies']['slug'] = 'csv2post_advancedtaxonomies';// home page slug set in main file
        $menu_array['advancedtaxonomies']['menu'] = __( 'Taxonomies', 'csv2post' );// main menu title
        $menu_array['advancedtaxonomies']['pluginmenu'] = __( 'Taxonomies', 'csv2post' );// main menu title
        $menu_array['advancedtaxonomies']['name'] = "advancedtaxonomies";// name of page (slug) and unique
        $menu_array['advancedtaxonomies']['title'] = __( 'Advanced Taxonomies', 'csv2post' );// page title seen once page is opened 
        $menu_array['advancedtaxonomies']['parent'] = 'advanceddata';// either "parent" or the name of the parent - used for building tab menu   
        $menu_array['advancedtaxonomies']['tabmenu'] = true;
                    
        // posttypes  
        $menu_array['advancedposttypes']['groupname'] = 'advancedsection';
        $menu_array['advancedposttypes']['slug'] = 'csv2post_advancedposttypes'; 
        $menu_array['advancedposttypes']['menu'] = __( 'Post Types', 'csv2post' );
        $menu_array['advancedposttypes']['pluginmenu'] = __( 'Post Types', 'csv2post' );
        $menu_array['advancedposttypes']['name'] = "advancedposttypes";
        $menu_array['advancedposttypes']['title'] = __( 'Advanced Post Types', 'csv2post' ); 
        $menu_array['advancedposttypes']['parent'] = 'advanceddata';
        $menu_array['advancedposttypes']['tabmenu'] = true;
                    
        // schedule  
        $menu_array['advancedschedule']['groupname'] = 'advancedsection';
        $menu_array['advancedschedule']['slug'] = 'csv2post_advancedschedule'; 
        $menu_array['advancedschedule']['menu'] = __( 'Schedule', 'csv2post' );
        $menu_array['advancedschedule']['pluginmenu'] = __( 'Schedule', 'csv2post' );
        $menu_array['advancedschedule']['name'] = "advancedschedule";
        $menu_array['advancedschedule']['title'] = __( 'Schedule', 'csv2post' ); 
        $menu_array['advancedschedule']['parent'] = 'advanceddata';
        $menu_array['advancedschedule']['tabmenu'] = true;
   
       ######################################################
        #                                                    #
        #                INFORMATION SECTION                 #
        #                                                    #
        ###################################################### 
        
        // checklist  
        $menu_array['infochecklist']['groupname'] = 'infosection';
        $menu_array['infochecklist']['slug'] = 'csv2post_infochecklist'; 
        $menu_array['infochecklist']['menu'] = __( 'Stats and Info', 'csv2post' );
        $menu_array['infochecklist']['pluginmenu'] = __( 'Checklist', 'csv2post' );
        $menu_array['infochecklist']['name'] = "infochecklist";
        $menu_array['infochecklist']['title'] = __( 'Checklist', 'csv2post' ); 
        $menu_array['infochecklist']['parent'] = 'parent'; 
        $menu_array['infochecklist']['tabmenu'] = true;
         
        // csvfiles
        $menu_array['infocsvfiles']['groupname'] = 'infosection';
        $menu_array['infocsvfiles']['slug'] = 'csv2post_infocsvfiles'; 
        $menu_array['infocsvfiles']['menu'] = __( 'Data Sources', 'csv2post' );
        $menu_array['infocsvfiles']['pluginmenu'] = __( 'CSV Files', 'csv2post' );
        $menu_array['infocsvfiles']['name'] = "infocsvfiles";
        $menu_array['infocsvfiles']['title'] = __( 'CSV Files', 'csv2post' ); 
        $menu_array['infocsvfiles']['parent'] = 'infochecklist'; 
        $menu_array['infocsvfiles']['tabmenu'] = true;
            
        // directorysources
        $menu_array['infodirectorysources']['groupname'] = 'infosection';
        $menu_array['infodirectorysources']['slug'] = 'csv2post_infodirectorysources'; 
        $menu_array['infodirectorysources']['menu'] = __( 'Directory Sources', 'csv2post' );
        $menu_array['infodirectorysources']['pluginmenu'] = __( 'Directory Sources', 'csv2post' );
        $menu_array['infodirectorysources']['name'] = "infodirectorysources";
        $menu_array['infodirectorysources']['title'] = __( 'Directory Sources', 'csv2post' ); 
        $menu_array['infodirectorysources']['parent'] = 'infochecklist'; 
        $menu_array['infodirectorysources']['tabmenu'] = true;                
        
        // datahistory
        $menu_array['infodatahistory']['groupname'] = 'infosection';
        $menu_array['infodatahistory']['slug'] = 'csv2post_infodatahistory'; 
        $menu_array['infodatahistory']['menu'] = __( 'Data History', 'csv2post' );
        $menu_array['infodatahistory']['pluginmenu'] = __( 'Data History', 'csv2post' );
        $menu_array['infodatahistory']['name'] = "infodatahistory";
        $menu_array['infodatahistory']['title'] = __( 'Data History', 'csv2post' ); 
        $menu_array['infodatahistory']['parent'] = 'infochecklist'; 
        $menu_array['infodatahistory']['tabmenu'] = true;
        
        // infosources  
        $menu_array['infosources']['groupname'] = 'infosection';
        $menu_array['infosources']['slug'] = 'csv2post_infosources'; 
        $menu_array['infosources']['menu'] = __( 'Projects Data Sources', 'csv2post' );
        $menu_array['infosources']['pluginmenu'] = __( 'Projects Data Sources', 'csv2post' );
        $menu_array['infosources']['name'] = "infosources";
        $menu_array['infosources']['title'] = __( 'Projects Data Sources', 'csv2post' ); 
        $menu_array['infosources']['parent'] = 'infochecklist';  
        $menu_array['infosources']['tabmenu'] = true;      
        
        // projectsdata  
        $menu_array['infoprojectsdata']['groupname'] = 'infosection';
        $menu_array['infoprojectsdata']['slug'] = 'csv2post_infoprojectsdata'; 
        $menu_array['infoprojectsdata']['menu'] = __( 'Data Table', 'csv2post' );
        $menu_array['infoprojectsdata']['pluginmenu'] = __( 'Data Table', 'csv2post' );
        $menu_array['infoprojectsdata']['name'] = "infoprojectsdata";
        $menu_array['infoprojectsdata']['title'] = __( 'Data Table', 'csv2post' ); 
        $menu_array['infoprojectsdata']['parent'] = 'infochecklist';
        $menu_array['infoprojectsdata']['tabmenu'] = true;

        // projectstable  
        $menu_array['infoprojectstable']['groupname'] = 'infosection';
        $menu_array['infoprojectstable']['slug'] = 'csv2post_infoprojectstable'; 
        $menu_array['infoprojectstable']['menu'] = __( 'Projects Table', 'csv2post' );
        $menu_array['infoprojectstable']['pluginmenu'] = __( 'Projects Table', 'csv2post' );
        $menu_array['infoprojectstable']['name'] = "infoprojectstable";
        $menu_array['infoprojectstable']['title'] = __( 'Projects Table', 'csv2post' ); 
        $menu_array['infoprojectstable']['parent'] = 'infochecklist'; 
        $menu_array['infoprojectstable']['tabmenu'] = true;
                
        // recent changes for all projects
        $menu_array['infoallprojectshistory']['groupname'] = 'infosection';
        $menu_array['infoallprojectshistory']['slug'] = 'csv2post_infoallprojectshistory'; 
        $menu_array['infoallprojectshistory']['menu'] = __( 'Full History', 'csv2post' );
        $menu_array['infoallprojectshistory']['pluginmenu'] = __( 'Full History', 'csv2post' );
        $menu_array['infoallprojectshistory']['name'] = "infoallprojectshistory";
        $menu_array['infoallprojectshistory']['title'] = __( 'Full Projects History', 'csv2post' ); 
        $menu_array['infoallprojectshistory']['parent'] = 'infochecklist'; 
        $menu_array['infoallprojectshistory']['tabmenu'] = true;
        
        // lastpost
        $menu_array['infolastpost']['groupname'] = 'infosection';
        $menu_array['infolastpost']['slug'] = 'csv2post_infolastpost';// home page slug set in main file
        $menu_array['infolastpost']['menu'] = __( 'Last Post', 'csv2post' );// main menu title
        $menu_array['infolastpost']['pluginmenu'] = __( 'Last Post', 'csv2post' );// main menu title
        $menu_array['infolastpost']['name'] = "infolastpost";// name of page (slug) and unique
        $menu_array['infolastpost']['title'] = __( 'Last Post Created By Current Project', 'csv2post' );// page title seen once page is opened 
        $menu_array['infolastpost']['parent'] = 'columns';// either "parent" or the name of the parent - used for building tab menu   
        $menu_array['infolastpost']['tabmenu'] = true;
                                                                 
        return $menu_array;
    }
} 
?>
