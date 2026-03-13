(function($) {
    'use strict';

    let scoreChart = null;
    let trendChart = null;
    let distributionChart = null;

    $(document).ready(function() {
        if (alma_ajax.latest_scan) {
            updateUI(alma_ajax.latest_scan);
        } else {
            initChart(0);
        }
        initTrendChart();

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

        $('#close-details').on('click', function() {
            $('#detailed-results-section').addClass('hidden');
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
                    y: { beginAtZero: true, max: 100 },
                    x: { display: false }
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
            type: 'doughnut',
            data: {
                labels: ['Bajo', 'Medio', 'Crítico'],
                datasets: [{
                    data: [counts.bajo || 0, counts.medio || 0, counts.critico || 0],
                    backgroundColor: ['#3B82F6', '#F59E0B', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'right' }
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

    function updateUI(data, type = 'all') {
        if (type === 'all') {
            initChart(data.score);

            const riskEl = $('#risk-level');
            riskEl.text(data.level);
            riskEl.removeClass('bg-green-100 text-green-800 bg-orange-100 text-orange-800 bg-red-100 text-red-800');
            if (data.score >= 80) riskEl.addClass('bg-green-100 text-green-800');
            else if (data.score >= 50) riskEl.addClass('bg-orange-100 text-orange-800');
            else riskEl.addClass('bg-red-100 text-red-800');

            $('#score-progress').css('width', data.score + '%').removeClass('bg-green-500 bg-orange-500 bg-red-500');
            if (data.score >= 80) $('#score-progress').addClass('bg-green-500');
            else if (data.score >= 50) $('#score-progress').addClass('bg-orange-500');
            else $('#score-progress').addClass('bg-red-500');

            const date = new Date(data.timestamp * 1000);
            $('#last-scan-info').text('Último análisis completo: ' + date.toLocaleString());

            initDistributionChart(data.counts || {});

            // Distribute results to cards
            distributeResultsToCards(data.vulnerabilities);
        } else {
            // Update only the specific card results and the global score (simulated)
            updateSpecificCard(type, data);
        }
    }

    function distributeResultsToCards(vulns) {
        const mapping = {
            wp: ['wp_update', 'debug_mode', 'xmlrpc', 'sensitive_files', 'server_config'],
            plugins: ['plugins_detailed'],
            themes: ['themes_detailed'],
            server: ['php_version', 'security_headers', 'https', 'file_permissions', 'directory_listing'],
            users: ['admin_users', 'admin_count', 'login_attempts'],
            malware: ['malware_scan']
        };

        Object.keys(mapping).forEach(key => {
            let html = '';
            let status = 'secure';
            let issues = 0;

            mapping[key].forEach(id => {
                const v = vulns[id];
                if (v) {
                    if (v.status !== 'secure') {
                        issues++;
                        if (v.status === 'critical') status = 'critical';
                        else if (status !== 'critical') status = 'warning';
                    }

                    const dotColor = v.status === 'secure' ? 'bg-green-500' : (v.status === 'warning' ? 'bg-orange-500' : 'bg-red-500');
                    html += `<div class="flex items-center text-[10px] font-bold text-gray-600"><span class="w-1.5 h-1.5 rounded-full ${dotColor} mr-2"></span>${v.name}</div>`;
                }
            });

            const badge = $('#status-badge-' + key);
            badge.removeClass('bg-green-100 text-green-700 bg-orange-100 text-orange-700 bg-red-100 text-red-700 bg-gray-100 text-gray-400');
            if (status === 'secure') badge.addClass('bg-green-100 text-green-700').text('Protegido');
            else if (status === 'warning') badge.addClass('bg-orange-100 text-orange-700').text(issues + ' Avisos');
            else badge.addClass('bg-red-100 text-red-700').text(issues + ' Críticos');

            $('#results-' + key).html(html || '<p class="text-xs text-gray-300 italic">Sin datos.</p>');
        });
    }

    function updateSpecificCard(type, data) {
        distributeResultsToCards(data.vulnerabilities);
        renderDetailedResults(data.vulnerabilities);
    }

    function renderDetailedResults(vulns) {
        let html = '';
        Object.values(vulns).forEach(v => {
            if (v.is_detailed || v.is_malware) return; // Skip complex results in generic detail list

            const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-orange-500' : 'text-red-500');
            const bgColor = v.status === 'secure' ? 'bg-green-50' : (v.status === 'warning' ? 'bg-orange-50' : 'bg-red-50');
            const borderColor = v.status === 'secure' ? 'border-green-100' : (v.status === 'warning' ? 'border-orange-100' : 'border-red-100');
            const statusLabel = v.status === 'secure' ? 'Verde' : (v.status === 'warning' ? 'Naranja' : 'Rojo');

            html += `
                <div class="bg-white p-6 rounded-3xl shadow-sm border-2 ${borderColor}">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xl font-black text-gray-900">${v.name}</h4>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${bgColor} ${statusColor}">${statusLabel}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">${v.description}</p>
                    <div class="p-4 ${bgColor} rounded-2xl border border-white">
                        <p class="text-xs text-gray-800 font-bold">${v.recommendation}</p>
                    </div>
                </div>
            `;
        });

        $('#detailed-results-container').html(html);
        $('#detailed-results-section').removeClass('hidden');

        // Scroll to details
        $('html, body').animate({
            scrollTop: $("#detailed-results-section").offset().top - 50
        }, 500);
    }

})(jQuery);
