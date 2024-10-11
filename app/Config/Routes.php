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

$routes->group('public', function ($routes) {
    // Rutas para la página pública de denuncias
    $routes->get('cliente/(:segment)', 'Publico::verCliente/$1');
    $routes->get('cliente/(:segment)/formulario-denuncia', 'Publico::formularioDenuncia/$1');
    $routes->get('cliente/(:segment)/seguimiento-denuncia', 'Publico::seguimientoDenuncia/$1');

    // Rutas públicas para obtener categorías y subcategorías
    $routes->get('categorias/listarSubcategorias', 'CategoriasController::listarSubcategorias'); // Obtener subcategorías según la categoría

    // Rutas públicas para obtener sucursales y departamentos
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'SucursalesController::obtenerSucursalesPorCliente/$1'); // Obtener sucursales de un cliente
    $routes->get('departamentos/listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1'); // Listar departamentos según sucursal

    // Ruta pública para guardar denuncias
    $routes->post('denuncias/guardar-public', 'Publico::guardarDenunciaPublica'); // Guardar denuncia pública

    // Ruta pública para subir archivos adjuntos usando Dropzone
    $routes->post('denuncias/subir-anexo-public', 'Publico::subirAnexoPublico'); // Subir anexos públicos

    // Ruta para la consulta de seguimiento de denuncias
    $routes->get('denuncias/consultar', 'Publico::consultarDenuncia'); // Nueva ruta para buscar la denuncia por folio
});


/**
 * Rutas para la autenticación y gestión de sesión
 */
$routes->get('/', 'Home::index', ['filter' => 'authFilter']); // Ruta principal, autenticada
$routes->get('/login', 'Auth::login'); // Mostrar formulario de login
$routes->post('/auth/loginSubmit', 'Auth::loginSubmit'); // Procesar login
$routes->get('/logout', 'Auth::logout'); // Cerrar sesión
$routes->get('/register', 'Auth::register'); // Mostrar formulario de registro
$routes->post('/auth/registerSubmit', 'Auth::registerSubmit'); // Procesar registro
$routes->get('/noautorizado', 'Error::noautorizado'); // Página de error para accesos no autorizados

/**
 * Rutas para el dashboard
 * Accesible para todos los usuarios autenticados
 */
