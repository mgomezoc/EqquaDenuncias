<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Configuración inicial de rutas
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override('App\Controllers\Errors::show404');
$routes->setAutoRoute(false);


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
});

// Cargar rutas adicionales basadas en el entorno
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
