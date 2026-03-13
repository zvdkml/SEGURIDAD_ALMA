(function($) {
    'use strict';

    let scoreChart = null;
    let trendChart = null;
    let distributionChart = null;

    $(document).ready(function() {
        if (alma_ajax.scan_index !== -1 && alma_ajax.history && alma_ajax.history[alma_ajax.scan_index]) {
            updateUI(alma_ajax.history[alma_ajax.scan_index]);
        } else if (alma_ajax.latest_scan) {
            updateUI(alma_ajax.latest_scan);
        } else {
            initChart(0);
        }
        initTrendChart();

        $('#run-scan-btn').on('click', function() {
            runScan('all');
        });

        $('#run-specific-scan').on('click', function() {
            const type = $(this).data('type');
            runScan(type);
        });

        $(document).on('click', '.view-scan-detail', function() {
            const index = $(this).data('index');
            window.location.href = '?page=alma-security&scan_index=' + index;
        });
    });

    function initTrendChart() {
        const ctx = document.getElementById('trendChart');
        if (!ctx || !alma_ajax.history || alma_ajax.history.length === 0) return;

        if (trendChart) {
            trendChart.destroy();
        }

        const history = [...alma_ajax.history].reverse();
        const labels = history.map(h => new Date(h.timestamp * 1000).toLocaleDateString());
        const scores = history.map(h => h.score);

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Security Score',
                    data: scores,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function initDistributionChart(counts) {
        const ctx = document.getElementById('distributionChart');
        if (!ctx) return;

        if (distributionChart) {
            distributionChart.destroy();
        }

        distributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Bajo', 'Medio', 'Crítico'],
                datasets: [{
                    data: [counts.bajo || 0, counts.medio || 0, counts.critico || 0],
                    backgroundColor: ['#3B82F6', '#F59E0B', '#EF4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    function initChart(score) {
        const ctx = document.getElementById('scoreChart');
        if (!ctx) return;

        if (scoreChart) {
            scoreChart.destroy();
        }

        const color = score >= 80 ? '#10B981' : (score >= 50 ? '#F59E0B' : '#EF4444');

        scoreChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [color, '#E5E7EB'],
                    borderWidth: 0,
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });

        $('#scoreText').text(score + '%').css('color', color);
    }

    function runScan(type = 'all') {
        const btn = type === 'all' ? $('#run-scan-btn') : $('#run-specific-scan');
        btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        $('#scan-loader').removeClass('hidden');

        $.ajax({
            url: alma_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'alma_run_scan',
                nonce: alma_ajax.nonce,
                type: type
            },
            success: function(response) {
                if (response.success) {
                    // Update global history for trend chart
                    if (!alma_ajax.history) alma_ajax.history = [];
                    alma_ajax.history.unshift(response.data);
                    updateUI(response.data);
                    initTrendChart();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Ocurrió un error al procesar el escaneo.');
            },
            complete: function() {
                btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                $('#scan-loader').addClass('hidden');
            }
        });
    }

    function updateUI(data) {
        const hasScoreChart = !!document.getElementById('scoreChart');
        const hasSpecificContainer = !!document.getElementById('scan-results-container');
        const hasVulnTable = !!document.getElementById('vulnerabilities-table-body');

        if (hasSpecificContainer) {
            renderSpecificResults(data);
        }

        if (!hasScoreChart) {
            if (hasVulnTable) {
                // Populate vulnerabilities table if we are on that page
                let tableHtml = '';
                Object.values(data.vulnerabilities).forEach(v => {
                    const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-yellow-500' : 'text-red-500');
                    const riskColor = v.risk === 'Crítico' ? 'text-red-600 font-bold' : (v.risk === 'Medio' ? 'text-yellow-600' : 'text-blue-600');
                    const alertIcon = `<svg class="h-4 w-4 ${statusColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        ${v.status === 'secure' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />'}
                    </svg>`;
                    tableHtml += `
                        <tr class="${v.status !== 'secure' ? 'bg-red-50' : ''}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><div class="flex items-center">${alertIcon}<span class="ml-2">${v.name}</span></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ${riskColor}">${v.risk}</td>
                            <td class="px-6 py-4 text-sm text-gray-500"><div class="font-bold text-gray-700 mb-1">${v.status.toUpperCase()}</div><div class="text-xs leading-tight">${v.description}</div></td>
                            <td class="px-6 py-4 text-sm text-blue-700 font-medium">${v.recommendation}</td>
                        </tr>`;
                });
                $('#vulnerabilities-table-body').html(tableHtml);
            }
            return;
        }

        // Update Chart
        initChart(data.score);

        // Update Risk Level
        const riskEl = $('#risk-level');
        riskEl.text(data.level);
        riskEl.removeClass('bg-gray-200 text-gray-500 bg-green-100 text-green-800 bg-yellow-100 text-yellow-800 bg-red-100 text-red-800');

        if (data.score >= 80) riskEl.addClass('bg-green-100 text-green-800');
        else if (data.score >= 50) riskEl.addClass('bg-yellow-100 text-yellow-800');
        else riskEl.addClass('bg-red-100 text-red-800');

        // Update Progress Bar
        const progress = $('#score-progress');
        progress.css('width', data.score + '%');
        progress.removeClass('bg-gray-300 bg-green-500 bg-yellow-500 bg-red-500');
        if (data.score >= 80) progress.addClass('bg-green-500');
        else if (data.score >= 50) progress.addClass('bg-yellow-500');
        else progress.addClass('bg-red-500');

        // Update Last Scan
        const date = new Date(data.timestamp * 1000);
        $('#last-scan-info').text('Último análisis: ' + date.toLocaleString());

        // Update Stats Summary
        let total = 0;
        let secure = 0;
        let warning = 0;
        let critical = 0;

        Object.values(data.vulnerabilities).forEach(v => {
            total++;
            if (v.status === 'secure') secure++;
            else if (v.status === 'warning') warning++;
            else if (v.status === 'critical') critical++;
        });

        $('#stat-total').text(total);
        $('#stat-secure').text(secure);
        $('#stat-critical-total').text(warning + critical);

        // Update Distribution Chart
        initDistributionChart(data.counts || {});

        // Update System Status Cards
        updateSystemStatusCards(data.vulnerabilities);

        // Update Vulnerability List & Critical Alerts
        let listHtml = '';
        let tableHtml = '';
        let criticalHtml = '';
        let criticalCount = 0;

        Object.values(data.vulnerabilities).forEach(v => {
            const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-yellow-500' : 'text-red-500');
            const riskColor = v.risk === 'Crítico' ? 'text-red-600 font-bold' : (v.risk === 'Medio' ? 'text-yellow-600' : 'text-blue-600');

            const alertIcon = `
                <svg class="h-4 w-4 ${statusColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    ${v.status === 'secure'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />'}
                </svg>`;

            listHtml += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-8 py-4 whitespace-nowrap text-sm font-bold text-gray-700">${v.name}</td>
                    <td class="px-8 py-4 whitespace-nowrap text-center">
                        <span class="text-[10px] font-black uppercase tracking-widest ${riskColor}">${v.risk}</span>
                    </td>
                    <td class="px-8 py-4 whitespace-nowrap flex justify-end">
                        ${alertIcon}
                    </td>
                </tr>
            `;

            tableHtml += `
                <tr class="${v.status !== 'secure' ? 'bg-red-50' : ''}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <div class="flex items-center">
                            ${alertIcon}
                            <span class="ml-2">${v.name}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${riskColor}">${v.risk}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="font-bold text-gray-700 mb-1">${v.status.toUpperCase()}</div>
                        <div class="text-xs leading-tight">${v.description}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-blue-700 font-medium">${v.recommendation}</td>
                </tr>
            `;

            if (v.status === 'critical' || (v.risk === 'Crítico' && v.status !== 'secure')) {
                criticalCount++;
                criticalHtml += `
                    <div class="flex items-start p-4 bg-white rounded-2xl shadow-sm border-l-4 border-red-500">
                        <svg class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-black text-gray-800 uppercase tracking-tight">${v.name}</p>
                            <p class="text-xs text-red-600 mt-1">${v.description} <span class="font-bold underline ml-1">${v.recommendation}</span></p>
                        </div>
                    </div>
                `;
            }
        });

        // Toggle Critical Section
        if (criticalCount > 0) {
            $('#critical-issues-section').removeClass('hidden');
            $('#critical-issues-container').html(criticalHtml);
        } else {
            $('#critical-issues-section').addClass('hidden');
        }

        $('#vulnerability-list-short').html(listHtml || '<tr><td colspan="3" class="px-8 py-12 text-center text-gray-300 italic">Análisis completado sin riesgos inmediatos.</td></tr>');
        $('#vulnerabilities-table-body').html(tableHtml);

        // Update Mini History
        updateMiniHistory();
    }

    function updateSystemStatusCards(vulns) {
        const checks = {
            wp: ['wp_update'],
            plugins: ['plugins_update', 'abandoned_plugins'],
            security: ['xmlrpc', 'debug_mode', 'sensitive_files', 'file_permissions', 'https', 'security_headers', 'directory_listing'],
            users: ['admin_users', 'login_attempts']
        };

        Object.keys(checks).forEach(key => {
            let status = 'secure';
            let label = 'Seguro';

            checks[key].forEach(checkId => {
                const check = vulns[checkId];
                if (check) {
                    if (check.status === 'critical') status = 'critical';
                    else if (check.status === 'warning' && status !== 'critical') status = 'warning';
                }
            });

            const badge = $('#status-badge-' + key);
            const text = $('#status-text-' + key);

            badge.removeClass('bg-gray-300 bg-green-500 bg-yellow-500 bg-red-500');
            text.removeClass('text-gray-800 text-green-600 text-yellow-600 text-red-600');

            if (status === 'secure') {
                badge.addClass('bg-green-500');
                text.addClass('text-green-600').text('Protegido');
            } else if (status === 'warning') {
                badge.addClass('bg-yellow-500');
                text.addClass('text-yellow-600').text('Atención');
            } else {
                badge.addClass('bg-red-500');
                text.addClass('text-red-600').text('Vulnerable');
            }
        });
    }

    function renderSpecificResults(data) {
        // Check if we have detailed data
        const detailed = Object.values(data.vulnerabilities).find(v => v.is_detailed || v.is_malware);

        if (detailed) {
            if (detailed.name.includes('Plugins')) {
                renderPluginSpecificResults(detailed.data);
            } else if (detailed.name.includes('Temas')) {
                renderThemeSpecificResults(detailed.data);
            } else if (detailed.is_malware) {
                renderMalwareResults(detailed);
            }
            return;
        }

        let html = '<div class="grid grid-cols-1 gap-6">';
        Object.values(data.vulnerabilities).forEach(v => {
            const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-yellow-500' : 'text-red-500');
            const alertIcon = `<svg class="h-8 w-8 ${statusColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                ${v.status === 'secure' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />'}
            </svg>`;

            html += `
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-start">
                    <div class="mr-6">${alertIcon}</div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start">
                            <h4 class="text-xl font-bold text-gray-800">${v.name}</h4>
                            <span class="text-xs font-bold uppercase px-3 py-1 rounded-full bg-gray-100 text-gray-500">${v.risk}</span>
                        </div>
                        <p class="text-gray-600 mt-2">${v.description}</p>
                        <div class="mt-4 p-4 bg-blue-50 rounded-2xl">
                            <span class="text-xs font-bold text-blue-600 uppercase">Recomendación</span>
                            <p class="text-sm text-blue-800 mt-1 font-medium">${v.recommendation}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        $('#scan-results-container').html(html);
    }

    function renderThemeSpecificResults(themes) {
        let html = `
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-6">Tema</th>
                            <th class="px-8 py-6">Estado</th>
                            <th class="px-8 py-6">Versión</th>
                            <th class="px-8 py-6">Última Act.</th>
                            <th class="px-8 py-6 text-right">Riesgo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 font-medium">
        `;

        Object.values(themes).forEach(t => {
            const statusColor = t.status === 'secure' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
            const riskColor = t.risk === 'Crítico' ? 'text-red-600' : (t.risk === 'Medio' ? 'text-yellow-600' : 'text-blue-600');

            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-8 py-6">
                        <div class="text-gray-800 font-bold">${t.name}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5">${t.active ? 'Activo' : 'Inactivo'}</div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter ${statusColor}">
                            ${t.status === 'secure' ? 'Seguro' : 'Atención'}
                        </span>
                    </td>
                    <td class="px-8 py-6 text-sm text-gray-500">${t.version}</td>
                    <td class="px-8 py-6 text-sm text-gray-500">${t.last_upd}</td>
                    <td class="px-8 py-6 text-right">
                        <span class="text-xs font-black uppercase tracking-widest ${riskColor}">${t.risk}</span>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        $('#scan-results-container').html(html);
    }

    function renderMalwareResults(malware) {
        let findingsHtml = '';
        if (malware.findings && malware.findings.length > 0) {
            malware.findings.forEach(f => {
                findingsHtml += `
                    <tr class="bg-red-50 hover:bg-red-100 transition-colors">
                        <td class="px-8 py-6">
                            <div class="text-red-800 font-bold font-mono text-xs">${f.file}</div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-gray-700 text-sm">${f.issue}</div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <span class="text-xs font-black uppercase tracking-widest text-red-600">CRÍTICO</span>
                        </td>
                    </tr>
                `;
            });
        } else {
            findingsHtml = '<tr><td colspan="3" class="px-8 py-20 text-center text-green-500 font-bold">¡Genial! No se detectaron archivos sospechosos.</td></tr>';
        }

        let html = `
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-6">Archivo</th>
                            <th class="px-8 py-6">Problema Detectado</th>
                            <th class="px-8 py-6 text-right">Riesgo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 font-medium">
                        ${findingsHtml}
                    </tbody>
                </table>
            </div>
            <div class="mt-8 p-8 bg-blue-600 rounded-[2rem] text-white shadow-lg shadow-blue-100">
                <h4 class="text-xl font-black uppercase tracking-tight mb-2">Recomendación Pro</h4>
                <p class="text-blue-100">${malware.recommendation}</p>
            </div>
        `;
        $('#scan-results-container').html(html);
    }

    function renderPluginSpecificResults(plugins) {
        let html = `
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-6">Plugin</th>
                            <th class="px-8 py-6">Estado</th>
                            <th class="px-8 py-6">Versión</th>
                            <th class="px-8 py-6">Última Act.</th>
                            <th class="px-8 py-6 text-right">Riesgo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 font-medium">
        `;

        Object.values(plugins).forEach(p => {
            const statusColor = p.status === 'secure' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
            const riskColor = p.risk === 'Crítico' ? 'text-red-600' : (p.risk === 'Medio' ? 'text-yellow-600' : 'text-blue-600');

            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-8 py-6">
                        <div class="text-gray-800 font-bold">${p.name}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5">${p.active ? 'Activo' : 'Desactivado'}</div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter ${statusColor}">
                            ${p.status === 'secure' ? 'Seguro' : 'Atención'}
                        </span>
                    </td>
                    <td class="px-8 py-6 text-sm text-gray-500">${p.version}</td>
                    <td class="px-8 py-6 text-sm text-gray-500">${p.last_upd}</td>
                    <td class="px-8 py-6 text-right">
                        <span class="text-xs font-black uppercase tracking-widest ${riskColor}">${p.risk}</span>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        $('#scan-results-container').html(html);
    }

    function updateMiniHistory() {
        if (!alma_ajax.history || alma_ajax.history.length === 0) return;

        let html = '';
        alma_ajax.history.slice(0, 5).forEach((h, i) => {
            const date = new Date(h.timestamp * 1000);
            const color = h.score >= 80 ? 'text-green-500' : (h.score >= 50 ? 'text-yellow-500' : 'text-red-500');

            html += `
                <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer view-scan-detail" data-index="${i}">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center mr-4 text-xs font-black ${color}">
                            ${h.score}%
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-700">${date.toLocaleDateString()}</p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">${date.toLocaleTimeString()}</p>
                        </div>
                    </div>
                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            `;
        });
        $('#history-mini-list').html(html);
    }

})(jQuery);
