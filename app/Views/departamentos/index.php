<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Departamentos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Departamentos', 'vista' => 'Departamentos']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Departamentos</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearDepartamento">
            <i class="fa fa-plus"></i> Agregar Departamento
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaDepartamentos" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Sucursal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<template id="tplAccionesTabla">
    <button class="btn btn-sm btn-danger remove" data-id="{{id}}">
        <i class="fa fa-trash"></i>
    </button>
    <button class="btn btn-sm btn-info edit" data-id="{{id}}" data-nombre="{{nombre}}" data-sucursal="{{id_sucursal}}">
        <i class="fa fa-edit"></i>
    </button>
</template>

<!-- Modal Crear/Editar Departamento -->
<div class="modal fade" id="modalCrearDepartamento" tabindex="-1" aria-labelledby="modalCrearDepartamentoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCrearDepartamento" action="<?= base_url('departamentos/guardarDepartamento') ?>" method="post">
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearDepartamentoLabel">Agregar/Editar Departamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_sucursal" class="form-label">Sucursal</label>
                        <select class="form-select select2" id="id_sucursal" name="id_sucursal" required style="width: 100%;">
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
<script src="<?= base_url('assets/js/departamentos.js') ?>"></script>
<?= $this->endSection() ?>