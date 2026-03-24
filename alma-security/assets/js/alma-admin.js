(function($) {
    'use strict';

    let scoreChart = null;
    let distributionChart = null;
    let evolutionChart = null;
    let currentFilter = 'all';

    $(document).ready(function() {
        applyRoleRestrictions(alma_ajax.user_role);

        // Load persisted results if available
        if (alma_ajax.db_results && Object.keys(alma_ajax.db_results).length > 0) {
            Object.keys(alma_ajax.db_results).forEach(checkId => {
                updateCheckUI(checkId, alma_ajax.db_results[checkId]);
            });
        }

        if (alma_ajax.scan_index !== -1 && alma_ajax.history && alma_ajax.history[alma_ajax.scan_index]) {
            updateUI(alma_ajax.history[alma_ajax.scan_index], 'history');
        } else if (alma_ajax.latest_scan) {
            updateUI(alma_ajax.latest_scan);
        } else {
            initChart(0);
        }

        initEvolutionChart(alma_ajax.score_history || []);
        updateAlerts();

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

        $(document).on('click', '.toggle-section-btn', function() {
            const targetId = $(this).data('target');
            toggleSection($('#' + targetId), $(this));
        });

        $(document).on('click', '.run-individual-scan-btn', function() {
            const checkId = $(this).data('check');
            const section = $(this).data('section');
            runIndividualScan(checkId, section, $(this));
        });

        $('#delete-data-btn').on('click', function() {
            if (confirm('¿Estás seguro de que deseas eliminar todos los datos de escaneo?')) {
                deleteScanData();
            }
        });

        $(document).on('click', '.view-check-history-btn', function() {
            const checkId = $(this).data('check');
            showCheckDetails(checkId);
        });

        // Ensure fix buttons are visible on initial load based on data-status
        $('.group\\/row').each(function() {
            const status = $(this).attr('data-status');
            const fixBtn = $(this).find('.fix-check-btn');
            if (status === 'warning' || status === 'critical') {
                fixBtn.removeClass('hidden');
            } else {
                fixBtn.addClass('hidden');
            }
        });

        $('.status-filter-btn').on('click', function() {
            const filter = $(this).data('filter');
            applyFilter(filter);
        });

        $('#close-modal-btn, #close-modal-footer-btn, #modal-overlay').on('click', function() {
            $('#history-modal').addClass('hidden');
        });
    });

    function applyRoleRestrictions(role) {
        const caps = alma_ajax.user_caps || {};

        if ( ! caps.can_scan ) {
            $('#run-scan-btn, .run-specific-scan-btn, .run-individual-scan-btn').remove();
        }

        if ( ! caps.can_fix ) {
            $('.fix-check-btn').remove();
        }

        if ( caps.can_admin ) {
            $('#delete-data-btn').removeClass('hidden');
        } else {
            $('#delete-data-btn').remove();
        }

        // Check if we are in frontend
        if ($('#alma-frontend-dashboard').length > 0) {
            $('.alma-security-wrap').removeClass('pr-4');
        }
    }

    function deleteScanData() {
        const btn = $('#delete-data-btn');
        btn.prop('disabled', true).addClass('opacity-50');

        $.post(alma_ajax.ajax_url, {
            action: 'alma_delete_scan_data',
            nonce: alma_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }).always(function() {
            btn.prop('disabled', false).removeClass('opacity-50');
        });
    }

    function toggleSection(container, btn, forceOpen = false) {
        if (forceOpen) {
            container.addClass('is-open').css('max-height', '2000px');
            btn.addClass('is-active text-blue-600 bg-blue-50');
            btn.find('svg').addClass('rotate-180');
            return;
        }

        if (container.hasClass('is-open')) {
            container.removeClass('is-open').css('max-height', '0');
            btn.removeClass('is-active text-blue-600 bg-blue-50');
            btn.find('svg').removeClass('rotate-180');
        } else {
            container.addClass('is-open').css('max-height', '2000px');
            btn.addClass('is-active text-blue-600 bg-blue-50');
            btn.find('svg').addClass('rotate-180');
        }
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
                labels: ['Seguro', 'Advertencia', 'Crítico'],
                datasets: [{
                    data: [counts.secure || 0, counts.warning || 0, counts.critical || 0],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderRadius: 8,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                layout: {
                    padding: { top: 0, bottom: 0, left: 0, right: 10 }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        display: false,
                        beginAtZero: true,
                        grid: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            font: { size: 10, weight: '900' },
                            color: '#9CA3AF',
                            padding: 10
                        }
                    }
                }
            }
        });
    }

    function initEvolutionChart(history) {
        const ctx = document.getElementById('evolutionChart');
        if (!ctx) return;

        if (evolutionChart) {
            evolutionChart.destroy();
        }

        const labels = history.map(item => new Date(item.scanned_at).toLocaleDateString());
        const scores = history.map(item => item.score);

        evolutionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score %',
                    data: scores,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3B82F6',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#9CA3AF' }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#F3F4F6' },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#9CA3AF' }
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

    function runIndividualScan(checkId, type, btn) {
        btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed').html('<div class="flex items-center gap-2 justify-center"><div class="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div> SCAN</div>');

        $.ajax({
            url: alma_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'alma_run_scan',
                nonce: alma_ajax.nonce,
                type: type,
                check_id: checkId
            },
            success: function(response) {
                if (response.success && response.data.vulnerabilities[checkId]) {
                    updateCheckUI(checkId, response.data.vulnerabilities[checkId]);
                } else {
                    alert('Error al escanear: ' + (response.data || 'Respuesta inválida'));
                }
            },
            error: function() {
                alert('Ocurrió un error al procesar el escaneo individual.');
            },
            complete: function() {
                btn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed').text('RE-SCAN');
            }
        });
    }

    function updateCheckUI(checkId, data) {
        const row = $(`#check-row-${checkId}`);
        if (!row.length) return;

        row.find('.check-name').text(data.name);

        if (checkId === 'plugin_vulnerabilities' && data.is_vulnerabilities && data.data) {
            let html = '<div class="space-y-2 plugin-vulnerabilities-list">';
            data.data.forEach(v => {
                html += `
                    <div class="flex items-center text-[10px] bg-red-50/50 p-2 rounded-xl border border-red-100/50">
                        <span class="font-black text-red-700 mr-2 uppercase tracking-tighter">${v.name}</span>
                        <span class="w-1 h-1 bg-red-300 rounded-full mr-2"></span>
                        <span class="font-bold text-red-600 mr-2">${v.risk}</span>
                        <span class="text-red-500 italic">${v.issue}</span>
                    </div>
                `;
            });
            html += '</div>';
            row.find('.check-description').html(html).removeClass('italic');
        } else {
            row.find('.check-description').text(data.description).removeClass('italic');
        }

        const badge = row.find('.check-status-badge');
        badge.removeClass('bg-gray-100 text-gray-400 bg-gray-200 text-gray-500 bg-green-100 text-green-800 bg-yellow-100 text-yellow-800 bg-red-100 text-red-800 shadow-sm border border-gray-200/50');

        let label = 'Desconocido';
        if (data.status === 'secure') {
            badge.addClass('bg-green-100 text-green-800 border-green-200');
            label = 'Seguro';
        } else if (data.status === 'warning') {
            badge.addClass('bg-yellow-100 text-yellow-800 border-yellow-200');
            label = 'Advertencia';
        } else if (data.status === 'critical') {
            badge.addClass('bg-red-100 text-red-800 border-red-200');
            label = 'Crítico';
        }
        badge.text(label);

        const riskBadge = row.find('.check-risk-badge');
        riskBadge.removeClass('bg-gray-50 text-gray-400 bg-red-100 text-red-800 bg-orange-100 text-orange-800 bg-blue-100 text-blue-800 border-red-200 border-orange-200 border-blue-200');

        const risk = data.risk_level || data.risk || 'Bajo';
        riskBadge.text(risk);
        if (risk === 'Crítico' || risk === 'Alto') riskBadge.addClass('bg-red-100 text-red-800 border-red-200');
        else if (risk === 'Medio') riskBadge.addClass('bg-orange-100 text-orange-800 border-orange-200');
        else riskBadge.addClass('bg-blue-100 text-blue-800 border-blue-200');

        const fixBtn = row.find('.fix-check-btn');
        if (data.status === 'warning' || data.status === 'critical') {
            fixBtn.removeClass('hidden');
        } else {
            fixBtn.addClass('hidden');
        }

        if (data.recommendation) {
            row.find('.check-recommendation').text(data.recommendation);
            row.find('.check-recommendation-box').removeClass('hidden');
        }

        row.attr('data-status', data.status);
        applyFilter(currentFilter);
        updateAlerts();
    }

    function showCheckDetails(checkId) {
        const modal = $('#history-modal');
        const content = $('#modal-content');
        const row = $(`#check-row-${checkId}`);

        $('#modal-description').text(row.find('.check-description').text());
        $('#modal-recommendation').text(row.find('.check-recommendation').text() || 'No hay recomendaciones adicionales.');

        modal.removeClass('hidden');
        content.empty().append($('<div class="flex justify-center py-10"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-gray-900"></div></div>'));

        $.get(alma_ajax.ajax_url, {
            action: 'alma_get_check_history',
            nonce: alma_ajax.nonce,
            check_id: checkId
        }, function(response) {
            if (response.success) {
                content.empty();
                if (response.data.length === 0) {
                    content.append($('<div class="text-center py-10 text-gray-400 italic">No hay historial para esta verificación.</div>'));
                } else {
                    response.data.forEach(item => {
                        const statusColor = item.status === 'secure' ? 'text-green-600' : (item.status === 'warning' ? 'text-yellow-600' : 'text-red-600');
                        const statusLabel = item.status === 'secure' ? 'SEGURO' : (item.status === 'warning' ? 'ADVERTENCIA' : 'CRÍTICO');

                        const historyItem = $(`
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-black uppercase tracking-widest ${statusColor}">${statusLabel}</span>
                                    <span class="text-[10px] font-bold text-gray-400"></span>
                                </div>
                                <p class="text-sm text-gray-700 font-medium"></p>
                            </div>
                        `);
                        historyItem.find('.text-gray-400').text(item.scanned_at);
                        historyItem.find('.text-gray-700').text(item.result);
                        content.append(historyItem);
                    });
                }
            } else {
                content.html('<div class="text-red-500 font-bold p-4"></div>').find('div').text('Error: ' + response.data);
            }
        });
    }

    function applyFilter(filter) {
        currentFilter = filter;
        $('.status-filter-btn').removeClass('bg-gray-900 text-white').addClass('text-gray-400 hover:text-gray-900');
        $(`.status-filter-btn[data-filter="${filter}"]`).removeClass('text-gray-400 hover:text-gray-900').addClass('bg-gray-900 text-white');

        $('.group\\/row').each(function() {
            const status = $(this).attr('data-status');
            if (filter === 'all' || status === filter) {
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
            }
        });

        // Hide empty sections
        $('.section-content-wrapper').each(function() {
            const visibleRows = $(this).find('.group\\/row:not(.hidden)').length;
            const section = $(this).closest('.group');
            if (visibleRows === 0 && filter !== 'all') {
                section.addClass('opacity-30 grayscale');
            } else {
                section.removeClass('opacity-30 grayscale');
            }
        });
    }

    function updateAlerts() {
        const container = $('#alma-alerts-container');
        container.empty();

        const findings = [];
        $('.group\\/row').each(function() {
            const status = $(this).attr('data-status');
            if (status === 'critical' || status === 'warning') {
                findings.push({
                    name: $(this).find('.check-name').text(),
                    status: status,
                    desc: $(this).find('.check-description').text()
                });
            }
        });

        if (findings.length > 0) {
            findings.slice(0, 3).forEach(alert => {
                const colorClass = alert.status === 'critical' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800';
                const icon = alert.status === 'critical'
                    ? '<svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>'
                    : '<svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';

                container.append(`
                    <div class="flex items-center p-5 rounded-2xl border-2 ${colorClass} animate-bounce-slow">
                        ${icon}
                        <div class="flex-1">
                            <span class="text-[10px] font-black uppercase tracking-widest block mb-1">Alerta de Seguridad</span>
                            <p class="text-sm font-bold tracking-tight">${alert.name}: <span class="font-medium opacity-80">${alert.desc}</span></p>
                        </div>
                    </div>
                `);
            });
        }
    }

    function runScan(type = 'all') {
        const btn = type === 'all' ? $('#run-scan-btn') : $(`.run-specific-scan-btn[data-type="${type}"]`);
        btn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');

        if (type !== 'all') {
            const toggleBtn = $(`.toggle-section-btn[data-target="section-${type}"]`);
            toggleSection($(`#section-${type}`), toggleBtn, true);
        }

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
        if (data.score_history) {
            initEvolutionChart(data.score_history);
        }

        const riskEl = $('#risk-level');
        riskEl.text(data.level);
        riskEl.removeClass('bg-green-100 text-green-800 bg-orange-100 text-orange-800 bg-red-100 text-red-800');
        if (data.score >= 80) riskEl.addClass('bg-green-100 text-green-800');
        else if (data.score >= 50) riskEl.addClass('bg-orange-100 text-orange-800');
        else riskEl.addClass('bg-red-100 text-red-800');

        const date = new Date(data.timestamp * 1000);
        $('#last-scan-info').text('Último análisis: ' + date.toLocaleString());

        initDistributionChart(data.counts || {});

        if (data.vulnerabilities) {
            Object.keys(data.vulnerabilities).forEach(checkId => {
                updateCheckUI(checkId, data.vulnerabilities[checkId]);
            });
        }
    }

    // Global refresh function for synchronized updates
    window.almaRefreshDashboard = function() {
        console.log('Refreshing dashboard data...');
        location.reload();
    };

})(jQuery);
