/***
 *
 * DENUNCIAS
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearDenuncia = $('#modalCrearDenuncia');

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
                field: 'categoria',
                title: 'Categoría'
            },
            {
                field: 'subcategoria',
                title: 'Subcategoría'
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
            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Inicializar select2 y validación para el formulario de edición
            $detail.find('select').select2();
            $detail.find('.formEditarDenuncia').validate({
                rules: {
                    folio: {
                        required: true
                    },
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
                    folio: {
                        required: 'Por favor ingrese el folio'
                    },
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
        }
    });

    $('#formCrearDenuncia').validate({
        rules: {
            folio: {
                required: true
            },
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
            }
        },
        messages: {
            folio: {
                required: 'Por favor ingrese el folio'
            },
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

    $(document).on('submit', '.formEditarDenuncia', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) {
            return false;
        }

        const formData = $frm.serializeObject();

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}denuncias/guardar`,
            method: 'POST',
            data: formData,
            success: function (data) {
                loadingFormXHR($frm, false);
                $tablaDenuncias.bootstrapTable('refresh');
                showToast('¡Listo!, se actualizó correctamente la denuncia.', 'success');
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

    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });
});

window.operateEvents = {
    'click .edit': function (e, value, row, index) {
        editarDenuncia(row.id);
    },
    'click .remove': function (e, value, row, index) {
        eliminarDenuncia(row.id);
    },
    'click .view-detail': function (e, value, row, index) {
        verDetalleDenuncia(row.id);
    },
    'click .change-status': function (e, value, row, index) {
        cambiarEstadoDenuncia(row.id);
    }
};

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
