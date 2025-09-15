/***
 *
 * CLIENTES
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaClientes;
let $modalCrearCliente;
let dropzones = {};

Dropzone.autoDiscover = false; // Desactivar la autodetección de Dropzone

// Añadir la regla personalizada 'regex'
$.validator.addMethod(
    'regex',
    function (value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    'Por favor, ingrese un valor válido.'
);

// Mapea la política a badge/etiqueta
function politicaFormatter(value) {
    const v = Number(value);
    if (v === 1) return '<span class="badge text-bg-success">Forzar anónimas</span>';
    if (v === 2) return '<span class="badge text-bg-warning">Forzar identificadas</span>';
    return '<span class="badge text-bg-secondary">Opcional</span>';
}

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearCliente = $('#modalCrearCliente');

    $tablaClientes = $('#tablaClientes').bootstrapTable({
        url: `${Server}clientes/listar`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre_empresa', title: 'Nombre Empresa' },
            { field: 'correo_contacto', title: 'Correo Contacto' },
            { field: 'telefono_contacto', title: 'Teléfono Contacto' },
            { field: 'politica_anonimato', title: 'Política de Anonimato', formatter: politicaFormatter },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                clickToSelect: false,
                formatter: operateFormatter,
                events: window.operateEvents,
                visible: rol == 'ADMIN'
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Setear valor del select de política
            $detail
                .find('[name="politica_anonimato"]')
                .val(row.politica_anonimato ?? 0)
                .trigger('change');

            // Inicializar Dropzones
            initializeDropzone(`dropzoneLogo-${row.id}`, 'logo', row.id);
            initializeDropzone(`dropzoneBanner-${row.id}`, 'banner', row.id);

            // Selects (si alguno usa select2)
            $detail.find('select').select2();

            // Validación:
            // - ADMIN: validación completa
            // - CLIENTE: solo permitir enviar política (no exigir otros campos)
            const isAdmin = rol === 'ADMIN';

            const $frm = $detail.find('.formEditarCliente');

            if (isAdmin) {
                $frm.validate({
                    rules: {
                        nombre_empresa: {
                            required: true,
                            minlength: 3,
                            remote: {
                                url: `${Server}clientes/validarUnico`,
                                type: 'post',
                                data: {
                                    nombre_empresa: function () {
                                        return $frm.find('[name="nombre_empresa"]').val();
                                    },
                                    id: function () {
                                        return $frm.find('[name="id"]').val();
                                    }
                                }
                            }
                        },
                        correo_contacto: {
                            required: true,
                            email: true,
                            remote: {
                                url: `${Server}clientes/validarUnico`,
                                type: 'post',
                                data: {
                                    correo_contacto: function () {
                                        return $frm.find('[name="correo_contacto"]').val();
                                    },
                                    id: function () {
                                        return $frm.find('[name="id"]').val();
                                    }
                                }
                            }
                        },
                        telefono_contacto: { required: true },
                        direccion: { required: true },
                        slug: {
                            required: true,
                            regex: /^[a-zA-Z0-9-]+$/,
                            remote: {
                                url: `${Server}clientes/validarUnico`,
                                type: 'post',
                                data: {
                                    slug: function () {
                                        return $frm.find('[name="slug"]').val();
                                    },
                                    id: function () {
                                        return $frm.find('[name="id"]').val();
                                    }
                                }
                            }
                        }
                    },
                    messages: {
                        nombre_empresa: {
                            required: 'Por favor ingrese el nombre de la empresa',
                            minlength: 'El nombre de la empresa debe tener al menos 3 caracteres',
                            remote: 'El nombre de la empresa ya está en uso'
                        },
                        correo_contacto: {
                            required: 'Por favor ingrese el correo de contacto',
                            email: 'Por favor ingrese un correo electrónico válido',
                            remote: 'El correo de contacto ya está en uso'
                        },
                        telefono_contacto: { required: 'Por favor ingrese el teléfono de contacto' },
                        direccion: { required: 'Por favor ingrese la dirección' },
                        slug: {
                            required: 'Por favor ingrese el slug',
                            regex: 'El slug solo puede contener letras, números y guiones',
                            remote: 'El slug ya está en uso'
                        }
                    }
                });
            } else {
                // Validación mínima para CLIENTE (permite enviar sin exigir otros campos)
                $frm.validate({
                    rules: {
                        politica_anonimato: {
                            required: true
                        }
                    }
                });
            }
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
                required: true,
                remote: {
                    url: `${Server}clientes/validarUnico`,
                    type: 'post',
                    data: {
                        numero_identificacion: function () {
                            return $('#formCrearCliente [name="numero_identificacion"]').val();
                        }
                    }
                }
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
            telefono_contacto: { required: true },
            direccion: { required: true },
            slug: {
                required: true,
                regex: /^[a-zA-Z0-9-]+$/,
                remote: {
                    url: `${Server}clientes/validarUnico`,
                    type: 'post',
                    data: {
                        slug: function () {
                            return $('#formCrearCliente [name="slug"]').val();
                        }
                    }
                }
            },
            politica_anonimato: { required: true }
        },
        messages: {
            nombre_empresa: {
                required: 'Por favor ingrese el nombre de la empresa',
                minlength: 'El nombre de la empresa debe tener al menos 3 caracteres',
                remote: 'El nombre de la empresa ya está en uso'
            },
            numero_identificacion: {
                required: 'Por favor ingrese el número de identificación',
                remote: 'El número de identificación ya está en uso'
            },
            correo_contacto: {
                required: 'Por favor ingrese el correo de contacto',
                email: 'Por favor ingrese un correo electrónico válido',
                remote: 'El correo de contacto ya está en uso'
            },
            telefono_contacto: { required: 'Por favor ingrese el teléfono de contacto' },
            direccion: { required: 'Por favor ingrese la dirección' },
            slug: {
                required: 'Por favor ingrese el slug',
                regex: 'El slug solo puede contener letras, números y guiones',
                remote: 'El slug ya está en uso'
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
                url: `${Server}clientes/guardar`,
                method: 'POST',
                data: formData,
                success: function () {
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
            success: function () {
                loadingFormXHR($frm, false);
                $tablaClientes.bootstrapTable('refresh');
                showToast('¡Listo!, se actualizó correctamente el cliente.', 'success');
            },
            error: function (xhr) {
                loadingFormXHR($frm, false);
                if (xhr.status === 409) {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.message, 'error');
                } else if (xhr.status === 403) {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.message || 'No autorizado.', 'error');
                }
            }
        });
    });

    $(document).on('submit', '.formActualizarImagenes', function (e) {
        e.preventDefault();

        const $frm = $(this);
        const formData = $frm.serializeObject();
        const clienteId = $frm.find('[name="id"]').val();

        if ((!dropzones[clienteId] || !dropzones[clienteId]['logo'].files.length) && (!dropzones[clienteId] || !dropzones[clienteId]['banner'].files.length)) {
            showToast('Por favor, suba una imagen antes de enviar el formulario.', 'error');
            return false;
        }

        loadingFormXHR($frm, true);

        ajaxCall({
            url: `${Server}clientes/guardar`,
            method: 'POST',
            data: formData,
            success: function () {
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

    initializeDropzone('dropzoneLogo', 'logo');
    initializeDropzone('dropzoneBanner', 'banner');
});

function initializeDropzone(elementId, fieldName, clienteId = null) {
    if (!dropzones[clienteId]) {
        dropzones[clienteId] = {};
    }

    dropzones[clienteId][fieldName] = new Dropzone(`#${elementId}`, {
        url: `${Server}clientes/subirImagen`,
        maxFiles: 1,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra una imagen aquí para subirla',
        dictRemoveFile: 'Eliminar imagen',
        init: function () {
            this.on('success', function (file, response) {
                $(`#formCrearCliente, #formActualizarImagenes-${clienteId}`).append(`<input type="hidden" name="${fieldName}" value="assets/images/clientes/${response.filename}">`);
            });
            this.on('removedfile', function () {
                $(`input[name="${fieldName}"]`).remove();
            });
        }
    });
}

function processDropzones(callback) {
    const dropzoneInstances = Object.values(dropzones).flatMap(clienteDropzones => Object.values(clienteDropzones));

    if (!dropzoneInstances.length) {
        callback();
        return;
    }

    let pendingUploads = dropzoneInstances.length;

    dropzoneInstances.forEach(dropzone => {
        dropzone.on('queuecomplete', () => {
            pendingUploads -= 1;
            if (pendingUploads === 0) {
                callback();
            }
        });
        dropzone.processQueue();
    });
}

window.operateEvents = {
    'click .edit': function (e, value, row, index) {
        editarCliente(row.id);
    },
    'click .remove': function (e, value, row, index) {
        eliminarCliente(row.id);
    },
    'click .view-public': function (e, value, row, index) {
        window.open(`${Server}c/${row.slug}`, '_blank');
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
            },
            error: function (xhr) {
                let errorMessage = 'Ocurrió un error al eliminar el cliente.';
                if (xhr.status === 409) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                }
                showToast(errorMessage, 'error');
            }
        });
    }
}
