<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Editar Cliente<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>Editar Cliente</h1>
            <form action="<?= base_url('clientes/actualizar/' . $cliente['id']) ?>" method="post">
                <div class="mb-3">
                    <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?= $cliente['nombre_empresa'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="numero_identificacion" class="form-label">Número de Identificación</label>
                    <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" value="<?= $cliente['numero_identificacion'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="correo_contacto" class="form-label">Correo de Contacto</label>
                    <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" value="<?= $cliente['correo_contacto'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="telefono_contacto" class="form-label">Teléfono de Contacto</label>
                    <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" value="<?= $cliente['telefono_contacto'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?= $cliente['direccion'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?= $cliente['slug'] ?>">
                </div>
                <div class="mb-3">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="text" class="form-control" id="logo" name="logo" value="<?= $cliente['logo'] ?>">
                </div>
                <div class="mb-3">
                    <label for="banner" class="form-label">Banner</label>
                    <input type="text" class="form-control" id="banner" name="banner" value="<?= $cliente['banner'] ?>">
                </div>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>