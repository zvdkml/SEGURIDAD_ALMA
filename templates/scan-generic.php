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

    <div id="scan-results-container" class="mt-8">
        <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100 text-center">
            <div class="p-6 bg-blue-50 w-20 h-20 rounded-2xl text-blue-600 mx-auto mb-6 flex items-center justify-center">
                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Listo para escanear</h3>
            <p class="text-gray-400 max-w-md mx-auto mb-8">Haz clic en el botón superior para realizar un análisis detallado de esta sección.</p>
        </div>
    </div>
</div>
