<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\UsuarioModel;
use App\Models\RolModel;

class Auth extends BaseController
{
    public function register()
    {
        return view('auth/register');
    }

    public function registerSubmit()
    {
        $usuarioModel = new UsuarioModel();
        $rolModel = new RolModel();

        $rol_id = $this->request->getVar('rol_id') ?? 1; // 1 es el ID por defecto

        // Verifica si el rol_id existe en la tabla roles
        if (!$rolModel->find($rol_id)) {
            return redirect()->back()->withInput()->with('msg', 'El rol especificado no existe');
        }

        $rules = [
            'nombre_usuario' => 'required|min_length[3]|is_unique[usuarios.nombre_usuario]',
            'correo_electronico' => 'required|valid_email|is_unique[usuarios.correo_electronico]',
            'contrasena' => 'required|min_length[5]',
            'rol_id' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'nombre_usuario' => $this->request->getVar('nombre_usuario'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'contrasena' => password_hash($this->request->getVar('contrasena'), PASSWORD_DEFAULT),
            'rol_id' => $rol_id,
        ];

        if ($usuarioModel->save($data)) {
            // Registrar la acción en la auditoría
            registrarAccion(session()->get('id'), 'Registro de usuario', 'Nombre de usuario: ' . $this->request->getVar('nombre_usuario'));
            return redirect()->to('/login')->with('msg', 'Usuario registrado exitosamente');
        } else {
            return redirect()->back()->withInput()->with('msg', 'Error al registrar el usuario');
        }
    }

    public function login()
    {
        return view('auth/login');
    }

    public function loginSubmit()
    {
        $session = session();
        $model = new UsuarioModel();
        $email = $this->request->getVar('correo_electronico');
        $password = $this->request->getVar('contrasena');
        $data = $model->getUserWithRole($email);

        if ($data) {
            $pass = $data['contrasena'];
            $authenticatePassword = password_verify($password, $pass);

            if ($authenticatePassword) {
                $model->updateLastLogin($data['id']); // Actualiza la última conexión

                $ses_data = [
                    'id' => $data['id'],
                    'nombre_usuario' => $data['nombre_usuario'],
                    'rol_nombre' => $data['rol_nombre'], // Nombre del rol
                    'rol_slug' => $data['rol_slug'],     // Slug del rol
                    'isLoggedIn' => TRUE
                ];

                // Si el rol es CLIENTE, agregar id_cliente y nombre_cliente a la sesión
                if (in_array($data['rol_slug'], ['CLIENTE'])) {
                    $modelCliente = new ClienteModel();
                    $cliente = $modelCliente->getClienteById($data['id_cliente']);


                    $ses_data['id_cliente'] = $data['id_cliente'];
                    $ses_data['nombre_empresa'] = $cliente['nombre_empresa'];
                    $ses_data['slug'] = $cliente['slug'];
                    $ses_data['solo_lectura'] = $data['solo_lectura'] ?? 0;
                }

                $session->set($ses_data);

                // Registrar la acción en la auditoría
                registrarAccion($data['id'], 'Inicio de sesión', 'Usuario: ' . $data['nombre_usuario']);
                return redirect()->to('/');
            } else {
                $session->setFlashdata('msg', 'Contraseña incorrecta');
                return redirect()->to('/login');
            }
        } else {
            $session->setFlashdata('msg', 'Correo electrónico no encontrado');
            return redirect()->to('/login');
        }
    }

    public function logout()
    {
        // Registrar la acción en la auditoría
        registrarAccion(session()->get('id'), 'Cierre de sesión', 'Usuario: ' . session()->get('nombre_usuario'));

        session()->destroy();
        return redirect()->to('/login');
    }

    public function forgotPassword()
    {
        return view('auth/forgot_password');
    }

    public function forgotPasswordSubmit()
    {
        $email = $this->request->getVar('correo_electronico');
        $usuarioModel = new \App\Models\UsuarioModel();
        $user = $usuarioModel->where('correo_electronico', $email)->where('activo', 1)->first();

        if ($user) {
            // Generar token único y su fecha de expiración (1 hora de validez)
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Actualizar el token y la fecha de expiración en la base de datos
            $usuarioModel->update($user['id'], ['password_reset_token' => $token, 'token_expiry' => $expiry]);

            // Enviar correo electrónico con el enlace para restablecer la contraseña
            $resetLink = base_url('reset-password/' . $token);
            $mensaje = $this->buildResetPasswordEmail($user['nombre_usuario'], $resetLink);

            $emailService = new \App\Services\EmailService();
            $emailService->sendEmail($email, 'Recuperación de contraseña', $mensaje);

            return redirect()->back()->with('msg', 'Revise su correo electrónico para restablecer su contraseña.');
        } else {
            return redirect()->back()->with('msg', 'El correo electrónico no está registrado.');
        }
    }

    private function buildResetPasswordEmail($nombreUsuario, $resetLink)
    {
        return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Restablecer contraseña</title>
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
                margin: 20px auto;
                background-color: #ffffff;
                border-collapse: collapse;
                border: 1px solid #ddd;
            }
            h1, h2, h3, p {
                margin: 0;
                padding: 0;
            }
            .header {
                background-color: #0047ba;
                padding: 20px;
                text-align: center;
                color: #ffffff;
            }
            .header img {
                max-width: 150px;
                height: auto;
            }
            .body-content {
                padding: 30px 20px;
                line-height: 1.6;
                color: #333333;
                font-size: 16px;
            }
            .body-content p {
                margin-bottom: 20px;
            }
            .cta-button {
                display: inline-block;
                padding: 12px 25px;
                background-color: #f4b400;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: bold;
            }
            .cta-button:hover {
                background-color: #c79800;
            }
            .footer {
                background-color: #0047ba;
                color: #ffffff;
                text-align: center;
                padding: 10px;
                font-size: 14px;
            }
            .footer a {
                color: #ffffff;
                text-decoration: underline;
            }
            @media only screen and (max-width: 600px) {
                .body-content {
                    padding: 20px;
                    font-size: 14px;
                }
                .cta-button {
                    padding: 10px 20px;
                    font-size: 14px;
                }
            }
        </style>
    </head>
    <body>
        <table>
            <!-- Header con el logo -->
            <tr>
                <td class="header">
                    <img src="https://eqqua.test/EqquaDenuncias/public/assets/images/logo.png" alt="Eqqua Denuncias Logo">
                    <h1>Restablecer contraseña</h1>
                </td>
            </tr>

            <!-- Contenido principal -->
            <tr>
                <td class="body-content">
                    <p>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:</p>

                    <!-- Botón de llamada a la acción -->
                    <p style="text-align: center;">
                        <a href="' . esc($resetLink) . '" class="cta-button">Restablecer Contraseña</a>
                    </p>

                    <p>Este enlace es válido por 1 hora.</p>
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer">
                    <p>&copy; ' . date('Y') . ' Eqqua Denuncias. Todos los derechos reservados.</p>
                    <p>Para más información, visita nuestro sitio web: <a href="https://eqqua.mx">eqqua.mx</a></p>
                </td>
            </tr>
        </table>
    </body>
    </html>';
    }


