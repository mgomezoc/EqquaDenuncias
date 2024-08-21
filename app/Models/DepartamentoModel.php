<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartamentoModel extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'id_sucursal'];

    public function getDepartamentosWithDetails()
    {
        return $this->select('departamentos.*, sucursales.nombre AS sucursal_nombre, clientes.nombre_empresa AS cliente_nombre')
            ->join('sucursales', 'sucursales.id = departamentos.id_sucursal')
            ->join('clientes', 'clientes.id = sucursales.id_cliente')
            ->orderBy('departamentos.id', 'DESC') // Ordenar del más reciente al más antiguo
            ->findAll();
    }
}
