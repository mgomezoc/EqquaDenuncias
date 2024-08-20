$(function () {
    // Compilación de los templates de Handlebars
    tplAccionesTabla = $('#tplAccionesTabla').html();
    $modalCrearDepartamento = $('#modalCrearDepartamento');

    $('#id_sucursal').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDepartamento')
    });

    // Inicializar la tabla de Departamentos
    $tablaDepartamentos = $('#tablaDepartamentos').bootstrapTable({
        url: `${Server}departamentos/listarDepartamentos`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre', title: 'Nombre' },
            { field: 'sucursal', title: 'Sucursal' },
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

    // Validación y envío del formulario de crear/editar departamento
    $('#formCrearDepartamento').validate({
        rules: {
            nombre: { required: true },
            id_sucursal: { required: true }
        },
        messages: {
            nombre: { required: 'Por favor ingrese el nombre del departamento' },
            id_sucursal: { required: 'Por favor seleccione una sucursal' }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();
            const isEdit = formData.id ? true : false;

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}departamentos/guardarDepartamento`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearDepartamento.modal('hide');
                    $tablaDepartamentos.bootstrapTable('refresh');
                    showToast(isEdit ? '¡Departamento actualizado correctamente!' : '¡Departamento creado correctamente!', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                },
                error: function (xhr) {
                    loadingFormXHR($frm, false);
                    handleError(xhr, 'Error al procesar el departamento.');
                }
            });
        }
    });

    // Reseteo de formularios al cerrar el modal
    $modalCrearDepartamento.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDepartamento');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });

    // Evento para editar departamentos
    $(document).on('click', '.edit', function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const nombre = $btn.data('nombre');
        const sucursal = $btn.data('sucursal');

        $('#formCrearDepartamento input[name="id"]').val(id);
        $('#formCrearDepartamento input[name="nombre"]').val(nombre);
        $('#id_sucursal').val(sucursal).trigger('change');
        $modalCrearDepartamento.modal('show');
    });

    // Evento para eliminar departamentos
    $(document).on('click', '.remove', async function () {
        const $btn = $(this);
        const id = $btn.data('id');

        const result = await confirm('Confirmación', '¿Está seguro de eliminar este departamento?');
        if (result.isConfirmed) {
            ajaxCall({
                url: `${Server}departamentos/eliminarDepartamento/${id}`,
                method: 'POST',
                success: function () {
                    $tablaDepartamentos.bootstrapTable('refresh');
                    showToast('¡Departamento eliminado correctamente!', 'success');
                },
                error: function (xhr) {
                    handleError(xhr, 'Error al eliminar el departamento.');
                }
            });
        }
    });

    // Cargar sucursales al abrir el modal de crear/editar departamento
    $modalCrearDepartamento.on('shown.bs.modal', function () {
        loadSucursales();
    });

    // Función para cargar sucursales en el select
    function loadSucursales() {
        $.ajax({
            url: `${Server}departamentos/listarSucursales`,
            method: 'GET',
            success: function (data) {
                let options = '<option value="">Seleccione una sucursal</option>';
                data.forEach(function (sucursal) {
                    options += `<option value="${sucursal.id}">${sucursal.nombre}</option>`;
                });
                $('#id_sucursal').html(options).trigger('change');
            },
            error: function () {
                console.error('Error loading branches.');
            }
        });
    }

    // Función para manejar la tabla de acciones
    function operateFormatter(value, row, index) {
        return Handlebars.compile(tplAccionesTabla)(row);
    }
});
