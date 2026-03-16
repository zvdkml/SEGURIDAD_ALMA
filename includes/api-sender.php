<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_API_Sender {

	public function send_data( $data ) {
		$endpoint = get_option( 'alma_security_monitor_endpoint', 'https://api.midominio.com/site-data' );
		$api_key  = get_option( 'alma_security_api_key' );

		$response = wp_remote_post( $endpoint, array(
			'body'    => json_encode( $data ),
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return ( $code >= 200 && $code < 300 );
	}
}
