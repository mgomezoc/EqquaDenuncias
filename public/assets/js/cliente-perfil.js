$(document).ready(function () {
    $('#formActualizarPerfil').validate({
        rules: {
            nombre_empresa: {
                required: true,
                minlength: 3
            },
            numero_identificacion: {
                required: true
            },
            correo_contacto: {
                required: true,
                email: true
            },
            telefono_contacto: {
                required: true
            },
            direccion: {
                required: true
            }
        },
        messages: {
            nombre_empresa: {
                required: 'Por favor ingrese el nombre de la empresa',
                minlength: 'El nombre debe tener al menos 3 caracteres'
            },
            numero_identificacion: {
                required: 'Por favor ingrese el número de identificación'
            },
            correo_contacto: {
                required: 'Por favor ingrese el correo de contacto',
                email: 'Por favor ingrese un correo electrónico válido'
            },
            telefono_contacto: {
                required: 'Por favor ingrese el teléfono de contacto'
            },
            direccion: {
                required: 'Por favor ingrese la dirección'
            }
        },
        submitHandler: function (form) {
            const formData = $(form).serialize();
            $.post(`${Server}cliente/perfil/actualizar`, formData)
                .done(function (response) {
                    showToast('Perfil actualizado correctamente', 'success');
                })
                .fail(function () {
                    showToast('Error al actualizar el perfil', 'error');
                });
        }
    });
});
