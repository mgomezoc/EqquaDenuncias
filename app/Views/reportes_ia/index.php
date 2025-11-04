<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php // Header de la página (usa tu partial estándar) 
?>
<?php echo view('partials/_pageHeader', ['controlador' => $controlador, 'vista' => $vista]); ?>

<div class="container-fluid py-3">

    <!-- Toolbar -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex gap-2">
            <a href="<?= base_url('reportes-ia/generar') ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Generar Reporte
            </a>
            <a href="<?= base_url('reportes-ia/estadisticas') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i> Estadísticas
            </a>
        </div>
    </div>

    <!-- Filtros (estilo Sparic) -->
    <div class="card custom-card mb-3">
        <div class="card-body">
            <form id="frmFiltros" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select name="id_cliente" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($filtros['id_cliente'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                <?= esc($c['nombre_empresa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Reporte</label>
                    <select name="tipo_reporte" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        <option value="mensual" <?= (($filtros['tipo_reporte'] ?? '') === 'mensual')    ? 'selected' : '' ?>>Mensual</option>
                        <option value="trimestral" <?= (($filtros['tipo_reporte'] ?? '') === 'trimestral') ? 'selected' : '' ?>>Trimestral</option>
                        <option value="semestral" <?= (($filtros['tipo_reporte'] ?? '') === 'semestral')  ? 'selected' : '' ?>>Semestral</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        <option value="generado" <?= (($filtros['estado'] ?? '') === 'generado')  ? 'selected' : '' ?>>Generado</option>
                        <option value="revisado" <?= (($filtros['estado'] ?? '') === 'revisado')  ? 'selected' : '' ?>>Revisado</option>
                        <option value="publicado" <?= (($filtros['estado'] ?? '') === 'publicado') ? 'selected' : '' ?>>Publicado</option>
                        <option value="archivado" <?= (($filtros['estado'] ?? '') === 'archivado') ? 'selected' : '' ?>>Archivado</option>
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

    <!-- Tabla (Bootstrap Table con AJAX) -->
    <div class="card custom-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaReportes"
                    class="table table-sm table-striped table-eqqua table-bordered table-hover"
                    data-toggle="table"
                    data-url="<?= base_url('reportes-ia/listar') ?>"
                    data-pagination="true"
                    data-side-pagination="client"
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
                            <th data-field="operate" data-formatter="operateFormatter" data-events="operateEvents" data-align="center" data-width="220">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Template Handlebars para acciones (Font Awesome + look Sparic) -->
<script id="tplAccionesReportes" type="text/x-handlebars-template">
    <div class="hstack gap-2 fs-15 justify-content-center">
    <a class="btn btn-icon btn-sm btn-info-light view" title="Ver Reporte" data-bs-toggle="tooltip">
      <i class="fas fa-eye"></i>
    </a>
    <a class="btn btn-icon btn-sm btn-secondary-light pdf" title="Descargar PDF" data-bs-toggle="tooltip">
      <i class="fas fa-file-pdf"></i>
    </a>
    <button class="btn btn-icon btn-sm btn-primary-light estado" title="Cambiar Estado" data-bs-toggle="tooltip">
      <i class="fas fa-arrows-rotate"></i>
    </button>
    <button class="btn btn-icon btn-sm btn-danger-light eliminar" title="Eliminar" data-bs-toggle="tooltip">
      <i class="fas fa-trash"></i>
    </button>
  </div>
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<style>
    /* Asegura el look de botones tipo Sparic dentro de la tabla */
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Script principal de Reportes IA -->
<script src="<?= base_url('assets/js/reportes_ia.js') ?>?v=<?= config('App')->assetVersion ?>"></script>

<script>
    $(function() {
        // Select2 al estilo bootstrap 5
        $('.select2').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Todos'
        });

        // Enviar filtros como queryParams a la tabla
        $('#tablaReportes').bootstrapTable('refreshOptions', {
            queryParams: function(p) {
                const data = Object.fromEntries(new FormData(document.getElementById('frmFiltros')).entries());
                return Object.assign({}, p, data);
            }
        });

        // Filtrar por AJAX sin recargar
        $('#frmFiltros').on('submit', function(e) {
            e.preventDefault();
            $('#tablaReportes').bootstrapTable('refresh', {
                pageNumber: 1
            });
        });

        // Tooltips
        const t = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...t].forEach(el => new bootstrap.Tooltip(el));
    });
</script>
<?= $this->endSection() ?>