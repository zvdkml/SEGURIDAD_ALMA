<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Data_Collector {

	public function get_site_info() {
		return array(
			'domain'         => home_url(),
			'wp_version'     => get_bloginfo( 'version' ),
			'active_plugins' => $this->get_active_plugins_list(),
			'php_version'    => PHP_VERSION,
			'timestamp'      => date( 'Y-m-d H:i:s' ),
		);
	}

	private function get_active_plugins_list() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active_plugins = get_option( 'active_plugins' );
		$all_plugins    = get_plugins();
		$active_list    = array();

		foreach ( $active_plugins as $plugin_path ) {
			if ( isset( $all_plugins[ $plugin_path ] ) ) {
				$active_list[] = $all_plugins[ $plugin_path ]['Name'] . ' (' . $all_plugins[ $plugin_path ]['Version'] . ')';
			}
		}

		return $active_list;
	}
}
