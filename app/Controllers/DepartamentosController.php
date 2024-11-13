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
            'id_cliente' => $this->request->getVar('id_cliente'),
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

        try {
            // Intenta eliminar el departamento
            $departamentoModel->delete($id);

            return $this->response->setJSON(['message' => 'Departamento eliminado correctamente']);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Captura el error de restricción de clave foránea
            if (strpos($e->getMessage(), 'a foreign key constraint fails') !== false) {
                return $this->response->setStatusCode(400)
                    ->setJSON(['error' => 'No se puede eliminar el departamento porque tiene denuncias asociadas.']);
            }

            // Para otros errores, lanza una excepción genérica
            return $this->response->setStatusCode(500)
                ->setJSON(['error' => 'Ocurrió un error al intentar eliminar el departamento.']);
        }
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
            ->select('departamentos.*, sucursales.id_cliente AS sucursal_cliente_id, sucursales.nombre AS sucursal_nombre, clientes.nombre_empresa AS cliente_nombre, clientes.id AS cliente_id')
            ->join('sucursales', 'sucursales.id = departamentos.id_sucursal', 'left')  // LEFT JOIN para sucursal
            ->join('clientes', 'clientes.id = IF(departamentos.es_general = 1, departamentos.id_cliente, sucursales.id_cliente)', 'left')  // LEFT JOIN para cliente, depende de es_general
            ->where('departamentos.id', $id)
            ->groupStart() // Agrupar condiciones para claridad
            ->where('departamentos.es_general', 1)
            ->where('departamentos.id_cliente = clientes.id')
            ->orGroupStart()
            ->where('departamentos.id_sucursal IS NOT NULL') // O departamentos con sucursal
            ->groupEnd()
            ->groupEnd()
            ->first();

        if (!$departamento) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Departamento no encontrado']);
        }

        return $this->response->setJSON($departamento);
    }


    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();

        // Obtener el ID del cliente correspondiente a la sucursal especificada
        $sucursalModel = new SucursalModel();  // Asegúrate de tener un modelo para sucursales
        $sucursal = $sucursalModel->find($id_sucursal);

        if (!$sucursal) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sucursal no encontrada.']);
        }

        $id_cliente = $sucursal['id_cliente'];

        // Obtener departamentos específicos de la sucursal o departamentos generales del cliente
        $departamentos = $departamentoModel
            ->groupStart()
            ->where('id_sucursal', $id_sucursal)
            ->orGroupStart()
            ->where('es_general', 1)
            ->where('id_cliente', $id_cliente)
            ->groupEnd()
            ->groupEnd()
            ->findAll();

        // Verificar si se obtuvieron resultados
        if (!$departamentos) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No se encontraron departamentos para la sucursal especificada o departamentos generales del cliente.']);
        }

        return $this->response->setJSON($departamentos);
    }
}
