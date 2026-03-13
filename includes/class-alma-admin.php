<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_alma_run_scan', array( $this, 'ajax_run_scan' ) );
	}

	public function register_settings() {
		register_setting( 'alma_security_settings', 'alma_security_api_endpoint' );
		register_setting( 'alma_security_settings', 'alma_security_api_key' );
		register_setting( 'alma_security_settings', 'alma_security_enable_api' );
	}

	public function register_menu() {
		add_menu_page(
			'Security Monitor',
			'Security Monitor',
			'manage_options',
			'alma-security',
			array( $this, 'render_dashboard' ),
			'dashicons-shield',
			80
		);

		add_submenu_page(
			'alma-security',
			'Security Dashboard',
			'Security Dashboard',
			'manage_options',
			'alma-security',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'alma-security',
			'Historial',
			'Historial',
			'manage_options',
			'alma-history',
			array( $this, 'render_history' )
		);

		add_submenu_page(
			'alma-security',
			'Configuración',
			'Configuración',
			'manage_options',
			'alma-settings',
			array( $this, 'render_settings' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'alma' ) === false ) {
			return;
		}

		// Tailwind CSS CDN
		wp_enqueue_style( 'alma-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' );

		// Chart.js CDN
		wp_enqueue_script( 'alma-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );

		// Custom JS
		wp_enqueue_script( 'alma-admin-js', ALMA_SECURITY_URL . 'assets/js/alma-admin.js', array( 'jquery', 'alma-chartjs' ), ALMA_SECURITY_VERSION, true );

		$history = new Alma_History();
		$scan_index = isset( $_GET['scan_index'] ) ? intval( $_GET['scan_index'] ) : -1;
		$history_data = $history->get_history();
		$latest_scan = $history->get_latest_scan();

		$current_scan = $latest_scan;
		if ( $scan_index !== -1 && isset( $history_data[ $scan_index ] ) ) {
			$current_scan = $history_data[ $scan_index ];
		}

		wp_localize_script( 'alma-admin-js', 'alma_ajax', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'alma_security_nonce' ),
			'latest_scan'  => $current_scan,
			'history'      => $history_data,
			'scan_index'   => $scan_index,
		) );
	}

	public function render_dashboard() {
		$history = new Alma_History();
		$scan_index = isset( $_GET['scan_index'] ) ? intval( $_GET['scan_index'] ) : -1;

		if ( $scan_index !== -1 ) {
			$history_data = $history->get_history();
			$latest_scan = isset( $history_data[ $scan_index ] ) ? $history_data[ $scan_index ] : $history->get_latest_scan();
		} else {
			$latest_scan = $history->get_latest_scan();
		}

		include ALMA_SECURITY_PATH . 'templates/dashboard.php';
	}


	public function render_history() {
		$history = new Alma_History();
		$history_data = $history->get_history();
		include ALMA_SECURITY_PATH . 'templates/history.php';
	}

	public function render_settings() {
		include ALMA_SECURITY_PATH . 'templates/settings.php';
	}

	public function ajax_run_scan() {
		check_ajax_referer( 'alma_security_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Acceso denegado' );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'all';
		$scanner = new Alma_Scanner();
		$results = $scanner->run_scan( $type );

		// Save to history
		$history = new Alma_History();
		$history->save_scan( $results );

		// API Integration
		if ( get_option( 'alma_security_enable_api' ) ) {
			$api = new Alma_API();
			$api->send_to_external_api( $results );
		}

		wp_send_json_success( $results );
	}
}

new Alma_Admin();
