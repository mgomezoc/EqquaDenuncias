/**
 * DENUNCIAS - AGENTE
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

    // Mostrar/Ocultar datos adicionales cuando cambia el radio
    $('input[name="anonimo"]').on('change', function () {
        if ($(this).val() === '0') {
            $('#infoAdicional').show();
        } else {
            $('#infoAdicional').hide();
        }
    });

    // -------- Política de anonimato (0=Opcional, 1=Forzar anónimas, 2=Forzar identificadas)
    function aplicarPoliticaAnonimato(politica) {
        const $radios = $modalCrearDenuncia.find('input[name="anonimo"]');
        const $si = $('#anonimo-si');
        const $no = $('#anonimo-no');

        // Reset
        $radios.prop('disabled', false);

        const p = Number(politica);

        if (p === 1) {
            // Forzar ANÓNIMAS
            $si.prop('checked', true);
            $radios.prop('disabled', true);
            $('#infoAdicional').hide();
        } else if (p === 2) {
            // Forzar IDENTIFICADAS
            $no.prop('checked', true);
            $radios.prop('disabled', true);
            $('#infoAdicional').show();
        } else {
            // Opcional
            if ($('input[name="anonimo"]:checked').val() === '0') {
                $('#infoAdicional').show();
            } else {
                $('#infoAdicional').hide();
            }
        }
    }

    function cargarPoliticaDeCliente(clienteId) {
        if (!clienteId) {
            aplicarPoliticaAnonimato(0);
            return;
        }
        $.get(`${Server}clientes/obtener/${clienteId}`, function (cliente) {
            aplicarPoliticaAnonimato(cliente?.politica_anonimato ?? 0);
        }).fail(function () {
            aplicarPoliticaAnonimato(0);
        });
    }
    // -------- fin política de anonimato

    // Inicializar select2 en los selects dentro del modal
    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

    // Validación del formulario de creación
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
            id_cliente: { required: true },
            id_sucursal: { required: true },
            categoria: { required: true },
            subcategoria: { required: true },
            fecha_incidente: { required: true, date: true },
            descripcion: { required: true }
        },
        messages: {
            id_cliente: { required: 'Por favor seleccione un cliente' },
            id_sucursal: { required: 'Por favor seleccione una sucursal' },
            categoria: { required: 'Por favor seleccione una categoría' },
            subcategoria: { required: 'Por favor seleccione una subcategoría' },
            fecha_incidente: { required: 'Por favor ingrese la fecha del incidente', date: 'Ingrese una fecha válida' },
            descripcion: { required: 'Por favor ingrese la descripción' }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = new FormData(form);

            loadingFormXHR($frm, true);

            $.ajax({
                url: `${Server}denuncias/guardar`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function () {
                    loadingFormXHR($frm, false);
                    $tablaDenuncias.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la denuncia.', 'success');

                    // Reset UI
                    $frm[0].reset();
                    $frm.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    $frm.find('.select2').val(null).trigger('change', true);
                    if (dropzones['archivosAdjuntos']) dropzones['archivosAdjuntos'].removeAllFiles(true);
                    // habilitar radios y ocultar info
                    $frm.find('input[name="anonimo"]').prop('disabled', false);
                    $('#infoAdicional').hide();

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

    // Cuando se selecciona una opción en select2, validar
    $('#modalCrearDenuncia .select2').on('change', function (e, trigger) {
        if (!trigger) $(this).valid();
    });

    // Resetear el formulario al cerrar el modal
    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
        $form.find('.select2').val(null).trigger('change');
        if (dropzones['archivosAdjuntos']) dropzones['archivosAdjuntos'].removeAllFiles(true);
        // radios habilitados y ocultar info adicional
        $form.find('input[name="anonimo"]').prop('disabled', false);
        $('#infoAdicional').hide();
    });

    // ==== Tabla de denuncias del agente ====
    window.operateEvents = {
        'click .remove': function (e, value, row) {
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
        'click .view-detail': function (e, value, row) {
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
                                <p><strong>Sexo:</strong> ${data.sexo_nombre || 'No especificado'}</p>
                                <p><strong>Categoría:</strong> ${data.categoria_nombre || 'N/A'}</p>
                                <p><strong>Subcategoría:</strong> ${data.subcategoria_nombre || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Departamento:</strong> ${data.departamento_nombre || 'N/A'}</p>
                                <p><strong>Estatus:</strong> ${data.estado_nombre}</p>
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
                                            <th>Fecha</th><th>De</th><th>A</th><th>Comentario</th><th>Por</th>
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
                                            </tr>`
                                            )
                                            .join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>`;
                $('#modalVerDetalle .modal-body').html(contenido);
                modal.show();
            });
        },
        'click .change-status': function (e, value, row) {
            $.get(`${Server}denuncias/obtenerEstados`, function (estados) {
                const estadosFiltrados = estados.filter(estado => estado.id === '1' || estado.id === '2');
                let opciones = '';
                estadosFiltrados.forEach(estado => {
                    const selected = estado.id === row.estado_actual ? 'selected' : '';
                    opciones += `<option value="${estado.id}" ${selected}>${estado.nombre}</option>`;
                });

                const modal = new bootstrap.Modal($('#modalCambiarEstado'));
                $('#modalCambiarEstado .modal-body').html(`
                    <form id="formCambiarEstado">
                        <div class="mb-3">
                            <label for="estado_nuevo" class="form-label">Nuevo Estatus</label>
                            <select id="estado_nuevo" name="estado_nuevo" class="form-select">${opciones}</select>
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
                                comentario
                            },
                            function () {
                                showToast('Estatus actualizado correctamente.', 'success');
                                $tablaDenuncias.bootstrapTable('refresh');
                                modal.hide();
                            }
                        ).fail(function () {
                            showToast('Error al actualizar el estattus.', 'error');
                        });
                    });
                modal.show();
            });
        },
        'click .view-comments': function (e, value, row) {
            cargarComentarios(row.id);
            $('#id_denuncia').val(row.id);
            $('#folioDenuncia').html(row.folio);
            $('#modalVerComentarios').modal('show');
        }
    };

    // Inicialización de la tabla
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listar-denuncias-agente`,
        columns: [
            { field: 'id', title: 'ID', formatter: v => `<b>${v}</b>` },
            { field: 'folio', title: 'Folio', cellStyle: { css: { 'white-space': 'nowrap', 'max-width': '250px', overflow: 'hidden', 'text-overflow': 'ellipsis' } } },
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

            const requests = [$.get(`${Server}clientes/listar`), $.get(`${Server}categorias/listarCategorias`), $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${row.id_cliente}`), $.get(`${Server}departamentos/listarDepartamentosPorSucursal/${row.id_sucursal}`), $.get(`${Server}denuncias/detalle/${row.id}`), $.get(`${Server}denuncias/obtenerEstados`), $.get(`${Server}denuncias/obtenerAnexos/${row.id}`)];
            if (row.categoria) requests.push($.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }));

            $.when(...requests).done(function (...responses) {
                const [clientes, categorias, sucursales, departamentos, denunciaDetalles, estados, anexos, subcategorias = [{ 0: [] }]] = responses;

                const safeSubcategorias = Array.isArray(subcategorias[0]) ? subcategorias[0] : [];
                const esAnonimo = row.anonimo === '0';

                const data = {
                    id: row.id,
                    clientes: clientes[0].map(c => ({ id: c.id, name: c.nombre_empresa })),
                    categorias: categorias[0].map(c => ({ id: c.id, name: c.nombre })),
                    subcategorias: safeSubcategorias.map(s => ({ id: s.id, name: s.nombre })),
                    sucursales: sucursales[0].map(s => ({ id: s.id, name: s.nombre })),
                    departamentos: departamentos[0].map(d => ({ id: d.id, name: d.nombre })),
                    estados: estados[0].map(e => ({ id: e.id, name: e.nombre })),
                    anexos: anexos[0],
                    id_cliente: row.id_cliente,
                    id_sucursal: row.id_sucursal,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo,
                    esAnonimo,
                    nombre_completo: row.nombre_completo,
                    correo_electronico: row.correo_electronico,
                    telefono: row.telefono,
                    departamento_nombre: row.departamento_nombre,
                    id_departamento: row.id_departamento,
                    fecha_incidente: denunciaDetalles[0].fecha_incidente,
                    como_se_entero: denunciaDetalles[0].como_se_entero,
                    area_incidente: denunciaDetalles[0].area_incidente,
                    denunciar_a_alguien: denunciaDetalles[0].denunciar_a_alguien,
                    comboComoSeEntero,
                    comboMedioRecepcion,
                    comboSexo,
                    id_sexo: row.id_sexo,
                    medio_recepcion: row.medio_recepcion
                };

                const renderData = Handlebars.compile(tplDetalleTabla)(data);
                $detail.html(renderData);

                if (row.estado_actual == 6) {
                    $detail.find('input, select, textarea').prop('disabled', true);
                    $detail.find('.btn-actualizar-denuncia').hide();
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
                        id_departamento: { required: true },
                        estado_actual: { required: true },
                        descripcion: { required: true }
                    },
                    messages: {
                        id_cliente: { required: 'Por favor seleccione un cliente' },
                        id_sucursal: { required: 'Por favor seleccione una sucursal' },
                        id_departamento: { required: 'Por favor seleccione un departamento' },
                        estado_actual: { required: 'Por favor seleccione un estatus' },
                        descripcion: { required: 'Por favor ingrese la descripción' }
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
                    loadSubcategorias($(this).val(), `#subcategoria-${row.id}`);
                });
                $detail.find(`#id_cliente-${row.id}`).change(function () {
                    loadSucursales($(this).val(), `#id_sucursal-${row.id}`);
                });
                $detail.find(`#id_sucursal-${row.id}`).change(function () {
                    loadDepartamentos($(this).val(), `#id_departamento-${row.id}`);
                });

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

    // Flatpickr creación
    $('#fecha_incidente').flatpickr({
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        maxDate: 'today'
    });

    // Dependencias creación
    $('#categoria').change(function () {
        loadSubcategorias($(this).val(), '#subcategoria');
    });

    $('#id_cliente').change(function () {
        const clienteId = $(this).val();
        loadSucursales(clienteId, '#id_sucursal');
        cargarPoliticaDeCliente(clienteId); // <-- aplica política al cambiar cliente
    });

    $('#id_sucursal').change(function () {
        loadDepartamentos($(this).val(), '#id_departamento');
    });

    // Dropzone creación
    initializeDropzone('dropzoneArchivos', 'formCrearDenuncia');

    // Comentarios
    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        const $textarea = $('#contenidoComentario');
        const $submitButton = $frm.find('button[type="submit"]');
        const formData = $frm.serialize();

        $textarea.prop('disabled', true);
        $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');

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
                $submitButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar');
            });
    });
});

// -------- utilidades existentes (sin cambios) --------
function initializeFlatpickrForEdit(selector) {
    $(selector).flatpickr({
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        maxDate: 'today'
    });
}

function initializeDropzone(elementId, formId) {
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

function loadDepartamentos(sucursalId, selectSelector) {
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

function operateFormatter(value, row) {
    return Handlebars.compile(tplAccionesTabla)(row);
}

function operateFormatterEstado(value, row) {
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
                    <hr>`;
            });
        } else {
            comentariosHtml = '<p class="text-muted">No hay comentarios aún.</p>';
        }
        $('#comentariosContainer').html(comentariosHtml);
    });
}
