<?php

namespace CSV2POST\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ImportService {

    private $repository;

    public function __construct( \CSV2POST\Services\SourceRepository $repository ) {
        $this->repository = $repository;
    }

    public function import_source( $source_id, $project_id, $event_type = 'import', $inserted_limit = 9999999 ) {
        $importer = $this->legacy_importer();

        return $importer->import_from_csv_file(
            (int) $source_id,
            (int) $project_id,
            $event_type,
            (int) $inserted_limit
        );
    }

    public function describe_import_context( $source_id, $project_id ) {
        $source = $this->repository->get_source( $source_id );
        $project = $this->repository->get_project( $project_id );

        return array(
            'source_id' => (int) $source_id,
            'project_id' => (int) $project_id,
            'source' => $source,
            'project' => $project,
            'data_treatment' => $this->repository->get_data_treatment( $project_id ),
            'project_sources' => $this->repository->get_project_sources( $project_id ),
            'main_table' => $this->repository->get_project_main_table( $project_id ),
        );
    }

    private function legacy_importer() {
        if ( ! class_exists( '\CSV2POST_Data' ) ) {
            require_once CSV2POST_ABSPATH . 'classes/class-data.php';
        }

        return new \CSV2POST_Data();
    }
}
