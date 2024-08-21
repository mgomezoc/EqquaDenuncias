<?php

namespace App\Controllers;

use App\Models\DepartamentoModel;
use App\Models\ClienteModel;
use App\Models\SucursalModel;
use CodeIgniter\Controller;

class DepartamentosController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Administración de Departamentos',
            'controlador' => 'Departamentos',
            'vista' => 'Departamentos',
        ];

        return view('departamentos/index', $data);
    }

    public function listarDepartamentos()
    {
        $departamentoModel = new DepartamentoModel();
        $departamentos = $departamentoModel->getDepartamentosWithDetails(); // Usar el nuevo método

        return $this->response->setJSON($departamentos);
    }

    public function guardarDepartamento()
    {
        $departamentoModel = new DepartamentoModel();
        $id = $this->request->getVar('id');

        $data = [
            'nombre' => $this->request->getVar('nombre'),
            'id_sucursal' => $this->request->getVar('id_sucursal')
        ];

        if ($id) {
            $departamentoModel->update($id, $data);
        } else {
            $departamentoModel->save($data);
        }

        return $this->response->setJSON(['message' => 'Departamento guardado correctamente']);
    }

    public function eliminarDepartamento($id)
    {
        $departamentoModel = new DepartamentoModel();
        $departamentoModel->delete($id);

        return $this->response->setJSON(['message' => 'Departamento eliminado correctamente']);
    }

    public function listarClientes()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        return $this->response->setJSON($clientes);
    }

    public function listarSucursales($id_cliente)
    {
        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->where('id_cliente', $id_cliente)->findAll();

        return $this->response->setJSON($sucursales);
    }

    public function obtener($id)
    {
        $departamentoModel = new DepartamentoModel();
        $departamento = $departamentoModel->find($id);

        if (!$departamento) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Departamento no encontrado']);
        }

        return $this->response->setJSON($departamento);
    }

    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();
        $departamentos = $departamentoModel->where('id_sucursal', $id_sucursal)->findAll();

        return $this->response->setJSON($departamentos);
    }
}
