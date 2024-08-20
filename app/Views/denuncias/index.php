<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Denuncias<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Denuncias', 'vista' => 'Denuncias']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Denuncias</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearDenuncia">
            <i class="fa fa-plus"></i> Agregar Denuncia
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaDenuncias" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Categoría</th>
                        <th>Subcategoría</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<template id="tplAccionesTabla">
    <button class="btn btn-sm btn-danger remove">
        <i class="fa fa-trash"></i>
    </button>
    <button class="btn btn-sm btn-info view-detail">
        <i class="fa fa-eye"></i>
    </button>
    <button class="btn btn-sm btn-warning change-status">
        <i class="fa fa-exchange-alt"></i>
    </button>
</template>

<template id="tplDetalleTabla">
    <div class="card custom-card card-body">
        <form id="formEditarDenuncia-{{id}}" action="<?= base_url('denuncias/guardar') ?>" method="post" class="formEditarDenuncia">
            <input type="hidden" name="id" value="{{id}}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="folio-{{id}}" class="form-label">Folio</label>
                    <input type="text" class="form-control" id="folio-{{id}}" name="folio" value="{{folio}}" required>
                </div>
                <div class="col-md-4">
                    <label for="id_cliente-{{id}}" class="form-label">Cliente</label>
                    <select class="form-select" id="id_cliente-{{id}}" name="id_cliente" required>
                        {{{selectOptions clientes id_cliente}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="categoria-{{id}}" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria-{{id}}" name="categoria" required>
                        {{{selectOptions categorias categoria}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="subcategoria-{{id}}" class="form-label">Subcategoría</label>
                    <select class="form-select" id="subcategoria-{{id}}" name="subcategoria" required>
                        {{{selectOptions subcategorias subcategoria}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="estado_actual-{{id}}" class="form-label">Estado</label>
                    <select class="form-select" id="estado_actual-{{id}}" name="estado_actual" required>
                        {{{selectOptions estados estado_actual}}}
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="descripcion-{{id}}" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion-{{id}}" name="descripcion" required>{{descripcion}}</textarea>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Modal Crear Denuncia -->
<div class="modal fade" id="modalCrearDenuncia" tabindex="-1" aria-labelledby="modalCrearDenunciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCrearDenuncia" action="<?= base_url('denuncias/guardar') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearDenunciaLabel">Agregar Denuncia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Folio -->
                        <div class="col-md-6">
                            <label for="folio" class="form-label">Folio</label>
                            <input type="text" class="form-control" id="folio" name="folio" required>
                        </div>
                        <!-- Cliente -->
                        <div class="col-md-6">
                            <label for="id_cliente" class="form-label">Cliente</label>
                            <select class="form-select" id="id_cliente" name="id_cliente" required>
                                <?php foreach ($clientes as $cliente) : ?>
                                    <option value="<?= $cliente['id'] ?>"><?= $cliente['nombre_empresa'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Sucursal -->
                        <div class="col-md-6">
                            <label for="id_sucursal" class="form-label">Sucursal</label>
                            <select class="form-select" id="id_sucursal" name="id_sucursal" required>
                                <!-- Opciones de sucursales aquí -->
                            </select>
                        </div>
                        <!-- Tipo de Denunciante -->
                        <div class="col-md-6">
                            <label for="tipo_denunciante" class="form-label">Tipo de Denunciante</label>
                            <input type="text" class="form-control" id="tipo_denunciante" name="tipo_denunciante" required>
                        </div>
                        <!-- Categoría -->
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <?php foreach ($categorias as $categoria) : ?>
                                    <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Subcategoría -->
                        <div class="col-md-6">
                            <label for="subcategoria" class="form-label">Subcategoría</label>
                            <select class="form-select" id="subcategoria" name="subcategoria" required>
                                <!-- Opciones de subcategorías aquí -->
                            </select>
                        </div>
                        <!-- Departamento -->
                        <div class="col-md-6">
                            <label for="departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" required>
                        </div>
                        <!-- Anonimo -->
                        <div class="col-md-6">
                            <label for="anonimo" class="form-label">Anónimo</label>
                            <select class="form-select" id="anonimo" name="anonimo" required>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <!-- Fecha del Incidente -->
                        <div class="col-md-6">
                            <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                            <input type="date" class="form-control" id="fecha_incidente" name="fecha_incidente" required>
                        </div>
                        <!-- Como se enteró -->
                        <div class="col-md-6">
                            <label for="como_se_entero" class="form-label">¿Cómo se Enteró?</label>
                            <input type="text" class="form-control" id="como_se_entero" name="como_se_entero" required>
                        </div>
                        <!-- Denunciar a Alguien -->
                        <div class="col-md-6">
                            <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                            <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien"></textarea>
                        </div>
                        <!-- Área del Incidente -->
                        <div class="col-md-6">
                            <label for="area_incidente" class="form-label">Área del Incidente</label>
                            <input type="text" class="form-control" id="area_incidente" name="area_incidente" required>
                        </div>
                        <!-- Descripción -->
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
                        </div>
                        <!-- Estado Actual -->
                        <div class="col-md-6">
                            <label for="estado_actual" class="form-label">Estado Actual</label>
                            <select class="form-select" id="estado_actual" name="estado_actual" required>
                                <?php foreach ($estados as $estado) : ?>
                                    <option value="<?= $estado['id'] ?>"><?= $estado['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Archivos Adjuntos -->
                        <div class="col-md-12">
                            <label for="archivos_adjuntos" class="form-label">Archivos Adjuntos</label>
                            <input type="file" class="form-control" id="archivos_adjuntos" name="archivos_adjuntos[]" multiple>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="<?= base_url('assets/js/denuncias.js') ?>"></script>
<?= $this->endSection() ?>