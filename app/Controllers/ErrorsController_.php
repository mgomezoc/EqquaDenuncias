<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class ErrorsController extends Controller
{
    public function show404()
    {
        $data = [
            'title' => 'Página no encontrada',
        ];
        return view('errors/custom_404', $data);
    }
}
