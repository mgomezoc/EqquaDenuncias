<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Categorías y Subcategorías<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Categorías', 'vista' => 'Categorías']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Categorías</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCategoria">
            <i class="fa fa-plus"></i> Agregar Categoría
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaCategorias" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="card custom-card mt-5">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Subcategorías</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearSubcategoria">
            <i class="fa fa-plus"></i> Agregar Subcategoría
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaSubcategorias" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<template id="tplAccionesTabla">
    <button class="btn btn-sm btn-danger remove" data-id="{{id}}">
        <i class="fa fa-trash"></i>
    </button>
    <button class="btn btn-sm btn-info edit" data-id="{{id}}" data-nombre="{{nombre}}" data-subcategoria="{{id_categoria}}">
        <i class="fa fa-edit"></i>
    </button>
</template>

<!-- Modal Crear Categoría -->
<div class="modal fade" id="modalCrearCategoria" tabindex="-1" aria-labelledby="modalCrearCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCrearCategoria" action="<?= base_url('categorias/guardarCategoria') ?>" method="post">
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearCategoriaLabel">Agregar/Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
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

<!-- Modal Crear Subcategoría -->
<div class="modal fade" id="modalCrearSubcategoria" tabindex="-1" aria-labelledby="modalCrearSubcategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCrearSubcategoria" action="<?= base_url('categorias/guardarSubcategoria') ?>" method="post">
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearSubcategoriaLabel">Agregar/Editar Subcategoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required style="width: 100%;">
                            <!-- Opciones dinámicas desde el JS -->
                        </select>
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
<script src="<?= base_url('assets/js/categorias.js') ?>"></script>
<?= $this->endSection() ?>