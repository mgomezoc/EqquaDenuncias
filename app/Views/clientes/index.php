<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Clientes<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Clientes', 'vista' => 'Clientes']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Clientes</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
            <i class="fa fa-plus"></i> Agregar Cliente
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaClientes" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Empresa</th>
                        <th>Correo Contacto</th>
                        <th>Teléfono Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<template id="tplAccionesTabla">
    <button class="btn btn-sm btn-info edit">
        <i class="fa fa-edit"></i>
    </button>
    <button class="btn btn-sm btn-danger remove">
        <i class="fa fa-trash"></i>
    </button>
</template>

<template id="tplDetalleTabla">
    <div class="card custom-card card-body">
        <form id="formEditarCliente-{{id}}" action="<?= base_url('clientes/guardar') ?>" method="post" class="formEditarCliente">
            <input type="hidden" name="id" value="{{id}}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="{{nombre_empresa}}" required>
                </div>
                <div class="col-md-4">
                    <label for="numero_identificacion" class="form-label">Número de Identificación</label>
                    <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" value="{{numero_identificacion}}" required>
                </div>
                <div class="col-md-4">
                    <label for="correo_contacto" class="form-label">Correo Contacto</label>
                    <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" value="{{correo_contacto}}" required>
                </div>
                <div class="col-md-4">
                    <label for="telefono_contacto" class="form-label">Teléfono Contacto</label>
                    <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" value="{{telefono_contacto}}" required>
                </div>
                <div class="col-md-4">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="{{direccion}}" required>
                </div>
                <div class="col-md-4">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="{{slug}}" required>
                </div>
                <div class="col-md-4">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="text" class="form-control" id="logo" name="logo" value="{{logo}}">
                </div>
                <div class="col-md-4">
                    <label for="banner" class="form-label">Banner</label>
                    <input type="text" class="form-control" id="banner" name="banner" value="{{banner}}">
                </div>
                <div class="col-md-4">
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
<!-- Modal Crear Cliente -->
<div class="modal fade" id="modalCrearCliente" tabindex="-1" aria-labelledby="modalCrearClienteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCrearCliente" action="<?= base_url('clientes/guardar') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearClienteLabel">Agregar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                        <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
                    </div>
                    <div class="mb-3">
                        <label for="numero_identificacion" class="form-label">Número de Identificación</label>
                        <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" required>
                    </div>
                    <div class="mb-3">
                        <label for="correo_contacto" class="form-label">Correo Contacto</label>
                        <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono_contacto" class="form-label">Teléfono Contacto</label>
                        <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" required>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                    </div>
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo</label>
                        <input type="text" class="form-control" id="logo" name="logo">
                    </div>
                    <div class="mb-3">
                        <label for="banner" class="form-label">Banner</label>
                        <input type="text" class="form-control" id="banner" name="banner">
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
<script src="<?= base_url('assets/js/clientes.js') ?>"></script>
<?= $this->endSection() ?>