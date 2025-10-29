<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-robot me-2"></i>Reportes generados por IA</h4>
        <div class="ms-auto">
            <a href="<?= base_url('reportes-ia/generar') ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Generar Reporte
            </a>
            <a href="<?= base_url('reportes-ia/estadisticas') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i> Estadísticas
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="frmFiltros" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select class="form-select select2" name="id_cliente" id="f-id_cliente" data-allow-clear="true">
                        <option value="">Todos</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= esc($c['id']) ?>" <?= isset($filtros['id_cliente']) && $filtros['id_cliente'] == $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['nombre_empresa'] ?? $c['nombre'] ?? 'Cliente ' . $c['id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="tipo_reporte" id="f-tipo">
                        <option value="">Todos</option>
                        <option value="mensual" <?= ($filtros['tipo_reporte'] ?? '') === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="trimestral" <?= ($filtros['tipo_reporte'] ?? '') === 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="semestral" <?= ($filtros['tipo_reporte'] ?? '') === 'semestral' ? 'selected' : '' ?>>Semestral</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado" id="f-estado">
                        <option value="">Todos</option>
                        <option value="generado" <?= ($filtros['estado'] ?? '') === 'generado' ? 'selected' : '' ?>>Generado</option>
                        <option value="revisado" <?= ($filtros['estado'] ?? '') === 'revisado' ? 'selected' : '' ?>>Revisado</option>
                        <option value="publicado" <?= ($filtros['estado'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                        <option value="archivado" <?= ($filtros['estado'] ?? '') === 'archivado' ? 'selected' : '' ?>>Archivado</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100"><i class="fas fa-filter me-1"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <table id="tablaReportes"
        class="table"
        data-toggle="table"
        data-pagination="true"
        data-page-size="10"
        data-search="true">
        <thead class="table-light">
            <tr>
                <th data-field="id" data-width="70">ID</th>
                <th data-field="cliente_nombre">Cliente</th>
                <th data-field="periodo_nombre">Periodo</th>
                <th data-field="tipo_reporte" data-formatter="tipoFormatter">Tipo</th>
                <th data-field="puntuacion_riesgo" data-formatter="riesgoFormatter">Riesgo</th>
                <th data-field="estado" data-formatter="estadoFormatter">Estado</th>
                <th data-field="created_at" data-formatter="fechaFormatter">Creado</th>
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
                    <td><?= esc($r['puntuacion_riesgo']) ?></td>
                    <td><?= esc($r['estado']) ?></td>
                    <td><?= esc($r['created_at']) ?></td>
                    <td data-id="<?= esc($r['id']) ?>" data-estado="<?= esc($r['estado']) ?>"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script id="tplAccionesReportes" type="text/x-handlebars-template">
    <div class="btn-group">
    <a class="btn btn-sm btn-outline-primary view" title="Ver"><i class="fas fa-eye"></i></a>
    <a class="btn btn-sm btn-outline-secondary pdf" title="PDF"><i class="fas fa-file-pdf"></i></a>
    <button class="btn btn-sm btn-outline-dark estado" title="Cambiar estado"><i class="fas fa-arrows-rotate"></i></button>
    <button class="btn btn-sm btn-outline-danger eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
  </div>
</script>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/reportes_ia.js?v=1') ?>"></script>
<script>
    $(function() {
        $('.select2').select2({
            allowClear: true,
            placeholder: 'Todos'
        });
    });
</script>
<?= $this->endSection() ?>