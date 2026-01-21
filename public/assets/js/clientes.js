/***
 *
 * CLIENTES
 *
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaClientes;
let $modalCrearCliente;

// Dropzones por cliente (detail view)
let dropzones = {};

// Dropzones del modal crear
let dzCrearLogo = null;
let dzCrearBanner = null;

Dropzone.autoDiscover = false;

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

function initSelect2InModal($modal) {
    // Evitar dobles init
    $modal.find('select').each(function () {
        const $s = $(this);
        if ($s.hasClass('select2-hidden-accessible')) return;

        $s.select2({
            width: '100%',
            dropdownParent: $modal
        });
    });
}

function destroySelect2InModal($modal) {
    $modal.find('select.select2-hidden-accessible').each(function () {
        $(this).select2('destroy');
    });
}

function resetCrearClienteModal() {
    const $form = $('#formCrearCliente');

    // 1) reset form + validación
    $form[0].reset();
    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    if ($form.data('validator')) {
        $form.validate().resetForm();
    }

    // 2) limpiar hidden inputs que vienen de dropzones
    $form.find('input[type="hidden"][name="logo"], input[type="hidden"][name="banner"]').remove();

    // 3) reset select2 (para que el UI se vea limpio)
    // Primero destruye y vuelve a iniciar (o solo setea valores si prefieres)
    destroySelect2InModal($modalCrearCliente);
    initSelect2InModal($modalCrearCliente);

    // 4) reset dropzones del modal
    if (dzCrearLogo) dzCrearLogo.removeAllFiles(true);
    if (dzCrearBanner) dzCrearBanner.removeAllFiles(true);
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

            // Setear select política
            $detail
                .find('[name="politica_anonimato"]')
                .val(row.politica_anonimato ?? 0)
                .trigger('change');

            // Setear select mostrar tipo denunciante público
            $detail
                .find('[name="mostrar_tipo_denunciante_publico"]')
                .val(row.mostrar_tipo_denunciante_publico ?? 0)
                .trigger('change');

            // NUEVO: setear tipos permitidos (si tu template ya lo tiene)
            // Esperado en DB: "Cliente,Colaborador,Proveedor"
            if (row.tipos_denunciante_publico_permitidos) {
                const arr = String(row.tipos_denunciante_publico_permitidos)
                    .split(',')
                    .map(x => x.trim())
                    .filter(Boolean);

                $detail.find('[name="tipos_denunciante_publico_permitidos[]"], [name="tipos_denunciante_publico_permitidos"]').val(arr).trigger('change');
            }

            // NUEVO: setear default
            if (row.tipo_denunciante_publico_default) {
                $detail.find('[name="tipo_denunciante_publico_default"]').val(row.tipo_denunciante_publico_default).trigger('change');
            }

            // Inicializar Dropzones (detail view)
            initializeDropzone(`dropzoneLogo-${row.id}`, 'logo', row.id);
            initializeDropzone(`dropzoneBanner-${row.id}`, 'banner', row.id);

            // Select2 en el detail row
            $detail.find('select').select2({ width: '100%' });

            // Validación
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
                $frm.validate({
                    rules: {
                        politica_anonimato: { required: true }
                    }
                });
            }
        }
    });

    // ===== Modal crear: Select2 =====
    // Inicia select2 cuando abra modal (asegura dropdownParent)
    $modalCrearCliente.on('shown.bs.modal', function () {
        initSelect2InModal($modalCrearCliente);
    });

    // Resetea completamente al cerrar modal
    $modalCrearCliente.on('hidden.bs.modal', function () {
        resetCrearClienteModal();
    });

    // ===== Dropzones del modal crear =====
    dzCrearLogo = initializeDropzoneCrear('dropzoneLogo', 'logo', '#formCrearCliente');
    dzCrearBanner = initializeDropzoneCrear('dropzoneBanner', 'banner', '#formCrearCliente');

    // ===== Validación crear =====
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
            error.insertAfter(element);
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

                    // Extra: por si el modal no alcanzó a disparar hidden.bs.modal
                    resetCrearClienteModal();
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

    // ===== Editar cliente =====
    $(document).on('submit', '.formEditarCliente', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) return false;

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

    // ===== Actualizar imágenes (detail view) =====
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
});

// ===== Dropzone: detail view =====
function initializeDropzone(elementId, fieldName, clienteId = null) {
    if (!dropzones[clienteId]) dropzones[clienteId] = {};

    // Evitar reinicializar si ya existe
    if (dropzones[clienteId][fieldName]) return;

    dropzones[clienteId][fieldName] = new Dropzone(`#${elementId}`, {
        url: `${Server}clientes/subirImagen`,
        maxFiles: 1,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra una imagen aquí para subirla',
        dictRemoveFile: 'Eliminar imagen',
        autoProcessQueue: true,
        init: function () {
            this.on('success', function (file, response) {
                // Quita hidden anterior del mismo field y agrega el nuevo
                $(`#formActualizarImagenes-${clienteId} input[type="hidden"][name="${fieldName}"]`).remove();
                $(`#formActualizarImagenes-${clienteId}`).append(`<input type="hidden" name="${fieldName}" value="assets/images/clientes/${response.filename}">`);
            });

            this.on('removedfile', function () {
                $(`#formActualizarImagenes-${clienteId} input[type="hidden"][name="${fieldName}"]`).remove();
            });
        }
    });
}

// ===== Dropzone: modal crear =====
function initializeDropzoneCrear(elementId, fieldName, formSelector) {
    const dz = new Dropzone(`#${elementId}`, {
        url: `${Server}clientes/subirImagen`,
        maxFiles: 1,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra una imagen aquí para subirla',
        dictRemoveFile: 'Eliminar imagen',
        autoProcessQueue: true,
        init: function () {
            this.on('success', function (file, response) {
                // Quita hidden anterior y agrega el nuevo
                $(`${formSelector} input[type="hidden"][name="${fieldName}"]`).remove();
                $(formSelector).append(`<input type="hidden" name="${fieldName}" value="assets/images/clientes/${response.filename}">`);
            });

            this.on('removedfile', function () {
                $(`${formSelector} input[type="hidden"][name="${fieldName}"]`).remove();
            });
        }
    });

    return dz;
}

window.operateEvents = {
    'click .remove': function (e, value, row) {
        eliminarCliente(row.id);
    },
    'click .view-public': function (e, value, row) {
        window.open(`${Server}c/${row.slug}`, '_blank');
    }
};

function operateFormatter(value, row, index) {
    return Handlebars.compile(tplAccionesTabla)(row);
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
                    if (response.message) errorMessage = response.message;
                }
                showToast(errorMessage, 'error');
            }
        });
    }
}
