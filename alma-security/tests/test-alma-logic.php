<?php

// Mocking WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

// Check if functions already exist before defining them (to avoid conflicts if run in certain envs)
if (!function_exists('get_site_transient')) {
    function get_site_transient($transient) {
        if ($transient === 'update_core') {
            $mock = new stdClass();
            $mock->updates = array();
            return $mock;
        }
        if ($transient === 'update_plugins' || $transient === 'update_themes') {
            $mock = new stdClass();
            $mock->response = array();
            return $mock;
        }
        return false;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        return $value;
    }
}

// Note: defined() is a language construct, cannot be redefined.
// Instead we will mock how WP_DEBUG is handled in the scanner if possible.

if (!function_exists('file_exists')) {
    function file_exists($path) {
        return false;
    }
}

if (!function_exists('fileperms')) {
    function fileperms($path) {
        // Mocking a safe return for wp-config.php test
        return 0644;
    }
}

if (!function_exists('get_users')) {
    function get_users($args) {
        return array();
    }
}

if (!function_exists('wp_get_themes')) {
    function wp_get_themes() {
        return array();
    }
}

if (!function_exists('wp_get_theme')) {
    class Mock_Theme {
        public function get_stylesheet() { return 'twentytwentyfour'; }
        public function get($prop) { return 'Mock'; }
        public function get_stylesheet_directory() { return __DIR__; }
    }
    function wp_get_theme() {
        return new Mock_Theme();
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return __DIR__;
    }
}

class Mock_DB {
    public $prefix = 'wp_';
}
global $wpdb;
$wpdb = new Mock_DB();

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', __DIR__);
}

if (!function_exists('is_ssl')) {
    function is_ssl() {
        return true;
    }
}

if (!function_exists('home_url')) {
    function home_url() {
        return 'http://example.com';
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url) {
        return new WP_Error('mock', 'Mocked remote get');
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return array('baseurl' => 'http://example.com/wp-content/uploads');
    }
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        if ($name === 'active_plugins') return array();
        return $default;
    }
}

if (!function_exists('get_plugins')) {
    function get_plugins() {
        return array();
    }
}

class WP_Error {
    public function __construct($code, $message) {}
    public function get_error_message() { return "Mock error"; }
}

if (!function_exists('get_transient')) {
    function get_transient($name) { return false; }
}
if (!function_exists('set_transient')) {
    function set_transient($name, $val, $exp) { return true; }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($res) { return 200; }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($res) { return "{}"; }
}
if (!function_exists('sanitize_title')) {
    function sanitize_title($t) { return $t; }
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

require_once __DIR__ . '/../includes/class-alma-api.php';
require_once __DIR__ . '/../includes/class-alma-scanner.php';

$scanner = new Alma_Scanner();
$results = $scanner->run_scan();

echo "Testing Scanner Logic...\n";
echo "Score: " . $results['score'] . "%\n";
echo "Level: " . $results['level'] . "\n";

if ($results['score'] > 0) {
    echo "SUCCESS: Scanner returned a score.\n";
} else {
    echo "FAILED: Scanner returned 0 or error.\n";
    exit(1);
}

if (isset($results['counts'])) {
    echo "Vulnerability Counts: Critical=" . $results['counts']['critical'] . ", Warning=" . $results['counts']['warning'] . ", Secure=" . $results['counts']['secure'] . "\n";
} else {
    echo "FAILED: Vulnerability counts missing.\n";
    exit(1);
}

foreach ($results['vulnerabilities'] as $key => $val) {
    echo "Check $key: " . $val['status'] . "\n";
}

echo "Testing complete.\n";
