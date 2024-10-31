<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $table = 'denuncias';

    public function getSucursales()
    {
        return $this->db->table('sucursales')
            ->select('id, nombre')
            ->get()
            ->getResultArray();
    }

    public function getDepartamentos()
    {
        return $this->db->table('departamentos')
            ->select('id, nombre')
            ->get()
            ->getResultArray();
    }

    public function getClientes()
    {
        return $this->db->table('clientes')
            ->select('id, nombre_empresa')
            ->get()
            ->getResultArray();
    }


    /**
     * Obtiene el total de denuncias agrupadas por estatus.
     */
    public function getDenunciasPorEstatus($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('estados_denuncias.nombre as estatus, COUNT(denuncias.id) as total')
            ->join('estados_denuncias', 'denuncias.estado_actual = estados_denuncias.id', 'left')
            ->groupBy('estados_denuncias.nombre');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por departamento y sucursal.
     */
    public function getDenunciasPorDepartamento($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('departamentos.nombre AS departamento, sucursales.nombre AS sucursal, COUNT(denuncias.id) AS total')
            ->join('departamentos', 'denuncias.id_departamento = departamentos.id', 'inner')
            ->join('sucursales', 'denuncias.id_sucursal = sucursales.id', 'inner')
            ->groupBy(['departamentos.nombre', 'sucursales.nombre'])
            ->orderBy('departamentos.nombre', 'ASC')
            ->orderBy('sucursales.nombre', 'ASC');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        $result = $builder->get()->getResultArray();

        // Reestructurar datos para el formato de tabla
        $data = [];
        $sucursales = [];

        foreach ($result as $row) {
            $departamento = $row['departamento'];
            $sucursal = $row['sucursal'];
            $total = $row['total'];

            if (!isset($data[$departamento])) {
                $data[$departamento] = array_fill_keys(array_column($result, 'sucursal'), 0);
                $data[$departamento]['Total'] = 0;
            }

            $data[$departamento][$sucursal] = $total;
            $data[$departamento]['Total'] += $total;

            if (!in_array($sucursal, $sucursales)) {
                $sucursales[] = $sucursal;
            }
        }

        $totales = array_fill_keys($sucursales, 0);
        $totales['Total'] = 0;

        foreach ($data as $deptData) {
            foreach ($sucursales as $sucursal) {
                $totales[$sucursal] += $deptData[$sucursal];
            }
            $totales['Total'] += $deptData['Total'];
        }

        $data['Total'] = $totales;

        return [
            'data' => $data,
            'sucursales' => $sucursales
        ];
    }

    /**
     * Obtiene el total de denuncias agrupadas por sucursal.
     */
    public function getDenunciasPorSucursal($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('sucursales.nombre as nombre, COUNT(denuncias.id) as total')
            ->join('sucursales', 'denuncias.id_sucursal = sucursales.id', 'left')
            ->groupBy('sucursales.nombre');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por conocimiento del incidente.
     */
    public function getDenunciasPorConocimiento($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('denuncias.como_se_entero as como_se_entero, COUNT(denuncias.id) as total')
            ->groupBy('denuncias.como_se_entero');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->get()->getResultArray();
    }

    /**
     * Cuenta denuncias con el estado "Nuevo" (id_estado = 1).
     */
    public function countDenunciasNuevas($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->where('estado_actual', 1);

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->countAllResults();
    }

    /**
     * Cuenta denuncias con estados "En Proceso" (id_estado = 2, 3, 4, o 5).
     */
    public function countDenunciasEnProceso($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->whereIn('estado_actual', [2, 3, 4, 5]);

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->countAllResults();
    }

    /**
     * Cuenta todas las denuncias recibidas.
     */
    public function countDenunciasRecibidas($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->countAllResults();
    }

    /**
     * Obtiene la cantidad de denuncias agrupadas por mes en un periodo específico.
     */
    public function getDenunciasPorMes($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        //exit("DEBUG");
        $builder = $this->db->table('denuncias')
            ->select("MONTH(created_at) as mes, COUNT(id) as total")
            ->groupBy("MONTH(created_at)")
            ->orderBy("mes", "ASC");

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        // Ejecutar la consulta y obtener los resultados
        $result = $builder->get()->getResultArray();

        // Crear un arreglo de 12 posiciones con valor 0 para cada mes (1 a 12)
        $data = array_fill(1, 12, 0);

        // Llenar el arreglo con los valores obtenidos de la consulta
        foreach ($result as $row) {
            $data[(int)$row['mes']] = (int)$row['total'];
        }

        // Transformar el arreglo en el formato adecuado para la gráfica
        $formattedData = [];
        foreach ($data as $mes => $total) {
            $formattedData[] = ['mes' => $mes, 'total' => $total];
        }

        return $formattedData;
    }


    /**
     * Filtra denuncias anónimas
     */
    public function getDenunciasAnonimas($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('IF(anonimo = 1, "Sí", "No") as anonimato, COUNT(id) as total')
            ->groupBy('anonimo');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->get()->getResultArray();
    }

    /**
     * Filtra denuncias por medio de recepción
     */
    public function getDenunciasPorMedioRecepcion($startDate = null, $endDate = null, $sucursal = null, $departamento = null, $anonimo = null, $cliente = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('medio_recepcion, COUNT(id) as total')
            ->groupBy('medio_recepcion');

        $this->applyFilters($builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente);

        return $builder->get()->getResultArray();
    }

    /**
     * Helper para aplicar filtros de fecha, sucursal, departamento y anonimato.
     */
    private function applyFilters(&$builder, $startDate, $endDate, $sucursal, $departamento, $anonimo, $cliente = null)
    {
        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        }
        if ($sucursal) {
            $builder->where('denuncias.id_sucursal', $sucursal);
        }
        if ($departamento) {
            $builder->where('denuncias.id_departamento', $departamento);
        }
        if ($anonimo) {
            $builder->where('denuncias.anonimo', $anonimo);
        }
        if ($cliente) {
            $builder->where('denuncias.id_cliente', $cliente);
        }
    }
}
