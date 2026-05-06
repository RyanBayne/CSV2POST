<?php
/**
 * Import Progress Tracker
 * 
 * Tracks the status and progress of import operations across sources.
 * Designed to support async operations, retry logic, and progress updates.
 *
 * @package CSV2POST\Services
 */

namespace CSV2POST\Services;

/**
 * ImportProgress
 * 
 * Provides status tracking for import operations without requiring legacy class polling.
 */
class ImportProgress {
    
    /**
     * @var \CSV2POST\Services\SourceRepository
     */
    private $source_repo;
    
    /**
     * Get the current import status for a source.
     * 
     * This queries the source metadata and staged table to determine
     * the current state of an import operation.
     * 
     * @param int $source_id
     * @return array Status report
     */
    public function get_source_status( $source_id ) {
        global $wpdb;
        
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source ) {
            return array(
                'status' => 'error',
                'message' => 'Source not found',
                'source_id' => $source_id,
            );
        }
        
        $table_name = $source->tablename ?? '';
        $table_exists = $this->source_repo->table_exists( $table_name );
        
        $staged_rows = 0;
        $processed_rows = 0;
        
        if ( $table_exists ) {
            $staged_rows = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}" . $table_name,
                array()
            ) );
        }
        
        return array(
            'status' => 'ok',
            'source_id' => $source_id,
            'source_name' => $source->name ?? '',
            'source_type' => $source->sourcetype ?? '',
            'staged_rows' => (int) $staged_rows,
            'table_name' => $table_name,
            'table_exists' => $table_exists,
            'last_updated' => $source->timestamp ?? '',
            'progress_percent' => $this->calculate_progress_percent( $source_id, (int) $staged_rows ),
        );
    }
    
    /**
     * Get import status for an entire project.
     * 
     * @param int $project_id
     * @return array
     */
    public function get_project_status( $project_id ) {
        $sources = $this->source_repo->get_project_sources( $project_id );
        if ( empty( $sources ) ) {
            return array(
                'status' => 'ok',
                'project_id' => $project_id,
                'message' => 'No sources configured',
                'sources' => array(),
                'total_staged_rows' => 0,
                'overall_progress_percent' => 0,
            );
        }
        
        $source_statuses = array();
        $total_staged_rows = 0;
        
        foreach ( $sources as $source ) {
            $source_id = $source->sourceid ?? $source->ID ?? null;
            if ( $source_id ) {
                $src_status = $this->get_source_status( $source_id );
                $source_statuses[] = $src_status;
                if ( isset( $src_status['staged_rows'] ) ) {
                    $total_staged_rows += $src_status['staged_rows'];
                }
            }
        }
        
        return array(
            'status' => 'ok',
            'project_id' => $project_id,
            'source_count' => count( $source_statuses ),
            'sources' => $source_statuses,
            'total_staged_rows' => $total_staged_rows,
            'overall_progress_percent' => $total_staged_rows > 0 ? 75 : 0, // Placeholder calculation
            'import_ready' => $total_staged_rows > 0,
        );
    }
    
    /**
     * Get a timeline of recent import operations.
     * 
     * This provides a history-like view of what imports have been attempted.
     * In future, this could be backed by a dedicated import_log table.
     * 
     * @param int $project_id
     * @param int $limit
     * @return array
     */
    public function get_import_history( $project_id, $limit = 10 ) {
        $sources = $this->source_repo->get_project_sources( $project_id );
        
        $history = array();
        
        foreach ( $sources as $source ) {
            $source_id = $source->sourceid ?? $source->ID ?? null;
            if ( $source_id ) {
                $history[] = array(
                    'source_id' => $source_id,
                    'source_name' => $source->name ?? '',
                    'last_action' => $source->timestamp ?? 'never',
                    'type' => 'source_update',
                );
            }
        }
        
        // Sort by timestamp desc and limit
        usort( $history, function( $a, $b ) {
            return strcmp( $b['last_action'], $a['last_action'] );
        } );
        
        return array_slice( $history, 0, $limit );
    }
    
    /**
     * Calculate progress as a percentage based on staged rows and other factors.
     * 
     * @param int $source_id
     * @param int $staged_rows
     * @return int Progress percentage (0-100)
     */
    private function calculate_progress_percent( $source_id, $staged_rows ) {
        // Simple heuristic: if there are staged rows, progress is 75% (ready for post gen)
        // else if the table exists, progress is 50% (schema ready)
        // else 25% (source exists but not ready)
        
        $source = $this->source_repo->get_source( $source_id );
        if ( ! $source ) {
            return 0;
        }
        
        if ( $staged_rows > 0 ) {
            return 75;
        }
        
        if ( $this->source_repo->table_exists( $source->tablename ?? '' ) ) {
            return 50;
        }
        
        return 25;
    }
    
    public function __construct( SourceRepository $source_repo ) {
        $this->source_repo = $source_repo;
    }
}
