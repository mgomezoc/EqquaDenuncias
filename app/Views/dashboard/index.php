<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Dashboard', 'vista' => 'Dashboard']); ?>

<div class="container mt-5">
    <!-- Filtro de fechas -->
    <form action="<?= base_url('dashboard/filtrar') ?>" method="post" class="mb-4" id="dateFilterForm">
        <div class="row g-3">
            <!-- Filtros de Fecha -->
            <div class="col-md-2">
                <input type="text" id="startDate" name="start_date" class="form-control" placeholder="Fecha inicio" value="<?= $startDate ?? '' ?>">
            </div>
            <div class="col-md-2">
                <input type="text" id="endDate" name="end_date" class="form-control" placeholder="Fecha fin" value="<?= $endDate ?? '' ?>">
            </div>
            <!-- Filtro de Cliente -->
            <div class="col-md-2">
                <select id="clienteFilter" name="cliente" class="form-select select2">
                    <option selected disabled>Cliente</option>
                    <option value="">TODOS</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>"><?= $cliente['nombre_empresa'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Filtro de Sucursal -->
            <div class="col-md-2">
                <select id="sucursalFilter" name="sucursal" class="form-select select2">
                    <option selected disabled>Sucursal</option>
                    <option value="">TODAS</option>

                    <?php foreach ($sucursales as $sucursal): ?>
                        <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Filtro de Departamento -->
            <div class="col-md-2">
                <select id="departamentoFilter" name="departamento" class="form-select select2">
                    <option selected disabled>Departamento</option>
                    <option value="">TODOS</option>
                    <?php foreach ($departamentos as $departamento): ?>
                        <option value="<?= $departamento['id'] ?>"><?= $departamento['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Filtro de Anónimo -->
            <div class="col-md-2">
                <select id="anonimoFilter" name="anonimo" class="form-select select2">
                    <option selected disabled>Anónimo</option>
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                    <option value="1381609">TODOS</option>

                </select>
            </div>
            <!-- Botón de Filtrar -->
            <div class="col-md-1">
                <button type="submit" id="filterButton" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <!-- Botón de Reset -->
            <div class="col-md-1">
                <button type="button" id="resetButton" class="btn btn-secondary w-100">Reset</button>
            </div>
        </div>
    </form>

    <!-- Contadores de denuncias -->
    <div class="card custom-card overflow-hidden">
        <div class="row">
            <!-- Denuncias Nuevas -->
            <div class="col-lg-4 border-end">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 fw-semibold text-muted">Denuncias Nuevas</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasNuevas"><?= $totalDenunciasNuevas ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-primary p-4 bg-primary-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza denuncias en estatus de: Recepción">
                                <i class="fa fa-folder-plus"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denuncias en Proceso -->
            <div class="col-lg-4 border-end">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 fw-semibold text-muted">Denuncias en Proceso</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasProceso"><?= $totalDenunciasProceso ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-secondary p-4 bg-secondary-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza denuncias en estatus de: Clasificada, Revisada por Calidad, Liberada al Cliente, En Revisión por Cliente">
                                <i class="fa fa-sync"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denuncias Recibidas -->
            <div class="col-lg-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 fw-semibold text-muted">Denuncias Recibidas</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasRecibidas"><?= $totalDenunciasRecibidas ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-danger p-4 bg-danger-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza todas las denuncias en estatus: Recepción, Clasificada, Revisada por Calidad, Liberada al Cliente, En Revisión por Cliente, Cerrada">
                                <i class="fa fa-inbox"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Gráficos -->
    <div class="row g-3">
        <!-- Gráfico de Mes de recepción de denuncia -->
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0">Mes de recepción de denuncia</div>
                    <div class="ms-auto">
                        <a href="javascript:void(0);" class="btn btn-sm border-0 text-dark fs-13 fw-semibold">
                            Total: <span id="totalMesDenuncias"></span>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="chartMesDenuncias"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0">Estatus de Denuncias</div>
                </div>
                <div class="card-body">
                    <canvas id="chartEstatusDenuncias"></canvas>
                    <p class="text-center mt-3" id="totalEstatus"></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0">Denuncias Anónimas</div>
                </div>
                <div class="card-body">
                    <canvas id="chartDenunciasAnonimas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0">Tipo de Denunciante</div>
                </div>
                <div class="card-body">
                    <canvas id="chartDenunciante"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0">Conocimiento del Incidente</div>
                </div>
                <div class="card-body">
                    <canvas id="chartConocimiento"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-3 mb-sm-0">Denuncias por Departamento</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableDenunciasDepartamento" class="table table-eqqua table-sm table-striped table-bordered">
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-3 mb-sm-0">Denuncias por Sucursal</div>
                </div>
                <div class="card-body">
                    <canvas id="chartSucursalesDenuncias" style="min-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="<?= base_url('assets/js/dashboard_admin.js') ?>"></script>
<?= $this->endSection() ?>