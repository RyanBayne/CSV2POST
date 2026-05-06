<?php
/**
 * Project Diagnostics Admin Page
 * 
 * Modern admin interface for viewing project health, sources, and data preview.
 * This page uses the ProjectDiagnostics service to display structured information
 * without modifying legacy admin code.
 *
 * @package CSV2POST\Admin
 */

namespace CSV2POST\Admin;

/**
 * ProjectDiagnosticsPage
 * 
 * Provides a modern admin page for project inspection and diagnostics.
 * Uses the service layer instead of legacy inspection methods.
 */
class ProjectDiagnosticsPage {
    
    /**
     * @var \CSV2POST\Services\ProjectDiagnostics
     */
    private $diagnostics;
    
    /**
     * @var string
     */
    private $page_slug = 'csv2post-diagnostics';
    
    public function __construct( \CSV2POST\Services\ProjectDiagnostics $diagnostics ) {
        $this->diagnostics = $diagnostics;
    }
    
    /**
     * Register the admin page and hooks.
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_csv2post_diagnostics_project', array( $this, 'ajax_get_project_diagnostic' ) );
        add_action( 'wp_ajax_csv2post_diagnostics_source', array( $this, 'ajax_get_source_diagnostic' ) );
    }
    
    /**
     * Add the diagnostics submenu page.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'csv2post',
            __( 'Project Diagnostics', 'csv2post' ),
            __( 'Diagnostics', 'csv2post' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_page' )
        );
    }
    
    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts( $hook ) {
        if ( "csv2post_page_{$this->page_slug}" !== $hook ) {
            return;
        }
        
        wp_enqueue_script( 
            'csv2post-diagnostics',
            plugins_url( 'js/diagnostics.js', CSV2POST__FILE__ ),
            array( 'jquery', 'wp-api-request' ),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'csv2post-diagnostics',
            plugins_url( 'css/diagnostics.css', CSV2POST__FILE__ ),
            array(),
            '1.0.0'
        );
        
        wp_localize_script( 'csv2post-diagnostics', 'csv2postDiag', array(
            'nonce' => wp_create_nonce( 'csv2post_diagnostics' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        ) );
    }
    
    /**
     * Render the diagnostics admin page.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'csv2post' ) );
        }
        
        global $CSV2POST_Class;
        $projects = $CSV2POST_Class ? $this->get_all_projects() : array();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'CSV2POST Project Diagnostics', 'csv2post' ); ?></h1>
            <p><?php esc_html_e( 'View project health, sources, and staged data.', 'csv2post' ); ?></p>
            
            <?php if ( empty( $projects ) ) : ?>
                <div class="notice notice-info"><p><?php esc_html_e( 'No projects found.', 'csv2post' ); ?></p></div>
            <?php else : ?>
                <div id="csv2post-diagnostics-container" class="csv2post-diagnostics">
                    <?php foreach ( $projects as $project ) : ?>
                        <div class="project-card" data-project-id="<?php echo absint( $project->projectid ); ?>">
                            <h2><?php echo esc_html( $project->projectname ); ?></h2>
                            <p class="project-meta">
                                <strong><?php esc_html_e( 'Project ID:', 'csv2post' ); ?></strong> 
                                <?php echo absint( $project->projectid ); ?>
                                <strong><?php esc_html_e( 'Created:', 'csv2post' ); ?></strong> 
                                <?php echo esc_html( $project->timestamp ); ?>
                            </p>
                            <button class="button load-diagnostic" data-project-id="<?php echo absint( $project->projectid ); ?>">
                                <?php esc_html_e( 'Load Diagnostic', 'csv2post' ); ?>
                            </button>
                            <div class="diagnostic-content"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for project diagnostics.
     */
    public function ajax_get_project_diagnostic() {
        check_ajax_referer( 'csv2post_diagnostics', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }
        
        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        if ( ! $project_id ) {
            wp_send_json_error( array( 'message' => 'Missing project_id' ) );
        }
        
        $diagnostic = $this->diagnostics->project_summary( $project_id );
        wp_send_json_success( $diagnostic );
    }
    
    /**
     * AJAX handler for source diagnostics.
     */
    public function ajax_get_source_diagnostic() {
        check_ajax_referer( 'csv2post_diagnostics', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }
        
        $source_id = isset( $_POST['source_id'] ) ? absint( $_POST['source_id'] ) : 0;
        $sample_limit = isset( $_POST['sample_limit'] ) ? absint( $_POST['sample_limit'] ) : 5;
        
        if ( ! $source_id ) {
            wp_send_json_error( array( 'message' => 'Missing source_id' ) );
        }
        
        $diagnostic = $this->diagnostics->source_full_diagnostic( $source_id, $sample_limit );
        wp_send_json_success( $diagnostic );
    }
    
    /**
     * Get all projects from the database.
     *
     * @return array
     */
    private function get_all_projects() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT projectid, projectname, timestamp FROM {$wpdb->prefix}c2pprojects ORDER BY timestamp DESC",
            OBJECT
        );
        
        return $results ? $results : array();
    }
}
