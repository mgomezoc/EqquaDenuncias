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
                title: 'Estado',
                formatter: operateFormatterEstado
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
