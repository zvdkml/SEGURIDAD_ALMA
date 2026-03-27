<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$db = new Alma_DB();
$all_results = $db->get_all_results();

$issues_by_module = array();
$modules = array(
    'wp'       => 'Sistema WordPress',
    'plugins'  => 'Plugins',
    'themes'   => 'Temas',
    'server'   => 'Servidor',
    'users'    => 'Usuarios',
    'malware'  => 'Malware',
    'login'    => 'Acceso/Login',
    'db'       => 'Base de Datos',
    'file_int' => 'Integridad de Archivos',
    'firewall' => 'Firewall',
    'headers'  => 'Cabeceras HTTP',
    'backup'   => 'Copias de Seguridad',
    'updates'  => 'Actualizaciones'
);

// Map check IDs back to modules
$check_to_module = array(
    'wp_update' => 'wp', 'wp_vulnerabilities' => 'wp', 'debug_mode' => 'wp', 'xmlrpc' => 'wp', 'sensitive_files' => 'wp', 'server_config' => 'wp',
    'plugins_detailed' => 'plugins', 'plugin_vulnerabilities' => 'plugins', 'plugins_update' => 'updates',
    'themes_detailed' => 'themes', 'theme_vulnerabilities' => 'themes', 'themes_update' => 'updates',
    'php_version' => 'server', 'https' => 'server', 'file_permissions' => 'server', 'directory_listing' => 'server',
    'admin_users' => 'users', 'admin_count' => 'users', 'login_attempts' => 'login',
    'malware_scan' => 'malware',
    'hidden_login' => 'login',
    'db_prefix' => 'db', 'db_remote' => 'db',
    'core_integrity' => 'file_int',
    'firewall_detect' => 'firewall',
    'security_headers' => 'headers',
    'backup_detect' => 'backup'
);

foreach ( $all_results as $row ) {
    if ( in_array( $row['status'], array( 'warning', 'critical' ) ) ) {
        $check_id = $row['check_id'];
        $mod_key = isset( $check_to_module[ $check_id ] ) ? $check_to_module[ $check_id ] : 'wp';
        $issues_by_module[ $mod_key ][] = $row;
    }
}

