/**
 * DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;
let dropzones = {};

Dropzone.autoDiscover = false;

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearDenuncia = $('#modalCrearDenuncia');

    $('input[name="anonimo"]').on('change', function () {
        if ($(this).val() == '0') {
            $('#infoAdicional').show(); // Mostrar los campos adicionales si no es anónimo
        } else {
            $('#infoAdicional').hide(); // Ocultar los campos adicionales si es anónimo
        }
    });

    // Inicializar select2 en los selects dentro del modal
    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

    // Configurar la validación del formulario
    $('#formCrearDenuncia').validate({
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        errorPlacement: function (error, element) {
            if (element.hasClass('select2') && element.next('.select2-container').length) {
                error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
            } else if (element.is('input[type="checkbox"]') || element.is('input[type="radio"]')) {
                error.addClass('invalid-feedback').insertAfter(element.closest('div'));
            } else {
                error.addClass('invalid-feedback').insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection').addClass(errorClass).removeClass(validClass);
            } else {
                $(element).addClass(errorClass).removeClass(validClass);
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection').removeClass(errorClass).addClass(validClass);
            } else {
                $(element).removeClass(errorClass).addClass(validClass);
            }
        },
        rules: {
            id_cliente: {
                required: true
            },
            id_sucursal: {
                required: true
            },
            categoria: {
                required: true
            },
            subcategoria: {
                required: true
            },
            fecha_incidente: {
                required: true,
                date: true
            },
            descripcion: {
                required: true
            }
        },
        messages: {
            id_cliente: {
                required: 'Por favor seleccione un cliente'
            },
            id_sucursal: {
                required: 'Por favor seleccione una sucursal'
            },
            categoria: {
                required: 'Por favor seleccione una categoría'
            },
            subcategoria: {
                required: 'Por favor seleccione una subcategoría'
            },
            fecha_incidente: {
                required: 'Por favor ingrese la fecha del incidente',
                date: 'Ingrese una fecha válida'
            },
            descripcion: {
                required: 'Por favor ingrese la descripción'
            }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = new FormData(form);

            loadingFormXHR($frm, true);

            // Enviar la solicitud AJAX para guardar la denuncia
            $.ajax({
                url: `${Server}denuncias/guardar`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $tablaDenuncias.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la denuncia.', 'success');

                    // Limpiar el formulario y los estilos de validación
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                    $frm.find('.is-invalid').removeClass('is-invalid');

                    // Resetear todos los select2
                    $frm.find('.select2').val(null).trigger('change', true);

                    // Limpiar los archivos de Dropzone
                    if (dropzones['archivosAdjuntos']) {
                        dropzones['archivosAdjuntos'].removeAllFiles(true);
                    }

                    $modalCrearDenuncia.modal('hide');
                },
                error: function (xhr) {
                    loadingFormXHR($frm, false);
                    if (xhr.status === 409) {
                        const response = JSON.parse(xhr.responseText);
                        showToast(response.message, 'error');
                    }
                }
            });
        }
    });

    // Cuando se selecciona una opción en select2, se debe actualizar la validación
    $('#modalCrearDenuncia .select2').on('change', function (e, trigger) {
        if (!trigger) {
            $(this).valid();
        }
    });

    // Resetear el formulario al cerrar el modal de creación
    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();

        // Reinicializar select2
        $form.find('.select2').val(null).trigger('change'); // <--- Asegúrate de reinicializar los select2

        // Resetear los archivos subidos en Dropzone
        if (dropzones['archivosAdjuntos']) {
            dropzones['archivosAdjuntos'].removeAllFiles(true);
        }
    });

    // Funcionalidades para los botones de la tabla
    window.operateEvents = {
        // Funcionalidad para el botón de eliminar
        'click .remove': function (e, value, row, index) {
            confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(result => {
                if (result.isConfirmed) {
                    // Si se confirma, proceder con la eliminación
                    $.ajax({
                        url: `${Server}denuncias/eliminar/${row.id}`,
                        method: 'POST',
                        success: function () {
                            showToast('Denuncia eliminada correctamente.', 'success');
                            $tablaDenuncias.bootstrapTable('refresh');
                        },
                        error: function () {
                            showToast('Error al eliminar la denuncia.', 'error');
                        }
                    });
                }
            });
        },

        // Funcionalidad para el botón de ver detalle
        'click .view-detail': function (e, value, row, index) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));
                const contenido = `
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Folio:</strong> ${data.folio}</p>
                    <p><strong>Cliente:</strong> ${data.cliente_nombre || 'N/A'}</p>
                    <p><strong>Sucursal:</strong> ${data.sucursal_nombre || 'N/A'}</p>
                    <p><strong>Tipo de Denunciante:</strong> ${data.tipo_denunciante}</p>
                    <p><strong>Categoría:</strong> ${data.categoria_nombre || 'N/A'}</p>
                    <p><strong>Subcategoría:</strong> ${data.subcategoria_nombre || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Departamento:</strong> ${data.departamento_nombre || 'N/A'}</p>
                    <p><strong>Estado:</strong> ${data.estado_nombre}</p>
                    <p><strong>Fecha del Incidente:</strong> ${data.fecha_incidente}</p>
                    <p><strong>Área del Incidente:</strong> ${data.area_incidente || 'N/A'}</p>
                    <p><strong>¿Cómo se Enteró?:</strong> ${data.como_se_entero || 'N/A'}</p>
                    <p><strong>Denunciar a Alguien:</strong> ${data.denunciar_a_alguien || 'N/A'}</p>
                </div>
                <div class="col-12 mt-3">
                    <p><strong>Descripción:</strong></p>
                    <p>${data.descripcion || 'N/A'}</p>
                </div>
                <div class="col-12 mt-3">
                    <h5>Historial de Seguimiento</h5>
                    <table class="table table-sm table-striped table-bordered table-eqqua-quaternary">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>De</th>
                                <th>A</th>
                                <th>Comentario</th>
                                <th>Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.seguimientos
                                .map(
                                    seg => `
                                    <tr>
                                        <td>${seg.fecha}</td>
                                        <td>${seg.estado_anterior_nombre}</td>
                                        <td>${seg.estado_nuevo_nombre}</td>
                                        <td>${seg.comentario || 'N/A'}</td>
                                        <td>${seg.usuario_nombre}</td>
                                    </tr>
                                `
                                )
                                .join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;

                $('#modalVerDetalle .modal-body').html(contenido);
                modal.show();
            });
        },

        // Funcionalidad para el botón de cambiar estado
        'click .change-status': function (e, value, row, index) {
            $.get(`${Server}denuncias/obtenerEstados`, function (estados) {
                let opciones = '';
                estados.forEach(estado => {
                    const selected = estado.id === row.estado_actual ? 'selected' : '';
                    opciones += `<option value="${estado.id}" ${selected}>${estado.nombre}</option>`;
                });

                const modal = new bootstrap.Modal($('#modalCambiarEstado'));
                $('#modalCambiarEstado .modal-body').html(`
            <form id="formCambiarEstado">
                <div class="mb-3">
                    <label for="estado_nuevo" class="form-label">Nuevo Estado</label>
                    <select id="estado_nuevo" name="estado_nuevo" class="form-select">
                        ${opciones}
                    </select>
                </div>
                <div class="mb-3">
                    <label for="comentario" class="form-label">Comentario (opcional)</label>
                    <textarea id="comentario" name="comentario" class="form-control" rows="3" placeholder="Escribe un comentario..."></textarea>
                </div>
            </form>
        `);
                $('#modalCambiarEstado .modal-footer .btn-primary')
                    .off('click')
                    .on('click', function () {
                        const estadoNuevo = $('#estado_nuevo').val();
                        const comentario = $('#comentario').val(); // Obtener el comentario

                        $.post(
                            `${Server}denuncias/cambiarEstado`,
                            {
                                id: row.id,
                                estado_nuevo: estadoNuevo,
                                comentario: comentario // Enviar el comentario al servidor
                            },
                            function () {
                                showToast('Estado actualizado correctamente.', 'success');
                                $tablaDenuncias.bootstrapTable('refresh');
                                modal.hide();
                            }
                        ).fail(function () {
                            showToast('Error al actualizar el estado.', 'error');
                        });
                    });
                modal.show();
            });
        },

        'click .view-comments': function (e, value, row, index) {
            // Cargar comentarios de la denuncia
            cargarComentarios(row.id);

            // Establecer la ID de la denuncia en el formulario
            $('#id_denuncia').val(row.id);

            // Mostrar el modal
            $('#modalVerComentarios').modal('show');
        }
    };

    // Agregar flatpickr a la fecha del incidente al cargar el detalle para edición
    function initializeFlatpickrForEdit(selector) {
        $(selector).flatpickr({
            dateFormat: 'Y-m-d',
            maxDate: 'today' // Restringe la selección a fechas anteriores o iguales a la actual
        });
    }

    // Inicialización de la tabla de denuncias
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listar`,
        columns: [
            {
                field: 'id',
                title: 'ID'
            },
            {
                field: 'folio',
                title: 'Folio'
            },
            {
                field: 'cliente_nombre',
                title: 'Cliente'
            },
            {
                field: 'sucursal_nombre',
                title: 'Sucursal'
            },
            {
                field: 'tipo_denunciante',
                title: 'Tipo Denunciante'
            },
            {
                field: 'categoria_nombre',
                title: 'Categoría'
            },
            {
                field: 'subcategoria_nombre',
                title: 'Subcategoría'
            },
            {
                field: 'departamento_nombre',
                title: 'Departamento'
            },
            {
                field: 'estado_nombre',
                title: 'Estado',
                formatter: operateFormatterEstado
            },
            {
                field: 'medio_recepcion',
                title: 'Medio de Recepcion'
            },
            {
                field: 'fecha_hora_reporte',
                title: 'Fecha'
            },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                clickToSelect: false,
                formatter: operateFormatter,
                events: operateEvents
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            $detail.html('Cargando...');
            const como_se_entero = [
                { id: 'Fui víctima', name: 'Fui víctima' },
                { id: 'Fui testigo', name: 'Fui testigo' },
                { id: 'Estaba involucrado', name: 'Estaba involucrado' },
                { id: 'Otro', name: 'Otro' }
            ];

            $.when(
                $.get(`${Server}clientes/listar`),
                $.get(`${Server}categorias/listarCategorias`),
                $.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }),
                $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${row.id_cliente}`),
                $.get(`${Server}departamentos/listarDepartamentosPorSucursal/${row.id_sucursal}`),
                $.get(`${Server}denuncias/detalle/${row.id}`),
                $.get(`${Server}denuncias/obtenerEstados`),
                $.get(`${Server}denuncias/obtenerAnexos/${row.id}`) // Obtener los anexos
            ).done(function (clientes, categorias, subcategorias, sucursales, departamentos, denunciaDetalles, estados, anexos) {
                const data = {
                    id: row.id,
                    clientes: clientes[0].map(cliente => ({ id: cliente.id, name: cliente.nombre_empresa })),
                    categorias: categorias[0].map(categoria => ({ id: categoria.id, name: categoria.nombre })),
                    subcategorias: subcategorias[0].map(subcategoria => ({ id: subcategoria.id, name: subcategoria.nombre })),
                    sucursales: sucursales[0].map(sucursal => ({ id: sucursal.id, name: sucursal.nombre })),
                    departamentos: departamentos[0].map(departamento => ({ id: departamento.id, name: departamento.nombre })),
                    estados: estados[0].map(estado => ({ id: estado.id, name: estado.nombre })),
                    anexos: anexos[0], // Añadir los anexos a los datos
                    id_cliente: row.id_cliente,
                    id_sucursal: row.id_sucursal,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo,
                    departamento_nombre: row.departamento_nombre,
                    id_departamento: row.id_departamento,
                    fecha_incidente: denunciaDetalles[0].fecha_incidente,
                    como_se_entero: denunciaDetalles[0].como_se_entero,
                    area_incidente: denunciaDetalles[0].area_incidente,
                    denunciar_a_alguien: denunciaDetalles[0].denunciar_a_alguien,
                    como_se_entero: como_se_entero
                };

                const renderData = Handlebars.compile(tplDetalleTabla)(data);

                // Renderizar y mostrar el detalle
                $detail.html(renderData);

                // Si la denuncia está cerrada, deshabilitar los campos y ocultar el botón de actualización
                if (row.estado_actual == 6) {
                    // 6 es el ID del estado "Cerrada"
                    $detail.find('input, select, textarea').prop('disabled', true);
                    $detail.find('.btn-actualizar-denuncia').hide();
                }

                // Inicializar select2 para los nuevos selectores
                $detail.find('select').select2();
                // Aplicar flatpickr a "Fecha del Incidente" en la edición
                initializeFlatpickrForEdit(`#fecha_incidente-${row.id}`);

                $detail.find('.formEditarDenuncia').validate({
                    errorClass: 'is-invalid',
                    validClass: 'is-valid',
                    errorElement: 'div',
                    errorPlacement: function (error, element) {
                        if (element.hasClass('select2-hidden-accessible')) {
                            // Para campos Select2, coloca el error después del contenedor de Select2
                            error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
                        } else if (element.is(':checkbox') || element.is(':radio')) {
                            // Para checkboxes y radios
                            error.addClass('invalid-feedback').insertAfter(element.closest('div'));
                        } else {
                            error.addClass('invalid-feedback').insertAfter(element);
                        }
                    },
                    highlight: function (element, errorClass, validClass) {
                        if ($(element).hasClass('select2-hidden-accessible')) {
                            $(element).next('.select2-container').find('.select2-selection').addClass(errorClass).removeClass(validClass);
                        } else {
                            $(element).addClass(errorClass).removeClass(validClass);
                        }
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        if ($(element).hasClass('select2-hidden-accessible')) {
                            $(element).next('.select2-container').find('.select2-selection').removeClass(errorClass).addClass(validClass);
                        } else {
                            $(element).removeClass(errorClass).addClass(validClass);
                        }
                    },
                    rules: {
                        id_cliente: {
                            required: true
                        },
                        id_sucursal: {
                            required: true
                        },
                        categoria: {
                            required: true
                        },
                        subcategoria: {
                            required: true
                        },
                        id_departamento: {
                            // <-- Añadido aquí
                            required: true
                        },
                        estado_actual: {
                            required: true
                        },
                        descripcion: {
                            required: true
                        }
                    },
                    messages: {
                        id_cliente: {
                            required: 'Por favor seleccione un cliente'
                        },
                        id_sucursal: {
                            required: 'Por favor seleccione una sucursal'
                        },
                        categoria: {
                            required: 'Por favor seleccione una categoría'
                        },
                        subcategoria: {
                            required: 'Por favor seleccione una subcategoría'
                        },
                        id_departamento: {
                            // <-- Añadido aquí
                            required: 'Por favor seleccione un departamento'
                        },
                        estado_actual: {
                            required: 'Por favor seleccione un estado'
                        },
                        descripcion: {
                            required: 'Por favor ingrese la descripción'
                        }
                    },
                    submitHandler: function (form) {
                        const $frm = $(form);
                        const formData = $frm.serializeObject();

                        loadingFormXHR($frm, true);

                        // Enviar la solicitud AJAX para actualizar la denuncia
                        $.ajax({
                            url: `${Server}denuncias/guardar`,
                            method: 'POST',
                            data: formData,
                            success: function (data) {
                                loadingFormXHR($frm, false);
                                $tablaDenuncias.bootstrapTable('refresh');
                                showToast('¡Listo!, se actualizó correctamente la denuncia.', 'success');
                            },
                            error: function (xhr) {
                                loadingFormXHR($frm, false);
                                if (xhr.status === 409) {
                                    const response = JSON.parse(xhr.responseText);
                                    showToast(response.message, 'error');
                                }
                            }
                        });
                    }
                });

                // Cargar dinámicamente las subcategorías según la categoría seleccionada
                $detail.find(`#categoria-${row.id}`).change(function () {
                    const categoriaId = $(this).val();
                    loadSubcategorias(categoriaId, `#subcategoria-${row.id}`);
                });

                // Cargar dinámicamente las sucursales según el cliente seleccionado
                $detail.find(`#id_cliente-${row.id}`).change(function () {
                    const clienteId = $(this).val();
                    loadSucursales(clienteId, `#id_sucursal-${row.id}`);
                });

                // Cargar dinámicamente los departamentos según la sucursal seleccionada
                $detail.find(`#id_sucursal-${row.id}`).change(function () {
                    const sucursalId = $(this).val();
                    loadDepartamentos(sucursalId, `#id_departamento-${row.id}`); // <-- Añadido aquí
                });

                // Inicializar Dropzone para subir nuevos archivos
                initializeDropzone(`dropzoneArchivos-${row.id}`, `formActualizarAnexos-${row.id}`);

                // Manejo de la eliminación de anexos
                $detail.on('click', '.delete-anexo', function () {
                    const anexoId = $(this).data('id');
                    eliminarAnexo(anexoId, row.id);
                });

                // Manejo del formulario de actualización de anexos
                $detail.find(`#formActualizarAnexos-${row.id}`).submit(function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    actualizarAnexos(formData, row.id);
                });
            });
        }
    });

    // Inicializar flatpickr para el campo de fecha
    $('#fecha_incidente').flatpickr({
        dateFormat: 'Y-m-d',
        maxDate: 'today'
    });

    // Cargar dinámicamente las subcategorías según la categoría seleccionada en el formulario de creación
    $('#categoria').change(function () {
        const categoriaId = $(this).val();
        loadSubcategorias(categoriaId, '#subcategoria');
    });

    // Cargar dinámicamente las sucursales según el cliente seleccionado en el formulario de creación
    $('#id_cliente').change(function () {
        const clienteId = $(this).val();
        loadSucursales(clienteId, '#id_sucursal');
    });

    // Cargar dinámicamente los departamentos según la sucursal seleccionada en el formulario de creación
    $('#id_sucursal').change(function () {
        const sucursalId = $(this).val();
        loadDepartamentos(sucursalId, '#id_departamento');
    });

    // Inicializar Dropzone para los archivos adjuntos en la creación de denuncia
    initializeDropzone('dropzoneArchivos', 'formCrearDenuncia');

    // Enviar nuevo comentario
    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        const formData = $frm.serialize();

        $.post(`${Server}comentarios/guardar`, formData, function (response) {
            cargarComentarios($('#id_denuncia').val()); // Recargar los comentarios
            $('#contenido').val(''); // Limpiar el campo de texto
            showToast('Comentario agregado exitosamente.', 'success');
            $frm[0].reset();
        }).fail(function () {
            showToast('Error al agregar el comentario.', 'error');
        });
    });
});

// Función para inicializar Dropzone
function initializeDropzone(elementId, formId) {
    const dropzoneElement = $(`#${elementId}`);
    const formElement = $(`#${formId}`);

    const myDropzone = new Dropzone(`#${elementId}`, {
        url: `${Server}denuncias/subirAnexo`,
        maxFiles: 5,
        acceptedFiles: 'image/*,application/pdf',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra los archivos aquí para subirlos',
        dictRemoveFile: 'Eliminar archivo',
        init: function () {
            this.on('success', function (file, response) {
                formElement.append(`<input type="hidden" name="archivos[]" value="assets/denuncias/${response.filename}">`);
            });
            this.on('removedfile', function (file) {
                const name = file.upload.filename;
                formElement.find(`input[value="assets/denuncias/${name}"]`).remove();
            });
        }
    });

    dropzones[elementId] = myDropzone;
}

// Función para eliminar un anexo con confirmación usando SweetAlert2
function eliminarAnexo(anexoId, denunciaId) {
    // Llamar a la función de confirmación
    confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(result => {
        // Si el usuario confirma la acción
        if (result.isConfirmed) {
            // Proceder con la eliminación
            $.ajax({
                url: `${Server}denuncias/anexos/eliminar/${anexoId}`,
                method: 'POST',
                success: function (response) {
                    showToast('Anexo eliminado correctamente.', 'success');
                    $(`#formActualizarAnexos-${denunciaId}`).find(`.delete-anexo[data-id="${anexoId}"]`).closest('.card').remove();
                },
                error: function (xhr) {
                    showToast('Error al eliminar el anexo.', 'error');
                }
            });
        }
    });
}

// Función para actualizar anexos
function actualizarAnexos(formData, denunciaId) {
    loadingFormXHR($(`#formActualizarAnexos-${denunciaId}`), true);

    $.ajax({
        url: `${Server}denuncias/actualizarAnexos`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            loadingFormXHR($(`#formActualizarAnexos-${denunciaId}`), false);
            showToast('Archivos actualizados correctamente.', 'success');
            $tablaDenuncias.bootstrapTable('refresh');
        },
        error: function (xhr) {
            loadingFormXHR($(`#formActualizarAnexos-${denunciaId}`), false);
            showToast('Error al actualizar los archivos.', 'error');
        }
    });
}

// Función para cargar subcategorías
function loadSubcategorias(categoriaId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}categorias/listarSubcategorias`,
        method: 'GET',
        data: { id_categoria: categoriaId },
        success: function (data) {
            let options = '<option value="">Seleccione una subcategoría</option>';
            data.forEach(function (subcategoria) {
                options += `<option value="${subcategoria.id}">${subcategoria.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            $(selectSelector).html('');
            console.error('Error loading subcategories.');
        }
    });
}

// Función para cargar sucursales
function loadSucursales(clienteId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${clienteId}`,
        method: 'GET',
        success: function (data) {
            let options = '<option value="">Seleccione una sucursal</option>';
            data.forEach(function (sucursal) {
                options += `<option value="${sucursal.id}">${sucursal.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            $(selectSelector).html('');
            console.error('Error loading branches.');
        }
    });
}

// Función para cargar departamentos
function loadDepartamentos(sucursalId, selectSelector) {
    console.log(sucursalId, selectSelector);
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
        method: 'GET',
        success: function (data) {
            let options = '<option value="">Seleccione un departamento</option>';
            data.forEach(function (departamento) {
                options += `<option value="${departamento.id}">${departamento.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            $(selectSelector).html('');
            console.error('Error al cargar los departamentos.');
        }
    });
}

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}

function operateFormatterEstado(value, row, index) {
    const estado = row.estado_nombre;
    let badgeClass = '';

    switch (estado) {
        case 'Recepción':
            badgeClass = 'bg-yellow'; // Amarillo (#f4b400)
            break;
        case 'Clasificada':
            badgeClass = 'bg-purple'; // Púrpura (#4285f4)
            break;
        case 'Revisada por Calidad':
            badgeClass = 'bg-teal'; // Verde Azulado (#0f9d58)
            break;
        case 'Liberada al Cliente':
            badgeClass = 'bg-red'; // Rojo (#db4437)
            break;
        case 'En Revisión por Cliente':
            badgeClass = 'bg-light-purple'; // Púrpura Claro
            break;
        case 'Cerrada':
            badgeClass = 'bg-dark-teal'; // Verde Azulado Oscuro
            break;
        default:
            badgeClass = 'bg-light text-dark'; // Para estados no reconocidos
    }

    return `<span class="badge ${badgeClass}">${estado}</span>`;
}

function cargarComentarios(denunciaId) {
    $.get(`${Server}comentarios/listar/${denunciaId}`, function (data) {
        let comentariosHtml = '';
        if (data.length > 0) {
            data.forEach(comentario => {
                let iniciales = comentario.nombre_usuario.charAt(0).toUpperCase();
                let badgeClass = '';

                // Asignar el color correspondiente según el estado
                switch (comentario.estado_nombre) {
                    case 'Recepción':
                        badgeClass = 'bg-yellow'; // Amarillo
                        break;
                    case 'Clasificada':
                        badgeClass = 'bg-purple'; // Púrpura
                        break;
                    case 'Revisada por Calidad':
                        badgeClass = 'bg-teal'; // Verde Azulado
                        break;
                    case 'Liberada al Cliente':
                        badgeClass = 'bg-red'; // Rojo
                        break;
                    case 'En Revisión por Cliente':
                        badgeClass = 'bg-light-purple'; // Púrpura Claro
                        break;
                    case 'Cerrada':
                        badgeClass = 'bg-dark-teal'; // Verde Azulado Oscuro
                        break;
                    default:
                        badgeClass = 'bg-light text-dark'; // Estado no reconocido
                }

                comentariosHtml += `
                    <div class="comentario-item d-flex mb-3">
                        <div class="avatar me-3">${iniciales}</div>
                        <div class="contenido flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">${comentario.nombre_usuario}</h6>
                                <small class="text-muted">${comentario.fecha_comentario}</small>
                            </div>
                            <span class="badge ${badgeClass} mb-2">${comentario.estado_nombre}</span>
                            <p class="mb-0">${comentario.contenido}</p>
                        </div>
                    </div>
                    <hr>
                `;
            });
        } else {
            comentariosHtml = '<p class="text-muted">No hay comentarios aún.</p>';
        }
        $('#comentariosContainer').html(comentariosHtml);
    });
}
