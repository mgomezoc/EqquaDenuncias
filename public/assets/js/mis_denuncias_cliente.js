/**
 * DENUNCIAS (vista cliente)
 * - Soporta visualización de MP3/audio en detalle de denuncia y en comentarios.
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let dropzones = {};

Dropzone.autoDiscover = false;

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();

    window.operateEvents = {
        // Ver detalle
        'click .view-detail': function (e, value, row) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));

                // Anexos
                $.get(`${Server}denuncias/obtenerAnexos/${row.id}`, function (anexos) {
                    let anexosHtml = '';

                    if (anexos.length > 0) {
                        anexosHtml = anexos
                            .map(anexo => {
                                const ruta = `${Server}${anexo.ruta_archivo}`;
                                const nombre = anexo.nombre_archivo || '';
                                const tipo = (anexo.tipo || '').toLowerCase();
                                const ext = nombre.split('.').pop()?.toLowerCase() || '';

                                // PDF
                                if (tipo === 'application/pdf' || ext === 'pdf') {
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <a href="${ruta}" data-fancybox="pdf-${anexo.id}" data-caption="${nombre}" class="pdf-viewer">
                                                ${nombre}
                                            </a>
                                        </div>
                                    </div>`;
                                }

                                // AUDIO (mp3 / audio/*)
                                if (tipo.startsWith('audio/') || ext === 'mp3') {
                                    // usar audio/mpeg como type por compatibilidad básica
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="small mb-2"><i class="fas fa-file-audio me-1"></i>${nombre}</div>
                                            <audio controls preload="none" style="width: 100%;">
                                                <source src="${ruta}" type="${tipo || 'audio/mpeg'}">
                                                Tu navegador no soporta audio HTML5.
                                            </audio>
                                            <div class="mt-1">
                                                <a href="${ruta}" download class="small">Descargar</a>
                                            </div>
                                        </div>
                                    </div>`;
                                }

                                // VIDEO (webm)
                                if (tipo === 'video/webm' || ext === 'webm') {
                                    return `
                                    <div class="card mb-3">
                                        <div class="card-body d-flex justify-content-between align-items-center">
                                            <a href="${ruta}" data-fancybox="video-${anexo.id}" data-caption="${nombre}">
                                                <video controls style="max-width: 120px;">
                                                    <source src="${ruta}" type="video/webm">
                                                    Tu navegador no soporta el formato WebM.
                                                </video>
                                            </a>
                                        </div>
                                    </div>`;
                                }

                                // Imagen (fallback)
                                return `
                                <div class="card mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <a href="${ruta}" data-fancybox="image-${anexo.id}" data-caption="${nombre}">
                                            <img src="${ruta}" alt="${nombre}" class="img-thumbnail" style="max-width: 120px;">
                                        </a>
                                    </div>
                                </div>`;
                            })
                            .join('');
                    } else {
                        anexosHtml = '<p class="text-center">No hay archivos adjuntos.</p>';
                    }

                    // Denunciante
                    let denuncianteHtml = '';
                    if (data.anonimo === '0') {
                        denuncianteHtml = `
                            <p><strong>Denunciante:</strong> ${data.nombre_completo || 'N/A'}</p>
                            <p><strong>Correo Electrónico:</strong> ${data.correo_electronico || 'N/A'}</p>
                            <p><strong>Teléfono:</strong> ${data.telefono || 'N/A'}</p>`;
                    } else {
                        denuncianteHtml = '<p><strong>Denunciante:</strong> Anónimo</p>';
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
                        break; // Liberada -> En Revisión por Cliente
                    case 5:
                        estadosPermitidos.push(6);
                        break; // En Revisión -> Cerrada
                    case 6:
                        estadosPermitidos.push(4, 5, 6);
                        break; // Cerrada -> (mostrar todos los visibles)
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

                $('#modalCambiarEstado .modal-footer .btn-primary')
                    .off('click')
                    .on('click', function () {
                        $.post(`${Server}denuncias/cambiarEstado`, { id: row.id, estado_nuevo: $('#estado_nuevo').val(), comentario: $('#comentario').val() }, function () {
                            showToast('Estatus actualizado correctamente.', 'success');
                            $tablaDenuncias.bootstrapTable('refresh');
                            modal.hide();
                        }).fail(() => showToast('Error al actualizar el estatus.', 'error'));
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
        }
    };

    // Tabla
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
            { field: 'operate', title: 'Acciones', align: 'center', valign: 'middle', clickToSelect: false, formatter: operateFormatter, events: operateEvents }
        ]
    });

    // Enviar comentario (con adjunto opcional)
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
            contentType: false
        })
            .done(() => {
                cargarComentarios($('#id_denuncia').val());
                showToast('Comentario agregado exitosamente.', 'success');
                $frm[0].reset();
            })
            .fail(err => showToast(err.responseJSON?.message || 'Error al enviar el comentario', 'error'))
            .always(() => {
                $textarea.prop('disabled', false);
                $submitButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar');
            });
    });
});

// Subcategorías
function loadSubcategorias(categoriaId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}categorias/listarSubcategorias`, { id_categoria: categoriaId })
        .done(data => {
            let options = '<option value="">Seleccione una subcategoría</option>';
            data.forEach(sc => {
                options += `<option value="${sc.id}">${sc.nombre}</option>`;
            });
            $(selectSelector).html(options);
        })
        .fail(() => {
            $(selectSelector).html('');
            console.error('Error loading subcategories.');
        });
}

// Sucursales
function loadSucursales(clienteId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${clienteId}`)
        .done(data => {
            let options = '<option value="">Seleccione una sucursal</option>';
            data.forEach(s => {
                options += `<option value="${s.id}">${s.nombre}</option>`;
            });
            $(selectSelector).html(options);
        })
        .fail(() => {
            $(selectSelector).html('');
            console.error('Error loading branches.');
        });
}

// Departamentos
function loadDepartamentos(sucursalId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.get(`${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`)
        .done(data => {
            let options = '<option value="">Seleccione un departamento</option>';
            data.forEach(d => {
                options += `<option value="${d.id}">${d.nombre}</option>`;
            });
            $(selectSelector).html(options);
        })
        .fail(() => {
            $(selectSelector).html('');
            console.error('Error al cargar los departamentos.');
        });
}

// Render acciones
function operateFormatter(value, row) {
    return Handlebars.compile(tplAccionesTabla)(row);
}

// Badges estados visibles al cliente
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

// Comentarios (con soporte de imágenes y audio)
function cargarComentarios(denunciaId) {
    $.get(`${Server}comentarios/listar-cliente/${denunciaId}`, function (data) {
        let comentariosHtml = '';

        if (data.length > 0) {
            data.forEach(c => {
                let archivosHtml = '';
                if (c.archivos && c.archivos.length > 0) {
                    archivosHtml += '<div class="mt-2"><strong>Archivos:</strong><ul class="list-unstyled d-flex flex-wrap gap-3">';

                    c.archivos.forEach(a => {
                        const ruta = `${Server}${a.ruta_archivo}`;
                        const tipo = (a.tipo_mime || '').toLowerCase();
                        const ext = (a.nombre_archivo || '').split('.').pop()?.toLowerCase() || '';

                        if (tipo.startsWith('image/')) {
                            archivosHtml += `
                                <li>
                                    <a href="${ruta}" data-fancybox="comentario-${c.id}" data-caption="${a.nombre_archivo}">
                                        <img src="${ruta}" class="img-thumbnail" style="max-width: 100px;">
                                    </a>
                                </li>`;
                        } else if (tipo.startsWith('audio/') || ext === 'mp3') {
                            archivosHtml += `
                                <li class="w-100">
                                    <div class="small mb-1"><i class="fas fa-file-audio me-1"></i>${a.nombre_archivo}</div>
                                    <audio controls preload="none" style="width: 100%;">
                                        <source src="${ruta}" type="${tipo || 'audio/mpeg'}">
                                        Tu navegador no soporta audio HTML5.
                                    </audio>
                                    <div><a href="${ruta}" download class="small">Descargar</a></div>
                                </li>`;
                        } else {
                            archivosHtml += `<li><a href="${ruta}" target="_blank">${a.nombre_archivo}</a></li>`;
                        }
                    });

                    archivosHtml += '</ul></div>';
                }

                comentariosHtml += `
                    <div class="comentario-item d-flex mb-3">
                        <div class="contenido flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">${c.nombre_usuario}</h6>
                                <small class="text-muted">${c.fecha_comentario}</small>
                            </div>
                            <p class="mb-0">${c.contenido}</p>
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
