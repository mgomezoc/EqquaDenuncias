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

    // Funcionalidades para los botones de la tabla
    window.operateEvents = {
        // Ver detalle
        'click .view-detail': function (e, value, row) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));

                // Obtener anexos
                $.get(`${Server}denuncias/obtenerAnexos/${row.id}`, function (anexos) {
                    let anexosHtml = '';

                    if (anexos.length > 0) {
                        anexosHtml = anexos
                            .map(anexo => {
                                const ruta = `${Server}${anexo.ruta_archivo}`;
                                const tipo = (anexo.tipo || '').toLowerCase();

                                // PDFs
                                if (tipo === 'application/pdf') {
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <a href="${ruta}" data-fancybox="pdf-${anexo.id}" data-caption="${anexo.nombre_archivo}" class="pdf-viewer">
                                                <i class="far fa-file-pdf me-2"></i>${anexo.nombre_archivo}
                                            </a>
                                        </div>
                                    </div>`;
                                }

                                // Videos WebM
                                if (tipo === 'video/webm') {
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <a href="${ruta}" data-fancybox="video-${anexo.id}" data-caption="${anexo.nombre_archivo}">
                                                <video controls style="max-width: 180px;">
                                                    <source src="${ruta}" type="video/webm">
                                                    Tu navegador no soporta el formato WebM.
                                                </video>
                                            </a>
                                        </div>
                                    </div>`;
                                }

                                // NUEVO: Audio (mp3, wav, ogg…)
                                if (tipo.startsWith('audio/')) {
                                    // normalizamos algunos tipos comunes
                                    const mime = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'].includes(tipo) ? tipo : 'audio/mpeg';
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center gap-3">
                                                <audio controls preload="none" style="width: 100%;">
                                                    <source src="${ruta}" type="${mime}">
                                                    Tu navegador no soporta reproducción de audio.
                                                </audio>
                                            </div>
                                            <div class="mt-2 small">
                                                <i class="fa fa-music me-1"></i>${anexo.nombre_archivo}
                                                · <a href="${ruta}" download>Descargar</a>
                                            </div>
                                        </div>
                                    </div>`;
                                }

                                // Imágenes u otros
                                return `
                                <div class="card mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <a href="${ruta}" data-fancybox="image-${anexo.id}" data-caption="${anexo.nombre_archivo}">
                                            <img src="${ruta}" alt="${anexo.nombre_archivo}" class="img-thumbnail" style="max-width: 100px;">
                                        </a>
                                    </div>
                                </div>`;
                            })
                            .join('');
                    } else {
                        anexosHtml = '<p class="text-center">No hay archivos adjuntos.</p>';
                    }

                    // Datos del denunciante
                    let denuncianteHtml =
                        data.anonimo === '0'
                            ? `<p><strong>Denunciante:</strong> ${data.nombre_completo || 'N/A'}</p>
                           <p><strong>Correo Electrónico:</strong> ${data.correo_electronico || 'N/A'}</p>
                           <p><strong>Teléfono:</strong> ${data.telefono || 'N/A'}</p>`
                            : '<p><strong>Denunciante:</strong> Anónimo</p>';

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
                                <div class="col-12 mt-3">${denuncianteHtml}</div>
                                <div class="col-12 mt-3">
                                    <p><strong>Archivos Adjuntos:</strong></p>
                                    ${anexosHtml}
                                </div>
                            </div>
                        </div>`;

                    $('#modalVerDetalle .modal-body').html(contenido);
                    modal.show();
                });
            });
        },

        // Cambiar estado
        'click .change-status': function (e, value, row) {
            $.get(`${Server}denuncias/obtenerEstados`, function (estados) {
                let opciones = '';
                const estadosPermitidos = [];
                switch (parseInt(row.estado_actual)) {
                    case 4:
                        estadosPermitidos.push(5);
                        break;
                    case 5:
                        estadosPermitidos.push(6);
                        break;
                    case 6:
                        estadosPermitidos.push(4, 5, 6);
                        break;
                    default:
                        estadosPermitidos.push(parseInt(row.estado_actual));
                        break;
                }
                estados.forEach(estado => {
                    if (estadosPermitidos.includes(parseInt(estado.id))) {
                        const selected = estado.id === row.estado_actual ? 'selected' : '';
                        opciones += `<option value="${estado.id}" ${selected}>${estado.nombre}</option>`;
                    }
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

                $('#modalCambiarEstado .btn-primary')
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

        // Ver comentarios
        'click .view-comments': function (e, value, row) {
            cargarComentarios(row.id);
            $('#id_denuncia').val(row.id);
            $('#folioDenuncia').html(row.folio);
            $('#modalVerComentarios').modal('show');
        },

        // Ver sugerencia IA publicada
        'click .view-ia-suggestion': function (e, value, row) {
            e.preventDefault();
            const modal = new bootstrap.Modal($('#modalSugerenciaIA'));
            const $box = $('#iaContenidoCliente');
            const $meta = $('#iaMetaCliente');

            $box.html('<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando sugerencia…</div>');
            $meta.hide();

            $.get(`${Server}api/denuncias/${row.id}/sugerencia-ia`)
                .done(resp => {
                    const sug = resp?.sugerencia || resp;
                    if (!sug || Number(sug.publicado) !== 1) {
                        $box.html(`
                            <div class="alert alert-info" role="alert">
                                Aún no hay una sugerencia disponible para esta denuncia.
                                El equipo puede generarla y publicarla desde la administración.
                            </div>`);
                        modal.show();
                        return;
                    }

                    const texto = (sug.sugerencia_agente || sug.sugerencia_generada || '').toString().replace(/\n/g, '<br>');
                    $box.html(`<div class="bg-white p-3 rounded">${texto || 'Sin contenido.'}</div>`);

                    $('#iaModeloCliente').text(sug.modelo_ia_usado || sug.modelo || '-');
                    $('#iaTokensCliente').text(sug.tokens_utilizados || sug.tokens_usados || 0);
                    $('#iaTiempoCliente').text(sug.tiempo_generacion || '0.000');
                    //$meta.show();

                    modal.show();
                })
                .fail(() => {
                    $box.html(`
                        <div class="alert alert-danger" role="alert">
                            Ocurrió un error al intentar obtener la sugerencia. Intenta nuevamente más tarde.
                        </div>`);
                    modal.show();
                });
        }
    };

    // Inicialización de la tabla
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listarDenunciasCliente`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'folio', title: 'Folio' },
            { field: 'sucursal_nombre', title: 'Sucursal' },
            {
                field: 'tipo_denunciante',
                title: 'Tipo Denunciante',
                formatter: (value, row) => (value === 'No anónimo' ? row.nombre_completo : value)
            },
            { field: 'categoria_nombre', title: 'Categoría' },
            { field: 'subcategoria_nombre', title: 'Subcategoría' },
            { field: 'departamento_nombre', title: 'Departamento' },
            { field: 'estado_nombre', title: 'Estatus', formatter: operateFormatterEstado },
            { field: 'fecha_hora_reporte', title: 'Fecha', formatter: operateFormatterFecha },
            { field: 'sexo_nombre', title: 'Sexo' },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                clickToSelect: false,
                formatter: operateFormatter,
                events: operateEvents
            }
        ]
    });

    // Envío de comentario
    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        const $textarea = $('#contenidoComentario');
        const $submitButton = $frm.find('button[type="submit"]');
        const formData = new FormData($frm[0]);

        $textarea.prop('disabled', true);
        $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');

        $.ajax({
            url: `${Server}comentarios/guardar`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                cargarComentarios($('#id_denuncia').val());
                showToast('Comentario agregado exitosamente.', 'success');
                $frm[0].reset();
            },
            error: function (err) {
                const message = err.responseJSON?.message || 'Error al enviar el comentario';
                showToast(message, 'error');
            },
            complete: function () {
                $textarea.prop('disabled', false);
                $submitButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar');
            }
        });
    });
});

// Helpers
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
    let estado = row.estado_nombre;
    let badgeClass = '';
    switch (estado) {
        case 'Liberada al Cliente':
            estado = 'Nueva';
            badgeClass = 'bg-primary';
            break;
        case 'En Revisión por Cliente':
            estado = 'En Revisión';
            badgeClass = 'bg-warning';
            break;
        default:
            return '';
    }
    return `<span class="badge ${badgeClass}">${estado}</span>`;
}

function cargarComentarios(denunciaId) {
    $.get(`${Server}comentarios/listar-cliente/${denunciaId}`, function (data) {
        let comentariosHtml = '';

        if (data.length > 0) {
            data.forEach(comentario => {
                // Renderizar archivos
                let archivosHtml = '';
                if (comentario.archivos && comentario.archivos.length > 0) {
                    archivosHtml += '<div class="mt-2"><strong>Archivos:</strong><ul class="list-unstyled d-flex flex-column gap-3">';

                    comentario.archivos.forEach(archivo => {
                        const ruta = `${Server}${archivo.ruta_archivo}`;
                        const mime = (archivo.tipo_mime || '').toLowerCase();

                        if (mime.startsWith('image/')) {
                            archivosHtml += `
                                <li>
                                    <a href="${ruta}" data-fancybox="comentario-${comentario.id}" data-caption="${archivo.nombre_archivo}">
                                        <img src="${ruta}" class="img-thumbnail" style="max-width: 120px;">
                                    </a>
                                </li>`;
                        } else if (mime.startsWith('audio/')) {
                            const tipoAudio = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'].includes(mime) ? mime : 'audio/mpeg';
                            archivosHtml += `
                                <li>
                                    <div class="card card-body">
                                        <div class="mb-1"><i class="fa fa-music me-1"></i>${archivo.nombre_archivo}</div>
                                        <audio controls preload="none" style="width:100%;">
                                            <source src="${ruta}" type="${tipoAudio}">
                                            Tu navegador no soporta reproducción de audio.
                                        </audio>
                                        <div class="mt-1 small"><a href="${ruta}" download>Descargar</a></div>
                                    </div>
                                </li>`;
                        } else {
                            archivosHtml += `<li><a href="${ruta}" target="_blank">${archivo.nombre_archivo}</a></li>`;
                        }
                    });

                    archivosHtml += '</ul></div>';
                }

                comentariosHtml += `
                    <div class="comentario-item d-flex mb-3">
                        <div class="contenido flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">${comentario.nombre_usuario}</h6>
                                <small class="text-muted">${comentario.fecha_comentario}</small>
                            </div>                            
                            <p class="mb-0">${comentario.contenido}</p>
                            ${archivosHtml}
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
