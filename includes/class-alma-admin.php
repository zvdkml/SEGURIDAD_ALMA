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
		add_action( 'wp_ajax_alma_manage_users', array( $this, 'ajax_manage_users' ) );
		add_action( 'wp_ajax_alma_delete_scan_data', array( $this, 'ajax_delete_scan_data' ) );
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

		$db = new Alma_DB();
		$db_results = $db->get_all_results();
		$persisted_results = array();
		foreach ( $db_results as $row ) {
			$persisted_results[ $row['check_id'] ] = array(
				'name'           => $row['check_name'],
				'status'         => $row['status'],
				'description'    => $row['result'],
				'recommendation' => $row['recommendation'],
				'last_scan_at'   => $row['last_scan_at']
			);
		}

		$auth = new Alma_Auth();
		$current_role = $auth->get_current_user_role();

		wp_localize_script( 'alma-admin-js', 'alma_ajax', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'alma_security_nonce' ),
			'latest_scan'  => $current_scan,
			'history'      => $history_data,
			'scan_index'   => $scan_index,
			'db_results'   => $persisted_results,
			'user_role'    => $current_role,
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

		$auth = new Alma_Auth();
		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'all';
		$check_id = isset( $_POST['check_id'] ) ? sanitize_text_field( $_POST['check_id'] ) : '';

		if ( $type === 'all' && ! $auth->can( 'full_scan' ) ) {
			wp_send_json_error( 'No tienes permisos para realizar un escaneo completo.' );
		}

		if ( ! empty( $check_id ) && ! $auth->can( 'individual_scan' ) ) {
			wp_send_json_error( 'No tienes permisos para realizar escaneos individuales.' );
		}
		$check_id = isset( $_POST['check_id'] ) ? sanitize_text_field( $_POST['check_id'] ) : '';

		$scanner = new Alma_Scanner();
		$results = $scanner->run_scan( $type, $check_id );

		// Database Persistence
		$db = new Alma_DB();
		if ( $type === 'all' ) {
			foreach ( $results['vulnerabilities'] as $id => $data ) {
				$db->save_check_result( $id, $data );
			}
		} elseif ( ! empty( $check_id ) && isset( $results['vulnerabilities'][ $check_id ] ) ) {
			$db->save_check_result( $check_id, $results['vulnerabilities'][ $check_id ] );
		}

		// Save to history (only full scans)
		if ( $type === 'all' ) {
			$history = new Alma_History();
			$history->save_scan( $results, 'all' );
		}

		// API Integration
		if ( get_option( 'alma_security_enable_api' ) ) {
			$api = new Alma_API();
			$api->send_to_external_api( $results );
		}

		wp_send_json_success( $results );
	}

	public function ajax_delete_scan_data() {
		check_ajax_referer( 'alma_security_nonce', 'nonce' );
		$auth = new Alma_Auth();
		if ( ! $auth->can( 'delete_data' ) ) {
			wp_send_json_error( 'Acceso denegado.' );
		}

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}alma_scans" );
		update_option( 'alma_security_history', array() );

		wp_send_json_success( 'Datos eliminados correctamente.' );
	}

	public function ajax_manage_users() {
		check_ajax_referer( 'alma_security_nonce', 'nonce' );
		$auth = new Alma_Auth();
		if ( ! $auth->can( 'manage_users' ) ) {
			wp_send_json_error( 'Acceso denegado.' );
		}

		$operation = isset( $_POST['operation'] ) ? sanitize_text_field( $_POST['operation'] ) : '';

		if ( $operation === 'create' ) {
			$user = sanitize_text_field( $_POST['username'] );
			$pass = $_POST['password'];
			$role = sanitize_text_field( $_POST['role'] );
			$auth->create_user( $user, $pass, $role );
			wp_send_json_success( 'Usuario creado.' );
		} elseif ( $operation === 'delete' ) {
			$id = intval( $_POST['user_id'] );
			$auth->delete_user( $id );
			wp_send_json_success( 'Usuario eliminado.' );
		}

		wp_send_json_error( 'Operación inválida.' );
	}
}

new Alma_Admin();
