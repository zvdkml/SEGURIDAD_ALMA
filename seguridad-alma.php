<?php
/**
 * Plugin Name: Seguridad Alma
 * Plugin URI: https://github.com/zvdkml/SEGURIDAD_ALMA
 * Description: Plugin de seguridad avanzado para WordPress con dashboard visual moderno.
 * Version: 1.0.0
 * Author: Jules
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALMA_SECURITY_VERSION', '1.0.0' );
define( 'ALMA_SECURITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALMA_SECURITY_URL', plugin_dir_url( __FILE__ ) );

class Seguridad_Alma {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-scanner.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-admin.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-history.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-api.php';
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		// Initialization logic
	}
}

Seguridad_Alma::get_instance();
