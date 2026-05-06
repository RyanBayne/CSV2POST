<?php

namespace CSV2POST\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PluginBootstrap {

    public static function boot( $plugin_file ) {
        self::define_constants( $plugin_file );
        self::load_modern_runtime();
        self::load_legacy_runtime();

        \add_action( 'plugins_loaded', array( self::class, 'load_textdomain' ) );
        \add_action( 'plugins_loaded', array( '\\CSV2POST', 'init' ) );

        $install = new \CSV2POST_Install();
        \register_activation_hook( $plugin_file, array( $install, 'install_plugin' ) );
        \register_deactivation_hook( $plugin_file, array( $install, 'deactivate_plugin' ) );
    }

    private static function load_modern_runtime() {
        require_once CSV2POST_DIR_PATH . 'src/ServiceContainer.php';
        require_once CSV2POST_DIR_PATH . 'src/Services/SourceRepository.php';
        require_once CSV2POST_DIR_PATH . 'src/Services/ImportService.php';
        require_once CSV2POST_DIR_PATH . 'src/Services/ProjectDiagnostics.php';
        require_once CSV2POST_DIR_PATH . 'src/Services/SchemaBuilder.php';
        require_once CSV2POST_DIR_PATH . 'src/Services/ImportProgress.php';
        require_once CSV2POST_DIR_PATH . 'src/Admin/WizardFlow.php';
        require_once CSV2POST_DIR_PATH . 'src/Admin/ProjectDiagnosticsPage.php';
    }

    private static function define_constants( $plugin_file ) {
        if ( ! \defined( 'CSV2POST_VERSION' ) ) {
            \define( 'CSV2POST_VERSION', '8.3.0' );
        }

        if ( ! \defined( 'CSV2POST_WPVERSIONMINIMUM' ) ) {
            \define( 'CSV2POST_WPVERSIONMINIMUM', '4.4.0' );
        }

        if ( ! \defined( 'CSV2POST_PHPVERSIONMINIMUM' ) ) {
            \define( 'CSV2POST_PHPVERSIONMINIMUM', '5.4.0' );
        }

        if ( ! \defined( 'CSV2POST_NAME' ) ) {
            \define( 'CSV2POST_NAME', \trim( \dirname( \plugin_basename( $plugin_file ) ), '/' ) );
        }

        if ( ! \defined( 'CSV2POST__FILE__' ) ) {
            \define( 'CSV2POST__FILE__', $plugin_file );
        }

        if ( ! \defined( 'CSV2POST_DIR_PATH' ) ) {
            \define( 'CSV2POST_DIR_PATH', \plugin_dir_path( $plugin_file ) );
        }

        if ( ! \defined( 'CSV2POST_BASENAME' ) ) {
            \define( 'CSV2POST_BASENAME', \plugin_basename( $plugin_file ) );
        }

        if ( ! \defined( 'CSV2POST_ABSPATH' ) ) {
            \define( 'CSV2POST_ABSPATH', \plugin_dir_path( $plugin_file ) );
        }

        if ( ! \defined( 'CSV2POST_IMAGES_URL' ) ) {
            \define( 'CSV2POST_IMAGES_URL', \plugins_url( 'images/', $plugin_file ) );
        }
    }

    private static function load_legacy_runtime() {
        require_once CSV2POST_DIR_PATH . 'functions/functions.debug.php';
        require_once CSV2POST_ABSPATH . 'classes/class-wpdb.php';
        require_once CSV2POST_ABSPATH . 'classes/class-options.php';
        require_once CSV2POST_ABSPATH . 'classes/class-install.php';
        require_once CSV2POST_ABSPATH . 'classes/class-configuration.php';
        require_once CSV2POST_ABSPATH . 'classes/class-csv2post.php';
        require_once CSV2POST_ABSPATH . 'classes/class-schedule.php';
        require_once CSV2POST_ABSPATH . 'classes/class-automation.php';
    }

    public static function load_textdomain() {
        \load_plugin_textdomain( 'csv2post', false, \dirname( \plugin_basename( CSV2POST__FILE__ ) ) . '/languages/' );
    }
}