<?php

namespace App\Models;

use CodeIgniter\Model;

class SucursalModel extends Model
{
    protected $table = 'sucursales';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_cliente', 'nombre', 'direccion'];

    public function getSucursales()
    {
        return $this->select('sucursales.*, clientes.nombre_empresa AS cliente_nombre')
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')
            ->findAll();
    }

    public function getSucursalById($id)
    {
        return $this->select('sucursales.*, clientes.nombre_empresa AS cliente_nombre')
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')
            ->where('sucursales.id', $id)
            ->first();
    }

    public function createSucursal($data)
    {
        return $this->insert($data);
    }

    public function updateSucursal($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteSucursal($id)
    {
        return $this->delete($id);
    }
}
