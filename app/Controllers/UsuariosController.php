<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\RolModel;
use App\Models\ClienteModel;
use App\Models\RelacionClientesUsuariosModel; // Añadir el modelo para manejar la relación usuario-cliente
use App\Services\EmailService;
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
        $relacionModel = new RelacionClientesUsuariosModel();
        $emailService = new EmailService(); // Instanciar el servicio de correo

        $id = $this->request->getVar('id');
        $id_cliente = $this->request->getVar('id_cliente');

        // Verificar si el cliente existe
        if ($id_cliente && !$clienteModel->find($id_cliente)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Cliente no válido']);
        }

        $data = [
            'nombre_usuario' => $this->request->getVar('nombre_usuario'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'rol_id' => $this->request->getVar('rol_id'),
            'id_cliente' => $id_cliente ?: null,
        ];

        // Si se proporciona una contraseña, actualizarla
        if ($contrasena = $this->request->getVar('contrasena')) {
            $data['contrasena'] = password_hash($contrasena, PASSWORD_DEFAULT);
        }

        // Validar la unicidad del nombre de usuario y correo electrónico
        $usuarioExistente = null;
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

        // Guardar o actualizar el usuario
        if ($id) {
            $usuarioModel->update($id, $data);
            $idUsuario = $id; // Mantener el ID del usuario actualizado
            registrarAccion(session()->get('id'), 'Actualización de usuario', 'ID: ' . $id);
        } else {
            $usuarioModel->save($data);
            $idUsuario = $usuarioModel->insertID(); // Obtener el ID del nuevo usuario
            registrarAccion(session()->get('id'), 'Creación de usuario', 'Nombre de usuario: ' . $this->request->getVar('nombre_usuario'));

            // Enviar correo de bienvenida después de la creación
            $this->enviarCorreoBienvenida(
                $data['correo_electronico'],
                $data['nombre_usuario'],
                $contrasena // Solo si se proporciona una contraseña
            );
        }

        // Actualizar la relación en la tabla `relacion_clientes_usuarios`
        if (in_array($this->request->getVar('rol_id'), [2, 3, 4]) && $id_cliente) {
            $relacionModel->where('id_usuario', $idUsuario)->delete();
            $relacionModel->insert([
                'id_usuario' => $idUsuario,
                'id_cliente' => $id_cliente,
            ]);
        }

        return $this->response->setJSON(['message' => 'Usuario guardado correctamente']);
    }

    /**
     * Enviar correo de bienvenida al nuevo usuario
     *
     * @param string $email Dirección de correo del usuario
     * @param string $nombreUsuario Nombre de usuario
     * @param string|null $contrasena Contraseña del usuario
     * @return void
     */
    protected function enviarCorreoBienvenida($email, $nombreUsuario, $contrasena = null)
    {
        $emailService = new EmailService();

        // Crear el mensaje
        $mensaje = "<p>Estimado/a <strong>{$nombreUsuario}</strong>,</p>";
        $mensaje .= "<p>Su cuenta ha sido creada exitosamente en nuestra plataforma de Eqqua Denuncias.</p>";
        $mensaje .= "<p>Sus credenciales de acceso son:</p>";
        $mensaje .= "<p><strong>Nombre de usuario:</strong> {$nombreUsuario}</p>";
        if ($contrasena) {
            $mensaje .= "<p><strong>Contraseña:</strong> {$contrasena}</p>";
        }
        $mensaje .= "<p>Puede acceder a su cuenta utilizando el siguiente enlace: <a href='" . base_url() . "'>Iniciar Sesión</a></p>";
        $mensaje .= "<p>Saludos cordiales,</p>";
        $mensaje .= "<p><strong>Eqqua Denuncias</strong></p>";

        // Enviar el correo
        $emailService->sendEmail($email, 'Bienvenido a Eqqua Denuncias', $mensaje);
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
