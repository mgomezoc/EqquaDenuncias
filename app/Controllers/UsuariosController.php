<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\RolModel;
use App\Models\ClienteModel;
use App\Models\RelacionClientesUsuariosModel;
use App\Models\UsuarioPermisoDenuncianteModel;
use App\Services\EmailService;
use CodeIgniter\Controller;

class UsuariosController extends Controller
{
    public function index()
    {
        $roles    = (new RolModel())->findAll();
        $clientes = (new ClienteModel())->findAll();

        $data = [
            'title'       => 'Administración de Usuarios',
            'controlador' => 'Usuarios',
            'vista'       => 'Usuarios',
            'roles'       => $roles,
            'clientes'    => $clientes,
        ];

        return view('usuarios/index', $data);
    }

    public function listar()
    {
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel
            ->select('usuarios.*, roles.nombre AS rol_nombre, clientes.nombre_empresa AS cliente_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('clientes', 'clientes.id = usuarios.id_cliente', 'left')
            ->where('usuarios.activo', 1)
            ->findAll();

        return $this->response->setJSON($usuarios);
    }

    public function guardar()
    {
        $usuarioModel  = new UsuarioModel();
        $clienteModel  = new ClienteModel();
        $relacionModel = new RelacionClientesUsuariosModel();

        $id         = $this->request->getVar('id');
        $id_cliente = $this->request->getVar('id_cliente');

        if ($id_cliente && !$clienteModel->find($id_cliente)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Cliente no válido']);
        }

        $data = [
            'nombre_usuario'       => $this->request->getVar('nombre_usuario'),
            'correo_electronico'   => $this->request->getVar('correo_electronico'),
            'rol_id'               => $this->request->getVar('rol_id'),
            'id_cliente'           => $id_cliente ?: null,
            'activo'               => 1,
            'recibe_notificaciones' => $this->request->getVar('recibe_notificaciones') ? 1 : 0,
            'correo_notificaciones' => $this->request->getVar('correo_notificaciones') ?: $this->request->getVar('correo_electronico'),
            'solo_lectura'         => $this->request->getVar('solo_lectura') ? 1 : 0,
        ];

        $contrasena = $this->request->getVar('contrasena');
        if (!empty($contrasena)) {
            $data['contrasena'] = $contrasena;
        }

        // Unicidad (solo usuarios activos)
        if ($id) {
            $usuarioExistente = $usuarioModel->where('id !=', $id)
                ->where('activo', 1)
                ->groupStart()
                ->where('nombre_usuario', $this->request->getVar('nombre_usuario'))
                ->orWhere('correo_electronico', $this->request->getVar('correo_electronico'))
                ->groupEnd()
                ->first();
        } else {
            $usuarioExistente = $usuarioModel->where('activo', 1)
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

        // Crear/actualizar usuario
        if ($id) {
            $usuarioModel->update($id, $data);
            $idUsuario = (int) $id;
            registrarAccion(session()->get('id'), 'Actualización de usuario', 'ID: ' . $id);
        } else {
            $usuarioModel->save($data);
            $idUsuario = (int) $usuarioModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de usuario', 'Nombre de usuario: ' . $this->request->getVar('nombre_usuario'));

            $this->enviarCorreoBienvenida(
                $data['correo_electronico'],
                $data['nombre_usuario'],
                $contrasena
            );
        }

        // Relación usuario-cliente (para Agente, Supervisor, Cliente)
        if (in_array((int)$this->request->getVar('rol_id'), [2, 3, 4], true) && $id_cliente) {
            $relacionModel->where('id_usuario', $idUsuario)->delete();
            $relacionModel->insert([
                'id_usuario' => $idUsuario,
                'id_cliente' => $id_cliente,
            ]);
        }

        // Permisos por tipo_denunciante (solo rol Cliente)
        $tiposValidos        = ['Colaborador', 'Proveedor', 'Cliente', 'No se'];
        $tiposSeleccionados  = $this->request->getVar('tipos_denunciante'); // array|null
        $permisoTipoModel    = new UsuarioPermisoDenuncianteModel();

        if ((int)$data['rol_id'] === 4) {
            // Limpia permisos actuales
            $permisoTipoModel->where('id_usuario', $idUsuario)->delete();

            // Si llegan tipos válidos, se restringe; vacío => ver todos (sin filas)
            if (is_array($tiposSeleccionados) && count($tiposSeleccionados)) {
                $rows = [];
                foreach ($tiposSeleccionados as $tipo) {
                    $tipo = trim((string)$tipo);
                    if (in_array($tipo, $tiposValidos, true)) {
                        $rows[] = [
                            'id_usuario'      => $idUsuario,
                            'tipo_denunciante' => $tipo,
                        ];
                    }
                }
                if (!empty($rows)) {
                    $permisoTipoModel->insertBatch($rows);
                }
            }
        } else {
            // Si cambia a otro rol, sin restricciones
            $permisoTipoModel->where('id_usuario', $idUsuario)->delete();
        }

        return $this->response->setJSON(['message' => 'Usuario guardado correctamente']);
    }

    protected function enviarCorreoBienvenida($email, $nombreUsuario, $contrasena = null)
    {
        $emailService = new EmailService();

        $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Bienvenido a Eqqua</title>
                <style>
                    body { font-family: "Arial", sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0; width: 100%; }
                    table { max-width: 600px; width: 100%; margin: 0 auto; background-color: #fff; border-collapse: collapse; }
                    h1, h2, h3, p { margin: 0; }
                    .header { background-color: #0047ba; padding: 20px; text-align: center; color: #fff; }
                    .header img { max-width: 150px; height: auto; }
                    .body-content { padding: 20px; }
                    .body-content h2 { color: #0047ba; font-size: 22px; }
                    .body-content p { font-size: 16px; color: #333; line-height: 1.6; }
                    .cta-button { display: inline-block; padding: 10px 20px; margin-top: 20px; background-color: #f4b400; color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px; }
                    .footer { background-color: #0047ba; color: #fff; text-align: center; padding: 10px 20px; font-size: 14px; }
                    .footer a { color: #fff; text-decoration: underline; }
                    @media only screen and (max-width: 600px) { .header img { max-width: 120px; } .body-content { padding: 15px; } .cta-button { font-size: 14px; } }
                </style>
            </head>
            <body>
                <table>
                    <tr>
                        <td class="header">
                            <img src="https://denuncias.eqqua.mx/assets/images/logo_blanco.png" alt="Eqqua Denuncias Logo">
                            <h1>Bienvenido a Eqqua</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-content">
                            <h2>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</h2>
                            <p>Su cuenta ha sido creada exitosamente en la plataforma <strong>Eqqua</strong>.</p>
                            <p>Credenciales de acceso:</p>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre de usuario:</strong> ' . esc($email) . '</li>
                                <li><strong>Contraseña:</strong> ' . esc((string)$contrasena) . '</li>
                            </ul>
                            <p>Para acceder haga clic en:</p>
                            <p><a href="' . base_url() . '" class="cta-button">Iniciar Sesión</a></p>
                            <p>Saludos cordiales,<br><strong>Eqqua</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p>&copy; ' . date('Y') . ' Eqqua. Todos los derechos reservados.</p>
                            <p>Más información: <a href="https://eqqua.mx">eqqua.mx</a></p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        $emailService->sendEmail($email, 'Bienvenido a Eqqua', $mensaje);
    }

    public function obtener($id)
    {
        $usuario = (new UsuarioModel())->find($id);
        registrarAccion(session()->get('id'), 'Visualización de usuario', 'ID: ' . $id);
        return $this->response->setJSON($usuario);
    }

    public function eliminar($id)
    {
        $usuarioModel = new UsuarioModel();

        $usuario = $usuarioModel->find($id);
        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Usuario no encontrado']);
        }

        $data = ['id' => $id, 'activo' => 0];

        if ($usuarioModel->save($data)) {
            registrarAccion(session()->get('id'), 'Desactivación de usuario', 'ID: ' . $id);
            return $this->response->setJSON(['message' => 'Usuario marcado como inactivo correctamente']);
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Error al desactivar el usuario']);
    }

    public function validarUnico()
    {
        $usuarioModel     = new UsuarioModel();
        $id               = $this->request->getVar('id');
        $nombre_usuario   = $this->request->getVar('nombre_usuario');
        $correo_electronico = $this->request->getVar('correo_electronico');

        $conditions = [];
        if ($nombre_usuario)     $conditions['nombre_usuario']   = $nombre_usuario;
        if ($correo_electronico) $conditions['correo_electronico'] = $correo_electronico;

        if (!empty($conditions)) {
            $usuarioModel->groupStart();
            foreach ($conditions as $field => $value) {
                $usuarioModel->orWhere($field, $value);
            }
            $usuarioModel->groupEnd();

            $usuarioModel->where('activo', 1);
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

    // Devuelve los tipos de denunciante asignados a un usuario (para precargar en edición)
    public function tiposDenunciante($id)
    {
        $tipos = (new UsuarioPermisoDenuncianteModel())->getTiposByUsuario((int)$id);
        return $this->response->setJSON($tipos);
    }
}
