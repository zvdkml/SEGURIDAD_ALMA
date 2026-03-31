<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$check_id = isset( $_GET['check'] ) ? sanitize_text_field( $_GET['check'] ) : '';
$plugin_slug = isset( $_GET['plugin'] ) ? sanitize_text_field( $_GET['plugin'] ) : '';
$db = new Alma_DB();
$result = $db->get_check_result( $check_id );

// Custom logic for plugin vulnerabilities
if ( $check_id === 'plugin_vulnerabilities' && ! empty( $plugin_slug ) ) {
    $vulnerabilities = array();
    if ( $result && ! empty( $result['result'] ) ) {
        $vulnerabilities = json_decode( $result['result'], true );
    }

    $plugin_data = null;
    if ( is_array( $vulnerabilities ) ) {
        foreach ( $vulnerabilities as $v ) {
            if ( isset( $v['slug'] ) && $v['slug'] === $plugin_slug ) {
                $plugin_data = $v;
                break;
            }
        }
    }

    if ( $plugin_data ) {
        $result = array(
            'check_id'   => 'plugin_vulnerabilities',
            'check_name' => 'Reparar: ' . $plugin_data['name'],
            'status'     => 'critical',
            'result'     => 'Se ha detectado una vulnerabilidad en ' . $plugin_data['name'] . ': ' . (isset($plugin_data['description']) ? $plugin_data['description'] : $plugin_data['issue']),
            'recommendation' => 'Haz clic en el botón de abajo para intentar mitigar este riesgo en ' . $plugin_data['name'] . '.',
            'risk_level' => $plugin_data['risk']
        );
    }
}

if ( ! $result ) {
    $check_name = Alma_Scanner::get_check_name( $check_id );

    if ( ! empty( $check_name ) ) {
        // ID is valid but no scan data exists yet
        $result = array(
            'check_id'   => $check_id,
            'check_name' => $check_name,
            'status'     => 'pending',
            'result'     => 'Aún no se han detectado vulnerabilidades o la verificación está pendiente de escaneo.',
            'recommendation' => 'Realiza un escaneo completo desde el dashboard para obtener información actualizada.',
            'risk_level' => 'N/A'
        );
    } else {
        echo '<div class="wrap alma-security-wrap p-10 font-sans text-gray-900 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto bg-white p-12 rounded-[3rem] shadow-xl border border-gray-100 text-center">
                <h1 class="text-4xl font-black text-gray-900 mb-6">Verificación no encontrada</h1>
                <p class="text-gray-500 mb-8 text-lg">No se han encontrado datos para esta verificación específica o el ID es inválido.</p>
                <a href="' . home_url('/security') . '" class="inline-block bg-gray-900 text-white font-black py-4 px-10 rounded-2xl">Volver al Dashboard</a>
            </div>
        </div>';
        return;
    }
}

$status_label = 'Seguro';
$status_color = 'green';
if ( $result['status'] === 'warning' ) {
    $status_label = 'Advertencia';
    $status_color = 'orange';
} elseif ( $result['status'] === 'critical' ) {
    $status_label = 'Crítico';
    $status_color = 'red';
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $result['check_name'] ); ?> - Reparar Seguridad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-gray-50 antialiased">
    <div class="wrap alma-security-wrap p-10 font-sans text-gray-900 min-h-screen">
        <div class="max-w-3xl mx-auto">
            <div class="mb-10 flex items-center justify-between">
                <a href="<?php echo home_url('/security'); ?>" class="flex items-center text-gray-400 hover:text-gray-900 font-bold transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
                    VOLVER AL DASHBOARD
                </a>
                <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em]">Reparación Automática</span>
            </div>

            <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl border border-gray-100 relative overflow-hidden">
                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-xs font-black text-blue-600 uppercase tracking-[0.4em] mb-4">Verificación</h1>
                    <h2 class="text-5xl font-black text-gray-900 tracking-tighter"><?php echo esc_html( $result['check_name'] ); ?></h2>
                </div>

                <!-- Problem Description -->
                <div class="bg-red-50 p-10 rounded-[2.5rem] border border-red-100 mb-10">
                    <h3 class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-4">Descripción del Problema</h3>
                    <p class="text-xl text-red-900 font-bold leading-relaxed">
                        <?php echo esc_html( $result['result'] ); ?>
                    </p>
                </div>

                <!-- Action Area -->
                <div class="flex flex-col items-center">
                    <button id="reparar-btn" data-check="<?php echo esc_attr($check_id); ?>" class="group w-full flex items-center justify-center bg-gray-900 hover:bg-blue-600 text-white font-black py-6 px-12 rounded-[2rem] transition-all duration-300 transform hover:-translate-y-1 shadow-2xl">
                        <svg id="reparar-icon" class="h-6 w-6 mr-3 group-hover:rotate-12 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span id="reparar-text">REPARAR</span>
                    </button>

                    <p class="mt-6 text-xs text-gray-400 font-medium uppercase tracking-widest">Esta acción intentará corregir la vulnerabilidad automáticamente.</p>

                    <div class="mt-12 w-full pt-8 border-t border-gray-100 flex justify-end">
                        <a href="<?php echo home_url('/security/issues'); ?>" class="group flex items-center text-gray-400 hover:text-blue-600 font-black transition-all uppercase tracking-widest text-xs">
                            Siguiente paso (Ver todos los problemas)
                            <svg class="h-4 w-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                </div>

                <!-- Success Message (Hidden) -->
                <div id="reparar-success" class="hidden mt-10 p-10 bg-green-50 rounded-[2.5rem] border-2 border-green-200 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="bg-green-500 text-white p-3 rounded-full">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                        </div>
                    </div>
                    <h4 class="text-2xl font-black text-green-900 tracking-tight">¡Problema Solucionado!</h4>
                    <a href="<?php echo home_url('/security'); ?>" class="mt-8 inline-block bg-green-900 text-white font-black py-4 px-10 rounded-2xl text-sm">VOLVER AL DASHBOARD</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#reparar-btn').on('click', function() {
            const btn = $(this);
            const checkId = btn.data('check');
            const icon = $('#reparar-icon');
            const text = $('#reparar-text');

            btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            text.text('PROCESANDO...');
            icon.addClass('animate-spin');

            // Call the dedicated fix action
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'alma_fix_check',
                nonce: '<?php echo wp_create_nonce("alma_security_nonce"); ?>',
                check_id: checkId
            }, function(r) {
                if(r.success) {
                    btn.fadeOut(300, function() {
                        $('#reparar-success').removeClass('hidden').addClass('animate-bounce-in');

                        // Notify opener if available
                        if (window.opener && typeof window.opener.almaRefreshDashboard === 'function') {
                            window.opener.almaRefreshDashboard();
                        }
                    });
                } else {
                    const message = (r.data && r.data.message) ? r.data.message : (r.data || 'No se pudo completar la reparación.');
                    alert('Error: ' + message);
                    btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                    text.text('REINTENTAR REPARACIÓN');
                    icon.removeClass('animate-spin');
                }
            });
        });
    });
    </script>

    <style>
    @keyframes bounce-in {
        0% { transform: scale(0.9); opacity: 0; }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }
    .animate-bounce-in { animation: bounce-in 0.5s ease-out forwards; }
    </style>
</body>
</html>
