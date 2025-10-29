<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-chart-line me-2"></i>Estadísticas de Reportes IA</h4>
        <div class="ms-auto">
            <a href="<?= base_url('reportes-ia') ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <?php /* ... (idéntico a la versión anterior; solo cambia el link Volver y el JS) ... */ ?>

</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/reportes_ia.js?v=1') ?>"></script>
<script>
    $(function() {
        pintarGraficaStats(
            <?= json_encode($estadisticas['por_tipo'] ?? [], JSON_UNESCAPED_UNICODE) ?>,
            <?= json_encode($estadisticas['por_estado'] ?? [], JSON_UNESCAPED_UNICODE) ?>
        );
    });
</script>
<?= $this->endSection() ?>