<div class="wrap alma-security-wrap pr-4 font-sans text-gray-900 bg-gray-50 min-h-screen">
    <!-- Compact Header Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-8 mb-6">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between col-span-1 md:col-span-2">
            <div class="flex items-center gap-6">
                <div class="relative w-20 h-20">
                    <canvas id="scoreChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span id="scoreText" class="text-xl font-black text-gray-900 leading-none">--</span>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-none mb-1">Alma <span class="text-blue-600">Security</span></h1>
                    <div id="risk-level" class="inline-block text-[9px] font-black px-2 py-0.5 rounded-lg bg-gray-100 text-gray-400 uppercase tracking-widest">--</div>
                </div>
            </div>
            <div class="flex gap-3">
                <button id="run-scan-btn" class="bg-gray-900 hover:bg-blue-600 text-white font-black py-3 px-6 rounded-2xl text-[10px] uppercase tracking-widest transition-all shadow-lg active:scale-95">RE-SCAN</button>
                <button id="delete-data-btn" class="hidden bg-red-50 hover:bg-red-100 text-red-600 font-black py-3 px-4 rounded-2xl text-[10px] uppercase transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        </div>

        <div id="summary-warning" class="bg-white p-6 rounded-3xl shadow-sm border border-yellow-100 flex items-center gap-4 group hover:bg-yellow-50/30 transition-colors">
            <div class="p-3 bg-yellow-100 text-yellow-600 rounded-2xl group-hover:scale-110 transition-transform">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div>
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Warnings</span>
                <span id="warning-count" class="text-2xl font-black text-gray-900 leading-none">0</span>
            </div>
        </div>

        <div id="summary-error" class="bg-white p-6 rounded-3xl shadow-sm border border-red-100 flex items-center gap-4 group hover:bg-red-50/30 transition-colors">
            <div class="p-3 bg-red-100 text-red-600 rounded-2xl group-hover:scale-110 transition-transform">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Errores</span>
                <span id="error-count" class="text-2xl font-black text-gray-900 leading-none">0</span>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">
        <!-- Left Column: Unified Checks Table -->
        <div class="lg:col-span-8 flex flex-col gap-4">
            <!-- Filter Bar -->
            <div class="bg-white px-6 py-4 rounded-[2rem] shadow-sm border border-gray-100 flex items-center justify-between sticky top-4 z-10">
                <div class="flex items-center gap-2">
                    <button data-filter="all" class="status-filter-btn px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all bg-gray-900 text-white">Todos</button>
                    <button data-filter="secure" class="status-filter-btn px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">OK</button>
                    <button data-filter="warning" class="status-filter-btn px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">Warning</button>
                    <button data-filter="critical" class="status-filter-btn px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-400 hover:text-gray-900">Error</button>
                </div>
                <div id="scan-loader" class="hidden flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full animate-pulse border border-blue-100">
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
                    <span class="text-[9px] font-black uppercase tracking-widest">Scanning...</span>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden flex-grow min-h-[500px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table-fixed">
                        <thead>
                            <tr class="bg-gray-50/50 text-[9px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-6 py-4 w-2/5">Verificación</th>
                                <th class="px-6 py-4 w-1/5 text-center">Estado</th>
                                <th class="px-6 py-4 w-1/5 text-center">Riesgo</th>
                                <th class="px-6 py-4 w-1/5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 font-medium text-xs">
                            <?php
                            $all_checks_list = array(
                            'wp_update', 'debug_mode', 'xmlrpc', 'sensitive_files', 'server_config',
                            'plugins_detailed', 'themes_detailed', 'php_version', 'https', 'file_permissions',
                            'directory_listing', 'admin_users', 'admin_count', 'malware_scan', 'login_attempts',
                            'hidden_login', 'db_prefix', 'db_remote', 'core_integrity', 'firewall_detect',
                            'security_headers', 'backup_detect', 'plugins_update', 'themes_update'
                            );

                            foreach ($all_checks_list as $check_id) :
                            ?>
                            <tr id="check-row-<?php echo $check_id; ?>" class="group/row hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 check-name">--</div>
                                    <div class="text-[8px] text-gray-400 uppercase mt-0.5 tracking-tighter opacity-0 group-hover/row:opacity-100 transition-opacity"><?php echo $check_id; ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="check-status-badge inline-flex items-center px-3 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-gray-100 text-gray-400 border border-gray-200/30">
                                        --
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="check-risk-badge inline-flex items-center px-2 py-0.5 rounded-lg text-[8px] font-bold uppercase bg-gray-50 text-gray-400">
                                        --
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button data-check="<?php echo $check_id; ?>" class="view-check-history-btn p-1.5 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-500 transition-all active:scale-90" title="Ver Detalles">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                        <button data-check="<?php echo $check_id; ?>" class="run-individual-scan-btn bg-gray-900 hover:bg-blue-600 text-white font-black p-1.5 rounded-lg transition-all shadow-sm active:scale-90" title="Scan">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        </button>
                                    </div>
                                </td>
                                <!-- Hidden extra data for JS search/filtering -->
                                <td class="hidden check-description">--</td>
                                <td class="hidden check-recommendation">--</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Evolution and Distribution -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <!-- Evolution Chart -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest">Evolución</h3>
                    <div id="last-scan-info" class="text-[8px] font-black text-gray-400 uppercase">--</div>
                </div>
                <div class="h-32">
                    <canvas id="evolutionChart"></canvas>
                </div>
            </div>

            <!-- Distribution Chart -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex-grow">
                <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest mb-6">Hallazgos</h3>
                <div class="h-40 relative">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- Alert Container (Small version) -->
            <div id="alma-alerts-container" class="space-y-3">
                <!-- Alerts load here -->
            </div>
        </div>
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
