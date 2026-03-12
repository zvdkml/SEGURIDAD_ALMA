<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_History {

	private $option_name = 'alma_security_history';

	public function save_scan( $data ) {
		$history = get_option( $this->option_name, array() );

		// Add new scan at the beginning
		array_unshift( $history, $data );

		// Keep only last 20 scans
		$history = array_slice( $history, 0, 20 );

		update_option( $this->option_name, $history );
	}

	public function get_history() {
		return get_option( $this->option_name, array() );
	}

	public function get_latest_scan() {
		$history = $this->get_history();
		return ! empty( $history ) ? $history[0] : null;
	}
}
