<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Scanner {

	public function run_scan( $type = 'all' ) {
		$all_checks = array(
			'wp'      => array( 'wp_update', 'xmlrpc', 'debug_mode', 'sensitive_files', 'file_permissions', 'directory_listing', 'https' ),
			'plugins' => array( 'plugins_detailed' ),
			'themes'  => array( 'themes_detailed' ),
			'server'  => array( 'php_version', 'file_permissions', 'https', 'security_headers', 'directory_listing', 'sensitive_files', 'server_config' ),
			'users'   => array( 'admin_users', 'admin_count', 'password_policy', 'login_attempts' ),
			'malware' => array( 'malware_scan' ),
		);

		$results = array();

		if ( $type === 'all' ) {
			$checks_to_run = array(
				'wp_update', 'xmlrpc', 'debug_mode',
				'plugins_detailed',
				'themes_detailed',
				'php_version', 'file_permissions', 'https', 'security_headers', 'directory_listing', 'sensitive_files', 'server_config',
				'admin_users', 'admin_count', 'password_policy', 'login_attempts',
				'malware_scan'
			);
		} else {
			$checks_to_run = isset( $all_checks[ $type ] ) ? $all_checks[ $type ] : array();
		}

		foreach ( $checks_to_run as $check_id ) {
			$method = 'check_' . $check_id;
			if ( method_exists( $this, $method ) ) {
				$results[ $check_id ] = $this->$method();
			}
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

	private function check_themes_detailed() {
		$all_themes = wp_get_themes();
		$active_theme = wp_get_theme();
		$update_themes = get_site_transient( 'update_themes' );

		$theme_results = array();

		foreach ( $all_themes as $slug => $theme ) {
			$is_active = ( $slug === $active_theme->get_stylesheet() );
			$has_update = isset( $update_themes->response[ $slug ] );

			// Check for insecure files in theme folder
			$theme_path = $theme->get_stylesheet_directory();
			$insecure_files_to_check = array( '.env', 'wp-config.php', 'config.php', 'sql.sql', 'db.sql', 'error_log' );
			$found_insecure = array();
			foreach ( $insecure_files_to_check as $f ) {
				if ( file_exists( $theme_path . '/' . $f ) ) {
					$found_insecure[] = $f;
				}
			}

			$status = 'secure';
			$risk = 'Bajo';

			if ( $has_update ) {
				$status = 'warning';
				$risk = 'Medio';
			}

			if ( ! $is_active ) {
				$status = 'warning';
				$risk = 'Bajo';
			}

			if ( ! empty( $found_insecure ) ) {
				$status = 'critical';
				$risk = 'Crítico';
			}

			$theme_results[ $slug ] = array(
				'name'      => $theme->get( 'Name' ),
				'version'   => $theme->get( 'Version' ),
				'active'    => $is_active,
				'update'    => $has_update,
				'insecure'  => $found_insecure,
				'status'    => $status,
				'risk'      => $risk,
				'last_upd'  => 'Reciente', // Simulated
			);
		}

		return array(
			'name'           => 'Análisis Detallado de Temas',
			'status'         => 'secure',
			'risk'           => 'Bajo',
			'is_detailed'    => true,
			'data'           => $theme_results,
			'description'    => 'Se han analizado ' . count( $all_themes ) . ' temas instalados.',
			'recommendation' => 'Mantén tus temas actualizados y elimina los que no utilices para reducir la superficie de ataque.',
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
			'name'           => 'Nombre de Usuario Inseguro',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Crítico',
			'description'    => $is_secure ? 'No se encontraron usuarios administradores con nombres genéricos.' : 'Se detectaron administradores con nombres inseguros (ej: ' . implode(', ', $found_insecure) . ').',
			'recommendation' => 'Evita usar "admin" o nombres similares ya que son los primeros objetivos en ataques de fuerza bruta.',
		);
	}

	private function check_admin_count() {
		$args = array( 'role' => 'administrator' );
		$users = get_users( $args );
		$count = count( $users );
		$is_secure = ( $count <= 2 );

		return array(
			'name'           => 'Cantidad de Administradores',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => "Tienes $count usuarios con rol administrador.",
			'recommendation' => 'Mantén el número de administradores al mínimo necesario para reducir riesgos internos.',
		);
	}

	private function check_password_policy() {
		$protection_plugins = array(
			'wp-strong-password-policies/wp-strong-password-policies.php',
			'force-strong-passwords/force-strong-passwords.php',
			'password-policy-manager/password-policy-manager.php',
		);

		$active_plugins = get_option( 'active_plugins', array() );
		$is_protected = false;
		foreach ( $protection_plugins as $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				$is_protected = true;
				break;
			}
		}

		return array(
			'name'           => 'Política de Contraseñas',
			'status'         => $is_protected ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => $is_protected ? 'Se detectó una política de contraseñas fuertes activa.' : 'No se detectó un sistema que obligue el uso de contraseñas robustas.',
			'recommendation' => 'Instala un plugin para obligar a los usuarios a usar contraseñas complejas.',
		);
	}

	private function check_php_version() {
		$version = PHP_VERSION;
		$is_secure = version_compare( $version, '8.1', '>=' );
		return array(
			'name'           => 'Versión de PHP',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => "Tu servidor ejecuta PHP $version. " . ( $is_secure ? 'Es una versión moderna y segura.' : 'Es una versión antigua que podría no recibir parches de seguridad.' ),
			'recommendation' => 'Actualiza PHP a la versión 8.1 o superior en tu panel de hosting.',
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

	private function check_server_config() {
		$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'Desconocido';
		$display_errors = ini_get( 'display_errors' );
		$is_secure = ( $display_errors === '0' || strtolower( $display_errors ) === 'off' );

		return array(
			'name'           => 'Configuración del Servidor',
			'status'         => $is_secure ? 'secure' : 'warning',
			'risk'           => 'Medio',
			'description'    => "Servidor: $server. Visualización de errores PHP: " . ( $is_secure ? 'Desactivado (Correcto)' : 'Activado (Riesgo)' ),
			'recommendation' => 'Asegúrate de que "display_errors" esté desactivado en producción para no exponer rutas del servidor.',
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

	private function check_plugins_detailed() {
		if ( ! function_exists( 'get_plugins' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$active_plugins = get_option( 'active_plugins', array() );
		$update_plugins = get_site_transient( 'update_plugins' );

		$plugin_results = array();

		foreach ( $all_plugins as $file => $data ) {
			$is_active = in_array( $file, $active_plugins );
			$has_update = isset( $update_plugins->response[ $file ] );

			$status = 'secure';
			$risk = 'Bajo';

			if ( $has_update ) {
				$status = 'warning';
				$risk = 'Medio';
			}

			if ( ! $is_active ) {
				$status = 'warning';
				$risk = 'Bajo';
			}

			// Simulating vulnerability check
			$is_vulnerable = false; // In real app, check against a CVE DB
			if ( $is_vulnerable ) {
				$status = 'critical';
				$risk = 'Crítico';
			}

			$plugin_results[ $file ] = array(
				'name'      => $data['Name'],
				'version'   => $data['Version'],
				'active'    => $is_active,
				'update'    => $has_update,
				'status'    => $status,
				'risk'      => $risk,
				'last_upd'  => 'Reciente', // Simulated
			);
		}

		return array(
			'name'           => 'Análisis Detallado de Plugins',
			'status'         => 'secure',
			'risk'           => 'Bajo',
			'is_detailed'    => true,
			'data'           => $plugin_results,
			'description'    => 'Se han analizado ' . count( $all_plugins ) . ' plugins instalados.',
			'recommendation' => 'Mantén tus plugins actualizados y elimina los que no utilices.',
		);
	}

	private function check_malware_scan() {
		$suspicious_patterns = array(
			'base64_decode\s*\(' => 'Uso potencial de código ofuscado.',
			'eval\s*\('          => 'Ejecución de código arbitrario detectada.',
			'shell_exec\s*\('    => 'Ejecución de comandos del sistema.',
			'gzinflate\s*\('     => 'Descompresión de código (común en malware).',
			'str_rot13\s*\('     => 'Ofuscación de cadenas detectada.',
		);

		$directories_to_scan = array(
			WP_CONTENT_DIR . '/uploads',
			get_stylesheet_directory(),
		);

		$findings = array();

		foreach ( $directories_to_scan as $dir ) {
			if ( ! is_dir( $dir ) ) continue;

			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
			foreach ( $files as $file ) {
				if ( $file->isDir() || $file->getExtension() !== 'php' ) continue;

				$content = file_get_contents( $file->getPathname() );
				if ( ! $content ) continue;

				foreach ( $suspicious_patterns as $pattern => $desc ) {
					if ( preg_match( '/' . $pattern . '/i', $content ) ) {
						$findings[] = array(
							'file' => str_replace( ABSPATH, '', $file->getPathname() ),
							'issue' => $desc,
							'pattern' => $pattern
						);
					}
				}

				if ( count($findings) > 50 ) break; // Limit results
			}
			if ( count($findings) > 50 ) break;
		}

		$is_secure = empty( $findings );

		return array(
			'name'           => 'Escaneo de Malware',
			'status'         => $is_secure ? 'secure' : 'critical',
			'risk'           => 'Crítico',
			'is_malware'     => true,
			'findings'       => $findings,
			'description'    => $is_secure ? 'No se detectaron patrones de código malicioso en las carpetas críticas.' : 'Se han encontrado archivos con código sospechoso.',
			'recommendation' => $is_secure ? 'Realiza escaneos periódicos para mantener la integridad.' : 'Revisa manualmente los archivos listados y elimina cualquier código no reconocido.',
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
