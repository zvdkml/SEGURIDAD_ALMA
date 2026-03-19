<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Auth {

	public static function get_roles() {
		return array(
			'admin'  => 'Acceso Total (Escanear todo, Eliminar)',
			'user'   => 'Editor de Seguridad (Escanear individual)',
			'viewer' => 'Solo Lectura (Ver resultados)',
		);
	}

	public function create_user( $username, $password, $role ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_users';

		return $wpdb->insert(
			$table_name,
			array(
				'username' => sanitize_user( $username ),
				'password' => wp_hash_password( $password ),
				'role'     => sanitize_text_field( $role ),
			),
			array( '%s', '%s', '%s' )
		);
	}

	public function delete_user( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_users';
		return $wpdb->delete( $table_name, array( 'id' => intval( $user_id ) ), array( '%d' ) );
	}

	public function get_users() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_users';
		return $wpdb->get_results( "SELECT id, username, role, created_at FROM $table_name", ARRAY_A );
	}

	/**
	 * For the purpose of this implementation, we will map the current WP user to an Alma Role.
	 * If no specific mapping exists in our table, we fallback to WP capabilities.
	 */
	public function get_current_user_role() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$wp_user = wp_get_current_user();

		// Check our custom table for specific overrides
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_users';
		$alma_user = $wpdb->get_row( $wpdb->prepare( "SELECT role FROM $table_name WHERE username = %s", $wp_user->user_login ), ARRAY_A );

		if ( $alma_user ) {
			return $alma_user['role'];
		}

		// Fallback to WP roles if no specific Alma override exists
		if ( current_user_can( 'manage_options' ) ) {
			return 'admin';
		}

		return 'viewer';
	}

	public function can( $action ) {
		$role = $this->get_current_user_role();

		switch ( $action ) {
			case 'full_scan':
			case 'delete_data':
			case 'manage_users':
				return ( $role === 'admin' );

			case 'individual_scan':
				return ( $role === 'admin' || $role === 'user' );

			case 'view_results':
				return ( $role === 'admin' || $role === 'user' || $role === 'viewer' );

			default:
				return false;
		}
	}
}
