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
    const MAX_FILE_SIZE_MB = 10; // Tamaño máximo para archivos normales (10 MB)
    const MAX_AUDIO_FILE_SIZE = 5 * 1024 * 1024; // Tamaño máximo para el archivo de audio (5 MB)
    const MAX_VIDEO_SIZE_MB = 20; // Tamaño máximo para videos cortos (20 MB)

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
            maxDate: 'today',
            defaultDate: 'today' // Fecha seleccionada por defecto: hoy
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
            maxFiles: MAX_FILES, // Máximo 5 archivos
            maxFilesize: MAX_FILE_SIZE_MB, // Limitar el tamaño de cada archivo a 10 MB
            acceptedFiles: 'image/*,application/pdf,video/mp4,video/webm,video/ogg', // Tipos permitidos: imágenes, PDFs y videos cortos
            addRemoveLinks: true,
            dictDefaultMessage: 'Arrastra los archivos aquí para subirlos (imágenes, PDF, videos cortos)',
            dictRemoveFile: 'Eliminar archivo',
            dictFileTooBig: 'El archivo es muy grande ({{filesize}} MB). Tamaño máximo: {{maxFilesize}} MB.',
            dictInvalidFileType: 'Tipo de archivo no permitido.',
            init: function () {
                this.on('success', handleFileUploadSuccess);
                this.on('removedfile', handleFileRemove);
                this.on('error', function (file, message) {
                    console.error('Error en la subida del archivo: ', message);
                    Swal.fire('Error', message, 'error');
                });
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
        $('input[name="anonimo"]').on('change', function () {
            if ($(this).val() == '0') {
                $('#infoAdicional').show(); // Mostrar los campos adicionales si no es anónimo
            } else {
                $('#infoAdicional').hide(); // Ocultar los campos adicionales si es anónimo
            }
        });

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
            Swal.fire('Error', 'El navegador no soporta la grabación de audio o no tiene permisos.', 'error');
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
                Swal.fire('Error', 'Error al acceder al micrófono. Verifique los permisos.', 'error');
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

        const audioFileSize = audioBlob.size / 1024 / 1024; // Tamaño del archivo en MB
        const MAX_AUDIO_SIZE_MB = 5; // Límite de tamaño de archivo de audio a 5 MB

        if (audioFileSize > MAX_AUDIO_SIZE_MB) {
            Swal.fire('Error', 'El archivo de audio supera el tamaño máximo permitido de 5 MB.', 'error');
            $('#audioPlayback').hide();
            $('#audio_input').val(''); // Limpiar el input de audio
            return;
        }

        const audioURL = URL.createObjectURL(audioBlob);
        $('#audioPlayback').attr('src', audioURL).show();

        const file = new File([audioBlob], 'grabacion_audio.wav', { type: 'audio/wav', lastModified: Date.now() });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('audio_input').files = dataTransfer.files;
    }

    /**
     * Manejador para enviar el formulario de creación de denuncia.
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
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Denuncia guardada',
                        text: `Tu denuncia ha sido registrada con éxito. El número de folio es: ${response.folio}`,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location = `${Server}public/cliente/${slug}/seguimiento-denuncia?folio=${response.folio}`;
                    });
                } else {
                    Swal.fire('Error', 'Ocurrió un error al guardar la denuncia. Por favor, intenta de nuevo.', 'error');
                }
            },
            error: function () {
                console.error('Error al guardar la denuncia.');
                Swal.fire('Error', 'Ocurrió un error al guardar la denuncia. Por favor, intenta de nuevo.', 'error');
            }
        });
    }

    // Inicializar todo
    initializeComponents();
});
