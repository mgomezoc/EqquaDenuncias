<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'Bienvenido ' . session()->get('nombre_usuario'),
            'controlador' => 'Inicio',
            'vista' => 'Home'
        ];

        return view('home', $data);
    }
}
