<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900">
    <!-- Header -->
    <div class="flex justify-between items-center py-8">
        <div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Alma Security <span class="text-blue-600">Pro</span></h1>
            <p class="text-gray-500 mt-1 font-medium">Panel avanzado de monitorización de seguridad.</p>
        </div>
        <div class="flex space-x-3">
            <button id="run-scan-btn" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-200 transition-all duration-200 transform hover:-translate-y-1">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Escanear Ahora
            </button>
        </div>
    </div>

    <!-- Loader -->
    <div id="scan-loader" class="hidden mb-8 p-6 bg-white border-2 border-blue-100 text-blue-800 rounded-2xl shadow-xl flex items-center animate-pulse">
        <svg class="animate-spin h-8 w-8 mr-4 text-blue-600" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-lg font-bold">Iniciando análisis profundo de seguridad...</span>
    </div>

    <!-- Main Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-10">
        <!-- Score Card -->
        <div class="lg:col-span-1 bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <svg class="h-24 w-24 text-gray-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L3 7v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z"/></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">Security Score</h3>
            <div class="relative w-40 h-40 mb-4">
                <canvas id="scoreChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span id="scoreText" class="text-4xl font-black">--</span>
                    <span id="risk-level" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 mt-1 uppercase tracking-tighter">--</span>
                </div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-4">
                <div id="score-progress" class="bg-gray-300 h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
            </div>
        </div>

        <!-- System Status Cards -->
        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- WP Status -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 rounded-2xl text-blue-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div id="status-badge-wp" class="h-2.5 w-2.5 rounded-full bg-gray-300 shadow-sm"></div>
                </div>
                <div>
                    <h4 class="text-gray-400 text-xs font-bold uppercase tracking-wider">WordPress</h4>
                    <p id="status-text-wp" class="text-lg font-bold text-gray-800 truncate mt-1">--</p>
                </div>
            </div>
            <!-- Plugins Status -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-purple-50 rounded-2xl text-purple-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div id="status-badge-plugins" class="h-2.5 w-2.5 rounded-full bg-gray-300 shadow-sm"></div>
                </div>
                <div>
                    <h4 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Plugins</h4>
                    <p id="status-text-plugins" class="text-lg font-bold text-gray-800 mt-1">--</p>
                </div>
            </div>
            <!-- Security Status -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-50 rounded-2xl text-green-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div id="status-badge-security" class="h-2.5 w-2.5 rounded-full bg-gray-300 shadow-sm"></div>
                </div>
                <div>
                    <h4 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Seguridad</h4>
                    <p id="status-text-security" class="text-lg font-bold text-gray-800 mt-1">--</p>
                </div>
            </div>
            <!-- Users Status -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:border-blue-200 transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-50 rounded-2xl text-orange-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div id="status-badge-users" class="h-2.5 w-2.5 rounded-full bg-gray-300 shadow-sm"></div>
                </div>
                <div>
                    <h4 class="text-gray-400 text-xs font-bold uppercase tracking-wider">Usuarios</h4>
                    <p id="status-text-users" class="text-lg font-bold text-gray-800 mt-1">--</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Issues Alert Section -->
    <div id="critical-issues-section" class="hidden mb-10 overflow-hidden rounded-3xl bg-red-50 border-2 border-red-100 shadow-lg shadow-red-50">
        <div class="bg-red-500 p-4 flex items-center text-white">
            <svg class="h-6 w-6 mr-3 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="font-black text-lg">ALERTAS CRÍTICAS DETECTADAS</h3>
        </div>
        <div id="critical-issues-container" class="p-6 space-y-4 font-medium text-red-900">
            <!-- Critical issues will be injected here -->
        </div>
    </div>

    <!-- Charts and Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <!-- Trend Chart -->
        <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-xl font-black text-gray-900">Evolución del Score</h3>
                <span class="text-xs font-bold text-gray-400 bg-gray-50 px-3 py-1 rounded-full uppercase">Últimos 20 escaneos</span>
            </div>
            <div class="h-72">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Risks Distribution -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-black text-gray-900 mb-8">Riesgos por Nivel</h3>
            <div class="h-72 flex flex-col">
                <div class="flex-grow flex items-center justify-center">
                    <canvas id="distributionChart"></canvas>
                </div>
                <div id="stats-summary" class="grid grid-cols-3 gap-2 mt-6 border-t pt-6">
                    <div class="text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1">Total</div>
                        <div id="stat-total" class="text-xl font-black text-gray-800">0</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1 text-green-500">Seguros</div>
                        <div id="stat-secure" class="text-xl font-black text-green-600">0</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter mb-1 text-red-500">Fallos</div>
                        <div id="stat-critical-total" class="text-xl font-black text-red-600">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details and Lists -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">
        <!-- Main Issues Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-gray-900">Resultados del Análisis</h3>
                <a href="<?php echo admin_url('admin.php?page=alma-vulnerabilities'); ?>" class="text-blue-600 text-xs font-bold uppercase tracking-widest hover:underline">Ver todo &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-4">Vulnerabilidad</th>
                            <th class="px-8 py-4 text-center">Riesgo</th>
                            <th class="px-8 py-4 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="vulnerability-list-short" class="divide-y divide-gray-50 font-medium">
                        <tr>
                            <td colspan="3" class="px-8 py-12 text-center text-gray-300 italic">No hay datos disponibles. Escanea tu sitio ahora.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Summary -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col">
            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-black text-gray-900">Historial Reciente</h3>
                <a href="<?php echo admin_url('admin.php?page=alma-history'); ?>" class="text-blue-600 text-xs font-bold uppercase tracking-widest hover:underline">Historial completo &rarr;</a>
            </div>
            <div class="p-8 flex-grow">
                <div id="history-mini-list" class="space-y-4">
                    <!-- History items will be injected here -->
                    <p class="text-center text-gray-300 italic py-10">Sin registros de escaneo anteriores.</p>
                </div>
            </div>
            <div class="p-8 bg-gray-50 text-center border-t border-gray-100">
                <p id="last-scan-info" class="text-xs font-bold text-gray-400 uppercase tracking-widest">No se detectaron escaneos.</p>
            </div>
        </div>
    </div>
</div>
