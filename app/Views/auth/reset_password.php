<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Restablecer Contraseña<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Card Form for Reset Password -->
<div class="row">
    <div class="col-md-6 col-lg-4 mx-auto">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Restablecer Contraseña</h4>
            </div>
            <div class="card-body">
                <!-- Formulario para Restablecer la Contraseña -->
                <form action="<?= base_url('reset-password-submit') ?>" method="post">
                    <!-- Campo oculto para el token -->
                    <input type="hidden" name="token" value="<?= esc($token) ?>">

                    <!-- Nueva Contraseña -->
                    <div class="form-group">
                        <label for="contrasena" class="form-label">Nueva Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" class="form-control" placeholder="Introduce tu nueva contraseña" required>
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div class="form-group mt-3">
                        <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-control" placeholder="Repite tu nueva contraseña" required>
                    </div>

                    <!-- Botón para restablecer -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Restablecer Contraseña</button>
                    </div>
                </form>

                <!-- Mensajes de Éxito/Error -->
                <?php if (session()->getFlashdata('msg')): ?>
                    <div class="alert alert-info mt-3 animate__animated animate__fadeIn" role="alert">
                        <?= session()->getFlashdata('msg') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>