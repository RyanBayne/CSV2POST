<?php
/** 
* WordPress database interaction covering common queries
* 
* @package CSV 2 POST
* @author Ryan Bayne   
* @since 8.0.0
*/

// load in WordPress only
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
* Class of functions which use $wpdb to execute both common and complex queries. 
* 
* @author Ryan R. Bayne
* @package CSV 2 POST
* @since 8.0.0
* @version 1.0.4 
*/
class CSV2POST_DB { 
	use CSV2POST_DBTrait;
}

trait CSV2POST_DBTrait { 
    
    /**
    * select a single row from a single table
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function selectrow( $tablename, $condition, $select = '*' ){
        global $wpdb;
        if(empty( $condition) ){
            return null;
        }
        return $wpdb->get_row( "SELECT $select FROM $tablename WHERE $condition", OBJECT );
    }
    
    /**
    * SELECT (optional WHERE) query returning OBJECT
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.0.1
    * 
    * @param mixed $tablename
    * @param mixed $condition
    * @param mixed $orderby
    * @param mixed $select
    * @param mixed $limit
    */
    public function selectorderby( $tablename, $condition=null, $orderby=null, $select = '*', $limit = '', $object = 'OBJECT' ){
        global $wpdb;
        $condition = empty ( $condition)? '' : 'WHERE ' . $condition;
        $condition .= empty( $orderby )? '': ' ORDER BY ' . $orderby;
        if(!empty( $limit) ){$limit = 'LIMIT ' . $limit;}
        $query = "SELECT $select FROM $tablename $condition $limit";
        return $wpdb->get_results( $query, $object );
    }
    
    /**
    * SELECT (optional WHERE) query returning any passed object
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.0.1 
    * 
    * @param mixed $tablename
    * @param mixed $condition
    * @param mixed $orderby
    * @param mixed $select
    */
    public function selectwherearray( $tablename, $condition=null, $orderby=null, $select = '*', $object = 'ARRAY_A', $sort = null ){
        global $wpdb;
        $condition = empty ( $condition)? '' : ' WHERE ' . $condition;
        $condition .= empty( $orderby )? '': ' ORDER BY ' . $orderby;
        if( $sort == 'ASC' || $sort == 'DESC' ){ $condition .= ' ' . $sort; }
        return $wpdb->get_results( "SELECT $select FROM $tablename $condition", $object);
    } 
    
    /**
    * insert a new row to any table

    * @version 2.0 
    * 
    * @param string $tablename
    * @param array $fields
    */
    public function insert( $tablename, $fields ){
        global $wpdb;
        $fieldss = '';
        $values = '';
        $first = true;
        
        foreach( $fields as $field => $value )
        {
             if( $first )
             {
                $first = false;
			 }
             else
             {
                $fieldss .= ',';
                $values .= ',';
             }
             
             $fieldss .= "`$field`";
             $values .= "'" . esc_sql( $value ) ."'";
        }

        $wpdb->query(
        	"INSERT INTO $tablename ( $fieldss ) 
             VALUES ( $values )"  
        );  
        
        return $wpdb->insert_id;
    }
    
    /**
    * Standard update query
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.5
    */
    public function update( $tablename, $condition, $fields ){
        global $wpdb;
        $query = " UPDATE $tablename SET ";
        $first = true;
        foreach( $fields as $field => $value )
        {
            if( $first) $first = false; else $query .= ' , ';
            $query .= " `$field` = '" . $value ."' ";
        }

        $query .= empty( $condition)? '': " WHERE $condition ";
        return $wpdb->query( $query );
    }   
    
    /**
    * Basic delete query
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1
    */
    public function delete( $tablename, $condition ){
        global $wpdb;
        return $wpdb->query( "DELETE FROM $tablename WHERE $condition ");
    }
    
    /**
    * count the number of rows in giving table with optional arguments if giving
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1
    */
    public function count_rows( $tablename, $where = '' ){
        global $wpdb;      
        return $wpdb->get_var( "SELECT COUNT(*) FROM $tablename" . $where );
    }  
      
