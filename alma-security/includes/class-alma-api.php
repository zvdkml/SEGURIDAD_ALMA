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
		error_log( "[Alma Security API] Entrando en get_vulnerability: $type ($slug)" );
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

		error_log( "[Alma Security API] Consultando URL: $url" );
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
		error_log( "[Alma Security API] Código de respuesta: $status_code" );

		if ( $status_code !== 200 ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		error_log( "[Alma Security API] Contenido recibido (primeros 500 chars): " . substr( $body, 0, 500 ) );

		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( "[Alma Security API] Error al decodificar JSON de WPVulnerability ($type $slug)" );
			return array();
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Consumes the WPVulnerability API and returns a list of vulnerable plugins.
	 *
	 * @return array List of structured vulnerability data.
	 */
	public function get_plugin_vulnerabilities_list() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$vulnerable_list = array();

		// Use transients to avoid API rate limits and slow loads
		$cache_key = 'alma_vuln_full_list';
		$cached = get_transient($cache_key);
		if ($cached !== false) return $cached;

		foreach ( $all_plugins as $path => $data ) {
			$slug = dirname( $path );
			if ( $slug === '.' ) continue;

			$response = $this->get_vulnerability( 'plugin', $slug );

			if ( is_array( $response ) && isset( $response['data']['vulnerability'] ) && is_array( $response['data']['vulnerability'] ) ) {
				foreach ( $response['data']['vulnerability'] as $v ) {
					$max_v = isset( $v['operator']['max_version'] ) ? $v['operator']['max_version'] : '';
					$max_op = isset( $v['operator']['max_operator'] ) ? $v['operator']['max_operator'] : 'le';
					$unfixed = isset( $v['operator']['unfixed'] ) ? (bool)$v['operator']['unfixed'] : false;

					// Map API operators to version_compare operators
					$comp_op = ( $max_op === 'le' ) ? '<=' : ( ( $max_op === 'lt' ) ? '<' : '<=' );

					$is_vulnerable = false;
					if ( $unfixed ) {
						$is_vulnerable = true;
					} elseif ( ! empty( $max_v ) ) {
						$is_vulnerable = version_compare( $data['Version'], $max_v, $comp_op );
					}

					if ( $is_vulnerable ) {
						$vulnerable_list[] = array(
							'slug'        => $slug,
							'name'        => $data['Name'],
							'risk'        => isset( $v['impact']['cvss']['severity'] ) ? $this->map_severity_api( $v['impact']['cvss']['severity'] ) : 'Medio',
							'description' => ! empty( $v['name'] ) ? $v['name'] : 'Vulnerabilidad detectada',
							'installed'   => true,
							'version'     => $data['Version']
						);
						break; // Found one vulnerability for this plugin, move to next
					}
				}
			}
		}

		set_transient($cache_key, $vulnerable_list, 12 * HOUR_IN_SECONDS);
		return $vulnerable_list;
	}

	/**
	 * Internal map for severity levels.
	 *
	 * @param string $severity English severity from API.
	 * @return string Spanish severity for UI.
	 */
	private function map_severity_api( $severity ) {
		$severity = strtolower( $severity );
		switch ( $severity ) {
			case 'critical':
				return 'Crítico';
			case 'high':
				return 'Alto';
			case 'medium':
				return 'Medio';
			case 'low':
				return 'Bajo';
			default:
				return 'Medio';
		}
	}
}
