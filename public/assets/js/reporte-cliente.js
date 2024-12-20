$(document).ready(function () {
    let $formFiltros = $('#formFiltros');

    // Inicialización de la tabla BootstrapTable
    const $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}reportes/listarParaCliente`,
        method: 'post',
        pagination: true,
        sidePagination: 'server',
        pageSize: 50,
        pageList: [50, 100, 150, 200],
        search: true,
        searchAlign: 'left',
        showRefresh: true,
        toolbar: '#toolbar',
        locale: 'es-MX',
        columns: [
            { field: 'fecha_hora_reporte', title: 'Fecha Reporte', sortable: true },
            { field: 'estado_nombre', title: 'Estatus', sortable: true },
            { field: 'folio', title: 'Folio', sortable: true },
            { field: 'sucursal_nombre', title: 'Sucursal', sortable: true },
            { field: 'departamento_nombre', title: 'Departamento', sortable: true },
            { field: 'categoria_nombre', title: 'Categoría', sortable: true },
            { field: 'subcategoria_nombre', title: 'SubCategoría', sortable: true },
            { field: 'fecha_incidente', title: 'Fecha Incidente', sortable: true, formatter: dateFormatter },
            { field: 'medio_recepcion', title: 'Medio Recepción', sortable: true }
        ],
        queryParams: function (params) {
            const filtros = $formFiltros.serializeObject();
            return { ...params, ...filtros };
        }
    });

    // Formateador de fechas para las columnas de la tabla
    function dateFormatter(value) {
        if (!value) return '-';
        const date = new Date(value);
        return date.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Inicializar Select2 para selects con búsqueda
    $('.select2').select2();

    // Inicializar flatpickr en los campos de fecha con el mes en curso
    flatpickr('#fecha_inicio', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) // Primer día del mes actual
    });

    flatpickr('#fecha_fin', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date() // Fecha actual como fecha de fin por defecto
    });

    // Cargar sucursales del cliente al cargar la página
    cargarSucursales();

    function cargarSucursales() {
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
    }

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

    // Resetear formulario y refrescar tabla
    $('#btnReset').on('click', function () {
        // Resetear los valores del formulario
        $formFiltros[0].reset();

        // Resetear los selects de Select2
        $('.select2').val(null).trigger('change');

        // Resetear los departamentos y deshabilitar el select
        $('#id_departamento').html('<option value="">Seleccionar Departamento</option>').prop('disabled', true);

        // Refrescar la tabla con los valores predeterminados
        $tablaDenuncias.bootstrapTable('refresh', { query: {} });
    });

    // Filtrar las denuncias al hacer clic en el botón
    $('#btnFiltrar').on('click', function () {
        if ($formFiltros.valid()) {
            $tablaDenuncias.bootstrapTable('refresh');
        }
    });

    // Exportar las denuncias a CSV
    $('#btnExportar').on('click', function () {
        if ($formFiltros.valid()) {
            const formData = $formFiltros.serialize();

            $.ajax({
                url: `${Server}reportes/exportarCSV`,
                method: 'POST',
                data: formData,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data, status, xhr) {
                    const filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].trim();
                    const blob = new Blob([data], { type: 'text/csv' });
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();
                    window.URL.revokeObjectURL(link.href);
                },
                error: function () {
                    showToast('Error al exportar el CSV.', 'error');
                }
            });
        }
    });
});
