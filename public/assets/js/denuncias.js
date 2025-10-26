/* global Server, Handlebars, Dropzone, Swal */
/**
 * DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;
let dropzones = {};

Dropzone.autoDiscover = false;

// --- Polyfill serializeObject ---
if (typeof $.fn.serializeObject !== 'function') {
    $.fn.serializeObject = function () {
        const o = {};
        const a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name] !== undefined) {
                if (!Array.isArray(o[this.name])) o[this.name] = [o[this.name]];
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
}

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearDenuncia = $('#modalCrearDenuncia');

    // --- Radios anónimo ---
    $('input[name="anonimo"]').on('change', function () {
        if ($(this).val() === '0') $('#infoAdicional').show();
        else $('#infoAdicional').hide();
    });

    // select2 dentro del modal crear
    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

    // ====== Política de anonimato ======
    function aplicarPoliticaAnonimato(politica) {
        const $radios = $modalCrearDenuncia.find('input[name="anonimo"]');
        const $si = $('#anonimo-si');
        const $no = $('#anonimo-no');
        const $help = $('#politicaHelp');
        $radios.prop('disabled', false);
        const p = Number(politica);
        if (p === 1) {
            $si.prop('checked', true);
            $radios.prop('disabled', true);
            $('#infoAdicional').hide();
            if ($help.length) $help.text('Política del cliente: se fuerza a ANÓNIMO.');
        } else if (p === 2) {
            $no.prop('checked', true);
            $radios.prop('disabled', true);
            $('#infoAdicional').show();
            if ($help.length) $help.text('Política del cliente: se fuerza a IDENTIFICADO.');
        } else {
            if ($('input[name="anonimo"]:checked').val() === '0') $('#infoAdicional').show();
            else $('#infoAdicional').hide();
            if ($help.length) $help.text('Política del cliente: OPCIONAL.');
        }
    }

    function cargarPoliticaDeCliente(clienteId) {
        if (!clienteId) return aplicarPoliticaAnonimato(0);
        $.get(`${Server}clientes/obtener/${clienteId}`, c => {
            aplicarPoliticaAnonimato(c?.politica_anonimato ?? 0);
        }).fail(() => aplicarPoliticaAnonimato(0));
    }
    // ====== FIN POLÍTICA ======

    // Validación crear
    $('#formCrearDenuncia').validate({
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        errorPlacement: function (error, element) {
            if (element.hasClass('select2') && element.next('.select2-container').length) {
                error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
            } else if (element.is('input[type="checkbox"],input[type="radio"]')) {
                error.addClass('invalid-feedback').insertAfter(element.closest('div'));
            } else {
                error.addClass('invalid-feedback').insertAfter(element);
            }
        },
        highlight: function (el, e, v) {
            if ($(el).hasClass('select2')) $(el).next('.select2-container').find('.select2-selection').addClass(e).removeClass(v);
            else $(el).addClass(e).removeClass(v);
        },
        unhighlight: function (el, e, v) {
            if ($(el).hasClass('select2')) $(el).next('.select2-container').find('.select2-selection').removeClass(e).addClass(v);
            else $(el).removeClass(e).addClass(v);
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
                    $frm[0].reset();
                    $frm.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    $frm.find('.select2').val(null).trigger('change', true);
                    if (dropzones['dropzoneArchivos']) dropzones['dropzoneArchivos'].removeAllFiles(true);
                    $frm.find('input[name="anonimo"]').prop('disabled', false);
                    $('#infoAdicional').hide();
                    $modalCrearDenuncia.modal('hide');
                },
                error: function (xhr) {
                    loadingFormXHR($frm, false);
                    if (xhr.status === 409) showToast(JSON.parse(xhr.responseText).message, 'error');
                    else showToast('Ocurrió un error al guardar la denuncia.', 'error');
                }
            });
        }
    });

    $('#modalCrearDenuncia .select2').on('change', function (e, trigger) {
        if (!trigger) $(this).valid();
    });

    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
        $form.find('.select2').val(null).trigger('change');
        if (dropzones['dropzoneArchivos']) dropzones['dropzoneArchivos'].removeAllFiles(true);
        $form.find('input[name="anonimo"]').prop('disabled', false);
        $('#infoAdicional').hide();
        if ($('#politicaHelp').length) $('#politicaHelp').text('');
    });

    // Tabla operaciones
    window.operateEvents = {
        'click .remove': function (e, v, row) {
            confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(r => {
                if (r.isConfirmed) {
                    $.post(`${Server}denuncias/eliminar/${row.id}`, () => {
                        showToast('Denuncia eliminada correctamente.', 'success');
                        $tablaDenuncias.bootstrapTable('refresh');
                    }).fail(() => showToast('Error al eliminar la denuncia.', 'error'));
                }
            });
        },
        'click .view-detail': function (e, v, row) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));
                const fechaIncidente = data?.fecha_incidente ? formatoFechaHora(data.fecha_incidente) : 'N/A';
                const getFileIcon = filename => {
                    const ext = filename.split('.').pop().toLowerCase();
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
                        mp3: 'fa-file-audio text-info'
                    };
                    return icons[ext] || 'fa-file text-secondary';
                };

                let archivosHtml = '';
                if (data.archivos && data.archivos.length > 0) {
                    archivosHtml += `<div class="mt-4"><h5 class="mb-3"><i class="fas fa-paperclip me-2"></i>Archivos Adjuntos <span class="badge bg-secondary ms-2">${data.archivos.length}</span></h5><div class="row g-3">`;
                    data.archivos.forEach((archivo, idx) => {
                        const url = `${Server}${archivo.ruta_archivo}`;
                        const ext = archivo.nombre_archivo.split('.').pop().toLowerCase();
                        const esImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                        const esAudio = ['mp3'].includes(ext);
                        const nombreCorto = archivo.nombre_archivo.length > 25 ? archivo.nombre_archivo.substring(0, 22) + '...' + ext : archivo.nombre_archivo;

                        if (esImagen) {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay:${idx * 0.1}s">
                                  <div class="card shadow-sm h-100 archivo-card">
                                    <a href="${url}" data-fancybox="denuncia-${data.id}" data-caption="${archivo.nombre_archivo}" class="archivo-imagen-link">
                                      <div class="archivo-imagen-container">
                                        <img src="${url}" alt="${archivo.nombre_archivo}" class="card-img-top archivo-imagen" loading="lazy">
                                        <div class="archivo-overlay"><i class="fas fa-search-plus"></i></div>
                                      </div>
                                    </a>
                                    <div class="card-body p-2">
                                      <p class="card-text text-center small mb-0" title="${archivo.nombre_archivo}">
                                        <i class="fas fa-image text-primary me-1"></i>${nombreCorto}
                                      </p>
                                    </div>
                                  </div>
                                </div>`;
                        } else if (esAudio) {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay:${idx * 0.1}s">
                                    <div class="card shadow-sm h-100 archivo-card">
                                        <div class="card-body text-center py-3">
                                            <i class="fas fa-file-audio text-info archivo-icono mb-2"></i>
                                            <p class="card-text small mb-2 text-dark" title="${archivo.nombre_archivo}">${nombreCorto}</p>
                                            <audio controls preload="none" style="width:100%">
                                                <source src="${url}" type="audio/mpeg">
                                                Tu navegador no soporta audio HTML5.
                                            </audio>
                                            <div class="mt-2">
                                                <a href="${url}" download class="small">Descargar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            archivosHtml += `
                              <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay:${idx * 0.1}s">
                                <div class="card shadow-sm h-100 archivo-card">
                                  <a href="${url}" target="_blank" class="text-decoration-none archivo-documento-link">
                                    <div class="card-body text-center py-4">
                                      <i class="fas ${getFileIcon(archivo.nombre_archivo)} archivo-icono mb-3"></i>
                                      <p class="card-text small mb-0 text-dark" title="${archivo.nombre_archivo}">${nombreCorto}</p>
                                    </div>
                                  </a>
                                </div>
                              </div>`;
                        }
                    });
                    archivosHtml += `</div></div>`;
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
                            ${
                                data.seguimientos
                                    ? `
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
                                                <td>${formatoFechaHora(seg.fecha)}</td>
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
                            </div>`
                                    : ''
                            }
                        </div>
                    </div>`;

                $('#modalVerDetalle .modal-body').html(contenido);
                modal.show();
                setTimeout(() => {
                    $(`[data-fancybox="denuncia-${data.id}"]`).fancybox({
                        buttons: ['zoom', 'share', 'slideShow', 'fullScreen', 'download', 'thumbs', 'close'],
                        loop: true,
                        protect: true,
                        animationEffect: 'zoom-in-out',
                        transitionEffect: 'slide',
                        thumbs: { autoStart: true }
                    });
                }, 100);
            });
        },
        'click .change-status': function (e, v, row) {
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
                            <label for="estado_nuevo" class="form-label">Nuevo Estatus</label>
                            <select id="estado_nuevo" name="estado_nuevo" class="form-select">${opciones}</select>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario (opcional)</label>
                            <textarea id="comentario" name="comentario" class="form-control" rows="3" placeholder="Escribe un comentario..."></textarea>
                        </div>
                    </form>`);
                $('#modalCambiarEstado .modal-footer .btn-primary')
                    .off('click')
                    .on('click', function () {
                        $.post(
                            `${Server}denuncias/cambiarEstado`,
                            {
                                id: row.id,
                                estado_nuevo: $('#estado_nuevo').val(),
                                comentario: $('#comentario').val()
                            },
                            function () {
                                showToast('Estatus actualizado correctamente.', 'success');
                                $tablaDenuncias.bootstrapTable('refresh');
                                modal.hide();
                            }
                        ).fail(() => showToast('Error al actualizar el estatus.', 'error'));
                    });
                modal.show();
            });
        },
        'click .view-comments': function (e, v, row) {
            cargarComentarios(row.id);
            $('#id_denuncia').val(row.id);
            $('#folioDenuncia').html(row.folio);
            $('#modalVerComentarios').modal('show');
        }
    };

    // Flatpickr crear
    $('#fecha_incidente').flatpickr({
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        maxDate: 'today'
    });

    // Dependencias crear
    $('#categoria').change(function () {
        loadSubcategorias($(this).val(), '#subcategoria');
    });
    $('#id_cliente').change(function () {
        const id = $(this).val();
        loadSucursales(id, '#id_sucursal');
        cargarPoliticaDeCliente(id);
    });
    $('#id_sucursal').change(function () {
        loadDepartamentos($(this).val(), '#id_departamento');
    });

    // Dropzone crear
    initializeDropzone('dropzoneArchivos', 'formCrearDenuncia');

    // Nuevo comentario
    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        $.post(`${Server}comentarios/guardar`, $frm.serialize(), function () {
            cargarComentarios($('#id_denuncia').val());
            $('#contenidoComentario').val('');
            showToast('Comentario agregado exitosamente.', 'success');
            $frm[0].reset();
        }).fail(err => showToast(err.responseJSON?.message || 'No se pudo agregar el comentario.', 'error'));
    });

    // Tabla
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listar`,
        columns: [
            { field: 'operate', title: 'Acciones', align: 'center', valign: 'middle', clickToSelect: false, formatter: operateFormatter, events: operateEvents },
            { field: 'id', title: 'ID' },
            { field: 'folio', title: 'Folio' },
            { field: 'cliente_nombre', title: 'Cliente' },
            { field: 'sucursal_nombre', title: 'Sucursal' },
            { field: 'tipo_denunciante', title: 'Denunciante', formatter: (v, row) => (v === 'No anónimo' ? row.nombre_completo : v) },
            { field: 'categoria_nombre', title: 'Categoría' },
            { field: 'subcategoria_nombre', title: 'Subcategoría' },
            { field: 'departamento_nombre', title: 'Departamento' },
            { field: 'estado_nombre', title: 'Estatus', align: 'center', formatter: operateFormatterEstado },
            { field: 'medio_recepcion', title: 'Canal de Recepcion' },
            { field: 'fecha_hora_reporte', title: 'Fecha Incidente', formatter: formatoFechaHora, visible: false },
            { field: 'created_at', title: 'Fecha de Registro', formatter: formatoFechaHora },
            { field: 'updated_at', title: 'Última Actualización', formatter: formatoFechaHora, visible: false },
            { field: 'sexo_nombre', title: 'Sexo', visible: false }
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

            if (row.categoria) {
                requests.push($.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }));
            }

            $.when(...requests).done(function (...responses) {
                const [clientes, categorias, sucursales, departamentos, denunciaDetalles, estados, anexos, subcategorias = [{ 0: [] }]] = responses;
                const safeSubcategorias = Array.isArray(subcategorias[0]) ? subcategorias[0] : [];
                const esAnonimo = row.anonimo === '0';

                const data = {
                    created_at: row.created_at,
                    id: row.id,
                    clientes: clientes[0].map(x => ({ id: x.id, name: x.nombre_empresa })),
                    categorias: categorias[0].map(x => ({ id: x.id, name: x.nombre })),
                    subcategorias: safeSubcategorias.map(x => ({ id: x.id, name: x.nombre })),
                    sucursales: sucursales[0].map(x => ({ id: x.id, name: x.nombre })),
                    departamentos: departamentos[0].map(x => ({ id: x.id, name: x.nombre })),
                    estados: estados[0].map(x => ({ id: x.id, name: x.nombre })),
                    anexos: anexos[0],
                    id_cliente: row.id_cliente,
                    id_sucursal: row.id_sucursal,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo,
                    esAnonimo: esAnonimo,
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
                initializeFlatpickrDateTime(`#created_at-${row.id}`);

                // IMPORTANTE: Asegurar que el campo ID esté presente en el formulario
                // Si no existe, agregarlo como campo oculto
                if (!$detail.find(`#formEditarDenuncia-${row.id} input[name="id"]`).length) {
                    $detail.find(`#formEditarDenuncia-${row.id}`).prepend(`<input type="hidden" name="id" value="${row.id}">`);
                }

                $detail.find('.formEditarDenuncia').validate({
                    errorClass: 'is-invalid',
                    validClass: 'is-valid',
                    errorElement: 'div',
                    errorPlacement: function (error, element) {
                        if (element.hasClass('select2-hidden-accessible')) {
                            error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
                        } else if (element.is(':checkbox,:radio')) {
                            error.addClass('invalid-feedback').insertAfter(element.closest('div'));
                        } else {
                            error.addClass('invalid-feedback').insertAfter(element);
                        }
                    },
                    highlight: function (el, e, v) {
                        if ($(el).hasClass('select2-hidden-accessible')) {
                            $(el).next('.select2-container').find('.select2-selection').addClass(e).removeClass(v);
                        } else {
                            $(el).addClass(e).removeClass(v);
                        }
                    },
                    unhighlight: function (el, e, v) {
                        if ($(el).hasClass('select2-hidden-accessible')) {
                            $(el).next('.select2-container').find('.select2-selection').removeClass(e).addClass(v);
                        } else {
                            $(el).removeClass(e).addClass(v);
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

                        // CRÍTICO: Asegurar que el ID esté presente
                        if (!formData.id) {
                            formData.id = row.id;
                        }

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
                                    showToast(JSON.parse(xhr.responseText).message, 'error');
                                } else {
                                    showToast('Ocurrió un error al actualizar la denuncia.', 'error');
                                }
                            }
                        });
                    }
                });

                // dependencias dinámicas
                $detail.find(`#categoria-${row.id}`).change(function () {
                    loadSubcategorias($(this).val(), `#subcategoria-${row.id}`);
                });
                $detail.find(`#id_cliente-${row.id}`).change(function () {
                    loadSucursales($(this).val(), `#id_sucursal-${row.id}`);
                });
                $detail.find(`#id_sucursal-${row.id}`).change(function () {
                    loadDepartamentos($(this).val(), `#id_departamento-${row.id}`);
                });

                // Dropzone anexos
                initializeDropzone(`dropzoneArchivos-${row.id}`, `formActualizarAnexos-${row.id}`);
                $detail.on('click', '.delete-anexo', function () {
                    eliminarAnexo($(this).data('id'), row.id);
                });
                $detail.find(`#formActualizarAnexos-${row.id}`).submit(function (e) {
                    e.preventDefault();
                    actualizarAnexos(new FormData(this), row.id);
                });

                // IA: sugerencia si existe
                iaLoadIfExists(row.id);
            });
        }
    });
});

// Helpers de fechas
function initializeFlatpickrForEdit(selector) {
    $(selector).flatpickr({ dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', maxDate: 'today' });
}
function initializeFlatpickrDateTime(selector) {
    $(selector).flatpickr({ enableTime: true, time_24hr: true, seconds: true, dateFormat: 'Y-m-d H:i:S', altInput: true, altFormat: 'd/m/Y H:i:s' });
}

// Dropzone
function initializeDropzone(elementId, formId) {
    const formElement = $(`#${formId}`);
    const myDropzone = new Dropzone(`#${elementId}`, {
        url: `${Server}denuncias/subirAnexo`,
        maxFiles: 5,
        acceptedFiles: 'image/*,application/pdf,audio/mpeg,.mp3,audio/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra los archivos aquí para subirlos',
        dictRemoveFile: 'Eliminar archivo',
        init: function () {
            this.on('success', function (file, response) {
                formElement.append(`<input type="hidden" name="archivos[]" value="assets/denuncias/${response.filename}">`);
            });
            this.on('removedfile', function (file) {
                const name = file.upload?.filename || file.name;
                formElement.find(`input[value="assets/denuncias/${name}"]`).remove();
            });
        }
    });
    dropzones[elementId] = myDropzone;
}

function eliminarAnexo(anexoId, denunciaId) {
    confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(r => {
        if (r.isConfirmed) {
            $.post(`${Server}denuncias/anexos/eliminar/${anexoId}`, () => {
                showToast('Anexo eliminado correctamente.', 'success');
                $(`#formActualizarAnexos-${denunciaId}`).find(`.delete-anexo[data-id="${anexoId}"]`).closest('.card').remove();
            }).fail(() => showToast('Error al eliminar el anexo.', 'error'));
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

// Catálogos
function loadSubcategorias(categoriaId, selectSelector) {
    if (!categoriaId) return false;
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}categorias/listarSubcategorias`, { id_categoria: categoriaId }, function (data) {
        let options = '<option value="">Seleccione una subcategoría</option>';
        data.forEach(s => {
            options += `<option value="${s.id}">${s.nombre}</option>`;
        });
        $(selectSelector).html(options);
    }).fail(() => {
        $(selectSelector).html('');
        console.error('Error loading subcategories.');
    });
}

function loadSucursales(clienteId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${clienteId}`, function (data) {
        let options = '<option value="">Seleccione una sucursal</option>';
        data.forEach(s => {
            options += `<option value="${s.id}">${s.nombre}</option>`;
        });
        $(selectSelector).html(options);
    }).fail(() => {
        $(selectSelector).html('');
        console.error('Error loading branches.');
    });
}

function loadDepartamentos(sucursalId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`, function (data) {
        let options = '<option value="">Seleccione un departamento</option>';
        data.forEach(d => {
            options += `<option value="${d.id}">${d.nombre}</option>`;
        });
        $(selectSelector).html(options);
    }).fail(() => {
        $(selectSelector).html('');
        console.error('Error al cargar los departamentos.');
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
        let html = '';
        if (data.length > 0) {
            data.forEach(c => {
                const badgeClass = obtenerBadgeClase(c.estado_nombre);
                html += `
                    <div class="comentario-item d-flex mb-3">
                        <div class="contenido flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">${c.nombre_usuario}</h6>
                                    <small class="text-muted">${c.fecha_comentario}</small><br>
                                    <span class="badge ${badgeClass} mb-2">${c.estado_nombre}</span>
                                    <p class="mb-0">${c.contenido}</p>`;
                if (c.archivos && c.archivos.length > 0) {
                    html += '<div class="mt-2">';
                    c.archivos.forEach(a => {
                        const url = `${Server}${a.ruta_archivo}`;
                        const ext = a.nombre_archivo.split('.').pop().toLowerCase();
                        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                            html += `<div><a href="${url}" data-fancybox="comentario-${c.id}" data-caption="${a.nombre_archivo}"><img src="${url}" alt="imagen" style="max-width: 120px;" class="img-thumbnail me-2 mb-2"></a></div>`;
                        } else {
                            html += `<div><a href="${url}" target="_blank">${a.nombre_archivo}</a></div>`;
                        }
                    });
                    html += '</div>';
                }
                html += `
                                </div>
                                <button type="button" class="btn btn-sm btn-danger ms-3 btn-eliminar-comentario" data-id="${c.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>`;
            });
        } else html = '<p class="text-muted">No hay comentarios aún.</p>';
        $('#comentariosContainer').html(html);
    });
}

function obtenerBadgeClase(estado) {
    switch (estado) {
        case 'Recepción':
            return 'bg-yellow';
        case 'Clasificada':
            return 'bg-purple';
        case 'Revisada por Calidad':
            return 'bg-teal';
        case 'Liberada al Cliente':
            return 'bg-red';
        case 'En Revisión por Cliente':
            return 'bg-light-purple';
        case 'Cerrada':
            return 'bg-dark-teal';
        default:
            return 'bg-light text-dark';
    }
}

$(document).on('click', '.btn-eliminar-comentario', function () {
    const id = $(this).data('id');
    const den = $('#id_denuncia').val();
    confirm('¿Estás seguro?', 'Esta acción no se puede deshacer.').then(r => {
        if (r.isConfirmed) {
            $.post(`${Server}comentarios/eliminar/${id}`, () => {
                showToast('Comentario eliminado correctamente.', 'success');
                cargarComentarios(den);
            }).fail(() => showToast('Error al eliminar el comentario.', 'error'));
        }
    });
});

/** ===== IA: helpers ===== */
function iaShowLoading($box, msg) {
    $box.html(`<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>${msg || 'Generando sugerencia...'}</span></div>`).removeClass('text-muted');
}

