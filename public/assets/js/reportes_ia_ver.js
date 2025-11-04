/* ==========================================================
 * VISTA: VER REPORTE IA
 * - Chart.js rendering (con "vacío" si no hay datos)
 * - Cambiar estado (SweetAlert)
 * - Badge helpers
 * ========================================================== */

/* global Server, $, bootstrap, Chart, Swal */

const ReporteIAVer = (function () {
    // ---------- Helpers de badges ----------
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
    function pintarBadgeEstado(selector, estado) {
        const $b = $(selector);
        if (!$b.length) return;
        const cls = badgeClassEstado(estado);
        const txt = (estado || '').charAt(0).toUpperCase() + (estado || '').slice(1);
        $b.removeClass('bg-secondary bg-info bg-success bg-dark bg-light text-dark').addClass(cls).text(txt);
    }

    // ---------- Charts ----------
    function chartOrEmpty(ctxId, labels, values, type = 'bar') {
        const canvas = document.getElementById(ctxId);
        if (!canvas) return;

        if (!labels || !labels.length) {
            canvas.parentElement.innerHTML = '<div class="text-muted small text-center py-3">Sin datos suficientes para mostrar.</div>';
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
                        backgroundColor: type === 'bar' || type === 'line' ? 'rgba(54, 162, 235, 0.5)' : undefined,
                        borderColor: type === 'bar' || type === 'line' ? 'rgba(54, 162, 235, 1)' : undefined,
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

    // Extrae labels/values de un arreglo de objetos
    function dist(arr, labelKey, valueKey = 'total') {
        if (!Array.isArray(arr)) return { labels: [], values: [] };
        return {
            labels: arr.map(x => x[labelKey] ?? 'N/D'),
            values: arr.map(x => Number(x[valueKey] ?? 0))
        };
    }

    // ---------- Cambiar estado ----------
    async function cambiarEstadoPrompt(idReporte, estadoActual) {
        const estados = { generado: 'Generado', revisado: 'Revisado', publicado: 'Publicado', archivado: 'Archivado' };
        let options = '';
        for (const [k, v] of Object.entries(estados)) {
            options += `<option value="${k}" ${k === estadoActual ? 'selected' : ''}>${v}</option>`;
        }

        const { value: nuevoEstado } = await Swal.fire({
            title: 'Cambiar estado',
            html: `
        <div class="mb-2 text-start">
          <label class="form-label">Seleccione el nuevo estado:</label>
          <select id="swal-estado" class="form-select">${options}</select>
        </div>`,
            showCancelButton: true,
            confirmButtonText: 'Cambiar',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => document.getElementById('swal-estado').value
        });

        if (!nuevoEstado || nuevoEstado === estadoActual) return;

        $.post(`${Server}reportes-ia/cambiar-estado`, { id_reporte: idReporte, estado: nuevoEstado })
            .done(() => {
                pintarBadgeEstado('#badgeEstado', nuevoEstado);
                Swal.fire({
                    icon: 'success',
                    title: 'Estado actualizado',
                    text: `El reporte ahora está como "${nuevoEstado}".`,
                    timer: 1800,
                    showConfirmButton: false
                });
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'No se pudo cambiar el estado.';
                Swal.fire('Error', msg, 'error');
            });
    }

    // ---------- Init ----------
    function init({ estadoActual, idReporte, metricas }) {
        // Pintar badge actual
        pintarBadgeEstado('#badgeEstado', estadoActual || 'generado');

        // Bind botón cambiar estado
        $('#btnCambiarEstado')
            .off('click')
            .on('click', function () {
                cambiarEstadoPrompt(idReporte, (estadoActual || '').toLowerCase());
            });

        // Gráficas
        const suc = dist(metricas?.distribucion_sucursal, 'sucursal');
        chartOrEmpty('chSucursales', suc.labels, suc.values, 'bar');

        const cat = dist(metricas?.distribucion_categoria, 'categoria');
        chartOrEmpty('chCategorias', cat.labels, cat.values, 'bar');

        const est = dist(metricas?.distribucion_estatus, 'estatus');
        chartOrEmpty('chEstatus', est.labels, est.values, 'doughnut');

        const med = dist(metricas?.distribucion_medio, 'medio');
        chartOrEmpty('chMedios', med.labels, med.values, 'doughnut');
    }

    // API pública
    return { init };
})();
