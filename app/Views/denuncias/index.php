<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Denuncias<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Denuncias', 'vista' => 'Denuncias']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Denuncias</span>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearDenuncia">
            <i class="fa fa-plus"></i> Agregar Denuncia
        </button>
    </div>
    <div class="card-body">
        <div class="p-3 border">
            <table id="tablaDenuncias" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Sucursal</th>
                        <th>Tipo Denunciante</th>
                        <th>Categoría</th>
                        <th>Subcategoría</th>
                        <th>Departamento</th>
                        <th>Estado</th>
                        <th>medio_recepcion</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<template id="tplAccionesTabla">
    <div class="dropdown">
        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-ellipsis-v"></i><span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item view-detail" href="#">
                    <i class="fas fa-eye me-2"></i> Ver Detalle
                </a>
            </li>
            <li>
                <a class="dropdown-item view-comments" href="#">
                    <i class="fas fa-comments me-2"></i> Ver Comentarios
                </a>
            </li>
            <li>
                <a class="dropdown-item change-status" href="#">
                    <i class="fas fa-exchange-alt me-2"></i> Cambiar Estado
                </a>
            </li>
            <li>
                <a class="dropdown-item remove" href="#">
                    <i class="fas fa-trash-alt text-danger me-2"></i> Eliminar
                </a>
            </li>
        </ul>
    </div>
</template>

