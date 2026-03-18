<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900 bg-gray-50 min-h-screen">
    <!-- Alerts System -->
    <div id="alma-alerts-container" class="pt-10 space-y-4">
        <!-- Dynamic Alerts will appear here -->
    </div>

    <!-- Header Section -->
    <div class="flex justify-between items-center py-10">
        <div>
            <h1 class="text-5xl font-black text-gray-900 tracking-tight leading-none">Security <span class="text-blue-600 font-extrabold italic">Dashboard</span></h1>
            <p class="text-gray-500 mt-3 text-lg font-medium">Panel profesional de monitorización y auditoría de seguridad.</p>
        </div>
        <div class="flex items-center gap-4">
            <button id="delete-data-btn" class="hidden group flex items-center bg-white border-2 border-red-100 hover:bg-red-50 text-red-600 font-black py-4 px-8 rounded-2xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                BORRAR DATOS
            </button>
            <button id="run-scan-btn" class="group flex items-center bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-10 rounded-2xl shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="h-6 w-6 mr-3 group-hover:rotate-180 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                ESCANEAR TODO EL SITIO
            </button>
        </div>
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
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-12">
        <!-- Main Score -->
        <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] mb-8">Security Score</h3>
            <div class="relative w-40 h-40 mb-6">
                <canvas id="scoreChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span id="scoreText" class="text-4xl font-black tracking-tighter">--</span>
                    <span id="risk-level" class="text-[10px] font-black px-3 py-1 rounded-full bg-gray-100 text-gray-500 mt-2 uppercase tracking-widest">--</span>
                </div>
            </div>
            <p id="last-scan-info" class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Sin datos de escaneo</p>
        </div>

        <!-- Evolution Chart -->
        <div class="lg:col-span-2 bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Evolución de Seguridad</h3>
                <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-3 py-1 rounded-full uppercase">Últimos 50 escaneos</span>
            </div>
            <div class="h-40">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>

        <!-- Distribution -->
        <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100 flex flex-col">
            <div class="mb-6 flex justify-between items-start">
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-tight">Hallazgos</h3>
                <div class="flex flex-col gap-1 text-gray-400 font-bold text-[9px] uppercase">
                    <div class="flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span> Seguro</div>
                    <div class="flex items-center"><span class="w-2 h-2 bg-orange-500 rounded-full mr-1.5"></span> Advertencia</div>
                    <div class="flex items-center"><span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span> Crítico</div>
                </div>
            </div>
            <div class="relative flex-grow h-48 min-h-[12rem] w-full">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Global Filter -->
    <div class="mb-10 flex flex-wrap gap-4 items-center">
        <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Filtrar por estado:</span>
        <div class="flex bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm">
            <button data-filter="all" class="status-filter-btn px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all bg-gray-900 text-white">Todos</button>
            <button data-filter="secure" class="status-filter-btn px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">Seguro</button>
            <button data-filter="warning" class="status-filter-btn px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">Advertencia</button>
            <button data-filter="critical" class="status-filter-btn px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">Crítico</button>
        </div>
    </div>

    <!-- Security Sections Grid -->
    <div class="space-y-12 mb-20">
        <?php
        $sections = array(
            'wp'       => array(
                'title' => 'WordPress Security',
                'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                'color' => 'blue',
                'checks' => array('wp_update', 'debug_mode', 'xmlrpc', 'sensitive_files', 'server_config')
            ),
            'plugins'  => array(
                'title' => 'Plugin Security',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'color' => 'purple',
                'checks' => array('plugins_detailed')
            ),
            'themes'   => array(
                'title' => 'Theme Security',
                'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z',
                'color' => 'pink',
                'checks' => array('themes_detailed')
            ),
            'server'   => array(
                'title' => 'Server Security',
                'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2',
                'color' => 'green',
                'checks' => array('php_version', 'https', 'file_permissions', 'directory_listing')
            ),
            'users'    => array(
                'title' => 'User Security',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z',
                'color' => 'orange',
                'checks' => array('admin_users', 'admin_count', 'login_attempts')
            ),
            'malware'  => array(
                'title' => 'Malware Scan',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'color' => 'red',
                'checks' => array('malware_scan')
            ),
            'login'    => array(
                'title' => 'Login Security Scan',
                'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
                'color' => 'indigo',
                'checks' => array('login_attempts', 'hidden_login')
            ),
            'db'       => array(
                'title' => 'Database Security Scan',
                'icon' => 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zm4-1h8',
                'color' => 'yellow',
                'checks' => array('db_prefix', 'db_remote')
            ),
            'file_int' => array(
                'title' => 'File Integrity Scan',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'color' => 'teal',
                'checks' => array('core_integrity')
            ),
            'firewall' => array(
                'title' => 'Firewall Status',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'color' => 'cyan',
                'checks' => array('firewall_detect')
            ),
            'headers'  => array(
                'title' => 'Security Headers Scan',
                'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
                'color' => 'blue',
                'checks' => array('security_headers')
            ),
            'backup'   => array(
                'title' => 'Backup Security',
                'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2',
                'color' => 'gray',
                'checks' => array('backup_detect')
            ),
            'updates'  => array(
                'title' => 'Update Monitor',
                'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                'color' => 'blue',
                'checks' => array('wp_update', 'plugins_update', 'themes_update')
            ),
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
                <div class="flex items-center space-x-4">
                    <button data-type="<?php echo $key; ?>" class="run-specific-scan-btn bg-gray-900 hover:bg-blue-600 text-white font-black py-4 px-8 rounded-2xl transition-all shadow-xl shadow-gray-200 hover:shadow-blue-200 uppercase text-[10px] tracking-widest">
                        ESCANEAR
                    </button>
                    <button class="toggle-section-btn p-3 bg-gray-100 hover:bg-gray-200 rounded-2xl transition-all duration-300 group/toggle" data-target="section-<?php echo $key; ?>">
                        <svg class="h-5 w-5 text-gray-600 transform transition-transform duration-300 group-[.is-active]/toggle:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Results Table Content -->
            <div id="section-<?php echo $key; ?>" class="section-content-wrapper overflow-hidden transition-all duration-500 max-h-0">
                <div class="p-8 pt-0">
                    <div class="overflow-x-auto rounded-[2.5rem] border border-gray-100 bg-white shadow-inner">
                        <table class="w-full text-left border-collapse table-fixed">
                            <thead>
                                <tr class="bg-gray-50/80 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                    <th class="px-8 py-5 w-3/12">Nombre de la verificación</th>
                                    <th class="px-8 py-5 w-2/12 text-center">Estado</th>
                                    <th class="px-8 py-5 w-3/12">Resultado</th>
                                    <th class="px-8 py-5 w-2/12 text-center">Riesgo</th>
                                    <th class="px-8 py-5 w-2/12 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium">
                                <?php foreach ( $data['checks'] as $check_id ) : ?>
                                <tr id="check-row-<?php echo $check_id; ?>" class="group/row hover:bg-gray-50/30 transition-colors" data-status="pending">
                                    <td class="px-8 py-6">
                                        <div class="font-bold text-gray-800 check-name">--</div>
                                        <div class="text-[9px] text-gray-400 uppercase tracking-tighter mt-1 font-black opacity-0 group-hover/row:opacity-100 transition-opacity">ID: <?php echo $check_id; ?></div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="check-status-badge inline-flex items-center px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-gray-100 text-gray-400 shadow-sm border border-gray-200/50">
                                            Pendiente
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs text-gray-500 leading-relaxed check-description italic line-clamp-2">No se ha realizado el escaneo.</p>
                                        <div class="mt-2 hidden check-recommendation-box">
                                            <p class="text-[9px] text-blue-600 font-bold check-recommendation bg-blue-50/50 px-2 py-1 rounded-lg border border-blue-100/50 inline-block"></p>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="check-risk-badge inline-flex items-center px-3 py-0.5 rounded-lg text-[9px] font-bold uppercase bg-gray-50 text-gray-400 border border-gray-100">
                                            --
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end items-center gap-3">
                                            <button data-check="<?php echo $check_id; ?>" class="view-check-history-btn p-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-500 hover:text-blue-600 rounded-xl transition-all shadow-sm hover:shadow-md" title="Ver detalles">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </button>
                                            <button data-check="<?php echo $check_id; ?>" data-section="<?php echo $key; ?>" class="run-individual-scan-btn bg-gray-900 hover:bg-blue-600 text-white font-black py-2.5 px-6 rounded-xl transition-all shadow-lg shadow-gray-200 hover:shadow-blue-200 text-[10px] uppercase tracking-widest whitespace-nowrap active:scale-95">
                                                Scan
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- History Modal -->
    <div id="history-modal" class="hidden fixed inset-0 z-[100000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div id="modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-[2rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-8 pt-8 pb-4 sm:p-10 sm:pb-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-3xl font-black text-gray-900 tracking-tighter" id="modal-title">Detalles de Verificación</h3>
                                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div id="modal-info" class="mb-8 p-6 bg-blue-50 rounded-3xl border border-blue-100">
                                <h4 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-2">Descripción y Recomendación</h4>
                                <p id="modal-description" class="text-sm text-blue-900 font-medium leading-relaxed"></p>
                                <p id="modal-recommendation" class="text-xs text-blue-700 mt-4 font-bold"></p>
                            </div>
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Historial de escaneos</h4>
                            <div id="modal-content" class="space-y-4 max-h-[40vh] overflow-y-auto pr-2">
                                <!-- Loaded dynamically -->
                                <div class="text-center py-10 text-gray-400 italic">Cargando historial...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-10 py-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="close-modal-footer-btn" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-8 py-3 bg-gray-900 text-base font-bold text-white hover:bg-gray-800 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        CERRAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
