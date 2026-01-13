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

Dropzone.autoDiscover = false;

// --- Polyfill serializeObject (por si no existe en tu proyecto) ---
if (typeof $.fn.serializeObject !== 'function') {
    $.fn.serializeObject = function () {
        const o = {};
        const a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name] !== undefined) {
                if (!Array.isArray(o[this.name])) o[this.name] = [o[this.name]];
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
}

// Regla personalizada 'regex'
$.validator.addMethod(
    'regex',
    function (value, element, regexp) {
        const re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    'Por favor, ingrese un valor válido.'
);

// Tipos de denunciante soportados
const TIPOS_DENUNCIANTE = ['Cliente', 'Colaborador', 'Proveedor'];

// Helpers
function parseCsv(value) {
    if (value === null || value === undefined) return [];
    const s = String(value).trim();
    if (!s) return [];
    return s
        .split(',')
        .map(x => x.trim())
        .filter(Boolean);
}

function toCsv(value) {
    if (Array.isArray(value)) return value.join(',');
    return (value ?? '').toString();
}

// Mapea la política a badge/etiqueta
function politicaFormatter(value) {
    const v = Number(value);
    if (v === 1) return '<span class="badge text-bg-success">Forzar anónimas</span>';
    if (v === 2) return '<span class="badge text-bg-warning">Forzar identificadas</span>';
    return '<span class="badge text-bg-secondary">Opcional</span>';
}

/**
 * Inicializa los controles de:
 * - mostrar_tipo_denunciante_publico
 * - tipos_denunciante_publico_permitidos
 * - tipo_denunciante_publico_default
 * dentro del detailView.
 */
function initTipoDenunciantePublicoControls($detail, row) {
    const $mostrar = $detail.find('[name="mostrar_tipo_denunciante_publico"]');

    // Puede ser <select multiple> o <input>
    const $permitidos = $detail.find('[name="tipos_denunciante_publico_permitidos"]');

    // Debe ser <select>
    const $default = $detail.find('[name="tipo_denunciante_publico_default"]');

    // 1) Setear mostrar
    const mostrarVal = Number(row.mostrar_tipo_denunciante_publico ?? 0);
    if ($mostrar.length) {
        $mostrar.val(String(mostrarVal)).trigger('change');
    }

    // 2) Permisos (tipos permitidos)
    const permitidosArr = parseCsv(row.tipos_denunciante_publico_permitidos);
    const permitidosFinal = permitidosArr.length ? permitidosArr : [...TIPOS_DENUNCIANTE];

    if ($permitidos.length) {
        // Si es un SELECT, nos aseguramos que existan opciones y seleccionamos
        if ($permitidos.is('select')) {
            // Si no tiene opciones, las creamos
            if ($permitidos.find('option').length === 0) {
                TIPOS_DENUNCIANTE.forEach(t => {
                    $permitidos.append(new Option(t, t, false, false));
                });
            }
            // Si es multiple: set array; si no: set string
            if ($permitidos.prop('multiple')) {
                $permitidos.val(permitidosFinal).trigger('change');
            } else {
                $permitidos.val(permitidosFinal[0] || 'Colaborador').trigger('change');
            }
        } else {
            // Si es INPUT, seteamos CSV
            $permitidos.val(permitidosFinal.join(','));
        }
    }

    // 3) Default
    const defaultVal = (row.tipo_denunciante_publico_default ?? 'Colaborador').toString();

    if ($default.length) {
        // Si no tiene opciones, las creamos
        if ($default.is('select') && $default.find('option').length === 0) {
            TIPOS_DENUNCIANTE.forEach(t => {
                $default.append(new Option(t, t, false, false));
            });
        }

        // Forzar que el default esté dentro de permitidos
        let fixedDefault = defaultVal;
        if (!permitidosFinal.includes(fixedDefault)) {
            fixedDefault = permitidosFinal[0] || 'Colaborador';
        }

        $default.val(fixedDefault).trigger('change');
    }

    // 4) Habilitar/deshabilitar según mostrar
    function toggleEnabled() {
        const m = Number($mostrar.val() ?? 0);
        const enabled = m === 1;

        if ($permitidos.length) {
            $permitidos.prop('disabled', !enabled).trigger('change.select2');
        }
        if ($default.length) {
            $default.prop('disabled', !enabled).trigger('change.select2');
        }
    }

    // 5) Si cambia mostrar, toggle
    if ($mostrar.length) {
        $mostrar.off('change._tipoDen').on('change._tipoDen', function () {
            toggleEnabled();
        });
    }

    // 6) Si cambia permitidos, asegurar default válido (solo si permitidos es select multiple)
    if ($permitidos.length && $permitidos.is('select') && $permitidos.prop('multiple')) {
        $permitidos.off('change._permitidos').on('change._permitidos', function () {
            const selected = $(this).val() || [];
            const selectedArr = Array.isArray(selected) ? selected : [selected];

            if (!$default.length) return;

            const currentDefault = ($default.val() ?? '').toString();
            if (selectedArr.length && !selectedArr.includes(currentDefault)) {
                $default.val(selectedArr[0]).trigger('change');
            }
        });
    }

    // aplicar estado inicial
    toggleEnabled();
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
                visible: rol === 'ADMIN'
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            const renderData = Handlebars.compile(tplDetalleTabla)(row);
            $detail.html(renderData);

            // Inicializar select2 primero (para que seteo .val() sea visible)
            $detail.find('select').select2({ width: '100%' });

            // Política
            $detail
                .find('[name="politica_anonimato"]')
                .val(row.politica_anonimato ?? 0)
                .trigger('change');

            // NUEVO: Tipo denunciante público (mostrar + permitidos + default)
            initTipoDenunciantePublicoControls($detail, row);

            // Dropzones
            initializeDropzone(`dropzoneLogo-${row.id}`, 'logo', row.id);
            initializeDropzone(`dropzoneBanner-${row.id}`, 'banner', row.id);

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
                        },
                        politica_anonimato: { required: true },
                        mostrar_tipo_denunciante_publico: { required: true }
                        // Tipos permitidos/default no los hago required porque dependen de "mostrar"
                    }
                });
            } else {
                // CLIENTE: mínimo
                $frm.validate({
                    rules: {
                        politica_anonimato: { required: true },
                        mostrar_tipo_denunciante_publico: { required: true }
                    }
                });
            }
        }
    });

    // Crear cliente
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

            // Si "tipos permitidos" llega como array (select multiple), convertir a CSV
            if (formData.tipos_denunciante_publico_permitidos !== undefined) {
                formData.tipos_denunciante_publico_permitidos = toCsv(formData.tipos_denunciante_publico_permitidos);
            }

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

    // Editar cliente
    $(document).on('submit', '.formEditarCliente', function (e) {
        e.preventDefault();

        const $frm = $(this);
        if (!$frm.valid()) return false;

        const formData = $frm.serializeObject();

        // Normalizar CSV si viene como array
        if (formData.tipos_denunciante_publico_permitidos !== undefined) {
            formData.tipos_denunciante_publico_permitidos = toCsv(formData.tipos_denunciante_publico_permitidos);
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
                } else if (xhr.status === 403) {
                    const response = JSON.parse(xhr.responseText);
                    showToast(response.message || 'No autorizado.', 'error');
                }
            }
        });
    });

    // Actualizar imágenes
    $(document).on('submit', '.formActualizarImagenes', function (e) {
        e.preventDefault();

        const $frm = $(this);
        const formData = $frm.serializeObject();
        const clienteId = $frm.find('[name="id"]').val();

        if ((!dropzones[clienteId] || !dropzones[clienteId]['logo']?.files?.length) && (!dropzones[clienteId] || !dropzones[clienteId]['banner']?.files?.length)) {
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
    if (!dropzones[clienteId]) dropzones[clienteId] = {};

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
                const path = `assets/images/clientes/${response.filename}`;

                // Alta
                if (clienteId === null) {
                    const $f = $('#formCrearCliente');
                    $f.find(`input[name="${fieldName}"]`).remove();
                    $f.append(`<input type="hidden" name="${fieldName}" value="${path}">`);
                    return;
                }

                // Edición / imágenes
                const $fImg = $(`#formActualizarImagenes-${clienteId}`);
                if ($fImg.length) {
                    $fImg.find(`input[name="${fieldName}"]`).remove();
                    $fImg.append(`<input type="hidden" name="${fieldName}" value="${path}">`);
                }

                // También lo ponemos en el form de edición, por si guardas desde ahí
                const $fEdit = $(`#formEditarCliente-${clienteId}`);
                if ($fEdit.length) {
                    $fEdit.find(`input[name="${fieldName}"]`).remove();
                    $fEdit.append(`<input type="hidden" name="${fieldName}" value="${path}">`);
                }
            });

            this.on('removedfile', function () {
                if (clienteId === null) {
                    $('#formCrearCliente').find(`input[name="${fieldName}"]`).remove();
                } else {
                    $(`#formActualizarImagenes-${clienteId}`).find(`input[name="${fieldName}"]`).remove();
                    $(`#formEditarCliente-${clienteId}`).find(`input[name="${fieldName}"]`).remove();
                }
            });
        }
    });
}

window.operateEvents = {
    'click .remove': function (e, value, row) {
        eliminarCliente(row.id);
    },
    'click .view-public': function (e, value, row) {
        window.open(`${Server}c/${row.slug}`, '_blank');
    }
};

function operateFormatter(value, row) {
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
