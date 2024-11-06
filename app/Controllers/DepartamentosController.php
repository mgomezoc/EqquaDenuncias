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

        // Si es_general es 1, id_sucursal debe ser NULL
        $data = [
            'nombre' => $this->request->getVar('nombre'),
            'es_general' => $this->request->getVar('es_general'),
            'id_sucursal' => $this->request->getVar('es_general') == 1 ? null : $this->request->getVar('id_sucursal')
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

        $departamento = $departamentoModel
            ->select('departamentos.*, sucursales.id_cliente, sucursales.nombre AS sucursal_nombre, clientes.nombre_empresa AS cliente_nombre')
            ->join('sucursales', 'sucursales.id = departamentos.id_sucursal', 'left')  // Cambiar a LEFT JOIN
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')          // Cambiar a LEFT JOIN
            ->where('departamentos.id', $id)
            ->where('(departamentos.es_general = 1 OR departamentos.id_sucursal IS NOT NULL)') // Incluir generales o con sucursal
            ->first();

        if (!$departamento) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Departamento no encontrado']);
        }

        return $this->response->setJSON($departamento);
    }

    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();

        // Obtener departamentos de la sucursal específica o los departamentos generales
        $departamentos = $departamentoModel
            ->where('id_sucursal', $id_sucursal)
            ->orWhere('es_general', 1)
            ->findAll();

        // Verificar si se obtuvieron resultados
        if (!$departamentos) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No se encontraron departamentos para la sucursal especificada o departamentos generales.']);
        }

        return $this->response->setJSON($departamentos);
    }
}
