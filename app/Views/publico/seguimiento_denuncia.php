<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('content') ?>
<section class="container my-5">

    <div class="text-center mb-5">
        <h1 class="titulo animate__animated animate__fadeIn">Seguimiento a mi Denuncia</h1>
        <p class="text-muted">Ingrese su número de denuncia para verificar el estado actual y recibir detalles actualizados.</p>
    </div>


    <!-- Formulario para buscar denuncia -->
    <form id="formBuscarDenuncia" class="mb-5">
        <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $cliente['id'] ?>">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <label for="folio" class="form-label">Número de Folio</label>
                <input type="text" class="form-control" id="folio" name="folio" value="<?= $folio ?>" placeholder="Ingrese su número de folio" required autofocus>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-search"></i> Consultar</button>
            </div>
        </div>
    </form>

    <!-- Sección de resultados -->
    <div id="resultadoDenuncia" class="animate__animated animate__fadeIn" style="display: none;">
        <h2 class="mb-4">Detalle de la Denuncia</h2>
        <div class="card mb-4">
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item"><strong>ID de Denuncia:</strong> <span id="denunciaId">N/A</span></li>
                    <li class="list-group-item"><strong>Fecha y Hora de Reporte:</strong> <span id="fechaHoraReporte">N/A</span></li>
                    <li class="list-group-item"><strong>Sucursal:</strong> <span id="sucursalNombre">N/A</span></li>
                    <li class="list-group-item"><strong>Categoría:</strong> <span id="categoriaNombre">N/A</span></li>
                    <li class="list-group-item"><strong>Subcategoría:</strong> <span id="subcategoriaNombre">N/A</span></li>
                    <li class="list-group-item"><strong>Descripción:</strong> <span id="descripcionDenuncia">N/A</span></li>
                </ul>
            </div>
        </div>

        <!-- Comentarios estilo chat -->
        <h3>Comentarios</h3>
        <div id="contenedorComentarios" class="mb-4" style="max-height: 400px; overflow-y: auto;"></div>

        <!-- Archivos Adjuntos -->
        <div id="archivosAdjuntos" style="display: none;">
            <h3>Archivos Adjuntos</h3>
            <ul id="listaArchivos" class="list-unstyled row"></ul>
        </div>

        <!-- Formulario para agregar comentarios -->
        <form id="formAgregarComentario" style="display: none;">
            <div class="mb-3">
                <textarea class="form-control" id="nuevo_comentario" name="contenido" rows="3" placeholder="Escribe tu comentario..." required></textarea>
            </div>
            <input type="hidden" id="id_denuncia" name="id_denuncia">
            <button type="submit" class="btn btn-success"><i class="fas fa-comment-dots"></i> Enviar Comentario</button>
        </form>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script src="<?= base_url('assets/js/seguimiento_denuncia.js') ?>"></script>
<?= $this->endSection() ?>