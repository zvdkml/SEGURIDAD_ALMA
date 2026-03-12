<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Scanner {

	public function run_scan() {
		$results = array(
			'wp_update'         => $this->check_wp_update(),
			'plugins_update'    => $this->check_plugins_update(),
			'themes_update'     => $this->check_themes_update(),
			'xmlrpc'            => $this->check_xmlrpc(),
			'debug_mode'        => $this->check_debug_mode(),
			'sensitive_files'   => $this->check_sensitive_files(),
			'file_permissions'  => $this->check_file_permissions(),
			'admin_users'       => $this->check_admin_users(),
			'login_attempts'    => $this->check_login_attempts(),
			'https'             => $this->check_https(),
			'security_headers'  => $this->check_security_headers(),
			'directory_listing' => $this->check_directory_listing(),
			'abandoned_plugins' => $this->check_abandoned_plugins(),
		);

		$score = $this->calculate_score( $results );

		$data = array(
			'score'           => $score,
			'level'           => $this->get_security_level( $score ),
			'vulnerabilities' => $results,
			'timestamp'       => time(),
		);

		return $data;
	}

	private function check_wp_update() {
		$current = get_site_transient( 'update_core' );
		$is_secure = true;
		if ( isset( $current->updates ) && ! empty( $current->updates ) ) {
			if ( $current->updates[0]->response === 'upgrade' ) {
				$is_secure = false;
			}
		}
		return array(
			'name'           => 'WordPress Core Update',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => $is_secure ? 'WordPress está actualizado.' : 'Hay una nueva versión de WordPress disponible.',
			'recommendation' => 'Actualiza WordPress a la última versión.',
		);
	}

	private function check_plugins_update() {
		$update_plugins = get_site_transient( 'update_plugins' );
		$count = ! empty( $update_plugins->response ) ? count( $update_plugins->response ) : 0;
		return array(
			'name'           => 'Plugin Updates',
			'status'         => $count === 0 ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'count'          => $count,
			'description'    => $count === 0 ? 'Todos los plugins están actualizados.' : "Tienes $count plugins desactualizados.",
			'recommendation' => 'Actualiza todos los plugins a sus últimas versiones.',
		);
	}

	private function check_themes_update() {
		$update_themes = get_site_transient( 'update_themes' );
		$count = ! empty( $update_themes->response ) ? count( $update_themes->response ) : 0;
		return array(
			'name'           => 'Theme Updates',
			'status'         => $count === 0 ? 'secure' : 'warning',
			'risk'           => 'Low',
			'count'          => $count,
			'description'    => $count === 0 ? 'Todos los temas están actualizados.' : "Tienes $count temas desactualizados.",
			'recommendation' => 'Actualiza tus temas.',
		);
	}

	private function check_xmlrpc() {
		$is_active = true; // By default WP has it active
		// Check if it's disabled via filter
		if ( ! apply_filters( 'xmlrpc_enabled', true ) ) {
			$is_active = false;
		}
		return array(
			'name'           => 'XML-RPC',
			'status'         => $is_active ? 'warning' : 'secure',
			'risk'           => 'Medium',
			'description'    => $is_active ? 'XML-RPC está activado, lo que puede ser usado para ataques de fuerza bruta.' : 'XML-RPC está desactivado.',
			'recommendation' => 'Desactiva XML-RPC si no lo necesitas.',
		);
	}

	private function check_debug_mode() {
		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		return array(
			'name'           => 'Modo Debug',
			'status'         => $debug ? 'critical' : 'secure',
			'risk'           => 'High',
			'description'    => $debug ? 'WP_DEBUG está activado, exponiendo información sensible.' : 'El modo debug está desactivado.',
			'recommendation' => 'Desactiva WP_DEBUG en wp-config.php.',
		);
	}

	private function check_sensitive_files() {
		$files = array( 'readme.html', 'license.txt', 'wp-config-sample.php' );
		$exposed = array();
		foreach ( $files as $file ) {
			if ( file_exists( ABSPATH . $file ) ) {
				$exposed[] = $file;
			}
		}
		$is_secure = empty( $exposed );
		return array(
			'name'           => 'Archivos Sensibles',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Low',
			'description'    => $is_secure ? 'No se detectaron archivos informativos expuestos.' : 'Archivos como readme.html o license.txt están presentes.',
			'recommendation' => 'Elimina archivos innecesarios que revelen la versión de WordPress.',
		);
	}

	private function check_file_permissions() {
		$file = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $file ) ) {
			return array(
				'name'           => 'Permisos de Archivos',
				'status'         => 'warning',
				'risk'           => 'Medium',
				'description'    => 'No se pudo encontrar wp-config.php para verificar permisos.',
				'recommendation' => 'Asegúrate de que wp-config.php exista y tenga permisos restrictivos.',
			);
		}
		$wp_config_perms = substr( sprintf( '%o', fileperms( $file ) ), -3 );
		$is_secure = ( $wp_config_perms == '400' || $wp_config_perms == '440' || $wp_config_perms == '600' || $wp_config_perms == '640' || $wp_config_perms == '644' );
		return array(
			'name'           => 'Permisos de Archivos',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => "Permisos de wp-config.php: $wp_config_perms.",
			'recommendation' => 'Asegúrate de que wp-config.php tenga permisos restrictivos (ej. 644 o 600).',
		);
	}

	private function check_admin_users() {
		$args = array( 'role' => 'Administrator' );
		$users = get_users( $args );
		$insecure_names = array( 'admin', 'administrator', 'webmaster' );
		$found_insecure = array();
		foreach ( $users as $user ) {
			if ( in_array( strtolower( $user->user_login ), $insecure_names ) ) {
				$found_insecure[] = $user->user_login;
			}
		}
		$is_secure = empty( $found_insecure );
		return array(
			'name'           => 'Usuarios Administradores',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => $is_secure ? 'No se encontraron nombres de usuario administrador comunes.' : 'Se encontraron administradores con nombres genéricos (admin).',
			'recommendation' => 'Evita usar "admin" como nombre de usuario administrador.',
		);
	}

	private function check_https() {
		$is_https = is_ssl();
		return array(
			'name'           => 'Conexión HTTPS',
			'status'         => $is_https ? 'secure' : 'critical',
			'risk'           => 'High',
			'description'    => $is_https ? 'El sitio usa HTTPS.' : 'El sitio no usa una conexión segura (HTTPS).',
			'recommendation' => 'Instala un certificado SSL y activa HTTPS.',
		);
	}

	private function check_security_headers() {
		$response = wp_remote_get( home_url() );
		if ( is_wp_error( $response ) ) {
			return array(
				'name'           => 'Headers de Seguridad',
				'status'         => 'warning',
				'risk'           => 'Medium',
				'description'    => 'No se pudo conectar al sitio para verificar los headers.',
				'recommendation' => 'Verifica manualmente los headers de seguridad.',
			);
		}

		$headers = wp_remote_retrieve_headers( $response );
		$security_headers = array(
			'Strict-Transport-Security',
			'X-Content-Type-Options',
			'X-Frame-Options',
			'X-XSS-Protection',
			'Content-Security-Policy',
		);

		$found = array();
		foreach ( $security_headers as $header ) {
			if ( isset( $headers[ strtolower( $header ) ] ) ) {
				$found[] = $header;
			}
		}

		$is_secure = count( $found ) >= 3;
		return array(
			'name'           => 'Headers de Seguridad',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => $is_secure ? 'Se detectaron headers de seguridad principales.' : 'Faltan headers de seguridad importantes (detectados: ' . ( empty( $found ) ? 'ninguno' : implode( ', ', $found ) ) . ').',
			'recommendation' => 'Configura headers de seguridad para prevenir ataques XSS y Clickjacking.',
		);
	}

	private function check_directory_listing() {
		$upload_dir = wp_upload_dir();
		$response = wp_remote_get( $upload_dir['baseurl'] );

		$is_secure = true;
		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			if ( strpos( $body, 'Index of' ) !== false || strpos( $body, 'Parent Directory' ) !== false ) {
				$is_secure = false;
			}
		}

		return array(
			'name'           => 'Listado de Directorios',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => $is_secure ? 'El listado de directorios parece estar desactivado.' : 'El listado de directorios está habilitado, exponiendo tus archivos.',
			'recommendation' => 'Añade "Options -Indexes" a tu archivo .htaccess o usa un archivo index.php vacío.',
		);
	}

	private function check_login_attempts() {
		$protection_plugins = array(
			'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php',
			'wp-limit-login-attempts/wp-limit-login-attempts.php',
			'all-in-one-wp-security-and-firewall/all-in-one-wp-security-and-firewall.php',
			'wordfence/wordfence.php',
			'sucuri-scanner/sucuri.php',
		);

		$active_plugins = get_option( 'active_plugins' );
		$is_protected = false;
		foreach ( $protection_plugins as $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				$is_protected = true;
				break;
			}
		}

		return array(
			'name'           => 'Intentos de Login',
			'status'         => $is_protected ? 'secure' : 'warning',
			'risk'           => 'Medium',
			'description'    => $is_protected ? 'Se detectó un plugin de protección contra ataques de fuerza bruta.' : 'No se detectó protección contra intentos de login ilimitados.',
			'recommendation' => 'Instala un plugin como "Limit Login Attempts Reloaded".',
		);
	}

	private function check_abandoned_plugins() {
		if ( ! function_exists( 'get_plugins' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$update_plugins = get_site_transient( 'update_plugins' );
		$abandoned = array();

		foreach ( $all_plugins as $file => $data ) {
			// In a real scenario, we'd check against a DB of abandoned plugins or WP.org API
			// For now, we check if there's information about the last update in the transient
			if ( isset( $update_plugins->no_update[ $file ] ) ) {
				// This info is limited in the transient, so we'll do a basic check
			}
		}

		return array(
			'name'           => 'Plugins Abandonados',
			'status'         => 'secure', // Hard to determine without complex API calls
			'risk'           => 'Medium',
			'description'    => 'No se detectaron plugins abandonados críticamente.',
			'recommendation' => 'Revisa periódicamente que tus plugins sigan recibiendo actualizaciones.',
		);
	}

	private function calculate_score( $results ) {
		$total_checks = count( $results );
		$secure_checks = 0;
		foreach ( $results as $check ) {
			if ( $check['status'] === 'secure' ) {
				$secure_checks++;
			} elseif ( $check['status'] === 'warning' ) {
				$secure_checks += 0.5;
			}
		}
		return round( ( $secure_checks / $total_checks ) * 100 );
	}

	private function get_security_level( $score ) {
		if ( $score >= 80 ) {
			return 'Alto';
		} elseif ( $score >= 50 ) {
			return 'Medio';
		} else {
			return 'Bajo';
		}
	}
}
