<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();

        // Obtener datos del mes actual
        $estatusDenuncias = $model->getDenunciasPorEstatus();
        $denunciasPorDepto = $model->getDenunciasPorDepartamento();
        $denunciasPorSucursal = $model->getDenunciasPorSucursal();
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento();

        // Calcular totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        $data = [
            'title' => 'Dashboard',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
        ];

        return view('dashboard/index', $data);
    }

    public function filtrar()
    {
        $model = new DashboardModel();

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);

        // Calcular totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        $data = [
            'title' => 'Dashboard',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        return view('dashboard/index', $data);
    }
}
