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
    <div class="grid grid-cols-1 gap-1.5 plugin-vulnerabilities-list">
        <?php foreach ( $vulnerabilities as $v ) :
            $risk = strtolower($v['risk']);
            $color = 'red';
            if ($risk === 'medio' || $risk === 'medium') $color = 'yellow';
            elseif ($risk === 'bajo' || $risk === 'low') $color = 'green';
        ?>
            <div class="flex items-center text-[9px] bg-<?php echo $color; ?>-50/50 p-1.5 rounded-lg border border-<?php echo $color; ?>-100/50 shadow-sm transition-all hover:bg-<?php echo $color; ?>-100/30">
                <span class="font-black text-<?php echo $color; ?>-700 mr-2 uppercase tracking-tighter w-16 truncate" title="<?php echo esc_attr( $v['name'] ); ?>"><?php echo esc_html( $v['name'] ); ?></span>
                <span class="w-1 h-1 bg-<?php echo $color; ?>-300 rounded-full mr-2 shrink-0"></span>
                <span class="font-bold text-<?php echo $color; ?>-600 mr-2 shrink-0"><?php echo esc_html( $v['risk'] ); ?></span>
                <span class="text-<?php echo $color; ?>-500 italic truncate" title="<?php echo esc_attr( $v['issue'] ); ?>"><?php echo esc_html( $v['issue'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php
endif;
