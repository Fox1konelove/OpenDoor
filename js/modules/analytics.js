// modules/analytics.js — построение графиков аналитики через Chart.js
const rub = (v) => `${Number(v || 0).toLocaleString('ru-RU')} ₽`;

function hexToRgba(hex, a) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
}

const PALETTE = ['#1a7a55', '#5fd0a6', '#11342b', '#f4a259', '#e76f51', '#2a9d8f', '#e9c46a', '#8ab17d', '#457b9d', '#a8dadc'];

export function renderCharts(data) {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js не загружен');
        return;
    }

    // Топ-10 продаваемых (бар)
    const top = (data.topProducts || []).slice(0, 10);
    const topCanvas = document.getElementById('chartTopProducts');
    if (topCanvas) {
        const ctx = topCanvas.getContext('2d');
        if (window._chartTop) window._chartTop.destroy();
        window._chartTop = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: top.map(p => p.title),
                datasets: [{
                    label: 'Продано (шт.)',
                    data: top.map(p => Number(p.total_qty)),
                    backgroundColor: PALETTE,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // Продажи по категориям (pie/doughnut)
    const byCat = (data.byCategory || []);
    const catCanvas = document.getElementById('chartByCategory');
    if (catCanvas) {
        const ctx = catCanvas.getContext('2d');
        if (window._chartCat) window._chartCat.destroy();
        window._chartCat = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: byCat.map(c => c.category),
                datasets: [{
                    data: byCat.map(c => Number(c.total_revenue)),
                    backgroundColor: byCat.map((_, i) => PALETTE[i % PALETTE.length]),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => `${c.label}: ${rub(c.parsed)}` } }
                }
            }
        });
    }

    // Выручка по датам (line)
    const rev = (data.revenueByDate || []);
    const revCanvas = document.getElementById('chartRevenue');
    if (revCanvas) {
        const ctx = revCanvas.getContext('2d');
        if (window._chartRev) window._chartRev.destroy();
        window._chartRev = new Chart(ctx, {
            type: 'line',
            data: {
                labels: rev.map(r => r.date),
                datasets: [{
                    label: 'Выручка',
                    data: rev.map(r => Number(r.revenue)),
                    borderColor: '#1a7a55',
                    backgroundColor: hexToRgba('#1a7a55', 0.15),
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => rub(c.parsed.y) } }
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => rub(v) } } }
            }
        });
    }
}

export function renderDashboardCharts(data) {
    renderCharts(data);
}
