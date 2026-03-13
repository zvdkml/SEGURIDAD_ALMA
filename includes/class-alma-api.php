<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_API {

	public function send_to_external_api( $data ) {
		$endpoint = get_option( 'alma_security_api_endpoint' );
		if ( ! $endpoint ) {
			return false;
		}

		// Calculate secure checks count
		$secure_count = 0;
		if ( isset( $data['vulnerabilities'] ) ) {
			foreach ( $data['vulnerabilities'] as $v ) {
				if ( isset($v['status']) && $v['status'] === 'secure' ) {
					$secure_count++;
				}
			}
		}

		$payload = array(
			'domain'         => home_url(),
			'scan_date'      => date( 'Y-m-d H:i:s', $data['timestamp'] ),
			'security_score' => $data['score'],
			'critical_count' => isset( $data['counts']['critico'] ) ? $data['counts']['critico'] : 0,
			'warning_count'  => isset( $data['counts']['medio'] ) ? $data['counts']['medio'] : 0,
			'secure_count'   => $secure_count,
		);

		$api_key = get_option( 'alma_security_api_key' );
		$response = wp_remote_post( $endpoint, array(
			'body'    => json_encode( $payload ),
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key
			),
		) );

		return ! is_wp_error( $response );
	}
}
