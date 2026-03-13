<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900">
    <!-- Header -->
    <div class="flex justify-between items-center py-8">
        <div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Alma Security <span class="text-blue-600">Pro</span></h1>
            <p class="text-gray-500 mt-1 font-medium">Security Dashboard Avanzado</p>
        </div>
        <button id="run-scan-btn" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-200 transition-all duration-200 transform hover:-translate-y-1">
            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Escanear Todo el Sitio
        </button>
    </div>

    <!-- Loader -->
    <div id="scan-loader" class="hidden mb-8 p-6 bg-white border-2 border-blue-100 text-blue-800 rounded-2xl shadow-xl flex items-center animate-pulse">
        <svg class="animate-spin h-8 w-8 mr-4 text-blue-600" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-lg font-bold">Iniciando análisis profundo de seguridad...</span>
    </div>

    <!-- Global Score -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 mb-10 flex items-center justify-between">
        <div class="flex items-center">
            <div class="relative w-24 h-24 mr-8">
                <canvas id="scoreChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span id="scoreText" class="text-xl font-black">--</span>
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Security Score Global</h2>
                <p class="text-gray-500 font-medium">Estado general de la protección de tu sitio.</p>
                <div id="risk-level" class="inline-block mt-2 text-[10px] font-black px-3 py-1 rounded-full bg-gray-100 text-gray-500 uppercase tracking-widest">--</div>
            </div>
        </div>
        <div class="w-1/3">
             <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                <div id="score-progress" class="bg-gray-300 h-full transition-all duration-1000" style="width: 0%"></div>
            </div>
            <p id="last-scan-info" class="text-right text-[10px] font-bold text-gray-400 mt-2 uppercase">Sin análisis previos</p>
        </div>
    </div>

    <!-- Scan Cards Grid -->
    <div id="cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <!-- WordPress Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-blue-50 rounded-2xl text-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <div id="status-badge-wp" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">WordPress Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">Versión, Debug y Archivos Core.</p>
            <div id="results-wp" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="wp" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear WordPress</button>
        </div>

        <!-- Plugins Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-purple-50 rounded-2xl text-purple-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div id="status-badge-plugins" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Plugin Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">Vulnerabilidades y Actualizaciones.</p>
            <div id="results-plugins" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="plugins" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear Plugins</button>
        </div>

        <!-- Themes Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-pink-50 rounded-2xl text-pink-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                </div>
                <div id="status-badge-themes" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Theme Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">Temas inactivos y sospechosos.</p>
            <div id="results-themes" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="themes" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear Temas</button>
        </div>

        <!-- Server Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-green-50 rounded-2xl text-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <div id="status-badge-server" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Server Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">PHP, Headers y HTTPS.</p>
            <div id="results-server" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="server" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear Servidor</button>
        </div>

        <!-- User Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-orange-50 rounded-2xl text-orange-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div id="status-badge-users" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">User Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">Admin accounts y Passwords.</p>
            <div id="results-users" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="users" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear Usuarios</button>
        </div>

        <!-- Malware Scan -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col hover:border-blue-200 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="p-4 bg-red-50 rounded-2xl text-red-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div id="status-badge-malware" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-gray-100 text-gray-400">Desconocido</div>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Malware Scan</h3>
            <p class="text-sm text-gray-500 font-medium mb-6">Inyección de código y Backdoors.</p>
            <div id="results-malware" class="flex-grow mb-6 space-y-2">
                 <p class="text-xs text-gray-300 italic">Realiza un escaneo para ver detalles.</p>
            </div>
            <button data-type="malware" class="run-specific-scan-btn w-full py-3 bg-gray-50 hover:bg-red-600 hover:text-white text-gray-600 font-black text-xs uppercase tracking-widest rounded-xl transition-all">Escanear Malware</button>
        </div>
    </div>

    <!-- Detailed Results -->
    <div id="detailed-results-section" class="hidden mb-12">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Análisis Detallado</h3>
            <button id="close-details" class="text-gray-400 hover:text-gray-600 font-bold text-xs uppercase tracking-widest">Ocultar Detalles &times;</button>
        </div>
        <div id="detailed-results-container" class="space-y-6">
            <!-- Results will be injected here -->
        </div>
    </div>

    <!-- Secondary Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
            <h3 class="text-xl font-black text-gray-900 mb-8 uppercase tracking-tight">Evolución del Score</h3>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
            <h3 class="text-xl font-black text-gray-900 mb-8 uppercase tracking-tight">Distribución de Hallazgos</h3>
            <div class="h-64">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>
