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

    // --------- Init: flatpickr (simple) ----------
    const fpStart = $fi.flatpickr({ dateFormat: 'Y-m-d' });
    const fpEnd = $ff.flatpickr({ dateFormat: 'Y-m-d' });

    // --------- Validación ----------
    if ($form.length) {
        $form.validate({
            rules: {
                id_cliente: { required: true },
                tipo_reporte: { required: true },
                fecha_inicio: { required: true, dateISO: true },
                fecha_fin: { required: true, dateISO: true }
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
                $('[data-bs-toggle="tooltip"]').each((_, el) => new bootstrap.Tooltip(el));
            })
            .fail(() => {
                $periodo.html('<option value="">No disponible</option>');
            });
    }

    // Cambios que disparan la recarga de periodos
    $tipo.on('change', cargarPeriodos);
    $('#btnRefrescarPeriodos').on('click', cargarPeriodos);

    // Selección de periodo → llena fechas
    $periodo.on('change', function () {
        const v = $(this).val();
        if (!v) return;
        const [ini, fin] = v.split('|');
        fpStart.setDate(ini, true, 'Y-m-d');
        fpEnd.setDate(fin, true, 'Y-m-d');
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
            $('.select2').val(null).trigger('change');
        }
        $tipo.val('mensual').trigger('change');
        fpStart.clear();
        fpEnd.clear();
        hideResult();
    });

    // --------- Generar ----------
    function generar() {
        const data = $form.serialize();

        lockUI(true);
        hideResult();

        $.ajax({
            url: `${Server}reportes-ia/procesar`,
            method: 'POST',
            data,
            dataType: 'json'
        })
            .done(res => {
                if (res.success) {
                    showSuccess(`
          <i class="fas fa-check-circle me-2"></i>${res.message || 'Reporte generado correctamente.'}
          <div class="mt-2 small">
            <span class="badge bg-light text-dark me-1"><i class="fas fa-money-bill-wave me-1"></i>Costo estimado: <strong>$${res.costo_estimado ?? '0.000000'}</strong></span>
            <span class="badge bg-light text-dark me-1"><i class="fas fa-ticket-alt me-1"></i>Tokens: <strong>${res.tokens_usados ?? 0}</strong></span>
            <span class="badge bg-light text-dark"><i class="fas fa-stopwatch me-1"></i>Tiempo: <strong>${res.tiempo ?? '—'}</strong></span>
          </div>
          <a href="${Server}reportes-ia/ver/${res.id_reporte}" class="btn btn-sm btn-outline-primary mt-2">
            <i class="fas fa-eye me-1"></i> Ver reporte
          </a>
        `);
                    // Mantener selección de cliente; limpiar fechas
                    fpStart.clear();
                    fpEnd.clear();
                    $periodo.val('');
                } else if (res.existe) {
                    showWarning(`
          <i class="fas fa-exclamation-triangle me-2"></i>${res.message || 'Ya existe un reporte para este periodo.'}
          <div class="mt-2">
            <a href="${Server}reportes-ia" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-list me-1"></i> Ir al listado
            </a>
          </div>
        `);
                } else {
                    showError(`<i class="fas fa-times-circle me-2"></i>${res.message || 'Error al generar el reporte.'}`);
                }
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Error interno del servidor.';
                showError(`<i class="fas fa-bug me-2"></i>${msg}`);
            })
            .always(() => lockUI(false));
    }

    // --------- UI helpers ----------
    function lockUI(state) {
        $btnGenerar.prop('disabled', state).html(state ? '<i class="fas fa-spinner fa-spin me-1"></i> Generando...' : '<i class="fas fa-cogs me-1"></i> Generar');
        $progress.toggleClass('d-none', !state);
    }

    function hideResult() {
        $resultado.addClass('d-none').removeClass('alert alert-success alert-danger alert-warning').empty();
    }
    function showSuccess(html) {
        $resultado.removeClass('d-none alert-danger alert-warning').addClass('alert alert-success').html(html);
    }
    function showError(html) {
        $resultado.removeClass('d-none alert-success alert-warning').addClass('alert alert-danger').html(html);
    }
    function showWarning(html) {
        $resultado.removeClass('d-none alert-success alert-danger').addClass('alert alert-warning').html(html);
    }

    // --------- Start ----------
    cargarPeriodos();
})();