    /**
    * get a single value from a single row
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1
    */
    public static function get_value( $columns, $tablename, $conditions ){
        global $wpdb;
        return $wpdb->get_var( "SELECT $columns FROM $tablename WHERE $conditions" );
    }  
    
    /**
    * Gets posts with the giving meta value
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    * 
    * @param mixed $meta_key
    * @param mixed $meta_value
    * @param mixed $limit
    * @param mixed $select add table reference wpostmeta if adding meta table columns to select
    * @param mixed $where begin string with AND
    * @param mixed $querytype
    */
    public function get_posts_join_meta( $meta_key, $meta_value, $limit = 1, $select = '*', $where = '', $querytype = 'get_results' ){
        global $wpdb;
        
        $q = "SELECT wposts.".$select."
        FROM ".$wpdb->posts." AS wposts
        INNER JOIN ".$wpdb->postmeta." AS wpostmeta
        ON wpostmeta.post_id = wposts.ID
        AND wpostmeta.meta_key = '".$meta_key."'                                                 
        AND wpostmeta.meta_value = '".$meta_value."' 
        ".$where."
        LIMIT ".$limit."";
     
        if( $querytype == 'query' ){
            $result = $wpdb->query( $q);    
        }elseif( $querytype == 'get_var' ){
            $result = $wpdb->get_var( $q);        
        }else{
            $result = $wpdb->get_results( $q, OBJECT);    
        }
        
        return $result;
    }
    
    /**
    * Function for validating values
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    * 
    * @access private
    */
    function _sql_validate_value( $var){
        if (is_null( $var) )
        {
            return 'NULL';
        }
        else if (is_string( $var) )
        {
            return "'" . $this->sql_escape( $var) . "'";
        }
        else
        {
            return (is_bool( $var) ) ? intval( $var) : $var;
        }
    }  
      
    /**
    * Build sql statement from array for insert/update/select statements
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    * 
    * Idea for this from Ikonboard
    * Possible query values: INSERT, INSERT_SELECT, UPDATE, SELECT
    */
    public function sql_build_array( $query, $assoc_ary = false ){
        if (!is_array( $assoc_ary ) ){
            return false;
        }

        $fields = $values = array();

        if ( $query == 'INSERT' || $query == 'INSERT_SELECT' )
        {
            foreach ( $assoc_ary as $key => $var)
            {
                $fields[] = $key;

                if (is_array( $var) && is_string( $var[0] ) )
                {
                    // This is used for INSERT_SELECT(s)
                    $values[] = $var[0];
                }
                else
                {
                    $values[] = $this->_sql_validate_value( $var);
                }
            }

            $query = ( $query == 'INSERT' ) ? ' ( ' . implode( ', ', $fields) . ' ) VALUES ( ' . implode( ', ', $values) . ' )' : ' ( ' . implode( ', ', $fields) . ' ) SELECT ' . implode( ', ', $values) . ' ';
        }
        else if ( $query == 'UPDATE' || $query == 'SELECT' )
        {
            $values = array();
            foreach ( $assoc_ary as $key => $var)
            {
                $values[] = "$key = " . $this->_sql_validate_value( $var);
            }
            $query = implode(( $query == 'UPDATE' ) ? ', ' : ' AND ', $values);
        }

        return $query;
    }    
    
