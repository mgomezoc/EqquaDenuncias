<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $session = session();
        $usuarioModel = new UsuarioModel();

        // Obtener los datos del usuario conectado
        $usuario = $usuarioModel->find($session->get('id'));

        $data = [
            'title' => 'Cambiar Contraseña',
            'controlador' => 'Sucursales',
            'vista' => 'Sucursales',
            'usuario' => $usuario
        ];

        // Pasar los datos a la vista
        return view('configuracion', $data);
    }

    public function actualizar()
    {
        $session = session();
        $usuarioModel = new UsuarioModel();

        // Obtener el usuario logueado
        $usuario = $usuarioModel->find($session->get('id'));

        // Validar que la contraseña anterior sea correcta
        $contrasenaAnterior = $this->request->getPost('contrasena_anterior');
        if (!password_verify($contrasenaAnterior, $usuario['contrasena'])) {
            return redirect()->back()->with('msg', 'La contraseña anterior es incorrecta');
        }

        // Validar que las contraseñas nuevas coincidan
        $nuevaContrasena = $this->request->getPost('nueva_contrasena');
        $confirmarContrasena = $this->request->getPost('confirmar_contrasena');

        if ($nuevaContrasena !== $confirmarContrasena) {
            return redirect()->back()->with('msg', 'Las contraseñas no coinciden');
        }

        // Actualizar la contraseña en la base de datos
        $usuarioModel->update($session->get('id'), [
            'contrasena' => $nuevaContrasena
        ]);

        // Mensaje de éxito
        return redirect()->back()->with('msg', '¡Contraseña actualizada con éxito!');
    }
}
