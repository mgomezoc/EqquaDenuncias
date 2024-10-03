/**
 * Script para manejar la lógica del formulario de denuncias públicas.
 * Incluye validación con jquery-validate, carga dinámica de subcategorías y departamentos,
 * manejo de Dropzone para archivos adjuntos y grabación de audio usando MediaRecorder API.
 */

// Deshabilitar la autodetección de Dropzone
Dropzone.autoDiscover = false;

$(document).ready(function () {
    // Constantes
    const MAX_FILES = 5;
    const MAX_AUDIO_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    let mediaRecorder;
    let audioChunks = [];
    let audioBlob;

    /**
     * Inicializa las librerías externas y comportamientos en el DOM.
     */
    function initializeComponents() {
        initializeSelect2();
        initializeFlatpickr();
        initializeDropzone();
        initializeValidation();
        initializeEventListeners();
    }

    /**
     * Inicializa el plugin Select2 para los selectores de categoría, subcategoría y departamento.
     */
    function initializeSelect2() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione una opción',
            allowClear: true
        });
    }

    /**
     * Inicializa el calendario de Flatpickr con restricciones.
     */
    function initializeFlatpickr() {
        $('.flatpickr').flatpickr({
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });
    }

    /**
     * Inicializa Dropzone para la subida de archivos adjuntos.
     */
    function initializeDropzone() {
        // Solución para evitar múltiples inicializaciones
        if (Dropzone.instances.length > 0) {
            Dropzone.instances.forEach(dropzone => dropzone.destroy());
        }

        const dropzoneOptions = {
            url: `${Server}public/denuncias/subir-anexo-public`,
            maxFiles: MAX_FILES,
            acceptedFiles: 'image/*,application/pdf',
            addRemoveLinks: true,
            dictDefaultMessage: 'Arrastra los archivos aquí para subirlos',
            dictRemoveFile: 'Eliminar archivo',
            init: function () {
                this.on('success', handleFileUploadSuccess);
                this.on('removedfile', handleFileRemove);
            },
            error: function (file, message) {
                console.error('Error en la subida del archivo: ', message);
            }
        };

        new Dropzone('#dropzoneArchivos', dropzoneOptions);
    }

    /**
     * Inicializa la validación del formulario con jquery-validate.
     */
    function initializeValidation() {
        $('#formCrearDenuncia').validate({
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            ignore: ':hidden:not(.select2)', // Permitir validar select2
            errorPlacement: function (error, element) {
                if (element.hasClass('select2')) {
                    error.insertAfter(element.next('.select2-container')); // Insertar el error debajo de select2
                } else {
                    error.insertAfter(element);
                }
            },
            rules: {
                id_sucursal: {
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
                como_se_entero: {
                    required: true
                },
                area_incidente: {
                    required: true,
                    minlength: 5
                },
                descripcion: {
                    required: true,
                    minlength: 10
                },
                anonimo: {
                    required: true
                }
            },
            messages: {
                id_sucursal: 'Seleccione una sucursal.',
                categoria: 'Seleccione una categoría.',
                subcategoria: 'Seleccione una subcategoría.',
                id_departamento: 'Seleccione un departamento.',
                fecha_incidente: 'Ingrese una fecha válida.',
                como_se_entero: 'Seleccione cómo se enteró.',
                area_incidente: 'Ingrese el área donde sucedió el incidente (mínimo 5 caracteres).',
                descripcion: 'Describa la denuncia (mínimo 10 caracteres).',
                anonimo: 'Indique si la denuncia es anónima.'
            }
        });

        // Soporte para validación de select2
        $('.select2').on('change', function () {
            $(this).valid();
        });
    }

    /**
     * Asigna los manejadores de eventos a los elementos del DOM.
     */
    function initializeEventListeners() {
        $('#categoria').on('change', function () {
            const categoriaId = $(this).val();
            loadSubcategorias(categoriaId);
        });

        $('#id_sucursal').on('change', function () {
            const sucursalId = $(this).val();
            loadDepartamentos(sucursalId);
        });

        $('#startRecording').on('click', startAudioRecording);
        $('#stopRecording').on('click', stopAudioRecording);

        $('#formCrearDenuncia').on('submit', handleFormSubmit);
    }

    /**
     * Función que carga dinámicamente las subcategorías al seleccionar una categoría.
     *
     * @param {number} categoriaId - ID de la categoría seleccionada.
     */
    function loadSubcategorias(categoriaId) {
        $.ajax({
            url: `${Server}public/categorias/listarSubcategorias`,
            method: 'GET',
            data: { id_categoria: categoriaId },
            success: function (data) {
                $('#subcategoria').empty().append('<option value="">Seleccione una subcategoría</option>');
                data.forEach(subcategoria => {
                    $('#subcategoria').append(`<option value="${subcategoria.id}">${subcategoria.nombre}</option>`);
                });
            },
            error: function () {
                $('#subcategoria').html('<option value="">Error al cargar subcategorías</option>');
            }
        });
    }

    /**
     * Función que carga dinámicamente los departamentos al seleccionar una sucursal.
     *
     * @param {number} sucursalId - ID de la sucursal seleccionada.
     */
    function loadDepartamentos(sucursalId) {
        $.ajax({
            url: `${Server}public/departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
            method: 'GET',
            success: function (data) {
                $('#id_departamento').empty().append('<option value="">Seleccione un departamento</option>');
                data.forEach(departamento => {
                    $('#id_departamento').append(`<option value="${departamento.id}">${departamento.nombre}</option>`);
                });
            },
            error: function () {
                $('#id_departamento').html('<option value="">Error al cargar departamentos</option>');
            }
        });
    }

    /**
     * Manejador del evento de éxito al subir un archivo con Dropzone.
     *
     * @param {Object} file - El archivo subido.
     * @param {Object} response - Respuesta del servidor.
     */
    function handleFileUploadSuccess(file, response) {
        $('<input>')
            .attr({
                type: 'hidden',
                name: 'archivos[]',
                value: 'uploads/denuncias/' + response.filename
            })
            .appendTo('#formCrearDenuncia');
    }

    /**
     * Manejador del evento de eliminación de archivo en Dropzone.
     *
     * @param {Object} file - El archivo eliminado.
     */
    function handleFileRemove(file) {
        const filename = file.upload.filename;
        $(`#formCrearDenuncia input[value="uploads/denuncias/${filename}"]`).remove();
    }

    /**
     * Verifica si el navegador soporta la API getUserMedia para grabar audio.
     */
    function checkMicrophoneSupport() {
        return navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    }

    /**
     * Inicia la grabación de audio usando la API de MediaRecorder.
     */
    function startAudioRecording() {
        if (!checkMicrophoneSupport()) {
            alert('El navegador no soporta la grabación de audio o no tiene permisos.');
            return;
        }

        navigator.mediaDevices
            .getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();

                audioChunks = [];
                $('#startRecording').attr('disabled', true);
                $('#stopRecording').attr('disabled', false);

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = handleAudioRecordingStop;
            })
            .catch(error => {
                console.error('Error al acceder al micrófono: ', error);
                alert('Error al acceder al micrófono. Verifique los permisos.');
            });
    }

    /**
     * Detiene la grabación de audio.
     */
    function stopAudioRecording() {
        if (mediaRecorder) {
            mediaRecorder.stop();
            $('#startRecording').attr('disabled', false);
            $('#stopRecording').attr('disabled', true);
        }
    }

    /**
     * Manejador de la finalización de la grabación de audio.
     */
    function handleAudioRecordingStop() {
        audioBlob = new Blob(audioChunks, { type: 'audio/wav' });

        const audioURL = URL.createObjectURL(audioBlob);
        $('#audioPlayback').attr('src', audioURL).show();

        const file = new File([audioBlob], 'grabacion_audio.wav', { type: 'audio/wav', lastModified: Date.now() });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('audio_input').files = dataTransfer.files;
    }

    /**
     * Manejador para enviar el formulario de creación de denuncia.
     *
     * @param {Event} e - Evento de envío del formulario.
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        if (!$('#formCrearDenuncia').valid()) {
            return;
        }

        const formData = new FormData(this);

        $.ajax({
            url: `${Server}public/denuncias/guardar-public`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                alert('Denuncia guardada correctamente.');
                window.location.reload();
            },
            error: function () {
                console.error('Error al guardar la denuncia.');
                alert('Ocurrió un error al guardar la denuncia. Por favor, intente de nuevo.');
            }
        });
    }

    // Inicializar todo
    initializeComponents();
});
