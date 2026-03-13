<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="flex justify-between items-center py-10">
        <div>
            <h1 class="text-5xl font-black text-gray-900 tracking-tight leading-none">Security <span class="text-blue-600 font-extrabold italic">Dashboard</span></h1>
            <p class="text-gray-500 mt-3 text-lg font-medium">Control total de la seguridad de tu WordPress en un solo lugar.</p>
        </div>
        <button id="run-scan-btn" class="group flex items-center bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-10 rounded-2xl shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <svg class="h-6 w-6 mr-3 group-hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            EJECUTAR ANÁLISIS GLOBAL
        </button>
    </div>

    <!-- Loader -->
    <div id="scan-loader" class="hidden mb-10 p-8 bg-white border-2 border-blue-500 rounded-[2rem] shadow-2xl flex items-center justify-center space-x-6 animate-pulse">
        <div class="flex space-x-2">
            <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            <div class="w-3 h-3 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
        </div>
        <span class="text-2xl font-black text-blue-900 uppercase tracking-tighter">Analizando integridad del sistema...</span>
    </div>

    <!-- Overview Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Main Score -->
        <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] mb-8">Security Score</h3>
            <div class="relative w-48 h-48 mb-6">
                <canvas id="scoreChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span id="scoreText" class="text-5xl font-black tracking-tighter">--</span>
                    <span id="risk-level" class="text-[10px] font-black px-3 py-1 rounded-full bg-gray-100 text-gray-500 mt-2 uppercase tracking-widest">--</span>
                </div>
            </div>
            <p id="last-scan-info" class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Sin datos de escaneo</p>
        </div>

        <!-- Trend and Distribution -->
        <div class="lg:col-span-2 bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-10">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Distribución de Hallazgos</h3>
                <div class="flex space-x-4">
                    <div class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span><span class="text-[10px] font-bold text-gray-400 uppercase">Seguro</span></div>
                    <div class="flex items-center"><span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span><span class="text-[10px] font-bold text-gray-400 uppercase">Advertencia</span></div>
                    <div class="flex items-center"><span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span><span class="text-[10px] font-bold text-gray-400 uppercase">Crítico</span></div>
                </div>
            </div>
            <div class="h-48">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Security Sections (Full Width Panels) -->
    <div class="space-y-12 mb-20">
        <?php
        $sections = array(
            'wp'      => array('title' => 'WordPress Security', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'color' => 'blue'),
            'plugins' => array('title' => 'Plugin Security', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'purple'),
            'themes'  => array('title' => 'Theme Security', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z', 'color' => 'pink'),
            'server'  => array('title' => 'Server Security', 'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2', 'color' => 'green'),
            'users'   => array('title' => 'User Security', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z', 'color' => 'orange'),
            'malware' => array('title' => 'Malware Scan', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'red'),
        );

        foreach ($sections as $key => $data) :
        ?>
        <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-xl">
            <!-- Section Header -->
            <div class="p-10 border-b border-gray-50 flex justify-between items-center">
                <div class="flex items-center">
                    <div class="p-5 bg-<?php echo $data['color']; ?>-50 rounded-[1.5rem] text-<?php echo $data['color']; ?>-600 mr-6">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="<?php echo $data['icon']; ?>" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tight"><?php echo $data['title']; ?></h3>
                        <p class="text-gray-400 font-bold text-xs uppercase mt-1 tracking-widest">Análisis de vulnerabilidades de <?php echo strtolower($data['title']); ?></p>
                    </div>
                </div>
                <button data-type="<?php echo $key; ?>" class="run-specific-scan-btn bg-gray-50 hover:bg-gray-900 hover:text-white text-gray-900 font-black py-3 px-8 rounded-2xl transition-all uppercase text-xs tracking-widest">
                    ESCANEAR AHORA
                </button>
            </div>

            <!-- Results Table -->
            <div class="p-2">
                <div class="overflow-x-auto rounded-[2rem]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                            <tr>
                                <th class="px-10 py-6 border-b border-gray-100">Verificación Realizada</th>
                                <th class="px-10 py-6 border-b border-gray-100 text-center">Estado</th>
                                <th class="px-10 py-6 border-b border-gray-100">Descripción del Problema</th>
                                <th class="px-10 py-6 border-b border-gray-100">Recomendación</th>
                            </tr>
                        </thead>
                        <tbody id="table-results-<?php echo $key; ?>" class="divide-y divide-gray-50 font-medium">
                            <tr>
                                <td colspan="4" class="px-10 py-16 text-center text-gray-300 italic text-lg font-medium">
                                    Haz clic en el botón "Escanear Ahora" para analizar esta sección.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
