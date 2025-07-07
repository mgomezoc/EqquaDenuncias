$(document).ready(function () {
    // --- SELECTORES DE ELEMENTOS ---
    const $formBuscarDenuncia = $('#formBuscarDenuncia');
    const $resultadoDenuncia = $('#resultadoDenuncia');

    // Selectores para detalles de la denuncia
    const $denunciaId = $('#denunciaId');
    const $estado_nombre = $('#estado_nombre');
    const $fechaHoraReporte = $('#fechaHoraReporte');
    const $sucursalNombre = $('#sucursalNombre');
    const $categoriaNombre = $('#categoriaNombre');
    const $descripcionDenuncia = $('#descripcionDenuncia');

    // Selectores para el historial y formulario de comentarios
    const $timelineHistorial = $('#timeline-historial');
    const $formAgregarComentario = $('#formAgregarComentario');
    const $idDenunciaInput = $('#id_denuncia');

    // --- URLs DE LA API ---
    const CONSULTA_URL = `${Server}/denuncias/consultar`;
    const COMENTARIO_URL = `${Server}/comentarios/guardar`;

    // --- MAPEO DE ESTADOS Y ESTILOS ---
    const estadoMap = {
        Recepción: 'Denuncia Recibida',
        Clasificada: 'En Proceso de Revisión',
        'Revisada por Calidad': 'Revisión Interna Completada',
        'Liberada al Cliente': 'En Revisión por el Cliente',
        'En Revisión por Cliente': 'Revisión en Proceso por el Cliente',
        Cerrada: 'Denuncia Cerrada'
    };

    const estadoClassMap = {
        Recepción: 'bg-secondary',
        Clasificada: 'bg-info',
        'Revisada por Calidad': 'bg-primary',
        'Liberada al Cliente': 'bg-warning text-dark',
        'En Revisión por Cliente': 'bg-warning text-dark',
        Cerrada: 'bg-success'
    };

    //======================================================================
    // EVENTO PRINCIPAL: BUSCAR DENUNCIA
    //======================================================================
    $formBuscarDenuncia.on('submit', function (event) {
        event.preventDefault();
        const formData = {
            folio: $('#folio').val().trim(),
            id_cliente: $('#id_cliente').val()
        };

        if (!formData.folio) {
            Swal.fire('Campo vacío', 'Por favor, ingrese su número de folio.', 'warning');
            return;
        }
        consultarDenuncia(formData);
    });

    //======================================================================
    // FUNCIÓN PARA CONSULTAR DENUNCIA (AJAX GET)
    //======================================================================
    function consultarDenuncia(data) {
        $.get(CONSULTA_URL, data)
            .done(function (response) {
                if (response.denuncia) {
                    mostrarDetallesDenuncia(response);
                } else {
                    Swal.fire('Denuncia no encontrada', 'No se encontró ninguna denuncia con ese folio.', 'error');
                    $resultadoDenuncia.hide();
                }
            })
            .fail(function (error) {
                console.error('Error al buscar la denuncia:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar la denuncia. Por favor, intenta nuevamente.', 'error');
                $resultadoDenuncia.hide();
            });
    }

    //======================================================================
    // FUNCIÓN PARA MOSTRAR TODOS LOS DETALLES EN LA VISTA
    //======================================================================
    function mostrarDetallesDenuncia(data) {
        $resultadoDenuncia.show();
        const { denuncia, comentarios, archivos } = data;

        // --- 1. Poblar detalles principales ---
        const estadoAmigable = estadoMap[denuncia.estado_nombre] || denuncia.estado_nombre;
        const estadoClass = estadoClassMap[denuncia.estado_nombre] || 'bg-dark';

        $estado_nombre.text(estadoAmigable).removeClass().addClass(`badge fs-6 ${estadoClass}`);
        $denunciaId.text(denuncia.id || 'N/A');
        $fechaHoraReporte.text(denuncia.fecha_hora_reporte || 'N/A');
        $sucursalNombre.text(denuncia.sucursal_nombre || 'N/A');
        $categoriaNombre.text(denuncia.categoria_nombre || 'N/A');
        $descripcionDenuncia.text(denuncia.descripcion || 'N/A');

        // --- 2. Preparar y mostrar el historial combinado ---
        const historialEventos = [];

        // Agregar archivos de la denuncia inicial a la lista de eventos
        if (archivos && archivos.length > 0) {
            archivos.forEach(archivo => {
                historialEventos.push({
                    fecha: denuncia.fecha_hora_reporte,
                    tipo: esImagen(archivo.nombre_archivo) ? 'imagen_inicial' : 'archivo_inicial',
                    data: archivo
                });
            });
        }

        // Agregar comentarios y sus archivos adjuntos a la lista de eventos
        if (comentarios && Object.keys(comentarios).length > 0) {
            $.each(comentarios, (key, comentario) => {
                // Agregar el comentario en sí
                historialEventos.push({
                    fecha: comentario.fecha_comentario,
                    tipo: 'comentario',
                    data: comentario
                });

                // Agregar los archivos de ese comentario
                if (comentario.archivos && comentario.archivos.length > 0) {
                    comentario.archivos.forEach(archivo => {
                        historialEventos.push({
                            fecha: comentario.fecha_comentario, // Usar la misma fecha del comentario
                            tipo: esImagen(archivo.nombre_archivo) ? 'imagen_comentario' : 'archivo_comentario',
                            data: archivo,
                            comentarioId: comentario.id
                        });
                    });
                }
            });
        }

        // Ordenar todos los eventos por fecha
        historialEventos.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

        // Renderizar en la línea de tiempo
        mostrarHistorialEnTimeline(historialEventos);

        // --- 3. Controlar visibilidad del formulario de comentarios ---
        if ([4, 5].includes(parseInt(denuncia.estado_actual))) {
            $formAgregarComentario.show();
            $idDenunciaInput.val(denuncia.id);
        } else {
            $formAgregarComentario.hide();
        }
    }

    //======================================================================
    // FUNCIÓN PARA RENDERIZAR EL HISTORIAL EN LA LÍNEA DE TIEMPO
    //======================================================================
    function mostrarHistorialEnTimeline(eventos) {
        $timelineHistorial.empty();

        if (eventos.length === 0) {
            $timelineHistorial.html('<p class="text-center text-muted">No hay eventos en el historial.</p>');
            return;
        }

        eventos.forEach(evento => {
            let timelineItemHTML = '';
            const rutaArchivo = `${Server}/${evento.data.ruta_archivo}`;

            switch (evento.tipo) {
                case 'comentario':
                    timelineItemHTML = `
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="far fa-comment-dots"></i></div>
                            <div class="timeline-card">
                                <time class="timeline-time">${evento.fecha}</time>
                                <p>${evento.data.contenido}</p>
                            </div>
                        </li>`;
                    break;

                case 'archivo_inicial':
                case 'archivo_comentario':
                    const iconoArchivo = obtenerIconoArchivo(evento.data.nombre_archivo);
                    const textoAdjunto = evento.tipo === 'archivo_inicial' ? 'Archivo adjuntado en la denuncia inicial:' : 'Archivo adjunto en comentario:';
                    timelineItemHTML = `
                        <li class="timeline-item">
                            <div class="timeline-icon" style="background-color: #6c757d;"><i class="fas fa-paperclip"></i></div>
                            <div class="timeline-card">
                                <time class="timeline-time">${evento.fecha}</time>
                                <p class="mb-2">${textoAdjunto}</p>
                                <a href="${rutaArchivo}" class="timeline-file" download>
                                    <i class="far ${iconoArchivo} file-icon"></i>
                                    <span class="file-info">${evento.data.nombre_archivo}</span>
                                </a>
                            </div>
                        </li>`;
                    break;

                case 'imagen_inicial':
                case 'imagen_comentario':
                    const textoImagen = evento.tipo === 'imagen_inicial' ? 'Imagen adjuntada en la denuncia inicial:' : 'Imagen adjunta en comentario:';
                    const lightboxGroup = evento.comentarioId ? `comentario-${evento.comentarioId}` : 'denuncia-inicial';
                    timelineItemHTML = `
                        <li class="timeline-item">
                            <div class="timeline-icon" style="background-color: #198754;"><i class="far fa-image"></i></div>
                            <div class="timeline-card">
                                <time class="timeline-time">${evento.fecha}</time>
                                <p class="mb-2">${textoImagen}</p>
                                <a href="${rutaArchivo}" data-lightbox="${lightboxGroup}" data-title="${evento.data.nombre_archivo}">
                                    <img src="${rutaArchivo}" class="img-fluid timeline-image-attachment" alt="Evidencia: ${evento.data.nombre_archivo}">
                                </a>
                            </div>
                        </li>`;
                    break;
            }
            $timelineHistorial.append(timelineItemHTML);
        });
    }

    //======================================================================
    // EVENTO PARA ENVIAR NUEVO COMENTARIO
    //======================================================================
    $formAgregarComentario.on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        const $submitButton = $(this).find('button[type="submit"]');

        $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');

        $.ajax({
            url: COMENTARIO_URL,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.success) {
                    Swal.fire('Comentario enviado', 'Tu comentario se ha enviado con éxito.', 'success');
                    $('#nuevo_comentario').val('');
                    $('#archivo_comentario').val('');
                    consultarDenuncia({ folio: $('#folio').val().trim(), id_cliente: $('#id_cliente').val() });
                } else {
                    Swal.fire('Error', data.message || 'No se pudo enviar el comentario.', 'error');
                }
            },
            error: function (error) {
                console.error('Error al enviar el comentario:', error);
                Swal.fire('Error', 'Ocurrió un error de comunicación. Intenta nuevamente.', 'error');
            },
            complete: function () {
                $submitButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar Información');
            }
        });
    });

    //======================================================================
    // FUNCIONES AUXILIARES
    //======================================================================
    function esImagen(nombreArchivo) {
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);
    }

    function obtenerIconoArchivo(nombreArchivo) {
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        switch (extension) {
            case 'pdf':
                return 'fa-file-pdf';
            case 'doc':
            case 'docx':
                return 'fa-file-word';
            case 'xls':
            case 'xlsx':
                return 'fa-file-excel';
            case 'zip':
            case 'rar':
                return 'fa-file-archive';
            default:
                return 'fa-file-alt';
        }
    }
});
