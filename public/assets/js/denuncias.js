/**
 * DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return v1 == v2 ? options.fn(this) : options.inverse(this);
        case '===':
            return v1 === v2 ? options.fn(this) : options.inverse(this);
        case '!=':
            return v1 != v2 ? options.fn(this) : options.inverse(this);
        case '!==':
            return v1 !== v2 ? options.fn(this) : options.inverse(this);
        case '<':
            return v1 < v2 ? options.fn(this) : options.inverse(this);
        case '<=':
            return v1 <= v2 ? options.fn(this) : options.inverse(this);
        case '>':
            return v1 > v2 ? options.fn(this) : options.inverse(this);
        case '>=':
            return v1 >= v2 ? options.fn(this) : options.inverse(this);
        case '&&':
            return v1 && v2 ? options.fn(this) : options.inverse(this);
        case '||':
            return v1 || v2 ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
    }
});

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearDenuncia = $('#modalCrearDenuncia');

    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

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
                field: 'departamento',
                title: 'Departamento'
            },
            {
                field: 'estado_nombre',
                title: 'Estado'
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
                events: window.operateEvents
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            // Obtener datos para los selectores de la fila expandida
            $.when(
                $.get(`${Server}clientes/listar`), // Ruta para listar clientes
                $.get(`${Server}categorias/listarCategorias`), // Ruta para listar categorías
                $.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }), // Ruta para listar subcategorías basadas en la categoría seleccionada
                $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${row.id_cliente}`), // Ruta para obtener sucursales por cliente
                $.get(`${Server}denuncias/detalle/${row.id}`) // Ruta para obtener los detalles de la denuncia
            ).done(function (clientes, categorias, subcategorias, sucursales, denunciaDetalles) {
                // Compilar el template con los datos recibidos
                const renderData = Handlebars.compile(tplDetalleTabla)({
                    id: row.id,
                    clientes: clientes[0],
                    categorias: categorias[0],
                    subcategorias: subcategorias[0],
                    sucursales: sucursales[0],
                    estados: denunciaDetalles[0].estados || [],
                    id_cliente: row.id_cliente,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo
                });

                // Renderizar y mostrar el detalle
                $detail.html(renderData);

                // Inicializar select2 para los nuevos selectores
                $detail.find('select').select2();
                $detail.find('.formEditarDenuncia').validate({
                    rules: {
                        id_cliente: {
                            required: true
                        },
                        categoria: {
                            required: true
                        },
                        subcategoria: {
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
                        categoria: {
                            required: 'Por favor seleccione una categoría'
                        },
                        subcategoria: {
                            required: 'Por favor seleccione una subcategoría'
                        },
                        estado_actual: {
                            required: 'Por favor seleccione un estado'
                        },
                        descripcion: {
                            required: 'Por favor ingrese la descripción'
                        }
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
            });
        }
    });

    // Inicializar flatpickr para el campo de fecha
    $('#fecha_incidente').flatpickr({
        dateFormat: 'Y-m-d'
    });

    // Validación y manejo del formulario de creación de denuncias
    $('#formCrearDenuncia').validate({
        rules: {
            id_cliente: {
                required: true
            },
            categoria: {
                required: true
            },
            subcategoria: {
                required: true
            },
            descripcion: {
                required: true
            },
            fecha_incidente: {
                required: true,
                date: true
            }
        },
        messages: {
            id_cliente: {
                required: 'Por favor seleccione un cliente'
            },
            categoria: {
                required: 'Por favor seleccione una categoría'
            },
            subcategoria: {
                required: 'Por favor seleccione una subcategoría'
            },
            descripcion: {
                required: 'Por favor ingrese la descripción'
            },
            fecha_incidente: {
                required: 'Por favor ingrese la fecha del incidente',
                date: 'Ingrese una fecha válida'
            }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}denuncias/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearDenuncia.modal('hide');
                    $tablaDenuncias.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la denuncia.', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
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

    // Resetear el formulario al cerrar el modal de creación
    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
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
});

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}

async function eliminarDenuncia(id) {
    const data = await confirm('¿Estás seguro de eliminar esta denuncia?');
    if (data.isConfirmed) {
        ajaxCall({
            url: `${Server}denuncias/eliminar/${id}`,
            method: 'POST',
            success: function (response) {
                $tablaDenuncias.bootstrapTable('refresh');
                showToast('¡Denuncia eliminada correctamente!', 'success');
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Ocurrió un error al eliminar la denuncia.';
                showToast(errorMessage, 'error');
            }
        });
    }
}

async function cambiarEstadoDenuncia(id) {
    const newStatus = await promptForStatus(); // Función que muestra un modal para seleccionar el nuevo estado
    if (newStatus) {
        ajaxCall({
            url: `${Server}denuncias/cambiarEstado`,
            method: 'POST',
            data: { id, estado_nuevo: newStatus },
            success: function (response) {
                $tablaDenuncias.bootstrapTable('refresh');
                showToast('¡Estado de la denuncia actualizado correctamente!', 'success');
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Ocurrió un error al actualizar el estado de la denuncia.';
                showToast(errorMessage, 'error');
            }
        });
    }
}

function verDetalleDenuncia(id) {
    // Implementación de la función para ver detalles completos de una denuncia en un modal o nueva vista.
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
            console.error('Error loading branches.');
        }
    });
}
