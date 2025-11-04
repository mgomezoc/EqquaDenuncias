<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php echo view('partials/_pageHeader', ['controlador' => $controlador, 'vista' => $vista]); ?>
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex gap-2">
            <a href="<?= base_url('reportes-ia/generar') ?>" class="btn btn-dark">
                <i class="fas fa-plus-circle me-1"></i>Generar Reporte
            </a>
            <a href="<?= base_url('reportes-ia/estadisticas') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i>Estadísticas
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="<?= base_url('reportes-ia') ?>" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select name="id_cliente" class="form-select select2">
                        <option value="">Todos</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>" <?= ($filtros['id_cliente'] ?? '') == $cliente['id'] ? 'selected' : '' ?>>
                                <?= esc($cliente['nombre_empresa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Reporte</label>
                    <select name="tipo_reporte" class="form-select select2">
                        <option value="">Todos</option>
                        <option value="mensual" <?= ($filtros['tipo_reporte'] ?? '') === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="trimestral" <?= ($filtros['tipo_reporte'] ?? '') === 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="semestral" <?= ($filtros['tipo_reporte'] ?? '') === 'semestral' ? 'selected' : '' ?>>Semestral</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select select2">
                        <option value="">Todos</option>
                        <option value="generado" <?= ($filtros['estado'] ?? '') === 'generado' ? 'selected' : '' ?>>Generado</option>
                        <option value="revisado" <?= ($filtros['estado'] ?? '') === 'revisado' ? 'selected' : '' ?>>Revisado</option>
                        <option value="publicado" <?= ($filtros['estado'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                        <option value="archivado" <?= ($filtros['estado'] ?? '') === 'archivado' ? 'selected' : '' ?>>Archivado</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla con Bootstrap Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table id="tablaReportes"
                class="table table-sm table-striped table-eqqua table-bordered table-hover"
                data-toggle="table"
                data-pagination="true"
                data-page-size="10"
                data-search="true"
                data-search-align="left"
                data-show-refresh="true"
                data-show-columns="true"
                data-locale="es-MX">
                <thead class="table-light">
                    <tr>
                        <th data-field="id" data-width="70" data-align="center" data-sortable="true">ID</th>
                        <th data-field="cliente_nombre" data-sortable="true">Cliente</th>
                        <th data-field="periodo_nombre" data-sortable="true">Periodo</th>
                        <th data-field="tipo_reporte" data-formatter="tipoFormatter" data-align="center" data-sortable="true">Tipo</th>
                        <th data-field="puntuacion_riesgo" data-formatter="riesgoFormatter" data-align="center" data-sortable="true">Riesgo</th>
                        <th data-field="estado" data-formatter="estadoFormatter" data-align="center" data-sortable="true">Estado</th>
                        <th data-field="created_at" data-formatter="fechaFormatter" data-sortable="true">Creado</th>
                        <th data-field="operate" data-formatter="operateFormatter" data-events="operateEvents" data-align="center" data-width="200">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportes as $r): ?>
                        <tr>
                            <td><?= esc($r['id']) ?></td>
                            <td><?= esc($r['cliente_nombre'] ?? 'N/D') ?></td>
                            <td><?= esc($r['periodo_nombre']) ?></td>
                            <td><?= esc($r['tipo_reporte']) ?></td>
                            <td><?= esc($r['puntuacion_riesgo'] ?? '-') ?></td>
                            <td><?= esc($r['estado']) ?></td>
                            <td><?= esc($r['created_at']) ?></td>
                            <td data-id="<?= esc($r['id']) ?>" data-periodo="<?= esc($r['periodo_nombre']) ?>" data-estado="<?= esc($r['estado']) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Template Handlebars para las acciones -->
<script id="tplAccionesReportes" type="text/x-handlebars-template">
    <div class="btn-group" role="group">
        <a class="btn btn-sm btn-outline-primary view" title="Ver Reporte" data-bs-toggle="tooltip">
            <i class="fas fa-eye"></i>
        </a>
        <a class="btn btn-sm btn-outline-secondary pdf" title="Descargar PDF" data-bs-toggle="tooltip">
            <i class="fas fa-file-pdf"></i>
        </a>
        <button class="btn btn-sm btn-outline-dark estado" title="Cambiar Estado" data-bs-toggle="tooltip">
            <i class="fas fa-arrows-rotate"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger eliminar" title="Eliminar" data-bs-toggle="tooltip">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Bootstrap Table CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Bootstrap Table JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Script principal de Reportes IA -->
<script src="<?= base_url('assets/js/reportes_ia.js') ?>?v=<?= config('App')->assetVersion ?>"></script>

<script>
    $(function() {
        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Todos'
        });

        // Inicializar tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
</script>
<?= $this->endSection() ?>