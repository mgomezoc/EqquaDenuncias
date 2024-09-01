<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index', ['filter' => 'authFilter']);

// Rutas para la autenticación
$routes->get('/login', 'Auth::login');
$routes->get('logout', 'Auth::logout');
$routes->post('/auth/loginSubmit', 'Auth::loginSubmit');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/registerSubmit', 'Auth::registerSubmit');
$routes->get('/logout', 'Auth::logout');
$routes->get('/noautorizado', 'Error::noautorizado');

// Ruta del dashboard, accesible para todos los roles autenticados
$routes->get('/dashboard', 'Dashboard::index', ['filter' => 'authFilter']);

// Grupo de rutas accesibles solo por ADMIN
$routes->group('', ['filter' => 'authFilter:ADMIN,CLIENTE'], function ($routes) {
    $routes->get('/admin', 'Admin::index');

    // Usuarios
    $routes->group('usuarios', function ($routes) {
        $routes->get('/', 'UsuariosController::index');
        $routes->get('listar', 'UsuariosController::listar');
        $routes->post('guardar', 'UsuariosController::guardar');
        $routes->get('obtener/(:num)', 'UsuariosController::obtener/$1');
        $routes->post('eliminar/(:num)', 'UsuariosController::eliminar/$1');
        $routes->post('validarUnico', 'UsuariosController::validarUnico');
    });

    // Clientes
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index');
        $routes->get('listar', 'ClientesController::listar');
        $routes->post('guardar', 'ClientesController::guardar');
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
        $routes->post('validarUnico', 'ClientesController::validarUnico');
        $routes->post('subirImagen', 'ClientesController::subirImagen');
    });

    // Categorías y Subcategorías
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

// Grupo de rutas accesibles por AGENTE y SUPERVISOR_CALIDAD (sección de denuncias)
$routes->group('denuncias', ['filter' => 'authFilter:ADMIN,AGENTE,SUPERVISOR_CALIDAD'], function ($routes) {
    $routes->get('/', 'DenunciasController::index');
    $routes->get('listar', 'DenunciasController::listar');
    $routes->get('detalle/(:num)', 'DenunciasController::detalle/$1');
    $routes->post('guardar', 'DenunciasController::guardar');
    $routes->post('eliminar/(:num)', 'DenunciasController::eliminar/$1');
    $routes->post('cambiarEstado', 'DenunciasController::cambiarEstado');
    $routes->post('subirAnexo', 'DenunciasController::subirAnexo');
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1');
    $routes->get('obtenerEstados', 'DenunciasController::obtenerEstados');
    $routes->get('obtenerAnexos/(:num)', 'DenunciasController::obtenerAnexos/$1');

    // Rutas exclusivas para SUPERVISOR_CALIDAD
    $routes->group('supervision', ['filter' => 'authFilter:SUPERVISOR_CALIDAD'], function ($routes) {
        $routes->get('gestion', 'DenunciasController::gestionSupervisor');
    });
});

// Grupo de rutas accesibles solo por CLIENTE (denuncias, clientes, sucursales, departamentos)
$routes->group('', ['filter' => 'authFilter:CLIENTE'], function ($routes) {

    // Clientes (acceso solo para el CLIENTE)
    $routes->group('clientes', function ($routes) {
        $routes->get('/', 'ClientesController::index');
        $routes->get('listar', 'ClientesController::listar');
        $routes->post('guardar', 'ClientesController::guardar');
        $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
        $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
    });

    // Sucursales
    $routes->group('sucursales', function ($routes) {
        $routes->get('/', 'SucursalesController::index');
        $routes->get('listar', 'SucursalesController::listar');
        $routes->post('guardar', 'SucursalesController::guardar');
        $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1'); // Asegúrate de que los clientes puedan acceder
        $routes->get('listarSucursales/(:num)', 'SucursalesController::listarSucursales/$1');
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

    // Denuncias del cliente
    $routes->group('denuncias', function ($routes) {
        $routes->get('mis-denuncias', 'DenunciasController::misDenuncias');
    });
});

// Cargar rutas adicionales basadas en el entorno
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
