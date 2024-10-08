<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use CodeIgniter\Controller;

class PerfilClienteController extends Controller
{
    public function perfil()
    {
        $clienteId = session()->get('id_cliente');
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->find($clienteId);

        if (!$cliente) {
            return redirect()->to('/noautorizado');
        }

        $data = [
            'title' => 'Perfil del Cliente',
            'cliente' => $cliente,
        ];

        return view('cliente/perfil', $data);
    }

    public function actualizarPerfil()
    {
        $clienteId = session()->get('id_cliente');
        $clienteModel = new ClienteModel();
        $data = $this->request->getPost();

        if ($clienteModel->update($clienteId, $data)) {
            return $this->response->setJSON(['message' => 'Perfil actualizado correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al actualizar el perfil']);
    }
}
