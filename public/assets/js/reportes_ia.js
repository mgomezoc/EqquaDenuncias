/* ====================================================================
 * REPORTES IA - JavaScript
 * Gestión de reportes generados por Inteligencia Artificial
 * ==================================================================== */

/* global Server, Handlebars, Swal, bootstrap, Chart */

let tplAccionesReportes;

/* ====== FORMATTERS para Bootstrap Table ====== */

/**
 * Formatter para fechas
 */
function fechaFormatter(value) {
    if (!value) return '<span class="text-muted">-</span>';
    // Formato: 2025-10-28 13:00:51 -> 2025-10-28 13:00
    return value.replace('T', ' ').substring(0, 16);
}

/**
 * Obtiene la clase CSS para el badge según el estado
 */
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

/**
 * Formatter para el estado
 */
function estadoFormatter(value) {
    if (!value) return '<span class="badge bg-light text-dark">Sin estado</span>';
    const cls = badgeClassEstado(value);
    const txt = value.charAt(0).toUpperCase() + value.slice(1);
    return `<span class="badge ${cls}">${txt}</span>`;
}

/**
 * Formatter para el tipo de reporte
 */
function tipoFormatter(value) {
    const map = {
        mensual: 'primary',
        trimestral: 'warning',
        semestral: 'purple'
    };
    const cls = map[(value || '').toLowerCase()] || 'secondary';
    const txt = (value || '').charAt(0).toUpperCase() + (value || '').slice(1);
    return `<span class="badge bg-${cls}">${txt}</span>`;
}

/**
 * Formatter para la puntuación de riesgo
 */
function riesgoFormatter(value) {
    if (!value && value !== 0) return '<span class="text-muted">-</span>';

    const v = Number(value);
    let cls = 'success';

    if (v >= 7) {
        cls = 'danger';
    } else if (v >= 4) {
        cls = 'warning';
    }

    return `<span class="badge bg-${cls}-subtle text-${cls} fw-bold">${v}/10</span>`;
}

/**
 * Formatter para la columna de acciones
 */
function operateFormatter(value, row) {
    if (!tplAccionesReportes) {
        tplAccionesReportes = $('#tplAccionesReportes').html();
    }
    return Handlebars.compile(tplAccionesReportes)(row);
}

/* ====== EVENTOS de las acciones ====== */

window.operateEvents = {
    /**
     * Ver detalle del reporte
     */
    'click .view': function (e, value, row) {
        e.preventDefault();
        window.location = `${Server}reportes-ia/ver/${row.id}`;
    },

    /**
     * Descargar PDF
     */
    'click .pdf': function (e, value, row) {
        e.preventDefault();
        window.open(`${Server}reportes-ia/descargar/${row.id}`, '_blank');
    },

    /**
     * Cambiar estado
     */
    'click .estado': async function (e, value, row) {
        e.preventDefault();
        cambiarEstadoPrompt(row.id, row.estado);
    },

    /**
     * Eliminar reporte
     */
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
                .done(response => {
                    $('#tablaReportes').bootstrapTable('refresh');
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'El reporte fue eliminado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .fail(xhr => {
                    const errorMsg = xhr.responseJSON?.message || 'No se pudo eliminar el reporte.';
                    Swal.fire('Error', errorMsg, 'error');
                });
        }
    }
};

/* ====== FUNCIONES AUXILIARES ====== */

/**
 * Muestra un prompt para cambiar el estado de un reporte
 */
