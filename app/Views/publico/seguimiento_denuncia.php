<?= $this->extend('layouts/public_layout') ?>

<?= $this->section('content') ?>
<section class="container my-5">
    <h1 class="text-center text-success">Seguimiento a mi Denuncia</h1>

    <!-- Formulario para buscar denuncia -->
    <form id="formBuscarDenuncia" class="mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <label for="folio" class="form-label">Número de Folio</label>
                <input type="text" class="form-control" id="folio" name="folio" placeholder="Ingrese su número de folio" required autofocus>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">Consultar</button>
            </div>
        </div>
    </form>

    <!-- Sección de resultados -->
    <div id="resultadoDenuncia" style="display: none;">
        <h2>Detalle de la Denuncia</h2>
        <table class="table table-bordered table-striped" id="tablaDetalleDenuncia"></table>

        <h3>Comentarios del Cliente</h3>
        <table class="table table-striped" id="tablaComentarios">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <!-- Formulario para agregar un comentario -->
        <form id="formAgregarComentario" style="display: none;">
            <div class="mb-3">
                <label for="nuevo_comentario" class="form-label">Agregar Comentario</label>
                <textarea class="form-control" id="nuevo_comentario" name="nuevo_comentario" rows="3" required></textarea>
            </div>
            <input type="hidden" id="id_denuncia" name="id_denuncia">
            <button type="submit" class="btn btn-success">Enviar Comentario</button>
        </form>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/seguimiento_denuncia.js') ?>"></script>
<?= $this->endSection() ?>