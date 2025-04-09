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
                    <option value="">TODOS</option>

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
            <div class="col-lg-3 border-end denuncias-nuevas">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 text-muted titulo-dashboard">Nuevas</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasNuevas"><?= $totalDenunciasNuevas ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-primary p-4 bg-primary-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza denuncias en estatus de: Recepción">
                                <i class="fa-solid fa-folder-plus"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denuncias en Proceso -->
            <div class="col-lg-3 border-end denuncias-en-proceso">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 text-muted titulo-dashboard">En Proceso</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasProceso"><?= $totalDenunciasProceso ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-warning p-4 bg-warning-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza denuncias en estatus de: Clasificada, Revisada por Calidad, Liberada al Cliente, En Revisión por Cliente">
                                <i class="fa-solid fa-spinner"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denuncias Cerradas -->
            <div class="col-lg-3 border-end denuncias-cerradas">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 text-muted titulo-dashboard">Cerradas</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasCerradas"><?= $totalDenunciasCerradas ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-success p-4 bg-success-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza denuncias en estatus: Cerrada">
                                <i class="fa-solid fa-check-circle"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denuncias Totales -->
            <div class="col-lg-3 denuncias-totales">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <p class="mb-0 text-muted titulo-dashboard">Totales</p>
                            <h3 class="mt-2 mb-1 fw-semibold" id="totalDenunciasTotales"><?= $totalDenunciasTotales ?></h3>
                        </div>
                        <div class="col mt-3 col-auto">
                            <span class="avatar text-info p-4 bg-info-transparent fs-24 rounded-circle text-center"
                                data-bs-toggle="tooltip"
                                title="Contabiliza todas las denuncias registradas">
                                <i class="fa-solid fa-list"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Filtro de año para la gráfica de Mes de Recepción de Denuncia -->
    <div class="row g-3">
        <div class="col-md-3">
            <label for="yearFilter" class="form-label">Seleccionar Año:</label>
            <select id="yearFilter" class="form-select">
                <?php for ($i = date('Y'); $i >= 2023; $i--): ?>
                    <option value="<?= $i ?>" <?= ($i == $currentYear) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- Gráfico de Mes de Recepción de Denuncia -->
    <div class="col-md-12">
        <div class="card custom-card">
            <div class="card-header border-bottom d-block d-sm-flex">
                <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Mes de Recepción de Denuncia</div>
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

    <div class="col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title titulo-dashboard">Denuncias por Categoría y Subcategoría</div>
            </div>
            <div class="card-body">
                <table id="tableCategoriasDenuncias" class="table table-striped"></table>
            </div>
        </div>
    </div>


    <!-- Gráficos -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card custom-card min-height">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Estatus de Denuncias</div>
                </div>
                <div class="card-body">
                    <canvas id="chartEstatusDenuncias"></canvas>
                    <p class="text-center mt-3" id="totalEstatus"></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card min-height">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Denuncias Anónimas</div>
                </div>
                <div class="card-body">
                    <canvas id="chartDenunciasAnonimas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card min-height">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Canal de Denuncia</div>
                </div>
                <div class="card-body">
                    <canvas id="chartDenunciante"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card custom-card min-height">
                <div class="card-header border-bottom d-block d-sm-flex">
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">¿Cómo se enteró del incidente?</div>
                </div>
                <div class="card-body">
                    <canvas id="chartConocimiento"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Denuncias por Departamento</div>
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
                    <div class="card-title mb-3 mb-sm-0 titulo-dashboard">Denuncias por Sucursal</div>
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
<script src="<?= base_url('assets/js/dashboard_admin.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>