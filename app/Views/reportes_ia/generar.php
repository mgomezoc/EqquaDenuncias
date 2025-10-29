<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Generar Reporte IA<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Reportes IA', 'vista' => 'Generar Reporte IA']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Generar Reporte IA</span>
        <a href="<?= base_url('reportes-ia') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>
    <div class="card-body">
        <form id="frmGenerarReporte" class="row g-3">
            <?php if (!empty($es_cliente) && $es_cliente && !empty($id_cliente_fijo)) : ?>
                <input type="hidden" name="id_cliente" value="<?= (int)$id_cliente_fijo ?>">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <input class="form-control" value="<?php
                                                        $c = array_filter($clientes, fn($x) => $x['id'] == $id_cliente_fijo);
                                                        $c = $c ? array_values($c)[0] : null;
                                                        echo esc($c['nombre_empresa'] ?? $c['nombre'] ?? ('Cliente ' . $id_cliente_fijo));
                                                        ?>" disabled>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select name="id_cliente" class="form-select select2" required>
                        <option value="">Seleccione cliente...</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= esc($c['id']) ?>">
                                <?= esc($c['nombre_empresa'] ?? $c['nombre'] ?? ('Cliente ' . $c['id'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label">Tipo de Reporte <span class="text-danger">*</span></label>
                <select name="tipo_reporte" id="tipo_reporte" class="form-select" required>
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="semestral">Semestral</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Periodo rápido</label>
                <select id="periodo_rapido" class="form-select">
                    <option value="">Selecciona...</option>
                </select>
                <div class="form-text">Puedes elegir un periodo sugerido o definir fechas abajo.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                <input type="text" class="form-control flatpickr" name="fecha_inicio" id="fecha_inicio" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha fin <span class="text-danger">*</span></label>
                <input type="text" class="form-control flatpickr" name="fecha_fin" id="fecha_fin" required>
            </div>

            <div class="col-12">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-cogs me-1"></i> Generar
                </button>
            </div>
        </form>

        <div id="resultadoGeneracion" class="alert mt-3 d-none"></div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Base absoluta para los endpoints (termina en "/")
    const Server = "<?= rtrim(base_url('/'), '/') . '/' ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="<?= base_url('assets/js/reportes_ia.js?v=2') ?>"></script>
<script>
    $(function() {
        $('.select2').select2({
            placeholder: 'Seleccione...',
            allowClear: true,
            width: '100%'
        });
        initGenerarReporte();
    });
</script>
<?= $this->endSection() ?>