$(function () {
    const $tablaUsuarios = $('#tablaUsuarios').bootstrapTable({
        url: `${Server}cliente/usuarios/listar`,
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
            row.roles = [
                { id: 2, name: 'Agente' },
                { id: 3, name: 'Supervisor de Calidad' }
            ];
            const renderData = Handlebars.compile($('#tplDetalleTabla').html())(row);
            $detail.html(renderData);

            // Inicializar select2 en el formulario expandido
            $detail.find('select').select2();

            // Configuración de validación y envío AJAX para el formulario de edición
            const $form = $detail.find('.formEditarUsuario');
            $form.validate({
                rules: {
                    nombre_usuario: {
                        required: true,
                        minlength: 3,
                        remote: {
                            url: `${Server}cliente/usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                nombre_usuario: function () {
                                    return $form.find('[name="nombre_usuario"]').val();
                                },
                                id: function () {
                                    return $form.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    correo_electronico: {
                        required: true,
                        email: true,
                        remote: {
                            url: `${Server}cliente/usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                correo_electronico: function () {
                                    return $form.find('[name="correo_electronico"]').val();
                                },
                                id: function () {
                                    return $form.find('[name="id"]').val();
                                }
                            }
                        }
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
                    }
                },
                submitHandler: function (form) {
                    const $frm = $(form);
                    const formData = $frm.serializeObject();

                    loadingFormXHR($frm, true);

                    ajaxCall({
                        url: `${Server}cliente/usuarios/guardar`,
                        method: 'POST',
                        data: formData,
                        success: function (data) {
                            loadingFormXHR($frm, false);
                            $tablaUsuarios.bootstrapTable('refresh');
                            showToast('¡Usuario actualizado correctamente!', 'success');
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
        }
    });

    // Configuración de validación para el formulario de creación de usuario
    $('#formCrearUsuario').validate({
        rules: {
            nombre_usuario: {
                required: true,
                minlength: 3,
                remote: {
                    url: `${Server}cliente/usuarios/validarUnico`,
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
                    url: `${Server}cliente/usuarios/validarUnico`,
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
            },
            id_cliente: {
                required: function () {
                    // Ajusta esto si es necesario dependiendo del rol específico
                    const selectedRole = $('#formCrearUsuario [name="rol_id"]').val();
                    return selectedRole == 2 || selectedRole == 3; // Agente o Supervisor de Calidad
                }
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
                url: `${Server}cliente/usuarios/guardar`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $('#modalCrearUsuario').modal('hide');
                    $tablaUsuarios.bootstrapTable('refresh');
                    showToast('¡Usuario creado correctamente!', 'success');
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

    function operateFormatter() {
        return `<button class="btn btn-sm btn-danger remove">
                    <i class="fa fa-trash"></i>
                </button>`;
    }
});

// Elimina el usuario cuando se hace clic en el botón de eliminación
window.operateEvents = {
    'click .remove': function (e, value, row, index) {
        confirm('¿Estás seguro de eliminar este usuario?', 'Esta acción no se puede deshacer.').then(result => {
            // Verifica si el usuario hizo clic en "Continuar"
            if (result.isConfirmed) {
                ajaxCall({
                    url: `${Server}cliente/usuarios/eliminar/${row.id}`,
                    method: 'POST',
                    success: function () {
                        $('#tablaUsuarios').bootstrapTable('refresh');
                        showToast('¡Usuario eliminado correctamente!', 'success');
                    },
                    error: function () {
                        showToast('Error al eliminar el usuario.', 'error');
                    }
                });
            }
        });
    }
};
