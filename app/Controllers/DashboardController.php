<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();

        // Obtener el mes y año actuales para el filtro inicial
        $startDate = date('Y-m-01'); // Primer día del mes actual
        $endDate = date('Y-m-t');    // Último día del mes actual

        // Obtener datos con el filtro de fechas
        $denunciasPorMes = $model->getDenunciasPorMes($startDate, $endDate);
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);
        $denunciasAnonimas = $model->getDenunciasAnonimas($startDate, $endDate);

        // Contadores de denuncias según los criterios
        $totalDenunciasNuevas = $model->countDenunciasNuevas($startDate, $endDate);
        $totalDenunciasProceso = $model->countDenunciasEnProceso($startDate, $endDate);
        $totalDenunciasRecibidas = $model->countDenunciasRecibidas($startDate, $endDate);

        // Calcular totales
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
            'denunciasAnonimas' => $denunciasAnonimas,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'totalDenunciasNuevas' => $totalDenunciasNuevas,
            'totalDenunciasProceso' => $totalDenunciasProceso,
            'totalDenunciasRecibidas' => $totalDenunciasRecibidas,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];

        return view('dashboard/index', $data);
    }

    public function filtrar()
    {
        $model = new DashboardModel();

        // Obtener las fechas desde la petición POST
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        // Obtener datos con el filtro de fechas
        $denunciasPorMes = $model->getDenunciasPorMes($startDate, $endDate);
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);
        $denunciasAnonimas = $model->getDenunciasAnonimas($startDate, $endDate);

        // Contadores de denuncias según los criterios
        $totalDenunciasNuevas = $model->countDenunciasNuevas($startDate, $endDate);
        $totalDenunciasProceso = $model->countDenunciasEnProceso($startDate, $endDate);
        $totalDenunciasRecibidas = $model->countDenunciasRecibidas($startDate, $endDate);

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
            'denunciasAnonimas' => $denunciasAnonimas,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'totalDenunciasNuevas' => $totalDenunciasNuevas,
            'totalDenunciasProceso' => $totalDenunciasProceso,
            'totalDenunciasRecibidas' => $totalDenunciasRecibidas,
        ]);
    }
}
