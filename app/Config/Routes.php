<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Públicas
$routes->get('send-email', 'MailController::sendEmail');

$routes->group('', function ($routes) {
    $routes->get('c/(:segment)', 'Publico::verCliente/$1');
    $routes->get('c/(:segment)/formulario-denuncia', 'Publico::formularioDenuncia/$1');
    $routes->get('c/(:segment)/seguimiento-denuncia', 'Publico::seguimientoDenuncia/$1');

    $routes->get('categorias/listarSubcategorias', 'CategoriasController::listarSubcategorias');

    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'SucursalesController::obtenerSucursalesPorCliente/$1');
    $routes->get('departamentos/listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1');

    $routes->post('denuncias/guardar-public', 'Publico::guardarDenunciaPublica');
    $routes->post('denuncias/subir-anexo-public', 'Publico::subirAnexoPublico');

    $routes->get('denuncias/consultar', 'Publico::consultarDenuncia');
});

// Autenticación
$routes->get('/', 'Home::index', ['filter' => 'authFilter']);
$routes->get('/login', 'Auth::login');
$routes->post('/auth/loginSubmit', 'Auth::loginSubmit');
$routes->get('/logout', 'Auth::logout');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/registerSubmit', 'Auth::registerSubmit');
$routes->get('/noautorizado', 'Error::noautorizado');

$routes->get('/forgot-password', 'Auth::forgotPassword');
$routes->post('/forgot-password-submit', 'Auth::forgotPasswordSubmit');
$routes->get('/reset-password/(:any)', 'Auth::resetPassword/$1');
$routes->post('/reset-password-submit', 'Auth::resetPasswordSubmit');

// Dashboard
$routes->get('/dashboard', 'DashboardController::index', ['filter' => 'authFilter']);
$routes->post('/dashboard/filtrar', 'DashboardController::filtrar');
$routes->post('/dashboard/getDenunciasPorAnio', 'DashboardController::getDenunciasPorAnio');
$routes->post('dashboard/getSubcategoriasPorCategoria', 'DashboardController::getSubcategoriasPorCategoria');
$routes->post('dashboard/getCategoriasResumen', 'DashboardController::getCategoriasResumen');
$routes->post('dashboard/getResumenCategoriasConFiltros', 'DashboardController::getResumenCategoriasConFiltros');

