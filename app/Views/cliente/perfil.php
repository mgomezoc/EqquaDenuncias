<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Perfil del Cliente<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Cliente', 'vista' => 'Perfil']); ?>

<div class="card custom-card">
    <div class="card-header">
        <h3>Información del Cliente</h3>
    </div>
    <div class="card-body">
        <form id="formActualizarPerfil" action="<?= base_url('cliente/perfil/actualizar') ?>" method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?= $cliente['nombre_empresa'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="numero_identificacion" class="form-label">Número Identificación</label>
                    <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" value="<?= $cliente['numero_identificacion'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="correo_contacto" class="form-label">Correo Contacto</label>
                    <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" value="<?= $cliente['correo_contacto'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="telefono_contacto" class="form-label">Teléfono Contacto</label>
                    <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" value="<?= $cliente['telefono_contacto'] ?>" required>
                </div>
                <div class="col-md-12">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?= $cliente['direccion'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="whatsapp" class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?= $cliente['whatsapp'] ?>" pattern="\d{10}">
                </div>
                <div class="col-md-12">
                    <label for="saludo" class="form-label">Saludo</label>
                    <textarea class="form-control" id="saludo" name="saludo" rows="3"><?= $cliente['saludo'] ?></textarea>
                </div>
                <div class="col-md-4">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <input type="color" class="form-control" id="primary_color" name="primary_color" value="<?= $cliente['primary_color'] ?>">
                </div>
                <div class="col-md-4">
                    <label for="secondary_color" class="form-label">Secondary Color</label>
                    <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="<?= $cliente['secondary_color'] ?>">
                </div>
                <div class="col-md-4">
                    <label for="link_color" class="form-label">Link Color</label>
                    <input type="color" class="form-control" id="link_color" name="link_color" value="<?= $cliente['link_color'] ?>">
                </div>
                <div class="mt-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
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
<script src="<?= base_url('assets/js/cliente-perfil.js') ?>"></script>
<?= $this->endSection() ?>