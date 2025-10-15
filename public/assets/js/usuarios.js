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
let optionsClientes;

Handlebars.registerHelper('checked', function (value) {
    return value ? 'xxxx' : 'xxxx';
});

$(function () {
    optionsRoles = roles.map(rol => ({
        id: rol.id,
        name: rol.nombre,
        text: rol.nombre
    }));

    optionsClientes = clientes.map(cliente => ({
        id: cliente.id,
        name: cliente.nombre_empresa
    }));

    optionsRoles = optionsRoles.filter(role => role.name !== 'Denunciante');

    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearUsuario = $('#modalCrearUsuario');

    // Selects del modal Crear
    $('#rol_id').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $modalCrearUsuario,
        data: optionsRoles
    });

    $('#id_cliente').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $modalCrearUsuario
    });

    $('#tipos_denunciante').select2({
        placeholder: 'Seleccione uno o varios',
        allowClear: true,
        dropdownParent: $modalCrearUsuario
    });

    $('#rol_id').on('change', function () {
        handleRoleChange($(this).val(), '#clienteContainer', '#id_cliente', '#soloLecturaContainer', '#tiposDenuncianteContainer', '#tipos_denunciante');
    });

    $tablaUsuarios = $('#tablaUsuarios').bootstrapTable({
        url: `${Server}usuarios/listar`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre_usuario', title: 'Nombre' },
            { field: 'correo_electronico', title: 'Correo' },
            { field: 'ultima_conexion', title: 'Última Conexión' },
            { field: 'rol_nombre', title: 'Rol' },
            { field: 'cliente_nombre', title: 'Cliente' },
            {
                field: 'recibe_notificaciones',
                title: 'Notificaciones',
                formatter: function (value) {
                    return value === '1' ? 'Sí' : 'No';
                }
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
            row.clientes = optionsClientes;
            row.recibe_notificaciones_checked = row.recibe_notificaciones == 1 ? 'checked' : '';
            row.solo_lectura_checked = row.solo_lectura == 1 ? 'checked' : '';

            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Selects de edición
            $detail.find(`#rol_id-${row.id}`).select2({ dropdownParent: $detail });
            $detail.find(`#id_cliente-${row.id}`).select2({ dropdownParent: $detail });
            $detail.find(`#tipos_denunciante-${row.id}`).select2({
                placeholder: 'Seleccione uno o varios',
                allowClear: true,
                dropdownParent: $detail
            });

            const rolSelect = $detail.find('[name="rol_id"]');
            rolSelect.on('change', function () {
                handleRoleChange($(this).val(), '#clienteContainer-' + row.id, '[name="id_cliente"]', '#soloLecturaContainer-' + row.id, '#tiposDenuncianteContainer-' + row.id, '#tipos_denunciante-' + row.id);
            });
            rolSelect.trigger('change');

            // Precarga de permisos por tipo_denunciante cuando es Cliente
            if (row.rol_id == 4) {
                $.getJSON(`${Server}usuarios/${row.id}/tipos-denunciante`, function (tipos) {
                    const $sel = $detail.find('#tipos_denunciante-' + row.id);
                    $sel.val(tipos).trigger('change'); // vacío => ver todos
                });
            }

            // Validación
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
                    contrasena: { minlength: 5 },
                    rol_id: { required: true },
                    id_cliente: {
                        required: function () {
                            const v = $detail.find('[name="rol_id"]').val();
                            return v == 4 || v == 5;
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
                    contrasena: { minlength: 'La contraseña debe tener al menos 5 caracteres' },
                    rol_id: { required: 'Por favor seleccione un rol' },
                    id_cliente: { required: 'Por favor seleccione un cliente' }
                }
            });

            // Checkbox notificaciones (edición)
            const $checkbox = $detail.find(`#recibe_notificaciones-${row.id}`);
            const $correoContainer = $detail.find(`#correoNotificacionesContainer-${row.id}`);
            $checkbox.on('change', function () {
                if ($(this).is(':checked')) {
                    $correoContainer.show();
                } else {
                    $correoContainer.hide();
                    $correoContainer.find('input').val('');
                }
            });
            $checkbox.trigger('change');
        }
    });

    // Checkbox notificaciones (crear)
    $('#recibe_notificaciones').on('change', function () {
        if ($(this).is(':checked')) {
            $('#correoNotificacionesContainer').show();
        } else {
            $('#correoNotificacionesContainer').hide();
            $('#correo_notificaciones').val('');
        }
    });

    // Validación crear
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
            rol_id: { required: true },
            id_cliente: {
                required: function () {
                    const selectedRole = $('#formCrearUsuario [name="rol_id"]').val();
                    return selectedRole == 4 || selectedRole == 5;
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
            rol_id: { required: 'Por favor seleccione un rol' },
            id_cliente: { required: 'Por favor seleccione un cliente' }
        },
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            if (element.prop('type') === 'checkbox') {
                error.insertAfter(element.parent('label'));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
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
                success: function () {
                    loadingFormXHR($frm, false);
                    $modalCrearUsuario.modal('hide');
                    $tablaUsuarios.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente el usuario.', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                    // Reset selects y contenedores
                    $('#tipos_denunciante').val(null).trigger('change');
                    $('#tiposDenuncianteContainer').hide();
                    $('#clienteContainer').hide();
                    $('#soloLecturaContainer').hide();
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
        if (!$frm.valid()) return false;

        const formData = $frm.serializeObject();

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}usuarios/guardar`,
            method: 'POST',
            data: formData,
            success: function () {
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

    $modalCrearUsuario.on('hidden.bs.modal', function () {
        const $form = $('#formCrearUsuario');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
        // Limpia selects múltiples
        $('#tipos_denunciante').val(null).trigger('change');
    });
});

window.operateEvents = {
    'click .edit': function (e, value, row) {
        editarUsuario(row.id);
    },
    'click .remove': function (e, value, row) {
        eliminarUsuario(row.id);
    }
};

function operateFormatter(value, row) {
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

/**
 * Muestra/oculta cliente, solo lectura y el multiselect de tipos_denunciante según el rol.
 * @param {number} rolId
 * @param {string} clienteContainerSelector
 * @param {string} clienteFieldSelector
 * @param {string} soloLecturaContainerSelector
 * @param {string} tiposContainerSelector
 * @param {string} tiposSelectSelector
 */
function handleRoleChange(rolId, clienteContainerSelector, clienteFieldSelector, soloLecturaContainerSelector, tiposContainerSelector, tiposSelectSelector) {
    if (rolId == 4) {
        $(clienteContainerSelector).show();
        $(clienteFieldSelector).prop('required', true);
        $(soloLecturaContainerSelector).show();
        if (tiposContainerSelector) $(tiposContainerSelector).show();
    } else {
        $(clienteContainerSelector).hide();
        $(clienteFieldSelector).prop('required', false);
        $(soloLecturaContainerSelector).hide().find('input').prop('checked', false);
        if (tiposContainerSelector) $(tiposContainerSelector).hide();
        if (tiposSelectSelector) $(tiposSelectSelector).val(null).trigger('change');
    }
}
