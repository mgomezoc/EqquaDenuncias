document.addEventListener('DOMContentLoaded', function () {
    const formBuscarDenuncia = document.getElementById('formBuscarDenuncia');
    const resultadoDenuncia = document.getElementById('resultadoDenuncia');
    const tablaDetalleDenuncia = document.getElementById('tablaDetalleDenuncia');
    const tablaComentarios = document.getElementById('tablaComentarios').querySelector('tbody');
    const formAgregarComentario = document.getElementById('formAgregarComentario');
    const nuevoComentario = document.getElementById('nuevo_comentario');
    const idDenunciaInput = document.getElementById('id_denuncia');

    // Evento para buscar la denuncia
    formBuscarDenuncia.addEventListener('submit', function (event) {
        event.preventDefault();
        const folio = document.getElementById('folio').value.trim();

        if (!folio) {
            alert('Por favor, ingrese su número de folio.');
            return;
        }

        // Realizar la consulta vía AJAX
        fetch(`${Server}/public/denuncias/consultar?folio=${folio}`)
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
            });
    });

    // Mostrar detalles de la denuncia
    function mostrarDetallesDenuncia(data) {
        resultadoDenuncia.style.display = 'block';
        tablaDetalleDenuncia.innerHTML = `
            <tr><td>ID de Denuncia</td><td>${data.denuncia.id}</td></tr>
            <tr><td>Fecha y Hora de Reporte</td><td>${data.denuncia.fecha_reporte}</td></tr>
            <tr><td>Sucursal</td><td>${data.denuncia.sucursal}</td></tr>
            <tr><td>Categoría</td><td>${data.denuncia.categoria}</td></tr>
            <tr><td>Subcategoría</td><td>${data.denuncia.subcategoria}</td></tr>
            <tr><td>Descripción</td><td>${data.denuncia.descripcion}</td></tr>
        `;

        // Mostrar comentarios
        tablaComentarios.innerHTML = '';
        if (data.comentarios.length > 0) {
            data.comentarios.forEach(comentario => {
                tablaComentarios.innerHTML += `
                    <tr><td>${comentario.fecha}</td><td>${comentario.comentario}</td></tr>
                `;
            });
        } else {
            tablaComentarios.innerHTML = '<tr><td colspan="2" class="text-center">No hay comentarios disponibles.</td></tr>';
        }

        // Si la denuncia está en los estados 4 o 5, permitir agregar comentario
        if ([4, 5].includes(data.denuncia.estado_actual)) {
            formAgregarComentario.style.display = 'block';
            idDenunciaInput.value = data.denuncia.id;
        } else {
            formAgregarComentario.style.display = 'none';
        }
    }

    // Enviar un nuevo comentario
    formAgregarComentario.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(formAgregarComentario);

        fetch(`${Server}/comentarios/guardar`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Comentario enviado con éxito');
                    nuevoComentario.value = '';
                    formBuscarDenuncia.submit(); // Volver a buscar para actualizar comentarios
                } else {
                    alert('Error al enviar el comentario');
                }
            })
            .catch(error => {
                console.error('Error al enviar comentario:', error);
            });
    });
});
