<?php

namespace App\Models;

use CodeIgniter\Model;

class SeguimientoDenunciaModel extends Model
{
    protected $table = 'seguimiento_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id_denuncia',
        'estado_anterior',
        'estado_nuevo',
        'fecha',
        'comentario',
        'id_usuario'
    ];

    public function getSeguimientoByDenunciaId($id_denuncia)
    {
        return $this->select('seguimiento_denuncias.*, estados_denuncias.nombre AS estado_anterior_nombre, estados_denuncias2.nombre AS estado_nuevo_nombre, usuarios.nombre_usuario AS usuario_nombre')
            ->join('estados_denuncias', 'estados_denuncias.id = seguimiento_denuncias.estado_anterior', 'left')
            ->join('estados_denuncias AS estados_denuncias2', 'estados_denuncias2.id = seguimiento_denuncias.estado_nuevo', 'left')
            ->join('usuarios', 'usuarios.id = seguimiento_denuncias.id_usuario', 'left')
            ->where('seguimiento_denuncias.id_denuncia', $id_denuncia)
            ->orderBy('seguimiento_denuncias.fecha', 'ASC')
            ->findAll();
    }

    public function getSeguimientoById($id)
    {
        return $this->select('seguimiento_denuncias.*, estados_denuncias.nombre AS estado_anterior_nombre, estados_denuncias2.nombre AS estado_nuevo_nombre, usuarios.nombre_usuario AS usuario_nombre')
            ->join('estados_denuncias', 'estados_denuncias.id = seguimiento_denuncias.estado_anterior', 'left')
            ->join('estados_denuncias AS estados_denuncias2', 'estados_denuncias2.id = seguimiento_denuncias.estado_nuevo', 'left')
            ->join('usuarios', 'usuarios.id = seguimiento_denuncias.id_usuario', 'left')
            ->where('seguimiento_denuncias.id', $id)
            ->first();
    }

    public function createSeguimiento($data)
    {
        return $this->insert($data);
    }

    public function deleteSeguimientoByDenunciaId($id_denuncia)
    {
        return $this->where('id_denuncia', $id_denuncia)->delete();
    }
}
