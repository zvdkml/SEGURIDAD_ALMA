<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900 bg-gray-50 min-h-screen">
    <!-- Header Section -->
    <div class="flex justify-between items-center py-10">
        <div>
            <h1 class="text-5xl font-black text-gray-900 tracking-tight leading-none">Security <span class="text-blue-600 font-extrabold italic">Dashboard</span></h1>
            <p class="text-gray-500 mt-3 text-lg font-medium">Panel profesional de monitorización y auditoría de seguridad.</p>
        </div>
        <button id="run-scan-btn" class="group flex items-center bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-10 rounded-2xl shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <svg class="h-6 w-6 mr-3 group-hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            ESCANEAR TODO EL SITIO
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

        <!-- Distribution -->
        <div class="lg:col-span-2 bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-10">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Distribución de Hallazgos</h3>
                <div class="flex space-x-4 text-gray-400 font-bold text-[10px] uppercase">
                    <div class="flex items-center"><span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-1.5"></span> Seguro</div>
                    <div class="flex items-center"><span class="w-2.5 h-2.5 bg-orange-500 rounded-full mr-1.5"></span> Advertencia</div>
                    <div class="flex items-center"><span class="w-2.5 h-2.5 bg-red-500 rounded-full mr-1.5"></span> Crítico</div>
                </div>
            </div>
            <div class="h-48">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Security Sections Grid -->
    <div class="space-y-12 mb-20">
        <?php
        $sections = array(
            'wp'       => array('title' => 'WordPress Security', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'color' => 'blue'),
            'plugins'  => array('title' => 'Plugin Security', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'purple'),
            'themes'   => array('title' => 'Theme Security', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z', 'color' => 'pink'),
            'server'   => array('title' => 'Server Security', 'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2', 'color' => 'green'),
            'users'    => array('title' => 'User Security', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z', 'color' => 'orange'),
            'malware'  => array('title' => 'Malware Scan', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'color' => 'red'),
            'login'    => array('title' => 'Login Security Scan', 'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1', 'color' => 'indigo'),
            'db'       => array('title' => 'Database Security Scan', 'icon' => 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zm4-1h8', 'color' => 'yellow'),
            'file_int' => array('title' => 'File Integrity Scan', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'teal'),
            'firewall' => array('title' => 'Firewall Status', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'cyan'),
            'headers'  => array('title' => 'Security Headers Scan', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', 'color' => 'blue'),
            'backup'   => array('title' => 'Backup Security', 'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2', 'color' => 'gray'),
            'updates'  => array('title' => 'Update Monitor', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'color' => 'blue'),
        );

        foreach ($sections as $key => $data) :
        ?>
        <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-2xl group">
            <!-- Section Header -->
            <div class="p-10 border-b border-gray-50 flex justify-between items-center bg-white group-hover:bg-gray-50/30 transition-colors">
                <div class="flex items-center">
                    <div class="p-5 bg-<?php echo $data['color']; ?>-50 rounded-3xl text-<?php echo $data['color']; ?>-600 mr-8 shadow-sm transition-transform group-hover:scale-110">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="<?php echo $data['icon']; ?>" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-4xl font-black text-gray-900 tracking-tighter"><?php echo $data['title']; ?></h3>
                        <p class="text-gray-400 font-bold text-[10px] uppercase mt-1 tracking-[0.2em]">Auditoría avanzada de <?php echo strtolower($data['title']); ?></p>
                    </div>
                </div>
                <button data-type="<?php echo $key; ?>" class="run-specific-scan-btn bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-10 rounded-2xl transition-all shadow-xl shadow-gray-200 hover:shadow-blue-200 uppercase text-xs tracking-widest">
                    ESCANEAR SECCIÓN
                </button>
            </div>

            <!-- Results Table -->
            <div class="p-4">
                <div class="overflow-x-auto rounded-[2.5rem] border border-gray-50">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                            <tr>
                                <th class="px-10 py-8 border-b border-gray-100">Verificación</th>
                                <th class="px-10 py-8 border-b border-gray-100 text-center">Estado</th>
                                <th class="px-10 py-8 border-b border-gray-100">Descripción</th>
                                <th class="px-10 py-8 border-b border-gray-100">Recomendación</th>
                            </tr>
                        </thead>
                        <tbody id="table-results-<?php echo $key; ?>" class="divide-y divide-gray-50 font-medium">
                            <tr>
                                <td colspan="4" class="px-10 py-24 text-center text-gray-300 italic text-xl font-medium tracking-tight">
                                    Listo para iniciar la auditoría de esta sección.
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
