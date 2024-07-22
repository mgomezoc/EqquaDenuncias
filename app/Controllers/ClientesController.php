<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class ClientesController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Administración de Clientes',
            'controlador' => 'Clientes',
            'vista' => 'Clientes',
        ];

        return view('clientes/index', $data);
    }

    public function listar()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        return $this->response->setJSON($clientes);
    }

    public function guardar()
    {
        $clienteModel = new ClienteModel();
        $id = $this->request->getVar('id');

        $data = [
            'nombre_empresa' => $this->request->getVar('nombre_empresa'),
            'numero_identificacion' => $this->request->getVar('numero_identificacion'),
            'correo_contacto' => $this->request->getVar('correo_contacto'),
            'telefono_contacto' => $this->request->getVar('telefono_contacto'),
            'direccion' => $this->request->getVar('direccion'),
            'slug' => $this->request->getVar('slug'),
            'logo' => $this->request->getVar('logo'),
            'banner' => $this->request->getVar('banner')
        ];

        if ($id) {
            $existingClient = $clienteModel->find($id);

            // Validar unicidad del nombre de empresa, correo de contacto y slug
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

        if ($clienteExistente) {
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
            // Filtrar los campos no enviados
            foreach ($data as $key => $value) {
                if (empty($value) && $value !== '0') {
                    unset($data[$key]);
                }
            }

            $clienteModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de cliente', 'ID: ' . $id);
        } else {
            $clienteModel->save($data);
            $newId = $clienteModel->insertID(); // Obtener el ID del nuevo cliente creado
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
                $messages = [];
                if ($cliente['nombre_empresa'] == $nombre_empresa) {
                    $messages[] = 'El nombre de la empresa ya está en uso';
                }
                if ($cliente['numero_identificacion'] == $numero_identificacion) {
                    $messages[] = 'El número de identificación ya está en uso';
                }
                if ($cliente['correo_contacto'] == $correo_contacto) {
                    $messages[] = 'El correo de contacto ya está en uso';
                }
                if ($cliente['slug'] == $slug) {
                    $messages[] = 'El slug ya está en uso';
                }

                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
    }
}
