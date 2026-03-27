<?php
/**
 * Mock WordPress Environment for Dashboard Rendering
 */

define('ABSPATH', __DIR__ . '/');
define('ALMA_SECURITY_PATH', __DIR__ . '/alma-security/');
define('ALMA_SECURITY_URL', 'http://localhost:8080/alma-security/');

// Mock WP functions
function is_admin() { return false; }
function language_attributes() { echo 'lang="es"'; }
function bloginfo($prop) { if ($prop === 'charset') echo 'UTF-8'; }
function includes_url($path) { return 'https://s.w.org/wp-includes/' . $path; }
function current_user_can($cap) { return true; }
function home_url($path = '') { return 'http://localhost:8080' . $path; }
function wp_create_nonce($action) { return 'mock_nonce'; }
function admin_url($path) { return 'http://localhost:8080/wp-admin/' . $path; }
function get_option($name, $default = false) { return $default; }
function esc_html($text) { return htmlspecialchars($text); }
function esc_attr($text) { return htmlspecialchars($text); }

class Alma_DB {
    public function get_check_result($id) {
        if ($id === 'plugin_vulnerabilities') {
            return [
                'check_id' => 'plugin_vulnerabilities',
                'status' => 'warning',
                'result' => json_encode([
                    ['name' => 'Elementor', 'risk' => 'Alto', 'issue' => 'Vulnerabilidad Estática', 'installed' => true],
                    ['name' => 'WooCommerce', 'risk' => 'Medio', 'issue' => 'Vulnerabilidad Estática', 'installed' => true]
                ]),
                'description' => 'Vulnerabilidades detectadas.',
                'recommendation' => 'Actualizar plugins.',
                'risk_level' => 'Alto',
                'is_vulnerabilities' => true
            ];
        }
        return false;
    }
    public function get_all_results() { return []; }
    public function get_score_history() { return []; }
}

class Alma_Scanner {
    public static function get_check_name($id) {
        $names = ['plugin_vulnerabilities' => 'Vulnerabilidades de plugins'];
        return isset($names[$id]) ? $names[$id] : $id;
    }
}

class Alma_History {
    public function get_latest_scan() { return ['score' => 75, 'level' => 'Medio', 'timestamp' => time()]; }
    public function get_history() { return []; }
}

class Alma_Auth {
    public function get_current_user_role() { return 'administrator'; }
}

// Render the dashboard
include 'alma-security/templates/dashboard.php';
