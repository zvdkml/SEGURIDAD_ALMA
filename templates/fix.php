<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$check_id = isset( $_GET['check'] ) ? sanitize_text_field( $_GET['check'] ) : '';
$db = new Alma_DB();
$result = $db->get_check_result( $check_id );

if ( ! $result ) {
    echo '<div class="wrap alma-security-wrap p-10 font-sans text-gray-900 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto bg-white p-12 rounded-[3rem] shadow-xl border border-gray-100 text-center">
            <h1 class="text-4xl font-black text-gray-900 mb-6">Verificación no encontrada</h1>
            <p class="text-gray-500 mb-8 text-lg">No se han encontrado datos para esta verificación específica o aún no ha sido escaneada.</p>
            <a href="' . home_url('/security') . '" class="inline-block bg-gray-900 text-white font-black py-4 px-10 rounded-2xl">Volver al Dashboard</a>
        </div>
    </div>';
    return;
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
    <title><?php echo esc_html( $result['check_name'] ); ?> - Solución de Seguridad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-50 antialiased">
    <div class="wrap alma-security-wrap p-10 font-sans text-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto">
            <div class="mb-10 flex items-center justify-between">
                <a href="<?php echo home_url('/security'); ?>" class="flex items-center text-gray-400 hover:text-gray-900 font-bold transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" /></svg>
                    VOLVER AL DASHBOARD
                </a>
                <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.3em]">Solución de Seguridad</span>
            </div>

            <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl border border-gray-100 relative overflow-hidden">
                <!-- Status Ribbon -->
                <div class="absolute top-0 right-0 mt-10 mr-[-50px] rotate-45 bg-<?php echo $status_color; ?>-500 text-white px-20 py-2 text-[10px] font-black uppercase tracking-widest shadow-lg">
                    <?php echo $status_label; ?>
                </div>

                <div class="flex items-start gap-8 mb-12">
                    <div class="p-6 bg-<?php echo $status_color; ?>-50 rounded-[2rem] text-<?php echo $status_color; ?>-600 shadow-inner">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-5xl font-black text-gray-900 tracking-tighter leading-tight"><?php echo esc_html( $result['check_name'] ); ?></h1>
                        <div class="flex gap-4 mt-4">
                            <span class="px-4 py-1.5 bg-gray-100 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-200">ID: <?php echo esc_html( $check_id ); ?></span>
                            <span class="px-4 py-1.5 bg-<?php echo $status_color; ?>-50 text-<?php echo $status_color; ?>-700 rounded-full text-[10px] font-black uppercase tracking-widest border border-<?php echo $status_color; ?>-100">Riesgo: <?php echo esc_html( $result['risk_level'] ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="bg-gray-50 p-10 rounded-[2.5rem] border border-gray-100">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Hallazgo Actual</h3>
                        <p class="text-lg text-gray-700 font-medium leading-relaxed italic">"<?php echo esc_html( $result['result'] ); ?>"</p>
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Escaneado el</p>
                            <p class="text-sm font-black text-gray-600"><?php echo esc_html( $result['last_scan_at'] ); ?></p>
                        </div>
                    </div>

                    <div class="bg-blue-600 p-10 rounded-[2.5rem] shadow-xl shadow-blue-200">
                        <h3 class="text-xs font-black text-blue-200 uppercase tracking-widest mb-6">Solución Recomendada</h3>
                        <div class="text-xl text-white font-bold leading-relaxed">
                            <?php echo esc_html( $result['recommendation'] ); ?>
                        </div>
                        <div class="mt-10 bg-blue-700/50 p-6 rounded-2xl border border-blue-400/30">
                            <p class="text-[10px] text-blue-100 font-black uppercase tracking-widest mb-2 flex items-center">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Nota del Experto
                            </p>
                            <p class="text-xs text-blue-50 font-medium opacity-90">Sigue estas instrucciones detalladas para mitigar el riesgo de seguridad identificado en este módulo.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Area -->
                <div class="mt-12 pt-10 border-t border-gray-100 flex justify-center">
                    <button onclick="window.location.reload();" class="group flex items-center bg-gray-900 hover:bg-blue-600 text-white font-black py-5 px-12 rounded-[2rem] transition-all duration-300 transform hover:-translate-y-1 shadow-2xl">
                        <svg class="h-6 w-6 mr-3 group-hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        RE-ESCANEAR Y VERIFICAR SOLUCIÓN
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
