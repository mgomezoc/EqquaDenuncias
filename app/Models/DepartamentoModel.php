<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartamentoModel extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'id_sucursal', 'es_general'];


    public function getDepartamentosWithDetails()
    {
        return $this->select('departamentos.*, sucursales.nombre AS sucursal_nombre, sucursales.id_cliente AS id_cliente, clientes.nombre_empresa AS cliente_nombre')
            ->join('sucursales', 'sucursales.id = departamentos.id_sucursal', 'left') // Cambiar a LEFT JOIN
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')       // Cambiar a LEFT JOIN
            ->where('departamentos.es_general', 1)                                  // Incluir los generales
            ->orWhere('departamentos.id_sucursal IS NOT NULL')                      // Incluir los que tienen sucursal
            ->orderBy('departamentos.id', 'DESC')                                   // Ordenar del más reciente al más antiguo
            ->findAll();
    }
}
