<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();

        // Obtener datos de las denuncias por mes
        $denunciasPorMes = $model->getDenunciasPorMes();

        // Otros datos
        $estatusDenuncias = $model->getDenunciasPorEstatus();
        $denunciasPorDepto = $model->getDenunciasPorDepartamento();
        $denunciasPorSucursal = $model->getDenunciasPorSucursal();
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento();

        // Obtener contadores para las categorías solicitadas
        $totalDenunciasNuevas = $model->countDenunciasNuevas();
        $totalDenunciasProceso = $model->countDenunciasEnProceso();
        $totalDenunciasRecibidas = $model->countDenunciasRecibidas();

        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));


        $data = [
            'title' => 'Dashboard',
            'denunciasPorMes' => $denunciasPorMes,
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'totalDenunciasNuevas' => $totalDenunciasNuevas,
            'totalDenunciasProceso' => $totalDenunciasProceso,
            'totalDenunciasRecibidas' => $totalDenunciasRecibidas,
        ];

        return view('dashboard/index', $data);
    }

    public function filtrar()
    {
        $model = new DashboardModel();

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        $denunciasPorMes = $model->getDenunciasPorMes($startDate, $endDate);
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);

        // Calcular totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        return $this->response->setJSON([
            'denunciasPorMes' => $denunciasPorMes,
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
        ]);
    }
}
