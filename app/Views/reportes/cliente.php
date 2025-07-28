<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Reporte de Denuncias - Cliente<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Reportes', 'vista' => 'Denuncias Cliente']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Reporte de Denuncias</span>
        <button type="button" class="btn btn-secondary" id="btnExportar">Exportar CSV</button>
    </div>

    <div class="card-body">
        <form id="formFiltros">
            <input type="hidden" name="id_cliente" value="<?= $clienteId ?>">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label for="fecha_inicio">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                </div>
                <div class="col-md-3">
                    <label for="id_sucursal">Sucursal</label>
                    <select class="form-control select2" id="id_sucursal" name="id_sucursal">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="id_departamento">Departamento</label>
                    <select class="form-control select2" id="id_departamento" name="id_departamento" disabled>
                        <option value="">Seleccionar Departamento</option>
                    </select>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3">
                    <label for="medio_recepcion">Canal de Recepción</label>
                    <select class="form-control" id="medio_recepcion" name="medio_recepcion">
                        <option value="">Todos</option>
                        <option value="Plataforma">Plataforma</option>
                        <option value="Plataforma Pública">Plataforma Pública</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="estado_actual">Estatus Actual</label>
                    <select class="form-control select2" id="estado_actual" name="estado_actual">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['id'] ?>"><?= $estado['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <button type="button" class="btn btn-primary" id="btnFiltrar">Filtrar</button>
                <button type="button" class="btn btn-secondary" id="btnReset">Reset</button>
            </div>

        </form>

        <div class="mt-4">
            <div class="table-responsive">
                <table id="tablaDenuncias" class="table table-sm table-striped table-eqqua">
                    <thead>
                        <th data-field="fecha_hora_reporte">Fecha Reporte</th>
                        <th data-field="estado_nombre">Estatus</th>
                        <th data-field="folio">Folio</th>
                        <th data-field="sucursal_nombre">Sucursal</th>
                        <th data-field="departamento_nombre">Departamento</th>
                        <th data-field="categoria_nombre">Categoría</th>
                        <th data-field="subcategoria_nombre">SubCategoría</th>
                        <th data-field="fecha_incidente">Fecha Incidente</th>
                        <th data-field="medio_recepcion">Medio Recepción</th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<div class="modal fade" id="modalVerDetalle" tabindex="-1" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerDetalleLabel">Detalle de Denuncia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4/dist/fancybox.css" />
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4/dist/fancybox.umd.js"></script>
<script>
    var clienteId = "<?= $clienteId ?>";
</script>
<script src="<?= base_url('assets/js/reporte-cliente.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>