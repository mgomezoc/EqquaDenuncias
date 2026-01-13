<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Administración de Clientes<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => 'Clientes', 'vista' => 'Clientes']); ?>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Clientes</span>
        <?php if ($rol_slug == 'ADMIN'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearCliente">
                <i class="fa fa-plus"></i> Agregar Cliente
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaClientes" class="table table-sm table-striped table-eqqua">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Empresa</th>
                        <th>Correo Contacto</th>
                        <th>Teléfono Contacto</th>
                        <th>Política de Anonimato</th>
                        <?php if ($rol_slug == 'ADMIN'): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<template id="tplAccionesTabla">
    <?php if ($rol_slug == 'ADMIN'): ?>
        <button class="btn btn-sm btn-danger remove">
            <i class="fa fa-trash"></i>
        </button>
        <button class="btn btn-sm btn-info view-public">
            <i class="fa fa-eye"></i>
        </button>
    <?php endif; ?>
</template>

<template id="tplDetalleTabla">
    <div class="">
        <form id="formEditarCliente-{{id}}" action="<?= base_url('clientes/guardar') ?>" method="post" class="formEditarCliente card custom-card card-body mb-4">
            <input type="hidden" name="id" value="{{id}}">

            <div class="row g-3">
                <!-- Datos generales -->
                <div class="col-md-4">
                    <label for="nombre_empresa-{{id}}" class="form-label">Nombre Empresa</label>
                    <input type="text" class="form-control" id="nombre_empresa-{{id}}" name="nombre_empresa" value="{{nombre_empresa}}" <?= $rol_slug === 'ADMIN' ? 'required' : 'readonly' ?>>
                </div>

                <?php if ($rol_slug === 'ADMIN') : ?>
                    <div class="col-md-4">
                        <label for="numero_identificacion-{{id}}" class="form-label">Número de colaboradores</label>
                        <input type="text" class="form-control" id="numero_identificacion-{{id}}" name="numero_identificacion" value="{{numero_identificacion}}" required>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label for="correo_contacto-{{id}}" class="form-label">Correo Contacto</label>
                    <input type="email" class="form-control" id="correo_contacto-{{id}}" name="correo_contacto" value="{{correo_contacto}}" <?= $rol_slug === 'ADMIN' ? 'required' : 'readonly' ?>>
                </div>

                <div class="col-md-4">
                    <label for="telefono_contacto-{{id}}" class="form-label">Teléfono Contacto</label>
                    <input type="text" class="form-control" id="telefono_contacto-{{id}}" name="telefono_contacto" value="{{telefono_contacto}}" <?= $rol_slug === 'ADMIN' ? 'required' : 'readonly' ?>>
                </div>

                <div class="col-md-4">
                    <label for="direccion-{{id}}" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="direccion-{{id}}" name="direccion" value="{{direccion}}" <?= $rol_slug === 'ADMIN' ? 'required' : 'readonly' ?>>
                </div>

                <?php if ($rol_slug === 'ADMIN') : ?>
                    <div class="col-md-4">
                        <label for="slug-{{id}}" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug-{{id}}" name="slug" value="{{slug}}" required>
                    </div>

                    <div class="col-md-8">
                        <label for="saludo-{{id}}" class="form-label">Saludo</label>
                        <textarea class="form-control" id="saludo-{{id}}" name="saludo" rows="4">{{saludo}}</textarea>
                    </div>

                    <!-- Tipo de denunciante (formulario público): Mostrar/No mostrar -->
                    <div class="col-md-4">
                        <label for="mostrar_tipo_denunciante_publico-{{id}}" class="form-label">Tipo de denunciante (formulario público)</label>
                        <select
                            class="form-select"
                            name="mostrar_tipo_denunciante_publico"
                            id="mostrar_tipo_denunciante_publico-{{id}}"
                            data-value="{{mostrar_tipo_denunciante_publico}}"
                            <?= ($rol_slug === 'ADMIN' || $rol_slug === 'CLIENTE') ? '' : 'disabled' ?>>
                            <option value="0">No mostrar</option>
                            <option value="1">Mostrar</option>
                        </select>
                        <small class="text-muted">
                            Controla si se muestra el combo “Tipo de denunciante” en el formulario público del cliente.
                        </small>
                    </div>

                    <!-- NUEVO: Configuración de opciones permitidas + default -->
                    <div class="col-md-8" id="bloque_tipo_denunciante_publico_config-{{id}}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tipos_denunciante_publico_permitidos-{{id}}" class="form-label">Tipos permitidos (combo público)</label>
                                <select
                                    class="form-select"
                                    id="tipos_denunciante_publico_permitidos-{{id}}"
                                    name="tipos_denunciante_publico_permitidos"
                                    multiple
                                    data-value="{{tipos_denunciante_publico_permitidos}}"
                                    <?= ($rol_slug === 'ADMIN' || $rol_slug === 'CLIENTE') ? '' : 'disabled' ?>>
                                    <option value="Cliente">Cliente</option>
                                    <option value="Colaborador">Colaborador</option>
                                    <option value="Proveedor">Proveedor</option>
                                </select>
                                <small class="text-muted">
                                    Selecciona qué opciones aparecerán en el combo público. Si no se muestra el combo, esta configuración no aplica.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label for="tipo_denunciante_publico_default-{{id}}" class="form-label">Tipo por defecto (formulario público)</label>
                                <select
                                    class="form-select"
                                    id="tipo_denunciante_publico_default-{{id}}"
                                    name="tipo_denunciante_publico_default"
                                    data-value="{{tipo_denunciante_publico_default}}"
                                    <?= ($rol_slug === 'ADMIN' || $rol_slug === 'CLIENTE') ? '' : 'disabled' ?>>
                                    <option value="Cliente">Cliente</option>
                                    <option value="Colaborador">Colaborador</option>
                                    <option value="Proveedor">Proveedor</option>
                                </select>
                                <small class="text-muted">
                                    Si el combo público NO se muestra, este valor será el que se enviará automáticamente. Recomendado: <strong>Colaborador</strong>.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12"></div>

                    <div class="col-md-4">
                        <label for="primary_color-{{id}}" class="form-label">Primary Color</label>
                        <input type="color" class="form-control" id="primary_color-{{id}}" name="primary_color" value="{{primary_color}}">
                    </div>

                    <div class="col-md-4">
                        <label for="secondary_color-{{id}}" class="form-label">Secondary Color</label>
                        <input type="color" class="form-control" id="secondary_color-{{id}}" name="secondary_color" value="{{secondary_color}}">
                    </div>

                    <div class="col-md-4">
                        <label for="link_color-{{id}}" class="form-label">Link Color</label>
                        <input type="color" class="form-control" id="link_color-{{id}}" name="link_color" value="{{link_color}}">
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label for="whatsapp-{{id}}" class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="whatsapp-{{id}}" name="whatsapp" value="{{whatsapp}}" <?= $rol_slug === 'ADMIN' ? '' : 'readonly' ?> pattern="\d{10}">
                </div>

                <!-- Política de anonimato -->
                <div class="col-md-4">
                    <label for="politica_anonimato-{{id}}" class="form-label">Política de anonimato</label>
                    <select
                        class="form-select"
                        name="politica_anonimato"
                        id="politica_anonimato-{{id}}"
                        data-value="{{politica_anonimato}}"
                        <?= ($rol_slug === 'ADMIN' || $rol_slug === 'CLIENTE') ? '' : 'disabled' ?>>
                        <option value="0">Opcional (reportante decide)</option>
                        <option value="1">Forzar anónimas</option>
                        <option value="2">Forzar identificadas</option>
                    </select>
                    <small class="text-muted">
                        Esta política se aplicará a <strong>todas</strong> las altas de denuncias (internas y formulario público).
                    </small>
                </div>

                <?php if ($rol_slug === 'ADMIN' || $rol_slug === 'CLIENTE') : ?>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar configuración
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <!-- Imágenes -->
        <form id="formActualizarImagenes-{{id}}" class="formActualizarImagenes card custom-card">
            <input type="hidden" name="id" value="{{id}}">
            <div class="card-body">
                <div class="row g-4">
                    <?php if ($rol_slug === 'ADMIN' || $rol_slug === 'AGENTE') : ?>
                        <div class="col-md-6">
                            <div class="card border-light mb-3">
                                <div class="card-header text-center bg-light">
                                    <h5 class="mb-0">Logo</h5>
                                </div>
                                <div class="card-body text-center">
                                    <a href="{{logo}}" data-lightbox="cliente-{{id}}-logo" data-title="Logo de {{nombre_empresa}}">
                                        <img src="{{logo}}" alt="logo" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    </a>
                                    <?php if ($rol_slug === 'ADMIN'): ?>
                                        <label for="logo" class="form-label d-block">Subir Nuevo Logo</label>
                                        <div id="dropzoneLogo-{{id}}" class="dropzone mb-3"></div>
                                        <small class="text-muted d-block">Solo si adjuntas una nueva imagen, esta reemplazará la actual.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <div class="card border-light mb-3">
                            <div class="card-header text-center bg-light">
                                <h5 class="mb-0">Banner</h5>
                            </div>
                            <div class="card-body text-center">
                                <a href="{{banner}}" data-lightbox="cliente-{{id}}-banner" data-title="Banner de {{nombre_empresa}}">
                                    <img src="{{banner}}" alt="banner" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                </a>
                                <?php if ($rol_slug === 'ADMIN'): ?>
                                    <label for="banner" class="form-label d-block">Subir Nuevo Banner</label>
                                    <div id="dropzoneBanner-{{id}}" class="dropzone mb-3"></div>
                                    <small class="text-muted d-block">Solo si adjuntas una nueva imagen, esta reemplazará la actual.</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($rol_slug == 'ADMIN'): ?>
                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Actualizar Imágenes
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<!-- Modal Crear Cliente -->
<div class="modal fade" id="modalCrearCliente" tabindex="-1" aria-labelledby="modalCrearClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCrearCliente" action="<?= base_url('clientes/guardar') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearClienteLabel">Agregar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre_empresa" class="form-label">Nombre Empresa</label>
                            <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
                        </div>

                        <div class="col-md-6">
                            <label for="numero_identificacion" class="form-label">Número de colaboradores</label>
                            <input type="text" class="form-control" id="numero_identificacion" name="numero_identificacion" required>
                        </div>

                        <div class="col-md-6">
                            <label for="correo_contacto" class="form-label">Correo Contacto</label>
                            <input type="email" class="form-control" id="correo_contacto" name="correo_contacto" required>
                        </div>

                        <div class="col-md-6">
                            <label for="telefono_contacto" class="form-label">Teléfono Contacto</label>
                            <input type="text" class="form-control" id="telefono_contacto" name="telefono_contacto" required>
                        </div>

                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>

                        <div class="col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" required>
                        </div>

                        <!-- Política al crear -->
                        <div class="col-md-6">
                            <label for="politica_anonimato" class="form-label">Política de anonimato</label>
                            <select class="form-select" name="politica_anonimato" id="politica_anonimato">
                                <option value="0">Opcional (reportante decide)</option>
                                <option value="1">Forzar anónimas</option>
                                <option value="2">Forzar identificadas</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="saludo" class="form-label">Saludo</label>
                            <textarea class="form-control" id="saludo" name="saludo" rows="4"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" pattern="\d{10}">
                        </div>

                        <!-- Tipo de denunciante (formulario público) al crear -->
                        <div class="col-md-6">
                            <label for="mostrar_tipo_denunciante_publico" class="form-label">Tipo de denunciante (formulario público)</label>
                            <select class="form-select" name="mostrar_tipo_denunciante_publico" id="mostrar_tipo_denunciante_publico">
                                <option value="0">No mostrar</option>
                                <option value="1">Mostrar</option>
                            </select>
                        </div>

                        <!-- NUEVO: Permisos + Default (al crear) -->
                        <div class="col-md-6">
                            <label for="tipos_denunciante_publico_permitidos" class="form-label">Tipos permitidos (combo público)</label>
                            <select class="form-select" id="tipos_denunciante_publico_permitidos" name="tipos_denunciante_publico_permitidos" multiple>
                                <option value="Cliente">Cliente</option>
                                <option value="Colaborador" selected>Colaborador</option>
                                <option value="Proveedor">Proveedor</option>
                            </select>
                            <small class="text-muted">Selecciona las opciones que aparecerán si el combo se muestra.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="tipo_denunciante_publico_default" class="form-label">Tipo por defecto (formulario público)</label>
                            <select class="form-select" id="tipo_denunciante_publico_default" name="tipo_denunciante_publico_default">
                                <option value="Cliente">Cliente</option>
                                <option value="Colaborador" selected>Colaborador</option>
                                <option value="Proveedor">Proveedor</option>
                            </select>
                            <small class="text-muted">Si no se muestra el combo, este valor se enviará automáticamente.</small>
                        </div>

                        <div class="col-md-4">
                            <label for="primary_color" class="form-label">Primary Color</label>
                            <input type="color" class="form-control" id="primary_color" name="primary_color">
                        </div>

                        <div class="col-md-4">
                            <label for="secondary_color" class="form-label">Secondary Color</label>
                            <input type="color" class="form-control" id="secondary_color" name="secondary_color">
                        </div>

                        <div class="col-md-4">
                            <label for="link_color" class="form-label">Link Color</label>
                            <input type="color" class="form-control" id="link_color" name="link_color">
                        </div>

                        <div class="col-md-6">
                            <label for="logo" class="form-label">Logo</label>
                            <div id="dropzoneLogo" class="dropzone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="banner" class="form-label">Banner</label>
                            <div id="dropzoneBanner" class="dropzone"></div>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
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
<script>
    const rol = '<?= $rol_slug ?>';
</script>
<script src="<?= base_url('assets/js/clientes.js') ?>?v=<?= config('App')->assetVersion ?>"></script>
<?= $this->endSection() ?>