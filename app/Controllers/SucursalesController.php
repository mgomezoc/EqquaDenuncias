<?php

namespace App\Controllers;

use App\Models\SucursalModel;
use App\Models\ClienteModel;
use CodeIgniter\Controller;

class SucursalesController extends Controller
{
    public function index()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        $data = [
            'title' => 'Administración de Sucursales',
            'controlador' => 'Sucursales',
            'vista' => 'Sucursales',
            'clientes' => $clientes
        ];

        return view('sucursales/index', $data);
    }

    public function listar()
    {
        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->select('sucursales.*, clientes.nombre_empresa AS cliente_nombre')
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')
            ->findAll();

        return $this->response->setJSON($sucursales);
    }

    public function guardar()
    {
        $sucursalModel = new SucursalModel();
        $id = $this->request->getVar('id');

        $data = [
            'id_cliente' => $this->request->getVar('id_cliente'),
            'nombre' => $this->request->getVar('nombre'),
            'direccion' => $this->request->getVar('direccion'),
        ];

        if ($id) {
            $sucursalModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de sucursal', 'ID: ' . $id);
        } else {
            $sucursalModel->save($data);
            $newId = $sucursalModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de sucursal', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Sucursal guardada correctamente']);
    }

    public function obtener($id)
    {
        $sucursalModel = new SucursalModel();
        $sucursal = $sucursalModel->find($id);

        registrarAccion(session()->get('id'), 'Visualización de sucursal', 'ID: ' . $id);

        return $this->response->setJSON($sucursal);
    }

    public function eliminar($id)
    {
        $sucursalModel = new SucursalModel();
        $sucursalModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de sucursal', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Sucursal eliminada correctamente']);
    }
}
