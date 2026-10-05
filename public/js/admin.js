document.addEventListener('DOMContentLoaded', function() {
    iniciarDashboardAdmin();
});

function iniciarDashboardAdmin() {
    const state = document.getElementById('adminDashboardData');

    if (!state || typeof Chart === 'undefined') {
        return;
    }

    const data = JSON.parse(state.textContent);

    Chart.defaults.color = '#d7d0df';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

    criarGraficoLinha(
        'adminUsersChart',
        data.usuarios.labels,
        data.usuarios.valores
    );

    criarGraficoBarras(
        'adminGamesChart',
        data.jogos.labels,
        data.jogos.valores
    );

    criarGraficoBarras(
        'adminPlatformsChart',
        data.plataformas.labels,
        data.plataformas.valores
    );
}

function criarGraficoLinha(id, labels, valores) {
    const canvas = document.getElementById(id);

    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                borderColor: '#e83bbd',
                backgroundColor: 'rgba(232, 59, 189, 0.16)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function criarGraficoBarras(id, labels, valores) {
    const canvas = document.getElementById(id);

    if (!canvas) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: 'rgba(232, 59, 189, 0.72)',
                borderColor: '#e83bbd',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
