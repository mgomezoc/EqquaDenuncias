<?php

namespace App\Models;

use CodeIgniter\Model;

class SubcategoriaDenunciaModel extends Model
{
    protected $table = 'subcategorias_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'id_categoria'];

    public function getSubcategorias()
    {
        return $this->select('subcategorias_denuncias.*, categorias_denuncias.nombre AS categoria_nombre')
            ->join('categorias_denuncias', 'categorias_denuncias.id = subcategorias_denuncias.id_categoria')
            ->findAll();
    }

    public function getSubcategoriaById($id)
    {
        return $this->select('subcategorias_denuncias.*, categorias_denuncias.nombre AS categoria_nombre')
            ->join('categorias_denuncias', 'categorias_denuncias.id = subcategorias_denuncias.id_categoria')
            ->where('subcategorias_denuncias.id', $id)
            ->first();
    }

    public function createSubcategoria($data)
    {
        return $this->insert($data);
    }

    public function updateSubcategoria($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteSubcategoria($id)
    {
        return $this->delete($id);
    }
}
