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
	}

	public function mitigate_login_attacks() {
		// Simple delay to slow down brute force
		sleep( 2 );
	}

	public function activate() {
		Alma_DB::create_tables();
		if ( class_exists( 'Alma_Cron' ) ) {
			Alma_Cron::activate();
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

		if ( $relative_path === '/security/fix' || $relative_path === '/security/issues' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Acceso denegado. No tienes permisos para ver esta página.' );
			}

			if ( $relative_path === '/security/fix' ) {
				include ALMA_SECURITY_PATH . 'templates/fix.php';
			} else {
				include ALMA_SECURITY_PATH . 'templates/issues.php';
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
