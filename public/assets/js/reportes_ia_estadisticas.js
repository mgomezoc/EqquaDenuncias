(() => {
    const charts = {};

    const fmtNumber = n => new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 }).format(n);

    const fmtMoney4 = n => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 4 }).format(n || 0);

    const toArrays = obj => {
        const labels = [],
            values = [];
        Object.entries(obj || {}).forEach(([k, v]) => {
            labels.push(k);
            values.push(Number(v) || 0);
        });
        const zipped = labels.map((l, i) => [l, values[i]]);
        zipped.sort((a, b) => b[1] - a[1]);
        return { labels: zipped.map(z => z[0]), values: zipped.map(z => z[1]) };
    };

    const destroyIfAny = key => {
        if (charts[key]) {
            charts[key].destroy();
            delete charts[key];
        }
    };

    // Paleta discreta (12 tonos); se repite si hay más segmentos
    const PALETTE = ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab', '#2a9d8f', '#e76f51'];
    const colorsFor = n => Array.from({ length: n }, (_, i) => PALETTE[i % PALETTE.length]);

    const showEmpty = (canvasId, msg = 'Sin datos') => {
        const el = document.getElementById(canvasId);
        if (!el) return;
        const ctx = el.getContext('2d');
        ctx.clearRect(0, 0, el.width, el.height);
        ctx.font = '14px system-ui, -apple-system, Segoe UI, Roboto, Ubuntu';
        ctx.fillStyle = '#6c757d';
        ctx.textAlign = 'center';
        ctx.fillText(msg, el.width / 2, el.height / 2);
    };

    const buildDoughnut = (canvasId, title, dataObj) => {
        const el = document.getElementById(canvasId);
        if (!el) return;

        const { labels, values } = toArrays(dataObj);
        if (!values.length || values.every(v => v === 0)) {
            destroyIfAny(canvasId);
            showEmpty(canvasId);
            return;
        }

        destroyIfAny(canvasId);
        charts[canvasId] = new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: colorsFor(values.length),
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false, text: title },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                const val = ctx.parsed || 0;
                                const pct = ((val / total) * 100).toFixed(1);
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '55%'
            }
        });
    };

    const paintBadgesEstados = objEstados => {
        const host = document.getElementById('badgesEstados');
        if (!host) return;
        host.innerHTML = '';
        const estados = Object.entries(objEstados || {}).sort((a, b) => b[1] - a[1]);
        const mapColor = name => {
            const n = (name || '').toLowerCase();
            if (n.includes('publicad')) return 'bg-success';
            if (n.includes('generad')) return 'bg-secondary';
            if (n.includes('borrador')) return 'bg-warning text-dark';
            if (n.includes('error')) return 'bg-danger';
            return 'bg-primary';
        };
        estados.forEach(([name, total]) => {
            const span = document.createElement('span');
            span.className = `badge ${mapColor(name)} px-3 py-2`;
            span.innerHTML = `<i class="fas fa-circle me-1"></i>${name}: <strong>${fmtNumber(total)}</strong>`;
            host.appendChild(span);
        });
    };

    const paintKPIs = () => {
        const tEl = document.getElementById('kpiTotalReportes');
        const cEl = document.getElementById('kpiCostoTotal');
        const kEl = document.getElementById('kpiTokensTotal');
        if (tEl) tEl.textContent = fmtNumber(Number(tEl.dataset.total || 0));
        if (cEl) cEl.textContent = fmtMoney4(Number(cEl.dataset.costo || 0));
        if (kEl) kEl.textContent = fmtNumber(Number(kEl.dataset.tokens || 0));
    };

    window.addEventListener('DOMContentLoaded', () => {
        try {
            buildDoughnut('chPorTipo', 'Reportes por tipo', window.STATS_POR_TIPO || {});
            buildDoughnut('chPorEstado', 'Reportes por estado', window.STATS_POR_ESTADO || {});
            paintBadgesEstados(window.STATS_POR_ESTADO || {});
            paintKPIs();
            // Debug opcional:
            // console.debug('por_tipo:', window.STATS_POR_TIPO);
            // console.debug('por_estado:', window.STATS_POR_ESTADO);
        } catch (err) {
            console.error('[estadisticas.js] error:', err);
        }
    });
})();
