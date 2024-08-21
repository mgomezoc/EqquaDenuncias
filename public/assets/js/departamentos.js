$(function () {
    let tplAccionesTabla;
    const $modalCrearDepartamento = $('#modalCrearDepartamento');
    const $tablaDepartamentos = $('#tablaDepartamentos');

    // Compilación de los templates de Handlebars
    tplAccionesTabla = $('#tplAccionesTabla').html();

    // Inicializar Select2 para el modal
    $('#id_cliente, #id_sucursal').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $modalCrearDepartamento
    });

    // Inicializar la tabla de departamentos
    $tablaDepartamentos.bootstrapTable({
        url: `${Server}departamentos/listar`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre', title: 'Nombre' },
            { field: 'sucursal_nombre', title: 'Sucursal' },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                clickToSelect: false,
                formatter: operateFormatter,
                events: window.operateEvents
            }
        ]
    });

    // Cargar clientes al abrir el modal
    $modalCrearDepartamento.on('shown.bs.modal', function () {
        loadClientes();
    });

    // Evento para cargar las sucursales al cambiar el cliente
    $('#id_cliente').on('change', function () {
        const clienteId = $(this).val();
        loadSucursales(clienteId);
    });

    // Función para cargar los clientes
    function loadClientes() {
        $.ajax({
            url: `${Server}departamentos/listarClientes`,
            method: 'GET',
            success: function (data) {
                let options = '<option value="">Seleccione un cliente</option>';
                data.forEach(function (cliente) {
                    options += `<option value="${cliente.id}">${cliente.nombre_empresa}</option>`;
                });
                $('#id_cliente').html(options).trigger('change');
            },
            error: function () {
                console.error('Error al cargar los clientes.');
            }
        });
    }

    // Función para cargar las sucursales
    function loadSucursales(clienteId) {
        $.ajax({
            url: `${Server}departamentos/listarSucursales/${clienteId}`,
            method: 'GET',
            success: function (data) {
                let options = '<option value="">Seleccione una sucursal</option>';
                data.forEach(function (sucursal) {
                    options += `<option value="${sucursal.id}">${sucursal.nombre}</option>`;
                });
                $('#id_sucursal').html(options).trigger('change');
            },
            error: function () {
                console.error('Error al cargar las sucursales.');
            }
        });
    }

    // Función para manejar la tabla de acciones
    function operateFormatter(value, row, index) {
        return Handlebars.compile(tplAccionesTabla)(row);
    }

    // Evento para abrir el modal y editar un departamento
    $(document).on('click', '.edit', function () {
        const $btn = $(this);
        const id = $btn.data('id');

        // Obtener datos del departamento
        $.ajax({
            url: `${Server}departamentos/obtener/${id}`,
            method: 'GET',
            success: function (data) {
                $('#formCrearDepartamento input[name="id"]').val(data.id);
                $('#formCrearDepartamento input[name="nombre"]').val(data.nombre);
                $('#id_cliente').val(data.id_cliente).trigger('change');

                // Cargar sucursales una vez que el cliente está seleccionado
                loadSucursales(data.id_cliente);

                $('#id_sucursal').val(data.id_sucursal).trigger('change');
                $modalCrearDepartamento.modal('show');
            },
            error: function () {
                console.error('Error al obtener los datos del departamento.');
            }
        });
    });

    // Evento para eliminar un departamento
    $(document).on('click', '.remove', async function () {
        const $btn = $(this);
        const id = $btn.data('id');

        const result = await confirm('Confirmación', '¿Está seguro de eliminar este departamento?');
        if (result.isConfirmed) {
            eliminarDepartamento(id);
        }
    });

    // Función para eliminar un departamento
    function eliminarDepartamento(id) {
        ajaxCall({
            url: `${Server}departamentos/eliminar/${id}`,
            method: 'POST',
            success: function () {
                $tablaDepartamentos.bootstrapTable('refresh');
                showToast('¡Departamento eliminado correctamente!', 'success');
            },
            error: function (xhr) {
                console.error('Error al eliminar el departamento.');
                console.error(xhr.responseText);
                showToast('Error al eliminar el departamento.', 'error');
            }
        });
    }

    // Validación y envío del formulario de crear/editar departamento
    $('#formCrearDepartamento').validate({
        rules: {
            nombre: { required: true },
            id_cliente: { required: true },
            id_sucursal: { required: true }
        },
        messages: {
            nombre: { required: 'Por favor ingrese el nombre del departamento' },
            id_cliente: { required: 'Por favor seleccione un cliente' },
            id_sucursal: { required: 'Por favor seleccione una sucursal' }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}departamentos/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearDepartamento.modal('hide');
                    $tablaDepartamentos.bootstrapTable('refresh');
                    showToast('¡Departamento guardado correctamente!', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                },
                error: function (xhr) {
                    console.error('Error al guardar el departamento.');
                    console.error(xhr.responseText);
                    showToast('Error al guardar el departamento.', 'error');
                    loadingFormXHR($frm, false);
                }
            });
        }
    });

    // Reseteo de formulario al cerrar el modal
    $modalCrearDepartamento.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDepartamento');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });
});
