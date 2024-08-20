<?php

namespace App\Controllers;

use App\Models\DepartamentoModel;
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
        $departamentos = $departamentoModel->findAll();

        return $this->response->setJSON($departamentos);
    }

    public function listarSucursales()
    {
        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->findAll();

        return $this->response->setJSON($sucursales);
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
            registrarAccion(session()->get('id'), 'Actualización de departamento', 'ID: ' . $id);
        } else {
            $departamentoModel->save($data);
            $newId = $departamentoModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de departamento', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Departamento guardado correctamente']);
    }

    public function eliminarDepartamento($id)
    {
        $departamentoModel = new DepartamentoModel();
        $departamentoModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de departamento', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Departamento eliminado correctamente']);
    }
}
