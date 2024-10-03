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
        $denunciasPorSucursal = $model->getDenunciasPorSucursal();  // NUEVO MÉTODO
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento();

        $data = [
            'title' => 'Dashboard',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,  // NUEVO
            'denunciasPorConocimiento' => $denunciasPorConocimiento
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
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);  // NUEVO
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);

        $data = [
            'title' => 'Dashboard',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,  // NUEVO
            'denunciasPorConocimiento' => $denunciasPorConocimiento
        ];

        return view('dashboard/index', $data);
    }
}