function iaRenderMarkdown(text) {
    if (!text) return '';
    return text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
}

function iaSetMeta(id, sug) {
    $('#iaMeta-' + id).removeClass('d-none');
    $('#iaMetaModelo-' + id).text(sug?.modelo || '-');
    $('#iaMetaTokens-' + id).text(sug?.tokens_usados || sug?.tokens_utilizados || 0);
    $('#iaMetaCosto-' + id).text((sug?.costo_estimado ?? 0).toString());
    $('#iaMetaTiempo-' + id).text(sug?.tiempo_generacion ?? '0.000');
    $('#iaId-' + id).text(sug?.id || sug?.id_sugerencia || '');

    const sugId = (sug?.id ?? sug?.id_sugerencia ?? '').toString();
    $(`.btn-publicar-ia[data-id="${id}"],
   .btn-retirar-ia[data-id="${id}"],
   .btn-guardar-edicion-ia[data-id="${id}"]`).data('sugerencia', sugId);

    if (sug?.prompt_usado) {
        $(`.btn-ver-prompt-ia[data-id="${id}"]`)
            .removeClass('d-none')
            .on('click', function () {
                $('#promptIAContent').text(sug.prompt_usado);
            });
    }
    // estado/público
    if (Number(sug?.publicado) === 1) {
        $('#iaPublicado-' + id).removeClass('d-none');
        $('.btn-retirar-ia[data-id="' + id + '"]').removeClass('d-none');
        $('.btn-publicar-ia[data-id="' + id + '"]').addClass('d-none');
    } else {
        $('#iaPublicado-' + id).addClass('d-none');
        $('.btn-retirar-ia[data-id="' + id + '"]').addClass('d-none');
        $('.btn-publicar-ia[data-id="' + id + '"]').removeClass('d-none');
    }
}

