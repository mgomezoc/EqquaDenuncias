<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container mt-5">

    <!-- Filtro de fechas -->
    <form action="<?= base_url('dashboard/filtrar') ?>" method="post" class="mb-4">
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
                <p class="text-muted text-center">Distribución de las denuncias por su estatus actual</p>
                <canvas id="chartEstatusDenuncias"></canvas>
            </div>
        </div>

        <!-- Gráfico de Denuncias por Departamento -->
        <div class="col-md-4">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Departamento</h4>
                <p class="text-muted text-center">Número de denuncias agrupadas por departamento involucrado</p>
                <canvas id="chartDeptosDenuncias"></canvas>
            </div>
        </div>

        <!-- Gráfico de Conocimiento del Incidente -->
        <div class="col-md-4">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Conocimiento del Incidente</h4>
                <p class="text-muted text-center">Cómo se enteraron los denunciantes del incidente</p>
                <canvas id="chartConocimiento"></canvas>
            </div>
        </div>

        <!-- Gráfico de Denuncias por Sucursal -->
        <div class="col-md-12">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Sucursal</h4>
                <p class="text-muted text-center">Cantidad de denuncias recibidas por sucursal</p>
                <canvas id="chartSucursalesDenuncias"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Scripts de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
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
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
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
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
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
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Denuncias por Sucursal
    const ctxSucursales = document.getElementById('chartSucursalesDenuncias').getContext('2d');
    const sucursalesChart = new Chart(ctxSucursales, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($denunciasPorSucursal, 'nombre')) ?>,
            datasets: [{
                label: 'Denuncias',
                data: <?= json_encode(array_column($denunciasPorSucursal, 'total')) ?>,
                backgroundColor: '#6a5acd',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sucursales'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de Denuncias'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

<?= $this->endSection() ?>