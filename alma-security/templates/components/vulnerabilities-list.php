<?php
/**
 * Component: Vulnerabilities List (Compact Design)
 *
 * @param array $vulnerabilities Array of vulnerability data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $vulnerabilities ) && is_array( $vulnerabilities ) ) :
?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 plugin-vulnerabilities-list">
        <?php foreach ( $vulnerabilities as $v ) :
            $risk = strtolower($v['risk']);
            $color = 'red';
            if ($risk === 'medio' || $risk === 'medium') $color = 'yellow';
            elseif ($risk === 'bajo' || $risk === 'low') $color = 'green';

            $desc = isset($v['description']) ? $v['description'] : (isset($v['issue']) ? $v['issue'] : '');
        ?>
            <div class="flex flex-col bg-white p-4 rounded-2xl border <?php echo !empty($v['installed']) ? 'border-red-500 ring-1 ring-red-50' : 'border-gray-100'; ?> shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-black text-gray-900 text-xs uppercase tracking-tight truncate w-32" title="<?php echo esc_attr( $v['name'] ); ?>">
                        <?php echo esc_html( $v['name'] ); ?>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[8px] font-black uppercase bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800 border border-<?php echo $color; ?>-200">
                        <?php echo esc_html( $v['risk'] ); ?>
                    </span>
                </div>

                <p class="text-[10px] text-gray-500 leading-snug line-clamp-2 mb-2 h-7" title="<?php echo esc_attr( $desc ); ?>">
                    <?php echo esc_html( $desc ); ?>
                </p>

                <?php if ( ! empty( $v['installed'] ) ) : ?>
                    <div class="mt-auto pt-2 border-t border-red-50 flex items-center justify-between">
                        <span class="text-[8px] font-black text-red-600 uppercase tracking-tighter animate-pulse flex items-center">
                            <svg class="h-2 w-2 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            INSTALADO Y VULNERABLE
                        </span>
                        <span class="bg-red-600 text-white text-[7px] font-black px-1.5 py-0.5 rounded uppercase tracking-tighter shadow-sm">CRÍTICO</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
endif;
