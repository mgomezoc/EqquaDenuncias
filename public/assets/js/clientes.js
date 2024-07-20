/***
 *
 * CLIENTES
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaClientes;
let $modalCrearCliente;

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearCliente = $('#modalCrearCliente');

    $tablaClientes = $('#tablaClientes').bootstrapTable({
        url: `${Server}clientes/listar`,
        columns: [
            {
                field: 'id',
                title: 'ID'
            },
            {
                field: 'nombre_empresa',
                title: 'Nombre Empresa'
            },
            {
                field: 'correo_contacto',
                title: 'Correo Contacto'
            },
            {
                field: 'telefono_contacto',
                title: 'Teléfono Contacto'
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
            $detail.find('.formEditarCliente').validate({
                rules: {
                    nombre_empresa: {
                        required: true,
                        minlength: 3,
                        remote: {
                            url: `${Server}clientes/validarUnico`,
                            type: 'post',
                            data: {
                                nombre_empresa: function () {
                                    return $detail.find('[name="nombre_empresa"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    numero_identificacion: {
                        required: true
                    },
                    correo_contacto: {
                        required: true,
                        email: true,
                        remote: {
                            url: `${Server}clientes/validarUnico`,
                            type: 'post',
                            data: {
                                correo_contacto: function () {
                                    return $detail.find('[name="correo_contacto"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    telefono_contacto: {
                        required: true
                    },
                    direccion: {
                        required: true
                    },
                    slug: {
                        required: true
                    }
                },
                messages: {
                    nombre_empresa: {
                        required: 'Por favor ingrese el nombre de la empresa',
                        minlength: 'El nombre de la empresa debe tener al menos 3 caracteres',
                        remote: 'El nombre de la empresa ya está en uso'
                    },
                    numero_identificacion: {
                        required: 'Por favor ingrese el número de identificación'
                    },
                    correo_contacto: {
                        required: 'Por favor ingrese el correo de contacto',
                        email: 'Por favor ingrese un correo electrónico válido',
                        remote: 'El correo de contacto ya está en uso'
                    },
                    telefono_contacto: {
                        required: 'Por favor ingrese el teléfono de contacto'
                    },
                    direccion: {
                        required: 'Por favor ingrese la dirección'
                    },
                    slug: {
                        required: 'Por favor ingrese el slug'
                    }
                }
            });
        }
    });

    $('#formCrearCliente').validate({
        rules: {
            nombre_empresa: {
                required: true,
                minlength: 3,
                remote: {
                    url: `${Server}clientes/validarUnico`,
                    type: 'post',
                    data: {
                        nombre_empresa: function () {
                            return $('#formCrearCliente [name="nombre_empresa"]').val();
                        }
                    }
                }
            },
            numero_identificacion: {
                required: true
            },
            correo_contacto: {
                required: true,
                email: true,
                remote: {
                    url: `${Server}clientes/validarUnico`,
                    type: 'post',
                    data: {
                        correo_contacto: function () {
                            return $('#formCrearCliente [name="correo_contacto"]').val();
                        }
                    }
                }
            },
            telefono_contacto: {
                required: true
            },
            direccion: {
                required: true
            },
            slug: {
                required: true
            }
        },
        messages: {
            nombre_empresa: {
                required: 'Por favor ingrese el nombre de la empresa',
                minlength: 'El nombre de la empresa debe tener al menos 3 caracteres',
                remote: 'El nombre de la empresa ya está en uso'
            },
            numero_identificacion: {
                required: 'Por favor ingrese el número de identificación'
            },
            correo_contacto: {
                required: 'Por favor ingrese el correo de contacto',
                email: 'Por favor ingrese un correo electrónico válido',
                remote: 'El correo de contacto ya está en uso'
            },
            telefono_contacto: {
                required: 'Por favor ingrese el teléfono de contacto'
            },
            direccion: {
                required: 'Por favor ingrese la dirección'
            },
            slug: {
                required: 'Por favor ingrese el slug'
            }
        },
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            if (element.prop('type') === 'checkbox') {
                error.insertAfter(element.parent('label'));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}clientes/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearCliente.modal('hide');
                    $tablaClientes.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente el cliente.', 'success');
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

    $(document).on('submit', '.formEditarCliente', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) {
            return false;
        }

        const formData = $frm.serializeObject();

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}clientes/guardar`,
            method: 'POST',
            data: formData,
            success: function (data) {
                loadingFormXHR($frm, false);
                $tablaClientes.bootstrapTable('refresh');
                showToast('¡Listo!, se actualizó correctamente el cliente.', 'success');
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

    $modalCrearCliente.on('hidden.bs.modal', function () {
        const $form = $('#formCrearCliente');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });
});

window.operateEvents = {
    'click .edit': function (e, value, row, index) {
        editarCliente(row.id);
    },
    'click .remove': function (e, value, row, index) {
        eliminarCliente(row.id);
    }
};

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}

async function eliminarCliente(id) {
    const data = await confirm('¿Estás seguro de eliminar este cliente?');
    if (data.isConfirmed) {
        ajaxCall({
            url: `${Server}clientes/eliminar/${id}`,
            method: 'POST',
            success: function () {
                $tablaClientes.bootstrapTable('refresh');
                showToast('¡Cliente eliminado correctamente!', 'success');
            }
        });
    }
}
