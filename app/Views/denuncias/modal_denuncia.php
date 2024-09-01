<!-- app/Views/denuncias/modal_denuncia.php -->
<div class="modal fade" id="modalDenuncia" tabindex="-1" aria-labelledby="modalDenunciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formDenuncia" action="<?= base_url('denuncias/guardar') ?>" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDenunciaLabel"><?= isset($denuncia) ? 'Editar Denuncia' : 'Agregar Denuncia' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Sección 1: Información del Cliente -->
                        <?php if (isset($esCliente) && $esCliente): ?>
                            <!-- Si es un cliente, mostrar el nombre del cliente como texto y ocultar el select -->
                            <input type="hidden" name="id_cliente" value="<?= session()->get('id_cliente') ?>">
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <p class="form-control-plaintext"><?= session()->get('nombre_cliente') ?></p>
                            </div>
                        <?php else: ?>
                            <!-- Si no es un cliente, mostrar el select para elegir el cliente -->
                            <div class="col-md-6">
                                <label for="id_cliente" class="form-label">Cliente</label>
                                <select class="form-select select2" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>" <?= isset($denuncia['id_cliente']) && $denuncia['id_cliente'] == $cliente['id'] ? 'selected' : '' ?>><?= $cliente['nombre_empresa'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label for="id_sucursal" class="form-label">Sucursal</label>
                            <select class="form-select select2" id="id_sucursal" name="id_sucursal" required>
                                <option value="">Seleccione una sucursal</option>
                                <!-- Las sucursales se cargarán dinámicamente vía JS -->
                            </select>
                        </div>

                        <!-- Sección 2: Detalles de la Denuncia -->
                        <div class="col-md-6">
                            <label for="tipo_denunciante" class="form-label">Tipo de Denunciante</label>
                            <select id="tipo_denunciante" name="tipo_denunciante" class="form-select select2" required>
                                <option value="Colaborador">Colaborador</option>
                                <option value="Proveedor">Proveedor</option>
                                <option value="Cliente">Cliente</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select select2" id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= isset($denuncia['categoria']) && $denuncia['categoria'] == $categoria['id'] ? 'selected' : '' ?>><?= $categoria['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subcategoria" class="form-label">Subcategoría</label>
                            <select class="form-select select2" id="subcategoria" name="subcategoria" required>
                                <option value="">Seleccione una subcategoría</option>
                                <!-- Las subcategorías se cargarán dinámicamente vía JS -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="id_departamento" class="form-label">Departamento</label>
                            <select class="form-select select2" id="id_departamento" name="id_departamento" required>
                                <option value="">Seleccione un departamento</option>
                                <!-- Los departamentos se cargarán dinámicamente vía JS -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_incidente" class="form-label">Fecha del Incidente</label>
                            <input type="text" class="form-control flatpickr" id="fecha_incidente" name="fecha_incidente" value="<?= isset($denuncia['fecha_incidente']) ? $denuncia['fecha_incidente'] : '' ?>" required>
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
                            <input type="text" class="form-control" id="area_incidente" name="area_incidente" value="<?= isset($denuncia['area_incidente']) ? $denuncia['area_incidente'] : '' ?>" required placeholder="Ingrese el área donde sucedió">
                        </div>
                        <div class="col-md-6">
                            <label for="denunciar_a_alguien" class="form-label">Denunciar a Alguien</label>
                            <textarea class="form-control" id="denunciar_a_alguien" name="denunciar_a_alguien" placeholder="Describa a la persona involucrada"><?= isset($denuncia['denunciar_a_alguien']) ? $denuncia['denunciar_a_alguien'] : '' ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" required placeholder="Describa la denuncia"><?= isset($denuncia['descripcion']) ? $denuncia['descripcion'] : '' ?></textarea>
                        </div>

                        <!-- Sección 4: Opciones de Denuncia -->
                        <div class="col-md-6">
                            <label class="form-label">Anónimo</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="anonimo" id="anonimo-si" value="1" <?= isset($denuncia['anonimo']) && $denuncia['anonimo'] == '1' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="anonimo-si">
                                        Sí
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="anonimo" id="anonimo-no" value="0" <?= isset($denuncia['anonimo']) && $denuncia['anonimo'] == '0' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="anonimo-no">
                                        No
                                    </label>
                                </div>
                            </div>
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