<?php

// Mocking WordPress environment
define('ABSPATH', __DIR__ . '/../');

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

class Mock_Auth_WPDB {
    public $prefix = 'wp_';
    public function get_row($query, $output) {
        if (strpos($query, "username = 'test_viewer'") !== false) return array('role' => 'viewer');
        if (strpos($query, "username = 'test_user'") !== false) return array('role' => 'user');
        if (strpos($query, "username = 'test_admin'") !== false) return array('role' => 'admin');
        return null;
    }
    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
}

global $wpdb;
$wpdb = new Mock_Auth_WPDB();

function is_user_logged_in() { return true; }
class Mock_WP_User { public $user_login; }
function wp_get_current_user() {
    global $current_mock_user;
    $u = new Mock_WP_User();
    $u->user_login = $current_mock_user;
    return $u;
}
function current_user_can($cap) { return false; }

require_once __DIR__ . '/../includes/class-alma-auth.php';

$auth = new Alma_Auth();

echo "Testing RBAC Roles...\n";

// Test Viewer
$GLOBALS['current_mock_user'] = 'test_viewer';
echo "User: viewer -> can full_scan? " . ($auth->can('full_scan') ? 'YES' : 'NO') . "\n";
echo "User: viewer -> can individual_scan? " . ($auth->can('individual_scan') ? 'YES' : 'NO') . "\n";
echo "User: viewer -> can view_results? " . ($auth->can('view_results') ? 'YES' : 'NO') . "\n";

// Test User
$GLOBALS['current_mock_user'] = 'test_user';
echo "User: user -> can full_scan? " . ($auth->can('full_scan') ? 'YES' : 'NO') . "\n";
echo "User: user -> can individual_scan? " . ($auth->can('individual_scan') ? 'YES' : 'NO') . "\n";

// Test Admin
$GLOBALS['current_mock_user'] = 'test_admin';
echo "User: admin -> can full_scan? " . ($auth->can('full_scan') ? 'YES' : 'NO') . "\n";
echo "User: admin -> can delete_data? " . ($auth->can('delete_data') ? 'YES' : 'NO') . "\n";

echo "RBAC Test complete.\n";
