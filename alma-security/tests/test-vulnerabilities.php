<?php
/**
 * Test script for Alma Security Vulnerability matching.
 */

// Define ABSPATH and other constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', '/tmp/wp-content');
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

// Mock WordPress functions
function get_option($option, $default = false) {
    if ($option === 'active_plugins') return array('akismet/akismet.php');
    return $default;
}
function get_site_transient($transient) { return false; }
function set_transient($transient, $value, $expiration = 0) { return true; }
function get_transient($transient) { return false; }
function sanitize_title($title) { return strtolower($title); }
function wp_remote_get($url, $args = array()) {
    // Mock successful response for Core API
    if (strpos($url, 'core') !== false) {
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'data' => array(
                    'vulnerability' => array(
                        array('name' => 'Mock Core Vulnerability', 'impact' => array('cvss' => array('severity' => 'critical')))
                    )
                )
            ))
        );
    }
    return new WP_Error('mock', 'Mock API');
}
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function is_wp_error($thing) { return $thing instanceof WP_Error; }
class WP_Error {
    private $msg;
    public function __construct($code, $message) { $this->msg = $message; }
    public function get_error_message() { return $this->msg; }
}
function home_url() { return 'http://example.com'; }
function wp_get_themes() {
    return array(
        'twentytwentyfour' => new class {
            public function get($key) { return '1.0'; }
        }
    );
}
function get_plugins() {
    return array(
        'akismet/akismet.php' => array(
            'Name' => 'Akismet Anti-Spam',
            'Version' => '5.0'
        )
    );
}

// Include the classes
require_once __DIR__ . '/../includes/class-alma-api.php';
require_once __DIR__ . '/../includes/class-alma-scanner.php';

$scanner = new Alma_Scanner();

// Test Core Vulnerabilities
$result_core = $scanner->run_scan('wp', 'wp_vulnerabilities');
$v_core = $result_core['vulnerabilities']['wp_vulnerabilities'];
echo "Checking wp_vulnerabilities results...\n";
echo "Status: " . $v_core['status'] . "\n";
echo "Description: " . $v_core['description'] . "\n\n";

// Test Theme Vulnerabilities
$result_theme = $scanner->run_scan('themes', 'theme_vulnerabilities');
$v_theme = $result_theme['vulnerabilities']['theme_vulnerabilities'];
echo "Checking theme_vulnerabilities results...\n";
echo "Status: " . $v_theme['status'] . "\n";
echo "Description: " . $v_theme['description'] . "\n\n";

// Test Plugin Vulnerabilities
$result = $scanner->run_scan('plugins', 'plugin_vulnerabilities');
$vulnerabilities = $result['vulnerabilities']['plugin_vulnerabilities'];

echo "Checking plugin_vulnerabilities results...\n";
echo "Status: " . $vulnerabilities['status'] . "\n";
echo "Risk: " . $vulnerabilities['risk'] . "\n";
echo "Description: " . $vulnerabilities['description'] . "\n";

$found_elementor = false;
foreach ($vulnerabilities['data'] as $v) {
    if ($v['name'] === 'Elementor') {
        $found_elementor = true;
        echo "Found Elementor vulnerability (Static).\n";
        echo "Installed: " . ($v['installed'] ? 'YES' : 'NO') . "\n";
        if ($v['installed']) {
            echo "SUCCESS: Elementor was correctly flagged as installed (via static data).\n";
        } else {
            echo "FAILURE: Elementor was NOT flagged as installed.\n";
        }
    }
}

if (!$found_elementor) {
    echo "FAILURE: Elementor not found in results.\n";
}

if ($vulnerabilities['status'] === 'warning' && $found_elementor) {
    echo "Overall status is correctly set to 'warning' due to installed vulnerabilities.\n";
} else {
    echo "Overall status is: " . $vulnerabilities['status'] . " (Expected: warning)\n";
}
