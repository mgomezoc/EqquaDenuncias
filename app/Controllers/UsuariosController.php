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
            ->where('usuarios.activo', 1) // Solo mostrar usuarios activos
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

        // Datos básicos
        $data = [
            'nombre_usuario' => $this->request->getVar('nombre_usuario'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'rol_id' => $this->request->getVar('rol_id'),
            'id_cliente' => $id_cliente ?: null,
            'activo' => 1,
            'recibe_notificaciones' => $this->request->getVar('recibe_notificaciones') ? 1 : 0,
            'correo_notificaciones' => $this->request->getVar('correo_notificaciones') ?: $this->request->getVar('correo_electronico'),
            'solo_lectura' => $this->request->getVar('solo_lectura') ? 1 : 0
        ];


        // Solo incluir la contraseña si se envía una
        $contrasena = $this->request->getVar('contrasena');
        if (!empty($contrasena)) {
            $data['contrasena'] = $contrasena;
        }

        // Validar la unicidad del nombre de usuario y correo electrónico solo para usuarios activos
        $usuarioExistente = null;
        if ($id) {
            $usuarioExistente = $usuarioModel->where('id !=', $id)
                ->where('activo', 1) // Filtrar solo usuarios activos
                ->groupStart()
                ->where('nombre_usuario', $this->request->getVar('nombre_usuario'))
                ->orWhere('correo_electronico', $this->request->getVar('correo_electronico'))
                ->groupEnd()
                ->first();
        } else {
            $usuarioExistente = $usuarioModel->where('activo', 1) // Filtrar solo usuarios activos
                ->groupStart()
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
            $idUsuario = $id;
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
        $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Bienvenido a Eqqua</title>
                <style>
                    /* Estilos generales */
                    body {
                        font-family: "Arial", sans-serif;
                        background-color: #f4f4f4;
                        color: #333333;
                        margin: 0;
                        padding: 0;
                        width: 100%;
                    }
                    table {
                        max-width: 600px;
                        width: 100%;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-collapse: collapse;
                    }
                    h1, h2, h3, p {
                        margin: 0;
                    }
                    .header {
                        background-color: #0047ba; /* Color primario del sistema */
                        padding: 20px;
                        text-align: center;
                        color: #ffffff;
                    }
                    .header img {
                        max-width: 150px;
                        height: auto;
                    }
                    .body-content {
                        padding: 20px;
                    }
                    .body-content h2 {
                        color: #0047ba;
                        font-size: 22px;
                    }
                    .body-content p {
                        font-size: 16px;
                        color: #333333;
                        line-height: 1.6;
                    }
                    .cta-button {
                        display: inline-block;
                        padding: 10px 20px;
                        margin-top: 20px;
                        background-color: #f4b400; /* Amarillo del sistema */
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 5px;
                        font-size: 16px;
                    }
                    .footer {
                        background-color: #0047ba;
                        color: #ffffff;
                        text-align: center;
                        padding: 10px 20px;
                        font-size: 14px;
                    }
                    .footer a {
                        color: #ffffff;
                        text-decoration: underline;
                    }
                    @media only screen and (max-width: 600px) {
                        .header img {
                            max-width: 120px;
                        }
                        .body-content {
                            padding: 15px;
                        }
                        .cta-button {
                            font-size: 14px;
                        }
                    }
                </style>
            </head>
            <body>
                <table>
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <img src="https://denuncias.eqqua.mx/assets/images/logo_blanco.png" alt="Eqqua Denuncias Logo">
                            <h1>Bienvenido a Eqqua</h1>
                        </td>
                    </tr>

                    <!-- Body content -->
                    <tr>
                        <td class="body-content">
                            <h2>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</h2>
                            <p>Nos complace informarle que su cuenta ha sido creada exitosamente en la plataforma <strong>Eqqua</strong>.</p>
                            <p>A continuación, encontrará sus credenciales de acceso:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre de usuario:</strong> ' . esc($email) . '</li>
                                <li><strong>Contraseña:</strong> ' . esc($contrasena) . '</li>
                            </ul>
                            <p>Para acceder a su cuenta, haga clic en el siguiente enlace:</p>
                            <p><a href="' . base_url() . '" class="cta-button">Iniciar Sesión</a></p>
                            <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
                            <p>Saludos cordiales,<br><strong>Eqqua</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p>&copy; ' . date('Y') . ' Eqqua. Todos los derechos reservados.</p>
                            <p>Para más información, visite nuestro sitio web: <a href="https://eqqua.mx">eqqua.mx</a></p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        // Enviar el correo
        $emailService->sendEmail($email, 'Bienvenido a Eqqua', $mensaje);
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

        // Verificar si el usuario existe
        $usuario = $usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Usuario no encontrado']);
        }

        // Marcar el usuario como inactivo en lugar de eliminarlo físicamente
        $data = ['id' => $id, 'activo' => 0]; // 0 indica inactivo

        if ($usuarioModel->save($data)) {
            registrarAccion(session()->get('id'), 'Desactivación de usuario', 'ID: ' . $id);
            return $this->response->setJSON(['message' => 'Usuario marcado como inactivo correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al desactivar el usuario']);
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

            // Filtrar solo usuarios activos
            $usuarioModel->where('activo', 1);

            // Excluir el usuario actual si se proporciona un ID
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
