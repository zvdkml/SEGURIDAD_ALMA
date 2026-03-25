<?php
/**
 * Plugin Name: Alma Security
 * Plugin URI: https://github.com/zvdkml/alma-security
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
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-db.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-auth.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-scanner.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-admin.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-history.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-api.php';
		require_once ALMA_SECURITY_PATH . 'includes/class-alma-shortcode.php';

		// Monitoring Modules
		require_once ALMA_SECURITY_PATH . 'includes/data-collector.php';
		require_once ALMA_SECURITY_PATH . 'includes/api-sender.php';
		require_once ALMA_SECURITY_PATH . 'includes/cron.php';
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'create_security_page' ) );
		add_action( 'template_redirect', array( $this, 'handle_fix_page' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	public function init() {
		if ( class_exists( 'Alma_Cron' ) ) {
			new Alma_Cron();
		}

		// Apply security mitigations from options
		if ( get_option( 'alma_fix_xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false', 999 );
		}
		if ( get_option( 'alma_fix_directory_listing' ) ) {
			// This is also handled by creating index.php, but this is a secondary layer
			add_filter( 'restricted_extentions', function( $exts ) {
				return array_merge( (array) $exts, array( 'php', 'sql' ) );
			} );
		}
		if ( get_option( 'alma_fix_debug_mode' ) ) {
			@ini_set( 'display_errors', '0' );
			@error_reporting( 0 );
			if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
				define( 'WP_DEBUG_DISPLAY', false );
			}
		}
		if ( get_option( 'alma_fix_login_attempts' ) ) {
			add_action( 'wp_login_failed', array( $this, 'mitigate_login_attacks' ) );
		}
		if ( get_option( 'alma_fix_hidden_login' ) ) {
			// Acknowledgement of hidden login mitigation
		}
		if ( get_option( 'alma_fix_firewall_detect' ) ) {
			// Acknowledgement of firewall mitigation
		}
		if ( get_option( 'alma_fix_security_headers' ) ) {
			add_action( 'send_headers', array( $this, 'send_security_headers' ) );
		}
		if ( get_option( 'alma_fix_backup_detect' ) ) {
			// Acknowledgement of backup mitigation
		}
		if ( get_option( 'alma_fix_admin_count' ) ) {
			// Acknowledgement of admin count mitigation
		}
		if ( get_option( 'alma_fix_plugins_detailed' ) ) {
			// Acknowledgement of plugin mitigation
		}
		if ( get_option( 'alma_fix_plugins_update' ) ) {
			// Acknowledgement of plugins update mitigation
		}
		if ( get_option( 'alma_fix_plugin_vulnerabilities' ) ) {
			// Acknowledgement of plugin vulnerabilities mitigation
		}
	}

	public function send_security_headers() {
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'X-XSS-Protection: 1; mode=block' );
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}
	}

	public function mitigate_login_attacks() {
		// Simple delay to slow down brute force
		sleep( 2 );
	}

	public function activate() {
		Alma_DB::create_tables();
		$this->add_capabilities();
		if ( class_exists( 'Alma_Cron' ) ) {
			Alma_Cron::activate();
		}
	}

	private function add_capabilities() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'alma_security_view' );
			$admin->add_cap( 'alma_security_scan' );
			$admin->add_cap( 'alma_security_fix' );
			$admin->add_cap( 'alma_security_admin' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'alma_security_view' );
			$editor->add_cap( 'alma_security_scan' );
		}

		$author = get_role( 'author' );
		if ( $author ) {
			$author->add_cap( 'alma_security_view' );
		}
	}

	public function deactivate() {
		if ( class_exists( 'Alma_Cron' ) ) {
			Alma_Cron::deactivate();
		}
	}

	public function handle_fix_page() {
		$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$site_path = parse_url( home_url(), PHP_URL_PATH );

		// Extract relative path from site root
		$relative_path = $path;
		if ( ! empty( $site_path ) && strpos( $path, $site_path ) === 0 ) {
			$relative_path = substr( $path, strlen( $site_path ) );
		}
		$relative_path = '/' . ltrim( untrailingslashit( (string) $relative_path ), '/' );

		if ( $relative_path === '/security' || $relative_path === '/security/fix' || $relative_path === '/security/issues' ) {
			if ( ! current_user_can( 'alma_security_view' ) && ! current_user_can( 'alma_security_admin' ) ) {
				wp_die( 'Acceso denegado. No tienes permisos para ver esta página.' );
			}

			if ( $relative_path === '/security/fix' ) {
				include ALMA_SECURITY_PATH . 'templates/fix.php';
			} elseif ( $relative_path === '/security/issues' ) {
				include ALMA_SECURITY_PATH . 'templates/issues.php';
			} else {
				$sections = array(
					'wp'       => array(
						'title' => 'WordPress Security',
						'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
						'color' => 'blue',
						'checks' => array('wp_update', 'debug_mode', 'xmlrpc', 'sensitive_files', 'server_config')
					),
					'plugins'  => array(
						'title' => 'Plugin Security',
						'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
						'color' => 'purple',
						'checks' => array('plugins_detailed', 'plugin_vulnerabilities')
					),
					'themes'   => array(
						'title' => 'Theme Security',
						'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z',
						'color' => 'pink',
						'checks' => array('themes_detailed')
					),
					'server'   => array(
						'title' => 'Server Security',
						'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2',
						'color' => 'green',
						'checks' => array('php_version', 'https', 'file_permissions', 'directory_listing')
					),
					'users'    => array(
						'title' => 'User Security',
						'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z',
						'color' => 'orange',
						'checks' => array('admin_users', 'admin_count', 'login_attempts')
					),
					'malware'  => array(
						'title' => 'Malware Scan',
						'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
						'color' => 'red',
						'checks' => array('malware_scan')
					),
					'login'    => array(
						'title' => 'Login Security Scan',
						'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
						'color' => 'indigo',
						'checks' => array('login_attempts', 'hidden_login')
					),
					'db'       => array(
						'title' => 'Database Security Scan',
						'icon' => 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zm4-1h8',
						'color' => 'yellow',
						'checks' => array('db_prefix', 'db_remote')
					),
					'file_int' => array(
						'title' => 'File Integrity Scan',
						'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
						'color' => 'teal',
						'checks' => array('core_integrity')
					),
					'firewall' => array(
						'title' => 'Firewall Status',
						'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
						'color' => 'cyan',
						'checks' => array('firewall_detect')
					),
					'headers'  => array(
						'title' => 'Security Headers Scan',
						'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
						'color' => 'blue',
						'checks' => array('security_headers')
					),
					'backup'   => array(
						'title' => 'Backup Security',
						'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2',
						'color' => 'gray',
						'checks' => array('backup_detect')
					),
					'updates'  => array(
						'title' => 'Update Monitor',
						'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
						'color' => 'blue',
						'checks' => array('wp_update', 'plugins_update', 'themes_update')
					),
				);
				include ALMA_SECURITY_PATH . 'templates/dashboard.php';
			}
			exit;
		}
	}

	public function create_security_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_path = 'security';
		$page_exists = get_page_by_path( $page_path );

		if ( ! $page_exists ) {
			$page_id = wp_insert_post( array(
				'post_title'   => 'Security Dashboard',
				'post_name'    => $page_path,
				'post_content' => '[alma_security_dashboard]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			) );
		}
	}
}

Seguridad_Alma::get_instance();
