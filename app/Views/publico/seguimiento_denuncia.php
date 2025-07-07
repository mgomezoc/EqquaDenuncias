<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('content') ?>
<section class="container my-5">

    <div class="text-center mb-5">
        <h1 class="titulo animate__animated animate__fadeIn">Seguimiento a mi Denuncia</h1>
        <p class="text-muted">Ingrese su número de denuncia para verificar el estatus actual y recibir detalles actualizados.</p>
    </div>

    <form id="formBuscarDenuncia" class="mb-5">
        <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $cliente['id'] ?>">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <label for="folio" class="form-label">Número de Folio</label>
                <input type="text" class="form-control form-control-lg" id="folio" name="folio" value="<?= $folio ?>" placeholder="Ej: 20250706-0001" required autofocus>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary btn-lg w-100"><i class="fas fa-search"></i> Consultar</button>
            </div>
        </div>
    </form>

    <div id="resultadoDenuncia" class="animate__animated animate__fadeIn" style="display: none;">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Detalles de la Denuncia</h4>
                        <span id="estado_nombre" class="badge fs-6"></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-hashtag me-2 text-muted"></i>ID de Denuncia:</strong>
                                <p class="text-secondary mb-0" id="denunciaId">N/A</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="far fa-calendar-alt me-2 text-muted"></i>Fecha de Reporte:</strong>
                                <p class="text-secondary mb-0" id="fechaHoraReporte">N/A</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-map-marker-alt me-2 text-muted"></i>Sucursal:</strong>
                                <p class="text-secondary mb-0" id="sucursalNombre">N/A</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-tag me-2 text-muted"></i>Categoría:</strong>
                                <p class="text-secondary mb-0" id="categoriaNombre">N/A</p>
                            </div>
                        </div>
                        <hr>
                        <strong><i class="far fa-file-alt me-2 text-muted"></i>Descripción del Incidente:</strong>
                        <p class="text-secondary" id="descripcionDenuncia">N/A</p>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Añadir Comentario o Evidencia</h5>
                    </div>
                    <div class="card-body">
                        <form id="formAgregarComentario" enctype="multipart/form-data" style="display: none;">
                            <input type="hidden" id="id_denuncia" name="id_denuncia">
                            <div class="mb-3">
                                <label for="nuevo_comentario" class="form-label">Tu Mensaje</label>
                                <textarea class="form-control" id="nuevo_comentario" name="contenido" rows="4" placeholder="Escribe aquí para aportar más información o responder a una solicitud..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="archivo_comentario" class="form-label">Adjuntar Archivo (Opcional)</label>
                                <input class="form-control" type="file" id="archivo_comentario" name="archivo_comentario" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.zip,.rar">
                                <div class="form-text">Puedes adjuntar imágenes, PDF, Word o archivos comprimidos.</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-paper-plane"></i> Enviar Información</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <h4 class="mb-3">Historial de Eventos</h4>
                <div class="timeline-container">
                    <ul id="timeline-historial" class="timeline">
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script>
    // Configuración global de Lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true
    })
</script>
<script src="<?= base_url('assets/js/seguimiento_denuncia.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>