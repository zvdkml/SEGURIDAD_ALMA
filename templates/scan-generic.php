<div class="wrap alma-security-wrap pr-4">
    <div class="flex justify-between items-center py-8">
        <div>
            <h1 class="text-4xl font-extrabold text-gray-900"><?php echo esc_html( $title ); ?></h1>
            <p class="text-gray-500 mt-1">Análisis específico para <?php echo esc_html( strtolower($title) ); ?>.</p>
        </div>
        <button id="run-specific-scan" data-type="<?php echo esc_attr($type); ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all transform hover:-translate-y-1">
            Iniciar Escaneo Especializado
        </button>
    </div>

    <!-- Loader -->
    <div id="scan-loader" class="hidden mb-8 p-6 bg-white border-2 border-blue-100 text-blue-800 rounded-3xl shadow-xl flex items-center animate-pulse">
        <svg class="animate-spin h-8 w-8 mr-4 text-blue-600" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-lg font-bold">Realizando escaneo especializado...</span>
    </div>

    <div id="scan-results-container" class="mt-8">
        <div class="bg-white p-16 rounded-[2.5rem] shadow-sm border border-gray-100 text-center">
            <div class="p-8 bg-blue-50 w-24 h-24 rounded-3xl text-blue-600 mx-auto mb-8 flex items-center justify-center">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 mb-3 uppercase tracking-tight">Listo para el Análisis</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-10 leading-relaxed">Este módulo permite realizar un escaneo en profundidad de <strong><?php echo esc_html($title); ?></strong> para detectar brechas de seguridad específicas.</p>
        </div>
    </div>
</div>
