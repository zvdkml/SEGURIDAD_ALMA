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
	 * Get plugin vulnerabilities from an external API.
	 *
	 * @return array|WP_Error
	 */
	public function get_plugin_vulnerabilities() {
		// In a real scenario, this would be a dedicated security API endpoint
		// For now, we use the configured endpoint or a default if available
		$endpoint = get_option( 'alma_security_api_endpoint' );

		if ( ! $endpoint ) {
			// Use a default endpoint if none is configured
			$endpoint = 'https://api.alma-security.com/v1/';
		}

		// Append vulnerabilities path if it's the base endpoint
		$vulnerabilities_url = trailingslashit( $endpoint ) . 'vulnerabilities';

		$api_key = get_option( 'alma_security_api_key' );

		$response = wp_remote_get( $vulnerabilities_url, array(
			'timeout' => 15,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $api_key
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new WP_Error( 'api_error', 'Error en la API: ' . $status_code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_json', 'Respuesta de API inválida (JSON error).' );
		}

		return $data;
	}
}
