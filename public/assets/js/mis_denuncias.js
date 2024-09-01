/**
 * MIS DENUNCIAS
 */
let tplAccionesTabla;
let tplDetalleTabla;
let $tablaDenuncias;
let $modalCrearDenuncia;
let dropzones = {};

Dropzone.autoDiscover = false;

$(document).ready(function () {
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplDetalleTabla = $('#tplDetalleTabla').html();

    // Inicializa la tabla de denuncias específicas del cliente
    $('#tablaMisDenuncias').bootstrapTable({
        url: `${Server}denuncias/mis-denuncias`, // Endpoint para cargar las denuncias del cliente
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'folio', title: 'Folio' },
            { field: 'sucursal_nombre', title: 'Sucursal' },
            { field: 'tipo_denunciante', title: 'Tipo Denunciante' },
            { field: 'categoria_nombre', title: 'Categoría' },
            { field: 'subcategoria_nombre', title: 'Subcategoría' },
            { field: 'departamento_nombre', title: 'Departamento' },
            { field: 'estado_nombre', title: 'Estado' },
            { field: 'fecha_hora_reporte', title: 'Fecha' },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                formatter: operateFormatter,
                events: window.operateEvents
            }
        ]
    });

    // Inicializar select2 en los selects dentro del modal
    $('#modalDenuncia .select2').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalDenuncia')
    });

    // Cargar las sucursales al abrir el modal
    $('#modalDenuncia').on('show.bs.modal', function () {
        const idCliente = $('input[name="id_cliente"]').val();

        if (idCliente) {
            loadSucursales(idCliente, '#id_sucursal');
        }
    });

    // Configurar la validación del formulario
    $('#formDenuncia').validate({
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
            id_sucursal: { required: true },
            categoria: { required: true },
            subcategoria: { required: true },
            id_departamento: { required: true },
            fecha_incidente: { required: true, date: true },
            descripcion: { required: true }
        },
        messages: {
            id_sucursal: { required: 'Por favor seleccione una sucursal' },
            categoria: { required: 'Por favor seleccione una categoría' },
            subcategoria: { required: 'Por favor seleccione una subcategoría' },
            id_departamento: { required: 'Por favor seleccione un departamento' },
            fecha_incidente: { required: 'Por favor ingrese la fecha del incidente', date: 'Ingrese una fecha válida' },
            descripcion: { required: 'Por favor ingrese la descripción' }
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
                    $('#modalDenuncia').modal('hide');
                    $('#tablaMisDenuncias').bootstrapTable('refresh');
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

    // Inicializar Dropzone para los archivos adjuntos
    initializeDropzone('dropzoneArchivos', 'archivosAdjuntos');

    // Cargar dinámicamente las subcategorías según la categoría seleccionada en el formulario de creación
    $('#categoria').change(function () {
        const categoriaId = $(this).val();
        loadSubcategorias(categoriaId, '#subcategoria');
    });

    // Cargar dinámicamente los departamentos según la sucursal seleccionada en el formulario de creación
    $('#id_sucursal').change(function () {
        const sucursalId = $(this).val();
        loadDepartamentos(sucursalId, '#id_departamento');
    });

    // Resetear el formulario al cerrar el modal de creación
    $('#modalDenuncia').on('hidden.bs.modal', function () {
        const $form = $('#formDenuncia');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();

        // Resetear los archivos subidos en Dropzone
        if (dropzones['archivosAdjuntos']) {
            dropzones['archivosAdjuntos'].removeAllFiles(true);
        }
    });
});

// Función para cargar sucursales
function loadSucursales(idCliente, selectSelector) {
    $.ajax({
        url: `${Server}sucursales/obtener/${idCliente}`,
        method: 'GET',
        success: function (data) {
            let options = '<option value="">Seleccione una sucursal</option>';
            data.forEach(function (sucursal) {
                options += `<option value="${sucursal.id}">${sucursal.nombre}</option>`;
            });
            $(selectSelector).html(options);
        },
        error: function () {
            console.error('Error loading sucursales.');
        }
    });
}

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
                $(`#formDenuncia`).append(`<input type="hidden" name="archivos[]" value="assets/denuncias/${response.filename}">`);
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
