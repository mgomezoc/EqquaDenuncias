/* global Server, Handlebars, Swal, bootstrap, Chart */
let tplAccionesReportes;

function fechaFormatter(value) {
    if (!value) return '';
    return value.replace('T', ' ').substring(0, 19);
}

function badgeClassEstado(estado) {
    switch ((estado || '').toLowerCase()) {
        case 'generado':
            return 'bg-secondary';
        case 'revisado':
            return 'bg-info';
        case 'publicado':
            return 'bg-success';
        case 'archivado':
            return 'bg-dark';
        default:
            return 'bg-light text-dark';
    }
}

function estadoFormatter(value) {
    return `<span class="badge ${badgeClassEstado(value)}">${value || ''}</span>`;
}

function tipoFormatter(value) {
    const map = { mensual: 'primary', trimestral: 'warning', semestral: 'purple' };
    const cls = map[(value || '').toLowerCase()] || 'secondary';
    const txt = (value || '').charAt(0).toUpperCase() + (value || '').slice(1);
    return `<span class="badge bg-${cls}">${txt}</span>`;
}

function riesgoFormatter(value) {
    if (!value && value !== 0) return '<span class="text-muted">-</span>';
    const v = Number(value);
    const cls = v >= 7 ? 'danger' : v >= 4 ? 'warning' : 'success';
    return `<span class="badge bg-${cls}-subtle text-${cls} fw-bold">${v}/10</span>`;
}

function operateFormatter(value, row) {
    if (!tplAccionesReportes) tplAccionesReportes = $('#tplAccionesReportes').html();
    return Handlebars.compile(tplAccionesReportes)(row);
}

