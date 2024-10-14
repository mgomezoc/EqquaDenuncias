<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Configuración de Usuario<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Configuración', 'vista' => 'Usuario']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Configuración de Usuario</span>
    </div>
    <div class="card-body">
        <form id="formConfigUsuario" action="<?= base_url('configuracion/actualizar') ?>" method="post">
            <div class="row g-3">
                <!-- Campo del correo electrónico -->
                <div class="col-md-6">
                    <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" value="<?= esc($usuario['correo_electronico']) ?>" readonly>
                </div>

                <!-- Campo para la contraseña anterior -->
                <div class="col-md-6">
                    <label for="contrasena_anterior" class="form-label">Contraseña Anterior</label>
                    <input type="password" class="form-control" id="contrasena_anterior" name="contrasena_anterior" required>
                </div>

                <!-- Campo para la nueva contraseña -->
                <div class="col-md-6">
                    <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required>
                </div>

                <!-- Campo para confirmar la nueva contraseña -->
                <div class="col-md-6">
                    <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
            </div>
        </form>

        <!-- Mostrar mensajes de error o éxito -->
        <?php if (session()->getFlashdata('msg')) : ?>
            <div class="alert alert-info mt-3"><?= session()->getFlashdata('msg') ?></div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>

<script>
    $(document).ready(function() {
        // Validación simple de contraseña (ejemplo)
        $('#formConfigUsuario').validate({
            rules: {
                contrasena_anterior: {
                    required: true
                },
                nueva_contrasena: {
                    required: true,
                    minlength: 6
                },
                confirmar_contrasena: {
                    equalTo: "#nueva_contrasena"
                }
            },
            messages: {
                confirmar_contrasena: {
                    equalTo: "Las contraseñas no coinciden"
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>