<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartamentoModel extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'id_cliente', 'id_sucursal', 'es_general', 'created_at'];

    public function getDepartamentosWithDetails()
    {
        return $this->select('departamentos.*, 
                          sucursales.nombre AS sucursal_nombre, 
                          clientes.nombre_empresa AS cliente_nombre')
            ->join('sucursales', 'sucursales.id = departamentos.id_sucursal', 'left')  // Unir sucursales, si existe
            ->join('clientes', 'clientes.id = departamentos.id_cliente', 'left')       // Unir clientes directamente en departamentos
            ->orderBy('departamentos.created_at', 'DESC')                              // Ordenar por fecha de creación
            ->findAll();
    }
}