    /**
    * Uses get_results and finds all DISTINCT meta_keys, returns the result.
    * Currently does not have any measure to ensure keys are custom field only.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    */
    public function customfield_keys_distinct() {
        global $wpdb;
        return $wpdb->get_results( "SELECT DISTINCT meta_key FROM $wpdb->postmeta 
                                      WHERE meta_key != '_encloseme' 
                                      AND meta_key != '_wp_page_template'
                                      AND meta_key != '_edit_last'
                                      AND meta_key != '_edit_lock'
                                      AND meta_key != '_wp_trash_meta_time'
                                      AND meta_key != '_wp_trash_meta_status'
                                      AND meta_key != '_wp_old_slug'
                                      AND meta_key != '_pingme'
                                      AND meta_key != '_thumbnail_id'
                                      AND meta_key != '_wp_attachment_image_alt'
                                      AND meta_key != '_wp_attachment_metadata'
                                      AND meta_key != '_wp_attached_file'");    
    }
    
    /**
    * Uses get_results and finds all DISTINCT meta_keys, returns the result  
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    */
    public function metakeys_distinct() {
        global $wpdb;
        return $wpdb->get_results( "SELECT DISTINCT meta_key FROM $wpdb->postmeta 
                                      WHERE meta_key != '_encloseme' 
                                      AND meta_key != '_wp_page_template'
                                      AND meta_key != '_edit_last'
                                      AND meta_key != '_edit_lock'
                                      AND meta_key != '_wp_trash_meta_time'
                                      AND meta_key != '_wp_trash_meta_status'
                                      AND meta_key != '_wp_old_slug'
                                      AND meta_key != '_pingme'
                                      AND meta_key != '_thumbnail_id'
                                      AND meta_key != '_wp_attachment_image_alt'
                                      AND meta_key != '_wp_attachment_metadata'
                                      AND meta_key != '_wp_attached_file'");    
    }
    
    /**
    * counts total records in giving project table
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1
    *  
    * @return 0 on fail or no records or the number of records in table
    */
    public function countrecords( $table_name, $where = '' ){
        global $wpdb;
        $records = $wpdb->get_var( 
            "
                SELECT COUNT(*) 
                FROM ". $table_name . "
                ".$where." 
            "
        );
        
        if( $records ){
            return $records;
        }else{
            return '0';
        }    
    }
    
    /**
    * Returns SQL query result of all option records in WordPress options table that begin with the giving 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    */
    public function options_beginning_with( $prependvalue){    
        global $wpdb;
        $optionrecord_array = array();
        
        // first get all records
        $optionrecords = $wpdb->get_results( "SELECT option_name FROM $wpdb->options" );
        
        // loop through each option record and check their name value for csv2post_ at the beginning
        foreach( $optionrecords as $optkey => $option ){
            if(strpos( $option->option_name , $prependvalue ) === 0){
                $optionrecord_array[] = $option->option_name;
            }
        } 
        
        return $optionrecord_array;   
    }
    
    /**
    * Query posts by ID 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1
    */
    public function post_exist_byid( $id){
        global $wpdb;
        return $wpdb->get_row( "SELECT post_title FROM $wpdb->posts WHERE id = '" . $id . "'", 'ARRAY_A' );    
    }
    
    /**
     * Checks if a database table name exists or not
     * 1. One issue with this function is that WordPress treats the lack of tables existence as an error
     * 2. Another approach is using csv2post_WP_SQL_get_tables() and checking the array for the table, this is error free
     * 
     * @author Ryan R. Bayne
     * @package CSV 2 POST
     * @since 7.0.0
     * @version 1.1
     * 
     * @global array $wpdb
     * @param string $table_name
     * @return boolean, true if table found, else if table does not exist
     */
    public function does_table_exist( $table_name ){
        global $wpdb;                                      
        if( $wpdb->query( "SHOW TABLES LIKE '".$table_name."'") ){return true;}else{return false;}
    }
    
    /**
     * Checks if a database table exist
     * 
     * @author Ryan R. Bayne
     * @package CSV 2 POST
     * @since 7.0.0
     * @version 1.1 
     * 
     * @param string $table_name (possible database table name)
     */
    public function database_table_exist( $table_name ){
        global $wpdb;
        if( $wpdb->get_var( "SHOW TABLES LIKE '".$table_name."'") != $table_name) {     
            return false;
        }else{
            return true;
        }
    }
    
    /**
    * Returns array of tables from the WordPress database.
    * 
    * @version 1.1 
    * 
    * @returns array $tables_array
    */
    public function get_tables() {
        global $wpdb;
        $result = mysql_query( "SHOW TABLES FROM `".$wpdb->dbname."`");
        if(!$result){return false;}
        $tables_array = array();
        while ( $row = mysql_fetch_row( $result) ) {
            $tables_array[] = $row[0];
        }        
        return $tables_array;
    }
    
    /**
    * Returns an array holding the column names for the giving table
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    * 
    * @param mixed $return_array [false] = mysql result [true] = array of the result
    * @param mixed $columns_only true will not return column information
    * @return array or mysql result or false on failure
    * 
    * @todo get_col_info() may not be the correct function to use here as it is to be used on a recent query only
    * @todo this method could be reduced by using the foreach loop once and everything within it 
    */
    public function get_tablecolumns( $table_name, $return_array = false, $columns_only = false ){
        global $wpdb;
                    
        // an array is required - what data is required in the array...    
        if( $return_array == true && $columns_only == false ){// return an array holding ALL info
            $columns_array = array();              
            foreach ( $wpdb->get_col_info( "DESC " . $table_name, 0 ) as $column_details ) {
                $columns_array[] = $column_details;
            }
            
            return $columns_array;
                            
        }elseif( $return_array == true && $columns_only == true){# return an array of column names only
            $columns_array = array();
            foreach ( $wpdb->get_col( "DESC " . $table_name, 0 ) as $column_name ) {
                $columns_array[] = $column_name;
            }
            
            return $columns_array;  
        }elseif( $return_array == false ){
            $columns_string = '';
            foreach ( $wpdb->get_col( "DESC " . $table_name, 0 ) as $column_name ) {
                $columns_string .= $column_name . ', ';
            }    
            $columns_string = rtrim( $columns_string, ", ");
            return $columns_string;        
        }   
    }
    
    /**
    * Drops the giving database table and displays result in notice 
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    * 
    * @param mixed $table_name
    * @returns boolean
    */
    public function drop_table( $table_name ){
        global $wpdb;
        $r = $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
        if( $r ){                  
            return true;
        }else{
            return false;
        }    
    }
    
    /**
    * Mass change one key name to another
    *
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.1 
    *  
    * @param mixed $old_key
    * @param mixed $new_key
    */
    public function update_meta_key( $old_key = NULL, $new_key = NULL ){
        global $wpdb;
        $results = $wpdb->get_results( 
            "
                UPDATE ".$wpdb->prefix."postmeta 
                SET meta_key = '".$new_key."' 
                WHERE meta_key = '".$old_key."'
            "
        , ARRAY_A );
        return $results;
    }

    /**
    * Queries distinct values in a giving column
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 7.0.0
    * @version 1.0.1
    * 
    * @returns array of distinct values or 0 if no records or false if none 
    */
    public function column_distinctvalues( $table_name, $column_name){
        global $wpdb;
        $distinct_values_found = $wpdb->get_results( "SELECT DISTINCT " . $column_name . " FROM ". $table_name, ARRAY_A );
                
        if( !$distinct_values_found ){
            return false;
        }else{
            return $distinct_values_found;        
        }  
        
        return false;                      
    }
    
    /**
    * Returns rows where the same values appears twice or more
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.3
    * @version 1.1
    */
    public function get_duplicate_keys( $table_name, $column ) {
         $rows_with_duplicates = array();
         
        // get all distinct values for looping through
        $distinct_array = self::column_distinctvalues( $table_name, $column );
        if( $distinct_array ){
            foreach( $distinct_array as $key => $distinct ){
                             
                // count how many rows have this $distinct value in this $column
                $count = self::count_rows( $table_name, ' WHERE ' . $column . ' = ' . $distinct[ $column ] );
                
                // if $count greater than 1 we have duplicates, add to $rows_with_duplicates
                if( $count > 1 ){
                    $rows_with_duplicates[] = $distinct[ $column ];
                }
            }
        }
        
        return $rows_with_duplicates;
    }
    
    /**
    * get posts based on comment count.
    * 1. if using range and wish to include posts with a single comment then pass 0 as the minimum due to sql argument using > only
    * 
    * @param mixed $comment_count_low
    * @param mixed $comment_count_high
    * @param mixed $post_type
    * @param mixed $post_status
    * @param mixed $output
    */
    public function query_posts_by_comments( $comment_count_low = 0, $comment_count_high = 9999, $post_type = 'post', $post_status = 'publish', $output = 'OBJECT' ){
        global $wpdb;
        $query = "
            SELECT *
            FROM {$wpdb->prefix}posts
            WHERE {$wpdb->prefix}posts.post_type = '{$post_type}'
            AND {$wpdb->prefix}posts.post_status = '{$post_status}'
            ";
        
        // if low and high are not zero we add a range to the query
        if( $comment_count_low == 0 && $comment_count_high == 0)
        {
            $query .= "AND {$wpdb->prefix}posts.comment_count = 0";    
        }    
        else
        {
            $query .= "AND {$wpdb->prefix}posts.comment_count > {$comment_count_low}
            AND {$wpdb->prefix}posts.comment_count <= {$comment_count_high}";
        }
        
        $query .= "
        ORDER BY {$wpdb->prefix}posts.post_date
        DESC;
        ";

        return $wpdb->get_results( $query, $output);        
    }
    
    /**
    * query multiple database tables, assumed to have a data set and shared key column which is required for JOIN to work
    * 
    * @param mixed $tables_array
    * @param mixed $idcolumn
    * @param mixed $where
    */
    public function query_multipletables( $tables_array = array(), $idcolumn = false, $where = false, $total = false ){
        global $wpdb;
                                  
        if(!is_array( $tables_array ) || !isset( $tables_array[0] ) ){
            return false;
        }
 
        // set the main table (always the first)
        $main_table = $tables_array[0];
        
        // build select
        $select = '';
        foreach( $tables_array as $key => $table_name ){
            
            // add comma for the next table being added
            if( $key > 0 ){
                $select .= ', ';
            }
            
            // we join the current table to the main table based on giving ID column
            $select .= "$table_name.*";          
        }        

        // build JOIN
        $join = '';
        foreach( $tables_array as $key => $table_name ){
            
            // avoid adding main table to the JOIN
            if( $key == 0){continue;}
            
            // only join tables if we have an id column
            if( $idcolumn !== false && is_string( $idcolumn ) ){
                // we join the current table to the main table based on giving ID column
                $join .= "
                JOIN $table_name ON $main_table.$idcolumn = $table_name.$idcolumn";    
            }    
        }

        // build limit
        $limit = '';
        if(is_numeric( $total ) ){$limit = "LIMIT $total";}
        
        // build where
        $wherepart = '';
        if( $where !== false && is_string( $where ) ){$wherepart = "WHERE $where";}
        
        $final_query = "SELECT $select FROM $main_table $wherepart $join $limit";
                      
        // build where
        return $wpdb->get_results( $final_query, ARRAY_A );
    }
    
    /**
    * Get the maximum value in column
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @since 8.1.34
    * @version 1.1
    */
    public function max_value( $column, $tablename ) {
        global $wpdb;        
        return $wpdb->get_var( "SELECT $column FROM $tablename ORDER BY $column DESC LIMIT 1" );        
    }

    /**
    * Find data sources with new .csv files that are not 100% imported
    * and have not changed previously i.e. entering update phase.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function find_waiting_files_new() {
        global $wpdb;
        return self::selectwherearray( 
            $wpdb->c2psources, 'progress < rows AND changecounter = 0', 'sourceid', '*' 
        );        
    } 

    /**
    * Find data sources with old .csv files that are not 100% imported
    * and have changed previously i.e. they are in the update phase.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function find_waiting_files_old() {
        global $wpdb;
        return self::selectwherearray( 
            $wpdb->c2psources, 'progress < rows AND changecounter > 0', 'sourceid', '*' 
        );        
    } 
    
    /**
    * gets a single row from c2psources table, returns query result
    * 
    * @uses $this->DB->selectrow()
    * 
    * @param mixed $project_id
    * @param mixed $source_id
    */
    public function get_source( $source_id ){
        global $wpdb;
        return self::selectrow( $wpdb->c2psources, "sourceid = $source_id", '*' );
    }

    public function get_data_treatment( $project_id ){
        global $wpdb;
        return self::get_value(
            'datatreatment', 
            $wpdb->c2psources, 
            "projectid = $project_id AND parentfileid = 0" 
        );    
    }
    
    /**
    * Gets all sources that have unused rows
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function get_all_sources() {
        global $wpdb;
        return $this->DB->selectwherearray( $wpdb->c2psources, 'sourceid = sourceid', 'sourceid', '*' );        
    }

    /**
    * Get all active projects.
    * 
    * Added September 2015.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function get_active_projects() {
        global $wpdb;
        return self::selectwherearray( $wpdb->c2pprojects, 'status = 1', 'projectid', '*' );                
    }
       
    /**
    * Get an active source with unused rows.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    * 
    * @todo 2 Task: replace "25" (total) with users limit setting 
    */
    public function get_sources_with_unused_rows( $total_sources ) {
        // create array
        $foundsources = array();
        
        // get active projects
        $activeprojects = self::get_active_projects();
        
        // check each projects source for unusued rows
        foreach( $activeprojects as $key => $project ) {
            if( !isset( $project['projectid']) ) { continue; }
  
            // query source for unused rows
            $rows = self::get_unused_rows( $project['projectid'], 1, $idcolumn = false );
            
            if( $rows ){
                $foundsources[] = array( 
                    'sourceid' => $project['source1'],
                    'projectid' => $project['projectid']
                );
                
                // avoid further iteration if only one source required
                if( $total_sources == 1 ) {
                    return $foundsources;
                }
            }
        }
        
        return $foundsources;
    }

    /**
    * query project rows which have changed but not yet been applied to their post
    * 
    * @param mixed $project_id
    * @param mixed $total
    * 
    * @todo 1 Hyperactive post updating is happening. I found it when using the
    * automatic post updating test form. A row is always returned despite no
    * update and I think it is due to two time formats in the applied and updated
    * columns (an hour difference). Found 24th Sep 2015.
    */
    public function get_updated_rows( $project_id, $total = 1, $idcolumn = false ){
        $tables_array = self::get_dbtable_sources( $project_id);
        return self::query_multipletables( $tables_array, $idcolumn, 'c2p_postid != 0 AND c2p_updated > c2p_applied', $total);
    }
    
    /**
    * Get sources that have one or more rows that have been updated
    * since their original import.
    * 
    * Only checks sources for active projects.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.0
    */
    public function get_sources_with_updated_rows( $total_sources ) {
        // create array
        $foundsources = array();

        // get active projects to avoid working with sources not in use
        $activeprojects = self::get_active_projects();
        
        // check each projects source for unusued rows
        foreach( $activeprojects as $key => $project ) {
            if( !isset( $project['projectid'] ) ) { continue; }
  
            $rows = self::get_updated_rows( $project['projectid'], 1, false );
     
            if( $rows ){
                $foundsources[] = array( 
                    'sourceid' => $project['source1'],
                    'projectid' => $project['projectid']
                );
                
                // avoid further iteration if only one source required
                if( $total_sources == 1 ) {
                    return $foundsources;
                }
            }
        }
        
        return $foundsources;                
    }
    
    /**
    * By default gets entire project row, specify 
    * specific field to return a single value.
    * 
    * @author Ryan R. Bayne
    * @package CSV 2 POST
    * @version 1.3
    * 
    * @param mixed $project_id
    * @param mixed $field
    */
    public function get_project( $project_id, $field = false ){
        global $wpdb; 
        $result = self::selectrow( $wpdb->c2pprojects, "projectid = $project_id", '*' );  
        if( $field === false || !is_string( $field ) ){return $result;}
        return $result->$field;  
    }
  
    /**
    * returns array of source ID for the giving project 
    * 
    * @version 1.2
    */
    public function get_project_sourcesid( $project_id ){
        global $wpdb;
        $result = self::selectwherearray( 
            $wpdb->c2pprojects, 
            'projectid = ' . $project_id, 
            'projectid', 
            'source1' 
        );
        $id_array = array();
        for( $i=0;$i<=5;$i++)
        {
            if(!empty( $result[0]["source$i"] ) && $result[0]["source$i"] !== '0' )
            {
                $id_array[] = $result[0]["source$i"];    
            }
        }        
        return $id_array;
    } 
      
    /**
    * returns an array of database table names ONLY for the projects database table type data sources 
    */
    public function get_dbtable_sources( $project_id ){
        global $wpdb;
        
        // get projects data source ID's
        $sourceid_array = self::get_project_sourcesid( $project_id );
        
        // create an array for storing table names
        $tables_added = array();

        // loop through source ID's and get tablename from source row
        foreach( $sourceid_array as $key => $source_id){

            $row = self::selectrow( $wpdb->c2psources, 'sourceid = "' . $source_id . '"', 'tablename,idcolumn' );

            // avoid using the same table twice
            if(in_array( $row->tablename, $tables_added) ){
                continue;
            }
            
            $tables_added[] = $row->tablename;       
        }    
        
        return $tables_added;
    }
    
    /**
    * gets imported rows not used to create posts, uses method to select data from multiple tables based on an ID field
    * 
    * @param mixed $project_id
    */
    public function get_unused_rows( $project_id, $total = 1, $idcolumn = false ){
        $tables_array = self::get_dbtable_sources( $project_id );       
        return self::query_multipletables( $tables_array, $idcolumn, 'c2p_postid = 0', $total );
    }

    public static function get_project_main_table( $project_id ){
        global $wpdb;                                                                                       
        return self::get_value( 'tablename', $wpdb->c2psources, "projectid = $project_id AND parentfileid = 0");    
    }
    
    /**
    * queries the projects datasources and puts all column names into an array with the table being the key
    * so that multi table projects can be used, however right now some of the plugin requires unique column names
    * 
    * @param mixed $project_id
    * @param mixed $from
    * @param boolean $apply_exclusions removes c2p columns in db method
    */
    public function get_project_columns_from_db( $project_id, $apply_exclusions = true ){
        if( !is_numeric( $project_id ) ){return false;}
                    
        global $wpdb;
                    
        $sourceid_array = array();// source id's from project table into an array for looping
        $final_columns_array = array();// array of all columns with table names as keys
        $queried_already = array();// track        
        
        // get project source id's and data treatment from the project table to apply that treatment to all sources 
        $project_row = self::selectrow( $wpdb->c2pprojects, "projectid = $project_id", 'datatreatment,source1,source2,source3,source4,source5' );
        
        // return the data treatment with columns to avoid having to query it again
        $final_columns_array['arrayinfo']['datatreatment'] = $project_row->datatreatment;
                      
        // put source id's into array because the rest of the function was already written before adding this approach
        $sourceid_array[] = $project_row->source1;
        if(!empty( $project_row->source2 ) ){$sourceid_array[] = $project_row->source2;}
        if(!empty( $project_row->source3 ) ){$sourceid_array[] = $project_row->source3;}
        if(!empty( $project_row->source4 ) ){$sourceid_array[] = $project_row->source4;}
        if(!empty( $project_row->source5 ) ){$sourceid_array[] = $project_row->source5;}
        
        // loop through source ID's
        foreach( $sourceid_array as $key => $source_id)
        {    
            // get the source row
            $row = self::selectrow( $wpdb->c2psources, 'sourceid = "' . $source_id . '"', 'path,tablename,thesep' );
            
            // user might have deleted source and $row is null - project contains sourceid for reference
            if( $row )
            {
                // avoid querying the same table twice to prevent a single table project being queried equal to number of sources
                if( !in_array( $row->tablename, $queried_already ) ){
                    $queried_already[] = $row->tablename;
                    $final_columns_array[ $row->tablename ] = self::get_tablecolumns( $row->tablename, true, true);
                    $final_columns_array[ 'arrayinfo' ][ 'sources' ][ $row->tablename ] = $source_id;
                }
            }
        }
              
        if( $apply_exclusions)
        {    
            // array of columns not to be used as column replacement tokens
            $excluded_array = array( 'c2p_changecounter', 'c2p_rowid', 'c2p_postid', 'c2p_use', 'c2p_updated', 'c2p_applied', 'c2p_categories', 'c2p_changecounter' );
            
            // loop through tables first
            foreach( $final_columns_array as $table_name => $columns_array ){
                // skip array of information
                if( $table_name !== 'arrayinfo' ) {        
                    // loop through the columns for $table_name
                    foreach( $columns_array as $numeric_key => $column ) {
                        if( in_array( $column, $excluded_array ) ) {
                            unset( $final_columns_array[ $table_name ][ $numeric_key ] );    
                        }
                    }    
                }
            }
        }       
            
        return $final_columns_array;
    }
                   
}// end class CSV2POST_DB
?>