<template id="tplDetalleTabla">
    <div class="">
        <!-- Formulario para editar la denuncia -->
        <form id="formEditarDenuncia-{{id}}" action="<?= base_url('denuncias/guardar') ?>" method="post" class="formEditarDenuncia card custom-card card-body mb-4">
            <input type="hidden" name="id" value="{{id}}">
            <div class="row g-3">
                <!-- Información General -->
                <div class="col-md-4">
                    <label for="id_cliente-{{id}}" class="form-label">Cliente</label>
                    <select class="form-select select2" id="id_cliente-{{id}}" name="id_cliente" required>
                        {{{selectOptions clientes id_cliente}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="id_sucursal-{{id}}" class="form-label">Sucursal</label>
                    <select class="form-select select2" id="id_sucursal-{{id}}" name="id_sucursal" required>
                        {{{selectOptions sucursales id_sucursal}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="categoria-{{id}}" class="form-label">Categoría</label>
                    <select class="form-select select2" id="categoria-{{id}}" name="categoria">
                        {{{selectOptions categorias categoria}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="subcategoria-{{id}}" class="form-label">Subcategoría</label>
                    <select class="form-select select2" id="subcategoria-{{id}}" name="subcategoria">
                        {{{selectOptions subcategorias subcategoria}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="id_departamento-{{id}}" class="form-label">Departamento</label>
                    <select class="form-select select2" id="id_departamento-{{id}}" name="id_departamento">
                        {{{selectOptions departamentos id_departamento}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="estado_actual-{{id}}" class="form-label">Estado</label>
                    <select id="estado_actual-{{id}}" name="estado_actual" class="form-select select2">
                        {{{selectOptions estados estado_actual}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="fecha_incidente-{{id}}" class="form-label">Fecha del Incidente</label>
                    <input type="text" class="form-control flatpickr" id="fecha_incidente-{{id}}" name="fecha_incidente" value="{{fecha_incidente}}" required>
                </div>
                <div class="col-md-4">
                    <label for="como_se_entero-{{id}}" class="form-label">¿Cómo se Enteró?</label>
                    <select name="como_se_entero" id="como_se_entero-{{id}}" class="form-select select2" required>
                        {{{selectOptions comboComoSeEntero como_se_entero}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="area_incidente-{{id}}" class="form-label">Área del Incidente</label>
                    <input type="text" class="form-control" id="area_incidente-{{id}}" name="area_incidente" value="{{area_incidente}}" required>
                </div>
                <div class="col-md-4">
                    <label for="denunciar_a_alguien-{{id}}" class="form-label">Denunciar a Alguien</label>
                    <textarea class="form-control" id="denunciar_a_alguien-{{id}}" name="denunciar_a_alguien">{{denunciar_a_alguien}}</textarea>
                </div>
                <div class="col-md-12">
                    <label for="descripcion-{{id}}" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion-{{id}}" name="descripcion" rows="14" required>{{descripcion}}</textarea>
                </div>
                <div class="col-md-4">
                    <label for="medio_recepcion-{{id}}" class="form-label">Canal de Recepción</label>
                    <select name="medio_recepcion" id="medio_recepcion-{{id}}" class="form-select select2" required>
                        {{{selectOptions comboMedioRecepcion medio_recepcion}}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Anónimo</label>
                    <div class="d-flex align-items-center">
                        {{#ifCond anonimo '==' '1'}}
                            <span>Sí</span>
                            {{else}}
                                <span>No</span>
                        {{/ifCond}}
                    </div>
                </div>
                <hr>
                {{#if esAnonimo}}
                    <div class="col-md-4">
                        <label for="nombre_completo-{{id}}" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre_completo-{{id}}" name="nombre_completo" value="{{nombre_completo}}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="correo_electronico-{{id}}" class="form-label">Correo Electrónico</label>
                        <input type="text" class="form-control" id="correo_electronico-{{id}}" name="correo_electronico" value="{{correo_electronico}}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="telefono-{{id}}" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono-{{id}}" name="telefono" value="{{telefono}}" readonly>
                    </div>
                {{/if}}

                <!-- Botón de Actualizar -->
                <div class="mt-5">
                    <button type="submit" class="btn btn-primary btn-actualizar-denuncia">
                        <i class="fa fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </form>

        <!-- Formulario para actualizar imágenes -->
        <form id="formActualizarAnexos-{{id}}" class="formActualizarAnexos card custom-card" enctype="multipart/form-data">
            <input type="hidden" name="id" value="{{id}}">
            <div class="card-body">
                <div class="row g-4">
                    <!-- Sección de Archivos Existentes -->
                    <div class="col-md-6">
                        <div class="card border-light mb-3">
                            <div class="card-header text-center bg-light">
                                <h5 class="mb-0">Archivos Existentes</h5>
                            </div>
                            <div class="card-body">
                                {{#each anexos}}
                                    <div class="card mb-3">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            {{#ifCond tipo '==' 'application/pdf'}}
                                                <a href="<?= base_url('/') ?>{{ruta_archivo}}" data-lightbox="pdf-{{id}}" data-title="{{nombre_archivo}}" class="pdf-viewer">{{nombre_archivo}}</a>
                                {{else}}
                                    <a href="<?= base_url('/') ?>{{ruta_archivo}}" data-fancybox="gallery" data-caption="{{nombre_archivo}}">{{nombre_archivo}}</a>
                                            {{/ifCond}}
                                            <button type="button" class="btn btn-danger btn-sm delete-anexo" data-id="{{id}}">
                                                <i class="fa fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                {{else}}
                                    <p class="text-center">No hay archivos adjuntos.</p>
                                {{/each}}
                            </div>
                        </div>
                    </div>
                    <!-- Sección para Subir Nuevos Archivos -->
                    <div class="col-md-6">
                        <div class="card border-light mb-3">
                            <div class="card-header text-center bg-light">
                                <h5 class="mb-0">Subir Nuevos Archivos</h5>
                            </div>
                            <div class="card-body text-center">
                                <div id="dropzoneArchivos-{{id}}" class="dropzone mb-3"></div>
                                <small class="text-muted d-block">Solo si adjuntas nuevos archivos, estos se agregarán a la denuncia.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Actualizar Archivos
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Modal para Ver Detalle -->
<div class="modal fade" id="modalVerDetalle" tabindex="-1" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerDetalleLabel">Detalle de Denuncia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Aquí se inyectará el contenido del detalle de la denuncia -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para comentarios -->
<div class="modal fade" id="modalVerComentarios" tabindex="-1" aria-labelledby="comentariosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comentariosModalLabel">
                    <i class="fas fa-comments"></i> Comentarios de la Denuncia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="comentariosContainer" class="overflow-auto" style="max-height: 300px;">
                    <!-- Aquí se insertan los comentarios -->
                </div>
                <form id="formAgregarComentario" class="mt-4">
                    <input type="hidden" name="id_denuncia" id="id_denuncia">
                    <div class="form-group">
                        <label for="contenidoComentario" class="form-label">Agregar Comentario</label>
                        <textarea name="contenido" id="contenidoComentario" class="form-control" rows="3" placeholder="Escribe tu comentario aquí..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCambiarEstadoLabel">Cambiar Estado de Denuncia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Aquí se inyectará el formulario para cambiar el estado -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Denuncia -->
<div class="modal fade" id="modalCrearDenuncia" tabindex="-1" aria-labelledby="modalCrearDenunciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCrearDenuncia" action="<?= base_url('denuncias/guardar') ?>" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearDenunciaLabel">Agregar Denuncia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Sección 0: Opciones de Denuncia -->
                        <div class="col-md-6">
                            <label for="medio_recepcion" class="form-label">Canal de Recepción</label>
                            <select name="medio_recepcion" id="medio_recepcion" class="form-select select2" required>
                                <option value="" selected disabled></option>
                                <option value="Llamada">Llamada</option>
                                <option value="Formulario">Formulario</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Email">Email</option>
                            </select>
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
                        <div id="infoAdicional" class="row g-3" style="display: none;" aria-hidden="true">
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
                        <hr>
                        <!-- Sección 1: Información del Cliente -->
                        <div class="col-md-6">
                            <label for="id_cliente" class="form-label">Cliente</label>
                            <select class="form-select select2" id="id_cliente" name="id_cliente" required>
                                <option value="">Seleccione un cliente</option>
                                <?php foreach ($clientes as $cliente) : ?>
                                    <option value="<?= $cliente['id'] ?>"><?= $cliente['nombre_empresa'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="id_sucursal" class="form-label">Sucursal</label>
                            <select class="form-select select2" id="id_sucursal" name="id_sucursal" required>
                                <option value="">Seleccione una sucursal</option>
                            </select>
                        </div>

                        <!-- Sección 2: Detalles de la Denuncia -->
                        <div class="col-md-6">
                            <label for="tipo_denunciante" class="form-label">Tipo de Denunciante</label>
                            <select id="tipo_denunciante" name="tipo_denunciante" class="form-select select2" required>
                                <option value="Colaborador">Colaborador</option>
                                <option value="Proveedor">Proveedor</option>
                                <option value="Cliente">Cliente</option>
                                <option value="No se">No se</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select select2" id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria) : ?>
                                    <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                        <div class="col-md-6">
                            <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                            <input type="text" class="form-control flatpickr" id="fecha_incidente" name="fecha_incidente" required>
                        </div>
                        <div class="col-md-6">
                            <label for="como_se_entero" class="form-label">¿Cómo se Enteró?</label>
                            <select name="como_se_entero" id="como_se_entero" class="form-select select2" required>
                                <option value="Fui víctima">Fui víctima</option>
                                <option value="Fui testigo">Fui testigo</option>
                                <option value="Estaba involucrado">Estaba involucrado</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <!-- Sección 3: Detalles Adicionales -->
                        <div class="col-md-6">
                            <label for="area_incidente" class="form-label">Área del Incidente</label>
                            <input type="text" class="form-control" id="area_incidente" name="area_incidente" required placeholder="Ingrese el área donde sucedió">
                        </div>
                        <div class="col-md-6">
                            <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                            <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien" placeholder="Describa a la persona involucrada"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" required placeholder="Describa la denuncia" rows="14"></textarea>
                        </div>

                        <!-- Sección 5: Archivos Adjuntos -->
                        <div class="col-md-12">
                            <label for="archivos_adjuntos" class="form-label">Archivos Adjuntos</label>
                            <div id="dropzoneArchivos" class="dropzone"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4/dist/fancybox.css" />
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.23.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.22.1/locale/bootstrap-table-es-MX.min.js"></script>
<script src="<?= base_url('assets/js/bootstrap-table-config.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4/dist/fancybox.umd.js"></script>
<script src="<?= base_url('assets/js/denuncias.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>