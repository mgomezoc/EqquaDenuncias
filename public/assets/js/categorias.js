$(function () {
    // Compilación de los templates de Handlebars
    tplAccionesTabla = $('#tplAccionesTabla').html();
    tplSubcategoriaTable = Handlebars.compile($('#tplSubcategoriaTable').html());
    $modalCrearCategoria = $('#modalCrearCategoria');
    $modalCrearSubcategoria = $('#modalCrearSubcategoria');

    $('#id_categoria').select2({
        placeholder: 'Seleccione una opción',
        allowClear: true,
        dropdownParent: $('#modalCrearSubcategoria')
    });

    // Inicializar la tabla de Categorías/Subcategorías
    $tablaCategorias = $('#tablaCategorias').bootstrapTable({
        url: `${Server}categorias/listarCategoriasYSubcategorias`,
        columns: [
            { field: 'id', title: 'ID' },
            { field: 'nombre', title: 'Nombre' },
            { field: 'subcategorias_total', title: 'Subcategorías' },
            {
                field: 'operate',
                title: 'Acciones',
                align: 'center',
                valign: 'middle',
                clickToSelect: false,
                formatter: operateFormatter,
                events: window.operateEvents
            }
        ],
        detailView: true,
        onExpandRow: function (index, row, $detail) {
            if (row.subcategorias) {
                $detail.html(tplSubcategoriaTable(row));
            }
        }
    });

    // Validación y envío del formulario de crear/editar categoría
    $('#formCrearCategoria').validate({
        rules: { nombre: { required: true } },
        messages: { nombre: { required: 'Por favor ingrese el nombre de la categoría' } },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();
            const isEdit = formData.id ? true : false;

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}categorias/guardarCategoria`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearCategoria.modal('hide');
                    $tablaCategorias.bootstrapTable('refresh');
                    showToast(isEdit ? '¡Categoría actualizada correctamente!' : '¡Categoría creada correctamente!', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                },
                error: function (xhr) {
                    loadingFormXHR($frm, false);
                    handleError(xhr, 'Error al procesar la categoría.');
                }
            });
        }
    });

    // Validación y envío del formulario de crear/editar subcategoría
    $('#formCrearSubcategoria').validate({
        rules: {
            nombre: { required: true },
            id_categoria: { required: true }
        },
        messages: {
            nombre: { required: 'Por favor ingrese el nombre de la subcategoría' },
            id_categoria: { required: 'Por favor seleccione una categoría' }
        },
        submitHandler: function (form) {
            const $frm = $(form);
            const formData = $frm.serializeObject();
            const isEdit = formData.id ? true : false;

            loadingFormXHR($frm, true);

            ajaxCall({
                url: `${Server}categorias/guardarSubcategoria`,
                method: 'POST',
                data: formData,
                success: function (data) {
                    loadingFormXHR($frm, false);
                    $modalCrearSubcategoria.modal('hide');
                    $tablaCategorias.bootstrapTable('refresh');
                    showToast(isEdit ? '¡Subcategoría actualizada correctamente!' : '¡Subcategoría creada correctamente!', 'success');
                    $frm[0].reset();
                    $frm.find('.is-valid').removeClass('is-valid');
                },
                error: function (xhr) {
                    loadingFormXHR($frm, false);
                    handleError(xhr, 'Error al procesar la subcategoría.');
                }
            });
        }
    });

    // Cargar categorías al abrir el modal de crear/editar subcategoría
    $modalCrearSubcategoria.on('shown.bs.modal', function () {
        loadCategorias();
    });

    // Función para cargar categorías en el select de subcategorías
    function loadCategorias() {
        $.ajax({
            url: `${Server}categorias/listarCategorias`,
            method: 'GET',
            success: function (data) {
                let options = '';
                data.forEach(function (categoria) {
                    options += `<option value="${categoria.id}">${categoria.nombre}</option>`;
                });
                $('#id_categoria').html(options).trigger('change');

                const selected = $('#id_categoria').data('selected');
                $('#id_categoria').val(selected).trigger('change');
            },
            error: function () {
                console.error('Error loading categories.');
            }
        });
    }

    // Evento para editar categorías o subcategorías
    $(document).on('click', '.edit', function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const nombre = $btn.data('nombre');
        const data = { id: id, nombre: nombre };

        data.id_categoria = $btn.data('subcategoria');

        if (!data.id_categoria) {
            editarCategoria(data);
        } else {
            editarSubcategoria(data);
        }
    });

    // Evento para eliminar categorías o subcategorías
    $(document).on('click', '.remove', async function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const id_categoria = $btn.data('subcategoria');

        if (!id_categoria) {
            const result = await confirm('Confirmación', '¿Está seguro de eliminar esta categoría? Esto también eliminará las subcategorías asociadas.');
            if (result.isConfirmed) {
                eliminarCategoria(id);
            }
        } else {
            const result = await confirm('Confirmación', '¿Está seguro de eliminar esta subcategoría?');
            if (result.isConfirmed) {
                eliminarSubcategoria(id);
            }
        }
    });

    // Función para manejar la tabla de acciones
    function operateFormatter(value, row, index) {
        return Handlebars.compile(tplAccionesTabla)(row);
    }

    // Funciones para editar y eliminar categorías
    function editarCategoria(categoria) {
        $('#formCrearCategoria input[name="id"]').val(categoria.id);
        $('#formCrearCategoria input[name="nombre"]').val(categoria.nombre);
        $modalCrearCategoria.modal('show');
    }

    function eliminarCategoria(id) {
        ajaxCall({
            url: `${Server}categorias/eliminarCategoria/${id}`,
            method: 'POST',
            success: function () {
                $tablaCategorias.bootstrapTable('refresh');
                showToast('¡Categoría eliminada correctamente!', 'success');
            },
            error: function (xhr) {
                handleError(xhr, 'Error al eliminar la categoría.');
            }
        });
    }

    // Funciones para editar y eliminar subcategorías
    function editarSubcategoria(subcategoria) {
        $('#formCrearSubcategoria input[name="id"]').val(subcategoria.id);
        $('#formCrearSubcategoria input[name="nombre"]').val(subcategoria.nombre);
        $('#id_categoria').data('selected', subcategoria.id_categoria);
        $modalCrearSubcategoria.modal('show');
    }

    function eliminarSubcategoria(id) {
        ajaxCall({
            url: `${Server}categorias/eliminarSubcategoria/${id}`,
            method: 'POST',
            success: function () {
                $tablaCategorias.bootstrapTable('refresh');
                showToast('¡Subcategoría eliminada correctamente!', 'success');
            },
            error: function (xhr) {
                handleError(xhr, 'Error al eliminar la subcategoría.');
            }
        });
    }

    // Reseteo de formularios al cerrar los modales
    $modalCrearCategoria.on('hidden.bs.modal', function () {
        const $form = $('#formCrearCategoria');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });

    $modalCrearSubcategoria.on('hidden.bs.modal', function () {
        const $form = $('#formCrearSubcategoria');
        $form[0].reset();
        $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $form.validate().resetForm();
    });
});
