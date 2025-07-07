let $formBuscarDenuncia;
let $resultadoDenuncia;
let $denunciaId;
let $estado_nombre;
let $fechaHoraReporte;
let $sucursalNombre;
let $categoriaNombre;
let $subcategoriaNombre;
let $descripcionDenuncia;
let $contenedorComentarios;
let $formAgregarComentario;
let $nuevoComentario;
let $idDenunciaInput;
let $listaArchivos;
let $archivosAdjuntos;

$(document).ready(function () {
    $formBuscarDenuncia = $('#formBuscarDenuncia');
    $resultadoDenuncia = $('#resultadoDenuncia');
    $denunciaId = $('#denunciaId');
    $estado_nombre = $('#estado_nombre');
    $fechaHoraReporte = $('#fechaHoraReporte');
    $sucursalNombre = $('#sucursalNombre');
    $categoriaNombre = $('#categoriaNombre');
    $subcategoriaNombre = $('#subcategoriaNombre');
    $descripcionDenuncia = $('#descripcionDenuncia');
    $contenedorComentarios = $('#contenedorComentarios');
    $formAgregarComentario = $('#formAgregarComentario');
    $nuevoComentario = $('#nuevo_comentario');
    $idDenunciaInput = $('#id_denuncia');
    $listaArchivos = $('#listaArchivos');
    $archivosAdjuntos = $('#archivosAdjuntos');

    const CONSULTA_URL = `${Server}/denuncias/consultar`;
    const COMENTARIO_URL = `${Server}/comentarios/guardar`;

    // Evento para buscar la denuncia
    $formBuscarDenuncia.on('submit', function (event) {
        event.preventDefault();
        const formData = $formBuscarDenuncia.serializeObject();
        formData.folio = formData.folio.trim();

        if (!formData.folio) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor, ingrese su número de folio.',
                confirmButtonText: 'OK'
            });
            return;
        }

        consultarDenuncia(formData);
    });

    // Función para consultar denuncia
    function consultarDenuncia(data) {
        $.get(`${CONSULTA_URL}`, { folio: data.folio, id_cliente: data.id_cliente })
            .done(function (response) {
                if (response.denuncia) {
                    mostrarDetallesDenuncia(response);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Denuncia no encontrada',
                        text: 'No se encontró ninguna denuncia con ese folio.',
                        confirmButtonText: 'Intentar de nuevo'
                    });
                    $resultadoDenuncia.hide();
                }
            })
            .fail(function (error) {
                console.error('Error al buscar la denuncia:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al buscar la denuncia. Por favor, intenta nuevamente.',
                    confirmButtonText: 'OK'
                });
                $resultadoDenuncia.hide();
            });
    }

    // Función para mostrar los detalles de la denuncia
    const estadoMap = {
        Recepción: 'Denuncia Recibida',
        Clasificada: 'En Proceso de Revisión',
        'Revisada por Calidad': 'Revisión Interna Completada',
        'Liberada al Cliente': 'En Revisión por el Cliente',
        'En Revisión por Cliente': 'Revisión en Proceso por el Cliente',
        Cerrada: 'Denuncia Cerrada'
    };

    function mostrarDetallesDenuncia(data) {
        $resultadoDenuncia.show();

        // Obtener el nombre del estado desde el mapeo o usar el original si no se encuentra en el mapeo
        const estadoAmigable = estadoMap[data.denuncia.estado_nombre] || data.denuncia.estado_nombre;

        // Mostrar los detalles de la denuncia con el nombre del estado traducido
        $estado_nombre.text(estadoAmigable);
        $denunciaId.text(data.denuncia.id || 'N/A');
        $fechaHoraReporte.text(data.denuncia.fecha_hora_reporte || 'N/A');
        $sucursalNombre.text(data.denuncia.sucursal_nombre || 'N/A');
        $categoriaNombre.text(data.denuncia.categoria_nombre || 'N/A');
        $subcategoriaNombre.text(data.denuncia.subcategoria_nombre || 'N/A');
        $descripcionDenuncia.text(data.denuncia.descripcion || 'N/A');

        mostrarComentarios(data.comentarios);
        mostrarArchivos(data.archivos);

        // Mostrar formulario para agregar comentarios si la denuncia está en estados 4 o 5
        if ([4, 5].includes(parseInt(data.denuncia.estado_actual))) {
            $formAgregarComentario.show();
            $idDenunciaInput.val(data.denuncia.id);
        } else {
            $formAgregarComentario.hide();
        }
    }

    // Función para mostrar los comentarios
    function mostrarComentarios(comentarios) {
        $contenedorComentarios.empty(); // Limpiar comentarios anteriores

        const comentariosKeys = Object.keys(comentarios);

        if (comentariosKeys.length > 0) {
            $.each(comentariosKeys, function (index, key) {
                const comentario = comentarios[key];
                const archivos = comentario.archivos || [];

                let archivosHTML = '';
                if (archivos.length > 0) {
                    archivosHTML += `<div class="mt-2">`;
                    archivos.forEach(archivo => {
                        const extension = archivo.nombre_archivo.split('.').pop().toLowerCase();
                        const ruta = `${Server}/${archivo.ruta_archivo}`;

                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                            archivosHTML += `
                                <a href="${ruta}" data-lightbox="comentario-${comentario.id}" data-title="${archivo.nombre_archivo}">
                                    <img src="${ruta}" alt="${archivo.nombre_archivo}" class="img-thumbnail me-2 mb-2" style="max-width: 150px;">
                                </a>
                            `;
                        } else {
                            archivosHTML += `
                                <div>
                                    <a href="${ruta}" target="_blank" rel="noopener">${archivo.nombre_archivo}</a>
                                </div>
                            `;
                        }
                    });
                    archivosHTML += `</div>`;
                }

                const comentarioHTML = `
                    <div class="alert alert-light" role="alert">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">${comentario.fecha_comentario}</span>
                        </div>
                        <p>${comentario.contenido}</p>
                        ${archivosHTML}
                    </div>
                `;
                $contenedorComentarios.append(comentarioHTML);
            });
        } else {
            $contenedorComentarios.html('<p class="text-center">No hay comentarios disponibles.</p>');
        }
    }

    // Función para mostrar los archivos adjuntos
    function mostrarArchivos(archivos) {
        $listaArchivos.empty(); // Limpiar lista de archivos

        if (archivos && archivos.length > 0) {
            $archivosAdjuntos.show(); // Mostrar sección de archivos

            $.each(archivos, function (index, archivo) {
                const extension = archivo.nombre_archivo.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                    // Si es una imagen, mostrar con Lightbox
                    const archivoHTML = `
                        <li class="col-md-3">
                            <a href="${Server}/${archivo.ruta_archivo}" data-lightbox="galeria-denuncia" data-title="${archivo.nombre_archivo}">
                                <img src="${Server}/${archivo.ruta_archivo}" class="img-fluid" alt="${archivo.nombre_archivo}">
                            </a>
                        </li>
                    `;
                    $listaArchivos.append(archivoHTML);
                } else {
                    // Otros archivos como enlaces normales
                    const archivoHTML = `
                        <li class="col-md-12">
                            <a href="${Server}/${archivo.ruta_archivo}" target="_blank">${archivo.nombre_archivo}</a>
                        </li>
                    `;
                    $listaArchivos.append(archivoHTML);
                }
            });
        } else {
            $archivosAdjuntos.hide(); // Ocultar si no hay archivos
        }
    }

    // Enviar un nuevo comentario
    $formAgregarComentario.on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        // Seleccionamos el textarea y el botón de envío
        const $textarea = $('#nuevo_comentario');
        const $submitButton = $(this).find('button[type="submit"]');

        // Deshabilitar el textarea y el botón, y cambiar el texto del botón
        $textarea.prop('disabled', true);
        $submitButton.prop('disabled', true);
        $submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...');

        $.ajax({
            url: COMENTARIO_URL,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Comentario enviado',
                        text: 'Tu comentario se ha enviado con éxito.',
                        confirmButtonText: 'OK'
                    });
                    $textarea.val(''); // Limpiar campo de comentario
                    consultarDenuncia({ folio: $('#folio').val().trim(), id_cliente: $('#id_cliente').val() }); // Volver a cargar los comentarios
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo enviar el comentario. Intenta de nuevo.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (error) {
                console.error('Error al enviar el comentario:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al enviar el comentario. Por favor, intenta nuevamente.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function () {
                // Rehabilitar el textarea y el botón, y restaurar el texto del botón original
                $textarea.prop('disabled', false);
                $submitButton.prop('disabled', false);
                $submitButton.html('<i class="fas fa-comment-dots"></i> Enviar Comentario');
            }
        });
    });
});
