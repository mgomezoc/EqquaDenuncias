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
            fecha_incidente: {
                required: 'Por favor ingrese la fecha del incidente',
                date: 'Ingrese una fecha válida'
            },
            descripcion: { required: 'Por favor ingrese la descripción' }
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
                success: function () {
                    loadingFormXHR($frm, false);
                    $tablaDenuncias.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la denuncia.', 'success');

                    // Limpiar el formulario y los estilos de validación
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                    $frm.find('.is-invalid').removeClass('is-invalid');

                    // Resetear todos los select2
                    $frm.find('.select2').val(null).trigger('change', true);

                    // Limpiar los archivos de Dropzone (key correcta)
                    if (dropzones['dropzoneArchivos']) {
                        dropzones['dropzoneArchivos'].removeAllFiles(true);
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

        // Reinicializar todos los select2
        $form.find('.select2').val(null).trigger('change');

        // Resetear los archivos subidos en Dropzone (key correcta)
        if (dropzones['dropzoneArchivos']) {
            dropzones['dropzoneArchivos'].removeAllFiles(true);
            dropzones['dropzoneArchivos'].element.classList.remove('dz-started');
        }
    });

    // Funcionalidades para los botones de la tabla
    window.operateEvents = {
        // Funcionalidad para el botón de eliminar
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

        // Funcionalidad para el botón de ver detalle (incluye render de mp3)
        'click .view-detail': function (e, value, row) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));

                // Opcional: intenta formatear fecha con helper global si existe
                const tryFormat = d => (typeof operateFormatterFecha === 'function' ? operateFormatterFecha(d) : d || 'N/A');
                const fechaIncidente = tryFormat(data.fecha_incidente);

                // Icono por tipo de archivo
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
                        mp3: 'fa-file-audio text-info',
                        wav: 'fa-file-audio text-info',
                        ogg: 'fa-file-audio text-info'
                    };
                    return icons[ext] || 'fa-file text-secondary';
                };

                // Renderizar archivos anexos (imágenes → Fancybox, mp3 → <audio>, otros → link)
                let archivosHtml = '';
                if (data.archivos && data.archivos.length > 0) {
                    archivosHtml += `
                        <div class="mt-4">
                            <h5 class="mb-3">
                                <i class="fas fa-paperclip me-2"></i>Archivos Adjuntos 
                                <span class="badge bg-secondary ms-2">${data.archivos.length}</span>
                            </h5>
                            <div class="row g-3">
                    `;

                    data.archivos.forEach((archivo, idx) => {
                        const url = `${Server}${archivo.ruta_archivo}`;
                        const ext = (archivo.nombre_archivo || '').split('.').pop().toLowerCase();
                        const esImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                        const esAudio = ['mp3', 'wav', 'ogg'].includes(ext);
                        const nombreCorto = archivo.nombre_archivo && archivo.nombre_archivo.length > 25 ? archivo.nombre_archivo.substring(0, 22) + '...' + ext : archivo.nombre_archivo || 'archivo';

                        if (esImagen) {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay: ${idx * 0.1}s">
                                    <div class="card shadow-sm h-100 archivo-card">
                                        <a href="${url}" 
                                           data-fancybox="denuncia-${data.id}" 
                                           data-caption="${archivo.nombre_archivo}"
                                           class="archivo-imagen-link">
                                            <div class="archivo-imagen-container">
                                                <img src="${url}" 
                                                     alt="${archivo.nombre_archivo}" 
                                                     class="card-img-top archivo-imagen"
                                                     loading="lazy">
                                                <div class="archivo-overlay">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="card-body p-2">
                                            <p class="card-text text-center small mb-0" title="${archivo.nombre_archivo}">
                                                <i class="fas fa-image text-primary me-1"></i>
                                                ${nombreCorto}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (esAudio) {
                            archivosHtml += `
                                <div class="col-12 col-md-6 animate__animated animate__fadeIn" style="animation-delay: ${idx * 0.1}s">
                                    <div class="card shadow-sm h-100 archivo-card p-3">
                                        <div class="small mb-2" title="${archivo.nombre_archivo}">
                                            <i class="fas fa-file-audio text-info me-1"></i>${nombreCorto}
                                        </div>
                                        <audio controls preload="none" style="width: 100%;">
                                            <source src="${url}" type="${ext === 'mp3' ? 'audio/mpeg' : ext === 'ogg' ? 'audio/ogg' : 'audio/wav'}">
                                            Tu navegador no soporta audio HTML5.
                                        </audio>
                                        <div class="mt-2">
                                            <a href="${url}" download class="small">Descargar</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            archivosHtml += `
                                <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay: ${idx * 0.1}s">
                                    <div class="card shadow-sm h-100 archivo-card">
                                        <a href="${url}" target="_blank" class="text-decoration-none archivo-documento-link">
                                            <div class="card-body text-center py-4">
                                                <i class="fas ${getFileIcon(archivo.nombre_archivo)} archivo-icono mb-3"></i>
                                                <p class="card-text small mb-0 text-dark" title="${archivo.nombre_archivo}">
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
                                        ${(data.seguimientos || [])
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

                // Inicializar Fancybox para imágenes
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

        // Funcionalidad para el botón de cambiar estado
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
        url: `${Server}denuncias/listar-denuncias-agente`,
        columns: [
            { field: 'id', title: 'ID', formatter: value => `<b>${value}</b>` },
            {
                field: 'folio',
                title: 'Folio',
                cellStyle: {
                    css: { 'white-space': 'nowrap', 'max-width': '250px', overflow: 'hidden', 'text-overflow': 'ellipsis' }
                }
            },
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

            if (row.categoria) {
                requests.push($.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }));
            }

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
                    comboComoSeEntero: comboComoSeEntero,
                    comboMedioRecepcion: comboMedioRecepcion,
                    comboSexo: comboSexo,
                    id_sexo: row.id_sexo,
                    medio_recepcion: row.medio_recepcion
                };

                const renderData = Handlebars.compile(tplDetalleTabla)(data);

                // Renderizar y mostrar el detalle
                $detail.html(renderData);

                // Si la denuncia está cerrada, deshabilitar los campos y ocultar el botón de actualización
                if (row.estado_actual == 6) {
                    $detail.find('input, select, textarea').prop('disabled', true);
                    $detail.find('.btn-actualizar-denuncia').hide();
                }

                // Inicializar select2 para los nuevos selectores
                $detail.find('select').select2();
                // Aplicar flatpickr a "Fecha del Incidente" en la edición
                initializeFlatpickrForEdit(`#fecha_incidente-${row.id}`);

                // Validación del formulario y configuración de eventos
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
                    loadDepartamentos(sucursalId, `#id_departamento-${row.id}`);
                });

                // Inicializar Dropzone
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
        altInput: true,
        altFormat: 'd/m/Y',
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
        // ✅ Acepta imágenes, PDFs y audio (incluye .mp3 explícito)
        acceptedFiles: 'image/*,application/pdf,audio/mpeg,.mp3,audio/*',
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
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
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

                // Asignar el color correspondiente según el estado
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
