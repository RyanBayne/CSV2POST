<?php
/**
 * Schema Builder Service
 * 
 * Provides a modern, structured interface for reading and building CSV2POST schemas
 * without directly manipulating legacy configuration classes.
 *
 * @package CSV2POST\Services
 */

namespace CSV2POST\Services;

/**
 * SchemaBuilder
 * 
 * Handles schema definition, validation, and manipulation for import projects.
 * Serves as a bridge between legacy schema storage and modern schema operations.
 */
class SchemaBuilder {
    
    /**
     * @var \CSV2POST\Services\SourceRepository
     */
    private $source_repo;
    
    public function __construct( SourceRepository $source_repo ) {
        $this->source_repo = $source_repo;
    }
    
    /**
     * Get the current schema for a source/project combination.
     * 
     * @param int $source_id
     * @param int $project_id
     * @return array Schema array with columns and mappings
     */
    public function get_schema( $source_id, $project_id ) {
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source ) {
            return array( 'status' => 'error', 'message' => 'Source not found' );
        }
        
        $columns = $this->source_repo->get_project_columns( $project_id );
        $data_treatment = $this->source_repo->get_data_treatment( $source_id );
        
        return array(
            'status' => 'ok',
            'source_id' => $source_id,
            'project_id' => $project_id,
            'columns' => $columns,
            'data_treatment' => $data_treatment,
            'source_name' => $source->name ?? '',
            'source_type' => $source->sourcetype ?? 'unknown',
        );
    }
    
    /**
     * Get column details including any mapping or transformation rules.
     * 
     * @param int $project_id
     * @return array
     */
    public function get_columns( $project_id ) {
        $columns = $this->source_repo->get_project_columns( $project_id );
        
        return array(
            'status' => 'ok',
            'project_id' => $project_id,
            'columns' => $columns ?: array(),
            'column_count' => count( $columns ?: array() ),
        );
    }
    
    /**
     * Validate that a source's CSV headers match the expected schema.
     * 
     * @param int $source_id
     * @param array $csv_headers Array of header names from CSV file
     * @return array Validation report with warnings/errors
     */
    public function validate_source_headers( $source_id, array $csv_headers ) {
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source ) {
            return array(
                'valid' => false,
                'message' => 'Source not found',
            );
        }
        
        $project_columns = $this->source_repo->get_project_columns( $source->projectid ?? 0 );
        
        $warnings = array();
        $errors = array();
        $matched = array();
        
        // Check which headers match expected columns
        foreach ( $project_columns as $col ) {
            $col_name = is_array( $col ) ? $col['name'] : $col->columnname;
            if ( in_array( $col_name, $csv_headers, true ) ) {
                $matched[] = $col_name;
            } else {
                $warnings[] = "Expected column '{$col_name}' not found in CSV";
            }
        }
        
        // Check for unexpected headers in CSV
        foreach ( $csv_headers as $header ) {
            $found = false;
            foreach ( $project_columns as $col ) {
                $col_name = is_array( $col ) ? $col['name'] : $col->columnname;
                if ( $col_name === $header ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                $warnings[] = "CSV column '{$header}' not defined in project schema";
            }
        }
        
        $valid = count( $errors ) === 0 && count( $matched ) > 0;
        
        return array(
            'valid' => $valid,
            'matched_columns' => $matched,
            'warnings' => $warnings,
            'errors' => $errors,
        );
    }
    
    /**
     * Get a summary of schema readiness for a project.
     * 
     * @param int $project_id
     * @return array
     */
    public function schema_readiness( $project_id ) {
        $project = $this->source_repo->get_project( $project_id );
        if ( ! $project ) {
            return array(
                'status' => 'error',
                'message' => 'Project not found',
            );
        }
        
        $columns = $this->source_repo->get_project_columns( $project_id );
        $sources = $this->source_repo->get_project_sources( $project_id );
        
        $issues = array();
        
        if ( empty( $columns ) ) {
            $issues[] = 'No columns defined';
        }
        
        if ( empty( $sources ) ) {
            $issues[] = 'No sources configured';
        }
        
        $data_treatment = $this->source_repo->get_data_treatment( $project_id );
        if ( ! $data_treatment ) {
            $issues[] = 'No data treatment rules defined';
        }
        
        $main_table = $this->source_repo->get_project_main_table( $project_id );
        if ( ! $this->source_repo->table_exists( $main_table ) ) {
            $issues[] = "Main table '{$main_table}' does not exist";
        }
        
        $ready = empty( $issues );
        
        return array(
            'status' => 'ok',
            'project_id' => $project_id,
            'project_name' => $project->projectname ?? '',
            'ready' => $ready,
            'column_count' => count( $columns ?: array() ),
            'source_count' => count( $sources ?: array() ),
            'issues' => $issues,
        );
    }
    
    /**
     * Compare two project schemas for compatibility.
     * 
     * @param int $project_id_1
     * @param int $project_id_2
     * @return array Comparison report
     */
    public function compare_schemas( $project_id_1, $project_id_2 ) {
        $schema1 = $this->get_schema( 0, $project_id_1 );
        $schema2 = $this->get_schema( 0, $project_id_2 );
        
        if ( 'error' === $schema1['status'] || 'error' === $schema2['status'] ) {
            return array(
                'status' => 'error',
                'message' => 'One or both projects not found',
            );
        }
        
        $cols1 = $schema1['columns'] ?: array();
        $cols2 = $schema2['columns'] ?: array();
        
        $col_names1 = array_map( function( $c ) {
            return is_array( $c ) ? $c['name'] : $c->columnname;
        }, $cols1 );
        
        $col_names2 = array_map( function( $c ) {
            return is_array( $c ) ? $c['name'] : $c->columnname;
        }, $cols2 );
        
        $matching = array_intersect( $col_names1, $col_names2 );
        $only_in_1 = array_diff( $col_names1, $col_names2 );
        $only_in_2 = array_diff( $col_names2, $col_names1 );
        
        return array(
            'status' => 'ok',
            'project_1_id' => $project_id_1,
            'project_2_id' => $project_id_2,
            'matching_columns' => $matching,
            'only_in_project_1' => $only_in_1,
            'only_in_project_2' => $only_in_2,
            'compatibility' => empty( $only_in_1 ) && empty( $only_in_2 ) ? 'identical' : 'different',
        );
    }
}
