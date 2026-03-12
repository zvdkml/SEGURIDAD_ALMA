<div class="wrap alma-security-wrap pr-4">
    <div class="flex justify-between items-center py-6">
        <h1 class="text-3xl font-bold text-gray-800">Security Monitor Dashboard</h1>
        <button id="run-scan-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-200">
            Realizar Escaneo
        </button>
    </div>

    <div id="scan-loader" class="hidden mb-6 p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded shadow">
        <div class="flex items-center">
            <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Analizando la seguridad del sitio... por favor espera.
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center">
            <h3 class="text-lg font-semibold text-gray-500 mb-2">Security Score</h3>
            <div class="relative w-32 h-32">
                <canvas id="scoreChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span id="scoreText" class="text-3xl font-bold text-gray-800">--</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-500 mb-4">Estado del Sistema</h3>
            <div id="system-status" class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Nivel de Riesgo:</span>
                    <span id="risk-level" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-600 text-transform: uppercase;">--</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="score-progress" class="bg-gray-400 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p id="last-scan-info" class="text-sm text-gray-400 mt-2 text-center">No se han realizado escaneos recientes.</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center">
            <h3 class="text-lg font-semibold text-gray-500 mb-2">Distribución de Riesgos</h3>
            <div class="h-32 w-full">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Details Stats -->
    <div id="stats-summary" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-center">
            <div class="text-blue-500 text-xs font-bold uppercase mb-1">Total Chequeos</div>
            <div id="stat-total" class="text-2xl font-bold text-blue-900">0</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-100 text-center">
            <div class="text-green-500 text-xs font-bold uppercase mb-1">Seguros</div>
            <div id="stat-secure" class="text-2xl font-bold text-green-900">0</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100 text-center">
            <div class="text-yellow-500 text-xs font-bold uppercase mb-1">Advertencias</div>
            <div id="stat-warning" class="text-2xl font-bold text-yellow-900">0</div>
        </div>
        <div class="bg-red-50 p-4 rounded-lg border border-red-100 text-center">
            <div class="text-red-500 text-xs font-bold uppercase mb-1">Críticos</div>
            <div id="stat-critical" class="text-2xl font-bold text-red-900">0</div>
        </div>
    </div>

    <!-- Trend Chart -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Tendencia de Seguridad</h3>
        <div class="h-64">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Quick Insights -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Resumen de Escaneo</h3>
            <ul id="vulnerability-list-short" class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                <li class="py-3 text-gray-400 text-center italic">Realiza un escaneo para ver los resultados.</li>
            </ul>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Recomendaciones Top</h3>
            <div id="top-recommendations" class="space-y-4">
                <p class="text-gray-400 italic text-center">No hay recomendaciones disponibles aún.</p>
            </div>
        </div>
    </div>
</div>
