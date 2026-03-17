<?php

// Mocking WordPress environment
define('ABSPATH', __DIR__ . '/../');
// require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

class Mock_WPDB {
    public $prefix = 'wp_';
    public function get_charset_collate() { return 'DEFAULT CHARSET utf8'; }
    public function replace($table, $data, $format) {
        echo "DB REPLACE: " . json_encode($data) . "\n";
        return 1;
    }
    public function insert($table, $data, $format) {
        echo "DB INSERT: " . json_encode($data) . "\n";
        return 1;
    }
    public function get_results($query, $output) {
        echo "DB GET_RESULTS: $query\n";
        return array();
    }
    public function get_row($query, $output) {
        echo "DB GET_ROW: $query\n";
        return null;
    }
    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
}

global $wpdb;
$wpdb = new Mock_WPDB();

function dbDelta($sql) {
    echo "dbDelta called with SQL.\n";
}

function current_time($type) {
    return date('Y-m-d H:i:s');
}

require_once __DIR__ . '/../includes/class-alma-db.php';

echo "Testing Alma_DB Logic...\n";

// Test Create Tables
Alma_DB::create_tables();

$db = new Alma_DB();

// Test Save
$test_data = array(
    'name' => 'Test Check',
    'status' => 'secure',
    'description' => 'Test Result',
    'recommendation' => 'Test Rec'
);
$db->save_check_result('test_id', $test_data);

// Test Get All
$db->get_all_results();

// Test Get One
$db->get_result('test_id');

echo "DB Test complete.\n";
