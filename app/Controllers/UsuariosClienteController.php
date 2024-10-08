<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class UsuariosClienteController extends Controller
{
    public function index()
    {
        $clienteId = session()->get('id_cliente');
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel->where('id_cliente', $clienteId)->findAll();

        $data = [
            'title' => 'Usuarios del Cliente',
            'usuarios' => $usuarios,
        ];

        return view('cliente/usuarios', $data);
    }

    public function guardar()
    {
        $usuarioModel = new UsuarioModel();
        $data = $this->request->getPost();
        $data['id_cliente'] = session()->get('id_cliente');

        if ($usuarioModel->save($data)) {
            return $this->response->setJSON(['message' => 'Usuario guardado correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al guardar el usuario']);
    }

    public function eliminar($id)
    {
        $usuarioModel = new UsuarioModel();
        if ($usuarioModel->delete($id)) {
            return $this->response->setJSON(['message' => 'Usuario eliminado correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al eliminar el usuario']);
    }
}
