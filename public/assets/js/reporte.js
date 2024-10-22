/**
 * REPORTE
 */

let $tablaDenuncias;
let $formFiltros;

$(document).ready(function () {
    $formFiltros = $('#formFiltros');

    $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}reportes/listar`,
        method: 'post',
        pagination: true,
        sidePagination: 'server',
        pageSize: 50,
        pageList: [50, 100, 150, 200],
        search: true,
        searchAlign: 'left',
        showRefresh: true,
        showColumns: true,
        showToggle: true,
        toolbar: '#toolbar',
        locale: 'es-MX',
        columns: [
            {
                field: 'fecha_hora_reporte',
                title: 'Fecha Reporte',
                sortable: true
            },
            {
                field: 'estado_nombre',
                title: 'Estatus',
                sortable: true
            },
            {
                field: 'folio',
                title: 'Folio',
                sortable: true,
                cellStyle: {
                    css: { 'white-space': 'nowrap', 'max-width': '250px', overflow: 'hidden', 'text-overflow': 'ellipsis' }
                }
            },
            {
                field: 'cliente_nombre',
                title: 'Cliente',
                sortable: true
            },
            {
                field: 'sucursal_nombre',
                title: 'Sucursal',
                sortable: true
            },
            {
                field: 'departamento_nombre',
                title: 'Departamento',
                sortable: true
            },
            {
                field: 'categoria_nombre',
                title: 'Categoría',
                sortable: true
            },
            {
                field: 'subcategoria_nombre',
                title: 'SubCategoría',
                sortable: true
            },
            {
                field: 'fecha_incidente',
                title: 'Fecha Incidente',
                sortable: true,
                formatter: dateFormatter
            },
            {
                field: 'medio_recepcion',
                title: 'Medio Recepción',
                sortable: true
            },
            {
                field: 'updated_at',
                title: 'Ultima Actualización',
                sortable: true
            }
        ],
        queryParams: function (params) {
            const filtros = $formFiltros.serializeObject();

            const queryParams = {
                ...params,
                ...filtros
            };

            console.log(queryParams);

            return queryParams;
        }
    });

    // Ejemplo de formateador de fecha
    function dateFormatter(value) {
        if (!value) return '-';
        const date = new Date(value);
        return date.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Inicializar Select2 para los selects
    $('.select2').select2();

    // Inicializar flatpickr en los campos de fecha con el mes en curso
    const fechaInicioInput = flatpickr('#fecha_inicio', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) // Primer día del mes actual
    });

    const fechaFinInput = flatpickr('#fecha_fin', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date() // Fecha actual como fecha de fin por defecto
    });

    // Regla personalizada para verificar que la fecha de fin sea mayor o igual a la fecha de inicio
    $.validator.addMethod(
        'greaterThan',
        function (value, element, param) {
            // Verificar si ambos campos de fecha tienen valores
            const startDate = $(param).val();
            if (!startDate || !value) {
                return true; // No validar si uno de los campos está vacío
            }

            // Comparar las fechas
            const start = new Date(startDate);
            const end = new Date(value);

            return end >= start;
        },
        'La fecha de fin debe ser mayor o igual a la fecha de inicio'
    );

    // Cargar sucursales y departamentos al cambiar cliente
    $('#id_cliente').on('change', function () {
        const clienteId = $(this).val();
        if (clienteId !== 'todos') {
            $.ajax({
                url: `${Server}sucursales/obtenerSucursalesPorCliente/${clienteId}`,
                method: 'GET',
                success: function (data) {
                    $('#id_sucursal').html('<option value="">Seleccionar Sucursal</option>');
                    data.forEach(sucursal => {
                        $('#id_sucursal').append(`<option value="${sucursal.id}">${sucursal.nombre}</option>`);
                    });
                    $('#id_sucursal').prop('disabled', false);
                }
            });
        } else {
            $('#id_sucursal, #id_departamento').html('<option value="">Seleccionar</option>').prop('disabled', true);
        }
    });

    // Cargar departamentos al cambiar sucursal
    $('#id_sucursal').on('change', function () {
        const sucursalId = $(this).val();
        if (sucursalId) {
            $.ajax({
                url: `${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
                method: 'GET',
                success: function (data) {
                    $('#id_departamento').html('<option value="">Seleccionar Departamento</option>');
                    data.forEach(departamento => {
                        $('#id_departamento').append(`<option value="${departamento.id}">${departamento.nombre}</option>`);
                    });
                    $('#id_departamento').prop('disabled', false);
                }
            });
        } else {
            $('#id_departamento').html('<option value="">Seleccionar Departamento</option>').prop('disabled', true);
        }
    });

    // Validación del formulario de filtros
    $('#formFiltros').validate({
        rules: {
            fecha_inicio: {
                required: true
            },
            fecha_fin: {
                required: true,
                greaterThan: '#fecha_inicio' // Aplicar la regla personalizada
            }
        },
        messages: {
            fecha_inicio: {
                required: 'La fecha de inicio es obligatoria'
            },
            fecha_fin: {
                required: 'La fecha de fin es obligatoria',
                greaterThan: 'La fecha de fin debe ser mayor o igual a la fecha de inicio'
            }
        }
    });

    // Filtrar las denuncias
    $('#btnFiltrar').on('click', function () {
        if ($('#formFiltros').valid()) {
            $tablaDenuncias.bootstrapTable('refreshOptions', { pageNumber: 1 });
        }
    });

    // Exportar las denuncias a CSV
    $('#btnExportar').on('click', function () {
        if ($('#formFiltros').valid()) {
            // Serializar los datos del formulario
            const formData = $('#formFiltros').serialize();

            // Hacer la llamada POST al backend
            $.ajax({
                url: `${Server}reportes/exportarCSV`,
                method: 'POST',
                data: formData,
                xhrFields: {
                    responseType: 'blob' // Para manejar la respuesta como archivo
                },
                success: function (data, status, xhr) {
                    // Obtener el nombre del archivo desde el encabezado Content-Disposition
                    const filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].trim();

                    // Crear un enlace de descarga temporal
                    const blob = new Blob([data], { type: 'text/csv' });
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();

                    // Limpiar el objeto URL para liberar memoria
                    window.URL.revokeObjectURL(link.href);
                },
                error: function () {
                    showToast('Error al exportar el CSV.', 'error');
                }
            });
        }
    });
});

// Definir eventos para las acciones de la tabla
window.operateEvents = {
    'click .remove': function (e, value, row, index) {
        eliminarDenuncia(row.id); // Llamar a la función de eliminación
    }
};

async function eliminarDenuncia(id) {
    const data = await confirm('¿Estás seguro de eliminar esta denuncia?', 'Eliminar esta denuncia es una acción permanente. Todos los comentarios, archivos adjuntos y seguimientos relacionados también serán eliminados de manera definitiva. Esta acción no se puede deshacer.');

    if (data.isConfirmed) {
        ajaxCall({
            url: `${Server}reportes/eliminarDenuncia/${id}`,
            method: 'POST',
            success: function (response) {
                $tablaDenuncias.bootstrapTable('refresh');
                showToast('¡Denuncia eliminada correctamente!', 'success');
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Ocurrió un error al eliminar la denuncia.';
                if (xhr.status === 409) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                }
                showToast(errorMessage, 'error');
            }
        });
    }
}

// Función para el formateador de la columna de acciones en la tabla
function operateFormatter(value, row, index) {
    return ['<a class="remove btn btn-danger btn-sm" href="javascript:void(0)" title="Eliminar">', '<i class="fa fa-trash"></i>', '</a>'].join('');
}
