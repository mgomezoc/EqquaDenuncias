<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class UsuariosClienteController extends Controller
{
    public function index()
    {
        return view('cliente/usuarios', [
            'title' => 'Usuarios del Cliente'
        ]);
    }

    public function listar()
    {
        $clienteId = session()->get('id_cliente');
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel->where('id_cliente', $clienteId)->findAll();

        return $this->response->setJSON($usuarios);
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

    public function validarUnico()
    {
        $usuarioModel = new UsuarioModel();
        $id = $this->request->getVar('id');
        $nombre_usuario = $this->request->getVar('nombre_usuario');
        $correo_electronico = $this->request->getVar('correo_electronico');

        $conditions = [];
        if ($nombre_usuario) {
            $conditions['nombre_usuario'] = $nombre_usuario;
        }
        if ($correo_electronico) {
            $conditions['correo_electronico'] = $correo_electronico;
        }

        if (!empty($conditions)) {
            $usuarioModel->groupStart();
            foreach ($conditions as $field => $value) {
                $usuarioModel->orWhere($field, $value);
            }
            $usuarioModel->groupEnd();

            if ($id) {
                $usuarioModel->where('id !=', $id);
            }

            $usuario = $usuarioModel->first();
            if ($usuario) {
                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
    }
}
