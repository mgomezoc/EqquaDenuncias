<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Generar Reporte IA<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Reportes IA', 'vista' => 'Generar Reporte IA']); ?>

<div class="container-fluid py-3">

    <!-- Header / volver -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-semibold">Generar Reporte con IA</h5>
        <a href="<?= base_url('reportes-ia') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- FORM -->
            <div class="card custom-card">
                <div class="card-body">
                    <form id="frmGenerarReporte" class="row g-3">

                        <?php if (!empty($es_cliente) && $es_cliente && !empty($id_cliente_fijo)) : ?>
                            <input type="hidden" name="id_cliente" value="<?= (int)$id_cliente_fijo ?>">
                            <div class="col-12">
                                <label class="form-label">Cliente</label>
                                <input class="form-control" value="<?php
                                                                    $c = array_filter($clientes, fn($x) => $x['id'] == $id_cliente_fijo);
                                                                    $c = $c ? array_values($c)[0] : null;
                                                                    echo esc($c['nombre_empresa'] ?? $c['nombre'] ?? ('Cliente ' . $id_cliente_fijo));
                                                                    ?>" disabled>
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select name="id_cliente" class="form-select select2" required data-placeholder="Seleccione cliente...">
                                    <option value="">Seleccione cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= esc($c['id']) ?>">
                                            <?= esc($c['nombre_empresa'] ?? $c['nombre'] ?? ('Cliente ' . $c['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de Reporte <span class="text-danger">*</span></label>
                            <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                                <option value="mensual">Mensual</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                            </select>
                            <div class="form-text">Cambia el tipo para ver periodos sugeridos.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-flex align-items-center gap-2">
                                Periodo sugerido
                                <span class="badge bg-light text-muted border">Auto</span>
                            </label>
                            <div class="input-group">
                                <select id="periodo_rapido" class="form-select">
                                    <option value="">Selecciona...</option>
                                </select>
                                <button type="button" id="btnRefrescarPeriodos" class="btn btn-outline-default" title="Actualizar periodos" data-bs-toggle="tooltip">
                                    <i class="fas fa-rotate"></i>
                                </button>
                            </div>
                            <div class="form-text">Selecciona un periodo rápido o define fechas manualmente.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control flatpickr" name="fecha_inicio" id="fecha_inicio" required placeholder="yyyy-mm-dd">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha fin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control flatpickr" name="fecha_fin" id="fecha_fin" required placeholder="yyyy-mm-dd">
                        </div>

                        <div class="col-12">
                            <button id="btnGenerar" class="btn btn-primary" type="submit">
                                <i class="fas fa-cogs me-1"></i> Generar
                            </button>
                            <button id="btnLimpiar" class="btn btn-outline-secondary ms-1" type="button">
                                <i class="fas fa-broom me-1"></i> Limpiar
                            </button>
                        </div>
                    </form>

                    <!-- Feedback -->
                    <div id="resultadoGeneracion" class="alert mt-3 d-none"></div>

                    <!-- Barra de progreso indeterminada -->
                    <div id="progreso" class="progress mt-2 d-none" style="height:6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel lateral informativo -->
        <div class="col-lg-4">
            <div class="card custom-card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Consejos</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li>Evita duplicados: si ya existe un reporte con el mismo <em>tipo</em> y <em>periodo</em>, el sistema te avisará.</li>
                        <li>El costo y tokens se registran al finalizar. Revisa el detalle del reporte para verlos.</li>
                        <li>Puedes descargar el PDF desde el listado o la pantalla de detalle.</li>
                    </ul>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header border-bottom">
                    <h6 class="card-title mb-0"><i class="fas fa-magic me-2"></i>Atajos</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm atajo" data-tipo="mensual">
                            <i class="fas fa-calendar-day me-1"></i> Últimos 12 meses
                        </button>
                        <button class="btn btn-outline-warning btn-sm atajo" data-tipo="trimestral">
                            <i class="fas fa-calendar-alt me-1"></i> Trimestres del año
                        </button>
                        <button class="btn btn-outline-dark btn-sm atajo" data-tipo="semestral">
                            <i class="fas fa-calendar me-1"></i> Semestres del año
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
<style>
    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .5rem
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Base absoluta para endpoint
    const Server = "<?= rtrim(base_url('/'), '/') . '/' ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>

<!-- JS dedicado de esta vista -->
<script src="<?= base_url('assets/js/reportes_ia_generar.js') ?>?v=<?= config('App')->assetVersion ?>"></script>

<script>
    $(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione...',
            allowClear: true,
            width: '100%'
        });
    });
</script>
<?= $this->endSection() ?>