<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaDenunciaModel extends Model
{
    protected $table = 'categorias_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre'];

    public function getCategorias()
    {
        return $this->findAll();
    }

    public function getCategoriaById($id)
    {
        return $this->find($id);
    }

    public function createCategoria($data)
    {
        return $this->insert($data);
    }

    public function updateCategoria($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteCategoria($id)
    {
        return $this->delete($id);
    }
}
