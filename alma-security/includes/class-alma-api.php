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
			'critical_count' => isset( $data['counts']['critical'] ) ? $data['counts']['critical'] : 0,
			'warning_count'  => isset( $data['counts']['warning'] ) ? $data['counts']['warning'] : 0,
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

	/**
	 * Get vulnerability information from WPVulnerability API.
	 *
	 * @param string $type Type of vulnerability to check: 'core', 'plugin', or 'theme'.
	 * @param string $slug Slug for 'plugin' or 'theme' types. Empty for 'core'.
	 * @return array Decoded JSON data or empty array on failure.
	 */
	public function get_vulnerability( $type, $slug = '' ) {
		$base_url = 'https://www.wpvulnerability.net/';

		switch ( $type ) {
			case 'core':
				global $wp_version;
				$url = $base_url . 'core/' . $wp_version . '/';
				break;
			case 'plugin':
				if ( empty( $slug ) ) return array();
				$url = $base_url . 'plugin/' . sanitize_title( $slug ) . '/';
				break;
			case 'theme':
				if ( empty( $slug ) ) return array();
				$url = $base_url . 'theme/' . sanitize_title( $slug ) . '/';
				break;
			default:
				return array();
		}

		$response = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( "[Alma Security API] Error al conectar con WPVulnerability ($type $slug): " . $response->get_error_message() );
			return array();
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( "[Alma Security API] Error al decodificar JSON de WPVulnerability ($type $slug)" );
			return array();
		}

		return is_array( $data ) ? $data : array();
	}
}
