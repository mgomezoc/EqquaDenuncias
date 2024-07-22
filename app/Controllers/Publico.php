<?php

namespace App\Controllers;

use App\Models\ClienteModel;

class Publico extends BaseController
{
    public function verCliente($slug)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'title' => 'Inicio - Sistema de Denuncias',
            'cliente' => $cliente
        ];

        return view('publico/ver_cliente', $data);
    }
}
