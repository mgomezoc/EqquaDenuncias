/**
 * DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;
let dropzones = {};

Dropzone.autoDiscover = false; // Desactivar la autodetección de Dropzone

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return v1 == v2 ? options.fn(this) : options.inverse(this);
        case '===':
            return v1 === v2 ? options.fn(this) : options.inverse(this);
        case '!=':
            return v1 != v2 ? options.fn(this) : options.inverse(this);
        case '!==':
            return v1 !== v2 ? options.fn(this) : options.inverse(this);
        case '<':
            return v1 < v2 ? options.fn(this) : options.inverse(this);
        case '<=':
            return v1 <= v2 ? options.fn(this) : options.inverse(this);
        case '>':
            return v1 > v2 ? options.fn(this) : options.inverse(this);
        case '>=':
            return v1 >= v2 ? options.fn(this) : options.inverse(this);
        case '&&':
            return v1 && v2 ? options.fn(this) : options.inverse(this);
        case '||':
            return v1 || v2 ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
    }
});

$(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();
    $modalCrearDenuncia = $('#modalCrearDenuncia');

    // Inicializar select2 en los selects dentro del modal
    $('#modalCrearDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearDenuncia')
    });

    // Configurar la validación del formulario
    $('#formCrearDenuncia').validate({
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        errorPlacement: function (error, element) {
            if (element.hasClass('select2') && element.next('.select2-container').length) {
                error.addClass('invalid-feedback').insertAfter(element.next('.select2-container'));
            } else if (element.is('input[type="checkbox"]') || element.is('input[type="radio"]')) {
                error.addClass('invalid-feedback').insertAfter(element.closest('div'));
            } else {
                error.addClass('invalid-feedback').insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection').addClass(errorClass).removeClass(validClass);
            } else {
                $(element).addClass(errorClass).removeClass(validClass);
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            if ($(element).hasClass('select2')) {
                $(element).next('.select2-container').find('.select2-selection').removeClass(errorClass).addClass(validClass);
            } else {
                $(element).removeClass(errorClass).addClass(validClass);
            }
        },
        rules: {
            id_cliente: {
                required: true
            },
            categoria: {
                required: true
            },
            subcategoria: {
                required: true
            },
            id_departamento: {
                required: true
            },
            fecha_incidente: {
                required: true,
                date: true
            },
            descripcion: {
                required: true
            }
        },
        messages: {
            id_cliente: {
                required: 'Por favor seleccione un cliente'
            },
            categoria: {
                required: 'Por favor seleccione una categoría'
            },
            subcategoria: {
                required: 'Por favor seleccione una subcategoría'
            },
            id_departamento: {
                required: 'Por favor seleccione un departamento'
            },
            fecha_incidente: {
                required: 'Por favor ingrese la fecha del incidente',
                date: 'Ingrese una fecha válida'
            },
            descripcion: {
                required: 'Por favor ingrese la descripción'
            }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = new FormData(form);

            loadingFormXHR($frm, true);

            // Enviar la solicitud AJAX para guardar la denuncia
            $.ajax({
                url: `${Server}denuncias/guardar`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearDenuncia.modal('hide');
                    $tablaDenuncias.bootstrapTable('refresh');
                    showToast('¡Listo!, se creó correctamente la denuncia.', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                    if (dropzones['archivosAdjuntos']) {
                        dropzones['archivosAdjuntos'].removeAllFiles(true);
                    }
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

    // Cuando se selecciona una opción en select2, se debe actualizar la validación
    $('#modalCrearDenuncia .select2').on('change', function (e) {
        $(this).valid();
    });

    // Resetear el formulario al cerrar el modal de creación
    $modalCrearDenuncia.on('hidden.bs.modal', function () {
        const $form = $('#formCrearDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();

        // Resetear los archivos subidos en Dropzone
        if (dropzones['archivosAdjuntos']) {
            dropzones['archivosAdjuntos'].removeAllFiles(true);
        }
    });

    // Inicialización de la tabla de denuncias
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
                field: 'sucursal_nombre',
                title: 'Sucursal'
            },
            {
                field: 'tipo_denunciante',
                title: 'Tipo Denunciante'
            },
            {
                field: 'categoria_nombre',
                title: 'Categoría'
            },
            {
                field: 'subcategoria_nombre',
                title: 'Subcategoría'
            },
            {
                field: 'departamento_nombre',
                title: 'Departamento'
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
            $detail.html(`Cargando...`);

            $.when(
                $.get(`${Server}clientes/listar`),
                $.get(`${Server}categorias/listarCategorias`),
                $.get(`${Server}categorias/listarSubcategorias`, { id_categoria: row.categoria }),
                $.get(`${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${row.id_cliente}`),
                $.get(`${Server}denuncias/detalle/${row.id}`),
                $.get(`${Server}denuncias/obtenerEstados`),
                $.get(`${Server}denuncias/obtenerAnexos/${row.id}`) // Obtener los anexos
            ).done(function (clientes, categorias, subcategorias, sucursales, denunciaDetalles, estados, anexos) {
                const data = {
                    id: row.id,
                    clientes: clientes[0].map(cliente => ({ id: cliente.id, name: cliente.nombre_empresa })),
                    categorias: categorias[0].map(categoria => ({ id: categoria.id, name: categoria.nombre })),
                    subcategorias: subcategorias[0].map(subcategoria => ({ id: subcategoria.id, name: subcategoria.nombre })),
                    sucursales: sucursales[0].map(sucursal => ({ id: sucursal.id, name: sucursal.nombre })),
                    estados: estados[0].map(estado => ({ id: estado.id, name: estado.nombre })),
                    anexos: anexos[0], // Añadir los anexos a los datos
                    id_cliente: row.id_cliente,
                    categoria: row.categoria,
                    subcategoria: row.subcategoria,
                    estado_actual: row.estado_actual,
                    descripcion: row.descripcion,
                    anonimo: row.anonimo,
                    departamento_nombre: row.departamento_nombre,
                    denuncia: row
                };

                console.log(data);

                const renderData = Handlebars.compile(tplDetalleTabla)(data);

                // Renderizar y mostrar el detalle
                $detail.html(renderData);

                // Inicializar select2 para los nuevos selectores
                $detail.find('select').select2();
                $detail.find('.formEditarDenuncia').validate({
                    rules: {
                        id_cliente: { required: true },
                        categoria: { required: true },
                        subcategoria: { required: true },
                        estado_actual: { required: true },
                        descripcion: { required: true }
                    },
                    messages: {
                        id_cliente: { required: 'Por favor seleccione un cliente' },
                        categoria: { required: 'Por favor seleccione una categoría' },
                        subcategoria: { required: 'Por favor seleccione una subcategoría' },
                        estado_actual: { required: 'Por favor seleccione un estado' },
                        descripcion: { required: 'Por favor ingrese la descripción' }
                    }
                });

                // Cargar dinámicamente las subcategorías según la categoría seleccionada
                $detail.find(`#categoria-${row.id}`).change(function () {
                    const categoriaId = $(this).val();
                    loadSubcategorias(categoriaId, `#subcategoria-${row.id}`);
                });

                // Cargar dinámicamente las sucursales según el cliente seleccionado
                $detail.find(`#id_cliente-${row.id}`).change(function () {
                    const clienteId = $(this).val();
                    loadSucursales(clienteId, `#id_sucursal-${row.id}`);
                });
            });
        }
    });

    // Inicializar flatpickr para el campo de fecha
    $('#fecha_incidente').flatpickr({
        dateFormat: 'Y-m-d'
    });

    // Cargar dinámicamente las subcategorías según la categoría seleccionada en el formulario de creación
    $('#categoria').change(function () {
        const categoriaId = $(this).val();
        loadSubcategorias(categoriaId, '#subcategoria');
    });

    // Cargar dinámicamente las sucursales según el cliente seleccionado en el formulario de creación
    $('#id_cliente').change(function () {
        const clienteId = $(this).val();
        loadSucursales(clienteId, '#id_sucursal');
    });

    // Cargar dinámicamente los departamentos según la sucursal seleccionada en el formulario de creación
    $('#id_sucursal').change(function () {
        const sucursalId = $(this).val();
        loadDepartamentos(sucursalId, '#id_departamento');
    });

    // Inicializar Dropzone para los archivos adjuntos
    initializeDropzone('dropzoneArchivos', 'archivosAdjuntos');
});

// Función para inicializar Dropzone
function initializeDropzone(elementId, fieldName) {
    dropzones[fieldName] = new Dropzone(`#${elementId}`, {
        url: `${Server}denuncias/subirAnexo`,
        maxFiles: 5,
        acceptedFiles: 'image/*,application/pdf',
        addRemoveLinks: true,
        dictDefaultMessage: 'Arrastra los archivos aquí para subirlos',
        dictRemoveFile: 'Eliminar archivo',
        init: function () {
            this.on('success', function (file, response) {
                $(`#formCrearDenuncia`).append(`<input type="hidden" name="archivos[]" value="assets/denuncias/${response.filename}">`);
            });
            this.on('removedfile', function (file) {
                const name = file.upload.filename;
                $(`input[value="assets/denuncias/${name}"]`).remove();
            });
        }
    });
}

// Función para cargar subcategorías
function loadSubcategorias(categoriaId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}categorias/listarSubcategorias`,
        method: 'GET',
        data: { id_categoria: categoriaId },
        success: function (data) {
            let options = '<option value="">Seleccione una subcategoría</option>';
            data.forEach(function (subcategoria) {
                options += `<option value="${subcategoria.id}">${subcategoria.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            console.error('Error loading subcategories.');
        }
    });
}

// Función para cargar sucursales
function loadSucursales(clienteId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}denuncias/sucursales/obtenerSucursalesPorCliente/${clienteId}`,
        method: 'GET',
        success: function (data) {
            let options = '<option value="">Seleccione una sucursal</option>';
            data.forEach(function (sucursal) {
                options += `<option value="${sucursal.id}">${sucursal.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            console.error('Error loading branches.');
        }
    });
}

// Función para cargar departamentos
function loadDepartamentos(sucursalId, selectSelector) {
    $(selectSelector).html('<option>Cargando...</option>');
    $.ajax({
        url: `${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
        method: 'GET',
        success: function (data) {
            let options = '<option value="">Seleccione un departamento</option>';
            data.forEach(function (departamento) {
                options += `<option value="${departamento.id}">${departamento.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            console.error('Error al cargar los departamentos.');
        }
    });
}

function operateFormatter(value, row, index) {
    const renderData = Handlebars.compile(tplAccionesTabla)(row);
    return renderData;
}