$has_issues = ! empty( $issues_by_module );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problemas de Seguridad Detectados - Alma Security</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-gray-50 antialiased text-gray-900 font-sans">
    <div class="wrap alma-security-wrap p-10 min-h-screen">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-12 flex justify-between items-end">
                <div>
                    <h1 class="text-xs font-black text-blue-600 uppercase tracking-[0.4em] mb-4">Auditoría Activa</h1>
                    <h2 class="text-5xl font-black text-gray-900 tracking-tighter">Problemas Pendientes</h2>
                </div>
                <div class="flex items-center gap-4">
                     <a href="<?php echo home_url('/security'); ?>" class="group flex items-center bg-white border-2 border-gray-100 hover:border-gray-900 text-gray-900 font-black py-3 px-8 rounded-2xl transition-all shadow-sm">
                        VOLVER AL DASHBOARD
                    </a>
                </div>
            </div>

            <?php if ( ! $has_issues ) : ?>
                <div class="bg-white p-20 rounded-[3.5rem] shadow-xl border border-gray-100 text-center">
                    <div class="inline-flex items-center justify-center p-6 bg-green-50 rounded-full text-green-500 mb-8">
                        <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-4xl font-black text-gray-900 tracking-tight mb-4">No hay problemas pendientes</h3>
                    <p class="text-gray-500 text-lg mb-10">Tu sitio se encuentra actualmente en un estado seguro. Todas las verificaciones críticas han pasado correctamente.</p>
                    <a href="<?php echo home_url('/security'); ?>" class="bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-12 rounded-2xl transition-all shadow-2xl uppercase text-sm tracking-widest">Ir al Dashboard</a>
                </div>
            <?php else : ?>
                <div class="space-y-12">
                    <?php foreach ( $issues_by_module as $mod_key => $rows ) : ?>
                        <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden issue-module-block" data-module="<?php echo esc_attr($mod_key); ?>">
                            <div class="px-10 py-6 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight"><?php echo esc_html( $modules[$mod_key] ); ?></h3>
                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"><?php echo count($rows); ?> Tareas</span>
                            </div>
                            <div class="p-4">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            <th class="px-6 py-4">Problema</th>
                                            <th class="px-6 py-4 text-center">Estado</th>
                                            <th class="px-6 py-4 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php foreach ( $rows as $row ) :
                                            $status_class = $row['status'] === 'critical' ? 'bg-red-50 text-red-600' : 'bg-orange-50 text-orange-600';
                                            $status_label = $row['status'] === 'critical' ? 'CRÍTICO' : 'ADVERTENCIA';
                                        ?>
                                        <tr class="issue-row" id="issue-row-<?php echo esc_attr($row['check_id']); ?>">
                                            <td class="px-6 py-6 font-bold text-gray-800"><?php echo esc_html( $row['check_name'] ); ?></td>
                                            <td class="px-6 py-6 text-center">
                                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest <?php echo $status_class; ?>">
                                                    <?php echo $status_label; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-6 text-right">
                                                <?php if ( current_user_can( 'alma_security_fix' ) || current_user_can( 'alma_security_admin' ) ) : ?>
                                                <button data-check="<?php echo esc_attr($row['check_id']); ?>" class="reparar-tarea-btn bg-gray-900 hover:bg-blue-600 text-white font-black py-2.5 px-6 rounded-xl transition-all text-[10px] uppercase tracking-widest shadow-md active:scale-95">
                                                    Reparar tarea
                                                </button>
                                                <?php else : ?>
                                                <span class="text-[9px] text-gray-400 font-bold uppercase">Sin permisos</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-20 flex flex-col items-center">
                    <a href="<?php echo home_url('/security'); ?>" class="bg-gray-900 hover:bg-blue-600 text-white font-black py-5 px-16 rounded-[2rem] transition-all duration-300 transform hover:-translate-y-1 shadow-2xl uppercase tracking-[0.2em] text-sm">
                        Volver al Dashboard
                    </a>
                    <p class="mt-6 text-xs text-gray-400 font-bold uppercase tracking-widest">Sincronización automática de estado activada</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.reparar-tarea-btn').on('click', function() {
            const btn = $(this);
            const checkId = btn.data('check');
            const row = btn.closest('.issue-row');
            const moduleBlock = btn.closest('.issue-module-block');

            console.log("[Alma Security] Click en Reparar tarea para: " + checkId);
            btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed').text('REPARANDO...');

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'alma_fix_check',
                nonce: '<?php echo wp_create_nonce("alma_security_nonce"); ?>',
                check_id: checkId
            }, function(r) {
                if(r.success) {
                    // Problem fixed: remove the row and update UI
                    row.fadeOut(300, function() {
                        row.remove();

                        // Check if module block is now empty
                        if (moduleBlock.find('.issue-row').length === 0) {
                            moduleBlock.fadeOut(400, function() {
                                moduleBlock.remove();

                                // Check if all pending issues are cleared
                                if ($('.issue-module-block').length === 0) {
                                    location.reload(); // Refreshes to show the "No problems" state
                                }
                            });
                        }
                    });

                    // Synchronize with the dashboard state
                    if (window.opener && typeof window.opener.almaRefreshDashboard === 'function') {
                        window.opener.almaRefreshDashboard();
                    }
                } else {
                    // Logic error or unfixable state
                    const errorMsg = r.data || 'El problema persiste después del intento de reparación. Por favor, realiza la acción manualmente.';
                    alert('Reparación fallida: ' + errorMsg);

                    btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed').text('REINTENTAR TAREA');
                }
            });
        });
    });
    </script>
</body>
</html>
