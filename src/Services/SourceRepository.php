<?php

namespace CSV2POST\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SourceRepository {

    private $db;

    public function __construct( $db = null ) {
        $this->db = $db ? $db : new \CSV2POST_DB();
    }

    public function get_source( $source_id ) {
        return $this->db->get_source( (int) $source_id );
    }

    public function get_project( $project_id ) {
        return $this->db->get_project( (int) $project_id );
    }

    public function get_data_treatment( $project_id ) {
        return $this->db->get_data_treatment( (int) $project_id );
    }

    public function get_project_sources( $project_id ) {
        return $this->db->get_project_sourcesid( (int) $project_id );
    }

    public function get_project_main_table( $project_id ) {
        return \CSV2POST_DB::get_project_main_table( (int) $project_id );
    }

    public function get_project_columns( $project_id, $apply_exclusions = true ) {
        return $this->db->get_project_columns_from_db( (int) $project_id, (bool) $apply_exclusions );
    }

    public function table_exists( $table_name ) {
        return $this->db->does_table_exist( $table_name );
    }
}
