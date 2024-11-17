<?php

namespace App\Controllers;

use App\Models\DashboardClienteModel;

class DashboardClienteController extends BaseController
{
    public function index()
    {
        $model = new DashboardClienteModel();

        $clientes = $model->getClientes();
        $clienteId = session()->get('id_cliente');

        // Obtener el mes y año actuales para el filtro inicial
        $startDate = date('Y-m-01'); // Primer día del mes actual
        $endDate = date('Y-m-t');    // Último día del mes actual

        $sucursales = $model->getSucursales($clienteId);
        $departamentos = $model->getDepartamentos($clienteId);

        // Obtener datos con el filtro de fechas
        $denunciasPorMes = $model->getDenunciasPorMes($startDate, $endDate);
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate);
        $denunciasAnonimas = $model->getDenunciasAnonimas($startDate, $endDate);
        $denunciasPorMedio = $model->getDenunciasPorMedioRecepcion($startDate, $endDate);

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
            'clienteId' => $clienteId,
            'clientes' => $clientes,
            'denunciasPorMes' => $denunciasPorMes,
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto['data'],
            'denunciasPorDeptoSucursales' => $denunciasPorDepto['sucursales'],
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'denunciasAnonimas' => $denunciasAnonimas,
            'denunciasPorMedio' => $denunciasPorMedio,
            'totalEstatus' => $totalEstatus,
            'totalDeptos' => $totalDeptos,
            'totalSucursales' => $totalSucursales,
            'totalConocimiento' => $totalConocimiento,
            'totalDenunciasNuevas' => $totalDenunciasNuevas,
            'totalDenunciasProceso' => $totalDenunciasProceso,
            'totalDenunciasRecibidas' => $totalDenunciasRecibidas,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sucursales' => $sucursales,           // Nuevos datos para el filtro de sucursales
            'departamentos' => $departamentos       // Nuevos datos para el filtro de departamentos
        ];

        return view('dashboard/cliente_index', $data);
    }


    public function filtrar()
    {
        $model = new DashboardClienteModel();
        $cliente = session()->get('id_cliente');


        // Obtener filtros desde la petición POST
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');
        $sucursal = $this->request->getPost('sucursal');
        $departamento = $this->request->getPost('departamento');
        $anonimo = $this->request->getPost('anonimo');

        // Obtener datos aplicando los filtros
        $denunciasPorMes = $model->getDenunciasPorMes($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $estatusDenuncias = $model->getDenunciasPorEstatus($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $denunciasPorDepto = $model->getDenunciasPorDepartamento($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $denunciasPorSucursal = $model->getDenunciasPorSucursal($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $denunciasPorConocimiento = $model->getDenunciasPorConocimiento($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $denunciasAnonimas = $model->getDenunciasAnonimas($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $denunciasPorMedio = $model->getDenunciasPorMedioRecepcion($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        // Contadores de denuncias según los criterios y filtros aplicados
        $totalDenunciasNuevas = $model->countDenunciasNuevas($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $totalDenunciasProceso = $model->countDenunciasEnProceso($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $totalDenunciasRecibidas = $model->countDenunciasRecibidas($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        // Calcular totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto['data'], 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        // Retornar los datos en formato JSON
        return $this->response->setJSON([
            'denunciasPorMes' => $denunciasPorMes,
            'estatusDenuncias' => $estatusDenuncias,
            'denunciasPorDepto' => $denunciasPorDepto['data'],
            'denunciasPorDeptoSucursales' => $denunciasPorDepto['sucursales'], // Columnas de la tabla de departamentos
            'denunciasPorSucursal' => $denunciasPorSucursal,
            'denunciasPorConocimiento' => $denunciasPorConocimiento,
            'denunciasAnonimas' => $denunciasAnonimas,
            'denunciasPorMedio' => $denunciasPorMedio,
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
