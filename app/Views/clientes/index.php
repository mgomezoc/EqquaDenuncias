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
    <button class="btn btn-sm btn-danger remove">
        <i class="fa fa-trash"></i>
    </button>
    <button class="btn btn-sm btn-info view-public">
        <i class="fa fa-eye"></i>
    </button>
</template>

<template id="tplDetalleTabla">
    <div class="">
        <form id="formEditarCliente-{{id}}" action="<?= base_url('clientes/guardar') ?>" method="post" class="formEditarCliente card custom-card card-body mb-4">
            <input type="hidden" name="id" value="{{id}}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="{{nombre_empresa}}" required>
                </div>
                <div class="col-md-4">
                    <label for="numero_identificacion" class="form-label">Número Identificación</label>
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
                <div class="col-md-12">
                    <label for="saludo" class="form-label">Saludo</label>
                    <textarea class="form-control" id="saludo" name="saludo" rows="4">{{saludo}}</textarea>
                </div>
                <div class="col-md-4">
                    <label for="whatsapp" class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="{{whatsapp}}" pattern="\d{10}">
                </div>
                <div class="col-md-4">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <input type="color" class="form-control" id="primary_color" name="primary_color" value="{{primary_color}}">
                </div>
                <div class="col-md-4">
                    <label for="secondary_color" class="form-label">Secondary Color</label>
                    <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="{{secondary_color}}">
                </div>
                <div class="col-md-4">
                    <label for="link_color" class="form-label">Link Color</label>
                    <input type="color" class="form-control" id="link_color" name="link_color" value="{{link_color}}">
                </div>
                <div class="mt-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </form>

        <form id="formActualizarImagenes-{{id}}" class="formActualizarImagenes card custom-card">
            <input type="hidden" name="id" value="{{id}}">
            <div class="card-body">
                <div class="row g-4">
                    <!-- Sección de Logo -->
                    <div class="col-md-6">
                        <div class="card border-light mb-3">
                            <div class="card-header text-center bg-light">
                                <h5 class="mb-0">Logo</h5>
                            </div>
                            <div class="card-body text-center">
                                <a href="{{logo}}" data-lightbox="cliente-{{id}}-logo" data-title="Logo de {{nombre_empresa}}">
                                    <img src="{{logo}}" alt="logo" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                </a>
                                <label for="logo" class="form-label d-block">Subir Nuevo Logo</label>
                                <div id="dropzoneLogo-{{id}}" class="dropzone mb-3"></div>
                                <small class="text-muted d-block">Solo si adjuntas una nueva imagen, esta reemplazará la actual.</small>
                            </div>
                        </div>
                    </div>
                    <!-- Sección de Banner -->
                    <div class="col-md-6">
                        <div class="card border-light mb-3">
                            <div class="card-header text-center bg-light">
                                <h5 class="mb-0">Banner</h5>
                            </div>
                            <div class="card-body text-center">
                                <a href="{{banner}}" data-lightbox="cliente-{{id}}-banner" data-title="Banner de {{nombre_empresa}}">
                                    <img src="{{banner}}" alt="banner" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                </a>
                                <label for="banner" class="form-label d-block">Subir Nuevo Banner</label>
                                <div id="dropzoneBanner-{{id}}" class="dropzone mb-3"></div>
                                <small class="text-muted d-block">Solo si adjuntas una nueva imagen, esta reemplazará la actual.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Actualizar Imágenes
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
    <div class="modal-dialog modal-lg"> <!-- Cambiado a modal-lg -->
        <div class="modal-content">
            <form id="formCrearCliente" action="<?= base_url('clientes/guardar') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearClienteLabel">Agregar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                            <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
                        </div>
                        <div class="col-md-6">
                            <label for="numero_identificacion" class="form-label">Número Identificación</label>
                            <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" required>
                        </div>
                        <div class="col-md-6">
                            <label for="correo_contacto" class="form-label">Correo Contacto</label>
                            <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono_contacto" class="form-label">Teléfono Contacto</label>
                            <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" required>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <div class="col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" required>
                        </div>
                        <div class="col-md-6">
                            <label for="saludo" class="form-label">Saludo</label>
                            <textarea class="form-control" id="saludo" name="saludo" rows="4"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" pattern="\d{10}">
                        </div>
                        <div class="col-md-4">
                            <label for="primary_color" class="form-label">Primary Color</label>
                            <input type="color" class="form-control" id="primary_color" name="primary_color">
                        </div>
                        <div class="col-md-4">
                            <label for="secondary_color" class="form-label">Secondary Color</label>
                            <input type="color" class="form-control" id="secondary_color" name="secondary_color">
                        </div>
                        <div class="col-md-4">
                            <label for="link_color" class="form-label">Link Color</label>
                            <input type="color" class="form-control" id="link_color" name="link_color">
                        </div>
                        <div class="col-md-6">
                            <label for="logo" class="form-label">Logo</label>
                            <div id="dropzoneLogo" class="dropzone"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="banner" class="form-label">Banner</label>
                            <div id="dropzoneBanner" class="dropzone"></div>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script src="<?= base_url('assets/js/clientes.js') ?>"></script>
<?= $this->endSection() ?>