// Grupo general con auth y roles
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE,AGENTE,SUPERVISOR_CALIDAD'], function ($routes) {
    // Configuración
    $routes->get('configuracion', 'ConfiguracionController::index', ['filter' => 'authFilter']);
    $routes->post('configuracion/actualizar', 'ConfiguracionController::actualizar', ['filter' => 'authFilter']);

    // Admin
    $routes->get('/admin', 'Admin::index');

    // Reportes (existente)
    $routes->group('reportes', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
        $routes->get('/', 'ReportesController::index');
        $routes->post('listar', 'ReportesController::listar');
        $routes->post('exportarCSV', 'ReportesController::exportarCSV');
        $routes->post('eliminarDenuncia/(:num)', 'ReportesController::eliminarDenuncia/$1');

        $routes->get('cliente', 'ReportesController::cliente');
        $routes->post('listarParaCliente', 'ReportesController::listarParaCliente');
    });

    // Reportes IA (nuevo)
    $routes->group('reportes-ia', ['filter' => 'authFilter:ADMIN'], function ($routes) {
        $routes->get('/', 'ReportesIAController::index');                       // Listado
        $routes->get('listar', 'ReportesIAController::listar');
        $routes->get('generar', 'ReportesIAController::generar');               // Form generar
        $routes->post('procesar', 'ReportesIAController::procesarGeneracion');  // AJAX generar
        $routes->get('ver/(:num)', 'ReportesIAController::ver/$1');             // Detalle
        $routes->post('cambiar-estado', 'ReportesIAController::cambiarEstado'); // Cambiar estado
        $routes->get('descargar/(:num)', 'ReportesIAController::descargarPDF/$1'); // PDF
        $routes->post('eliminar', 'ReportesIAController::eliminar');            // Eliminar
        $routes->get('estadisticas', 'ReportesIAController::estadisticas');     // Estadísticas
        $routes->get('periodos', 'ReportesIAController::getPeriodosDisponibles'); // Periodos disponibles
    });

    // Sucursales helper
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1');

    // Usuarios
    $routes->group('usuarios', function ($routes) {
        $routes->get('/', 'UsuariosController::index');
        $routes->get('listar', 'UsuariosController::listar');
        $routes->post('guardar', 'UsuariosController::guardar');
        $routes->get('obtener/(:num)', 'UsuariosController::obtener/$1');
        $routes->post('eliminar/(:num)', 'UsuariosController::eliminar/$1');
        $routes->post('validarUnico', 'UsuariosController::validarUnico');
        $routes->get('(:num)/tipos-denunciante', 'UsuariosController::tiposDenunciante/$1');
    });

    // Clientes
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index');
        $routes->get('listar', 'ClientesController::listar');
        $routes->post('guardar', 'ClientesController::guardar');
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
        $routes->post('subirImagen', 'ClientesController::subirImagen');
        $routes->post('validarUnico', 'ClientesController::validarUnico');
    });

    // Categorías
    $routes->group('categorias', function ($routes) {
        $routes->get('/', 'CategoriasController::index');
        $routes->get('listarCategorias', 'CategoriasController::listarCategorias');
        $routes->get('listarSubcategorias', 'CategoriasController::listarSubcategorias');
        $routes->get('listarCategoriasYSubcategorias', 'CategoriasController::listarCategoriasYSubcategorias');
        $routes->post('guardarCategoria', 'CategoriasController::guardarCategoria');
        $routes->post('guardarSubcategoria', 'CategoriasController::guardarSubcategoria');
        $routes->post('eliminarCategoria/(:num)', 'CategoriasController::eliminarCategoria/$1');
        $routes->post('eliminarSubcategoria/(:num)', 'CategoriasController::eliminarSubcategoria/$1');
    });

    // Sucursales
    $routes->group('sucursales', function ($routes) {
        $routes->get('/', 'SucursalesController::index');
        $routes->get('listar', 'SucursalesController::listar');
        $routes->post('guardar', 'SucursalesController::guardar');
        $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'SucursalesController::eliminar/$1');
    });

    // Departamentos
    $routes->group('departamentos', function ($routes) {
        $routes->get('/', 'DepartamentosController::index');
        $routes->get('listar', 'DepartamentosController::listarDepartamentos');
        $routes->post('guardar', 'DepartamentosController::guardarDepartamento');
        $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1');
        $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1');
        $routes->get('listarClientes', 'DepartamentosController::listarClientes');
        $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1');
        $routes->get('listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1');
    });
});

// Denuncias (CRUD y vistas)
$routes->group('denuncias', ['filter' => 'authFilter:ADMIN,AGENTE,SUPERVISOR_CALIDAD,CLIENTE'], function ($routes) {
    $routes->get('/', 'DenunciasController::index');
    $routes->get('listar', 'DenunciasController::listar');
    $routes->get('detalle/(:num)', 'DenunciasController::detalle/$1');
    $routes->post('guardar', 'DenunciasController::guardar');
    $routes->post('eliminar/(:num)', 'DenunciasController::eliminar/$1');
    $routes->post('cambiarEstado', 'DenunciasController::cambiarEstado');
    $routes->post('subirAnexo', 'DenunciasController::subirAnexo');
    $routes->post('actualizarAnexos', 'DenunciasController::actualizarAnexos');
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1');
    $routes->get('obtenerEstados', 'DenunciasController::obtenerEstados');
    $routes->get('obtenerAnexos/(:num)', 'DenunciasController::obtenerAnexos/$1');

    // Usuarios excluidos al liberar denuncia
    $routes->get('usuarios-por-cliente/(:num)', 'DenunciasController::obtenerUsuariosPorCliente/$1');
    $routes->get('usuarios-excluidos/(:num)', 'DenunciasController::obtenerUsuariosExcluidosDenuncia/$1');

    $routes->get('mis-denuncias-agente', 'DenunciasController::misDenunciasAgente');
    $routes->get('listar-denuncias-agente', 'DenunciasController::listarDenunciasAgente');
    $routes->get('listar-denuncias-calidad', 'DenunciasController::listarDenunciasCalidad', ['filter' => 'authFilter:SUPERVISOR_CALIDAD']);
    $routes->get('listarDenunciasCliente', 'DenunciasController::listarDenunciasCliente');

    $routes->group('anexos', function ($routes) {
        $routes->post('eliminar/(:num)', 'DenunciasController::eliminarAnexo/$1');
    });

    $routes->group('supervision', ['filter' => 'authFilter:SUPERVISOR_CALIDAD'], function ($routes) {
        $routes->get('gestion', 'DenunciasController::gestionSupervisor');
    });
});

