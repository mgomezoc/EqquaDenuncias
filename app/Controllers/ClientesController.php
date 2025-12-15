<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class ClientesController extends Controller
{
    public function index()
    {
        $session = session();

        $data = [
            'title'       => 'Administración de Clientes',
            'controlador' => 'Clientes',
            'vista'       => 'Clientes',
            'rol_slug'    => $session->get('rol_slug'),
        ];

        return view('clientes/index', $data);
    }

    public function listar()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll(); // incluye politica_anonimato y mostrar_tipo_denunciante_publico

        return $this->response->setJSON($clientes);
    }

    public function guardar()
    {
        $session      = session();
        $rolSlug      = $session->get('rol_slug');
        $clienteModel = new ClienteModel();

        $id = $this->request->getVar('id');

        // Leer todos los campos que podrían venir del formulario
        $data = [
            'nombre_empresa'        => $this->request->getVar('nombre_empresa'),
            'numero_identificacion' => $this->request->getVar('numero_identificacion'),
            'correo_contacto'       => $this->request->getVar('correo_contacto'),
            'telefono_contacto'     => $this->request->getVar('telefono_contacto'),
            'direccion'             => $this->request->getVar('direccion'),
            'slug'                  => $this->request->getVar('slug'),
            'logo'                  => $this->request->getVar('logo'),
            'banner'                => $this->request->getVar('banner'),
            'saludo'                => $this->request->getVar('saludo'),
            'whatsapp'              => $this->request->getVar('whatsapp'),
            'primary_color'         => $this->request->getVar('primary_color'),
            'secondary_color'       => $this->request->getVar('secondary_color'),
            'link_color'            => $this->request->getVar('link_color'),
        ];

        // Normalizar/validar política si viene en la petición
        $politica = $this->request->getVar('politica_anonimato');
        if ($politica !== null) {
            $politica = (int) $politica;
            if (!in_array($politica, [0, 1, 2], true)) {
                $politica = 0;
            }
            $data['politica_anonimato'] = $politica;
        }

        // Normalizar/validar flag de visualización del tipo de denunciante (formulario público)
        $mostrarTipoDenunciantePublico = $this->request->getVar('mostrar_tipo_denunciante_publico');
        if ($mostrarTipoDenunciantePublico !== null) {
            $valorNormalizado = strtolower(trim((string) $mostrarTipoDenunciantePublico));
            $data['mostrar_tipo_denunciante_publico'] = in_array($valorNormalizado, ['1', 'true', 'on', 'si', 'yes'], true) ? 1 : 0;
        }

        // === Reglas de permisos ===
        // CLIENTE sólo puede actualizar su propio registro y únicamente 'politica_anonimato' y 'mostrar_tipo_denunciante_publico'
        if ($rolSlug === 'CLIENTE') {
            $miClienteId = (int) ($session->get('id_cliente') ?? 0);
            if ((int) $id !== $miClienteId) {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'No autorizado.']);
            }

            $data = array_intersect_key($data, array_flip([
                'politica_anonimato',
                'mostrar_tipo_denunciante_publico'
            ]));
        }

        // === Validación de unicidad (solo aplica si ADMIN crea/edita más campos) ===
        if ($id) {
            $existingClient = $clienteModel->find($id);

            $clienteExistente = $clienteModel->where('id !=', $id)
                ->groupStart()
                ->where('nombre_empresa', $this->request->getVar('nombre_empresa'))
                ->orWhere('correo_contacto', $this->request->getVar('correo_contacto'))
                ->orWhere('slug', $this->request->getVar('slug'))
                ->groupEnd()
                ->first();
        } else {
            $clienteExistente = $clienteModel->groupStart()
                ->where('nombre_empresa', $this->request->getVar('nombre_empresa'))
                ->orWhere('correo_contacto', $this->request->getVar('correo_contacto'))
                ->orWhere('slug', $this->request->getVar('slug'))
                ->groupEnd()
                ->first();
        }

        if ($clienteExistente && $rolSlug === 'ADMIN') {
            $message = [];
            if ($clienteExistente['nombre_empresa'] == $this->request->getVar('nombre_empresa')) {
                $message[] = 'El nombre de la empresa ya está en uso';
            }
            if ($clienteExistente['correo_contacto'] == $this->request->getVar('correo_contacto')) {
                $message[] = 'El correo de contacto ya está en uso';
            }
            if ($clienteExistente['slug'] == $this->request->getVar('slug')) {
                $message[] = 'El slug ya está en uso';
            }
            return $this->response->setStatusCode(409)->setJSON(['message' => implode(', ', $message)]);
        }

        if ($id) {
            // Filtrar campos vacíos, conservando '0'
            foreach ($data as $key => $value) {
                if ($value === null || $value === '') {
                    unset($data[$key]);
                }
            }

            if (!empty($data)) {
                $clienteModel->update($id, $data);
                registrarAccion(session()->get('id'), 'Actualización de cliente', 'ID: ' . $id);
            }
        } else {
            // Alta (sólo ADMIN)
            if ($rolSlug !== 'ADMIN') {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'No autorizado.']);
            }
            $clienteModel->save($data);
            $newId = $clienteModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de cliente', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Cliente guardado correctamente']);
    }

    public function obtener($id)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->find($id);

        registrarAccion(session()->get('id'), 'Visualización de cliente', 'ID: ' . $id);

        return $this->response->setJSON($cliente);
    }

    public function eliminar($id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->where('id_cliente', $id)->first();

        if ($usuario) {
            return $this->response->setStatusCode(409)->setJSON(['message' => 'No se puede eliminar el cliente porque tiene usuarios asociados']);
        }

        $clienteModel = new ClienteModel();
        $clienteModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de cliente', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Cliente eliminado correctamente']);
    }

    public function subirImagen()
    {
        $file = $this->request->getFile('file');

        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . '../public/assets/images/clientes', $newName);
            return $this->response->setJSON(['filename' => $newName]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo subir la imagen.']);
    }

    public function validarUnico()
    {
        $clienteModel = new ClienteModel();
        $id = $this->request->getVar('id');
        $nombre_empresa = $this->request->getVar('nombre_empresa');
        $numero_identificacion = $this->request->getVar('numero_identificacion');
        $correo_contacto = $this->request->getVar('correo_contacto');
        $slug = $this->request->getVar('slug');

        $conditions = [];
        if ($nombre_empresa) {
            $conditions['nombre_empresa'] = $nombre_empresa;
        }
        if ($numero_identificacion) {
            $conditions['numero_identificacion'] = $numero_identificacion;
        }
        if ($correo_contacto) {
            $conditions['correo_contacto'] = $correo_contacto;
        }
        if ($slug) {
            $conditions['slug'] = $slug;
        }

        if (!empty($conditions)) {
            $clienteModel->groupStart();
            foreach ($conditions as $field => $value) {
                $clienteModel->orWhere($field, $value);
            }
            $clienteModel->groupEnd();

            if ($id) {
                $clienteModel->where('id !=', $id);
            }

            $cliente = $clienteModel->first();
            if ($cliente) {
                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
    }
}
