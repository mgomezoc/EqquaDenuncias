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
                        <th>Sucursal</th>
                        <th>Tipo Denunciante</th>
                        <th>Categoría</th>
                        <th>Subcategoría</th>
                        <th>Departamento</th>
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
                <!-- Cliente -->
                <div class="col-md-4">
                    <label for="id_cliente-{{id}}" class="form-label">Cliente</label>
                    <select class="form-select select2" id="id_cliente-{{id}}" name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        {{#each clientes}}
                            <option value="{{this.id}}" {{#ifCond ../id_cliente '==' this.id}}selected{{/ifCond}}>{{this.nombre_empresa}}</option>
                        {{/each}}
                    </select>
                </div>
                <!-- Categoría -->
                <div class="col-md-4">
                    <label for="categoria-{{id}}" class="form-label">Categoría</label>
                    <select class="form-select select2" id="categoria-{{id}}" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        {{#each categorias}}
                            <option value="{{this.id}}" {{#ifCond ../categoria '==' this.id}}selected{{/ifCond}}>{{this.nombre}}</option>
                        {{/each}}
                    </select>
                </div>
                <!-- Subcategoría -->
                <div class="col-md-4">
                    <label for="subcategoria-{{id}}" class="form-label">Subcategoría</label>
                    <select class="form-select select2" id="subcategoria-{{id}}" name="subcategoria" required>
                        <option value="">Seleccione una subcategoría</option>
                        {{#each subcategorias}}
                            <option value="{{this.id}}" {{#ifCond ../subcategoria '==' this.id}}selected{{/ifCond}}>{{this.nombre}}</option>
                        {{/each}}
                    </select>
                </div>
                <!-- Estado -->
                <div class="col-md-4">
                    <label for="estado_actual-{{id}}" class="form-label">Estado</label>
                    <select class="form-select select2" id="estado_actual-{{id}}" name="estado_actual" required>
                        <option value="">Seleccione un estado</option>
                        {{#each estados}}
                            <option value="{{this.id}}" {{#ifCond ../estado_actual '==' this.id}}selected{{/ifCond}}>{{this.nombre}}</option>
                        {{/each}}
                    </select>
                </div>
                <!-- Descripción -->
                <div class="col-md-12">
                    <label for="descripcion-{{id}}" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion-{{id}}" name="descripcion" required>{{descripcion}}</textarea>
                </div>
                <!-- Anonimo -->
                <div class="col-md-6">
                    <label class="form-label">Anónimo</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-si-{{id}}" value="1" {{#ifCond anonimo '==' '1'}}checked{{/ifCond}} required>
                        <label class="form-check-label" for="anonimo-si-{{id}}">
                            Sí
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-no-{{id}}" value="0" {{#ifCond anonimo '==' '0'}}checked{{/ifCond}} required>
                        <label class="form-check-label" for="anonimo-no-{{id}}">
                            No
                        </label>
                    </div>
                </div>
                <!-- Botón de Actualizar -->
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
                        <!-- Cliente -->
                        <div class="col-md-6">
                            <label for="id_cliente" class="form-label">Cliente</label>
                            <div class="d-flex flex-column-reverse">
                                <select class="form-select select2" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php foreach ($clientes as $cliente) : ?>
                                        <option value="<?= $cliente['id'] ?>"><?= $cliente['nombre_empresa'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Sucursal -->
                        <div class="col-md-6">
                            <label for="id_sucursal" class="form-label">Sucursal</label>
                            <div class="d-flex flex-column-reverse">
                                <select class="form-select select2" id="id_sucursal" name="id_sucursal" required>
                                    <option value="">Seleccione una sucursal</option>
                                </select>
                            </div>
                        </div>
                        <!-- Tipo de Denunciante -->
                        <div class="col-md-6">
                            <label for="tipo_denunciante" class="form-label">Tipo de Denunciante</label>
                            <div class="d-flex flex-column-reverse">
                                <select id="tipo_denunciante" name="tipo_denunciante" class="form-select select2" required>
                                    <option value="Colaborador">Colaborador</option>
                                    <option value="Proveedor">Proveedor</option>
                                    <option value="Cliente">Cliente</option>
                                </select>
                            </div>
                        </div>
                        <!-- Categoría -->
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <div class="d-flex flex-column-reverse">
                                <select class="form-select select2" id="categoria" name="categoria" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categorias as $categoria) : ?>
                                        <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Subcategoría -->
                        <div class="col-md-6">
                            <label for="subcategoria" class="form-label">Subcategoría</label>
                            <div class="d-flex flex-column-reverse">
                                <select class="form-select select2" id="subcategoria" name="subcategoria" required>
                                    <option value="">Seleccione una subcategoría</option>
                                </select>
                            </div>
                        </div>
                        <!-- Departamento -->
                        <div class="col-md-6">
                            <label for="departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" required placeholder="Ingrese el departamento">
                        </div>
                        <!-- Anonimo -->
                        <div class="col-md-6">
                            <label class="form-label">Anónimo</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="anonimo" id="anonimo-si" value="1" required>
                                <label class="form-check-label" for="anonimo-si">
                                    Sí
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="anonimo" id="anonimo-no" value="0" required>
                                <label class="form-check-label" for="anonimo-no">
                                    No
                                </label>
                            </div>
                        </div>
                        <!-- Fecha del Incidente -->
                        <div class="col-md-6">
                            <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                            <input type="text" class="form-control flatpickr" id="fecha_incidente" name="fecha_incidente" required>
                        </div>
                        <!-- Como se enteró -->
                        <div class="col-md-6">
                            <label for="como_se_entero" class="form-label">¿Cómo se Enteró?</label>
                            <select name="como_se_entero" id="como_se_entero" class="form-select select2" required>
                                <option value="Fui víctima">Fui víctima</option>
                                <option value="Fui testigo">Fui testigo</option>
                                <option value="Estaba involucrado">Estaba involucrado</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <!-- Denunciar a Alguien -->
                        <div class="col-md-6">
                            <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                            <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien" placeholder="Describa a la persona involucrada"></textarea>
                        </div>
                        <!-- Área del Incidente -->
                        <div class="col-md-6">
                            <label for="area_incidente" class="form-label">Área del Incidente</label>
                            <input type="text" class="form-control" id="area_incidente" name="area_incidente" required placeholder="Ingrese el área donde sucedió">
                        </div>
                        <!-- Descripción -->
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" required placeholder="Describa la denuncia"></textarea>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?= base_url('assets/js/denuncias.js') ?>"></script>
<?= $this->endSection() ?>