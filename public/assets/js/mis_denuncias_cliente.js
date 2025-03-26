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
        // Funcionalidad para el botón de ver detalle
        'click .view-detail': function (e, value, row, index) {
            $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                const modal = new bootstrap.Modal($('#modalVerDetalle'));

                // Obtener los anexos de la denuncia
                $.get(`${Server}denuncias/obtenerAnexos/${row.id}`, function (anexos) {
                    let anexosHtml = '';

                    if (anexos.length > 0) {
                        anexosHtml = anexos
                            .map(anexo => {
                                if (anexo.tipo === 'application/pdf') {
                                    // Para archivos PDF
                                    return `
                                        <div class="card mb-3">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <a href="${Server}${anexo.ruta_archivo}" data-fancybox="pdf-${anexo.id}" data-caption="${anexo.nombre_archivo}" class="pdf-viewer">${anexo.nombre_archivo}</a>
                                            </div>
                                        </div>
                                    `;
                                } else if (anexo.tipo === 'video/webm') {
                                    // Para archivos WebM (videos)
                                    return `
                                        <div class="card mb-3">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <a href="${Server}${anexo.ruta_archivo}" data-fancybox="video-${anexo.id}" data-caption="${anexo.nombre_archivo}">
                                                    <video controls style="max-width: 100px;">
                                                        <source src="${Server}${anexo.ruta_archivo}" type="video/webm">
                                                        Tu navegador no soporta el formato WebM.
                                                    </video>
                                                </a>
                                            </div>
                                        </div>
                                    `;
                                } else {
                                    // Para otros archivos (imágenes, etc.)
                                    return `
                                        <div class="card mb-3">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <a href="${Server}${anexo.ruta_archivo}" data-fancybox="image-${anexo.id}" data-caption="${anexo.nombre_archivo}">
                                                    <img src="${Server}${anexo.ruta_archivo}" alt="${anexo.nombre_archivo}" class="img-thumbnail" style="max-width: 100px;">
                                                </a>
                                            </div>
                                        </div>
                                    `;
                                }
                            })
                            .join('');
                    } else {
                        anexosHtml = '<p class="text-center">No hay archivos adjuntos.</p>';
                    }

                    let denuncianteHtml = '';
                    if (data.anonimo === '0') {
                        denuncianteHtml = `
                            <p><strong>Denunciante:</strong> ${data.nombre_completo || 'N/A'}</p>
                            <p><strong>Correo Electrónico:</strong> ${data.correo_electronico || 'N/A'}</p>
                            <p><strong>Teléfono:</strong> ${data.telefono || 'N/A'}</p>
                        `;
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
                    </div>
                `;

                    $('#modalVerDetalle .modal-body').html(contenido);
                    modal.show();
                });
            });
        },

        // Funcionalidad para el botón de cambiar estado
        'click .change-status': function (e, value, row, index) {
            $.get(`${Server}denuncias/obtenerEstados`, function (estados) {
                let opciones = '';
                const estadosPermitidos = []; // Aquí almacenaremos los estados que el cliente puede seleccionar

                // Determinar los estados permitidos en función del estado actual
                switch (parseInt(row.estado_actual)) {
                    case 4: // "Liberada al Cliente"
                        estadosPermitidos.push(5); // "En Revisión por Cliente"
                        break;
                    case 5: // "En Revisión por Cliente"
                        estadosPermitidos.push(6); // "Cerrada"
                        break;
                    case 6: // "Cerrada"
                        estadosPermitidos.push(4);
                        estadosPermitidos.push(5);
                        estadosPermitidos.push(6);
                        break;
                    default:
                        // En otros estados, no se permite cambiar el estado por el cliente
                        estadosPermitidos.push(parseInt(row.estado_actual)); // Solo mostrar el estado actual como seleccionable
                        break;
                }

                // Filtrar los estados según los permitidos para el cliente
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
            // Cargar comentarios de la denuncia
            cargarComentarios(row.id);

            // Establecer la ID de la denuncia en el formulario
            $('#id_denuncia').val(row.id);

            // Mostrar el modal
            $('#modalVerComentarios').modal('show');
        }
    };

    // Inicialización de la tabla de denuncias
    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}denuncias/listarDenunciasCliente`,
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
                title: 'Estatus',
                formatter: operateFormatterEstado
            },
            {
                field: 'fecha_hora_reporte',
                title: 'Fecha',
                formatter: operateFormatterFecha
            },
            {
                field: 'sexo_nombre',
                title: 'Sexo'
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
        ]
    });

    $('#formAgregarComentario').submit(function (e) {
        e.preventDefault();
        const $frm = $(this);
        const $textarea = $('#contenidoComentario');
        const $submitButton = $frm.find('button[type="submit"]');
        const formData = $frm.serialize();

        // Deshabilitar el textarea y el botón, y cambiar el texto del botón
        $textarea.prop('disabled', true);
        $submitButton.prop('disabled', true);
        $submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');

        $.post(`${Server}comentarios/guardar`, formData, function (response) {
            cargarComentarios($('#id_denuncia').val()); // Recargar los comentarios
            showToast('Comentario agregado exitosamente.', 'success');
            $textarea.val(''); // Limpiar el campo de texto
            $frm[0].reset();
        })
            .fail(function () {
                showToast('Error al agregar el comentario.', 'error');
            })
            .always(function () {
                // Rehabilitar el textarea y el botón, y restaurar el texto del botón original
                $textarea.prop('disabled', false);
                $submitButton.prop('disabled', false);
                $submitButton.html('<i class="fas fa-paper-plane"></i> Enviar');
            });
    });
});

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
    let estado = row.estado_nombre;
    let badgeClass = '';

    // Ajustar el nombre y color de los estados visibles para el cliente
    switch (estado) {
        case 'Liberada al Cliente':
            estado = 'Nueva'; // Cambiar el nombre para hacerlo más amigable
            badgeClass = 'bg-primary'; // Color azul para indicar que es un estado nuevo
            break;
        case 'En Revisión por Cliente':
            estado = 'En Revisión'; // Cambiar el nombre para hacerlo más amigable
            badgeClass = 'bg-warning'; // Color amarillo para indicar revisión en progreso
            break;
        default:
            // Si por alguna razón llega un estado que no debería estar aquí, no mostrar nada
            return '';
    }

    return `<span class="badge ${badgeClass}">${estado}</span>`;
}

function cargarComentarios(denunciaId) {
    $.get(`${Server}comentarios/listar-cliente/${denunciaId}`, function (data) {
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
