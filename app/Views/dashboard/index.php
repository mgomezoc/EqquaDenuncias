<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Dashboard', 'vista' => 'Dashboard']); ?>

<div class="container mt-5">

    <!-- Filtro de fechas -->
    <form action="<?= base_url('dashboard/filtrar') ?>" method="post" class="mb-4" id="dateFilterForm">
        <div class="row">
            <div class="col-md-4">
                <input type="text" id="startDate" name="start_date" class="form-control" placeholder="Fecha inicio" value="<?= $startDate ?? '' ?>">
            </div>
            <div class="col-md-4">
                <input type="text" id="endDate" name="end_date" class="form-control" placeholder="Fecha fin" value="<?= $endDate ?? '' ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        <!-- Gráfico de Estatus de Denuncias -->
        <div class="col-md-6">
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
        <div class="col-md-6">
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
        <div class="col-md-6">
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

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
    flatpickr("#startDate", {
        dateFormat: "Y-m-d",
        defaultDate: "<?= $startDate ?? 'today' ?>",
        locale: "es"
    });

    flatpickr("#endDate", {
        dateFormat: "Y-m-d",
        defaultDate: "<?= $endDate ?? 'today' ?>",
        locale: "es"
    });

    const totalDenuncias = (dataset) => dataset.reduce((a, b) => a + b, 0);

    // Paleta de colores personalizada
    const colors = ['#f4b400', '#db4437', '#0f9d58', '#4285f4', '#34a853', '#ff6d00', '#ffeb3b', '#1e88e5', '#6a5acd', '#d81b60'];

    // Gráfico de Estatus de Denuncias
    const ctxEstatus = document.getElementById('chartEstatusDenuncias').getContext('2d');
    const estatusChart = new Chart(ctxEstatus, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($estatusDenuncias, 'estatus')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($estatusDenuncias, 'total')) ?>,
                backgroundColor: colors
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left', // Alinear leyenda a la izquierda
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i],
                                boxWidth: 20 // Tamaño de las cajas de color
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

    // Gráfico de Denuncias por Departamento
    const ctxDepto = document.getElementById('chartDeptosDenuncias').getContext('2d');
    const deptoChart = new Chart(ctxDepto, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($denunciasPorDepto, 'departamento')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($denunciasPorDepto, 'total')) ?>,
                backgroundColor: colors
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left', // Alinear leyenda a la izquierda
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i],
                                boxWidth: 20
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
                backgroundColor: colors
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left', // Alinear leyenda a la izquierda
                    labels: {
                        font: {
                            size: 14
                        },
                        generateLabels: function(chart) {
                            const dataset = chart.data.datasets[0];
                            const total = totalDenuncias(dataset.data);
                            return chart.data.labels.map((label, i) => ({
                                text: `${label}: ${dataset.data[i]} (${((dataset.data[i] / total) * 100).toFixed(2)}%)`,
                                fillStyle: dataset.backgroundColor[i],
                                boxWidth: 20
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

    // Obtener la cantidad de sucursales para generar los colores dinámicamente
    const sucursalesCount = <?= count($denunciasPorSucursal) ?>;

    // Ajustar el array de colores para tener suficientes colores para cada barra
    const sucursalColors = colors.slice(0, sucursalesCount);

    const sucursalesChart = new Chart(ctxSucursales, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($denunciasPorSucursal, 'nombre')) ?>,
            datasets: [{
                label: 'Denuncias',
                data: <?= json_encode(array_column($denunciasPorSucursal, 'total')) ?>,
                backgroundColor: sucursalColors, // Asignar un color diferente a cada barra
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    grid: {
                        display: false
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
                        display: false
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
                    position: 'top',
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
                            return `Denuncias: ${value}`;
                        }
                    }
                },
                // Habilitar datalabels para mostrar los totales en la parte superior
                datalabels: {
                    anchor: 'end',
                    align: 'start',
                    color: '#000', // Color del texto
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    formatter: (value) => value, // Mostrar el valor como etiqueta
                }
            }
        },
        plugins: [ChartDataLabels] // Activar el plugin de datalabels
    });
</script>
<?= $this->endSection() ?>