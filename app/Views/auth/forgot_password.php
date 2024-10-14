<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Recuperar Contraseña<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Card Form for Password Recovery -->
<div class="row">
    <div class="col-md-6 col-lg-4 mx-auto">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Recuperar Contraseña</h4>
            </div>
            <div class="card-body">
                <!-- Formulario para Recuperar la Contraseña -->
                <form action="<?= base_url('forgot-password-submit') ?>" method="post">
                    <div class="form-group">
                        <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                        <input type="email" id="correo_electronico" name="correo_electronico" class="form-control" placeholder="Ingresa tu correo electrónico" required>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Enviar Enlace de Recuperación</button>
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