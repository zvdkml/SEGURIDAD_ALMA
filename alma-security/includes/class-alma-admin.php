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
		add_action( 'wp_ajax_alma_fix_check', array( $this, 'ajax_fix_check' ) );
		add_action( 'wp_ajax_alma_manage_users', array( $this, 'ajax_manage_users' ) );
		add_action( 'wp_ajax_alma_delete_scan_data', array( $this, 'ajax_delete_scan_data' ) );
		add_action( 'wp_ajax_alma_get_check_history', array( $this, 'ajax_get_check_history' ) );
	}

	public function register_settings() {
		register_setting( 'alma_security_settings', 'alma_security_api_endpoint' );
		register_setting( 'alma_security_settings', 'alma_security_api_key' );
		register_setting( 'alma_security_settings', 'alma_security_enable_api' );
		register_setting( 'alma_security_settings', 'alma_security_monitor_endpoint' );
	}

	public function register_menu() {
		add_menu_page(
			'Security Monitor',
			'Security Monitor',
			'alma_security_view',
			'alma-security',
			array( $this, 'render_dashboard' ),
			'dashicons-shield',
			80
		);

		add_submenu_page(
			'alma-security',
			'Security Dashboard',
			'Security Dashboard',
			'alma_security_view',
			'alma-security',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'alma-security',
			'Historial',
			'Historial',
			'alma_security_view',
			'alma-history',
			array( $this, 'render_history' )
		);

		add_submenu_page(
			'alma-security',
			'Configuración',
			'Configuración',
			'alma_security_admin',
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
		$score_history = $db->get_score_history();
		$persisted_results = array();
		foreach ( $db_results as $row ) {
			$is_vulnerabilities = ! empty( $row['is_vulnerabilities'] );
			$persisted_results[ $row['check_id'] ] = array(
				'name'               => $row['check_name'],
				'status'             => $row['status'],
				'description'        => $is_vulnerabilities ? 'Se han detectado vulnerabilidades conocidas.' : $row['result'],
				'recommendation'     => $row['recommendation'],
				'risk_level'         => $row['risk_level'],
				'last_scan_at'       => $row['last_scan_at'],
				'is_vulnerabilities' => $is_vulnerabilities,
				'data'               => $is_vulnerabilities ? json_decode( $row['result'], true ) : null,
			);
		}

		$auth = new Alma_Auth();
		$current_role = $auth->get_current_user_role();

		$user_caps = array(
			'can_scan' => $auth->can( 'individual_scan' ),
			'can_fix'  => $auth->can( 'fix_issues' ),
			'can_admin' => $auth->can( 'manage_users' ),
		);

		wp_localize_script( 'alma-admin-js', 'alma_ajax', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'alma_security_nonce' ),
			'latest_scan'  => $current_scan,
			'history'      => $history_data,
			'scan_index'   => $scan_index,
			'db_results'   => $persisted_results,
			'user_role'    => $current_role,
			'user_caps'    => $user_caps,
			'score_history'=> $score_history,
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

		// Security Checks
		if ( $type === 'all' && ! $auth->can( 'full_scan' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar un escaneo completo.' ) );
		}

		if ( ! empty( $check_id ) && ! $auth->can( 'individual_scan' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar escaneos individuales.' ) );
		}

		if ( $type !== 'all' && empty( $check_id ) && ! $auth->can( 'individual_scan' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar escaneos de módulos.' ) );
		}

		$scanner = new Alma_Scanner();
		$results = $scanner->run_scan( $type, $check_id );

		// Database Persistence
		$db = new Alma_DB();
		if ( isset( $results['vulnerabilities'] ) ) {
			foreach ( $results['vulnerabilities'] as $id => $data ) {
				$db->save_check_result( $id, $data );
			}
		}

		// Save to history (only full scans)
		if ( $type === 'all' ) {
			$history = new Alma_History();
			$history->save_scan( $results, 'all' );
			$db->save_score_history( $results['score'] );
			$results['score_history'] = $db->get_score_history();
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
			wp_send_json_error( array( 'message' => 'Acceso denegado.' ) );
		}

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}alma_scans" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}alma_scan_history" );
		update_option( 'alma_security_history', array() );

		wp_send_json_success( array( 'message' => 'Datos eliminados correctamente.' ) );
	}

	public function ajax_get_check_history() {
		check_ajax_referer( 'alma_security_nonce', 'nonce' );

		$check_id = isset( $_GET['check_id'] ) ? sanitize_text_field( $_GET['check_id'] ) : '';
		if ( empty( $check_id ) ) {
			wp_send_json_error( array( 'message' => 'ID de verificación faltante.' ) );
		}

		$db = new Alma_DB();
		$history = $db->get_check_history( $check_id );

		wp_send_json_success( $history );
	}

	public function ajax_fix_check() {
		error_log( "[Alma Security] Iniciando ajax_fix_check" );
		check_ajax_referer( 'alma_security_nonce', 'nonce' );
		$auth = new Alma_Auth();
		if ( ! $auth->can( 'fix_issues' ) ) {
			error_log( "[Alma Security] Error: Permisos insuficientes" );
			wp_send_json_error( array( 'message' => 'No tienes permisos para realizar reparaciones.' ) );
		}

		$check_id = isset( $_POST['check_id'] ) ? sanitize_text_field( $_POST['check_id'] ) : '';
		error_log( "[Alma Security] Parámetro check recibido: " . $check_id );
		if ( empty( $check_id ) ) {
			wp_send_json_error( array( 'message' => 'ID de verificación faltante.' ) );
		}

		$scanner = new Alma_Scanner();

		// Attempt to automatically fix the issue
		error_log( "[Alma Security] Ejecutando fix_check para: " . $check_id );
		$scanner->fix_check( $check_id );

		$results = $scanner->run_scan( 'individual', $check_id );

		// Update database with new result
		if ( isset( $results['vulnerabilities'][ $check_id ] ) ) {
			$db = new Alma_DB();

			// Force status to secure if fix_check says so, even if scan lag exists
			if ( $results['vulnerabilities'][ $check_id ]['status'] !== 'secure' ) {
				error_log( "[Alma Security] El escaneo inicial post-reparación no devolvió 'secure'. Re-verificando..." );
				// Re-verify strictly
				$results = $scanner->run_scan( 'individual', $check_id );
			}

			error_log( "[Alma Security] Guardando nuevo estado en DB: " . $results['vulnerabilities'][ $check_id ]['status'] );
			$db->save_check_result( $check_id, $results['vulnerabilities'][ $check_id ] );

			if ( $results['vulnerabilities'][ $check_id ]['status'] === 'secure' ) {
				// Success: Synchronize global score and latest scan data
				$full_scan = $scanner->run_scan( 'all' );
				$db->save_score_history( $full_scan['score'] );

				$history = new Alma_History();
				$history->save_scan( $full_scan, 'all' );

				wp_send_json_success( array( 'message' => 'Reparación completada y verificada.' ) );
			} else {
				wp_send_json_error( array( 'message' => 'La reparación fue intentada pero el sistema sigue detectando el problema. Por favor, revisa las recomendaciones.' ) );
			}
		}

		wp_send_json_error( array( 'message' => 'No se pudo verificar la reparación.' ) );
	}

	public function ajax_manage_users() {
		check_ajax_referer( 'alma_security_nonce', 'nonce' );
		$auth = new Alma_Auth();
		if ( ! $auth->can( 'manage_users' ) ) {
			wp_send_json_error( array( 'message' => 'Acceso denegado.' ) );
		}

		$operation = isset( $_POST['operation'] ) ? sanitize_text_field( $_POST['operation'] ) : '';

		if ( $operation === 'create' ) {
			$user = sanitize_text_field( $_POST['username'] );
			$pass = $_POST['password'];
			$role = sanitize_text_field( $_POST['role'] );
			$auth->create_user( $user, $pass, $role );
			wp_send_json_success( array( 'message' => 'Usuario creado.' ) );
		} elseif ( $operation === 'delete' ) {
			$id = intval( $_POST['user_id'] );
			$auth->delete_user( $id );
			wp_send_json_success( array( 'message' => 'Usuario eliminado.' ) );
		}

		wp_send_json_error( array( 'message' => 'Operación inválida.' ) );
	}
}

new Alma_Admin();
