<?php

// Mock WordPress environment
define('ABSPATH', './');
function home_url() { return 'http://localhost'; }
function get_bloginfo($key) { return '6.4.1'; }
function get_option($key, $default = '') {
    if ($key === 'active_plugins') return array('akismet/akismet.php');
    if ($key === 'alma_security_monitor_endpoint') return 'http://localhost:3000/site-data';
    if ($key === 'alma_security_api_key') return 'ALMA-SECURITY-SECRET-KEY';
    return $default;
}
function get_plugins() {
    return array(
        'akismet/akismet.php' => array('Name' => 'Akismet Anti-Spam', 'Version' => '5.3')
    );
}

// Mock wp_remote_post
function wp_remote_post($url, $args) {
    echo "Sending data to: $url\n";
    echo "Payload: " . $args['body'] . "\n";
    echo "Headers: " . json_encode($args['headers']) . "\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $args['body']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: ' . $args['headers']['Authorization']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array('response' => array('code' => $code), 'body' => $response);
}

function wp_remote_retrieve_response_code($response) {
    return isset($response['response']['code']) ? $response['response']['code'] : 500;
}

function is_wp_error($response) { return false; }

require_once 'includes/data-collector.php';
require_once 'includes/api-sender.php';

echo "Testing Monitoring Integration...\n";
$collector = new Alma_Data_Collector();
$data = $collector->get_site_info();

$sender = new Alma_API_Sender();
$success = $sender->send_data($data);

if ($success) {
    echo "SUCCESS: Data sent to monitoring server.\n";
} else {
    echo "ERROR: Failed to send data.\n";
}
