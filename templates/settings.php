<div class="wrap alma-security-wrap pr-4">
    <div class="flex justify-between items-center py-6">
        <h1 class="text-3xl font-bold text-gray-800">Configuración de Seguridad</h1>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 max-w-2xl">
        <form method="post" action="options.php">
            <?php
            settings_fields( 'alma_security_settings' );
            do_settings_sections( 'alma_security_settings' );
            ?>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Endpoint de API Externa</label>
                    <input type="url" name="alma_security_api_endpoint" value="<?php echo esc_attr( get_option( 'alma_security_api_endpoint' ) ); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://tu-dashboard.com/api/security">
                    <p class="text-xs text-gray-400 mt-1">URL donde se enviarán los datos de escaneo automáticamente.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">API Key</label>
                    <input type="password" name="alma_security_api_key" value="<?php echo esc_attr( get_option( 'alma_security_api_key' ) ); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••••••••••">
                    <p class="text-xs text-gray-400 mt-1">Clave de autenticación para el dashboard externo.</p>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="alma_security_enable_api" value="1" <?php checked( 1, get_option( 'alma_security_enable_api' ), true ); ?> class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label class="ml-2 block text-sm text-gray-900 font-medium">Habilitar envío automático a API externa</label>
                </div>

                <div class="pt-10 border-t border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Monitoreo Remoto (Central Monitor)</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Endpoint de Central Monitor</label>
                            <input type="url" name="alma_security_monitor_endpoint" value="<?php echo esc_attr( get_option( 'alma_security_monitor_endpoint', 'https://api.midominio.com/site-data' ) ); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="https://monitor.tudominio.com/site-data">
                            <p class="text-xs text-gray-400 mt-1">URL del servidor Node.js/Central Monitor.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-8">
                    <?php submit_button( 'Guardar Todas las Configuraciones', 'primary', 'submit', false, array( 'class' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition duration-200' ) ); ?>
                </div>
            </div>
        </form>
    </div>

    <?php
    /**
     * Gestión de Usuarios de Seguridad (Hidden from UI per user request)
     * The underlying logic remains in Alma_Auth and Alma_Admin for backend operations.
     */
    /*
    $auth = new Alma_Auth();
    if ( $auth->can( 'manage_users' ) ) :
        $alma_users = $auth->get_users();
    ?>
    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 max-w-4xl mt-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Gestión de Usuarios de Seguridad</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- List Users -->
            <div class="md:col-span-2">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b">
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($alma_users as $u) : ?>
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-4 font-bold text-gray-700"><?php echo esc_html($u['username']); ?></td>
                            <td class="px-4 py-4"><span class="px-2 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-lg uppercase"><?php echo esc_html($u['role']); ?></span></td>
                            <td class="px-4 py-4 text-right">
                                <button onclick="deleteAlmaUser(<?php echo $u['id']; ?>)" class="text-red-400 hover:text-red-600 transition">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($alma_users)) : ?>
                        <tr><td colspan="3" class="px-4 py-10 text-center text-gray-300 italic">No hay usuarios específicos creados. Se usarán los roles de WordPress por defecto.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add User -->
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wider">Crear Usuario</h3>
                <div class="space-y-4">
                    <input type="text" id="new-alma-user" placeholder="Username" class="w-full px-4 py-2 border rounded-lg text-sm">
                    <input type="password" id="new-alma-pass" placeholder="Password" class="w-full px-4 py-2 border rounded-lg text-sm">
                    <select id="new-alma-role" class="w-full px-4 py-2 border rounded-lg text-sm">
                        <?php foreach (Alma_Auth::get_roles() as $val => $label) : ?>
                        <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="createAlmaUser()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg transition">CREAR ACCESO</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; */ ?>
</div>
