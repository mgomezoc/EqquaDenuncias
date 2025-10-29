<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-file-lines me-2"></i>
            Reporte: <?= esc($reporte['periodo_nombre']) ?> (<?= esc(ucfirst($reporte['tipo_reporte'])) ?>)
        </h4>
        <div class="ms-3">
            <span id="badgeEstado" class="badge bg-secondary"><?= esc($reporte['estado'] ?? 'generado') ?></span>
        </div>
        <div class="ms-auto d-flex gap-2">
            <button class="btn btn-outline-dark" id="btnCambiarEstado" data-id="<?= (int)$reporte['id'] ?>">
                <i class="fas fa-arrows-rotate me-1"></i> Cambiar estado
            </button>
            <a class="btn btn-outline-secondary" href="<?= base_url('reportes-ia/descargar-pdf/' . $reporte['id']) ?>">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <a class="btn btn-outline-secondary" href="<?= base_url('reportes-ia') ?>">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Cliente</div>
                    <div class="fw-semibold"><?= esc($reporte['cliente_nombre'] ?? '—') ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Riesgo</div>
                    <div class="fw-bold">
                        <?= isset($reporte['puntuacion_riesgo']) ? esc($reporte['puntuacion_riesgo']) . '/10' : '—' ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Generado por</div>
                    <div class="fw-semibold"><?= esc($reporte['generado_por_nombre'] ?? '—') ?></div>
                    <div class="text-muted small"><?= esc($reporte['created_at'] ?? '') ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Modelo IA / Tokens / Costo</div>
                    <div class="fw-semibold">
                        <?= esc($reporte['modelo_ia_usado'] ?? '—') ?>
                    </div>
                    <div class="text-muted small">
                        Tokens: <?= esc($reporte['tokens_utilizados'] ?? '0') ?> ·
                        Costo: $<?= esc(number_format((float)($reporte['costo_estimado'] ?? 0), 4)) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Texto del reporte -->
    <div class="row g-3">
        <div class="col-lg-8">
            <!-- Resumen Ejecutivo -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Resumen ejecutivo</strong></div>
                <div class="card-body">
                    <div class="lh-base"><?= nl2br(esc($reporte['resumen_ejecutivo'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>

            <!-- Hallazgos -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Hallazgos principales</strong></div>
                <div class="card-body">
                    <div class="lh-base"><?= nl2br(esc($reporte['hallazgos_principales'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>

            <!-- Eficiencia Operativa -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Eficiencia operativa</strong></div>
                <div class="card-body">
                    <div class="lh-base"><?= nl2br(esc($reporte['eficiencia_operativa'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>

            <!-- Sugerencias -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Sugerencias proactivas</strong></div>
                <div class="card-body">
                    <div class="alert alert-warning py-2 px-3 mb-3">
                        Nota: este contenido fue generado por IA y debe revisarse antes de aplicarse.
                    </div>
                    <div class="lh-base"><?= nl2br(esc($reporte['sugerencias_predictivas'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Por sucursal</strong></div>
                <div class="card-body">
                    <canvas id="chSucursales" height="220"></canvas>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Por categoría</strong></div>
                <div class="card-body">
                    <canvas id="chCategorias" height="220"></canvas>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Por estatus</strong></div>
                <div class="card-body">
                    <canvas id="chEstatus" height="220"></canvas>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>Por medio de recepción</strong></div>
                <div class="card-body">
                    <canvas id="chMedios" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis Geográfico y Categórico (texto) -->
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong>Análisis geográfico</strong></div>
                <div class="card-body">
                    <div class="lh-base"><?= nl2br(esc($reporte['analisis_geografico'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong>Análisis por categoría</strong></div>
                <div class="card-body">
                    <div class="lh-base"><?= nl2br(esc($reporte['analisis_categorico'] ?? 'Sin contenido')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Dependencias específicas de esta vista -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Si usas datalabels, descomenta: -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script> -->

<script src="<?= base_url('assets/js/reportes_ia.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<script>
    $(function() {
        pintarBadgeEstado('#badgeEstado', '<?= esc($reporte['estado']) ?>');
        initVerReporte(<?= json_encode($reporte['metricas'] ?? [], JSON_UNESCAPED_UNICODE) ?>);
        bindCambiarEstadoReporte(<?= (int)$reporte['id'] ?>);
    });
</script>
<?= $this->endSection() ?>