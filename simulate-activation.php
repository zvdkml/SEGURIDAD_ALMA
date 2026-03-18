<?php
// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WPINC', 'wp-includes');

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
function add_shortcode($tag, $func) {}
function register_activation_hook($file, $function) {}
function register_deactivation_hook($file, $function) {}
function plugin_dir_path($file) { return __DIR__ . '/'; }
function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/seguridad-alma/'; }
function get_site_transient($transient) { return false; }
function apply_filters($tag, $value) { return $value; }
function wp_next_scheduled($hook) { return false; }
function wp_schedule_event($timestamp, $recurrence, $hook, $args = array(), $wp_error = false) { return true; }
function wp_unschedule_event($timestamp, $hook, $args = array(), $wp_error = false) { return true; }
function current_time($type) { return date('Y-m-d H:i:s'); }
function get_option($name, $default = false) { return $default; }
function update_option($name, $value) { return true; }
function checked($helper, $current, $echo) { return ''; }
function submit_button() {}
function admin_url($path) { return 'http://example.com/wp-admin/' . $path; }
function wp_create_nonce($action) { return 'nonce'; }
function is_user_logged_in() { return true; }
function wp_get_current_user() {
    $user = new stdClass();
    $user->user_login = 'admin';
    return $user;
}
function current_user_can($cap) { return true; }
function get_page_by_path($path) { return null; }
function wp_insert_post($args) { return 1; }

class wpdb {
    public $prefix = 'wp_';
    public function get_charset_collate() { return ''; }
    public function query($query) { return true; }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $format) { return true; }
    public function replace($table, $data, $format) { return true; }
    public function get_results($query, $output) { return array(); }
    public function get_row($query, $output) { return null; }
}
$wpdb = new wpdb();

require_once 'seguridad-alma.php';

echo "Attempting activation...\n";
$plugin = Seguridad_Alma::get_instance();
$plugin->activate();
echo "Activation successful!\n";
