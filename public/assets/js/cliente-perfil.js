/***
 *
 * PERFIL CLIENTE
 *
 */

Dropzone.autoDiscover = false;

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

    // Inicializar Dropzones para logo y banner
    initializeDropzone('dropzoneLogo', 'logo');
    initializeDropzone('dropzoneBanner', 'banner');
});

function initializeDropzone(elementId, fieldName) {
    new Dropzone(`#${elementId}`, {
        url: `${Server}clientes/subirImagen`,
        maxFiles: 1,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra una imagen aquí para subirla',
        dictRemoveFile: 'Eliminar imagen',
        init: function () {
            this.on('success', function (file, response) {
                $(`#formActualizarPerfil`).append(`<input type="hidden" name="${fieldName}" value="assets/images/clientes/${response.filename}">`);
            });
            this.on('removedfile', function (file) {
                $(`input[name="${fieldName}"]`).remove();
            });
        }
    });
}