    public function resetPassword($token)
    {
        $usuarioModel = new \App\Models\UsuarioModel();
        $user = $usuarioModel->where('password_reset_token', $token)
            ->where('token_expiry >=', date('Y-m-d H:i:s'))
            ->first();

        if ($user) {
            return view('auth/reset_password', ['token' => $token]);
        } else {
            return redirect()->to('/login')->with('msg', 'El enlace para restablecer la contraseña ha expirado o no es válido.');
        }
    }

    public function resetPasswordSubmit()
    {
        $token = $this->request->getVar('token');
        $password = $this->request->getVar('contrasena');
        $confirmPassword = $this->request->getVar('confirmar_contrasena');

        if ($password !== $confirmPassword) {
            return redirect()->back()->with('msg', 'Las contraseñas no coinciden.');
        }

        $usuarioModel = new \App\Models\UsuarioModel();
        $user = $usuarioModel->where('password_reset_token', $token)
            ->where('token_expiry >=', date('Y-m-d H:i:s'))
            ->first();

        if ($user) {
            // Actualizar la contraseña y eliminar el token
            $usuarioModel->update($user['id'], [
                'contrasena' => $password,
                'password_reset_token' => null,
                'token_expiry' => null
            ]);

            return redirect()->to('/login')->with('msg', 'Tu contraseña ha sido restablecida con éxito.');
        } else {
            return redirect()->to('/login')->with('msg', 'El token ha expirado o no es válido.');
        }
    }
}
