<?php
/**
 * Test script for Alma Security Plugin Mitigation.
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
$options = array(
    'active_plugins' => array('akismet/akismet.php'),
    'alma_fixed_plugins' => array()
);

function get_option($option, $default = false) {
    global $options;
    return isset($options[$option]) ? $options[$option] : $default;
}

function update_option($option, $value) {
    global $options;
    $options[$option] = $value;
    return true;
}

function get_site_transient($transient) { return false; }
function set_transient($transient, $value, $expiration = 0) { return true; }
function get_transient($transient) { return false; }
function sanitize_title($title) { return strtolower($title); }
function wp_remote_get($url, $args = array()) { return new WP_Error('mock', 'Mock API'); }
function wp_remote_retrieve_response_code($response) { return 200; }
function wp_remote_retrieve_body($response) { return '{}'; }
function is_wp_error($thing) { return $thing instanceof WP_Error; }
class WP_Error {
    private $msg;
    public function __construct($code, $message) { $this->msg = $message; }
    public function get_error_message() { return $this->msg; }
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

echo "--- Initial Scan ---\n";
$result = $scanner->run_scan('plugins', 'plugin_vulnerabilities');
$vulnerabilities = $result['vulnerabilities']['plugin_vulnerabilities'];
echo "Status: " . $vulnerabilities['status'] . "\n";
echo "Count: " . count($vulnerabilities['data']) . "\n";

echo "\n--- Fixing Elementor ---\n";
$scanner->fix_check('plugin_vulnerabilities', 'elementor');

echo "\n--- Second Scan (Elementor should be gone) ---\n";
$result = $scanner->run_scan('plugins', 'plugin_vulnerabilities');
$vulnerabilities = $result['vulnerabilities']['plugin_vulnerabilities'];
echo "Status: " . $vulnerabilities['status'] . "\n";
echo "Count: " . count($vulnerabilities['data']) . "\n";
$found_elementor = false;
foreach ($vulnerabilities['data'] as $v) {
    if ($v['slug'] === 'elementor') $found_elementor = true;
}
echo "Elementor found: " . ($found_elementor ? 'YES (FAIL)' : 'NO (PASS)') . "\n";

echo "\n--- Fixing WooCommerce ---\n";
$scanner->fix_check('plugin_vulnerabilities', 'woocommerce');

echo "\n--- Third Scan (Both should be gone, status should be secure) ---\n";
$result = $scanner->run_scan('plugins', 'plugin_vulnerabilities');
$vulnerabilities = $result['vulnerabilities']['plugin_vulnerabilities'];
echo "Status: " . $vulnerabilities['status'] . "\n";
echo "Count: " . count($vulnerabilities['data']) . "\n";
if ($vulnerabilities['status'] === 'secure') {
    echo "SUCCESS: Status is now secure.\n";
} else {
    echo "FAILURE: Status is still " . $vulnerabilities['status'] . "\n";
}
