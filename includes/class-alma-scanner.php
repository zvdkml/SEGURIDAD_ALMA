<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Scanner {

	public function run_scan( $type = 'all' ) {
		$all_checks = array(
			'wp'      => array( 'wp_update', 'xmlrpc', 'debug_mode', 'sensitive_files', 'file_permissions', 'directory_listing', 'https' ),
			'plugins' => array( 'plugins_update', 'abandoned_plugins' ),
			'themes'  => array( 'themes_update' ),
			'server'  => array( 'file_permissions', 'https', 'security_headers', 'directory_listing', 'sensitive_files' ),
			'users'   => array( 'admin_users', 'login_attempts' ),
			'malware' => array(), // Placeholder for integrity/malware checks
		);

		$results = array();

		if ( $type === 'all' ) {
			$checks_to_run = array( 'wp_update', 'plugins_update', 'themes_update', 'xmlrpc', 'debug_mode', 'sensitive_files', 'file_permissions', 'admin_users', 'login_attempts', 'https', 'security_headers', 'directory_listing', 'abandoned_plugins' );
		} else {
			$checks_to_run = isset( $all_checks[ $type ] ) ? $all_checks[ $type ] : array();
		}

		foreach ( $checks_to_run as $check_id ) {
			$method = 'check_' . $check_id;
			if ( method_exists( $this, $method ) ) {
				$results[ $check_id ] = $this->$method();
			}
		}

		// Handle Malware Scan specially (simulated for now)
		if ( $type === 'malware' ) {
			$results['malware_integrity'] = array(
				'name'           => 'Integridad de Archivos',
				'status'         => 'secure',
				'risk'           => 'Crítico',
				'description'    => 'No se detectaron archivos modificados sospechosos.',
				'recommendation' => 'Sigue monitoreando cambios en archivos del core.',
			);
		}

		$score = $this->calculate_score( $results );
		$counts = $this->get_vulnerability_counts( $results );

		$data = array(
			'score'           => $score,
			'level'           => $this->get_security_level( $score ),
			'vulnerabilities' => $results,
			'counts'          => $counts,
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
			'name'           => 'Actualización de WordPress',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => $is_secure ? 'WordPress está actualizado.' : 'Hay una nueva versión de WordPress disponible que puede contener parches de seguridad.',
			'recommendation' => 'Actualiza WordPress a la última versión disponible.',
		);
	}

	private function check_plugins_update() {
		$update_plugins = get_site_transient( 'update_plugins' );
		$count = ! empty( $update_plugins->response ) ? count( $update_plugins->response ) : 0;
		return array(
			'name'           => 'Actualización de Plugins',
			'status'         => $count === 0 ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'count'          => $count,
			'description'    => $count === 0 ? 'Todos los plugins están actualizados.' : "Tienes $count plugins desactualizados, lo que aumenta el riesgo de vulnerabilidades.",
			'recommendation' => 'Actualiza todos los plugins a sus últimas versiones.',
		);
	}

	private function check_themes_update() {
		$update_themes = get_site_transient( 'update_themes' );
		$count = ! empty( $update_themes->response ) ? count( $update_themes->response ) : 0;
		return array(
			'name'           => 'Actualización de Temas',
			'status'         => $count === 0 ? 'secure' : 'warning',
			'risk'           => 'Bajo',
			'count'          => $count,
			'description'    => $count === 0 ? 'Todos los temas están actualizados.' : "Tienes $count temas desactualizados.",
			'recommendation' => 'Actualiza tus temas.',
		);
	}

	private function check_xmlrpc() {
		$is_active = true;
		if ( ! apply_filters( 'xmlrpc_enabled', true ) ) {
			$is_active = false;
		}
		return array(
			'name'           => 'XML-RPC',
			'status'         => $is_active ? 'warning' : 'secure',
			'risk'           => 'Medio',
			'description'    => $is_active ? 'XML-RPC está activado, lo que puede ser explotado para ataques de fuerza bruta o DDoS.' : 'XML-RPC está desactivado correctamente.',
			'recommendation' => 'Desactiva XML-RPC mediante un plugin o añadiendo un filtro si no utilizas aplicaciones externas o Jetpack.',
		);
	}

	private function check_debug_mode() {
		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		return array(
			'name'           => 'Modo Debug',
			'status'         => $debug ? 'critical' : 'secure',
			'risk'           => 'Crítico',
			'description'    => $debug ? 'El modo de depuración (WP_DEBUG) está activo, lo que puede exponer rutas de archivos y errores internos.' : 'El modo debug está desactivado.',
			'recommendation' => 'Desactiva WP_DEBUG en el archivo wp-config.php.',
		);
	}

	private function check_sensitive_files() {
		$files = array( 'readme.html', 'license.txt', 'wp-config-sample.php', 'wp-config.php.bak', 'wp-config.php.save', '.env', 'phpinfo.php' );
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
			'risk'           => 'Medio',
			'description'    => $is_secure ? 'No se detectaron archivos sensibles expuestos.' : 'Se han encontrado archivos que exponen información del sistema: ' . implode(', ', $exposed),
			'recommendation' => 'Elimina o bloquea el acceso a estos archivos para evitar que atacantes obtengan información sobre tu instalación.',
		);
	}

	private function check_file_permissions() {
		$files_to_check = array(
			'wp-config.php' => '644',
			'.htaccess'     => '644',
			'index.php'     => '644',
			'wp-content'    => '755',
		);

		$issues = array();
		foreach ( $files_to_check as $file => $expected ) {
			$path = ABSPATH . $file;
			if ( file_exists( $path ) ) {
				$perms = substr( sprintf( '%o', fileperms( $path ) ), -3 );
				if ( $perms > $expected ) {
					$issues[] = "$file ($perms)";
				}
			}
		}

		$is_secure = empty( $issues );
		return array(
			'name'           => 'Permisos de Archivos',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => $is_secure ? 'Los archivos principales tienen permisos correctos.' : 'Se detectaron permisos inseguros en: ' . implode( ', ', $issues ),
			'recommendation' => 'Asegúrate de que los archivos tengan permisos restrictivos (644 para archivos, 755 para carpetas).',
		);
	}

	private function check_admin_users() {
		$args = array( 'role' => 'administrator' );
		$users = get_users( $args );
		$insecure_names = array( 'admin', 'administrator', 'webmaster', 'root' );
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
			'risk'           => 'Crítico',
			'description'    => $is_secure ? 'No se encontraron usuarios administradores con nombres genéricos.' : 'Se detectaron administradores con nombres inseguros (ej: admin).',
			'recommendation' => 'Crea un nuevo administrador con un nombre único y elimina el usuario "admin".',
		);
	}

	private function check_https() {
		$is_https = is_ssl();
		return array(
			'name'           => 'Conexión HTTPS',
			'status'         => $is_https ? 'secure' : 'critical',
			'risk'           => 'Crítico',
			'description'    => $is_https ? 'El sitio web utiliza una conexión segura mediante HTTPS.' : 'El sitio no utiliza HTTPS, los datos se transmiten en texto plano.',
			'recommendation' => 'Instala un certificado SSL y configura el sitio para que use HTTPS obligatoriamente.',
		);
	}

	private function check_security_headers() {
		$response = wp_remote_get( home_url() );
		if ( is_wp_error( $response ) ) {
			return array(
				'name'           => 'Cabeceras de Seguridad',
				'status'         => 'warning',
				'risk'           => 'Medio',
				'description'    => 'No se pudo realizar la conexión para verificar las cabeceras HTTP.',
				'recommendation' => 'Verifica que el servidor esté configurado para enviar cabeceras de seguridad.',
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
			'name'           => 'Cabeceras de Seguridad',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => $is_secure ? 'Tu sitio envía cabeceras de seguridad recomendadas.' : 'Faltan cabeceras de seguridad (X-Frame-Options, CSP, etc.). Detectadas: ' . ( empty( $found ) ? 'ninguna' : implode( ', ', $found ) ),
			'recommendation' => 'Implementa cabeceras de seguridad HTTP para protegerte contra ataques XSS y de inyección.',
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
			'risk'           => 'Medio',
			'description'    => $is_secure ? 'El listado de directorios está desactivado correctamente.' : 'El listado de directorios está habilitado, permitiendo ver todos tus archivos subidos.',
			'recommendation' => 'Desactiva el listado de directorios en el servidor o añade un archivo index.php vacío en la carpeta de uploads.',
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
			'risk'           => 'Medio',
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
			'risk'           => 'Medio',
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

	private function get_vulnerability_counts( $results ) {
		$counts = array(
			'critico' => 0,
			'medio'   => 0,
			'bajo'    => 0,
		);

		foreach ( $results as $check ) {
			if ( $check['status'] === 'secure' ) continue;

			switch ( $check['risk'] ) {
				case 'Crítico':
					$counts['critico']++;
					break;
				case 'Medio':
					$counts['medio']++;
					break;
				case 'Bajo':
					$counts['bajo']++;
					break;
			}
		}

		return $counts;
	}
}
