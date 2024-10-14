<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\Controller;
use App\Services\EmailService;

class UsuariosClienteController extends Controller
{
    protected $usuarioModel;

    public function __construct()
    {
        // Instancia del modelo de usuario para su uso en todos los métodos del controlador
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        return view('cliente/usuarios', [
            'title' => 'Usuarios del Cliente'
        ]);
    }

    public function listar()
    {
        $clienteId = session()->get('id_cliente');

        // Obtener solo usuarios activos junto con el nombre del rol
        $usuarios = $this->usuarioModel->select('usuarios.id, usuarios.nombre_usuario, usuarios.correo_electronico, usuarios.ultima_conexion, roles.nombre AS rol_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('usuarios.id_cliente', $clienteId)
            ->where('usuarios.activo', 1) // Solo usuarios activos
            ->findAll();

        return $this->response->setJSON($usuarios);
    }

    public function guardar()
    {
        $data = $this->request->getPost();
        $data['id_cliente'] = session()->get('id_cliente');
        $data['activo'] = 1; // Por defecto, los nuevos usuarios estarán activos

        if ($this->usuarioModel->save($data)) {
            $data = [
                'nombre_usuario' => $this->request->getVar('nombre_usuario'),
                'correo_electronico' => $this->request->getVar('correo_electronico'),
                'contrasena' => $this->request->getVar('contrasena'),
            ];

            // Enviar correo de bienvenida después de la creación
            $this->enviarCorreoBienvenida(
                $data['correo_electronico'],
                $data['nombre_usuario'],
                $this->request->getVar('contrasena') // Solo si se proporciona una contraseña
            );

            registrarAccion(session()->get('id'), 'Creación de usuario', 'Nombre de usuario: ' . $this->request->getVar('nombre_usuario'));

            return $this->response->setJSON(['message' => 'Usuario guardado correctamente']);
        }


        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al guardar el usuario']);
    }

    public function eliminar($id)
    {
        // Verificar si el usuario existe y pertenece al cliente autenticado
        $clienteId = session()->get('id_cliente');
        $usuario = $this->usuarioModel->where('id', $id)
            ->where('id_cliente', $clienteId)
            ->first();

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Usuario no encontrado o no autorizado']);
        }

        // Marcar el usuario como inactivo
        if ($this->usuarioModel->setActivo($id, false)) {
            registrarAccion(session()->get('id'), 'Desactivación de usuario', 'ID: ' . $id);

            return $this->response->setJSON(['message' => 'Usuario marcado como inactivo correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al marcar como inactivo el usuario']);
    }

    public function validarUnico()
    {
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
            $this->usuarioModel->groupStart();
            foreach ($conditions as $field => $value) {
                $this->usuarioModel->orWhere($field, $value);
            }
            $this->usuarioModel->groupEnd();

            if ($id) {
                $this->usuarioModel->where('id !=', $id);
            }

            // Verificar solo usuarios activos para evitar conflictos con usuarios inactivos
            $this->usuarioModel->where('activo', 1);

            $usuario = $this->usuarioModel->first();
            if ($usuario) {
                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
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
                            <img src="https://eqqua.test/EqquaDenuncias/public/assets/images/logo.png" alt="Eqqua Denuncias Logo">
                            <h1>Bienvenido a Eqqua</h1>
                        </td>
                    </tr>

                    <!-- Body content -->
                    <tr>
                        <td class="body-content">
                            <h2>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</h2>
                            <p>Nos complace informarle que su cuenta ha sido creada exitosamente en la plataforma <strong>Eqqua Denuncias</strong>.</p>
                            <p>A continuación, encontrará sus credenciales de acceso:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre de usuario:</strong> ' . esc($nombreUsuario) . '</li>
                                <li><strong>Contraseña:</strong> ' . esc($contrasena) . '</li>
                            </ul>
                            <p>Para acceder a su cuenta, haga clic en el siguiente enlace:</p>
                            <p><a href="' . base_url() . '" class="cta-button">Iniciar Sesión</a></p>
                            <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
                            <p>Saludos cordiales,<br><strong>Eqqua Denuncias</strong></p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p>&copy; ' . date('Y') . ' Eqqua Denuncias. Todos los derechos reservados.</p>
                            <p>Para más información, visite nuestro sitio web: <a href="https://eqqua.mx">eqqua.mx</a></p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        // Enviar el correo
        $emailService->sendEmail($email, 'Bienvenido a Eqqua', $mensaje);
    }
}
