<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Cron {

	public function __construct() {
		add_action( 'alma_hourly_monitoring_event', array( $this, 'do_monitoring_task' ) );
	}

	public function do_monitoring_task() {
		$collector = new Alma_Data_Collector();
		$data = $collector->get_site_info();

		$sender = new Alma_API_Sender();
		$sender->send_data( $data );
	}

	public static function activate() {
		if ( ! wp_next_scheduled( 'alma_hourly_monitoring_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'alma_hourly_monitoring_event' );
		}
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'alma_hourly_monitoring_event' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'alma_hourly_monitoring_event' );
		}
	}
}
