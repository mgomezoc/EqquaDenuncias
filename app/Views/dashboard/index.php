<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Dashboard', 'vista' => 'Dashboard']); ?>

<div class="container mt-5">
    <!-- Filtro de fechas -->
    <form action="<?= base_url('dashboard/filtrar') ?>" method="post" class="mb-4" id="dateFilterForm">
        <div class="row">
            <div class="col-md-3">
                <input type="text" id="startDate" name="start_date" class="form-control" placeholder="Fecha inicio" value="<?= $startDate ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" id="endDate" name="end_date" class="form-control" placeholder="Fecha fin" value="<?= $endDate ?? '' ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" id="filterButton" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <!-- Contadores de denuncias -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Denuncias Nuevas</h5>
                    <h2 id="totalDenunciasNuevas"><?= $totalDenunciasNuevas ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Denuncias en Proceso</h5>
                    <h2 id="totalDenunciasProceso"><?= $totalDenunciasProceso ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Denuncias Recibidas</h5>
                    <h2 id="totalDenunciasRecibidas"><?= $totalDenunciasRecibidas ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Gráfico de Mes de recepción de denuncia -->
        <div class="col-md-12">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Mes de recepción de denuncia</h4>
                <canvas id="chartMesDenuncias"></canvas>
                <p class="text-center mt-3" id="totalMesDenuncias"></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Estatus de Denuncias</h4>
                <canvas id="chartEstatusDenuncias"></canvas>
                <p class="text-center mt-3" id="totalEstatus"></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias Anónimas</h4>
                <canvas id="chartDenunciasAnonimas"></canvas>
                <p class="text-center mt-3" id="totalDenunciasAnonimas"></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Tipo de Denunciante</h4>
                <canvas id="chartDenunciante"></canvas>
                <p class="text-center mt-3" id="totalDenunciasPorMedio"></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Conocimiento del Incidente</h4>
                <canvas id="chartConocimiento"></canvas>
                <p class="text-center mt-3" id="totalConocimiento"></p>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Departamento</h4>
                <div class="table-responsive">
                    <table id="tableDenunciasDepartamento" class="table table-eqqua table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Departamento</th>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <th><?= esc($sucursal) ?></th>
                                <?php endforeach; ?>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($denunciasPorDepto as $departamento => $totales): ?>
                                <tr>
                                    <td><?= esc($departamento) ?></td>
                                    <?php foreach ($sucursales as $sucursal): ?>
                                        <td><?= esc($totales[$sucursal] ?? 0) ?></td>
                                    <?php endforeach; ?>
                                    <td><?= esc($totales['Total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card mb-4 p-4 shadow-sm">
                <h4 class="text-center mb-3">Denuncias por Sucursal</h4>
                <canvas id="chartSucursalesDenuncias"></canvas>
                <p class="text-center mt-3" id="totalSucursales"></p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="<?= base_url('assets/js/dashboard_admin.js') ?>"></script>
<?= $this->endSection() ?>