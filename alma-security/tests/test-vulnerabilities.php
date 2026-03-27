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
function wp_remote_get($url, $args = array()) { return new WP_Error('mock', 'Mock API'); }
function is_wp_error($thing) { return $thing instanceof WP_Error; }
class WP_Error { public function __construct($code, $message) {} }
function home_url() { return 'http://example.com'; }
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
$result = $scanner->run_scan('plugins', 'plugin_vulnerabilities');

$vulnerabilities = $result['vulnerabilities']['plugin_vulnerabilities'];

echo "Checking plugin_vulnerabilities results...\n";
echo "Status: " . $vulnerabilities['status'] . "\n";
echo "Risk: " . $vulnerabilities['risk'] . "\n";
echo "Description: " . $vulnerabilities['description'] . "\n";

$found_akismet = false;
foreach ($vulnerabilities['data'] as $v) {
    if ($v['name'] === 'Akismet Anti-Spam') {
        $found_akismet = true;
        echo "Found Akismet Anti-Spam vulnerability.\n";
        echo "Installed: " . ($v['installed'] ? 'YES' : 'NO') . "\n";
        if ($v['installed']) {
            echo "SUCCESS: Akismet was correctly flagged as installed and vulnerable (via mock data).\n";
        } else {
            echo "FAILURE: Akismet was NOT flagged as installed.\n";
        }
    }
}

if (!$found_akismet) {
    echo "FAILURE: Akismet Anti-Spam not found in results.\n";
}

if ($vulnerabilities['status'] === 'warning' && $found_akismet) {
    echo "Overall status is correctly set to 'warning' due to installed vulnerabilities.\n";
} else {
    echo "Overall status is: " . $vulnerabilities['status'] . " (Expected: warning)\n";
}
