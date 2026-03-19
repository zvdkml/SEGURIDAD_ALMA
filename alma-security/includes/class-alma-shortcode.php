<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Shortcode {

	public function __construct() {
		add_shortcode( 'alma_security_status', array( $this, 'render_shortcode' ) );
		add_shortcode( 'alma_security_dashboard', array( $this, 'render_dashboard' ) );
	}

	public function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			return '<div style="padding: 50px; text-align: center; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); font-family: sans-serif;">
				<h2 style="font-weight: 900; color: #111827; margin-bottom: 20px;">Acceso Restringido</h2>
				<p style="color: #6b7280; margin-bottom: 30px;">Debes iniciar sesión para acceder al dashboard de seguridad.</p>
				<a href="' . wp_login_url( get_permalink() ) . '" style="display: inline-block; background: #111827; color: #fff; padding: 15px 30px; border-radius: 15px; text-decoration: none; font-weight: 700;">Iniciar Sesión</a>
			</div>';
		}

		// Enqueue scripts/styles for frontend
		$this->enqueue_frontend_assets();

		$history = new Alma_History();
		$latest_scan = $history->get_latest_scan();

		ob_start();
		echo '<div id="alma-frontend-dashboard" class="alma-security-dashboard-frontend" style="min-height: 100vh; background: #f9fafb;">';
		include ALMA_SECURITY_PATH . 'templates/dashboard.php';
		echo '</div>';
		return ob_get_clean();
	}

	private function enqueue_frontend_assets() {
		wp_enqueue_style( 'alma-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' );
		wp_enqueue_script( 'alma-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
		wp_enqueue_script( 'alma-admin-js', ALMA_SECURITY_URL . 'assets/js/alma-admin.js', array( 'jquery', 'alma-chartjs' ), ALMA_SECURITY_VERSION, true );

		$history = new Alma_History();
		$latest_scan = $history->get_latest_scan();
		$db = new Alma_DB();
		$db_results = $db->get_all_results();
		$score_history = $db->get_score_history();
		$history_data = $history->get_history();

		$persisted_results = array();
		foreach ( $db_results as $row ) {
			$persisted_results[ $row['check_id'] ] = array(
				'name'           => $row['check_name'],
				'status'         => $row['status'],
				'description'    => $row['result'],
				'recommendation' => $row['recommendation'],
				'risk_level'     => $row['risk_level'],
				'last_scan_at'   => $row['last_scan_at']
			);
		}

		$auth = new Alma_Auth();
		$current_role = $auth->get_current_user_role();

		wp_localize_script( 'alma-admin-js', 'alma_ajax', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'alma_security_nonce' ),
			'latest_scan'  => $latest_scan,
			'history'      => $history_data,
			'scan_index'   => -1,
			'db_results'   => $persisted_results,
			'user_role'    => $current_role,
			'score_history'=> $score_history,
		) );
	}

	public function render_shortcode() {
		$history = new Alma_History();
		$latest_scan = $history->get_latest_scan();

		if ( ! $latest_scan ) {
			return '<div class="alma-security-shortcode" style="padding: 30px; background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 24px; text-align: center; color: #6b7280; font-family: sans-serif; font-weight: 600;">No hay datos de seguridad disponibles. Por favor, realiza un escaneo desde el administrador.</div>';
		}

		$score = $latest_scan['score'];
		$level = $latest_scan['level'];
		$color = $score >= 80 ? '#10B981' : ( $score >= 50 ? '#F59E0B' : '#EF4444' );
		$bg_color = $score >= 80 ? '#ecfdf5' : ( $score >= 50 ? '#fffbeb' : '#fef2f2' );

		ob_start();
		?>
		<div class="alma-security-shortcode" style="font-family: 'Inter', system-ui, sans-serif; max-width: 450px; margin: 2rem 0; padding: 32px; background: #ffffff; border-radius: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02); border: 1px solid #f3f4f6;">
			<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 32px;">
				<div>
					<h3 style="margin: 0; font-size: 22px; color: #111827; font-weight: 900; letter-spacing: -0.025em;">Alma Security</h3>
					<p style="margin: 4px 0 0; font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; tracking: 0.05em;">Estado de Protección</p>
				</div>
				<?php
				$status_text = 'Seguro';
				if ($latest_scan['score'] < 50) $status_text = 'Crítico';
				elseif ($latest_scan['score'] < 80) $status_text = 'Advertencia';
				?>
				<div style="background: <?php echo $bg_color; ?>; padding: 6px 12px; border-radius: 12px; color: <?php echo $color; ?>; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;"><?php echo $status_text; ?></div>
			</div>

			<div style="display: flex; align-items: center; gap: 32px; margin-bottom: 32px; background: #f9fafb; padding: 24px; border-radius: 32px;">
				<div style="width: 90px; height: 90px; border-radius: 50%; background: #ffffff; border: 10px solid #f3f4f6; border-top-color: <?php echo $color; ?>; display: flex; align-items: center; justify-content: center; position: relative; box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.05);">
					<span style="font-weight: 900; color: #111827; font-size: 24px; letter-spacing: -0.05em;"><?php echo $score; ?><span style="font-size: 12px; color: #9ca3af;">%</span></span>
				</div>
				<div>
					<div style="font-size: 13px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Integridad del Sitio</div>
					<div style="font-size: 11px; color: #9ca3af; font-weight: 500;">Basado en <?php echo count($latest_scan['vulnerabilities']); ?> verificaciones automáticas realizadas el <?php echo date( 'd/m/Y', $latest_scan['timestamp'] ); ?>.</div>
				</div>
			</div>

			<div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
				<a href="<?php echo admin_url('admin.php?page=alma-security'); ?>" style="display: inline-flex; align-items: center; text-decoration: none; background: #111827; color: #ffffff; padding: 12px 20px; border-radius: 16px; font-size: 12px; font-weight: 700; transition: all 0.2s ease; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); white-space: nowrap;">
					Abrir Dashboard &rarr;
				</a>
				<a href="<?php echo admin_url('admin.php?page=alma-security&auto_scan=1'); ?>" style="display: inline-flex; align-items: center; text-decoration: none; background: #2563eb; color: #ffffff; padding: 12px 20px; border-radius: 16px; font-size: 12px; font-weight: 700; transition: all 0.2s ease; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); white-space: nowrap;">
					Ejecutar Escaneo
				</a>
				<?php if (current_user_can('manage_options')) : ?>
				<span style="font-size: 11px; color: #10b981; font-weight: 700; display: flex; align-items: center;">
					<span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; margin-right: 6px; animation: pulse 2s infinite;"></span>
					Admin Verificado
				</span>
				<?php endif; ?>
			</div>
		</div>
		<style>
			@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
		</style>
		<?php
		return ob_get_clean();
	}
}

new Alma_Shortcode();
