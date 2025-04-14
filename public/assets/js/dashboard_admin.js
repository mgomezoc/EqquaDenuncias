// Define variables globales para cada gráfico
let estatusChart, conocimientoChart, sucursalesChart, anonimatoChart, denuncianteChart;
let $tableDenunciasDepartamento;
let mesDenunciasChart = [];

// Función para inicializar los gráficos
function initCharts() {
    const colors = ['#f4b400', '#db4437', '#4285f4', '#34a853', '#ff6d00', '#ffeb3b', '#1e88e5', '#6a5acd', '#d81b60'];

    // Mes de recepción de denuncia
    const ctxMesDenuncias = document.getElementById('chartMesDenuncias').getContext('2d');
    mesDenunciasChart = new Chart(ctxMesDenuncias, {
        type: 'bar',
        data: {
            labels: [], // Meses
            datasets: [
                {
                    label: 'Total de Denuncias',
                    data: [], // Totales
                    backgroundColor: '#6460a9',
                    borderWidth: 1
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            aspectRatio: 2,
            layout: {
                padding: {
                    top: 20 // Añadir un relleno superior para evitar el corte
                }
            },
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
                            axis.max = maxValue + 2; // Incrementa el valor máximo para añadir espacio adicional
                        } catch {
                            console.log('No hay datos para mostrar');
                        }
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Estatus de Denuncias
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
                    display: true,
                    position: 'bottom',
                    labels: {
                        // Modificamos el texto de la leyenda para mostrar nombre y cantidad
                        generateLabels: function (chart) {
                            const dataset = chart.data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);

                            return chart.data.labels.map((label, index) => {
                                const value = dataset.data[index];
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                return {
                                    text: `${label}: ${value} (${percentage}%)`,
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: dataset.backgroundColor[index],
                                    hidden: false,
                                    index: index
                                };
                            });
                        },
                        font: {
                            family: 'Nunito',
                            size: 12,
                            style: 'italic',
                            weight: 'bold'
                        },
                        boxWidth: 10,
                        boxHeight: 30,
                        color: '#231f20',
                        padding: 40,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        textAlign: 'left'
                    }
                },
                tooltip: {
                    callbacks: {
                        position: 'nearest',
                        label: function (tooltipItem) {
                            const label = tooltipItem.label || '';
                            const value = tooltipItem.raw;
                            return `${label}: ${value} denuncias`;
                        }
                    }
                },
                datalabels: {
                    display: false
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
                    ctx.font = 'bold 24px Nunito';
                    ctx.fillStyle = '#231f20';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Dibujar "Total" en el centro
                    ctx.fillText('Total', centerX, centerY - 10);

                    // Dibujar el número total debajo de "Total"
                    ctx.font = 'bold 30px Nunito';
                    ctx.fillText(total, centerX, centerY + 20);
                    ctx.restore();
                }
            }
        ]
    });

    // Denuncias Anónimas
    const ctxAnonimato = document.getElementById('chartDenunciasAnonimas').getContext('2d');
    anonimatoChart = new Chart(ctxAnonimato, {
        type: 'doughnut',
        data: {
            labels: [], // Etiquetas dinámicas ("Sí", "No")
            datasets: [
                {
                    data: [], // Datos dinámicos para "Sí" y "No"
                    backgroundColor: ['#49beaf', '#6460a9'] // Colores personalizados para cada opción
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom',
                    align: 'center',
                    labels: {
                        // Generar etiquetas personalizadas para mostrar "Nombre: Cantidad"
                        generateLabels: function (chart) {
                            const dataset = chart.data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);

                            return chart.data.labels.map((label, index) => {
                                const value = dataset.data[index];
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                return {
                                    text: `${label}: ${value} (${percentage}%)`,
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: dataset.backgroundColor[index],
                                    hidden: false,
                                    index: index
                                };
                            });
                        },
                        font: {
                            family: 'Nunito',
                            size: 12,
                            style: 'italic',
                            weight: 'bold'
                        },
                        boxWidth: 10,
                        boxHeight: 30,
                        color: '#231f20',
                        padding: 40,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        textAlign: 'left'
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
                    display: false
                }
            }
        },
        plugins: [
            ChartDataLabels,
            {
                id: 'centerText',
                afterDatasetsDraw: chart => {
                    const { ctx, chartArea } = chart;

                    const total = chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0);

                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.font = 'bold 23px Nunito';
                    ctx.fillStyle = '#231f20';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillText('Total', centerX, centerY - 10);

                    ctx.font = 'bold 30px Nunito';
                    ctx.fillText(total, centerX, centerY + 20);
                    ctx.restore();
                }
            }
        ]
    });

    // Tipo de Denunciante
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
                    position: 'bottom',
                    align: 'center',
                    labels: {
                        generateLabels: function (chart) {
                            const dataset = chart.data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);

                            return chart.data.labels.map((label, index) => {
                                const value = dataset.data[index];
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                return {
                                    text: `${label}: ${value} (${percentage}%)`,
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: dataset.backgroundColor[index],
                                    hidden: false,
                                    index: index
                                };
                            });
                        },
                        font: {
                            family: 'Nunito',
                            size: 13,
                            style: 'italic',
                            weight: 'bold'
                        },
                        boxWidth: 10,
                        boxHeight: 30,
                        color: '#231f20',
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 40
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
                    display: false
                }
            }
        },
        plugins: [
            ChartDataLabels,
            {
                id: 'centerText',
                afterDatasetsDraw: chart => {
                    const { ctx, chartArea } = chart;

                    const total = chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0);

                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.font = 'bold 23px Nunito';
                    ctx.fillStyle = '#231f20';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillText('Total', centerX, centerY - 10);

                    ctx.font = 'bold 30px Nunito';
                    ctx.fillText(total, centerX, centerY + 20);
                    ctx.restore();
                }
            }
        ]
    });

    // Conocimiento del Incidente
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
                    position: 'bottom',
                    align: 'center',
                    labels: {
                        generateLabels: function (chart) {
                            const dataset = chart.data.datasets[0];
                            const total = dataset.data.reduce((a, b) => a + b, 0);

                            return chart.data.labels.map((label, index) => {
                                const value = dataset.data[index];
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                return {
                                    text: `${label}: ${value} (${percentage}%)`,
                                    fillStyle: dataset.backgroundColor[index],
                                    strokeStyle: dataset.backgroundColor[index],
                                    hidden: false,
                                    index: index
                                };
                            });
                        },
                        font: {
                            family: 'Nunito',
                            size: 13,
                            style: 'italic',
                            weight: 'bold'
                        },
                        boxWidth: 10,
                        boxHeight: 30,
                        color: '#231f20',
                        padding: 40,
                        usePointStyle: true,
                        pointStyle: 'circle'
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
                    display: false
                }
            }
        },
        plugins: [
            ChartDataLabels,
            {
                id: 'centerText',
                afterDatasetsDraw: chart => {
                    const { ctx, chartArea } = chart;

                    const total = chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0);

                    ctx.save();
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.font = 'bold 23px Nunito';
                    ctx.fillStyle = '#231f20';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillText('Total', centerX, centerY - 10);

                    ctx.font = 'bold 30px Nunito';
                    ctx.fillText(total, centerX, centerY + 20);
                    ctx.restore();
                }
            }
        ]
    });

    // Denuncias por Sucursal
    const ctxSucursales = document.getElementById('chartSucursalesDenuncias').getContext('2d');
    sucursalesChart = new Chart(ctxSucursales, {
        type: 'line',
        data: {
            labels: [], // Nombres de las sucursales
            datasets: [
                {
                    label: 'Número de Denuncias',
                    data: [], // Totales de denuncias por sucursal
                    backgroundColor: 'rgba(0, 0, 255, 0.1)', // Fondo del área bajo la línea
                    borderColor: '#6460a9', // Color de la línea
                    borderWidth: 2,
                    pointBackgroundColor: '#6460a9',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Ocultar la leyenda
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return `Número de Denuncias: ${tooltipItem.raw}`;
                        }
                    }
                },
                datalabels: {
                    color: '#ee3741',
                    align: 'top',
                    anchor: 'end',
                    font: { weight: 'bold', size: 12 },
                    formatter: value => value
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#ee3741',
                        maxRotation: 90,
                        minRotation: 0,
                        padding: 5,
                        font: {
                            family: 'Nunito',
                            size: 12,
                            style: 'italic',
                            weight: 'bold'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sucursal',
                        color: '#3b82f6',
                        font: {
                            family: 'Nunito',
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#3b82f6',
                        font: {
                            family: 'Nunito',
                            size: 14,
                            style: 'italic',
                            weight: 'bold'
                        },
                        stepSize: 2 // Ajuste del intervalo de las etiquetas del eje Y
                    },
                    title: {
                        display: true,
                        text: 'Número de denuncias',
                        color: '#3b82f6',
                        font: {
                            family: 'Nunito',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

// Cargar datos con AJAX
function loadDashboardData(startDate = null, endDate = null, sucursal = '', departamento = '', anonimo = '', cliente = '') {
    $.ajax({
        url: `${Server}dashboard/filtrar`,
        method: 'POST',
        data: {
            start_date: startDate,
            end_date: endDate,
            sucursal: sucursal,
            departamento: departamento,
            anonimo: anonimo,
            cliente: cliente
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
    // Actualizar contadores
    $('#totalDenunciasNuevas').html(data.totalDenunciasNuevas);
    $('#totalDenunciasProceso').html(data.totalDenunciasProceso);
    $('#totalDenunciasCerradas').html(data.totalDenunciasCerradas);
    $('#totalDenunciasTotales').html(data.totalDenunciasTotales);

    if (data.denunciasPorMedio && denuncianteChart) {
        denuncianteChart.data.labels = data.denunciasPorMedio.map(item => item.medio_recepcion);
        denuncianteChart.data.datasets[0].data = data.denunciasPorMedio.map(item => parseInt(item.total));
        denuncianteChart.update();

        // Calcular el total y mostrarlo en el HTML
        const totalDenunciante = data.denunciasPorMedio.reduce((sum, item) => sum + parseInt(item.total, 10), 0);
        $('#totalDenunciasPorMedio').text(`Total ${totalDenunciante}`);
    }

    if (data.denunciasAnonimas && anonimatoChart) {
        // Asignar etiquetas y datos de la gráfica
        anonimatoChart.data.labels = data.denunciasAnonimas.map(item => `${item.anonimato}`);
        anonimatoChart.data.datasets[0].data = data.denunciasAnonimas.map(item => parseInt(item.total));
        anonimatoChart.update();

        // Calcular y mostrar el total en el HTML
        const totalAnonimas = data.denunciasAnonimas.reduce((sum, item) => sum + parseInt(item.total, 10), 0);
        $('#totalDenunciasAnonimas').text(`Total ${totalAnonimas}`);
    }
    /*
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
    }*/

    if (data.estatusDenuncias && estatusChart) {
        estatusChart.data.labels = data.estatusDenuncias.map(item => item.estatus);
        estatusChart.data.datasets[0].data = data.estatusDenuncias.map(item => parseInt(item.total));
        estatusChart.update();
    }

    if (data.denunciasPorConocimiento && conocimientoChart) {
        conocimientoChart.data.labels = data.denunciasPorConocimiento.map(item => item.como_se_entero);
        conocimientoChart.data.datasets[0].data = data.denunciasPorConocimiento.map(item => parseInt(item.total));
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
        tableHTML += `<th class="text-center">${sucursal}</th>`;
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
        dateFormat: 'd/m/Y', // Cambiado a DD/MM/YYYY
        locale: 'es',
        onChange: function (selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                endDatePicker.set('minDate', dateStr);
                const endDate = $('#endDate').val();
                if (endDate && new Date(endDate.split('/').reverse().join('-')) < new Date(dateStr.split('/').reverse().join('-'))) {
                    $('#endDate').val(dateStr);
                }
            } else {
                endDatePicker.set('minDate', null);
            }
        }
    });

    const endDatePicker = flatpickr('#endDate', {
        dateFormat: 'd/m/Y', // Cambiado a DD/MM/YYYY
        locale: 'es',
        onChange: function (selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                startDatePicker.set('maxDate', dateStr);
                const startDate = $('#startDate').val();
                if (startDate && new Date(startDate.split('/').reverse().join('-')) > new Date(dateStr.split('/').reverse().join('-'))) {
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
    let initialYear = $('#yearFilter').val();
    updateMesDenunciasChart(initialYear);

    // Cargar datos iniciales del dashboard
    const startDate = $('#startDate').val();
    const endDate = $('#endDate').val();
    loadDashboardData(startDate, endDate, null, null, '', null);

    $('#tableCategoriasDenuncias').bootstrapTable({
        url: Server + 'dashboard/getResumenCategoriasConFiltros',
        method: 'post',
        contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
        queryParams: function (params) {
            return {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                cliente: $('#clienteFilter').val(),
                sucursal: $('#sucursalFilter').val(),
                departamento: $('#departamentoFilter').val(),
                anonimo: $('#anonimoFilter').val()
            };
        },
        columns: [
            {
                field: 'categoria',
                title: 'Categoría',
                sortable: true
            },
            {
                field: 'total_denuncias',
                title: 'Denuncias',
                sortable: true,
                align: 'center'
            },
            {
                field: 'total_subcategorias',
                title: 'Subcategorías',
                sortable: true,
                align: 'center'
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            cargarDetalleSubcategorias(row.id, $detail);
        }
    });

    // Aplicar filtros al enviar el formulario
    $('#dateFilterForm').submit(function (e) {
        e.preventDefault();

        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const sucursal = $('#sucursalFilter').val();
        const departamento = $('#departamentoFilter').val();
        const anonimo = $('#anonimoFilter').val();
        const cliente = $('#clienteFilter').val();

        loadDashboardData(startDate, endDate, sucursal, departamento, anonimo, cliente);

        refreshCategoriasTable();
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
        $('#clienteFilter').val('').trigger('change');

        // Recargar los datos con filtros vacíos (para mostrar toda la información)
        loadDashboardData(null, null, null, null, '', null);
    });

    // Evento para cambiar el año en la gráfica de Mes de Recepción de Denuncia
    $('#yearFilter').change(function () {
        let selectedYear = $(this).val();
        updateMesDenunciasChart(selectedYear);
    });
});

// Función para actualizar la gráfica de Mes de Recepción de Denuncia
function updateMesDenunciasChart(year) {
    $.ajax({
        url: `${Server}dashboard/getDenunciasPorAnio`,
        method: 'POST',
        data: { year: year },
        dataType: 'json',
        success: function (response) {
            if (response.denunciasPorMes) {
                let meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

                // Asigna los nombres de los meses como etiquetas
                mesDenunciasChart.data.labels = meses;

                // Asigna los valores de total para cada mes
                mesDenunciasChart.data.datasets[0].data = response.denunciasPorMes.map(item => item.total);

                // Actualiza la gráfica
                mesDenunciasChart.update();

                // Actualiza el total en la vista
                let totalDenuncias = response.denunciasPorMes.reduce((sum, item) => sum + item.total, 0);
                $('#totalMesDenuncias').html(totalDenuncias);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar datos del gráfico por año:', error);
        }
    });
}

function refreshCategoriasTable() {
    $('#tableCategoriasDenuncias').bootstrapTable('refresh', {
        url: Server + 'dashboard/getResumenCategoriasConFiltros',
        query: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            cliente: $('#clienteFilter').val(),
            sucursal: $('#sucursalFilter').val(),
            departamento: $('#departamentoFilter').val(),
            anonimo: $('#anonimoFilter').val()
        }
    });
}

function cargarDetalleSubcategorias(categoriaId, $elemento) {
    $.post(
        Server + 'dashboard/getSubcategoriasPorCategoria',
        {
            categoria_id: categoriaId,
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            cliente: $('#clienteFilter').val(),
            sucursal: $('#sucursalFilter').val(),
            departamento: $('#departamentoFilter').val(),
            anonimo: $('#anonimoFilter').val()
        },
        function (response) {
            // Renderizar la tabla dentro de $elemento
            let html = '<div id="subcategoriasTable-' + categoriaId + '" class="table-responsive"><table class="table table-bordered"><thead class="table-secondary"><tr><th class="p-1">Subcategoría</th><th class="p-1">Total Denuncias</th></tr></thead><tbody>';

            response.data.forEach(item => {
                html += `<tr><td>${item.nombre}</td><td>${item.total}</td></tr>`;
            });

            html += '</tbody></table></div>';
            $elemento.html(html);
        }
    );
}
