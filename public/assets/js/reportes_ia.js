/* ====================================================================
 * REPORTES IA - JavaScript Mejorado
 * ==================================================================== */

/* global Server, Handlebars, Swal, bootstrap, Chart */
let tplAccionesReportes;

/* ====== FORMATTERS ====== */
function fechaFormatter(value) {
    if (!value) return '<span class="text-muted">-</span>';
    const v = String(value).replace('T', ' ');
    return v.substring(0, 16);
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
    if (!value) return '<span class="badge bg-light text-dark">Sin estado</span>';
    const cls = badgeClassEstado(value);
    const txt = value.charAt(0).toUpperCase() + value.slice(1);
    return `<span class="badge ${cls}">${txt}</span>`;
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
    let cls = 'success';
    if (v >= 7) cls = 'danger';
    else if (v >= 4) cls = 'warning';
    return `<span class="badge bg-${cls}-subtle text-${cls} fw-bold">${v}/10</span>`;
}

function operateFormatter(value, row) {
    if (!tplAccionesReportes) tplAccionesReportes = $('#tplAccionesReportes').html();
    return Handlebars.compile(tplAccionesReportes)(row);
}

/* ====== EVENTOS ====== */
window.operateEvents = {
    'click .view': function (e, value, row) {
        e.preventDefault();
        window.location = `${Server}reportes-ia/ver/${row.id}`;
    },

    'click .pdf': function (e, value, row) {
        e.preventDefault();
        window.open(`${Server}reportes-ia/descargar/${row.id}`, '_blank');
    },

    'click .estado': function (e, value, row) {
        e.preventDefault();
        cambiarEstadoPrompt(row.id, row.estado);
    },

    'click .eliminar': async function (e, value, row) {
        e.preventDefault();
        const result = await Swal.fire({
            title: '¿Eliminar reporte?',
            html: `<p>ID <strong>${row.id}</strong> – ${row.periodo_nombre}</p>
             <p class="text-danger mb-0">Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            $.post(`${Server}reportes-ia/eliminar`, { id_reporte: row.id })
                .done(() => {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'El reporte fue eliminado correctamente.',
                        icon: 'success',
                        timer: 1600,
                        showConfirmButton: false,
                        didClose: () => $('#tablaReportes').bootstrapTable('refresh')
                    });
                })
                .fail(xhr => {
                    const msg = xhr.responseJSON?.message || 'No se pudo eliminar el reporte.';
                    Swal.fire('Error', msg, 'error');
                });
        }
    }
};

/* ====== CAMBIAR ESTADO ====== */
async function cambiarEstadoPrompt(idReporte, estadoActual) {
    const estados = {
        generado: 'Generado',
        revisado: 'Revisado',
        publicado: 'Publicado',
        archivado: 'Archivado'
    };

    let optionsHtml = '';
    for (const [key, label] of Object.entries(estados)) {
        const selected = key === estadoActual ? 'selected' : '';
        optionsHtml += `<option value="${key}" ${selected}>${label}</option>`;
    }

    const { value: nuevoEstado } = await Swal.fire({
        title: 'Cambiar Estado',
        html: `
      <div class="mb-3">
        <label class="form-label">Seleccione el nuevo estado:</label>
        <select id="swal-estado" class="form-select">
          ${optionsHtml}
        </select>
      </div>
    `,
        showCancelButton: true,
        confirmButtonText: 'Cambiar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => document.getElementById('swal-estado').value
    });

    if (nuevoEstado && nuevoEstado !== estadoActual) {
        $.post(`${Server}reportes-ia/cambiar-estado`, {
            id_reporte: idReporte,
            estado: nuevoEstado
        })
            .done(() => {
                Swal.fire({
                    title: 'Estado actualizado',
                    text: `El reporte ahora está como "${estados[nuevoEstado]}".`,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    didClose: () => $('#tablaReportes').bootstrapTable('refresh')
                });
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'No se pudo cambiar el estado.';
                Swal.fire('Error', msg, 'error');
            });
    }
}

/* ====== GENERAR REPORTE ====== */
function initGenerarReporte() {
    const $form = $('#formGenerarReporte');
    if (!$form.length) return;

    $form.validate({
        rules: {
            id_cliente: { required: true },
            tipo_reporte: { required: true },
            fecha_inicio: { required: true },
            fecha_fin: { required: true }
        },
        submitHandler: () => procesarGeneracionReporte()
    });
}

function procesarGeneracionReporte() {
    const $form = $('#formGenerarReporte');
    const $btn = $('#btnGenerar');
    const $resultado = $('#resultadoGeneracion');

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando...');
    $resultado.addClass('d-none').removeClass('alert alert-success alert-danger').text('');

    $.ajax({
        url: `${Server}reportes-ia/procesar`,
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    })
        .done(res => {
            if (res.success) {
                $resultado.removeClass('d-none').addClass('alert alert-success').html(`
            <i class="fas fa-check-circle me-2"></i>${res.message}
            <br>
            <a href="${Server}reportes-ia/ver/${res.id_reporte}" class="alert-link">
              Ver reporte <i class="fas fa-arrow-right"></i>
            </a>
          `);
                $form[0].reset();
                $('.select2').val(null).trigger('change');
            } else {
                $resultado.removeClass('d-none').addClass('alert alert-danger').html(`<i class="fas fa-exclamation-triangle me-2"></i>${res.message}`);
            }
        })
        .fail(xhr => {
            const msg = xhr.responseJSON?.message || 'Error interno del servidor.';
            $resultado.removeClass('d-none').addClass('alert alert-danger').html(`<i class="fas fa-exclamation-triangle me-2"></i>${msg}`);
        })
        .always(() => $btn.prop('disabled', false).html('<i class="fas fa-cogs me-1"></i> Generar'));
}

/* ====== GRÁFICOS ====== */
function chartOrEmpty(ctxId, labels, values, type = 'bar') {
    const canvas = document.getElementById(ctxId);
    if (!canvas) return;

    if (!labels || !labels.length) {
        canvas.parentElement.innerHTML = '<div class="text-muted small text-center py-3">Sin datos suficientes.</div>';
        return;
    }

    new Chart(canvas, {
        type,
        data: {
            labels,
            datasets: [
                {
                    label: 'Total',
                    data: values,
                    backgroundColor: type === 'bar' ? 'rgba(54,162,235,0.5)' : undefined,
                    borderColor: type === 'bar' ? 'rgba(54,162,235,1)' : undefined,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: type === 'pie' || type === 'doughnut' ? {} : { y: { beginAtZero: true } }
        }
    });
}

/* ====== FILTROS AJAX ====== */
$(function () {
    // Filtros AJAX para la tabla
    $('#frmFiltros').on('submit', function (e) {
        e.preventDefault();
        $('#tablaReportes').bootstrapTable('refresh', { pageNumber: 1 });
    });

    // QueryParams dinámicos
    $('#tablaReportes').bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            const data = Object.fromEntries(new FormData(document.getElementById('frmFiltros')).entries());
            return Object.assign({}, params, data);
        }
    });

    // Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
});
