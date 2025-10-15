/**
 * DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let dropzones = {};

Dropzone.autoDiscover = false;

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();

    // Inicializar select2 en los selects dentro del modal
    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

    // Funcionalidades para los botones de la tabla
    window.operateEvents = {
        // Funcionalidad para el botón de eliminar
        'click .remove': function (e, value, row, index) {
            confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(result => {
                if (result.isConfirmed) {
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
                const fechaIncidente = typeof operateFormatterFecha === 'function' ? operateFormatterFecha(data.fecha_incidente) : data.fecha_incidente || '';

                // Icono por extensión (para documentos no imagen/ni audio)
                const getFileIcon = filename => {
                    const ext = (filename || '').split('.').pop().toLowerCase();
                    const icons = {
                        pdf: 'fa-file-pdf text-danger',
                        doc: 'fa-file-word text-primary',
                        docx: 'fa-file-word text-primary',
                        xls: 'fa-file-excel text-success',
                        xlsx: 'fa-file-excel text-success',
                        zip: 'fa-file-zipper text-warning',
                        rar: 'fa-file-zipper text-warning',
                        txt: 'fa-file-lines text-secondary',
                        csv: 'fa-file-csv text-info',
                        mp3: 'fa-file-audio text-info',
                        wav: 'fa-file-audio text-info',
                        ogg: 'fa-file-audio text-info'
                    };
                    return icons[ext] || 'fa-file text-secondary';
                };

                // Renderizar archivos anexos (imágenes -> Lightbox, audio -> <audio>, otros -> link con icono)
                let archivosHtml = '';
                if (data.archivos && data.archivos.length > 0) {
                    archivosHtml += `
                        <div class="col-12 mt-3">
                            <h5 class="mb-3">
                                <i class="fas fa-paperclip me-2"></i>Archivos Adjuntos
                                <span class="badge bg-secondary ms-2">${data.archivos.length}</span>
                            </h5>
                            <div class="row g-3">
                    `;

                    data.archivos.forEach((archivo, idx) => {
                        const url = `${Server}${archivo.ruta_archivo}`;
                        const nombre = archivo.nombre_archivo || '';
                        const ext = nombre.split('.').pop().toLowerCase();
                        const esImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext);
                        const esAudio = ['mp3', 'mpeg', 'ogg', 'wav'].includes(ext);
                        const nombreCorto = nombre.length > 28 ? nombre.substring(0, 24) + '….' + ext : nombre;

                        if (esImagen) {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card shadow-sm h-100">
                                        <a href="${url}"
                                           data-lightbox="denuncia-${data.id}"
                                           data-title="${nombre}">
                                            <img src="${url}" alt="${nombre}" class="card-img-top" loading="lazy">
                                        </a>
                                        <div class="card-body p-2">
                                            <p class="card-text text-center small mb-0" title="${nombre}">
                                                <i class="fas fa-image text-primary me-1"></i>${nombreCorto}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (esAudio || (archivo.tipo && archivo.tipo.startsWith('audio/'))) {
                            // Determinar mime preferido
                            let mime = 'audio/mpeg';
                            if (ext === 'ogg') mime = 'audio/ogg';
                            if (ext === 'wav') mime = 'audio/wav';

                            archivosHtml += `
                                <div class="col-12 col-md-6">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-file-audio text-info me-2"></i>
                                                <span class="small" title="${nombre}">${nombreCorto}</span>
                                            </div>
                                            <audio controls preload="none" style="width:100%;">
                                                <source src="${url}" type="${mime}">
                                                Tu navegador no soporta audio HTML5.
                                            </audio>
                                            <div class="mt-2 text-end">
                                                <a class="small" href="${url}" download>Descargar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card shadow-sm h-100">
                                        <a href="${url}" target="_blank" class="text-decoration-none">
                                            <div class="card-body text-center py-4">
                                                <i class="fas ${getFileIcon(nombre)} fa-2x mb-3"></i>
                                                <p class="card-text small mb-0 text-dark" title="${nombre}">
                                                    ${nombreCorto}
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    });

                    archivosHtml += `
                            </div>
                        </div>
                    `;
                }

                const contenido = `
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Folio:</strong> ${data.folio}</p>
                    <p><strong>Cliente:</strong> ${data.cliente_nombre || 'N/A'}</p>
                    <p><strong>Sucursal:</strong> ${data.sucursal_nombre || 'N/A'}</p>
                    <p><strong>Tipo de Denunciante:</strong> ${data.tipo_denunciante}</p>
                    <p><strong>Sexo:</strong> ${data.sexo_nombre || 'No especificado'}</p>
                    <p><strong>Categoría:</strong> ${data.categoria_nombre || 'N/A'}</p>
                    <p><strong>Subcategoría:</strong> ${data.subcategoria_nombre || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Departamento:</strong> ${data.departamento_nombre || 'N/A'}</p>
                    <p><strong>Estatus:</strong> ${data.estado_nombre}</p>
                    <p><strong>Fecha del Incidente:</strong> ${fechaIncidente}</p>
                    <p><strong>Área del Incidente:</strong> ${data.area_incidente || 'N/A'}</p>
                    <p><strong>¿Cómo se Enteró?:</strong> ${data.como_se_entero || 'N/A'}</p>
                    <p><strong>Denunciar a Alguien:</strong> ${data.denunciar_a_alguien || 'N/A'}</p>
                </div>
                <div class="col-12 mt-3">
                    <p><strong>Descripción:</strong></p>
                    <p>${data.descripcion || 'N/A'}</p>
                </div>
                ${archivosHtml}
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
                                    <td>${typeof formatoFechaHora === 'function' ? formatoFechaHora(seg.fecha) : seg.fecha}</td>
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
                // Lightbox2 se auto-inicializa con data-lightbox (no requiere código extra)
            });
        },

        // Funcionalidad para el botón de cambiar estado
        'click .change-status': function (e, value, row, index) {
            $.get(`${Server}denuncias/obtenerEstados`, function (estados) {
                let opciones = '';
                const estadosPermitidos = ['1', '2', '3', '4']; // Recepción, Clasificada, Revisada por Calidad, Liberada al Cliente

                estados.forEach(estado => {
                    if (estadosPermitidos.includes(estado.id)) {
                        const selected = estado.id === row.estado_actual ? 'selected' : '';
                        opciones += `<option value="${estado.id}" ${selected}>${estado.nombre}</option>`;
                    }
                });

                const modal = new bootstrap.Modal($('#modalCambiarEstado'));
                $('#modalCambiarEstado .modal-body').html(`
            <form id="formCambiarEstado">
                <div class="mb-3">
                    <label for="estado_nuevo" class="form-label">Nuevo Estatus</label>
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
                        const comentario = $('#comentario').val();

                        $.post(
                            `${Server}denuncias/cambiarEstado`,
                            {
                                id: row.id,
                                estado_nuevo: estadoNuevo,
                                comentario: comentario
                            },
                            function () {
                                showToast('Estatus actualizado correctamente.', 'success');
                                $tablaDenuncias.bootstrapTable('refresh');
                                modal.hide();
                            }
                        ).fail(function () {
                            showToast('Error al actualizar el estatus.', 'error');
                        });
                    });
                modal.show();
            });
        },

        'click .view-comments': function (e, value, row, index) {
            cargarComentarios(row.id);
            $('#id_denuncia').val(row.id);
            $('#folioDenuncia').html(row.folio);
            $('#modalVerComentarios').modal('show');
        }
    };

    // Agregar flatpickr a la fecha del incidente al cargar el detalle para edición
    function initializeFlatpickrForEdit(selector) {
        $(selector).flatpickr({
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            maxDate: 'today'
        });
    }

    // Inicialización de la tabla de denuncias
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listar-denuncias-calidad`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'folio', title: 'Folio' },
            { field: 'cliente_nombre', title: 'Cliente' },
            { field: 'sucursal_nombre', title: 'Sucursal' },
            { field: 'tipo_denunciante', title: 'Tipo Denunciante' },
            { field: 'categoria_nombre', title: 'Categoría' },
            { field: 'subcategoria_nombre', title: 'Subcategoría' },
            { field: 'departamento_nombre', title: 'Departamento' },
            { field: 'estado_nombre', title: 'Estado', formatter: operateFormatterEstado },
            { field: 'medio_recepcion', title: 'Canal de Recepcion' },
            { field: 'fecha_hora_reporte', title: 'Fecha', formatter: operateFormatterFecha },
            { field: 'sexo_nombre', title: 'Sexo', visible: false },
            { field: 'operate', title: 'Acciones', align: 'center', valign: 'middle', clickToSelect: false, formatter: operateFormatter, events: operateEvents }
        ],
        showColumns: true,
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            $detail.html('Cargando...');

            // Opciones estáticas
            const comboComoSeEntero = [
                { id: 'Fui víctima', name: 'Fui víctima' },
                { id: 'Fui testigo', name: 'Fui testigo' },
                { id: 'Estaba involucrado', name: 'Estaba involucrado' },
                { id: 'Otro', name: 'Otro' }
            ];

            const comboMedioRecepcion = [
                { id: 'Llamada', name: 'Llamada' },
                { id: 'Formulario', name: 'Formulario' },
                { id: 'WhatsApp', name: 'WhatsApp' },
                { id: 'Email', name: 'Email' },
                { id: 'Plataforma Pública', name: 'Plataforma Pública' }
            ];

            const comboSexo = [
                { id: '1', name: 'Masculino' },
                { id: '2', name: 'Femenino' },
                { id: '3', name: 'Otro' }
            ];

            const requests = [$.get(`${Server}clientes/listar`), $.get(`${Server}categorias/listarCategorias`), $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${row.id_cliente}`), $.get(`${Server}denuncias/detalle/${row.id}`), $.get(`${Server}denuncias/obtenerEstados`), $.get(`${Server}denuncias/obtenerAnexos/${row.id}`)];

            if (row.categoria) {
                requests.push($.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }));
            }

            $.when(...requests).done(function (...responses) {
                const [clientes, categorias, sucursales, denunciaDetallesXHR, estados, anexos, subcategorias = [{ 0: [] }]] = responses;

                const denunciaDetalles = denunciaDetallesXHR[0];
                const estatusEditables = ['Recepción', 'Clasificada', 'Revisada por Calidad'];
                const esEditable = estatusEditables.indexOf(denunciaDetalles.estado_nombre) !== -1;
                const esAnonimo = denunciaDetalles.anonimo === '0';

                const data = {
                    id: row.id,
                    clientes: clientes[0].map(c => ({ id: c.id, name: c.nombre_empresa })),
                    categorias: categorias[0].map(c => ({ id: c.id, name: c.nombre })),
                    subcategorias: Array.isArray(subcategorias[0]) ? subcategorias[0].map(s => ({ id: s.id, name: s.nombre })) : [],
                    sucursales: sucursales[0].map(s => ({ id: s.id, name: s.nombre })),
                    estados: estados[0].map(e => ({ id: e.id, name: e.nombre })),
                    anexos: anexos[0],
                    id_cliente: row.id_cliente,
                    id_sucursal: row.id_sucursal,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo,
                    esAnonimo: esAnonimo,
                    nombre_completo: denunciaDetalles.nombre_completo,
                    correo_electronico: denunciaDetalles.correo_electronico,
                    telefono: denunciaDetalles.telefono,
                    departamento_nombre: row.departamento_nombre,
                    fecha_incidente: denunciaDetalles.fecha_incidente,
                    como_se_entero: denunciaDetalles.como_se_entero,
                    area_incidente: denunciaDetalles.area_incidente,
                    denunciar_a_alguien: denunciaDetalles.denunciar_a_alguien,
                    medio_recepcion: denunciaDetalles.medio_recepcion,
                    comboComoSeEntero,
                    comboMedioRecepcion,
                    comboSexo,
                    id_sexo: row.id_sexo,
                    esEditable
                };

                const renderData = Handlebars.compile(tplDetalleTabla)(data);
                $detail.html(renderData);

                if (!esEditable) {
                    $detail.find('input, select, textarea, button').prop('disabled', true);
                }

                $detail.find('select').select2();
                initializeFlatpickrForEdit(`#fecha_incidente-${row.id}`);

                $detail.find('.formEditarDenuncia').validate({
                    errorClass: 'is-invalid',
                    validClass: 'is-valid',
                    errorElement: 'div',
                    errorPlacement: function (error, element) {
                        if (element.hasClass('select2-hidden-accessible')) {
                            error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
                        } else if (element.is(':checkbox') || element.is(':radio')) {
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
                        id_cliente: { required: true },
                        id_sucursal: { required: true },
                        categoria: { required: true },
                        subcategoria: { required: true },
                        estado_actual: { required: true },
                        descripcion: { required: true }
                    },
                    messages: {
                        id_cliente: 'Por favor seleccione un cliente',
                        id_sucursal: 'Por favor seleccione una sucursal',
                        categoria: 'Por favor seleccione una categoría',
                        subcategoria: 'Por favor seleccione una subcategoría',
                        estado_actual: 'Por favor seleccione un estatus',
                        descripcion: 'Por favor ingrese la descripción'
                    },
                    submitHandler: function (form) {
                        const $frm = $(form);
                        const formData = $frm.serializeObject();

                        loadingFormXHR($frm, true);

                        $.ajax({
                            url: `${Server}denuncias/guardar`,
                            method: 'POST',
                            data: formData,
                            success: function () {
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

                $detail.find(`#categoria-${row.id}`).change(function () {
                    const categoriaId = $(this).val();
                    loadSubcategorias(categoriaId, `#subcategoria-${row.id}`);
                });

                $detail.find(`#id_cliente-${row.id}`).change(function () {
                    const clienteId = $(this).val();
                    loadSucursales(clienteId, `#id_sucursal-${row.id}`);
                });

                // Dropzone con soporte de audio (mp3/ogg/wav)
                initializeDropzone(`dropzoneArchivos-${row.id}`, `formActualizarAnexos-${row.id}`);

                $detail.on('click', '.delete-anexo', function () {
                    const anexoId = $(this).data('id');
                    eliminarAnexo(anexoId, row.id);
                });

                $detail.find(`#formActualizarAnexos-${row.id}`).submit(function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    actualizarAnexos(formData, row.id);
                });
            });
        }
    });

    // Enviar nuevo comentario
    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        const $textarea = $('#contenidoComentario');
        const $submitButton = $frm.find('button[type="submit"]');
        const formData = $frm.serialize();

        $textarea.prop('disabled', true);
        $submitButton.prop('disabled', true);
        $submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');

        $.post(`${Server}comentarios/guardar`, formData, function () {
            cargarComentarios($('#id_denuncia').val());
            showToast('Comentario agregado exitosamente.', 'success');
            $textarea.val('');
            $frm[0].reset();
        })
            .fail(function (err) {
                const message = err.responseJSON.message;
                showToast(message, 'error');
            })
            .always(function () {
                $textarea.prop('disabled', false);
                $submitButton.prop('disabled', false);
                $submitButton.html('<i class="fas fa-paper-plane"></i> Enviar');
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
        // ACEPTA imágenes, PDF y audios (mp3/ogg/wav)
        acceptedFiles: 'image/*,application/pdf,audio/mpeg,.mp3,audio/ogg,.ogg,audio/wav,.wav,audio/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra los archivos aquí para subirlos',
        dictRemoveFile: 'Eliminar archivo',
        init: function () {
            this.on('success', function (file, response) {
                formElement.append(`<input type="hidden" name="archivos[]" value="assets/denuncias/${response.filename}">`);
            });
            this.on('removedfile', function (file) {
                const name = file.upload && file.upload.filename ? file.upload.filename : file.name || '';
                formElement.find(`input[value="assets/denuncias/${name}"]`).remove();
            });
        }
    });

    dropzones[elementId] = myDropzone;
}

// Función para eliminar un anexo con confirmación usando SweetAlert2
function eliminarAnexo(anexoId, denunciaId) {
    confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `${Server}denuncias/anexos/eliminar/${anexoId}`,
                method: 'POST',
                success: function () {
                    showToast('Anexo eliminado correctamente.', 'success');
                    $(`#formActualizarAnexos-${denunciaId}`).find(`.delete-anexo[data-id="${anexoId}"]`).closest('.card').remove();
                },
                error: function () {
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
        success: function () {
            loadingFormXHR($(`#formActualizarAnexos-${denunciaId}`), false);
            showToast('Archivos actualizados correctamente.', 'success');
            $tablaDenuncias.bootstrapTable('refresh');
        },
        error: function () {
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
            badgeClass = 'bg-yellow';
            break;
        case 'Clasificada':
            badgeClass = 'bg-purple';
            break;
        case 'Revisada por Calidad':
            badgeClass = 'bg-teal';
            break;
        case 'Liberada al Cliente':
            badgeClass = 'bg-red';
            break;
        case 'En Revisión por Cliente':
            badgeClass = 'bg-light-purple';
            break;
        case 'Cerrada':
            badgeClass = 'bg-dark-teal';
            break;
        default:
            badgeClass = 'bg-light text-dark';
    }

    return `<span class="badge ${badgeClass}">${estado}</span>`;
}

function cargarComentarios(denunciaId) {
    $.get(`${Server}comentarios/listar/${denunciaId}`, function (data) {
        let comentariosHtml = '';
        if (data.length > 0) {
            data.forEach(comentario => {
                let badgeClass = '';

                switch (comentario.estado_nombre) {
                    case 'Recepción':
                        badgeClass = 'bg-yellow';
                        break;
                    case 'Clasificada':
                        badgeClass = 'bg-purple';
                        break;
                    case 'Revisada por Calidad':
                        badgeClass = 'bg-teal';
                        break;
                    case 'Liberada al Cliente':
                        badgeClass = 'bg-red';
                        break;
                    case 'En Revisión por Cliente':
                        badgeClass = 'bg-light-purple';
                        break;
                    case 'Cerrada':
                        badgeClass = 'bg-dark-teal';
                        break;
                    default:
                        badgeClass = 'bg-light text-dark';
                }

                comentariosHtml += `
                    <div class="comentario-item d-flex mb-3">
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
