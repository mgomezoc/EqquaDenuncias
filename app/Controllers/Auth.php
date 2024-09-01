<?php

namespace App\Controllers;

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

                // Si el rol es CLIENTE, agregamos id_cliente y nombre_cliente a la sesión
                if ($data['rol_slug'] === 'CLIENTE') {
                    $ses_data['id_cliente'] = $data['id_cliente']; // Guarda el id_cliente en la sesión
                    $ses_data['nombre_cliente'] = $data['nombre_cliente']; // Guarda el nombre_cliente en la sesión
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
}
