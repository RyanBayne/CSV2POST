<?php
/**
 * Project Diagnostics Service
 * 
 * Provides inspectable, structured diagnostics for CSV2POST projects, sources,
 * and import contexts. Designed as a read-only modern interface for project health,
 * schema validation, and data preview.
 *
 * @package CSV2POST\Services
 */

namespace CSV2POST\Services;

/**
 * ProjectDiagnostics
 * 
 * Exposes detailed information about projects, sources, data staging,
 * and import readiness without modifying the legacy diagnostic/inspection approach.
 */
class ProjectDiagnostics {
    
    /**
     * @var \CSV2POST\Services\SourceRepository
     */
    private $source_repo;
    
    /**
     * @var \CSV2POST\Services\ImportService
     */
    private $import_service;
    
    public function __construct( SourceRepository $source_repo, ImportService $import_service ) {
        $this->source_repo = $source_repo;
        $this->import_service = $import_service;
    }
    
    /**
     * Get diagnostic summary for a project.
     * 
     * @param int $project_id
     * @return array Diagnostic report with status, sources, tables, readiness
     */
    public function project_summary( $project_id ) {
        $project = $this->source_repo->get_project( $project_id );
        if ( ! $project ) {
            return array(
                'status' => 'error',
                'message' => 'Project not found',
                'project_id' => $project_id,
            );
        }
        
        $sources = $this->source_repo->get_project_sources( $project_id );
        $project_table = $this->source_repo->get_project_main_table( $project_id );
        
        return array(
            'status' => 'ok',
            'project_id' => $project_id,
            'project_name' => $project->projectname ?? '',
            'created' => $project->timestamp ?? '',
            'source_count' => count( $sources ),
            'sources' => array_map( array( $this, 'source_brief' ), $sources ),
            'main_table' => $project_table,
            'import_readiness' => $this->assess_import_readiness( $project_id, $sources ),
        );
    }
    
    /**
     * Get brief info about a single source.
     * 
     * @param object $source
     * @return array
     */
    public function source_brief( $source ) {
        $source_id = $source->sourceid ?? $source->ID ?? null;
        if ( ! $source_id ) {
            return array( 'status' => 'error', 'message' => 'No source ID found' );
        }
        
        $data_treatment = $this->source_repo->get_data_treatment( $source_id );
        $table_exists = $this->source_repo->table_exists( $source->tablename ?? '' );
        
        return array(
            'source_id' => $source_id,
            'source_name' => $source->name ?? $source->sourcename ?? '',
            'type' => $source->sourcetype ?? 'unknown',
            'path' => $source->path ?? '',
            'table_name' => $source->tablename ?? '',
            'table_exists' => $table_exists,
            'data_treatment' => $data_treatment,
            'rows_staged' => $table_exists ? $this->count_source_rows( $source_id ) : 0,
        );
    }
    
    /**
     * Get full diagnostic report for a source including columns and sample data.
     * 
     * @param int $source_id
     * @param int $sample_limit Rows to show in preview (default 5)
     * @return array
     */
    public function source_full_diagnostic( $source_id, $sample_limit = 5 ) {
        global $wpdb;
        
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source ) {
            return array(
                'status' => 'error',
                'message' => 'Source not found',
                'source_id' => $source_id,
            );
        }
        
        $brief = $this->source_brief( $source );
        $columns = $this->source_repo->get_project_columns( $source->projectid ?? 0 );
        $table_name = $source->tablename ?? '';
        
        $rows = array();
        $row_count = 0;
        if ( $this->source_repo->table_exists( $table_name ) ) {
            $row_count = $wpdb->get_var( $wpdb->prepare( 
                "SELECT COUNT(*) FROM {$wpdb->prefix}" . $table_name,
                array()
            ) );
            
            $sample = $wpdb->get_results( 
                "SELECT * FROM {$wpdb->prefix}{$table_name} LIMIT " . absint( $sample_limit ),
                ARRAY_A
            );
            $rows = $sample ?: array();
        }
        
        return array(
            'status' => 'ok',
            'brief' => $brief,
            'columns' => $columns,
            'row_count' => (int) $row_count,
            'sample_rows' => $rows,
            'import_context' => $this->import_service->describe_import_context( $source_id, $source->projectid ?? 0 ),
        );
    }
    
    /**
     * Count rows in a source's staging table.
     * 
     * @param int $source_id
     * @return int
     */
    private function count_source_rows( $source_id ) {
        global $wpdb;
        
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source || ! $this->source_repo->table_exists( $source->tablename ?? '' ) ) {
            return 0;
        }
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}" . $source->tablename,
            array()
        ) );
        
        return (int) $count;
    }
    
    /**
     * Assess whether a project is ready to import/generate posts.
     * 
     * @param int $project_id
     * @param array $sources
     * @return array Report with readiness status and any warnings
     */
    private function assess_import_readiness( $project_id, $sources ) {
        $warnings = array();
        $ready = true;
        
        if ( empty( $sources ) ) {
            $warnings[] = 'No sources configured';
            $ready = false;
        }
        
        $has_staged_rows = false;
        foreach ( $sources as $source ) {
            $source_id = $source->sourceid ?? $source->ID ?? null;
            if ( $source_id && $this->count_source_rows( $source_id ) > 0 ) {
                $has_staged_rows = true;
                break;
            }
        }
        
        if ( ! $has_staged_rows ) {
            $warnings[] = 'No rows staged for import';
        }
        
        $project = $this->source_repo->get_project( $project_id );
        $project_table = $this->source_repo->get_project_main_table( $project_id );
        if ( ! $this->source_repo->table_exists( $project_table ) ) {
            $warnings[] = "Project table '{$project_table}' does not exist";
            $ready = false;
        }
        
        return array(
            'ready' => $ready,
            'warnings' => $warnings,
            'has_staged_data' => $has_staged_rows,
        );
    }
}
