<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => $controlador, 'vista' => $vista]); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Usuarios</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
            <i class="fa fa-plus"></i> Agregar Usuario
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaUsuarios" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Última Conexión</th>
                        <th>Rol</th>
                        <th>Cliente</th>
                        <th>Recibe Notificaciones</th>
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
</template>

<template id="tplDetalleTabla">
    <div class="card custom-card card-body">
        <form id="formEditarUsuario-{{id}}" action="<?= base_url('usuarios/guardar') ?>" method="post" class="formEditarUsuario">
            <input type="hidden" name="id" value="{{id}}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nombre_usuario-{{id}}" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre_usuario-{{id}}" name="nombre_usuario" value="{{nombre_usuario}}" required autocomplete="username">
                </div>
                <div class="col-md-4">
                    <label for="correo_electronico-{{id}}" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="correo_electronico-{{id}}" name="correo_electronico" value="{{correo_electronico}}" required autocomplete="email">
                </div>
                <div class="col-md-4">
                    <label for="rol_id-{{id}}" class="form-label">Rol</label>
                    <select class="form-select" id="rol_id-{{id}}" name="rol_id" required>
                        {{{selectOptions roles rol_id}}}
                    </select>
                </div>
                <div class="col-md-4" id="clienteContainer-{{id}}" style="display: none;">
                    <label for="id_cliente-{{id}}" class="form-label">Cliente</label>
                    <select class="form-select" id="id_cliente-{{id}}" name="id_cliente">
                        <option value="">Seleccionar Cliente</option>
                        {{{selectOptions clientes id_cliente}}}
                    </select>
                </div>
                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                            id="recibe_notificaciones-{{id}}"
                            name="recibe_notificaciones"
                            value="1" {{recibe_notificaciones_checked}}>
                        <label class="form-check-label" for="recibe_notificaciones-{{id}}">Recibir notificaciones del sistema</label>
                    </div>
                </div>
                <div class="col-md-4" id="correoNotificacionesContainer-{{id}}" style="display: none;">
                    <label for="correo_notificaciones-{{id}}" class="form-label">Correo para Notificaciones</label>
                    <input type="email" class="form-control" id="correo_notificaciones-{{id}}" name="correo_notificaciones" value="{{correo_notificaciones}}">
                </div>

                <div class="col-md-12 mt-5">
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
<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-labelledby="modalCrearUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCrearUsuario" action="<?= base_url('usuarios/guardar') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearUsuarioLabel">Agregar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_usuario" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" required autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="rol_id" class="form-label">Rol</label>
                        <select class="form-select select2ModalCrearUsuario" id="rol_id" name="rol_id" required>
                            <option value="" selected disabled>Selecciona una opción</option>
                        </select>
                    </div>
                    <div class="mb-3" id="clienteContainer" style="display: none;">
                        <label for="id_cliente" class="form-label">Cliente</label>
                        <select class="form-select select2ModalCrearUsuario" id="id_cliente" name="id_cliente">
                            <option value="">Seleccionar Cliente</option>
                            <?php foreach ($clientes as $cliente) : ?>
                                <option value="<?= $cliente['id'] ?>"><?= $cliente['nombre_empresa'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="recibe_notificaciones" name="recibe_notificaciones" value="1">
                            <label class="form-check-label" for="recibe_notificaciones">Recibir notificaciones del sistema</label>
                        </div>
                    </div>
                    <div class="mb-3" id="correoNotificacionesContainer" style="display: none;">
                        <label for="correo_notificaciones" class="form-label">Correo para Notificaciones</label>
                        <input type="email" class="form-control" id="correo_notificaciones" name="correo_notificaciones" placeholder="Opcional, si no se repite el correo principal">
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
<script>
    let roles = '<?= json_encode($roles) ?>';
    let clientes = '<?= json_encode($clientes) ?>';

    roles = JSON.parse(roles);
    clientes = JSON.parse(clientes);
</script>
<script src="<?= base_url('assets/js/usuarios.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>