$routes->get('/dashboard', 'DashboardController::index', ['filter' => 'authFilter']); // Dashboard principal
$routes->post('/dashboard/filtrar', 'DashboardController::filtrar');
/**
 * Grupo de rutas accesibles solo por ADMIN y otros roles específicos
 * Usuarios autenticados con roles ADMIN, CLIENTE, AGENTE, SUPERVISOR_CALIDAD
 */
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE,AGENTE,SUPERVISOR_CALIDAD'], function ($routes) {

    // Ruta para la página de administración
    $routes->get('/admin', 'Admin::index');

    // Gestión de reportes de denuncias
    $routes->group('reportes', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
        $routes->get('/', 'ReportesController::index');
        $routes->post('listar', 'ReportesController::listar');
        $routes->post('exportarCSV', 'ReportesController::exportarCSV');

        $routes->get('cliente', 'ReportesController::cliente');
        $routes->post('listarParaCliente', 'ReportesController::listarParaCliente');
    });

    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1');


    // Gestión de usuarios
    $routes->group('usuarios', function ($routes) {
        $routes->get('/', 'UsuariosController::index'); // Listar usuarios
        $routes->get('listar', 'UsuariosController::listar'); // API para listar usuarios
        $routes->post('guardar', 'UsuariosController::guardar'); // Guardar/crear usuario
        $routes->get('obtener/(:num)', 'UsuariosController::obtener/$1'); // Obtener usuario por ID
        $routes->post('eliminar/(:num)', 'UsuariosController::eliminar/$1'); // Eliminar usuario por ID
        $routes->post('validarUnico', 'UsuariosController::validarUnico'); // Validar unicidad de usuario
    });

    // Gestión de clientes
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index'); // Listar clientes
        $routes->get('listar', 'ClientesController::listar'); // API para listar clientes
        $routes->post('guardar', 'ClientesController::guardar'); // Guardar/crear cliente
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1'); // Obtener cliente por ID
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1'); // Eliminar cliente por ID
        $routes->post('subirImagen', 'ClientesController::subirImagen'); // Subir imagen del cliente
    });

    // Gestión de categorías y subcategorías de denuncias
    $routes->group('categorias', function ($routes) {
        $routes->get('/', 'CategoriasController::index'); // Listar categorías
        $routes->get('listarCategorias', 'CategoriasController::listarCategorias'); // API para listar categorías
        $routes->get('listarSubcategorias', 'CategoriasController::listarSubcategorias'); // API para listar subcategorías
        $routes->get('listarCategoriasYSubcategorias', 'CategoriasController::listarCategoriasYSubcategorias'); // Listar todo
        $routes->post('guardarCategoria', 'CategoriasController::guardarCategoria'); // Guardar categoría
        $routes->post('guardarSubcategoria', 'CategoriasController::guardarSubcategoria'); // Guardar subcategoría
        $routes->post('eliminarCategoria/(:num)', 'CategoriasController::eliminarCategoria/$1'); // Eliminar categoría
        $routes->post('eliminarSubcategoria/(:num)', 'CategoriasController::eliminarSubcategoria/$1'); // Eliminar subcategoría
    });

    // Gestión de sucursales
    $routes->group('sucursales', function ($routes) {
        $routes->get('/', 'SucursalesController::index'); // Listar sucursales
        $routes->get('listar', 'SucursalesController::listar'); // API para listar sucursales
        $routes->post('guardar', 'SucursalesController::guardar'); // Guardar/crear sucursal
        $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1'); // Obtener sucursal por ID
        $routes->post('eliminar/(:num)', 'SucursalesController::eliminar/$1'); // Eliminar sucursal por ID
    });

    // Gestión de departamentos
    $routes->group('departamentos', function ($routes) {
        $routes->get('/', 'DepartamentosController::index'); // Listar departamentos
        $routes->get('listar', 'DepartamentosController::listarDepartamentos'); // API para listar departamentos
        $routes->post('guardar', 'DepartamentosController::guardarDepartamento'); // Guardar departamento
        $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1'); // Eliminar departamento
        $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1'); // Obtener departamento por ID
        $routes->get('listarClientes', 'DepartamentosController::listarClientes'); // Listar clientes asociados
        $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1'); // Listar sucursales por cliente
        $routes->get('listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1'); // Listar departamentos por sucursal
    });
});

/**
 * Rutas relacionadas con la gestión de denuncias
 * Accesibles por roles ADMIN, AGENTE, SUPERVISOR_CALIDAD y CLIENTE
 */
$routes->group('denuncias', ['filter' => 'authFilter:ADMIN,AGENTE,SUPERVISOR_CALIDAD,CLIENTE'], function ($routes) {
    $routes->get('/', 'DenunciasController::index'); // Página principal de denuncias
    $routes->get('listar', 'DenunciasController::listar'); // API para listar denuncias
    $routes->get('detalle/(:num)', 'DenunciasController::detalle/$1'); // Ver detalle de denuncia por ID
    $routes->post('guardar', 'DenunciasController::guardar'); // Guardar/crear denuncia
    $routes->post('eliminar/(:num)', 'DenunciasController::eliminar/$1'); // Eliminar denuncia por ID
    $routes->post('cambiarEstado', 'DenunciasController::cambiarEstado'); // Cambiar estado de denuncia
    $routes->post('subirAnexo', 'DenunciasController::subirAnexo'); // Subir archivos anexos a una denuncia
    $routes->post('actualizarAnexos', 'DenunciasController::actualizarAnexos'); // Actualizar anexos
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1'); // Obtener sucursales por cliente
    $routes->get('obtenerEstados', 'DenunciasController::obtenerEstados'); // Obtener estados de denuncia
    $routes->get('obtenerAnexos/(:num)', 'DenunciasController::obtenerAnexos/$1'); // Obtener anexos de una denuncia

    // Denuncias del agente
    $routes->get('mis-denuncias-agente', 'DenunciasController::misDenunciasAgente'); // Denuncias asignadas al agente
    $routes->get('listar-denuncias-agente', 'DenunciasController::listarDenunciasAgente'); // Listar denuncias por agente
    $routes->get('listar-denuncias-calidad', 'DenunciasController::listarDenunciasCalidad', ['filter' => 'authFilter:SUPERVISOR_CALIDAD']); // Listar denuncias para supervisión de calidad
    $routes->get('listarDenunciasCliente', 'DenunciasController::listarDenunciasCliente'); // Listar denuncias del cliente

    // Gestión de anexos
    $routes->group('anexos', function ($routes) {
        $routes->post('eliminar/(:num)', 'DenunciasController::eliminarAnexo/$1'); // Eliminar anexo
    });

    // Rutas exclusivas para la supervisión de calidad
    $routes->group('supervision', ['filter' => 'authFilter:SUPERVISOR_CALIDAD'], function ($routes) {
        $routes->get('gestion', 'DenunciasController::gestionSupervisor'); // Gestión de denuncias por el supervisor de calidad
    });
});

