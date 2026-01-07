<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php echo view('partials/_pageHeader', ['controlador' => $controlador, 'vista' => $vista]); ?>

<div class="container-fluid py-3">
    <div class="row">
        <!-- Formulario principal -->
        <div class="col-lg-8">
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-robot me-2 text-primary"></i>Generar Reporte con IA
                    </h5>
                    <a href="<?= base_url('reportes-ia') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <form id="frmGenerarReporte" method="post" autocomplete="off">
                        <?= csrf_field() ?>

                        <!-- Cliente -->
                        <div class="mb-3">
                            <label for="id_cliente" class="form-label">Cliente <span class="text-danger">*</span></label>
                            <?php if (!empty($es_cliente) && !empty($id_cliente_fijo)): ?>
                                <input type="hidden" name="id_cliente" value="<?= $id_cliente_fijo ?>">
                                <input type="text" class="form-control" value="<?= esc($clientes[0]['nombre_empresa'] ?? 'Cliente') ?>" disabled>
                            <?php else: ?>
                                <select name="id_cliente" id="id_cliente" class="form-select select2" data-placeholder="Selecciona un cliente" required>
                                    <option value=""></option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>"><?= esc($cliente['nombre_empresa']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Tipo y Periodo -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_reporte" class="form-label">Tipo de Reporte <span class="text-danger">*</span></label>
                                <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                                    <option value="mensual" selected>Mensual</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="anual">Anual</option>
                                </select>
                                <small class="text-muted">Cambia el tipo para ver periodos sugeridos.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="periodo_rapido" class="form-label">
                                    Periodo sugerido
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">Auto</span>
                                </label>
                                <div class="input-group">
                                    <select id="periodo_rapido" class="form-select">
                                        <option value="">Selecciona...</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btnRefrescarPeriodos" title="Refrescar periodos">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Selecciona un periodo rápido o define fechas manualmente.</small>
                            </div>
                        </div>

                        <!-- Fechas -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                                <input type="text" name="fecha_inicio" id="fecha_inicio" class="form-control flatpickr" placeholder="yyyy-mm-dd" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_fin" class="form-label">Fecha fin <span class="text-danger">*</span></label>
                                <input type="text" name="fecha_fin" id="fecha_fin" class="form-control flatpickr" placeholder="yyyy-mm-dd" required>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-2">
                            <button type="submit" id="btnGenerar" class="btn btn-primary">
                                <i class="fas fa-cogs me-1"></i> Generar
                            </button>
                            <button type="button" id="btnLimpiar" class="btn btn-outline-success">
                                <i class="fas fa-eraser me-1"></i> Limpiar
                            </button>
                        </div>
                    </form>

                    <!-- Resultado -->
                    <div id="resultadoGeneracion" class="alert mt-4 d-none" role="alert"></div>

                    <!-- Progreso -->
                    <div id="progreso" class="mt-3 d-none">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                        </div>
                        <small class="text-muted">Generando reporte, esto puede tardar unos segundos...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel lateral -->
        <div class="col-lg-4">
            <!-- Consejos -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Consejos</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-1"></i>
                            Evita duplicados: si ya existe un reporte con el mismo <em>tipo y periodo</em>, el sistema te avisará.
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-1"></i>
                            El costo y tokens se registran al finalizar. Revisa el detalle del reporte para verlos.
                        </li>
                        <li>
                            <i class="fas fa-check text-success me-1"></i>
                            Puedes descargar el PDF desde el listado o la pantalla de detalle.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Atajos -->
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Atajos</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm atajo" data-tipo="mensual">
                            <i class="fas fa-calendar-day me-1"></i> Últimos 12 meses
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm atajo" data-tipo="trimestral">
                            <i class="fas fa-calendar-alt me-1"></i> Trimestres del año
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm atajo" data-tipo="semestral">
                            <i class="fas fa-calendar me-1"></i> Semestres del año
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm atajo" data-tipo="anual">
                            <i class="fas fa-calendar-check me-1"></i> Reportes anuales
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="<?= base_url('assets/js/reportes_ia_generar.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
