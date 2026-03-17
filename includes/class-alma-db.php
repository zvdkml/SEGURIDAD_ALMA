<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_DB {

	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Scans Table
		$scans_table = $wpdb->prefix . 'alma_scans';
		$sql_scans = "CREATE TABLE $scans_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			check_id varchar(100) NOT NULL,
			check_name varchar(255) NOT NULL,
			status varchar(50) NOT NULL,
			result text NOT NULL,
			recommendation text NOT NULL,
			risk_level varchar(50) NOT NULL,
			last_scan_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY check_id (check_id)
		) $charset_collate;";

		// Scan History Table
		$history_table = $wpdb->prefix . 'alma_scan_history';
		$sql_history = "CREATE TABLE $history_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			check_id varchar(100) NOT NULL,
			check_name varchar(255) NOT NULL,
			status varchar(50) NOT NULL,
			result text NOT NULL,
			scanned_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Users Table
		$users_table = $wpdb->prefix . 'alma_users';
		$sql_users = "CREATE TABLE $users_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			username varchar(100) NOT NULL,
			password varchar(255) NOT NULL,
			role varchar(50) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY username (username)
		) $charset_collate;";

		// Score Evolution Table
		$score_history_table = $wpdb->prefix . 'alma_score_history';
		$sql_score_history = "CREATE TABLE $score_history_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			score int NOT NULL,
			scanned_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql_scans );
			dbDelta( $sql_history );
			dbDelta( $sql_users );
			dbDelta( $sql_score_history );
		}
	}

	public function save_check_result( $id, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_scans';
		$history_table = $wpdb->prefix . 'alma_scan_history';

		$wpdb->replace(
			$table_name,
			array(
				'check_id'       => $id,
				'check_name'     => $data['name'],
				'status'         => $data['status'],
				'result'         => $data['description'],
				'recommendation' => $data['recommendation'],
				'risk_level'     => isset($data['risk']) ? $data['risk'] : 'Bajo',
				'last_scan_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Log into History
		$wpdb->insert(
			$history_table,
			array(
				'check_id'   => $id,
				'check_name' => $data['name'],
				'status'     => $data['status'],
				'result'     => $data['description'],
				'scanned_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function get_check_history( $check_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_scan_history';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE check_id = %s ORDER BY scanned_at DESC LIMIT 10", $check_id ), ARRAY_A );
	}

	public function get_all_results() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_scans';
		return $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
	}

	public function get_result( $check_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_scans';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE check_id = %s", $check_id ), ARRAY_A );
	}

	public function save_score_history( $score ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_score_history';
		$wpdb->insert(
			$table_name,
			array(
				'score'      => $score,
				'scanned_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s' )
		);
	}

	public function get_score_history() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'alma_score_history';
		return $wpdb->get_results( "SELECT score, scanned_at FROM $table_name ORDER BY scanned_at ASC LIMIT 50", ARRAY_A );
	}
}
