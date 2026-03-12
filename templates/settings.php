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

                <div class="pt-4">
                    <?php submit_button( 'Guardar Configuración', 'primary', 'submit', false, array( 'class' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-200' ) ); ?>
                </div>
            </div>
        </form>
    </div>
</div>
