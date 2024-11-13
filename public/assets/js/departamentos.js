$(function () {
    let tplAccionesTabla;
    const $modalCrearDepartamento = $('#modalCrearDepartamento');
    const $formCrearDepartamento = $('#formCrearDepartamento');
    const $tablaDepartamentos = $('#tablaDepartamentos');
    const $idCliente = $('#id_cliente');
    const $idSucursal = $('#id_sucursal');
    const $esGeneral = $('#es_general');

    loadClientes();

    // Compilación de los templates de Handlebars
    tplAccionesTabla = $('#tplAccionesTabla').html();

    // Inicializar Select2 para el modal
    $('#id_cliente, #id_sucursal').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $modalCrearDepartamento
    });

    // Evento para habilitar/deshabilitar selección de cliente/sucursal y actualizar la validación
    $esGeneral.on('change', function () {
        const isGeneral = $esGeneral.is(':checked');

        // Deshabilitar y limpiar Sucursal si "Es general" está marcado
        $idSucursal.prop('disabled', isGeneral);

        if (isGeneral) {
            $idSucursal.val(null).trigger('change'); // Limpiar selección de sucursal

            // Ocultar mensajes de error de validación si existen
            $formCrearDepartamento.validate().element($idSucursal);
        } else {
            $idSucursal.prop('disabled', false);
        }
    });

    // Inicializar la tabla de departamentos
    $tablaDepartamentos.bootstrapTable({
        url: `${Server}departamentos/listar`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre', title: 'Nombre' },
            { field: 'cliente_nombre', title: 'Cliente' },
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

    // Función para manejar la tabla de acciones
    function operateFormatter(value, row, index) {
        return Handlebars.compile(tplAccionesTabla)(row);
    }

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

    // Cargar sucursales al seleccionar un cliente
    $idCliente.on('change', function (e, isChange) {
        if (!isChange) {
            const clienteId = $(this).val();
            loadSucursales(clienteId);
        }
    });

    // Función para cargar clientes
    function loadClientes() {
        $.ajax({
            url: `${Server}departamentos/listarClientes`,
            method: 'GET',
            success: function (data) {
                let options = '<option value="">Seleccione un cliente</option>';
                data.forEach(function (cliente) {
                    options += `<option value="${cliente.id}">${cliente.nombre_empresa}</option>`;
                });
                $idCliente.html(options).trigger('change');
            },
            error: function () {
                console.error('Error al cargar los clientes.');
            }
        });
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
                $modalCrearDepartamento.modal('show');
                $('#formCrearDepartamento input[name="id"]').val(data.id);
                $('#formCrearDepartamento input[name="nombre"]').val(data.nombre);
                $('#id_cliente').val(data.id_cliente).trigger('change', true);

                // Cargar sucursales una vez que el cliente está seleccionado
                loadSucursales(data.id_cliente, data.id_sucursal, data.id_sucursal);

                if (data.es_general == '1') {
                    $esGeneral.prop('checked', true);
                } else {
                    $esGeneral.prop('checked', false);
                }
            },
            error: function () {
                console.error('Error al obtener los datos del departamento.');
            }
        });
    });

    // Función para cargar las sucursales
    function loadSucursales(clienteId, id_sucursal = null, selected = null) {
        if (!clienteId) {
            return;
        }

        $.ajax({
            url: `${Server}departamentos/listarSucursales/${clienteId}`,
            method: 'GET',
            success: function (data) {
                let options = '<option value="">Seleccione una sucursal</option>';
                data.forEach(function (sucursal) {
                    // Si id_sucursal coincide, añadir "selected" al option
                    const selected = sucursal.id == id_sucursal ? 'selected' : '';
                    options += `<option value="${sucursal.id}" ${selected}>${sucursal.nombre}</option>`;
                });

                $('#id_sucursal').html(options).trigger('change');

                if (selected) {
                    $('#id_sucursal').val(selected).trigger('change');
                }
            },
            error: function () {
                console.error('Error al cargar las sucursales.');
            }
        });
    }

    // Validación y envío del formulario de crear/editar departamento
    $formCrearDepartamento.validate({
        rules: {
            nombre: { required: true },
            id_cliente: {
                required: function () {
                    return !$esGeneral.is(':checked'); // Solo obligatorio si "Es general" no está seleccionado
                }
            },
            id_sucursal: {
                required: function () {
                    return !$esGeneral.is(':checked'); // Solo obligatorio si "Es general" no está seleccionado
                }
            }
        },
        messages: {
            nombre: { required: 'Por favor ingrese el nombre del departamento' },
            id_cliente: { required: 'Por favor seleccione un cliente o marque "Es general"' },
            id_sucursal: { required: 'Por favor seleccione una sucursal o marque "Es general"' }
        },
        submitHandler: function (form) {
            const formData = $formCrearDepartamento.serializeObject();

            formData.es_general = $esGeneral.is(':checked') ? 1 : 0;

            loadingFormXHR($formCrearDepartamento, true);

            ajaxCall({
                url: `${Server}departamentos/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($formCrearDepartamento, false);
                    $modalCrearDepartamento.modal('hide');
                    $tablaDepartamentos.bootstrapTable('refresh');
                    showToast('¡Departamento guardado correctamente!', 'success');
                    $formCrearDepartamento[0].reset();
                    $formCrearDepartamento.find('.is-valid').removeClass('is-valid');
                },
                error: function (xhr) {
                    console.error('Error al guardar el departamento.');
                    console.error(xhr.responseText);
                    showToast('Error al guardar el departamento.', 'error');
                    loadingFormXHR($formCrearDepartamento, false);
                }
            });
        }
    });

    // Reseteo de formulario al cerrar el modal
    $modalCrearDepartamento.on('hidden.bs.modal', function () {
        $formCrearDepartamento[0].reset();
        $formCrearDepartamento.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $formCrearDepartamento.validate().resetForm();

        // Restablece los selects de Select2
        $idCliente.val(null).trigger('change'); // Resetear Select2 para Cliente
        $idSucursal.val(null).trigger('change'); // Resetear Select2 para Sucursal

        // Habilita nuevamente los selects en caso de que hayan sido deshabilitados
        $idCliente.prop('disabled', false);
        $idSucursal.prop('disabled', false);
    });

    $('#btnCrearDepartamento').on('click', function () {
        $('#formCrearDepartamento input[name="id"]').val(null);

        $modalCrearDepartamento.modal('show');
    });
});