function iaRenderResult(id, payload, wasRegenerated = false) {
    const $orig = $(`#iaResult-${id}`);
    const $clientePrev = $(`#iaCliente-${id} > div`);
    const $btnGen = $(`.btn-generar-ia[data-id="${id}"]`);
    const $btnRegen = $(`.btn-regenerar-ia[data-id="${id}"]`);
    const sug = payload?.sugerencia || payload;

    if (sug) {
        const original = sug.sugerencia_generada || sug.sugerencia || '';
        $orig.removeClass('text-muted').html(iaRenderMarkdown(original || ''));
        const ed = sug.sugerencia_agente || '';
        $clientePrev.html(iaRenderMarkdown(ed || original || 'Sin edición del agente todavía.'));
        $btnGen.addClass('d-none');
        $btnRegen.removeClass('d-none');
        $('.btn-editar-ia[data-id="' + id + '"], .btn-guardar-edicion-ia[data-id="' + id + '"], .btn-publicar-ia[data-id="' + id + '"]').removeClass('d-none');
        iaSetMeta(id, sug);
        if (wasRegenerated) Swal.fire('Listo', 'Sugerencia regenerada.', 'success');
    } else {
        $orig.addClass('text-muted').text('No hay sugerencia generada aún.');
        $clientePrev.text('Sin edición del agente todavía.');
        $btnGen.removeClass('d-none');
        $btnRegen.addClass('d-none');
        $('.btn-editar-ia[data-id="' + id + '"], .btn-guardar-edicion-ia[data-id="' + id + '"], .btn-publicar-ia[data-id="' + id + '"], .btn-retirar-ia[data-id="' + id + '"]').addClass('d-none');
        $('#iaMeta-' + id).addClass('d-none');
    }
}

