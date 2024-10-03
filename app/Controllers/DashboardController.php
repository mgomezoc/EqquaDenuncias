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
        //$denunciasAnonimas = $model->getDenunciasAnonimas();
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento();

        $data = [
            'title' => '',
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            //'denunciasAnonimas' => $denunciasAnonimas,
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
        $denunciasAnonimas = $model->getDenunciasAnonimas($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);

        $data = [
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto,
            'denunciasAnonimas' => $denunciasAnonimas,
            'denunciasPorConocimiento' => $denunciasPorConocimiento
        ];

        return view('dashboard/index', $data);
    }
}
