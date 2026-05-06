<?php

namespace CSV2POST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ServiceContainer {

    private static $instance = null;

    private $services = array();

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function source_repository() {
        if ( ! isset( $this->services['source_repository'] ) ) {
            $this->services['source_repository'] = new \CSV2POST\Services\SourceRepository();
        }

        return $this->services['source_repository'];
    }

    public function import_service() {
        if ( ! isset( $this->services['import_service'] ) ) {
            $this->services['import_service'] = new \CSV2POST\Services\ImportService( $this->source_repository() );
        }

        return $this->services['import_service'];
    }

    public function wizard_flow() {
        if ( ! isset( $this->services['wizard_flow'] ) ) {
            $this->services['wizard_flow'] = new \CSV2POST\Admin\WizardFlow();
        }

        return $this->services['wizard_flow'];
    }

    public function project_diagnostics() {
        if ( ! isset( $this->services['project_diagnostics'] ) ) {
            $this->services['project_diagnostics'] = new \CSV2POST\Services\ProjectDiagnostics(
                $this->source_repository(),
                $this->import_service()
            );
        }

        return $this->services['project_diagnostics'];
    }

    public function diagnostics_page() {
        if ( ! isset( $this->services['diagnostics_page'] ) ) {
            $this->services['diagnostics_page'] = new \CSV2POST\Admin\ProjectDiagnosticsPage(
                $this->project_diagnostics()
            );
        }

        return $this->services['diagnostics_page'];
    }

    public function schema_builder() {
        if ( ! isset( $this->services['schema_builder'] ) ) {
            $this->services['schema_builder'] = new \CSV2POST\Services\SchemaBuilder(
                $this->source_repository()
            );
        }

        return $this->services['schema_builder'];
    }

    public function import_progress() {
        if ( ! isset( $this->services['import_progress'] ) ) {
            $this->services['import_progress'] = new \CSV2POST\Services\ImportProgress(
                $this->source_repository()
            );
        }

        return $this->services['import_progress'];
    }
}