/**
 * Grupo de rutas exclusivas para el CLIENTE autenticado
 * Denuncias, clientes, sucursales y departamentos
 */
$routes->group('', ['filter' => 'authFilter:CLIENTE'], function ($routes) {
    // Denuncias del cliente
    $routes->group('denuncias', function ($routes) {
        $routes->get('mis-denuncias-cliente', 'DenunciasController::misDenunciasCliente'); // Ver denuncias del cliente autenticado
    });

    $routes->group('cliente', ['filter' => 'authFilter:CLIENTE'], function ($routes) {
        // Gestión del perfil del cliente
        $routes->get('perfil', 'PerfilClienteController::perfil');
        $routes->post('perfil/actualizar', 'PerfilClienteController::actualizarPerfil');

        // Gestión de usuarios del cliente
        $routes->group('usuarios', function ($routes) {
            $routes->get('/', 'UsuariosClienteController::index'); // Página principal de usuarios del cliente
            $routes->get('listar', 'UsuariosClienteController::listar'); // API para listar usuarios del cliente
            $routes->post('guardar', 'UsuariosClienteController::guardar'); // Guardar/crear usuario del cliente
            $routes->post('eliminar/(:num)', 'UsuariosClienteController::eliminar/$1'); // Eliminar usuario por ID
            $routes->post('validarUnico', 'UsuariosClienteController::validarUnico'); // Validar unicidad de usuario
        });

        // Gestión de usuarios del cliente
        $routes->get('usuarios', 'UsuariosClienteController::index');
        $routes->post('usuarios/guardar', 'UsuariosClienteController::guardar');
        $routes->post('usuarios/eliminar/(:num)', 'UsuariosClienteController::eliminar/$1');
    });

    // Gestión de clientes (solo accesible para el cliente autenticado)
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index'); // Listar clientes
        $routes->get('listar', 'ClientesController::listar'); // Listar clientes en API
        $routes->post('guardar', 'ClientesController::guardar'); // Guardar cliente
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1'); // Obtener cliente por ID
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1'); // Eliminar cliente por ID
    });

    // Gestión de sucursales del cliente
    $routes->group('sucursales', function ($routes) {
        $routes->get('/', 'SucursalesController::index'); // Listar sucursales
        $routes->get('listar', 'SucursalesController::listar'); // API para listar sucursales
        $routes->post('guardar', 'SucursalesController::guardar'); // Guardar sucursal
        $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1'); // Obtener sucursal por ID
        $routes->get('listarSucursales/(:num)', 'SucursalesController::listarSucursales/$1'); // Listar sucursales por cliente
        $routes->post('eliminar/(:num)', 'SucursalesController::eliminar/$1'); // Eliminar sucursal
    });

    // Gestión de departamentos del cliente
    $routes->group('departamentos', function ($routes) {
        $routes->get('/', 'DepartamentosController::index'); // Listar departamentos
        $routes->get('listar', 'DepartamentosController::listarDepartamentos'); // API para listar departamentos
        $routes->post('guardar', 'DepartamentosController::guardarDepartamento'); // Guardar departamento
        $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1'); // Eliminar departamento
        $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1'); // Obtener departamento por ID
        $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1'); // Listar sucursales por cliente
    });
});

/**
 * Gestión de comentarios
 * Las rutas aquí permiten listar y guardar comentarios en una denuncia
 */
$routes->group('comentarios', function ($routes) {
    $routes->get('listar/(:num)', 'ComentariosController::listar/$1');
    $routes->get('listar-cliente/(:num)', 'ComentariosController::listarCliente/$1');
    $routes->post('guardar', 'ComentariosController::guardar');
});

/**
 * Cargar rutas adicionales basadas en el entorno
 * Estas rutas dependen del ambiente de desarrollo o producción
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