async function cambiarEstadoPrompt(idReporte, estadoActual) {
    const estados = {
        generado: 'Generado',
        revisado: 'Revisado',
        publicado: 'Publicado',
        archivado: 'Archivado'
    };

    // Crear opciones HTML para el select
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
        preConfirm: () => {
            return document.getElementById('swal-estado').value;
        }
    });

    if (nuevoEstado && nuevoEstado !== estadoActual) {
        $.post(`${Server}reportes-ia/cambiar-estado`, {
            id_reporte: idReporte,
            nuevo_estado: nuevoEstado
        })
            .done(response => {
                $('#tablaReportes').bootstrapTable('refresh');
                Swal.fire({
                    title: 'Estado actualizado',
                    text: `El reporte ahora está como "${estados[nuevoEstado]}".`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .fail(xhr => {
                const errorMsg = xhr.responseJSON?.message || 'No se pudo cambiar el estado.';
                Swal.fire('Error', errorMsg, 'error');
            });
    }
}

/**
 * Pinta el badge de estado en un elemento
 */
function pintarBadgeEstado(selector, estado) {
    const $badge = $(selector);
    if (!$badge.length) return;

    const cls = badgeClassEstado(estado);
    const txt = (estado || '').charAt(0).toUpperCase() + (estado || '').slice(1);

    $badge.removeClass('bg-secondary bg-info bg-success bg-dark bg-light').addClass(cls).text(txt);
}

/**
 * Bind evento cambiar estado en página de ver
 */
function bindCambiarEstadoReporte(idReporte) {
    $('#btnCambiarEstado')
        .off('click')
        .on('click', function () {
            const estadoActual = $('#badgeEstado').text().trim().toLowerCase();
            cambiarEstadoPrompt(idReporte, estadoActual);
        });
}

/* ====== GENERAR REPORTE ====== */

/**
 * Inicializa el formulario de generar reporte
 */
function initGenerarReporte() {
    const $form = $('#formGenerarReporte');
    if (!$form.length) return;

    // Validación con jQuery Validate
    $form.validate({
        rules: {
            id_cliente: { required: true },
            tipo_reporte: { required: true },
            fecha_inicio: { required: true },
            fecha_fin: { required: true }
        },
        messages: {
            id_cliente: 'Seleccione un cliente',
            tipo_reporte: 'Seleccione el tipo de reporte',
            fecha_inicio: 'Ingrese la fecha de inicio',
            fecha_fin: 'Ingrese la fecha de fin'
        },
        submitHandler: function (form) {
            procesarGeneracionReporte();
        }
    });
}

/**
 * Procesa la generación del reporte vía AJAX
 */
function procesarGeneracionReporte() {
    const $form = $('#formGenerarReporte');
    const $btn = $('#btnGenerar');
    const $resultado = $('#resultadoGeneracion');

    // Deshabilitar botón
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando...');

    // Ocultar resultado anterior
    $resultado.addClass('d-none').removeClass('alert alert-success alert-danger').text('');

    // Enviar datos
    $.ajax({
        url: `${Server}reportes-ia/procesar`,
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    })
        .done(response => {
            if (response.success) {
                $resultado.removeClass('d-none').addClass('alert alert-success').html(`
                    <i class="fas fa-check-circle me-2"></i>
                    ${response.message || 'Reporte generado correctamente.'}
                    <br>
                    <a href="${Server}reportes-ia/ver/${response.id_reporte}" class="alert-link">
                        Ver reporte <i class="fas fa-arrow-right"></i>
                    </a>
                `);

                // Limpiar formulario
                $form[0].reset();
                $('.select2').val(null).trigger('change');
            } else {
                $resultado
                    .removeClass('d-none')
                    .addClass('alert alert-danger')
                    .html(`<i class="fas fa-exclamation-triangle me-2"></i>${response.message || 'Error al generar el reporte.'}`);
            }
        })
        .fail(xhr => {
            const errorMsg = xhr.responseJSON?.message || 'Error interno del servidor.';
            $resultado.removeClass('d-none').addClass('alert alert-danger').html(`<i class="fas fa-exclamation-triangle me-2"></i>${errorMsg}`);
        })
        .always(() => {
            // Rehabilitar botón
            $btn.prop('disabled', false).html('<i class="fas fa-cogs me-1"></i> Generar');
        });
}

/* ====== PÁGINA VER: Gráficas ====== */

/**
 * Crea un gráfico o muestra mensaje de "sin datos"
 */
function chartOrEmpty(ctxId, labels, values, type = 'bar') {
    const canvas = document.getElementById(ctxId);
    if (!canvas) return;

    // Si no hay datos
    if (!labels || !labels.length) {
        canvas.parentElement.innerHTML = '<div class="text-muted small text-center py-3">Sin datos suficientes para mostrar.</div>';
        return;
    }

    // Crear gráfico
    new Chart(canvas, {
        type: type,
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total',
                    data: values,
                    backgroundColor: type === 'bar' ? 'rgba(54, 162, 235, 0.5)' : undefined,
                    borderColor: type === 'bar' ? 'rgba(54, 162, 235, 1)' : undefined,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales:
                type === 'pie' || type === 'doughnut'
                    ? {}
                    : {
                          y: { beginAtZero: true }
                      }
        }
    });
}

/**
 * Inicializa los gráficos en la página de ver reporte
 */
function initVerReporte(metricas) {
    if (!metricas || typeof metricas !== 'object') return;

    // Helper para extraer labels y values
    const dist = (arr, campoLabel, campoVal = 'total') => {
        if (!Array.isArray(arr)) return { labels: [], values: [] };
        return {
            labels: arr.map(x => x[campoLabel] || 'N/D'),
            values: arr.map(x => Number(x[campoVal] || 0))
        };
    };

    // Gráfico de sucursales
    const suc = dist(metricas.distribucion_sucursal, 'sucursal');
    chartOrEmpty('chSucursales', suc.labels, suc.values, 'bar');

    // Gráfico de categorías
    const cat = dist(metricas.distribucion_categoria, 'categoria');
    chartOrEmpty('chCategorias', cat.labels, cat.values, 'bar');

    // Gráfico de estatus
    const est = dist(metricas.distribucion_estatus, 'estatus');
    chartOrEmpty('chEstatus', est.labels, est.values, 'doughnut');

    // Gráfico de medios
    const med = dist(metricas.distribucion_medio, 'medio');
    chartOrEmpty('chMedios', med.labels, med.values, 'doughnut');
}

/* ====== ESTADÍSTICAS ====== */

/**
 * Pinta las gráficas de estadísticas
 */
function pintarGraficaStats(porTipo, porEstado) {
    // Helper para convertir objeto a arrays
    const toArr = obj => {
        const labels = Object.keys(obj || {});
        const values = labels.map(k => Number((obj || {})[k] || 0));
        return { labels, values };
    };

    // Gráfico por tipo
    const t = toArr(porTipo);
    chartOrEmpty(
        'chPorTipo',
        t.labels.map(x => x.toUpperCase()),
        t.values,
        'doughnut'
    );

    // Gráfico por estado
    const e = toArr(porEstado);
    chartOrEmpty(
        'chPorEstado',
        e.labels.map(x => x.toUpperCase()),
        e.values,
        'bar'
    );
}
