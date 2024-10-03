$(document).ready(function () {
    const $formBuscarDenuncia = $('#formBuscarDenuncia');
    const $resultadoDenuncia = $('#resultadoDenuncia');
    const $denunciaId = $('#denunciaId');
    const $fechaHoraReporte = $('#fechaHoraReporte');
    const $sucursalNombre = $('#sucursalNombre');
    const $categoriaNombre = $('#categoriaNombre');
    const $subcategoriaNombre = $('#subcategoriaNombre');
    const $descripcionDenuncia = $('#descripcionDenuncia');
    const $contenedorComentarios = $('#contenedorComentarios');
    const $formAgregarComentario = $('#formAgregarComentario');
    const $nuevoComentario = $('#nuevo_comentario');
    const $idDenunciaInput = $('#id_denuncia');
    const $listaArchivos = $('#listaArchivos');
    const $archivosAdjuntos = $('#archivosAdjuntos');

    const CONSULTA_URL = `${Server}/public/denuncias/consultar`;
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
            });
    }

    // Función para mostrar los detalles de la denuncia
    function mostrarDetallesDenuncia(data) {
        $resultadoDenuncia.show();

        // Mostrar los detalles de la denuncia
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

        // Convertir el objeto de comentarios a un array si es necesario
        const comentariosKeys = Object.keys(comentarios); // Obtener las claves del objeto

        if (comentariosKeys.length > 0) {
            $.each(comentariosKeys, function (index, key) {
                const comentario = comentarios[key]; // Acceder al comentario usando la clave actual
                const comentarioHTML = `
                <div class="alert alert-secondary" role="alert">
                    <div class="d-flex justify-content-between">
                        <strong>${comentario.nombre_usuario}</strong>
                        <span class="text-muted">${comentario.fecha_comentario}</span>
                    </div>
                    <p>${comentario.contenido}</p>
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
                    $nuevoComentario.val(''); // Limpiar campo de comentario
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
            }
        });
    });
});
