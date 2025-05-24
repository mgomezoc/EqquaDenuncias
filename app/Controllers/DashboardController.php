<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();

        $clientes = $model->getClientes();

        // Obtener el mes y año actuales
        $startDateFormatted = date('d/m/Y', strtotime(date('Y-m-01'))); // DD/MM/YYYY
        $endDateFormatted = date('d/m/Y', strtotime(date('Y-m-t')));    // DD/MM/YYYY

        // Convertir fechas al formato que requiere el modelo
        $startDate = date('Y-m-d', strtotime(date('Y-m-01'))); // YYYY-MM-DD
        $endDate = date('Y-m-d', strtotime(date('Y-m-t')));    // YYYY-MM-DD

        // Obtener listas de sucursales y departamentos para los filtros
        $sucursales = $model->getSucursales(); // Método para obtener todas las sucursales
        $departamentos = $model->getDepartamentos(); // Método para obtener todos los departamentos

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
        $totalDenunciasCerradas = $model->countDenunciasCerradas($startDate, $endDate);
        $totalDenunciasTotales = $model->countDenunciasTotales($startDate, $endDate);


        // Calcular totales
        $totalEstatus = array_sum(array_column($estatusDenuncias, 'total'));
        $totalDeptos = array_sum(array_column($denunciasPorDepto, 'total'));
        $totalSucursales = array_sum(array_column($denunciasPorSucursal, 'total'));
        $totalConocimiento = array_sum(array_column($denunciasPorConocimiento, 'total'));

        // Año actual para la gráfica de mes de recepción
        $currentYear = date('Y');
        $denunciasPorMes = $model->getDenunciasPorMesAnio($currentYear);


        $data = [
            'title' => 'Dashboard',
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
            'totalDenunciasCerradas' => $totalDenunciasCerradas,
            'totalDenunciasTotales' => $totalDenunciasTotales,
            'startDate' => $startDateFormatted,
            'endDate' => $endDateFormatted,
            'denunciasPorMes' => $denunciasPorMes, // Datos iniciales de la gráfica
            'currentYear' => $currentYear, // Enviar año actual a la vista
            'sucursales' => $sucursales,           // Nuevos datos para el filtro de sucursales
            'departamentos' => $departamentos       // Nuevos datos para el filtro de departamentos
        ];

        return view('dashboard/index', $data);
    }

    /**
     * Obtiene las denuncias agrupadas por mes para un año específico.
     */
    public function getDenunciasPorAnio()
    {
        $model = new DashboardModel();

        $year = $this->request->getPost('year');

        if (!$year || !is_numeric($year)) {
            return $this->response->setJSON(['error' => 'Año inválido']);
        }

        $filters = [
            'cliente'      => $this->request->getPost('cliente'),
            'sucursal'     => $this->request->getPost('sucursal'),
            'departamento' => $this->request->getPost('departamento'),
            'anonimo'      => $this->request->getPost('anonimo')
        ];

        $denunciasPorMes = $model->getDenunciasPorMesAnio($year, $filters);

        return $this->response->setJSON(['denunciasPorMes' => $denunciasPorMes]);
    }



    public function filtrar()
    {
        $model = new DashboardModel();

        // Obtener filtros desde la petición POST
        $startDateFormatted = $this->request->getPost('start_date');
        $endDateFormatted = $this->request->getPost('end_date');
        $startDate = $startDateFormatted
            ? date('Y-m-d', strtotime(str_replace('/', '-', $startDateFormatted)))
            : null;
        $endDate = $endDateFormatted
            ? date('Y-m-d', strtotime(str_replace('/', '-', $endDateFormatted)))
            : null;
        $sucursal = $this->request->getPost('sucursal');
        $departamento = $this->request->getPost('departamento');
        $anonimo = $this->request->getPost('anonimo');
        $cliente = $this->request->getPost('cliente');

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
        $totalDenunciasCerradas = $model->countDenunciasCerradas($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);
        $totalDenunciasTotales = $model->countDenunciasTotales($startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);


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
            'totalDenunciasCerradas' => $totalDenunciasCerradas,
            'totalDenunciasTotales' => $totalDenunciasTotales
        ]);
    }

    public function getSubcategoriasPorCategoria()
    {
        $categoriaId = $this->request->getPost('categoria_id');

        // Convertir fechas del formato DD/MM/YYYY a YYYY-MM-DD
        $startDate = $this->convertirFecha($this->request->getPost('start_date'));
        $endDate = $this->convertirFecha($this->request->getPost('end_date'));

        $filters = [
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'cliente'      => $this->request->getPost('cliente'),
            'sucursal'     => $this->request->getPost('sucursal'),
            'departamento' => $this->request->getPost('departamento'),
            'anonimo'      => $this->request->getPost('anonimo'),
        ];

        $model = new \App\Models\DenunciaModel();
        $subcategorias = $model->getSubcategoriasPorCategoria($categoriaId, $filters);

        return $this->response->setJSON(['data' => $subcategorias]);
    }

    // 👇 Función auxiliar para convertir formato de fecha
    private function convertirFecha($fecha)
    {
        if (!$fecha) return null;
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return "{$partes[2]}-{$partes[1]}-{$partes[0]}";
        }
        return $fecha;
    }


    public function getResumenCategoriasConFiltros()
    {
        $model = new \App\Models\DenunciaModel();

        $startDate = $this->convertirFecha($this->request->getPost('start_date'));
        $endDate   = $this->convertirFecha($this->request->getPost('end_date'));

        $filters = [
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'cliente'      => $this->request->getPost('cliente'),
            'sucursal'     => $this->request->getPost('sucursal'),
            'departamento' => $this->request->getPost('departamento'),
            'anonimo'      => $this->request->getPost('anonimo')
        ];

        $resumen = $model->getResumenCategoriasConFiltros($filters);

        return $this->response->setJSON($resumen);
    }
}
