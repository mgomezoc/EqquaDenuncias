<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard del Cliente<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-5">

    <!-- Filtro de fechas -->
    <form action="<?= base_url('cliente/dashboard/filtrar') ?>" method="post" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <input type="date" name="start_date" class="form-control" placeholder="Fecha inicio">
            </div>
            <div class="col-md-4">
                <input type="date" name="end_date" class="form-control" placeholder="Fecha fin">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        <!-- Gráfico de Estatus de Denuncias -->
        <div class="col-md-4">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Estatus de Denuncias</h4>
                <canvas id="chartEstatusDenuncias"></canvas>
                <p class="text-center mt-3">
                    Basado en un total de <?= $totalEstatus ?> denuncias.
                    <br>
                    Periodo: <?= $startDate ?? 'Mes actual' ?> - <?= $endDate ?? 'Hoy' ?>
                </p>
            </div>
        </div>

        <!-- Gráfico de Denuncias por Departamento -->
        <div class="col-md-4">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Departamento</h4>
                <canvas id="chartDeptosDenuncias"></canvas>
                <p class="text-center mt-3">
                    Basado en un total de <?= $totalDeptos ?> denuncias.
                    <br>
                    Periodo: <?= $startDate ?? 'Mes actual' ?> - <?= $endDate ?? 'Hoy' ?>
                </p>
            </div>
        </div>

        <!-- Gráfico de Conocimiento del Incidente -->
        <div class="col-md-4">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Conocimiento del Incidente</h4>
                <canvas id="chartConocimiento"></canvas>
                <p class="text-center mt-3">
                    Basado en un total de <?= $totalConocimiento ?> denuncias.
                    <br>
                    Periodo: <?= $startDate ?? 'Mes actual' ?> - <?= $endDate ?? 'Hoy' ?>
                </p>
            </div>
        </div>

        <!-- Gráfico de Denuncias por Sucursal -->
        <div class="col-md-12">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Sucursal</h4>
                <canvas id="chartSucursalesDenuncias"></canvas>
                <p class="text-center mt-3">
                    Basado en un total de <?= $totalSucursales ?> denuncias.
                    <br>
                    Periodo: <?= $startDate ?? 'Mes actual' ?> - <?= $endDate ?? 'Hoy' ?>
                </p>
            </div>
        </div>
    </div>

</div>

<!-- Scripts de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Agregar plugin de Chart.js Data Labels -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>


<script>
    // Función para obtener el total de denuncias (suma de todas las categorías)
    const totalDenuncias = (dataset) => dataset.reduce((a, b) => a + b, 0);

    // Gráfico de Estatus de Denuncias
    const ctxEstatus = document.getElementById('chartEstatusDenuncias').getContext('2d');
    const estatusChart = new Chart(ctxEstatus, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($estatusDenuncias, 'estatus')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($estatusDenuncias, 'total')) ?>,
                backgroundColor: ['#f4b400', '#db4437', '#0f9d58', '#4285f4']
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom', // Mueve los labels debajo del gráfico
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i]
                            }));
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff', // Color de los porcentajes dentro del gráfico
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`; // Muestra solo el porcentaje
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });


    // Gráfico de Denuncias por Departamento
    const ctxDepto = document.getElementById('chartDeptosDenuncias').getContext('2d');
    const deptoChart = new Chart(ctxDepto, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($denunciasPorDepto, 'departamento')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($denunciasPorDepto, 'total')) ?>,
                backgroundColor: ['#f4b400', '#db4437', '#0f9d58', '#4285f4', '#f4b400', '#db4437']
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom', // Mueve los labels debajo del gráfico
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i]
                            }));
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });


    // Gráfico de Conocimiento del Incidente
    const ctxConocimiento = document.getElementById('chartConocimiento').getContext('2d');
    const conocimientoChart = new Chart(ctxConocimiento, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($denunciasPorConocimiento, 'como_se_entero')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($denunciasPorConocimiento, 'total')) ?>,
                backgroundColor: ['#f4b400', '#4285f4']
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom', // Mueve los labels debajo del gráfico
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i]
                            }));
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: {
                        weight: 'bold',
                        size: 12
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });


    // Gráfico de Denuncias por Sucursal
    const ctxSucursales = document.getElementById('chartSucursalesDenuncias').getContext('2d');
    const sucursalesChart = new Chart(ctxSucursales, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($denunciasPorSucursal, 'nombre')) ?>, // Etiquetas (Sucursales)
            datasets: [{
                label: 'Denuncias',
                data: <?= json_encode(array_column($denunciasPorSucursal, 'total')) ?>, // Datos de denuncias por sucursal
                backgroundColor: '#6a5acd', // Color de las barras
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    grid: {
                        display: false // Ocultar la cuadrícula en el eje X
                    },
                    title: {
                        display: true,
                        text: 'Sucursales',
                        font: {
                            size: 16
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false // Ocultar la cuadrícula en el eje Y
                    },
                    title: {
                        display: true,
                        text: 'Número de Denuncias',
                        font: {
                            size: 16
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top', // Leyenda en la parte superior
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const value = tooltipItem.raw;
                            return `Denuncias: ${value}`; // Mostrar el número de denuncias en el tooltip
                        }
                    }
                }
            }
        }
    });
</script>

<?= $this->endSection() ?>