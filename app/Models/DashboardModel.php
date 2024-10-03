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
        } else {
            // Filtrar por el mes actual
            $builder->where('MONTH(denuncias.created_at)', date('m'))
                ->where('YEAR(denuncias.created_at)', date('Y'));
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
        } else {
            $builder->where('MONTH(denuncias.created_at)', date('m'))
                ->where('YEAR(denuncias.created_at)', date('Y'));
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por si son anónimas o no.
     */
    public function getDenunciasAnonimas($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select("IF(denuncias.id_creador IS NULL OR denuncias.anonimo = 1, 'Si', 'No') as anonimo, COUNT(denuncias.id) as total")
            ->groupBy('anonimo');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        } else {
            $builder->where('MONTH(denuncias.created_at)', date('m'))
                ->where('YEAR(denuncias.created_at)', date('Y'));
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Obtiene el total de denuncias agrupadas por cómo se enteró el denunciante del incidente.
     */
    public function getDenunciasPorConocimiento($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('denuncias.como_se_entero, COUNT(denuncias.id) as total')
            ->groupBy('denuncias.como_se_entero');

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        } else {
            $builder->where('MONTH(denuncias.created_at)', date('m'))
                ->where('YEAR(denuncias.created_at)', date('Y'));
        }

        return $builder->get()->getResultArray();
    }

    public function getDenunciasPorSucursal($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('denuncias')
            ->select('sucursales.nombre, COUNT(denuncias.id) as total')
            ->join('sucursales', 'denuncias.id_sucursal = sucursales.id')
            ->groupBy('sucursales.nombre')
            ->orderBy('total', 'DESC');  // Ordena por la cantidad de denuncias

        if ($startDate && $endDate) {
            $builder->where('denuncias.created_at >=', $startDate)
                ->where('denuncias.created_at <=', $endDate);
        } else {
            $builder->where('MONTH(denuncias.created_at)', date('m'))
                ->where('YEAR(denuncias.created_at)', date('Y'));
        }

        return $builder->get()->getResultArray();
    }
}
