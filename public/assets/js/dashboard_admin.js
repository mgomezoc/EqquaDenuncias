// Define variables globales para cada gráfico
let estatusChart, conocimientoChart, sucursalesChart, mesDenunciasChart, anonimatoChart, denuncianteChart;
let $tableDenunciasDepartamento;

// Función para inicializar los gráficos
function initCharts() {
    const colors = ['#f4b400', '#db4437', '#0f9d58', '#4285f4', '#34a853', '#ff6d00', '#ffeb3b', '#1e88e5', '#6a5acd', '#d81b60'];

    // Inicializar gráfico de Medio de Recepción de Denuncias
    const ctxDenunciante = document.getElementById('chartDenunciante').getContext('2d');
    denuncianteChart = new Chart(ctxDenunciante, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [
                {
                    data: [],
                    backgroundColor: colors
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: { weight: 'bold', size: 12 }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Inicializar gráfico de Denuncias Anónimas
    const ctxAnonimato = document.getElementById('chartDenunciasAnonimas').getContext('2d');
    anonimatoChart = new Chart(ctxAnonimato, {
        type: 'doughnut',
        data: {
            labels: [], // Labels (Sí, No)
            datasets: [
                {
                    data: [], // Totales
                    backgroundColor: ['#4CAF50', '#FF5722']
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left',
                    labels: { font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${percentage}%`;
                    },
                    anchor: 'center',
                    align: 'center',
                    font: { weight: 'bold', size: 14 }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    // Inicializar gráfico de Mes de recepción de denuncia
    const ctxMesDenuncias = document.getElementById('chartMesDenuncias').getContext('2d');
    mesDenunciasChart = new Chart(ctxMesDenuncias, {
        type: 'bar',
        data: {
            labels: [], // Meses
            datasets: [
                {
                    label: 'Total de denuncias',
                    data: [], // Totales
                    backgroundColor: '#4285f4',
                    borderWidth: 1
                }
            ]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return `Denuncias: ${tooltipItem.raw}`;
                        }
                    }
                },
                datalabels: {
                    color: '#ff0000',
                    anchor: 'end',
                    align: 'top',
                    formatter: value => value,
                    font: { weight: 'bold' }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Mes' } },
                y: { title: { display: true, text: 'Total de denuncias' }, beginAtZero: true }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Inicializar gráfico de Estatus de Denuncias
    const ctxEstatus = document.getElementById('chartEstatusDenuncias').getContext('2d');
    estatusChart = new Chart(ctxEstatus, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [
                {
                    data: [],
                    backgroundColor: colors
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left',
                    labels: {
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: { weight: 'bold', size: 12 }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Inicializar gráfico de Conocimiento del Incidente
    const ctxConocimiento = document.getElementById('chartConocimiento').getContext('2d');
    conocimientoChart = new Chart(ctxConocimiento, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [
                {
                    data: [],
                    backgroundColor: colors
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left',
                    labels: { font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const total = tooltipItem.dataset.data.reduce((a, b) => a + b, 0);
                            const value = tooltipItem.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${tooltipItem.label}: ${value} (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 10,
                    font: { weight: 'bold', size: 12 }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Inicializar gráfico de Denuncias por Sucursal
    const ctxSucursales = document.getElementById('chartSucursalesDenuncias').getContext('2d');
    sucursalesChart = new Chart(ctxSucursales, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Denuncias',
                    data: [],
                    backgroundColor: colors.slice(0, 10), // Selecciona colores para las primeras 10 barras
                    borderWidth: 1
                }
            ]
        },
        options: {
            scales: {
                x: {
                    grid: { display: false },
                    title: { display: true, text: 'Sucursales', font: { size: 16 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    title: { display: true, text: 'Número de Denuncias', font: { size: 16 } }
                }
            },
            plugins: {
                legend: { display: true, position: 'top', labels: { font: { size: 14 } } },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return `Denuncias: ${tooltipItem.raw}`;
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'start',
                    color: '#000',
                    font: { weight: 'bold', size: 12 },
                    formatter: value => value
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

// Cargar datos con AJAX
function loadDashboardData(startDate = null, endDate = null) {
    $.ajax({
        url: `${Server}dashboard/filtrar`,
        method: 'POST',
        data: { start_date: startDate, end_date: endDate },
        dataType: 'json',
        success: function (data) {
            updateCharts(data);
            updateDepartmentTable(data);
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar datos del dashboard:', error);
        }
    });
}

// Función para actualizar los datos de los gráficos
function updateCharts(data) {
    //Total denuncias
    $('#totalDenunciasNuevas').html(data.totalDenunciasNuevas);
    $('#totalDenunciasProceso').html(data.totalDenunciasProceso);
    $('#totalDenunciasRecibidas').html(data.totalDenunciasRecibidas);

    if (data.denunciasPorMedio && denuncianteChart) {
        denuncianteChart.data.labels = data.denunciasPorMedio.map(item => item.medio_recepcion);
        denuncianteChart.data.datasets[0].data = data.denunciasPorMedio.map(item => item.total);
        denuncianteChart.update();

        // Calcular el total y mostrarlo en el HTML
        const totalDenunciante = data.denunciasPorMedio.reduce((sum, item) => sum + parseInt(item.total, 10), 0);
        $('#totalDenunciasPorMedio').text(`Total ${totalDenunciante}`);
    }

    if (data.denunciasAnonimas && anonimatoChart) {
        // Asignamos los valores de etiquetas y datos
        anonimatoChart.data.labels = data.denunciasAnonimas.map(item => item.anonimato);
        anonimatoChart.data.datasets[0].data = data.denunciasAnonimas.map(item => item.total);
        anonimatoChart.update();

        // Calcular el total correctamente
        const totalAnonimas = data.denunciasAnonimas.reduce((sum, item) => sum + parseInt(item.total, 10), 0);

        // Mostrar el total en el elemento HTML correspondiente
        $('#totalDenunciasAnonimas').text(`Total ${totalAnonimas}`);
    }

    if (data.denunciasPorMes && mesDenunciasChart) {
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        mesDenunciasChart.data.labels = data.denunciasPorMes.map(item => meses[item.mes - 1]);
        mesDenunciasChart.data.datasets[0].data = data.denunciasPorMes.map(item => item.total);
        mesDenunciasChart.update();
    }

    if (data.estatusDenuncias && estatusChart) {
        estatusChart.data.labels = data.estatusDenuncias.map(item => item.estatus);
        estatusChart.data.datasets[0].data = data.estatusDenuncias.map(item => item.total);
        estatusChart.update();
    }

    if (data.denunciasPorConocimiento && conocimientoChart) {
        conocimientoChart.data.labels = data.denunciasPorConocimiento.map(item => item.como_se_entero);
        conocimientoChart.data.datasets[0].data = data.denunciasPorConocimiento.map(item => item.total);
        conocimientoChart.update();
    }

    if (data.denunciasPorSucursal && sucursalesChart) {
        sucursalesChart.data.labels = data.denunciasPorSucursal.map(item => item.nombre);
        sucursalesChart.data.datasets[0].data = data.denunciasPorSucursal.map(item => item.total);
        sucursalesChart.update();
    }
}

// Función para actualizar la tabla de denuncias por departamento
function updateDepartmentTable(data) {
    let tableHTML = '';

    // Validación para verificar si hay sucursales y si el único dato en denunciasPorDepto es un total vacío
    if (data.sucursales.length === 0 || (Object.keys(data.denunciasPorDepto).length === 1 && data.denunciasPorDepto.Total && data.denunciasPorDepto.Total.Total === 0)) {
        // Mostrar mensaje de "Sin información disponible" si no hay datos significativos
        tableHTML = '<tr><td colspan="100%" class="text-center">Sin información disponible</td></tr>';
        $('#tableDenunciasDepartamento').html(`<thead><tr><th>Departamento</th></tr></thead><tbody>${tableHTML}</tbody>`);
        return;
    }

    const sucursales = data.sucursales;

    // Generar encabezado de la tabla
    tableHTML += '<thead><tr><th>Departamento</th>';
    sucursales.forEach(sucursal => {
        tableHTML += `<th>${sucursal}</th>`;
    });
    tableHTML += '<th>Total</th></tr></thead><tbody>';

    // Generar filas de la tabla para cada departamento
    for (const [departamento, totales] of Object.entries(data.denunciasPorDepto)) {
        tableHTML += `<tr><td>${departamento}</td>`;
        sucursales.forEach(sucursal => {
            tableHTML += `<td class='text-center'>${totales[sucursal] || 0}</td>`;
        });
        tableHTML += `<td class='text-center'><b>${totales.Total}</b></td></tr>`;
    }

    tableHTML += '</tbody>';

    // Reemplazar el contenido de la tabla
    $('#tableDenunciasDepartamento').html(tableHTML);
}

// Configurar Flatpickr para seleccionar fechas
$(document).ready(function () {
    flatpickr('#startDate', {
        dateFormat: 'Y-m-d',
        defaultDate: 'today',
        locale: 'es'
    });

    flatpickr('#endDate', {
        dateFormat: 'Y-m-d',
        defaultDate: 'today',
        locale: 'es'
    });

    // Inicializar los gráficos
    initCharts();

    // Cargar datos iniciales del dashboard
    loadDashboardData();

    // Aplicar filtros al enviar el formulario
    $('#dateFilterForm').submit(function (e) {
        e.preventDefault();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        loadDashboardData(startDate, endDate);
    });
});
