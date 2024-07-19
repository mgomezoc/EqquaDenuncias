/***
 *
 * USUARIOS
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaUsuarios;
let $modalCrearUsuario;
let optionsRoles;

$(function () {
    optionsRoles = roles.map(rol => ({
        id: rol.id,
        name: rol.nombre
    }));

    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearUsuario = $('#modalCrearUsuario');

    $tablaUsuarios = $('#tablaUsuarios').bootstrapTable({
        url: `${Server}usuarios/listar`,
        columns: [
            {
                field: 'id',
                title: 'ID'
            },
            {
                field: 'nombre_usuario',
                title: 'Nombre'
            },
            {
                field: 'correo_electronico',
                title: 'Correo'
            },
            {
                field: 'ultima_conexion',
                title: 'Última Conexión'
            },
            {
                field: 'rol_nombre',
                title: 'Rol'
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
            row.roles = optionsRoles;
            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Inicializar select2 y validación para el formulario de edición
            $detail.find('select').select2();
            $detail.find('.formEditarUsuario').validate({
                rules: {
                    nombre_usuario: {
                        required: true,
                        minlength: 3,
                        remote: {
                            url: `${Server}usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                nombre_usuario: function () {
                                    return $detail.find('[name="nombre_usuario"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    correo_electronico: {
                        required: true,
                        email: true,
                        remote: {
                            url: `${Server}usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                correo_electronico: function () {
                                    return $detail.find('[name="correo_electronico"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    contrasena: {
                        minlength: 5
                    },
                    rol_id: {
                        required: true
                    }
                },
                messages: {
                    nombre_usuario: {
                        required: 'Por favor ingrese el nombre de usuario',
                        minlength: 'El nombre de usuario debe tener al menos 3 caracteres',
                        remote: 'El nombre de usuario ya está en uso'
                    },
                    correo_electronico: {
                        required: 'Por favor ingrese el correo electrónico',
                        email: 'Por favor ingrese un correo electrónico válido',
                        remote: 'El correo electrónico ya está en uso'
                    },
                    contrasena: {
                        minlength: 'La contraseña debe tener al menos 5 caracteres'
                    },
                    rol_id: {
                        required: 'Por favor seleccione un rol'
                    }
                }
            });
        }
    });

    $('#formCrearUsuario').validate({
        rules: {
            nombre_usuario: {
                required: true,
                minlength: 3,
                remote: {
                    url: `${Server}usuarios/validarUnico`,
                    type: 'post',
                    data: {
                        nombre_usuario: function () {
                            return $('#formCrearUsuario [name="nombre_usuario"]').val();
                        }
                    }
                }
            },
            correo_electronico: {
                required: true,
                email: true,
                remote: {
                    url: `${Server}usuarios/validarUnico`,
                    type: 'post',
                    data: {
                        correo_electronico: function () {
                            return $('#formCrearUsuario [name="correo_electronico"]').val();
                        }
                    }
                }
            },
            contrasena: {
                required: true,
                minlength: 5
            },
            rol_id: {
                required: true
            }
        },
        messages: {
            nombre_usuario: {
                required: 'Por favor ingrese el nombre de usuario',
                minlength: 'El nombre de usuario debe tener al menos 3 caracteres',
                remote: 'El nombre de usuario ya está en uso'
            },
            correo_electronico: {
                required: 'Por favor ingrese el correo electrónico',
                email: 'Por favor ingrese un correo electrónico válido',
                remote: 'El correo electrónico ya está en uso'
            },
            contrasena: {
                required: 'Por favor ingrese la contraseña',
                minlength: 'La contraseña debe tener al menos 5 caracteres'
            },
            rol_id: {
                required: 'Por favor seleccione un rol'
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
                url: `${Server}usuarios/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearUsuario.modal('hide');
                    $tablaUsuarios.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente el usuario.', 'success');
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

    $(document).on('submit', '.formEditarUsuario', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) {
            return false;
        }

        const formData = $frm.serializeObject();

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}usuarios/guardar`,
            method: 'POST',
            data: formData,
            success: function (data) {
                loadingFormXHR($frm, false);
                $tablaUsuarios.bootstrapTable('refresh');
                showToast('¡Listo!, se actualizó correctamente el usuario.', 'success');
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
});

window.operateEvents = {
    'click .edit': function (e, value, row, index) {
        editarUsuario(row.id);
    },
    'click .remove': function (e, value, row, index) {
        eliminarUsuario(row.id);
    }
};

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}

async function eliminarUsuario(id) {
    const data = await confirm('¿Estás seguro de eliminar este usuario?');
    if (data.isConfirmed) {
        ajaxCall({
            url: `${Server}usuarios/eliminar/${id}`,
            method: 'POST',
            success: function () {
                $tablaUsuarios.bootstrapTable('refresh');
                showToast('¡Usuario eliminado correctamente!', 'success');
            }
        });
    }
}

$modalCrearUsuario.on('hidden.bs.modal', function () {
    const $form = $('#formCrearUsuario');
    $form[0].reset();
    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    $form.validate().resetForm();
});
