<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/**
 * Rutas públicas para cualquier usuario
 * Estas rutas no requieren autenticación y son accesibles por cualquier persona
 */
$routes->get('send-email', 'MailController::sendEmail');

$routes->group('', function ($routes) {
    // Rutas para la página pública de denuncias
    $routes->get('c/(:segment)', 'Publico::verCliente/$1');
    $routes->get('c/(:segment)/formulario-denuncia', 'Publico::formularioDenuncia/$1');
    $routes->get('c/(:segment)/seguimiento-denuncia', 'Publico::seguimientoDenuncia/$1');

    // Rutas públicas para obtener categorías y subcategorías
    $routes->get('categorias/listarSubcategorias', 'CategoriasController::listarSubcategorias');

    // Rutas públicas para obtener sucursales y departamentos
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'SucursalesController::obtenerSucursalesPorCliente/$1');
    $routes->get('departamentos/listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1');

    // Ruta pública para guardar denuncias
    $routes->post('denuncias/guardar-public', 'Publico::guardarDenunciaPublica');

    // Ruta pública para subir archivos adjuntos usando Dropzone
    $routes->post('denuncias/subir-anexo-public', 'Publico::subirAnexoPublico');

    // Consulta de seguimiento de denuncias
    $routes->get('denuncias/consultar', 'Publico::consultarDenuncia');
});


/**
 * Rutas para la autenticación y gestión de sesión
 */
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


/**
 * Rutas para el dashboard
 */
$routes->get('/dashboard', 'DashboardController::index', ['filter' => 'authFilter']);
$routes->post('/dashboard/filtrar', 'DashboardController::filtrar');
$routes->post('/dashboard/getDenunciasPorAnio', 'DashboardController::getDenunciasPorAnio');
$routes->post('dashboard/getSubcategoriasPorCategoria', 'DashboardController::getSubcategoriasPorCategoria');
$routes->post('dashboard/getCategoriasResumen', 'DashboardController::getCategoriasResumen');
$routes->post('dashboard/getResumenCategoriasConFiltros', 'DashboardController::getResumenCategoriasConFiltros');


/**
 * Grupo de rutas accesibles solo por ADMIN y otros roles específicos
 */
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE,AGENTE,SUPERVISOR_CALIDAD'], function ($routes) {
    // Configuración
    $routes->get('configuracion', 'ConfiguracionController::index', ['filter' => 'authFilter']);
    $routes->post('configuracion/actualizar', 'ConfiguracionController::actualizar', ['filter' => 'authFilter']);

    // Admin
    $routes->get('/admin', 'Admin::index');

    // Reportes
    $routes->group('reportes', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
        $routes->get('/', 'ReportesController::index');
        $routes->post('listar', 'ReportesController::listar');
        $routes->post('exportarCSV', 'ReportesController::exportarCSV');
        $routes->post('eliminarDenuncia/(:num)', 'ReportesController::eliminarDenuncia/$1');

        $routes->get('cliente', 'ReportesController::cliente');
        $routes->post('listarParaCliente', 'ReportesController::listarParaCliente');
    });

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

/**
 * Rutas relacionadas con la gestión de denuncias
 */
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

    // Denuncias del agente
    $routes->get('mis-denuncias-agente', 'DenunciasController::misDenunciasAgente');
    $routes->get('listar-denuncias-agente', 'DenunciasController::listarDenunciasAgente');
    $routes->get('listar-denuncias-calidad', 'DenunciasController::listarDenunciasCalidad', ['filter' => 'authFilter:SUPERVISOR_CALIDAD']);
    $routes->get('listarDenunciasCliente', 'DenunciasController::listarDenunciasCliente');

    // Gestión de anexos
    $routes->group('anexos', function ($routes) {
        $routes->post('eliminar/(:num)', 'DenunciasController::eliminarAnexo/$1');
    });

    // Supervisor de calidad
    $routes->group('supervision', ['filter' => 'authFilter:SUPERVISOR_CALIDAD'], function ($routes) {
        $routes->get('gestion', 'DenunciasController::gestionSupervisor');
    });
});

/**
 * Grupo de rutas exclusivas para el CLIENTE autenticado
 */
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
    // Denuncias del cliente
    $routes->group('denuncias', function ($routes) {
        $routes->get('mis-denuncias-cliente', 'DenunciasController::misDenunciasCliente');
    });

    $routes->group('cliente', ['filter' => 'authFilter:CLIENTE'], function ($routes) {
        // Dashboard
        $routes->get('dashboard', 'DashboardClienteController::index');
        $routes->post('dashboard/filtrar', 'DashboardClienteController::filtrar');

        // Perfil
        $routes->get('perfil', 'PerfilClienteController::perfil');
        $routes->post('perfil/actualizar', 'PerfilClienteController::actualizarPerfil');

        // Usuarios del cliente
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

    // Clientes
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index');
        $routes->get('listar', 'ClientesController::listar');
        $routes->post('guardar', 'ClientesController::guardar');
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
    });

    // Sucursales del cliente
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

    // Departamentos del cliente
    $routes->group('departamentos', function ($routes) {
        $routes->get('/', 'DepartamentosController::index');
        $routes->get('listar', 'DepartamentosController::listarDepartamentos');
        $routes->post('guardar', 'DepartamentosController::guardarDepartamento');
        $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1');
        $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1');
        $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1');
    });
});

/**
 * Gestión de comentarios
 */
$routes->group('comentarios', function ($routes) {
    $routes->get('listar/(:num)', 'ComentariosController::listar/$1');
    $routes->get('listar-cliente/(:num)', 'ComentariosController::listarCliente/$1');
    $routes->post('guardar', 'ComentariosController::guardar');
    $routes->post('eliminar/(:num)', 'ComentariosController::eliminar/$1');
});

// Rutas para funcionalidad de IA en denuncias
$routes->group('api/denuncias', ['namespace' => 'App\Controllers'], function ($routes) {

    // Generar sugerencia de IA para una denuncia
    $routes->post('(:num)/sugerencia-ia', 'DenunciasController::generarSugerenciaIA/$1');

    // Obtener sugerencia de IA existente
    $routes->get('(:num)/sugerencia-ia', 'DenunciasController::obtenerSugerenciaIA/$1');

    // Regenerar sugerencia de IA
    $routes->put('(:num)/sugerencia-ia/regenerar', 'DenunciasController::regenerarSugerenciaIA/$1');

    // Evaluar sugerencia de IA
    $routes->post('sugerencia-ia/evaluar', 'DenunciasController::evaluarSugerenciaIA');

    // Guardar edición del agente (texto en sugerencia_agente)
    $routes->post('sugerencia-ia/guardar-edicion', 'DenunciasController::guardarEdicionSugerencia');

    // Publicar / retirar la sugerencia para el cliente final
    $routes->post('sugerencia-ia/publicar', 'DenunciasController::publicarSugerencia');

    // Estadísticas de uso de IA
    $routes->get('estadisticas-ia', 'DenunciasController::estadisticasIA');
});

// Rutas adicionales para administración de IA (solo para administradores)
$routes->group('admin/ia', ['namespace' => 'App\Controllers', 'filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'IAAdminController::dashboard');
    $routes->get('configuracion', 'IAAdminController::configuracion');
    $routes->post('configuracion', 'IAAdminController::guardarConfiguracion');
    $routes->get('logs', 'IAAdminController::logs');
    $routes->get('costos', 'IAAdminController::reporteCostos');
});


/**
 * Cargar rutas adicionales basadas en el entorno
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
