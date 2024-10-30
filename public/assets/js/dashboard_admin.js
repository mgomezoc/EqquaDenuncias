// Define variables globales para cada gráfico
let estatusChart, conocimientoChart, sucursalesChart, anonimatoChart, denuncianteChart;
let $tableDenunciasDepartamento;
let mesDenunciasChart = [];

// Función para inicializar los gráficos
function initCharts() {
    const colors = ['#f4b400', '#db4437', '#4285f4', '#34a853', '#ff6d00', '#ffeb3b', '#1e88e5', '#6a5acd', '#d81b60'];

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
                    backgroundColor: '#6460a9',
                    borderWidth: 1
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            aspectRatio: 2,
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
                    color: '#ee3741',
                    anchor: 'end',
                    align: 'top',
                    formatter: value => value,
                    font: { weight: 'bold' }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { stepSize: 1 },
                    afterDataLimits: axis => {
                        try {
                            const maxValue = Math.max(...mesDenunciasChart.data.datasets[0].data);
                            axis.max = maxValue + 1;
                        } catch {
                            console.log('No hay datos para mostrar');
                        }
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Inicializar gráfico de Estatus de Denuncias
    const ctxEstatus = document.getElementById('chartEstatusDenuncias').getContext('2d');
    estatusChart = new Chart(ctxEstatus, {
        type: 'doughnut',
        data: {
            labels: [], // Las etiquetas dinámicas se cargarán aquí
            datasets: [
                {
                    data: [], // Los datos dinámicos se cargarán aquí
                    backgroundColor: colors // Colores personalizados
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'left', // Cambiar la posición de la leyenda a la izquierda para una lista vertical
                    align: 'center', // Centrar la leyenda verticalmente
                    labels: {
                        // Modificamos el texto de la leyenda para mostrar nombre y cantidad
                        generateLabels: function (chart) {
                            const dataset = chart.data.datasets[0];
                            return chart.data.labels.map((label, index) => {
                                const value = dataset.data[index];
                                return {
                                    text: `${label}: ${value}`, // Mostrar "Nombre: Cantidad"
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: dataset.backgroundColor[index],
                                    hidden: false,
                                    index: index
                                };
                            });
                        },
                        font: { size: 14 },
                        boxWidth: 12, // Ancho de la caja de color
                        padding: 10 // Espaciado entre elementos
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const label = tooltipItem.label || '';
                            const value = tooltipItem.raw;
                            return `${label}: ${value} denuncias`;
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
                    offset: 10,
                    font: { weight: 'bold', size: 14 }
                }
            }
        },
        plugins: [
            ChartDataLabels,
            {
                // Plugin para mostrar el total en el centro de la dona
                id: 'centerText',
                afterDatasetsDraw: chart => {
                    const { ctx, chartArea } = chart;

                    // Calcular el total usando los datos actuales
                    const total = chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0);

                    // Estilo del texto
                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    // Configurar fuente y color para el texto
                    ctx.font = 'bold 24px sans-serif';
                    ctx.fillStyle = '#231f20';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Dibujar "Total" en el centro
                    ctx.fillText('Total', centerX, centerY - 10);

                    // Dibujar el número total debajo de "Total"
                    ctx.font = 'bold 30px sans-serif';
                    ctx.fillText(total, centerX, centerY + 20);
                    ctx.restore();
                }
            }
        ]
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
function loadDashboardData(startDate = null, endDate = null, sucursal = '', departamento = '', anonimo = '') {
    $.ajax({
        url: `${Server}dashboard/filtrar`,
        method: 'POST',
        data: {
            start_date: startDate,
            end_date: endDate,
            sucursal: sucursal,
            departamento: departamento,
            anonimo: anonimo
        },
        dataType: 'json',
        success: function (data) {
            updateCharts(data);
            updateDepartmentTable(data); // Actualizar la tabla de departamentos con los filtros
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

        // Asigna los nombres de los meses como etiquetas
        mesDenunciasChart.data.labels = data.denunciasPorMes.map(item => meses[item.mes - 1]);

        // Asigna los valores de total para cada mes
        mesDenunciasChart.data.datasets[0].data = data.denunciasPorMes.map(item => item.total);

        // Actualiza la gráfica
        mesDenunciasChart.update();

        const totalDenuncias = data.denunciasPorMes.reduce((sum, item) => sum + item.total, 0);
        $('#totalMesDenuncias').html(totalDenuncias);
    }

    if (data.estatusDenuncias && estatusChart) {
        estatusChart.data.labels = data.estatusDenuncias.map(item => item.estatus);
        estatusChart.data.datasets[0].data = data.estatusDenuncias.map(item => parseInt(item.total));
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
    if (data.denunciasPorDeptoSucursales.length === 0 || (Object.keys(data.denunciasPorDepto).length === 1 && data.denunciasPorDepto.Total && data.denunciasPorDepto.Total.Total === 0)) {
        // Mostrar mensaje de "Sin información disponible" si no hay datos significativos
        tableHTML = '<tr><td colspan="100%" class="text-center">Sin información disponible</td></tr>';
        $('#tableDenunciasDepartamento').html(`<thead><tr><th>Departamento</th></tr></thead><tbody>${tableHTML}</tbody>`);
        return;
    }

    const sucursales = data.denunciasPorDeptoSucursales;

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
    $('[data-bs-toggle="tooltip"]').tooltip();

    const startDatePicker = flatpickr('#startDate', {
        dateFormat: 'Y-m-d',
        locale: 'es',
        onChange: function (selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                endDatePicker.set('minDate', dateStr);
                const endDate = $('#endDate').val();
                if (endDate && new Date(endDate) < new Date(dateStr)) {
                    $('#endDate').val(dateStr);
                }
            } else {
                endDatePicker.set('minDate', null);
            }
        }
    });

    const endDatePicker = flatpickr('#endDate', {
        dateFormat: 'Y-m-d',
        locale: 'es',
        onChange: function (selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                startDatePicker.set('maxDate', dateStr);
                const startDate = $('#startDate').val();
                if (startDate && new Date(startDate) > new Date(dateStr)) {
                    $('#startDate').val(dateStr);
                }
            } else {
                startDatePicker.set('maxDate', null);
            }
        }
    });

    $('.select2').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: true
    });

    // Inicializar los gráficos
    initCharts();

    // Cargar datos iniciales del dashboard
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    loadDashboardData(startDate, endDate);

    // Aplicar filtros al enviar el formulario
    $('#dateFilterForm').submit(function (e) {
        e.preventDefault();

        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const sucursal = $('#sucursalFilter').val();
        const departamento = $('#departamentoFilter').val();
        const anonimo = $('#anonimoFilter').val();

        loadDashboardData(startDate, endDate, sucursal, departamento, anonimo);
    });

    // Función para resetear los filtros
    $('#resetButton').click(function () {
        // Limpiar los campos de fecha
        $('#startDate').val('');
        $('#endDate').val('');

        // Limpiar y actualizar select2
        $('#sucursalFilter').val('').trigger('change');
        $('#departamentoFilter').val('').trigger('change');
        $('#anonimoFilter').val('').trigger('change');

        // Recargar los datos con filtros vacíos (para mostrar toda la información)
        loadDashboardData();
    });
});
