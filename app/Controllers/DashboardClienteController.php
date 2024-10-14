<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardClienteController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();
        $clienteId = session()->get('id_cliente'); // Obtener el ID del cliente

        // Obtener datos del mes actual o filtrados
        $estatusDenuncias = $model->getDenunciasPorEstatus(null, null, $clienteId);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento(null, null, $clienteId);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal(null, null, $clienteId);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento(null, null, $clienteId);

        // Calcular los totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        $data = [
            'title' => 'Dashboard del Cliente',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento
        ];
        /*
        echo '<pre>' . print_r($data, true) . "</pre><br>";
        exit($clienteId);
        */

        return view('dashboard/cliente_index', $data);
    }

    public function filtrar()
    {
        $model = new DashboardModel();
        $clienteId = session()->get('id_cliente'); // Obtener el ID del cliente

        // Obtener las fechas de filtro desde el formulario
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        // Obtener los datos filtrados
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate, $clienteId);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate, $clienteId);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate, $clienteId);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate, $clienteId);

        // Calcular los totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        // Pasar los datos a la vista
        $data = [
            'title' => 'Dashboard del Cliente',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];

        return view('dashboard/cliente_index', $data);
    }
}
