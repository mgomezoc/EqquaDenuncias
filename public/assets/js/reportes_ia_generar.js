/* ==========================================================
 * GENERAR REPORTE IA (JS dedicado a la vista generar.php)
 * ========================================================== */

/* global Server, $, Swal, bootstrap */

(function () {
    const $form = $('#frmGenerarReporte');
    const $btnGenerar = $('#btnGenerar');
    const $btnLimpiar = $('#btnLimpiar');
    const $resultado = $('#resultadoGeneracion');
    const $progress = $('#progreso');
    const $tipo = $('#tipo_reporte');
    const $periodo = $('#periodo_rapido');
    const $fi = $('#fecha_inicio');
    const $ff = $('#fecha_fin');

    // --------- Init: flatpickr ----------
    const fpConfig = {
        dateFormat: 'Y-m-d',
        locale: 'es'
    };
    const fpStart = $fi.length ? flatpickr($fi[0], fpConfig) : null;
    const fpEnd = $ff.length ? flatpickr($ff[0], fpConfig) : null;

    // --------- Validación ----------
    if ($form.length && typeof $.fn.validate === 'function') {
        $form.validate({
            rules: {
                id_cliente: { required: true },
                tipo_reporte: { required: true },
                fecha_inicio: { required: true },
                fecha_fin: { required: true }
            },
            messages: {
                id_cliente: 'Selecciona un cliente',
                tipo_reporte: 'Selecciona un tipo de reporte',
                fecha_inicio: 'Ingresa la fecha de inicio',
                fecha_fin: 'Ingresa la fecha de fin'
            },
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                if (element.hasClass('select2')) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function () {
                generar();
            }
        });
    }

    // --------- Cargar periodos sugeridos ----------
    function cargarPeriodos() {
        const tipo = $tipo.val() || 'mensual';
        $periodo.html('<option value="">Cargando...</option>');
        
        $.getJSON(`${Server}reportes-ia/periodos`, { tipo_reporte: tipo })
            .done(res => {
                const arr = res?.periodos || [];
                let html = '<option value="">Selecciona...</option>';
                arr.forEach(p => {
                    const v = `${p.fecha_inicio}|${p.fecha_fin}|${p.nombre}`;
                    html += `<option value="${v}">${p.nombre}</option>`;
                });
                $periodo.html(html);
            })
            .fail(() => {
                $periodo.html('<option value="">No disponible</option>');
            });
    }

    // Cargar periodos al inicio
    if ($tipo.length) {
        cargarPeriodos();
    }

    // Cambios que disparan la recarga de periodos
    $tipo.on('change', cargarPeriodos);
    $('#btnRefrescarPeriodos').on('click', cargarPeriodos);

    // Selección de periodo → llena fechas
    $periodo.on('change', function () {
        const v = $(this).val();
        if (!v) return;
        const partes = v.split('|');
        if (partes.length >= 2) {
            if (fpStart) fpStart.setDate(partes[0], true, 'Y-m-d');
            if (fpEnd) fpEnd.setDate(partes[1], true, 'Y-m-d');
        }
    });

    // Atajos laterales
    $('.atajo').on('click', function () {
        const t = $(this).data('tipo');
        $tipo.val(t).trigger('change');
        $('html,body').animate({ scrollTop: $periodo.offset().top - 80 }, 250);
    });

    // --------- Limpiar ----------
    $btnLimpiar.on('click', function () {
        // No reset a select2 con cliente fijo
        if ($('[name="id_cliente"][type="hidden"]').length === 0) {
            $('#id_cliente').val(null).trigger('change');
        }
        $tipo.val('mensual').trigger('change');
        if (fpStart) fpStart.clear();
        if (fpEnd) fpEnd.clear();
        $periodo.val('');
        ocultarResultado();
    });

    // --------- Generar ----------
    function generar() {
        const data = $form.serialize();

        bloquearUI(true);
        ocultarResultado();

        $.ajax({
            url: `${Server}reportes-ia/procesar`,
            method: 'POST',
            data: data,
            dataType: 'json'
        })
            .done(res => {
                if (res.success) {
                    mostrarExito(`
                        <i class="fas fa-check-circle me-2"></i>${res.message || 'Reporte generado correctamente.'}
                        <div class="mt-2 small">
                            <span class="badge bg-light text-dark me-1">
                                <i class="fas fa-money-bill-wave me-1"></i>Costo: <strong>$${res.costo_estimado ?? '0.000000'}</strong>
                            </span>
                            <span class="badge bg-light text-dark me-1">
                                <i class="fas fa-ticket-alt me-1"></i>Tokens: <strong>${res.tokens_usados ?? 0}</strong>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-stopwatch me-1"></i>Tiempo: <strong>${res.tiempo ?? '—'}</strong>
                            </span>
                        </div>
                        <a href="${Server}reportes-ia/ver/${res.id_reporte}" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-eye me-1"></i> Ver reporte
                        </a>
                    `);
                    // Limpiar fechas pero mantener cliente
                    if (fpStart) fpStart.clear();
                    if (fpEnd) fpEnd.clear();
                    $periodo.val('');
                } else if (res.existe) {
                    mostrarAdvertencia(`
                        <i class="fas fa-exclamation-triangle me-2"></i>${res.message || 'Ya existe un reporte para este periodo.'}
                        <div class="mt-2">
                            <a href="${Server}reportes-ia" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> Ir al listado
                            </a>
                        </div>
                    `);
                } else {
                    mostrarError(`<i class="fas fa-times-circle me-2"></i>${res.message || 'Error al generar el reporte.'}`);
                }
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Error interno del servidor.';
                mostrarError(`<i class="fas fa-times-circle me-2"></i>${msg}`);
            })
            .always(() => {
                bloquearUI(false);
            });
    }

    // --------- Helpers UI ----------
    function bloquearUI(bloquear) {
        if (bloquear) {
            $btnGenerar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando...');
            $progress.removeClass('d-none');
        } else {
            $btnGenerar.prop('disabled', false).html('<i class="fas fa-cogs me-1"></i> Generar');
            $progress.addClass('d-none');
        }
    }

    function ocultarResultado() {
        $resultado.addClass('d-none').removeClass('alert-success alert-danger alert-warning').html('');
    }

    function mostrarExito(html) {
        $resultado.removeClass('d-none alert-danger alert-warning').addClass('alert alert-success').html(html);
    }

    function mostrarError(html) {
        $resultado.removeClass('d-none alert-success alert-warning').addClass('alert alert-danger').html(html);
    }

    function mostrarAdvertencia(html) {
        $resultado.removeClass('d-none alert-success alert-danger').addClass('alert alert-warning').html(html);
    }

    // --------- Init Select2 ----------
    $(function () {
        if (typeof $.fn.select2 === 'function') {
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function () {
                    return $(this).data('placeholder') || 'Selecciona...';
                }
            });
        }
    });

})();
