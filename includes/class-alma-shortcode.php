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
			return '<div class="alma-security-shortcode" style="padding: 20px; border: 1px solid #eee; border-radius: 8px; text-align: center;">No hay datos de seguridad disponibles. Por favor, realiza un escaneo desde el panel de administración.</div>';
		}

		$score = $latest_scan['score'];
		$level = $latest_scan['level'];
		$color = $score >= 80 ? '#10B981' : ( $score >= 50 ? '#F59E0B' : '#EF4444' );

		ob_start();
		?>
		<div class="alma-security-shortcode" style="font-family: sans-serif; max-width: 400px; margin: 20px 0; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #f3f4f6;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<h3 style="margin: 0; font-size: 18px; color: #1f2937; font-weight: 700;">Estado de Seguridad</h3>
				<span style="font-size: 12px; color: #9ca3af;">Último escaneo: <?php echo date( 'd/m/Y', $latest_scan['timestamp'] ); ?></span>
			</div>

			<div style="display: flex; align-items: center; gap: 20px;">
				<div style="width: 80px; height: 80px; border-radius: 50%; border: 8px solid #f3f4f6; border-top-color: <?php echo $color; ?>; display: flex; align-items: center; justify-content: center; position: relative;">
					<span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 800; color: <?php echo $color; ?>; font-size: 20px;"><?php echo $score; ?>%</span>
				</div>
				<div>
					<div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">Nivel de Riesgo</div>
					<div style="font-size: 24px; font-weight: 800; color: #111827;"><?php echo strtoupper($level); ?></div>
				</div>
			</div>

			<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f3f4f6;">
				<a href="<?php echo admin_url('admin.php?page=alma-security'); ?>" style="text-decoration: none; color: #2563eb; font-size: 14px; font-weight: 600;">Ver detalles completos &rarr;</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

new Alma_Shortcode();
