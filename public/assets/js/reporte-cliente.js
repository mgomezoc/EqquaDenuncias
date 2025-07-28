$(document).ready(function () {
    let $formFiltros = $('#formFiltros');

    // Inicialización de la tabla BootstrapTable
    const $tablaDenuncias = $('#tablaDenuncias').bootstrapTable({
        url: `${Server}reportes/listarParaCliente`,
        method: 'post',
        pagination: true,
        sidePagination: 'server',
        pageSize: 50,
        pageList: [50, 100, 150, 200],
        search: true,
        searchAlign: 'left',
        showRefresh: true,
        toolbar: '#toolbar',
        locale: 'es-MX',
        columns: [
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                clickToSelect: false,
                formatter: function (value, row) {
                    return `
                        <div class="dropdown">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i><span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item view-detail" href="#" data-id="${row.id}">
                                        <i class="fas fa-eye me-2"></i> Ver Detalle
                                    </a>
                                </li>
                            </ul>
                        </div>
                    `;
                },
                events: {
                    'click .view-detail': function (e, value, row) {
                        e.preventDefault();
                        $.get(`${Server}denuncias/detalle/${row.id}`, function (data) {
                            // Reutilizar lógica de denuncias.js
                            const modal = new bootstrap.Modal($('#modalVerDetalle'));

                            const fechaIncidente = formatoFecha(data.fecha_incidente);
                            const getFileIcon = filename => {
                                const ext = filename.split('.').pop().toLowerCase();
                                const icons = {
                                    pdf: 'fa-file-pdf text-danger',
                                    doc: 'fa-file-word text-primary',
                                    docx: 'fa-file-word text-primary',
                                    xls: 'fa-file-excel text-success',
                                    xlsx: 'fa-file-excel text-success',
                                    zip: 'fa-file-zipper text-warning',
                                    rar: 'fa-file-zipper text-warning',
                                    txt: 'fa-file-lines text-secondary',
                                    csv: 'fa-file-csv text-info'
                                };
                                return icons[ext] || 'fa-file text-secondary';
                            };

                            let archivosHtml = '';
                            if (data.archivos && data.archivos.length > 0) {
                                archivosHtml += `
                                    <div class="mt-4">
                                        <h5 class="mb-3">
                                            <i class="fas fa-paperclip me-2"></i>Archivos Adjuntos 
                                            <span class="badge bg-secondary ms-2">${data.archivos.length}</span>
                                        </h5>
                                        <div class="row g-3">`;

                                data.archivos.forEach((archivo, idx) => {
                                    const url = `${Server}${archivo.ruta_archivo}`;
                                    const ext = archivo.nombre_archivo.split('.').pop().toLowerCase();
                                    const esImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                                    const nombreCorto = archivo.nombre_archivo.length > 25 ? archivo.nombre_archivo.substring(0, 22) + '...' + ext : archivo.nombre_archivo;

                                    if (esImagen) {
                                        archivosHtml += `
                                            <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay: ${idx * 0.1}s">
                                                <div class="card shadow-sm h-100 archivo-card">
                                                    <a href="${url}" 
                                                    data-fancybox="denuncia-${data.id}" 
                                                    data-caption="${archivo.nombre_archivo}"
                                                    class="archivo-imagen-link">
                                                        <div class="archivo-imagen-container">
                                                            <img src="${url}" 
                                                                alt="${archivo.nombre_archivo}" 
                                                                class="card-img-top archivo-imagen"
                                                                loading="lazy">
                                                            <div class="archivo-overlay">
                                                                <i class="fas fa-search-plus"></i>
                                                            </div>
                                                        </div>
                                                    </a>
                                                    <div class="card-body p-2">
                                                        <p class="card-text text-center small mb-0" title="${archivo.nombre_archivo}">
                                                            <i class="fas fa-image text-primary me-1"></i>
                                                            ${nombreCorto}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>`;
                                    } else {
                                        archivosHtml += `
                                            <div class="col-6 col-md-4 col-lg-3 animate__animated animate__fadeIn" style="animation-delay: ${idx * 0.1}s">
                                                <div class="card shadow-sm h-100 archivo-card">
                                                    <a href="${url}" 
                                                    target="_blank" 
                                                    class="text-decoration-none archivo-documento-link">
                                                        <div class="card-body text-center py-4">
                                                            <i class="fas ${getFileIcon(archivo.nombre_archivo)} archivo-icono mb-3"></i>
                                                            <p class="card-text small mb-0 text-dark" title="${archivo.nombre_archivo}">
                                                                ${nombreCorto}
                                                            </p>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>`;
                                    }
                                });

                                archivosHtml += `</div></div>`;
                            }

                            const detalleHtml = `
                                <div class="container-fluid animate__animated animate__fadeIn">
                                    <div class="row gy-4">
                                        <div class="col-12">
                                            <div class="border-start border-4 border-primary ps-3">
                                                <h5 class="mb-1 fw-bold text-primary">Folio:</h5>
                                                <span class="fs-6">${data.folio}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3 shadow-sm h-100">
                                                <p class="mb-1 fw-semibold text-muted">Sucursal</p>
                                                <p class="mb-2">${data.sucursal_nombre || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Departamento</p>
                                                <p class="mb-2">${data.departamento_nombre || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Tipo de Denunciante</p>
                                                <p class="mb-2">${data.tipo_denunciante || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Sexo</p>
                                                <p class="mb-2">${data.sexo_nombre || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Medio de Recepción</p>
                                                <p class="mb-0">${data.medio_recepcion || '-'}</p>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3 shadow-sm h-100">
                                                <p class="mb-1 fw-semibold text-muted">Categoría</p>
                                                <p class="mb-2">${data.categoria_nombre || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Subcategoría</p>
                                                <p class="mb-2">${data.subcategoria_nombre || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Área del Incidente</p>
                                                <p class="mb-2">${data.area_incidente || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">¿Cómo se Enteró?</p>
                                                <p class="mb-2">${data.como_se_entero || '-'}</p>

                                                <p class="mb-1 fw-semibold text-muted">Fecha del Incidente</p>
                                                <p class="mb-0">${fechaIncidente || '-'}</p>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="p-4 border rounded bg-white shadow-sm">
                                                <h6 class="fw-bold text-dark mb-2">
                                                    <i class="fas fa-align-left me-2 text-secondary"></i>Descripción
                                                </h6>
                                                <p class="mb-0">${data.descripcion || 'Sin descripción.'}</p>
                                            </div>
                                        </div>

                                        ${archivosHtml}
                                    </div>
                                </div>
                            `;

                            $('#modalVerDetalle .modal-body').html(detalleHtml);
                            modal.show();
                        });
                    }
                }
            },
            { field: 'fecha_hora_reporte', title: 'Fecha Reporte', sortable: true },
            { field: 'estado_nombre', title: 'Estatus', sortable: true },
            { field: 'folio', title: 'Folio', sortable: true },
            { field: 'sucursal_nombre', title: 'Sucursal', sortable: true },
            { field: 'departamento_nombre', title: 'Departamento', sortable: true },
            { field: 'categoria_nombre', title: 'Categoría', sortable: true },
            { field: 'subcategoria_nombre', title: 'SubCategoría', sortable: true },
            { field: 'fecha_incidente', title: 'Fecha Incidente', sortable: true, formatter: dateFormatter },
            { field: 'medio_recepcion', title: 'Medio Recepción', sortable: true }
        ],
        queryParams: function (params) {
            const filtros = $formFiltros.serializeObject();
            return { ...params, ...filtros };
        }
    });

    // Formateador de fechas para las columnas de la tabla
    function dateFormatter(value) {
        if (!value) return '-';
        const date = new Date(value);
        return date.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Inicializar Select2 para selects con búsqueda
    $('.select2').select2();

    // Inicializar flatpickr en los campos de fecha con el mes en curso
    flatpickr('#fecha_inicio', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1) // Primer día del mes actual
    });

    flatpickr('#fecha_fin', {
        dateFormat: 'Y-m-d'
        //defaultDate: new Date() // Fecha actual como fecha de fin por defecto
    });

    // Cargar sucursales del cliente al cargar la página
    cargarSucursales();

    function cargarSucursales() {
        $.ajax({
            url: `${Server}sucursales/obtenerSucursalesPorCliente/${clienteId}`,
            method: 'GET',
            success: function (data) {
                $('#id_sucursal').html('<option value="">Seleccionar Sucursal</option>');
                data.forEach(sucursal => {
                    $('#id_sucursal').append(`<option value="${sucursal.id}">${sucursal.nombre}</option>`);
                });
                $('#id_sucursal').prop('disabled', false);
            }
        });
    }

    // Cargar departamentos al cambiar sucursal
    $('#id_sucursal').on('change', function () {
        const sucursalId = $(this).val();
        if (sucursalId) {
            $.ajax({
                url: `${Server}departamentos/listarDepartamentosPorSucursal/${sucursalId}`,
                method: 'GET',
                success: function (data) {
                    $('#id_departamento').html('<option value="">Seleccionar Departamento</option>');
                    data.forEach(departamento => {
                        $('#id_departamento').append(`<option value="${departamento.id}">${departamento.nombre}</option>`);
                    });
                    $('#id_departamento').prop('disabled', false);
                }
            });
        } else {
            $('#id_departamento').html('<option value="">Seleccionar Departamento</option>').prop('disabled', true);
        }
    });

    // Resetear formulario y refrescar tabla
    $('#btnReset').on('click', function () {
        // Resetear los valores del formulario
        $formFiltros[0].reset();

        // Resetear los selects de Select2
        $('.select2').val(null).trigger('change');

        // Resetear los departamentos y deshabilitar el select
        $('#id_departamento').html('<option value="">Seleccionar Departamento</option>').prop('disabled', true);

        // Refrescar la tabla con los valores predeterminados
        $tablaDenuncias.bootstrapTable('refresh', { query: {} });
    });

    // Filtrar las denuncias al hacer clic en el botón
    $('#btnFiltrar').on('click', function () {
        if ($formFiltros.valid()) {
            $tablaDenuncias.bootstrapTable('refresh');
        }
    });

    // Exportar las denuncias a CSV
    $('#btnExportar').on('click', function () {
        if ($formFiltros.valid()) {
            const formData = $formFiltros.serialize();

            $.ajax({
                url: `${Server}reportes/exportarCSV`,
                method: 'POST',
                data: formData,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data, status, xhr) {
                    const filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].trim();
                    const blob = new Blob([data], { type: 'text/csv' });
                    const link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();
                    window.URL.revokeObjectURL(link.href);
                },
                error: function () {
                    showToast('Error al exportar el CSV.', 'error');
                }
            });
        }
    });
});
