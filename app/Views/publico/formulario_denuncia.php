<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('content') ?>
<section class="container my-5">
    <div class="text-center mb-5">
        <h1 class="titulo animate__animated animate__fadeIn">Registrar una Denuncia</h1>
        <p class="text-muted">Complete el formulario para reportar una denuncia. Todos los campos son obligatorios.</p>
    </div>

    <form id="formCrearDenuncia" action="<?= base_url('denuncias/guardar') ?>" method="post" enctype="multipart/form-data">
        <!-- Dato oculto: Cliente -->
        <input type="hidden" name="id_cliente" value="<?= esc($cliente['id']) ?>">
        <input type="hidden" name="tipo_denunciante" value="Cliente">

        <!-- Agrupación de campos en secciones lógicas -->
        <div class="row g-4">

            <!-- Información de sucursal y categoría -->
            <div class="col-md-6">
                <label for="id_sucursal" class="form-label">Sucursal</label>
                <select class="form-select select2" id="id_sucursal" name="id_sucursal" required>
                    <option value="">Seleccione una sucursal</option>
                    <?php foreach ($sucursales as $sucursal): ?>
                        <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select select2" id="categoria" name="categoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Subcategoría y Departamento -->
            <div class="col-md-6">
                <label for="subcategoria" class="form-label">Subcategoría</label>
                <select class="form-select select2" id="subcategoria" name="subcategoria" required>
                    <option value="">Seleccione una subcategoría</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="id_departamento" class="form-label">Departamento</label>
                <select class="form-select select2" id="id_departamento" name="id_departamento">
                    <option value="">Seleccione un departamento</option>
                </select>
            </div>

            <!-- Información del incidente -->
            <div class="col-md-6">
                <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                <input type="text" class="form-control flatpickr" id="fecha_incidente" name="fecha_incidente" required>
            </div>

            <div class="col-md-6">
                <label for="como_se_entero" class="form-label">¿Cómo se Enteró?</label>
                <select class="form-select select2" id="como_se_entero" name="como_se_entero" required>
                    <option value="Fui víctima">Fui víctima</option>
                    <option value="Fui testigo">Fui testigo</option>
                    <option value="Estaba involucrado">Estaba involucrado</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <!-- Descripción del área y la denuncia -->
            <div class="col-md-6">
                <label for="area_incidente" class="form-label">Área del Incidente</label>
                <input type="text" class="form-control" id="area_incidente" name="area_incidente" required placeholder="Ingrese el área donde sucedió">
            </div>

            <div class="col-md-6">
                <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien" placeholder="Describa a la persona involucrada"></textarea>
            </div>

            <!-- Descripción completa del incidente -->
            <div class="col-md-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" required placeholder="Describa la denuncia" rows="5"></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">¿Es anónimo?</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-si" value="1" checked required>
                        <label class="form-check-label" for="anonimo-si">Sí</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="anonimo" id="anonimo-no" value="0" required>
                        <label class="form-check-label" for="anonimo-no">No</label>
                    </div>
                </div>
            </div>

            <!-- Información adicional cuando no es anónimo -->
            <div id="infoAdicional" class="row g-3" style="display: none;">
                <div class="col-md-6">
                    <label for="nombre_completo" class="form-label">Nombre Completo</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" placeholder="Ingrese su nombre completo">
                </div>

                <div class="col-md-6">
                    <label for="correo_electronico" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" placeholder="Ingrese su correo electrónico">
                </div>

                <div class="col-md-6">
                    <label for="telefono" class="form-label">Teléfono (opcional)</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ingrese su teléfono (opcional)">
                </div>
            </div>

            <!-- Adjuntos -->
            <div class="col-md-12">
                <label for="archivos_adjuntos" class="form-label">Archivos Adjuntos</label>
                <div id="dropzoneArchivos" class="dropzone border p-3" style="border-radius: 10px;"></div>
            </div>

            <!-- Audio -->
            <div class="col-md-12">
                <label for="audio_input" class="form-label">¿Nos quieres dejar un audio?</label>
                <div class="input-group">
                    <button id="startRecording" type="button" class="btn btn-outline-secondary"><i class="fa fa-microphone"></i> Iniciar Grabación</button>
                    <button id="stopRecording" type="button" class="btn btn-outline-secondary" disabled><i class="fa fa-stop"></i> Detener Grabación</button>
                    <input type="file" name="audio_file" id="audio_input" class="form-control" accept="audio/*">
                </div>
                <audio id="audioPlayback" controls style="display: none;" class="mt-3"></audio>
            </div>
        </div>

        <!-- Botón de envío -->
        <div class="mt-5 text-center">
            <button type="submit" class="btn btn-secondary btn-lg px-5">
                <i class="fa fa-paper-plane me-1"></i> Enviar Denuncia
            </button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script>
    const slug = '<?= $cliente['slug'] ?>';
</script>
<script src="<?= base_url("assets/js/denuncias_public.js") ?>"></script>
<?= $this->endSection() ?>