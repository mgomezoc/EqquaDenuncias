<?php

namespace App\Models;

use CodeIgniter\Model;

class AnexoDenunciaModel extends Model
{
    protected $table = 'anexos_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_denuncia', 'nombre_archivo', 'ruta_archivo', 'tipo'];

    public function getAnexosByDenunciaId($id_denuncia)
    {
        return $this->where('id_denuncia', $id_denuncia)->findAll();
    }

    public function getAnexoById($id)
    {
        return $this->find($id);
    }

    public function createAnexo($data)
    {
        return $this->insert($data);
    }

    public function deleteAnexo($id)
    {
        return $this->delete($id);
    }

    public function deleteAnexosByDenunciaId($id_denuncia)
    {
        return $this->where('id_denuncia', $id_denuncia)->delete();
    }
}