window.operateEvents = {
    'click .view': function (e, value, row) {
        window.location = `${Server}reportes-ia/ver/${row.id}`;
    },
    'click .pdf': function (e, value, row) {
        window.open(`${Server}reportes-ia/descargar/${row.id}`, '_blank');
    },
    'click .estado': async function (e, value, row) {
        cambiarEstadoPrompt(row.id, row.estado);
    },
    'click .eliminar': async function (e, value, row) {
        const r = await Swal.fire({
            title: '¿Eliminar reporte?',
            text: `ID ${row.id} – ${row.periodo_nombre}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (r.isConfirmed) {
            $.post(`${Server}reportes-ia/eliminar`, { id_reporte: row.id })
                .done(() => {
                    $('.table').bootstrapTable('refresh');
                    Swal.fire('Eliminado', 'El reporte fue eliminado.', 'success');
                })
                .fail(() => Swal.fire('Error', 'No se pudo eliminar.', 'error'));
        }
    }
};

function bindCambiarEstadoReporte(id) {
    $('#btnCambiarEstado').on('click', () => cambiarEstadoPrompt(id));
}

async function cambiarEstadoPrompt(id, estadoActual) {
    const { value: estado } = await Swal.fire({
        title: 'Cambiar estado',
        input: 'select',
        inputOptions: {
            generado: 'Generado',
            revisado: 'Revisado',
            publicado: 'Publicado',
            archivado: 'Archivado'
        },
        inputValue: (estadoActual || 'generado').toLowerCase(),
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar'
    });
    if (estado) {
        $.post(`${Server}reportes-ia/cambiar-estado`, { id_reporte: id, estado })
            .done(() => Swal.fire('Listo', 'Estado actualizado', 'success').then(() => location.reload()))
            .fail(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
    }
}

function pintarBadgeEstado(selector, estado) {
    $(selector)
        .removeClass()
        .addClass(`badge ${badgeClassEstado(estado)}`)
        .text(estado);
}

/* ====== Generar ====== */
function initGenerarReporte() {
    // Flatpickr
    $('.flatpickr').flatpickr({ dateFormat: 'Y-m-d' });

    // Periodos sugeridos
    function cargarPeriodos() {
        const tipo = $('#tipo_reporte').val();
        const fechaRef = new Date().toISOString().substring(0, 10);
        $('#periodo_rapido').html('<option value="">Cargando...</option>');
        $.get(`${Server}reportes-ia/periodos`, { tipo_reporte: tipo, fecha_referencia: fechaRef })
            .done(resp => {
                const $sel = $('#periodo_rapido').empty().append('<option value="">Selecciona...</option>');
                (resp.periodos || []).forEach(p => {
                    $sel.append(`<option value="${p.fecha_inicio}|${p.fecha_fin}">${p.nombre}</option>`);
                });
            })
            .fail(() => $('#periodo_rapido').html('<option value="">—</option>'));
    }
    cargarPeriodos();
    $('#tipo_reporte').on('change', cargarPeriodos);

    $('#periodo_rapido').on('change', function () {
        const v = $(this).val();
        if (!v) return;
        const [ini, fin] = v.split('|');
        $('#fecha_inicio').val(ini);
        $('#fecha_fin').val(fin);
    });

    // jQuery Validate
    $('#frmGenerarReporte').validate({
        ignore: [],
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            id_cliente: { required: true, digits: true },
            tipo_reporte: { required: true },
            fecha_inicio: { required: true, dateISO: true },
            fecha_fin: { required: true, dateISO: true }
        },
        messages: {
            id_cliente: 'Seleccione un cliente.',
            tipo_reporte: 'Seleccione el tipo.',
            fecha_inicio: 'Ingrese una fecha válida (YYYY-MM-DD).',
            fecha_fin: 'Ingrese una fecha válida (YYYY-MM-DD).'
        },
        submitHandler: function (form) {
            const $btn = $(form).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generando...');
            const data = $(form).serialize();

            $.post(`${Server}reportes-ia/procesar`, data)
                .done(resp => {
                    const $r = $('#resultadoGeneracion').removeClass('d-none alert-danger alert-success');
                    if (resp.success) {
                        $r.addClass('alert-success').html(`<b>¡Reporte generado!</b> ID: ${resp.id_reporte}<br>Tokens: ${resp.tokens_usados} | Costo: $${resp.costo_estimado} | Tiempo: ${resp.tiempo}`);
                        setTimeout(() => (window.location = `${Server}reportes-ia/ver/${resp.id_reporte}`), 600);
                    } else if (resp.existe) {
                        $r.addClass('alert-danger').html(resp.message || 'Ya existe un reporte para el periodo.');
                    } else {
                        $r.addClass('alert-danger').text(resp.message || 'Error al generar.');
                    }
                })
                .fail(() => {
                    $('#resultadoGeneracion').removeClass('d-none').addClass('alert alert-danger').text('Error interno.');
                })
                .always(() => $btn.prop('disabled', false).html('<i class="fas fa-cogs me-1"></i> Generar'));
        }
    });
}

/* ====== Ver (gráficas) ====== */
function chartOrEmpty(ctxId, labels, values, type) {
    const ctx = document.getElementById(ctxId);
    if (!ctx) return;
    if (!labels || !labels.length) {
        ctx.parentElement.innerHTML = '<div class="text-muted small">Sin datos suficientes.</div>';
        return;
    }
    new Chart(ctx, {
        type: type || 'bar',
        data: { labels, datasets: [{ label: 'Total', data: values }] },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: type === 'pie' || type === 'doughnut' ? {} : { y: { beginAtZero: true } }
        }
    });
}

function initVerReporte(metricas) {
    if (!metricas || typeof metricas !== 'object') return;
    const dist = (arr, campoLabel, campoVal = 'total') => {
        if (!Array.isArray(arr)) return { labels: [], values: [] };
        return { labels: arr.map(x => x[campoLabel] || 'N/D'), values: arr.map(x => Number(x[campoVal] || 0)) };
    };
    const suc = dist(metricas.distribucion_sucursal, 'sucursal');
    chartOrEmpty('chSucursales', suc.labels, suc.values, 'bar');
    const cat = dist(metricas.distribucion_categoria, 'categoria');
    chartOrEmpty('chCategorias', cat.labels, cat.values, 'bar');
    const est = dist(metricas.distribucion_estatus, 'estatus');
    chartOrEmpty('chEstatus', est.labels, est.values, 'doughnut');
    const med = dist(metricas.distribucion_medio, 'medio');
    chartOrEmpty('chMedios', med.labels, med.values, 'doughnut');
}

/* ====== Estadísticas ====== */
function pintarGraficaStats(porTipo, porEstado) {
    const toArr = obj => {
        const labels = Object.keys(obj || {});
        const values = labels.map(k => Number((obj || {})[k] || 0));
        return { labels, values };
    };
    const t = toArr(porTipo);
    chartOrEmpty(
        'chPorTipo',
        t.labels.map(x => x.toUpperCase()),
        t.values,
        'doughnut'
    );
    const e = toArr(porEstado);
    chartOrEmpty(
        'chPorEstado',
        e.labels.map(x => x.toUpperCase()),
        e.values,
        'bar'
    );
}
