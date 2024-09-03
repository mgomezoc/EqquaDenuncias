<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\RolModel;
use App\Models\ClienteModel;
use App\Models\RelacionClientesUsuariosModel; // Añadir el modelo para manejar la relación usuario-cliente
use CodeIgniter\Controller;

class UsuariosController extends Controller
{
    public function index()
    {
        $rolModel = new RolModel();
        $roles = $rolModel->findAll();

        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        $data = [
            'title' => 'Administración de Usuarios',
            'controlador' => 'Usuarios',
            'vista' => 'Usuarios',
            'roles' => $roles,
            'clientes' => $clientes
        ];

        return view('usuarios/index', $data);
    }

    public function listar()
    {
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel->select('usuarios.*, roles.nombre AS rol_nombre, clientes.nombre_empresa AS cliente_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('clientes', 'clientes.id = usuarios.id_cliente', 'left')
            ->findAll();

        return $this->response->setJSON($usuarios);
    }

    public function guardar()
    {
        $usuarioModel = new UsuarioModel();
        $clienteModel = new ClienteModel();
        $relacionModel = new RelacionClientesUsuariosModel(); // Instanciar el modelo de relación
        $id = $this->request->getVar('id');

        $id_cliente = $this->request->getVar('id_cliente');

        // Verificar si el id_cliente existe en la tabla clientes si se proporciona
        if ($id_cliente && !$clienteModel->find($id_cliente)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Cliente no válido']);
        }

        $data = [
            'nombre_usuario' => $this->request->getVar('nombre_usuario'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'rol_id' => $this->request->getVar('rol_id'),
            'id_cliente' => $id_cliente ?: null  // Si id_cliente no está presente, asignar null
        ];

        if ($contrasena = $this->request->getVar('contrasena')) {
            $data['contrasena'] = password_hash($contrasena, PASSWORD_DEFAULT);
        }

        // Validar unicidad del nombre de usuario y correo electrónico
        if ($id) {
            $usuarioExistente = $usuarioModel->where('id !=', $id)
                ->groupStart()
                ->where('nombre_usuario', $this->request->getVar('nombre_usuario'))
                ->orWhere('correo_electronico', $this->request->getVar('correo_electronico'))
                ->groupEnd()
                ->first();
        } else {
            $usuarioExistente = $usuarioModel->groupStart()
                ->where('nombre_usuario', $this->request->getVar('nombre_usuario'))
                ->orWhere('correo_electronico', $this->request->getVar('correo_electronico'))
                ->groupEnd()
                ->first();
        }

        if ($usuarioExistente) {
            $message = [];
            if ($usuarioExistente['nombre_usuario'] == $this->request->getVar('nombre_usuario')) {
                $message[] = 'El nombre de usuario ya está en uso';
            }
            if ($usuarioExistente['correo_electronico'] == $this->request->getVar('correo_electronico')) {
                $message[] = 'El correo electrónico ya está en uso';
            }
            return $this->response->setStatusCode(409)->setJSON(['message' => implode(', ', $message)]);
        }

        if ($id) {
            $usuarioModel->update($id, $data);
            $idUsuario = $id; // Mantener el ID del usuario actualizado
            registrarAccion(session()->get('id'), 'Actualización de usuario', 'ID: ' . $id);
        } else {
            $usuarioModel->save($data);
            $idUsuario = $usuarioModel->insertID(); // Obtener el ID del nuevo usuario
            registrarAccion(session()->get('id'), 'Creación de usuario', 'Nombre de usuario: ' . $this->request->getVar('nombre_usuario'));
        }

        // Actualizar la relación en la tabla `relacion_clientes_usuarios`
        if (in_array($this->request->getVar('rol_id'), [4, 5]) && $id_cliente) {  // 4 y 5 son roles de Agente y Supervisor de Calidad
            // Borrar relación existente
            $relacionModel->where('id_usuario', $idUsuario)->delete();

            // Guardar la nueva relación
            $relacionModel->insert([
                'id_usuario' => $idUsuario,
                'id_cliente' => $id_cliente,
            ]);
        }

        return $this->response->setJSON(['message' => 'Usuario guardado correctamente']);
    }

    public function obtener($id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find($id);

        registrarAccion(session()->get('id'), 'Visualización de usuario', 'ID: ' . $id);

        return $this->response->setJSON($usuario);
    }

    public function eliminar($id)
    {
        $usuarioModel = new UsuarioModel();
        $relacionModel = new RelacionClientesUsuariosModel();

        // Eliminar la relación en `relacion_clientes_usuarios`
        $relacionModel->where('id_usuario', $id)->delete();

        // Eliminar el usuario
        $usuarioModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de usuario', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Usuario eliminado correctamente']);
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
                $messages = [];
                if ($usuario['nombre_usuario'] == $nombre_usuario) {
                    $messages[] = 'El nombre de usuario ya está en uso';
                }
                if ($usuario['correo_electronico'] == $correo_electronico) {
                    $messages[] = 'El correo electrónico ya está en uso';
                }

                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
    }
}
