document.addEventListener('DOMContentLoaded', function () {
    const formBuscarDenuncia = document.getElementById('formBuscarDenuncia');
    const resultadoDenuncia = document.getElementById('resultadoDenuncia');
    const denunciaId = document.getElementById('denunciaId');
    const fechaHoraReporte = document.getElementById('fechaHoraReporte');
    const sucursalNombre = document.getElementById('sucursalNombre');
    const categoriaNombre = document.getElementById('categoriaNombre');
    const subcategoriaNombre = document.getElementById('subcategoriaNombre');
    const descripcionDenuncia = document.getElementById('descripcionDenuncia');
    const contenedorComentarios = document.getElementById('contenedorComentarios');
    const formAgregarComentario = document.getElementById('formAgregarComentario');
    const nuevoComentario = document.getElementById('nuevo_comentario');
    const idDenunciaInput = document.getElementById('id_denuncia');

    const CONSULTA_URL = `${Server}/public/denuncias/consultar`;
    const COMENTARIO_URL = `${Server}/comentarios/guardar`;

    formBuscarDenuncia.addEventListener('submit', function (event) {
        event.preventDefault();
        const folio = document.getElementById('folio').value.trim();

        if (!folio) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor, ingrese su número de folio.',
                confirmButtonText: 'OK'
            });
            return;
        }

        consultarDenuncia(folio);
    });

    function consultarDenuncia(folio) {
        fetch(`${CONSULTA_URL}?folio=${folio}`)
            .then(response => response.json())
            .then(data => {
                if (data.denuncia) {
                    mostrarDetallesDenuncia(data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Denuncia no encontrada',
                        text: 'No se encontró ninguna denuncia con ese folio.',
                        confirmButtonText: 'Intentar de nuevo'
                    });
                    resultadoDenuncia.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error al buscar la denuncia:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al buscar la denuncia. Por favor, intenta nuevamente.',
                    confirmButtonText: 'OK'
                });
            });
    }

    function mostrarDetallesDenuncia(data) {
        resultadoDenuncia.style.display = 'block';

        // Mostrar los detalles de la denuncia
        denunciaId.textContent = data.denuncia.id || 'N/A';
        fechaHoraReporte.textContent = data.denuncia.fecha_hora_reporte || 'N/A';
        sucursalNombre.textContent = data.denuncia.sucursal_nombre || 'N/A';
        categoriaNombre.textContent = data.denuncia.categoria_nombre || 'N/A';
        subcategoriaNombre.textContent = data.denuncia.subcategoria_nombre || 'N/A';
        descripcionDenuncia.textContent = data.denuncia.descripcion || 'N/A';

        mostrarComentarios(data.comentarios);
        mostrarArchivos(data.archivos);

        // Mostrar formulario para agregar comentarios si la denuncia está en estados 4 o 5
        if ([4, 5].includes(parseInt(data.denuncia.estado_actual))) {
            formAgregarComentario.style.display = 'block';
            idDenunciaInput.value = data.denuncia.id;
        } else {
            formAgregarComentario.style.display = 'none';
        }
    }

    function mostrarComentarios(comentarios) {
        contenedorComentarios.innerHTML = ''; // Limpiar comentarios anteriores
        const comentariosKeys = Object.keys(comentarios);

        if (comentariosKeys.length > 0) {
            comentariosKeys.forEach(key => {
                const comentario = comentarios[key];
                const comentarioHTML = `
                    <div class="alert alert-secondary" role="alert">
                        <div class="d-flex justify-content-between">
                            <strong>${comentario.nombre_usuario}</strong>
                            <span class="text-muted">${comentario.fecha_comentario}</span>
                        </div>
                        <p>${comentario.contenido}</p>
                    </div>
                `;
                contenedorComentarios.innerHTML += comentarioHTML;
            });
        } else {
            contenedorComentarios.innerHTML = '<p class="text-center">No hay comentarios disponibles.</p>';
        }
    }

    function mostrarArchivos(archivos) {
        const listaArchivos = document.getElementById('listaArchivos');
        const archivosAdjuntos = document.getElementById('archivosAdjuntos');

        if (archivos && archivos.length > 0) {
            archivosAdjuntos.style.display = 'block';
            listaArchivos.innerHTML = ''; // Limpiar lista

            archivos.forEach(archivo => {
                const extension = archivo.nombre_archivo.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                    // Si es una imagen, mostrar con Lightbox
                    listaArchivos.innerHTML += `
                        <li class="col-md-3">
                            <a href="${Server}/${archivo.ruta_archivo}" data-lightbox="galeria-denuncia" data-title="${archivo.nombre_archivo}">
                                <img src="${Server}/${archivo.ruta_archivo}" class="img-fluid" alt="${archivo.nombre_archivo}">
                            </a>
                        </li>
                    `;
                } else {
                    // Otros archivos (PDF, DOCX, etc.) como enlaces normales
                    listaArchivos.innerHTML += `
                        <li class="col-md-12">
                            <a href="${Server}/${archivo.ruta_archivo}" target="_blank">${archivo.nombre_archivo}</a>
                        </li>
                    `;
                }
            });
        } else {
            archivosAdjuntos.style.display = 'none';
        }
    }

    formAgregarComentario.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(formAgregarComentario);

        fetch(COMENTARIO_URL, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Comentario enviado',
                        text: 'Tu comentario se ha enviado con éxito.',
                        confirmButtonText: 'OK'
                    });
                    nuevoComentario.value = ''; // Limpiar campo de comentario
                    consultarDenuncia(document.getElementById('folio').value.trim()); // Volver a cargar los comentarios
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo enviar el comentario. Intenta de nuevo.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error al enviar el comentario:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al enviar el comentario. Por favor, intenta nuevamente.',
                    confirmButtonText: 'OK'
                });
            });
    });
});
