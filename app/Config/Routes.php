<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/login', 'Auth::login');
$routes->get('logout', 'Auth::logout');
$routes->post('/auth/loginSubmit', 'Auth::loginSubmit');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/registerSubmit', 'Auth::registerSubmit');
$routes->get('/logout', 'Auth::logout');
$routes->get('/dashboard', 'Dashboard::index', ['filter' => 'authFilter']);
$routes->get('/admin', 'Admin::index', ['filter' => 'authFilter:1']);
$routes->get('/noautorizado', 'Error::noautorizado');

$routes->get('cliente/(:segment)', 'Publico::verCliente/$1');

// USUARIOS
$routes->group('usuarios', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'UsuariosController::index');
    $routes->get('listar', 'UsuariosController::listar');
    $routes->post('guardar', 'UsuariosController::guardar');
    $routes->get('obtener/(:num)', 'UsuariosController::obtener/$1');
    $routes->post('eliminar/(:num)', 'UsuariosController::eliminar/$1');
    $routes->post('validarUnico', 'UsuariosController::validarUnico');
});

// CLIENTES
$routes->group('clientes', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'ClientesController::index');
    $routes->get('listar', 'ClientesController::listar');
    $routes->post('guardar', 'ClientesController::guardar');
    $routes->get('obtener/(:num)', 'ClientesController::obtener/$1');
    $routes->post('eliminar/(:num)', 'ClientesController::eliminar/$1');
    $routes->post('validarUnico', 'ClientesController::validarUnico');
    $routes->post('subirImagen', 'ClientesController::subirImagen'); // Ruta para subir imágenes
});

// DENUNCIAS
$routes->group('denuncias', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'DenunciasController::index');
    $routes->get('listar', 'DenunciasController::listar');
    $routes->get('detalle/(:num)', 'DenunciasController::detalle/$1');
    $routes->post('guardar', 'DenunciasController::guardar');
    $routes->post('eliminar/(:num)', 'DenunciasController::eliminar/$1');
    $routes->post('cambiarEstado', 'DenunciasController::cambiarEstado');
    $routes->post('subirAnexo', 'DenunciasController::subirAnexo'); // Nueva ruta para subir archivos adjuntos en denuncias
    $routes->get('sucursales/obtenerSucursalesPorCliente/(:num)', 'DenunciasController::obtenerSucursalesPorCliente/$1');
});

// SUCURSALES
$routes->group('sucursales', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'SucursalesController::index');
    $routes->get('listar', 'SucursalesController::listar');
    $routes->post('guardar', 'SucursalesController::guardar');
    $routes->get('obtener/(:num)', 'SucursalesController::obtener/$1');
    $routes->post('eliminar/(:num)', 'SucursalesController::eliminar/$1');
});

// CATEGORÍAS Y SUBCATEGORÍAS
$routes->group('categorias', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'CategoriasController::index');
    $routes->get('listarCategorias', 'CategoriasController::listarCategorias');
    $routes->get('listarSubcategorias', 'CategoriasController::listarSubcategorias');
    $routes->get('listarCategoriasYSubcategorias', 'CategoriasController::listarCategoriasYSubcategorias'); // Nueva ruta para obtener categorías y subcategorías en una estructura jerárquica
    $routes->post('guardarCategoria', 'CategoriasController::guardarCategoria');
    $routes->post('guardarSubcategoria', 'CategoriasController::guardarSubcategoria');
    $routes->post('eliminarCategoria/(:num)', 'CategoriasController::eliminarCategoria/$1');
    $routes->post('eliminarSubcategoria/(:num)', 'CategoriasController::eliminarSubcategoria/$1');
});

// DEPARTAMENTOS
$routes->group('departamentos', ['namespace' => 'App\Controllers', 'filter' => 'authFilter'], function ($routes) {
    $routes->get('/', 'DepartamentosController::index');
    $routes->get('listar', 'DepartamentosController::listarDepartamentos');
    $routes->post('guardar', 'DepartamentosController::guardarDepartamento');
    $routes->post('eliminar/(:num)', 'DepartamentosController::eliminarDepartamento/$1');
    $routes->get('obtener/(:num)', 'DepartamentosController::obtener/$1');
    $routes->get('listarClientes', 'DepartamentosController::listarClientes');
    $routes->get('listarSucursales/(:num)', 'DepartamentosController::listarSucursales/$1');
    $routes->get('listarDepartamentosPorSucursal/(:num)', 'DepartamentosController::listarDepartamentosPorSucursal/$1');
});

// Cargar rutas adicionales basadas en el entorno
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
