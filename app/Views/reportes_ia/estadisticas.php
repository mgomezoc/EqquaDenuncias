<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>Estadísticas de Reportes IA</h4>
        <div class="ms-auto">
            <a href="<?= base_url('reportes-ia') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="fas fa-file-lines fa-lg text-primary"></i>
                    <div>
                        <div class="text-muted small">Total de reportes</div>
                        <div class="h5 mb-0" id="kpiTotalReportes"
                            data-total="<?= (int)($estadisticas['total_reportes'] ?? 0) ?>">
                            <?= (int)($estadisticas['total_reportes'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="fas fa-coins fa-lg text-warning"></i>
                    <div>
                        <div class="text-muted small">Costo total (estimado)</div>
                        <div class="h5 mb-0" id="kpiCostoTotal"
                            data-costo="<?= (float)($estadisticas['costo_total'] ?? 0) ?>">
                            $<?= number_format((float)($estadisticas['costo_total'] ?? 0), 4) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="fas fa-dna fa-lg text-info"></i>
                    <div>
                        <div class="text-muted small">Tokens consumidos</div>
                        <div class="h5 mb-0" id="kpiTokensTotal"
                            data-tokens="<?= (int)($estadisticas['tokens_total'] ?? 0) ?>">
                            <?= number_format((int)($estadisticas['tokens_total'] ?? 0)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Leyenda rápida de estados -->
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small mb-2"><i class="fas fa-traffic-light me-1"></i>Estados</div>
                    <div id="badgesEstados" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center">
                    <i class="fas fa-layer-group me-2 text-primary"></i>
                    <strong>Reportes por tipo</strong>
                </div>
                <div class="card-body">
                    <canvas id="chPorTipo" height="260"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center">
                    <i class="fas fa-flag-checkered me-2 text-success"></i>
                    <strong>Reportes por estado</strong>
                </div>
                <div class="card-body">
                    <canvas id="chPorEstado" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js (necesario para las gráficas) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<!-- JS exclusivo de esta vista -->
<script>
    const STATS_POR_TIPO = <?= json_encode($estadisticas['por_tipo']   ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const STATS_POR_ESTADO = <?= json_encode($estadisticas['por_estado'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('assets/js/reportes_ia_estadisticas.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>