function iaLoadIfExists(idDenuncia) {
    const $res = $(`#iaResult-${idDenuncia}`);
    iaShowLoading($res, 'Buscando sugerencia existente...');
    $.get(`${Server}api/denuncias/${idDenuncia}/sugerencia-ia`)
        .done(resp => {
            if (resp.success && resp.sugerencia) iaRenderResult(idDenuncia, resp.sugerencia);
            else iaRenderResult(idDenuncia, null);
        })
        .fail(() => iaRenderResult(idDenuncia, null));
}

$(document).on('click', '.btn-generar-ia', function (e) {
    e.preventDefault();
    const id = $(this).data('id');
    const $res = $(`#iaResult-${id}`);
    iaShowLoading($res, 'Generando sugerencia con IA...');
    $.post(`${Server}api/denuncias/${id}/sugerencia-ia`)
        .done(resp => {
            if (resp.success) {
                iaRenderResult(id, {
                    sugerencia: {
                        id: resp.id || resp.id_sugerencia,
                        id_sugerencia: resp.id_sugerencia,
                        sugerencia: resp.sugerencia,
                        sugerencia_generada: resp.sugerencia,
                        sugerencia_agente: resp.sugerencia,
                        tokens_usados: resp.tokens_usados,
                        costo_estimado: resp.costo_estimado,
                        modelo: resp.modelo || 'gpt-4o',
                        tiempo_generacion: resp.tiempo_generacion,
                        prompt_usado: resp.prompt_usado || null,
                        publicado: 0
                    }
                });
            } else {
                Swal.fire('Aviso', resp.message || 'No se pudo generar la sugerencia.', 'warning');
                iaRenderResult(id, null);
            }
        })
        .fail(() => {
            Swal.fire('Error', 'Error al generar la sugerencia.', 'error');
            iaRenderResult(id, null);
        });
});

