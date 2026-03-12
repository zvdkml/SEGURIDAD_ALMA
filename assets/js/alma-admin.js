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
            runScan();
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

    function runScan() {
        $('#run-scan-btn').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
        $('#scan-loader').removeClass('hidden');

        $.ajax({
            url: alma_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'alma_run_scan',
                nonce: alma_ajax.nonce
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
                $('#run-scan-btn').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                $('#scan-loader').addClass('hidden');
            }
        });
    }

    function updateUI(data) {
        // Update Chart
        initChart(data.score);

        // Update Risk Level
        const riskEl = $('#risk-level');
        riskEl.text(data.level);
        riskEl.removeClass('bg-gray-200 text-gray-600 bg-green-100 text-green-800 bg-yellow-100 text-yellow-800 bg-red-100 text-red-800');

        if (data.score >= 80) riskEl.addClass('bg-green-100 text-green-800');
        else if (data.score >= 50) riskEl.addClass('bg-yellow-100 text-yellow-800');
        else riskEl.addClass('bg-red-100 text-red-800');

        // Update Progress Bar
        const progress = $('#score-progress');
        progress.css('width', data.score + '%');
        progress.removeClass('bg-gray-400 bg-green-500 bg-yellow-500 bg-red-500');
        if (data.score >= 80) progress.addClass('bg-green-500');
        else if (data.score >= 50) progress.addClass('bg-yellow-500');
        else progress.addClass('bg-red-500');

        // Update Last Scan
        const date = new Date(data.timestamp * 1000);
        $('#last-scan-info').text('Último escaneo: ' + date.toLocaleString());

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
        $('#stat-warning').text(warning);
        $('#stat-critical').text(critical);

        // Update Distribution Chart
        initDistributionChart(data.counts || {});

        // Update Vulnerability List
        let listHtml = '';
        let tableHtml = '';
        let recHtml = '';

        Object.values(data.vulnerabilities).forEach(v => {
            const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-yellow-500' : 'text-red-500');
            const riskColor = v.risk === 'Crítico' ? 'text-red-600 font-bold' : (v.risk === 'Medio' ? 'text-yellow-600' : 'text-blue-600');

            const alertIcon = `
                <svg class="h-5 w-5 ${statusColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    ${v.status === 'secure'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />'}
                </svg>`;

            listHtml += `
                <li class="py-3 flex justify-between items-center">
                    <div class="flex items-center">
                        ${alertIcon}
                        <span class="ml-2 text-gray-700 font-medium">${v.name}</span>
                    </div>
                    <span class="text-xs font-bold ${riskColor}">${v.risk}</span>
                </li>
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

            if (v.status !== 'secure' && recHtml.split('</div>').length < 5) {
                recHtml += `
                    <div class="p-3 bg-gray-50 border-l-4 border-yellow-400 rounded">
                        <p class="text-sm font-bold text-gray-800">${v.name}</p>
                        <p class="text-xs text-gray-600">${v.recommendation}</p>
                    </div>
                `;
            }
        });

        $('#vulnerability-list-short').html(listHtml);
        $('#vulnerabilities-table-body').html(tableHtml);
        $('#top-recommendations').html(recHtml || '<p class="text-green-500 italic text-center">¡Buen trabajo! No hay recomendaciones urgentes.</p>');
    }

})(jQuery);
