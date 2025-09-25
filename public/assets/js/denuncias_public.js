/**
 * Script para manejar la lógica del formulario de denuncias públicas.
 * - Política de anonimato por cliente (POLITICA: 0=opcional, 1=forzar anónimas, 2=forzar identificadas)
 * - Validación con jquery-validate
 * - Carga dinámica de departamentos (y subcategorías si existen en el DOM)
 * - Dropzone para archivos
 * - Grabación de audio con MediaRecorder
 */

Dropzone.autoDiscover = false; // Deshabilitar autodetección

$(document).ready(function () {
    // Constantes
    const MAX_FILES = 5;
    const MAX_FILE_SIZE_MB = 10; // imágenes/pdf 10 MB
    const MAX_VIDEO_SIZE_MB = 20; // videos 20 MB (validado por Dropzone vía acceptedFiles)
    const MAX_AUDIO_SIZE_MB = 5; // audio 5 MB

    let mediaRecorder;
    let audioChunks = [];
    let audioBlob;

    /** ---------- Helpers de política ---------- */
    function isForcedAnon() {
        return typeof POLITICA !== 'undefined' && POLITICA === 1;
    }
    function isForcedIdent() {
        return typeof POLITICA !== 'undefined' && POLITICA === 2;
    }
    function isOptional() {
        return typeof POLITICA === 'undefined' || POLITICA === 0;
    }

    // ¿Se requiere denuncia identificada?
    function needIdentificado() {
        if (isForcedIdent()) return true;
        if (isForcedAnon()) return false;
        // Opcional: depende del radio
        return $('input[name="anonimo"]:checked').val() === '0';
    }

    /** ---------- Inicialización general ---------- */
    function initializeComponents() {
        initializeSelect2();
        initializeFlatpickr();
        // Registrar validador de fecha D/M/Y ANTES de crear reglas
        registerDateDMYValidator();
        initializeDropzone();
        initializeValidation();
        initializeEventListeners();
        applyPolicyUI(); // Ajustar UI según política
    }

    function initializeSelect2() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Seleccione una opción',
            allowClear: true
        });
    }

    function initializeFlatpickr() {
        $('.flatpickr').flatpickr({
            dateFormat: 'd/m/Y',
            altInput: false,
            maxDate: 'today',
            defaultDate: 'today',
            locale: 'es'
        });
    }

    function initializeDropzone() {
        // Evitar múltiples instancias
        if (Dropzone.instances.length > 0) {
            Dropzone.instances.forEach(dz => dz.destroy());
        }

        new Dropzone('#dropzoneArchivos', {
            url: `${Server}denuncias/subir-anexo-public`,
            maxFiles: MAX_FILES,
            maxFilesize: MAX_FILE_SIZE_MB,
            acceptedFiles: 'image/*,application/pdf,video/mp4,video/webm,video/ogg',
            addRemoveLinks: true,
            dictDefaultMessage: 'Arrastra los archivos aquí para subirlos (imágenes, PDF, videos cortos)',
            dictRemoveFile: 'Eliminar archivo',
            dictFileTooBig: 'El archivo es muy grande ({{filesize}} MB). Máximo: {{maxFilesize}} MB.',
            dictInvalidFileType: 'Tipo de archivo no permitido.',
            init: function () {
                this.on('success', handleFileUploadSuccess);
                this.on('removedfile', handleFileRemove);
                this.on('error', function (file, message) {
                    console.error('Error en subida de archivo:', message);
                    Swal.fire('Error', message, 'error');
                });
            }
        });
    }

    /** ---------- Validador de fecha dd/mm/yyyy ---------- */
    function registerDateDMYValidator() {
        // Valida 31/12/2025, sin permitir fechas imposibles
        $.validator.addMethod(
            'dateDMY',
            function (value, element) {
                const v = (value || '').trim();
                if (v === '') return true; // required se controla aparte
                const parts = v.split('/');
                if (parts.length !== 3) return false;
                const d = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1; // 0..11
                const y = parseInt(parts[2], 10);
                if (isNaN(d) || isNaN(m) || isNaN(y)) return false;
                const dt = new Date(y, m, d);
                // Comprobar que Date no “corrigió” una fecha inválida
                if (dt.getFullYear() !== y || dt.getMonth() !== m || dt.getDate() !== d) return false;
                // No permitir fechas futuras (flatpickr ya lo limita, esto es defensa adicional)
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return dt <= today;
            },
            'Ingrese una fecha válida (dd/mm/aaaa).'
        );
    }

    /** ---------- Validación del formulario ---------- */
    function initializeValidation() {
        // Reglas dinámicas (solo agregamos si el campo existe)
        const rules = {
            id_sucursal: { required: true },
            // id_departamento es opcional en el formulario público (no agregamos required)
            fecha_incidente: { required: true, dateDMY: true }, // <<< usamos dateDMY
            como_se_entero: { required: true },
            area_incidente: { required: true, minlength: 5 },
            descripcion: { required: true, minlength: 10 },
            anonimo: { required: true },
            // Condicionales de identificación
            nombre_completo: {
                required: {
                    depends: function () {
                        return needIdentificado();
                    }
                },
                minlength: {
                    depends: function () {
                        return needIdentificado();
                    },
                    param: 3
                }
            },
            correo_electronico: {
                email: true // "al menos uno" (correo o teléfono) se valida en submit
            }
        };

        if ($('#categoria').length) rules.categoria = { required: true };
        if ($('#subcategoria').length) rules.subcategoria = { required: true };

        const messages = {
            id_sucursal: 'Seleccione una sucursal.',
            categoria: 'Seleccione una categoría.',
            subcategoria: 'Seleccione una subcategoría.',
            fecha_incidente: 'Ingrese una fecha válida.',
            como_se_entero: 'Seleccione cómo se enteró.',
            area_incidente: 'Ingrese el área donde sucedió (mínimo 5 caracteres).',
            descripcion: 'Describa la denuncia (mínimo 10 caracteres).',
            anonimo: 'Indique si la denuncia es anónima.',
            nombre_completo: {
                required: 'Indique su nombre.',
                minlength: 'Su nombre debe tener al menos 3 caracteres.'
            },
            correo_electronico: { email: 'Ingrese un correo válido.' }
        };

        $('#formCrearDenuncia').validate({
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            // Select2 deja el input oculto con .select2-hidden-accessible
            ignore: ':hidden:not(.select2-hidden-accessible)',
            rules,
            messages,
            errorPlacement: function (error, element) {
                if (element.hasClass('select2')) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            }
        });

        // Validar cuando cambie select2
        $('.select2').on('change', function () {
            $(this).valid();
        });
    }

    /** ---------- Listeners ---------- */
    function initializeEventListeners() {
        // Política opcional: mostrar/ocultar info adicional según radio
        $(document).on('change', 'input[name="anonimo"]', function () {
            applyPolicyUI();
        });

        // Cargar subcategorías si existe el selector #categoria
        if ($('#categoria').length) {
            $('#categoria').on('change', function () {
                loadSubcategorias($(this).val());
            });
        }

        // Cargar departamentos al cambiar sucursal
        $('#id_sucursal').on('change', function () {
            const sucursalId = $(this).val();
            loadDepartamentos(sucursalId);
        });

        // Audio
        $('#startRecording').on('click', startAudioRecording);
        $('#stopRecording').on('click', stopAudioRecording);

        // Envío del formulario
        $('#formCrearDenuncia').on('submit', handleFormSubmit);
    }

    /** ---------- UI según política ---------- */
    function applyPolicyUI() {
        const $info = $('#infoAdicional');
        const $grupoAnonimo = $('#grupoAnonimo');
        const $inputsIdent = $('#nombre_completo, #correo_electronico, #telefono, #id_sexo');

        if (isForcedAnon()) {
            // Forzar anónimas: ocultar selector, ocultar y limpiar identificación
            $grupoAnonimo.hide();
            $info.hide().attr('aria-hidden', 'true');
            $inputsIdent.val('').trigger('change');
            $inputsIdent.prop('disabled', true);
        } else if (isForcedIdent()) {
            // Forzar identificadas: ocultar selector, mostrar identificación
            $grupoAnonimo.hide();
            $info.show().attr('aria-hidden', 'false');
            $inputsIdent.prop('disabled', false);
        } else {
            // Opcional
            $grupoAnonimo.show();
            const anon = $('input[name="anonimo"]:checked').val();
            if (anon === '0') {
                $info.show().attr('aria-hidden', 'false');
                $inputsIdent.prop('disabled', false);
            } else {
                $info.hide().attr('aria-hidden', 'true');
                $inputsIdent.val('').trigger('change');
                $inputsIdent.prop('disabled', false); // se envían vacíos si están ocultos
            }
        }
    }

    /** ---------- Cargas dinámicas ---------- */
    function loadSubcategorias(categoriaId) {
        if (!categoriaId) {
            $('#subcategoria').empty().append('<option value="">Seleccione una subcategoría</option>');
            return;
        }
        $.ajax({
            url: `${Server}categorias/listarSubcategorias`,
            method: 'GET',
            data: { id_categoria: categoriaId },
            success: function (data) {
                const $sub = $('#subcategoria');
                $sub.empty().append('<option value="">Seleccione una subcategoría</option>');
                data.forEach(sc => $sub.append(`<option value="${sc.id}">${sc.nombre}</option>`));
                $sub.trigger('change');
            },
            error: function () {
                $('#subcategoria').html('<option value="">Error al cargar subcategorías</option>');
            }
        });
    }

    function loadDepartamentos(sucursalId) {
        if (!sucursalId) {
            $('#id_departamento').empty().append('<option value="">Seleccione un departamento</option>');
            return;
        }
        $.ajax({
            url: `${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
            method: 'GET',
            success: function (data) {
                const $dep = $('#id_departamento');
                $dep.empty().append('<option value="">Seleccione un departamento</option>');
                data.forEach(dep => $dep.append(`<option value="${dep.id}">${dep.nombre}</option>`));
                $dep.trigger('change');
            },
            error: function () {
                $('#id_departamento').html('<option value="">Error al cargar departamentos</option>');
            }
        });
    }

    /** ---------- Dropzone handlers ---------- */
    function handleFileUploadSuccess(file, response) {
        $('<input>')
            .attr({
                type: 'hidden',
                name: 'archivos[]',
                value: 'uploads/denuncias/' + response.filename
            })
            .appendTo('#formCrearDenuncia');
    }

    function handleFileRemove(file) {
        if (!file || !file.upload || !file.upload.filename) return;
        const filename = file.upload.filename;
        $(`#formCrearDenuncia input[value="uploads/denuncias/${filename}"]`).remove();
    }

    /** ---------- Audio (MediaRecorder) ---------- */
    function checkMicrophoneSupport() {
        return navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    }

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

                mediaRecorder.ondataavailable = e => {
                    audioChunks.push(e.data);
                };
                mediaRecorder.onstop = handleAudioRecordingStop;
            })
            .catch(err => {
                console.error('Error al acceder al micrófono:', err);
                Swal.fire('Error', 'Error al acceder al micrófono. Verifique los permisos.', 'error');
            });
    }

    function stopAudioRecording() {
        if (mediaRecorder) {
            mediaRecorder.stop();
            $('#startRecording').attr('disabled', false);
            $('#stopRecording').attr('disabled', true);
        }
    }

    function handleAudioRecordingStop() {
        audioBlob = new Blob(audioChunks, { type: 'audio/wav' });

        const audioSizeMB = audioBlob.size / 1024 / 1024;
        if (audioSizeMB > MAX_AUDIO_SIZE_MB) {
            Swal.fire('Error', `El archivo de audio supera el tamaño máximo permitido de ${MAX_AUDIO_SIZE_MB} MB.`, 'error');
            $('#audioPlayback').hide();
            $('#audio_input').val('');
            return;
        }

        const audioURL = URL.createObjectURL(audioBlob);
        $('#audioPlayback').attr('src', audioURL).show();

        const file = new File([audioBlob], 'grabacion_audio.wav', { type: 'audio/wav', lastModified: Date.now() });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('audio_input').files = dataTransfer.files;
    }

    /** ---------- Submit ---------- */
    function handleFormSubmit(e) {
        e.preventDefault();

        const $form = $('#formCrearDenuncia');
        if (!$form.valid()) return;

        // Si es identificada (forzada u opcional), requerir al menos correo o teléfono
        if (needIdentificado()) {
            const correo = ($('#correo_electronico').val() || '').trim();
            const tel = ($('#telefono').val() || '').trim();
            if (correo === '' && tel === '') {
                Swal.fire('Faltan datos', 'Ingrese al menos correo o teléfono para una denuncia identificada.', 'warning');
                return;
            }
        }

        const formData = new FormData($form[0]);

        $.ajax({
            url: `${Server}denuncias/guardar-public`,
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
                        window.location = `${Server}c/${slug}/seguimiento-denuncia?folio=${response.folio}`;
                    });
                } else {
                    Swal.fire('Error', response.message || 'Ocurrió un error al guardar la denuncia. Intenta de nuevo.', 'error');
                }
            },
            error: function (xhr) {
                console.error('Error al guardar la denuncia.', xhr);
                let msg = 'Ocurrió un error al guardar la denuncia. Intenta de nuevo.';
                try {
                    msg = JSON.parse(xhr.responseText).message || msg;
                } catch (e) {}
                Swal.fire('Error', msg, 'error');
            }
        });
    }

    // GO!
    initializeComponents();
});
