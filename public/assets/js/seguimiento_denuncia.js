document.addEventListener('DOMContentLoaded', function () {
    // Elementos del DOM
    const formBuscarDenuncia = document.getElementById('formBuscarDenuncia');
    const resultadoDenuncia = document.getElementById('resultadoDenuncia');
    const tablaDetalleDenuncia = document.getElementById('tablaDetalleDenuncia');
    const tablaComentarios = document.getElementById('tablaComentariosCuerpo');
    const formAgregarComentario = document.getElementById('formAgregarComentario');
    const nuevoComentario = document.getElementById('nuevo_comentario');
    const idDenunciaInput = document.getElementById('id_denuncia');

    // URLs reutilizables
    const CONSULTA_URL = `${Server}/public/denuncias/consultar`;
    const COMENTARIO_URL = `${Server}/comentarios/guardar`;

    // Evento para buscar la denuncia
    formBuscarDenuncia.addEventListener('submit', function (event) {
        event.preventDefault();
        const folio = document.getElementById('folio').value.trim();

        if (!folio) {
            alert('Por favor, ingrese su número de folio.');
            return;
        }

        consultarDenuncia(folio);
    });

    // Función para realizar la consulta de la denuncia vía AJAX
    function consultarDenuncia(folio) {
        fetch(`${CONSULTA_URL}?folio=${folio}`)
            .then(response => response.json())
            .then(data => {
                if (data.denuncia) {
                    mostrarDetallesDenuncia(data);
                } else {
                    alert('Denuncia no encontrada');
                    resultadoDenuncia.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error al buscar la denuncia:', error);
                alert('Ocurrió un error al buscar la denuncia. Por favor, intenta nuevamente.');
            });
    }

    // Mostrar detalles de la denuncia
    function mostrarDetallesDenuncia(data) {
        resultadoDenuncia.style.display = 'block';

        // Verificar y establecer valores, usar 'N/A' para valores indefinidos
        tablaDetalleDenuncia.innerHTML = `
            <tr><td>ID de Denuncia</td><td>${data.denuncia.id || 'N/A'}</td></tr>
            <tr><td>Fecha y Hora de Reporte</td><td>${data.denuncia.fecha_hora_reporte || 'N/A'}</td></tr>
            <tr><td>Sucursal</td><td>${data.denuncia.id_sucursal || 'N/A'}</td></tr>
            <tr><td>Categoría</td><td>${data.denuncia.categoria || 'N/A'}</td></tr>
            <tr><td>Subcategoría</td><td>${data.denuncia.subcategoria || 'N/A'}</td></tr>
            <tr><td>Descripción</td><td>${data.denuncia.descripcion || 'N/A'}</td></tr>
        `;

        mostrarComentarios(data.comentarios);
        mostrarArchivos(data.archivos);

        // Si la denuncia está en los estados 4 o 5, mostrar el formulario para agregar comentario
        if ([4, 5].includes(parseInt(data.denuncia.estado_actual))) {
            formAgregarComentario.style.display = 'block';
            idDenunciaInput.value = data.denuncia.id;
        } else {
            formAgregarComentario.style.display = 'none';
        }
    }

    // Mostrar comentarios
    function mostrarComentarios(comentarios) {
        tablaComentarios.innerHTML = ''; // Limpiar los comentarios existentes
        const comentariosKeys = Object.keys(comentarios);

        if (comentariosKeys.length > 0) {
            comentariosKeys.forEach(key => {
                const comentario = comentarios[key];
                tablaComentarios.innerHTML += `
                    <tr>
                        <td>${comentario.fecha_comentario || 'N/A'}</td>
                        <td>${comentario.contenido || 'N/A'}</td>
                    </tr>
                `;
            });
        } else {
            tablaComentarios.innerHTML = '<tr><td colspan="2" class="text-center">No hay comentarios disponibles.</td></tr>';
        }
    }

    // Mostrar archivos adjuntos
    function mostrarArchivos(archivos) {
        const listaArchivos = document.getElementById('listaArchivos');
        const archivosAdjuntos = document.getElementById('archivosAdjuntos');

        if (archivos && archivos.length > 0) {
            archivosAdjuntos.style.display = 'block'; // Mostrar la sección de archivos
            listaArchivos.innerHTML = ''; // Limpiar lista antes de agregar nuevos archivos

            archivos.forEach(archivo => {
                listaArchivos.innerHTML += `
                    <li><a href="${Server}${archivo.ruta_archivo}" target="_blank">${archivo.nombre_archivo}</a></li>
                `;
            });
        } else {
            archivosAdjuntos.style.display = 'none'; // Ocultar la sección si no hay archivos
        }
    }

    // Evento para enviar un nuevo comentario
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
                    alert('Comentario enviado con éxito');
                    nuevoComentario.value = ''; // Limpiar el campo de comentario
                    consultarDenuncia(document.getElementById('folio').value.trim()); // Recargar los comentarios
                } else {
                    alert('Error al enviar el comentario');
                }
            })
            .catch(error => {
                console.error('Error al enviar comentario:', error);
                alert('Ocurrió un error al enviar el comentario.');
            });
    });
});
