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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 plugin-vulnerabilities-list">
        <?php foreach ( $vulnerabilities as $v ) :
            $risk = strtolower($v['risk']);
            $color = 'red';
            if ($risk === 'medio' || $risk === 'medium') $color = 'yellow';
            elseif ($risk === 'bajo' || $risk === 'low') $color = 'green';

            $desc = isset($v['description']) ? $v['description'] : (isset($v['issue']) ? $v['issue'] : '');
            $is_installed = !empty($v['installed']);
        ?>
            <div class="flex flex-col bg-white p-6 rounded-[2.5rem] border-2 <?php echo $is_installed ? 'border-red-100 ring-4 ring-red-50/30' : 'border-gray-50'; ?> shadow-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                <!-- Header: Name & Risk Badge -->
                <div class="flex items-start justify-between mb-4">
                    <h4 class="font-black text-gray-900 text-xl tracking-tighter leading-none pr-4 truncate" title="<?php echo esc_attr( $v['name'] ); ?>">
                        <?php echo esc_html( $v['name'] ); ?>
                    </h4>
                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800 border border-<?php echo $color; ?>-200 whitespace-nowrap">
                        <?php echo esc_html( $v['risk'] ); ?>
                    </span>
                </div>

                <!-- Description: Limited to 2 lines -->
                <div class="mb-6 flex-grow">
                    <p class="text-xs text-gray-500 font-medium leading-relaxed line-clamp-2 h-8" title="<?php echo esc_attr( $desc ); ?>">
                        <?php echo esc_html( $desc ); ?>
                    </p>
                </div>

                <!-- Footer: Action or Installed Info -->
                <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between gap-3">
                    <?php if ( $is_installed ) : ?>
                        <div class="flex flex-col">
                            <span class="text-[8px] font-black text-red-600 uppercase tracking-widest animate-pulse flex items-center mb-1">
                                <svg class="h-2 w-2 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                CRÍTICO
                            </span>
                            <span class="text-[7px] text-gray-400 font-bold uppercase tracking-tighter italic">Plugin Instalado</span>
                        </div>
                        <a href="<?php echo home_url('/security/fix?check=plugin_vulnerabilities&plugin=' . (isset($v['slug']) ? $v['slug'] : '')); ?>" class="bg-gray-900 hover:bg-red-600 text-white text-[9px] font-black px-5 py-2.5 rounded-xl uppercase tracking-widest shadow-lg shadow-gray-200 transition-all active:scale-95">
                            REPARAR
                        </a>
                    <?php else : ?>
                        <span class="text-[8px] text-gray-300 font-bold uppercase tracking-widest italic">No Instalado</span>
                        <span class="text-[8px] text-gray-200 font-black tracking-widest italic">--</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php
endif;
