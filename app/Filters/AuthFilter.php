<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Verifica si el usuario está autenticado
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        // Si hay roles especificados en los argumentos, verificamos el acceso
        if (!empty($arguments)) {
            $userRole = $session->get('rol_slug'); // Suponiendo que guardas el 'slug' del rol en la sesión

            // Verifica si el rol del usuario está en los roles permitidos
            if (!in_array($userRole, $arguments)) {
                return redirect()->to('/noautorizado');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No se requiere acción posterior en este caso
    }
}
