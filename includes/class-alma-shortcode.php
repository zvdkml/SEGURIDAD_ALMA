<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Alma_Shortcode {

	public function __construct() {
		add_shortcode( 'alma_security_status', array( $this, 'render_shortcode' ) );
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
