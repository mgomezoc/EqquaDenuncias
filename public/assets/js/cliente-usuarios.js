$(function () {
    const $tablaUsuarios = $('#tablaUsuarios').bootstrapTable({
        url: `${Server}cliente/usuarios/listar`,
        columns: [
            {
                field: 'id',
                title: 'ID'
            },
            {
                field: 'nombre_usuario',
                title: 'Nombre'
            },
            {
                field: 'correo_electronico',
                title: 'Correo'
            },
            {
                field: 'ultima_conexion',
                title: 'Última Conexión'
            },
            {
                field: 'rol_nombre',
                title: 'Rol'
            },
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
            const renderData = Handlebars.compile($('#tplDetalleTabla').html())(row);
            $detail.html(renderData);

            $detail.find('.formEditarUsuario').validate({
                rules: {
                    nombre_usuario: {
                        required: true,
                        minlength: 3,
                        remote: {
                            url: `${Server}cliente/usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                nombre_usuario: function () {
                                    return $detail.find('[name="nombre_usuario"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    },
                    correo_electronico: {
                        required: true,
                        email: true,
                        remote: {
                            url: `${Server}cliente/usuarios/validarUnico`,
                            type: 'post',
                            data: {
                                correo_electronico: function () {
                                    return $detail.find('[name="correo_electronico"]').val();
                                },
                                id: function () {
                                    return $detail.find('[name="id"]').val();
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    $('#formCrearUsuario').validate({
        rules: {
            nombre_usuario: {
                required: true,
                minlength: 3,
                remote: {
                    url: `${Server}cliente/usuarios/validarUnico`,
                    type: 'post',
                    data: {
                        nombre_usuario: function () {
                            return $('#nombre_usuario').val();
                        }
                    }
                }
            },
            correo_electronico: {
                required: true,
                email: true,
                remote: {
                    url: `${Server}cliente/usuarios/validarUnico`,
                    type: 'post',
                    data: {
                        correo_electronico: function () {
                            return $('#correo_electronico').val();
                        }
                    }
                }
            }
        },
        submitHandler: function (form) {
            const formData = $(form).serialize();
            $.post(`${Server}cliente/usuarios/guardar`, formData)
                .done(function () {
                    $tablaUsuarios.bootstrapTable('refresh');
                    $('#modalCrearUsuario').modal('hide');
                })
                .fail(function () {
                    alert('Error al guardar el usuario.');
                });
        }
    });

    window.operateEvents = {
        'click .remove': function (e, value, row, index) {
            if (confirm('¿Estás seguro de eliminar este usuario?')) {
                $.post(`${Server}cliente/usuarios/eliminar/${row.id}`)
                    .done(function () {
                        $tablaUsuarios.bootstrapTable('refresh');
                    })
                    .fail(function () {
                        alert('Error al eliminar el usuario.');
                    });
            }
        }
    };

    function operateFormatter() {
        return `<button class="btn btn-sm btn-danger remove">
                    <i class="fa fa-trash"></i>
                </button>`;
    }
});
