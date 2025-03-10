<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Mis Denuncias<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Denuncias', 'vista' => 'Mis Denuncias']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Mis Denuncias</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDenuncia">
            <i class="fa fa-plus"></i> Agregar Denuncia
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaMisDenuncias" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Folio</th>
                        <th>Sucursal</th>
                        <th>Tipo Denunciante</th>
                        <th>Categoría</th>
                        <th>Subcategoría</th>
                        <th>Departamento</th>
                        <th>Estatus</th>
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

<!-- Incluir el modal de denuncia -->
<?= view('denuncias/modal_denuncia', ['categorias' => $categorias, 'esCliente' => true]) ?>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script src="<?= base_url('assets/js/mis_denuncias.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>