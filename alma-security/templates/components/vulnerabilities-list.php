<?php
/**
 * Component: Vulnerabilities List
 *
 * @param array $vulnerabilities Array of vulnerability data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $vulnerabilities ) && is_array( $vulnerabilities ) ) :
?>
    <div class="space-y-2 plugin-vulnerabilities-list">
        <?php foreach ( $vulnerabilities as $v ) : ?>
            <div class="flex items-center text-[10px] bg-red-50/50 p-2 rounded-xl border border-red-100/50">
                <span class="font-black text-red-700 mr-2 uppercase tracking-tighter"><?php echo esc_html( $v['name'] ); ?></span>
                <span class="w-1 h-1 bg-red-300 rounded-full mr-2"></span>
                <span class="font-bold text-red-600 mr-2"><?php echo esc_html( $v['risk'] ); ?></span>
                <span class="text-red-500 italic"><?php echo esc_html( $v['issue'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php
endif;
