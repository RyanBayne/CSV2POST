<?php  
/** 
 * Functions related to handling .csv files data.
 * 
 * Import, update and querying the temporary tables data is imported into.
 * 
 * Class created September 2015.
 * 
 * @package CSV 2 POST
 * @author Ryan Bayne   
 * @version 1.0.
 */

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );
                                               
class CSV2POST_Data {
    public function __construct() {
        $this->DB = new CSV2POST_DB();
        $this->CONFIG = new CSV2POST_Configuration();
        $this->PHP = $this->CONFIG->load_class( 'CSV2POST_PHP', 'class-phplibary.php', 'classes' ); 
        $this->UI = $this->CONFIG->load_class( 'CSV2POST_UI', 'class-ui.php', 'classes' ); 
    }
    
    /**
    * Import data from a single csv file.
    * 
    * @param mixed $table_name
    * @param mixed $source_id
    * @param mixed $project_id
    * 
    * @version 2.0
    */         
    public function import_from_csv_file( $source_id, $project_id, $event_type = 'import', $inserted_limit = 9999999 ){
        global $wpdb;
        // get source
        $DB = new CSV2POST_DB;
        $source_row = $DB->get_source( $source_id );
        
        // if $event_type == update then we reset progress
        // TODO must note files time stamp and prevent progress being reset each time this is fun.
        if( $event_type == 'update' ){
            $source_row->progress = 0;
        } 
        
        // get rules array - if multiple source project + sources were joined then we must use 
        // the main source rules array, no matter how many files are added to multi-file project, 
        // we store rules in the main source unless individual tables are in use
        $rules_array = array();
        
        $datatreatment = $this->DB->get_data_treatment( $project_id );
        
        if( $datatreatment == 'join' || $datatreatment == 'append' ){   
            $rules_array = $this->get_parent_rulesarray( $project_id );// one rules array applied to many sources
        }else{
            $rules_array = maybe_unserialize( $source_row->rules );// rule array per source (individual tables, also the default for single table users which is most common)    
        }
        
        // ensure file exists
        if( !file_exists( $source_row->path ) ) {
            $this->UI->create_notice( "Please ensure the .csv file at the 
            following path still exists as CSV 2 POST failed to find it. If you 
            see an invalid path, please contact WebTechGlobal so we can 
            correct it: " . $source_row->path, 
            'success', 'Small', __( '.csv File Not Located' ) );        
            return;    
        }
                             
        //$source_object = $this->DB->selectrow( $table_name, ' )
        $file = new SplFileObject( $source_row->path );
            
        // put headers into array, we will use the key while processing each row, by doing it this way 
        // it should be possible for users to change their database and not interfere this procedure
        $original_headers_array = array();
        
        $rows_looped = 0;
        $inserted = 0;
        $updated = 0;
        $voidbaddata = 0;
        $voidduplicate = 0;
        
        // if rows already exist, change to false
        // if it stays true and any rows are updated, it means a duplicate ID value exists in the .csv file
        $firsttimeimport = true; 
        $rows_exist = $this->DB->count_rows( $source_row->tablename );
        if( $rows_exist ){$firsttimeimport = false;}
        
        while ( !$file->eof() ) 
        {      
            $insertready_array = array();
            $currentcsv_row = $file->fgetcsv( $source_row->thesep, '"' );
    
            // set an array of headers for keeps, we need it to build insert query and rules checking
            if( $rows_looped == 0)
            {
                $original_headers_array = $currentcsv_row;
                // create array of mysql ready headers
                $cleaned_headers_array = array();
                foreach( $original_headers_array as $key => $origheader ){
                    $cleaned_headers_array[] = $this->PHP->clean_sqlcolumnname( $origheader );    
                }                
                ++$rows_looped;
                continue;
            }
            
            // skip rows until $rows_looped == progress
            if( $rows_looped < $source_row->progress )
            {
                continue;
            }
                       
            // build insert part of query - loop through values, build a new array with columns as the key
            foreach( $currentcsv_row as $key => $value )
            {
                $insertready_array[$cleaned_headers_array[$key]] = $value;
            }
                         
            // does the row id value already exist in table, if it does we do not perform insert
            $exists = false;
            if( isset( $source_row->idcolumn ) && !empty( $source_row->idcolumn ) )
            {
                $exists = $this->DB->selectrow( 
                				$source_row->tablename, 
                				$source_row->idcolumn . ' = ' . esc_sql( $insertready_array[$source_row->idcolumn] ), 
                				'c2p_rowid' 
                				);
            }
            
            if( $exists)
            {   
                $row_id = $exists->c2p_rowid;       
                ++$updated;  
            }
            else
            {
            	// TODO 2 -o Owner -c Database: Discover formats of all values and add the third parameter to insert() rather than defaulting to string
				$wpdb->insert( 
					$source_row->tablename, 
					$insertready_array
				);

				$row_id = $wpdb->insert_id;

                ++$inserted;     
            }
         
            // apply rules
            if(!empty( $rules_array ) )
            {      
                $currentcsv_row = $this->apply_rules( $insertready_array, $rules_array, $row_id );
            }
            else
            {                          
                $currentcsv_row = $insertready_array;
            }

            // update row
            $this->DB->update( $source_row->tablename, "c2p_rowid = $row_id", $currentcsv_row);

            ++$rows_looped;   
            
            if( $inserted >= $inserted_limit){
                break;
            }                   
        }      
        
        // update the source with progress
        $total_progress = $source_row->progress + $inserted + $updated;// update request resets progress and so updates still count towards progress 
     
        $this->DB->update( $wpdb->c2psources, "sourceid = $source_id", array( 'progress' => $total_progress) );
        
        $details_for_notice = "
        <ul>
            <li>Source ID: $source_id</li>
            <li>Project ID: $project_id</li>
            <li>Rows Processed: $rows_looped</li>
            <li>Inserted Rows: $inserted</li>
            <li>Updated Rows: $updated</li>
            <li>Previous Progress: $source_row->progress</li>
            <li>New Progress Count: $total_progress</li>
            <li>Database Table: $source_row->tablename</li>
        </ol>";
        
        if( $source_row->progress == $total_progress ){
            
            $this->UI->create_notice( __( "All rows have already been imported 
            according to the progress counter. If you wish to re-import due to 
            your file being updated please click Update Data. $details_for_notice"), 
            'success', 'Small', __( 'Source Fully Imported' ) );  
                      
        }else{    
            if( $event_type == 'import' ){    
                
                $this->UI->create_notice( "A total of $rows_looped .csv file 
                rows were processed (including header). 
                $inserted rows were inserted to $source_row->tablename and 
                $updated were updated. This event 
                may not import every row depending on your settings. Click 
                import again to ensure all rows are 
                imported. $details_for_notice", 'success', 'Small', __( 'Data Import Event Finished' ) );
            
            }elseif( $event_type == 'update' ){
                
                $this->UI->create_notice( "A total of <strong>$rows_looped</strong> 
                .csv file rows were processed (including header). 
                <strong>$inserted</strong> rows were inserted
                to <strong>$source_row->tablename</strong> and <strong>$updated</strong> 
                were updated. This event processes the entire source, all rows should now be
                in your projects database table. $details_for_notice", 
                'success', 'Small', __( 'Data Update Event Finished' ) );            
            }
        }
        
        if( $firsttimeimport && $updated !== 0){
            // if this was a first time update yet a row or more was updated we tell the user they have duplicate ID values
            $this->UI->create_notice( __( "The plugin has detected $updated with duplicate ID values. This may be duplicate rows
            or rows that share the same ID. This needs to be corrected before continuing. Either start over or run an update
            on the data already imported to correct the problem."), 'warning', 'Small',
            'Duplicate Rows/ID In .CSV File' );
        }
    }
        
}
?>
