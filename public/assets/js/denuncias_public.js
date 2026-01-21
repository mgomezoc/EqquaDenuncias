/**
 * Script para manejar la lógica del formulario de denuncias públicas.
 * - Política de anonimato por cliente (POLITICA: 0=opcional, 1=forzar anónimas, 2=forzar identificadas)
 * - Tipo de denunciante público configurable:
 *      MOSTRAR_TIPO_DENUNCIANTE_PUBLICO
 *      TIPOS_PERMITIDOS_PUBLICO (array)
 *      TIPO_PUBLICO_DEFAULT (string)
 * - Validación con jquery-validate
 * - Carga dinámica de departamentos
 * - Dropzone para archivos
 * - Grabación de audio con MediaRecorder
 */

Dropzone.autoDiscover = false;

$(document).ready(function () {
    const MAX_FILES = 5;
    const MAX_FILE_SIZE_MB = 10;
    const MAX_AUDIO_SIZE_MB = 5;

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

    function needIdentificado() {
        if (isForcedIdent()) return true;
        if (isForcedAnon()) return false;
        return $('input[name="anonimo"]:checked').val() === '0';
    }

    /** ---------- Helpers tipo denunciante público ---------- */
    function getPermitidosPublico() {
        if (Array.isArray(window.TIPOS_PERMITIDOS_PUBLICO) && window.TIPOS_PERMITIDOS_PUBLICO.length) {
            return window.TIPOS_PERMITIDOS_PUBLICO.map(x => (x || '').toString().toLowerCase().trim());
        }
        return ['cliente', 'colaborador', 'proveedor'];
    }

    function getDefaultPublico() {
        const def = (window.TIPO_PUBLICO_DEFAULT || 'colaborador').toString().toLowerCase().trim();
        const permitidos = getPermitidosPublico();
        if (permitidos.includes(def)) return def;
        return permitidos[0] || 'colaborador';
    }

    function ensureTipoDenuncianteValue() {
        const permitidos = getPermitidosPublico();
        const def = getDefaultPublico();

        const $sel = $('#tipo_denunciante_publico');
        if ($sel.length) {
            const v = ($sel.val() || '').toString().toLowerCase().trim();
            if (!permitidos.includes(v)) {
                $sel.val(def).trigger('change');
            }
            return;
        }

        // Si NO existe el select (porque está oculto por configuración),
        // aseguramos un hidden con el valor correcto antes de enviar.
        let $hidden = $('#formCrearDenuncia input[name="tipo_denunciante_publico"]');
        if (!$hidden.length) {
            $hidden = $('<input>', { type: 'hidden', name: 'tipo_denunciante_publico' }).appendTo('#formCrearDenuncia');
        }
        $hidden.val(def);
    }

    /** ---------- Inicialización general ---------- */
    function initializeComponents() {
        initializeSelect2();
        initializeFlatpickr();
        registerDateDMYValidator();
        initializeDropzone();
        initializeValidation();
        initializeEventListeners();
        applyPolicyUI();
        ensureTipoDenuncianteValue(); // <<< NUEVO
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
        $.validator.addMethod(
            'dateDMY',
            function (value) {
                const v = (value || '').trim();
                if (v === '') return true;
                const parts = v.split('/');
                if (parts.length !== 3) return false;
                const d = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const y = parseInt(parts[2], 10);
                if (isNaN(d) || isNaN(m) || isNaN(y)) return false;
                const dt = new Date(y, m, d);
                if (dt.getFullYear() !== y || dt.getMonth() !== m || dt.getDate() !== d) return false;
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return dt <= today;
            },
            'Ingrese una fecha válida (dd/mm/aaaa).'
        );
    }

    /** ---------- Validación del formulario ---------- */
    function initializeValidation() {
        const rules = {
            id_sucursal: { required: true },
            fecha_incidente: { required: true, dateDMY: true },
            como_se_entero: { required: true },
            area_incidente: { required: true, minlength: 5 },
            descripcion: { required: true, minlength: 10 },
            anonimo: { required: true },

            nombre_completo: {
                required: { depends: () => needIdentificado() },
                minlength: { depends: () => needIdentificado(), param: 3 }
            },
            correo_electronico: { email: true }
        };

        // Tipo de denunciante público:
        if ($('#tipo_denunciante_publico').length) {
            rules.tipo_denunciante_publico = {
                required: true,
                // defensa: solo permitir valores del arreglo
                normalizer: function (value) {
                    return (value || '').toString().toLowerCase().trim();
                }
            };

            // Método custom para "permitidos"
            $.validator.addMethod(
                'inPermitidosPublico',
                function (value) {
                    const v = (value || '').toString().toLowerCase().trim();
                    return getPermitidosPublico().includes(v);
                },
                'Seleccione un tipo de denunciante válido.'
            );

            rules.tipo_denunciante_publico.inPermitidosPublico = true;
        }

        const messages = {
            id_sucursal: 'Seleccione una sucursal.',
            tipo_denunciante_publico: 'Seleccione el tipo de denunciante.',
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

        $('.select2').on('change', function () {
            $(this).valid();
        });
    }

    /** ---------- Listeners ---------- */
    function initializeEventListeners() {
        $(document).on('change', 'input[name="anonimo"]', function () {
            applyPolicyUI();
        });

        // Si existe el select, asegurar valor permitido al cambiar
        $(document).on('change', '#tipo_denunciante_publico', function () {
            ensureTipoDenuncianteValue();
        });

        $('#id_sucursal').on('change', function () {
            loadDepartamentos($(this).val());
        });

        $('#startRecording').on('click', startAudioRecording);
        $('#stopRecording').on('click', stopAudioRecording);

        $('#formCrearDenuncia').on('submit', handleFormSubmit);
    }

    /** ---------- UI según política ---------- */
    function applyPolicyUI() {
        const $info = $('#infoAdicional');
        const $grupoAnonimo = $('#grupoAnonimo');
        const $inputsIdent = $('#nombre_completo, #correo_electronico, #telefono, #id_sexo');

        if (isForcedAnon()) {
            $grupoAnonimo.hide();
            $info.hide().attr('aria-hidden', 'true');
            $inputsIdent.val('').trigger('change');
            $inputsIdent.prop('disabled', true);
        } else if (isForcedIdent()) {
            $grupoAnonimo.hide();
            $info.show().attr('aria-hidden', 'false');
            $inputsIdent.prop('disabled', false);
        } else {
            $grupoAnonimo.show();
            const anon = $('input[name="anonimo"]:checked').val();
            if (anon === '0') {
                $info.show().attr('aria-hidden', 'false');
                $inputsIdent.prop('disabled', false);
            } else {
                $info.hide().attr('aria-hidden', 'true');
                $inputsIdent.val('').trigger('change');
                $inputsIdent.prop('disabled', false);
            }
        }
    }

    /** ---------- Cargas dinámicas ---------- */
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

                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
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
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('audio_input').files = dt.files;
    }

    /** ---------- Submit ---------- */
    function handleFormSubmit(e) {
        e.preventDefault();

        ensureTipoDenuncianteValue(); // <<< NUEVO: setea/valida antes de enviar

        const $form = $('#formCrearDenuncia');
        if (!$form.valid()) return;

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