$(document).on('click', '.btn-regenerar-ia', function (e) {
    e.preventDefault();
    const id = $(this).data('id');
    const $res = $(`#iaResult-${id}`);
    iaShowLoading($res, 'Regenerando sugerencia con IA...');
    iaBusyOn(id, 'Regenerando…');
    const slowTimer = setTimeout(() => {
        showToast('La IA sigue trabajando…', 'info');
    }, 8000);
    $.ajax({ url: `${Server}api/denuncias/${id}/sugerencia-ia/regenerar`, type: 'PUT' })
        .done(resp => {
            if (resp.success) {
                iaRenderResult(
                    id,
                    {
                        sugerencia: {
                            id: resp.id || resp.id_sugerencia,
                            id_sugerencia: resp.id_sugerencia,
                            sugerencia: resp.sugerencia,
                            sugerencia_generada: resp.sugerencia,
                            sugerencia_agente: resp.sugerencia,
                            tokens_usados: resp.tokens_usados,
                            costo_estimado: resp.costo_estimado,
                            modelo: resp.modelo || 'gpt-4o',
                            tiempo_generacion: resp.tiempo_generacion,
                            prompt_usado: resp.prompt_usado || null,
                            publicado: 0
                        }
                    },
                    true
                );
                $tablaDenuncias.bootstrapTable('refresh');
                showToast('Sugerencia regenerada.', 'success');
            } else {
                Swal.fire('Aviso', resp.message || 'No se pudo regenerar la sugerencia.', 'warning');
                iaRenderResult(id, null);
            }
        })
        .fail(() => {
            Swal.fire('Error', 'Error al regenerar la sugerencia.', 'error');
            iaRenderResult(id, null);
        })
        .always(() => {
            clearTimeout(slowTimer);
            iaBusyOff(id);
        });
});