// Cliente autenticado
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
    $routes->group('denuncias', function ($routes) {
        $routes->get('mis-denuncias-cliente', 'DenunciasController::misDenunciasCliente');
    });

    $routes->group('cliente', ['filter' => 'authFilter:CLIENTE'], function ($routes) {
        $routes->get('dashboard', 'DashboardClienteController::index');
        $routes->post('dashboard/filtrar', 'DashboardClienteController::filtrar');

        $routes->get('perfil', 'PerfilClienteController::perfil');
        $routes->post('perfil/actualizar', 'PerfilClienteController::actualizarPerfil');

        $routes->group('usuarios', function ($routes) {
            $routes->get('/', 'UsuariosClienteController::index');
            $routes->get('listar', 'UsuariosClienteController::listar');
            $routes->post('guardar', 'UsuariosClienteController::guardar');
            $routes->post('eliminar/(:num)', 'UsuariosClienteController::eliminar/$1');
            $routes->post('validarUnico', 'UsuariosClienteController::validarUnico');
            $routes->get('(:num)/tipos-denunciante', 'UsuariosController::tiposDenunciante/$1');
        });

        $routes->get('usuarios', 'UsuariosClienteController::index');
        $routes->post('usuarios/guardar', 'UsuariosClienteController::guardar');
        $routes->post('usuarios/eliminar/(:num)', 'UsuariosClienteController::eliminar/$1');
    });

    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index');
        $routes->get('listar', 'ClientesController::listar');
        $routes->post('guardar', 'ClientesController::guardar');
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
    });

    $routes->group('sucursales', function ($routes) {
        $routes->get('/', 'SucursalesController::index');
        $routes->get('listar', 'SucursalesController::listar');
        $routes->post('guardar', 'SucursalesController::guardar');
        $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1');
        $routes->get('listarSucursales/(:num)', 'SucursalesController::listarSucursales/$1');
        $routes->post('eliminar/(:num)', 'SucursalesController::eliminar/$1');

        $routes->post('subir-imagen', 'SucursalesController::subirImagen');
        $routes->get('listar-imagenes/(:num)', 'SucursalesController::listarImagenes/$1');
        $routes->post('eliminar-imagen', 'SucursalesController::eliminarImagen');
    });

    $routes->group('departamentos', function ($routes) {
        $routes->get('/', 'DepartamentosController::index');
        $routes->get('listar', 'DepartamentosController::listarDepartamentos');
        $routes->post('guardar', 'DepartamentosController::guardarDepartamento');
        $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1');
        $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1');
        $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1');
    });
});

// Comentarios
$routes->group('comentarios', function ($routes) {
    $routes->get('listar/(:num)', 'ComentariosController::listar/$1');
    $routes->get('listar-cliente/(:num)', 'ComentariosController::listarCliente/$1');
    $routes->post('guardar', 'ComentariosController::guardar');
    $routes->post('eliminar/(:num)', 'ComentariosController::eliminar/$1');
});

// IA en denuncias
$routes->group('api/denuncias', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->post('(:num)/sugerencia-ia', 'DenunciasController::generarSugerenciaIA/$1');
    $routes->get('(:num)/sugerencia-ia', 'DenunciasController::obtenerSugerenciaIA/$1');
    $routes->put('(:num)/sugerencia-ia/regenerar', 'DenunciasController::regenerarSugerenciaIA/$1');
    $routes->post('sugerencia-ia/evaluar', 'DenunciasController::evaluarSugerenciaIA');
    $routes->post('sugerencia-ia/guardar-edicion', 'DenunciasController::guardarEdicionSugerencia');
    $routes->post('sugerencia-ia/publicar', 'DenunciasController::publicarSugerencia');
    $routes->get('estadisticas-ia', 'DenunciasController::estadisticasIA');
});

// Admin IA
$routes->group('admin/ia', ['namespace' => 'App\Controllers', 'filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'IAAdminController::dashboard');
    $routes->get('configuracion', 'IAAdminController::configuracion');
    $routes->post('configuracion', 'IAAdminController::guardarConfiguracion');
    $routes->get('logs', 'IAAdminController::logs');
    $routes->get('costos', 'IAAdminController::reporteCostos');
});

// Rutas por entorno
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
