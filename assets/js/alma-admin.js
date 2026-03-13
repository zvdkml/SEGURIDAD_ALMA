(function($) {
    'use strict';

    let scoreChart = null;
    let distributionChart = null;

    $(document).ready(function() {
        if (alma_ajax.scan_index !== -1 && alma_ajax.history && alma_ajax.history[alma_ajax.scan_index]) {
            updateUI(alma_ajax.history[alma_ajax.scan_index], 'history');
        } else if (alma_ajax.latest_scan) {
            updateUI(alma_ajax.latest_scan);
        } else {
            initChart(0);
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_scan') === '1') {
            runScan('all');
        }

        $('#run-scan-btn').on('click', function() {
            runScan('all');
        });

        $(document).on('click', '.run-specific-scan-btn', function() {
            const type = $(this).data('type');
            runScan(type);
        });

        $(document).on('click', '.view-scan-detail', function() {
            const index = $(this).data('index');
            window.location.href = '?page=alma-security&scan_index=' + index;
        });
    });

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
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderRadius: 10,
                    barThickness: 30
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
                    x: { display: false, beginAtZero: true },
                    y: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' } }
                    }
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
                    backgroundColor: [color, '#F3F4F6'],
                    borderWidth: 0,
                }]
            },
            options: {
                cutout: '85%',
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
        const btn = type === 'all' ? $('#run-scan-btn') : $(`.run-specific-scan-btn[data-type="${type}"]`);
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
                    if (!alma_ajax.history) alma_ajax.history = [];
                    alma_ajax.history.unshift(response.data);
                    updateUI(response.data, type);
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

    function updateUI(data, type = 'all') {
        initChart(data.score);

        const riskEl = $('#risk-level');
        riskEl.text(data.level);
        riskEl.removeClass('bg-green-100 text-green-800 bg-orange-100 text-orange-800 bg-red-100 text-red-800');
        if (data.score >= 80) riskEl.addClass('bg-green-100 text-green-800');
        else if (data.score >= 50) riskEl.addClass('bg-orange-100 text-orange-800');
        else riskEl.addClass('bg-red-100 text-red-800');

        const date = new Date(data.timestamp * 1000);
        $('#last-scan-info').text('Último análisis: ' + date.toLocaleString());

        initDistributionChart(data.counts || {});

        // Populate category tables
        if (type === 'all' || type === 'history') {
            const categories = ['wp', 'plugins', 'themes', 'server', 'users', 'malware'];
            categories.forEach(cat => {
                populateCategoryTable(cat, data.vulnerabilities);
            });
        } else {
            populateCategoryTable(type, data.vulnerabilities);
        }
    }

    function populateCategoryTable(category, vulns) {
        const mapping = {
            wp: ['wp_update', 'debug_mode', 'xmlrpc', 'sensitive_files', 'server_config'],
            plugins: ['plugins_detailed'],
            themes: ['themes_detailed'],
            server: ['php_version', 'security_headers', 'https', 'file_permissions', 'directory_listing'],
            users: ['admin_users', 'admin_count', 'login_attempts'],
            malware: ['malware_scan']
        };

        let html = '';
        const checkIds = mapping[category] || [];

        checkIds.forEach(id => {
            const v = vulns[id];
            if (!v) return;

            // Handle sub-data if it exists (like for plugins_detailed or themes_detailed)
            if (v.is_detailed) {
                Object.values(v.data).forEach(item => {
                    html += buildTableRow(item.name, item.status, 'Versión: ' + item.version, 'Revisar actualizaciones.');
                });
            } else if (v.is_malware) {
                 if (v.findings && v.findings.length > 0) {
                     v.findings.forEach(f => {
                         html += buildTableRow(f.file, 'critical', f.issue, 'Eliminar código sospechoso.');
                     });
                 } else {
                     html += buildTableRow(v.name, v.status, v.description, v.recommendation);
                 }
            } else {
                html += buildTableRow(v.name, v.status, v.description, v.recommendation);
            }
        });

        $('#table-results-' + category).html(html || '<tr><td colspan="4" class="px-10 py-8 text-center text-gray-400">No se encontraron problemas en esta sección.</td></tr>');
    }

    function buildTableRow(name, status, desc, rec) {
        const statusColor = status === 'secure' ? 'text-green-600' : (status === 'warning' ? 'text-orange-600' : 'text-red-600');
        const bgColor = status === 'secure' ? 'bg-green-100' : (status === 'warning' ? 'bg-orange-100' : 'bg-red-100');
        const statusLabel = status === 'secure' ? 'Verde' : (status === 'warning' ? 'Naranja' : 'Rojo');
        const icon = status === 'secure'
            ? '<svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'
            : '<svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>';

        return `
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-10 py-6 text-gray-900 font-bold tracking-tight">${name}</td>
                <td class="px-10 py-6 text-center">
                    <span class="inline-flex items-center px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${bgColor} ${statusColor}">
                        ${icon} ${statusLabel}
                    </span>
                </td>
                <td class="px-10 py-6 text-gray-500 text-sm leading-relaxed max-w-xs">${desc}</td>
                <td class="px-10 py-6">
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-bold text-gray-700 leading-snug">
                        ${rec}
                    </div>
                </td>
            </tr>
        `;
    }

})(jQuery);