function iaBusyOn(id, msg) {
    const $box = $(`#iaBox-${id}`);
    if (!$box.find('.ia-overlay').length) $box.append(`<div class="ia-overlay"><div class="spinner-border" role="status" aria-hidden="true"></div><small class="mt-2">${msg || 'Procesando...'}</small></div>`);
    $(`.btn-generar-ia[data-id="${id}"], .btn-regenerar-ia[data-id="${id}"]`).each(function () {
        const $b = $(this);
        if (!$b.data('origHtml')) $b.data('origHtml', $b.html());
        $b.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${$b.text().trim()}`);
    });
}

function iaBusyOff(id) {
    $(`#iaBox-${id} .ia-overlay`).remove();
    $(`.btn-generar-ia[data-id="${id}"], .btn-regenerar-ia[data-id="${id}"]`).each(function () {
        const $b = $(this);
        const orig = $b.data('origHtml');
        if (orig) $b.html(orig);
        $b.prop('disabled', false);
    });
}

$(document).on('click', '.btn-editar-ia', function () {
    const id = $(this).data('id');
    const $wrap = $(`#iaEditorWrap-${id}`);
    const $ta = $(`#iaEdit-${id}`);
    const prevHtml = $(`#iaCliente-${id} > div`).html();
    let base = prevHtml && prevHtml.trim() !== 'Sin edición del agente todavía.' ? prevHtml : $(`#iaResult-${id}`).html();
    const text = $('<div>')
        .html(base)
        .text()
        .replace(/\s*\n\s*/g, '\n');
    if (!$ta.val()) $ta.val(text);
    $wrap.toggleClass('d-none');
});

$(document).on('click', '.btn-guardar-edicion-ia', function () {
    const id = $(this).data('id');
    const sugId = $('#iaId-' + id).text();
    const texto = $(`#iaEdit-${id}`).val().trim();
    if (!sugId) return Swal.fire('Aviso', 'No hay sugerencia para editar.', 'warning');
    if (!texto) return Swal.fire('Aviso', 'Escribe algo para guardar.', 'warning');
    $.post(`${Server}api/denuncias/sugerencia-ia/guardar-edicion`, { id_sugerencia: sugId, texto }, function (resp) {
        if (resp.success) {
            $(`#iaCliente-${id} > div`).html(iaRenderMarkdown(texto));
            $(`#iaEditorWrap-${id}`).addClass('d-none');
            showToast('Edición guardada.', 'success');
        } else Swal.fire('Aviso', resp.message || 'No se pudo guardar la edición.', 'warning');
    }).fail(() => Swal.fire('Error', 'Error al guardar la edición.', 'error'));
});

$(document).on('click', '.btn-publicar-ia, .btn-retirar-ia', function () {
    const id = $(this).data('id');
    const publicar = Number($(this).data('publicar'));
    const sugId = $('#iaId-' + id).text();
    if (!sugId) return Swal.fire('Aviso', 'No hay sugerencia para publicar.', 'warning');
    $.post(`${Server}api/denuncias/sugerencia-ia/publicar`, { id_sugerencia: sugId, publicar }, function (resp) {
        if (resp.success) {
            if (publicar === 1) {
                $('#iaPublicado-' + id).removeClass('d-none');
                $('.btn-retirar-ia[data-id="' + id + '"]').removeClass('d-none');
                $('.btn-publicar-ia[data-id="' + id + '"]').addClass('d-none');
                showToast('Sugerencia publicada al cliente.', 'success');
            } else {
                $('#iaPublicado-' + id).addClass('d-none');
                $('.btn-retirar-ia[data-id="' + id + '"]').addClass('d-none');
                $('.btn-publicar-ia[data-id="' + id + '"]').removeClass('d-none');
                showToast('Sugerencia retirada del cliente.', 'success');
            }
        } else Swal.fire('Aviso', resp.message || 'No se pudo completar la acción.', 'warning');
    }).fail(() => Swal.fire('Error', 'Error al actualizar el estado de publicación.', 'error'));
});
