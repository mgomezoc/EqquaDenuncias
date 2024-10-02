<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('content') ?>
<section class="container my-5">
    <h1>Registrar una Denuncia</h1>
    <p>Por favor, complete el formulario para reportar una nueva denuncia. Todos los campos son obligatorios.</p>

    <form id="formCrearDenuncia" action="<?= base_url('denuncias/guardar') ?>" method="post" enctype="multipart/form-data">
        <!-- Dato oculto: Cliente -->
        <input type="hidden" name="id_cliente" value="<?= esc($cliente['id']) ?>">
        <input type="hidden" name="tipo_denunciante" value="Cliente"> <!-- Tipo de denunciante predefinido -->

        <div class="row g-4">
            <!-- Sucursal -->
            <div class="col-md-6">
                <label for="id_sucursal" class="form-label">Sucursal</label>
                <select class="form-select select2" id="id_sucursal" name="id_sucursal" required>
                    <option value="">Seleccione una sucursal</option>
                    <?php foreach ($sucursales as $sucursal) : ?>
                        <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Categoría -->
            <div class="col-md-6">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select select2" id="categoria" name="categoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categorias as $categoria) : ?>
                        <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Subcategoría -->
            <div class="col-md-6">
                <label for="subcategoria" class="form-label">Subcategoría</label>
                <select class="form-select select2" id="subcategoria" name="subcategoria" required>
                    <option value="">Seleccione una subcategoría</option>
                </select>
            </div>

            <!-- Departamento -->
            <div class="col-md-6">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select select2" id="id_departamento" name="id_departamento">
                    <option value="">Seleccione un departamento</option>
                </select>
            </div>

            <!-- Fecha del Incidente -->
            <div class="col-md-6">
                <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                <input type="text" class="form-control flatpickr" id="fecha_incidente" name="fecha_incidente" required>
            </div>

            <!-- ¿Cómo se Enteró? -->
            <div class="col-md-6">
                <label for="como_se_entero" class="form-label">¿Cómo se Enteró?</label>
                <select name="como_se_entero" id="como_se_entero" class="form-select select2" required>
                    <option value="Fui víctima">Fui víctima</option>
                    <option value="Fui testigo">Fui testigo</option>
                    <option value="Estaba involucrado">Estaba involucrado</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <!-- Área del Incidente -->
            <div class="col-md-6">
                <label for="area_incidente" class="form-label">Área del Incidente</label>
                <input type="text" class="form-control" id="area_incidente" name="area_incidente" required placeholder="Ingrese el área donde sucedió">
            </div>

            <!-- Denunciar a Alguien -->
            <div class="col-md-6">
                <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien" placeholder="Describa a la persona involucrada"></textarea>
            </div>

            <!-- Descripción -->
            <div class="col-md-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" required placeholder="Describa la denuncia" rows="7"></textarea>
            </div>

            <!-- ¿Es Anónimo? -->
            <div class="col-md-6">
                <label class="form-label">Anónimo</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-si" value="1" required>
                        <label class="form-check-label" for="anonimo-si">Sí</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-no" value="0" required>
                        <label class="form-check-label" for="anonimo-no">No</label>
                    </div>
                </div>
            </div>

            <!-- Archivos Adjuntos -->
            <div class="col-md-12">
                <label for="archivos_adjuntos" class="form-label">Archivos Adjuntos</label>
                <div id="dropzoneArchivos" class="dropzone"></div>
            </div>

            <!-- Audio Input Section -->
            <div class="col-md-12">
                <label for="audio_input" class="form-label">¿Nos quieres dejar un audio?</label>
                <div class="input-group">
                    <!-- Botones de grabación -->
                    <button id="startRecording" type="button" class="btn btn-outline-secondary"><i class="fa fa-microphone"></i> Iniciar Grabación</button>
                    <button id="stopRecording" type="button" class="btn btn-outline-secondary" disabled><i class="fa fa-stop"></i> Detener Grabación</button>

                    <!-- Campo para subir archivos de audio -->
                    <input type="file" name="audio_file" id="audio_input" class="form-control" accept="audio/*">
                </div>
                <!-- Contenedor de la grabación -->
                <audio id="audioPlayback" controls style="display: none;"></audio>
            </div>

        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Enviar Denuncia</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script src="<?= base_url("assets/js/denuncias_public.js") ?>"></script>
<?= $this->endSection() ?>