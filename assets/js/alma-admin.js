(function($) {
    'use strict';

    let scoreChart = null;

    $(document).ready(function() {
        if (alma_ajax.latest_scan) {
            updateUI(alma_ajax.latest_scan);
        } else {
            initChart(0);
        }

        $('#run-scan-btn').on('click', function() {
            runScan();
        });

        $(document).on('click', '.view-scan-detail', function() {
            const index = $(this).data('index');
            const history = alma_ajax.history || [];
            if (history[index]) {
                updateUI(history[index]);
                // Navigate to dashboard if on history page
                if (window.location.href.indexOf('alma-history') !== -1) {
                    window.location.href = '?page=alma-security';
                }
            }
        });
    });

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
                    updateUI(response.data);
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

        // Update Vulnerability List
        let criticalCount = 0;
        let listHtml = '';
        let tableHtml = '';
        let recHtml = '';

        Object.values(data.vulnerabilities).forEach(v => {
            if (v.status === 'critical') criticalCount++;

            const statusColor = v.status === 'secure' ? 'text-green-500' : (v.status === 'warning' ? 'text-yellow-500' : 'text-red-500');
            const statusIcon = v.status === 'secure' ? '✓' : '!';

            listHtml += `
                <li class="py-3 flex justify-between items-center">
                    <span class="text-gray-700 font-medium">${v.name}</span>
                    <span class="font-bold ${statusColor}">${statusIcon}</span>
                </li>
            `;

            tableHtml += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${v.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${v.risk}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${v.status === 'secure' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${v.status}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">${v.recommendation}</td>
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

        $('#critical-count').text(criticalCount);
        $('#vulnerability-list-short').html(listHtml);
        $('#vulnerabilities-table-body').html(tableHtml);
        $('#top-recommendations').html(recHtml || '<p class="text-green-500 italic text-center">¡Buen trabajo! No hay recomendaciones urgentes.</p>');
    }

})(jQuery);
