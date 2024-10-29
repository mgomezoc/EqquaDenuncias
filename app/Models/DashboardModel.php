<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $table = 'denuncias';

    /**
     * Obtiene el total de denuncias agrupadas por estatus.
     */
    public function getDenunciasPorEstatus($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('estados_denuncias.nombre as estatus, COUNT(denuncias.id) as total')
            ->join('estados_denuncias', 'denuncias.estado_actual = estados_denuncias.id', 'left')
            ->groupBy('estados_denuncias.nombre');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por departamento.
     */
    public function getDenunciasPorDepartamento($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('IFNULL(departamentos.nombre, "Sin departamento") as departamento, COUNT(denuncias.id) as total')
            ->join('departamentos', 'denuncias.id_departamento = departamentos.id', 'left')
            ->groupBy('departamentos.nombre');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por sucursal.
     */
    public function getDenunciasPorSucursal($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('sucursales.nombre as nombre, COUNT(denuncias.id) as total')
            ->join('sucursales', 'denuncias.id_sucursal = sucursales.id', 'left')
            ->groupBy('sucursales.nombre');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por conocimiento del incidente.
     */
    public function getDenunciasPorConocimiento($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('denuncias.como_se_entero as como_se_entero, COUNT(denuncias.id) as total')
            ->groupBy('denuncias.como_se_entero');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Cuenta denuncias por estado específico (ej. 'Nuevo', 'En Proceso').
     */
    public function countDenunciasPorEstado($estadoNombre)
    {
        $builder = $this->db->table('denuncias')
            ->select('COUNT(denuncias.id) as total')
            ->join('estados_denuncias', 'denuncias.estado_actual = estados_denuncias.id', 'left')
            ->where('estados_denuncias.nombre', $estadoNombre);

        return $builder->get()->getRow()->total;
    }

    /**
     * Cuenta denuncias con el estado "Nuevo" (id_estado = 1).
     */
    public function countDenunciasNuevas($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->where('estado_actual', 1);

        if ($startDate && $endDate) {
            $builder->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate);
        }

        return $builder->countAllResults();
    }

    /**
     * Cuenta denuncias con estados "En Proceso" (id_estado = 2, 3, 4, o 5).
     */
    public function countDenunciasEnProceso($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->whereIn('estado_actual', [2, 3, 4, 5]);

        if ($startDate && $endDate) {
            $builder->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate);
        }

        return $builder->countAllResults();
    }

    /**
     * Cuenta todas las denuncias recibidas.
     */
    public function countDenunciasRecibidas($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias');

        if ($startDate && $endDate) {
            $builder->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate);
        }

        return $builder->countAllResults();
    }

    /**
     * Obtiene la cantidad de denuncias agrupadas por mes en un periodo específico.
     */
    public function getDenunciasPorMes($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select("MONTH(fecha_hora_reporte) as mes, COUNT(id) as total")
            ->groupBy("MONTH(fecha_hora_reporte)")
            ->orderBy("mes", "ASC");

        if ($startDate && $endDate) {
            $builder->where('fecha_hora_reporte >=', $startDate)
                ->where('fecha_hora_reporte <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    public function getDenunciasAnonimas($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('IF(anonimo = 1, "Sí", "No") as anonimato, COUNT(id) as total')
            ->groupBy('anonimo');

        // Filtro de fechas si están presentes
        if ($startDate && $endDate) {
            $builder->where('created_at >=', $startDate)
                ->where('created_at <=', $endDate);
        }

        return $builder->get()->getResultArray();
    }

    public function getDenunciasPorMedioRecepcion($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('medio_recepcion, COUNT(id) as total')
            ->groupBy('medio_recepcion');

        if ($startDate && $endDate) {
            $builder->where('fecha_hora_reporte >=', $startDate)
                ->where('fecha_hora_reporte <=', $endDate);
        } else {
            // Filtrar por mes actual si no hay fechas especificadas
            $builder->where('MONTH(fecha_hora_reporte)', date('m'))
                ->where('YEAR(fecha_hora_reporte)', date('Y'));
        }

        return $builder->get()->getResultArray();
    }
}
