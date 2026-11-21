

import Alpine from 'alpinejs';
import Toastify from 'toastify-js';
import 'font-awesome/css/font-awesome.min.css';
import 'toastify-js/src/toastify.css';

window.Alpine = Alpine;
window.CityCareToast = (message, type = 'success') => Toastify({
    text: String(message),
    duration: 5000,
    close: true,
    gravity: 'top',
    position: 'right',
    stopOnFocus: true,
    className: `citycare-toast citycare-toast-${type}`,
}).showToast();

Alpine.start();

if (window.CityCareDashboard) {
    import('chart.js/auto').then(({ default: Chart }) => {
        Chart.defaults.font.family = 'Figtree, sans-serif';
        Chart.defaults.color = '#64748b';

        const dashboard = window.CityCareDashboard;
        const palette = ['#0f766e', '#0891b2', '#10b981', '#f59e0b', '#e11d48'];
        const axisOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } },
            },
        };

        const appointmentTrend = document.getElementById('appointment-trend-chart');
        if (appointmentTrend) {
            new Chart(appointmentTrend, {
                type: 'line',
                data: {
                    labels: dashboard.appointmentTrend.labels,
                    datasets: [{
                        label: 'Appointments',
                        data: dashboard.appointmentTrend.values,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.12)',
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#0f766e',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: axisOptions,
            });
        }

        const appointmentStatuses = document.getElementById('appointment-status-chart');
        if (appointmentStatuses) {
            new Chart(appointmentStatuses, {
                type: 'doughnut',
                data: {
                    labels: dashboard.appointmentStatuses.labels,
                    datasets: [{
                        data: dashboard.appointmentStatuses.values,
                        backgroundColor: palette,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    },
                },
            });
        }

        const monthlyCollections = document.getElementById('monthly-collections-chart');
        if (monthlyCollections && dashboard.monthlyCollections) {
            new Chart(monthlyCollections, {
                type: 'bar',
                data: {
                    labels: dashboard.monthlyCollections.labels,
                    datasets: [{
                        label: 'Received (UGX)',
                        data: dashboard.monthlyCollections.values,
                        backgroundColor: '#14b8a6',
                        borderRadius: 7,
                        maxBarThickness: 34,
                    }],
                },
                options: {
                    ...axisOptions,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `UGX ${new Intl.NumberFormat('en-UG').format(context.parsed.y)}`,
                            },
                        },
                    },
                    scales: {
                        ...axisOptions.scales,
                        y: {
                            ...axisOptions.scales.y,
                            ticks: { callback: value => new Intl.NumberFormat('en-UG', { notation: 'compact' }).format(value) },
                        },
                    },
                },
            });
        }
    }).catch(() => window.CityCareToast('Dashboard charts could not be loaded.', 'error'));
}
