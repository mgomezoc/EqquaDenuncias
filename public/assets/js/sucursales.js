/***
 *
 * SUCURSALES
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaSucursales;
let $modalCrearSucursal;

const optionsClientes = clientes.map(cliente => ({
    id: cliente.id,
    name: cliente.nombre_empresa
}));

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearSucursal = $('#modalCrearSucursal');

    $modalCrearSucursal.find('.select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $modalCrearSucursal
    });

    $tablaSucursales = $('#tablaSucursales').bootstrapTable({
        url: `${Server}sucursales/listar`,
        columns: [
            {
                field: 'id',
                title: 'ID'
            },
            {
                field: 'nombre',
                title: 'Nombre'
            },
            {
                field: 'cliente_nombre',
                title: 'Cliente'
            },
            {
                field: 'direccion',
                title: 'Dirección'
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
            row.clientes = optionsClientes;
            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Inicializar select2 y validación para el formulario de edición
            $detail.find('select').select2();
            $detail.find('.formEditarSucursal').validate({
                rules: {
                    nombre: {
                        required: true
                    },
                    id_cliente: {
                        required: true
                    }
                },
                messages: {
                    nombre: {
                        required: 'Por favor ingrese el nombre de la sucursal'
                    },
                    id_cliente: {
                        required: 'Por favor seleccione un cliente'
                    }
                }
            });
        }
    });

    $('#formCrearSucursal').validate({
        rules: {
            nombre: {
                required: true
            },
            id_cliente: {
                required: true
            }
        },
        messages: {
            nombre: {
                required: 'Por favor ingrese el nombre de la sucursal'
            },
            id_cliente: {
                required: 'Por favor seleccione un cliente'
            }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}sucursales/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearSucursal.modal('hide');
                    $tablaSucursales.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la sucursal.', 'success');
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

    $(document).on('submit', '.formEditarSucursal', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) {
            return false;
        }

        const formData = $frm.serializeObject();

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}sucursales/guardar`,
            method: 'POST',
            data: formData,
            success: function (data) {
                loadingFormXHR($frm, false);
                $tablaSucursales.bootstrapTable('refresh');
                showToast('¡Listo!, se actualizó correctamente la sucursal.', 'success');
            },
            error: function (xhr) {
                loadingFormXHR($frm, false);
                if (xhr.status === 409) {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.message, 'error');
                }
            }
        });
    });

    $modalCrearSucursal.on('hidden.bs.modal', function () {
        const $form = $('#formCrearSucursal');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
        $form.find('.select2').val(null).trigger('change');
    });
});

window.operateEvents = {
    'click .remove': function (e, value, row, index) {
        eliminarSucursal(row.id);
    },
    'click .view-detail': function (e, value, row, index) {
        verDetalleSucursal(row.id);
    }
};

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}

async function eliminarSucursal(id) {
    const data = await confirm('¿Estás seguro de eliminar esta sucursal?');
    if (data.isConfirmed) {
        ajaxCall({
            url: `${Server}sucursales/eliminar/${id}`,
            method: 'POST',
            success: function (response) {
                $tablaSucursales.bootstrapTable('refresh');
                showToast('¡Sucursal eliminada correctamente!', 'success');
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Ocurrió un error al eliminar la sucursal.';
                showToast(errorMessage, 'error');
            }
        });
    }
}

function verDetalleSucursal(id) {
    // Implementación de la función para ver detalles completos de una sucursal en un modal o nueva vista.
}
