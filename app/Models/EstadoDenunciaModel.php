<?php

namespace App\Models;

use CodeIgniter\Model;

class EstadoDenunciaModel extends Model
{
    protected $table = 'estados_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre'];

    public function getEstados()
    {
        return $this->findAll();
    }

    public function getEstadoById($id)
    {
        return $this->find($id);
    }

    public function createEstado($data)
    {
        return $this->insert($data);
    }

    public function updateEstado($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteEstado($id)
    {
        return $this->delete($id);
    }
}
