<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioPermisoDenuncianteModel extends Model
{
    protected $table      = 'usuarios_permisos_denunciante';
    protected $primaryKey = 'id';

    protected $allowedFields = ['id_usuario', 'tipo_denunciante', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getTiposByUsuario(int $idUsuario): array
    {
        return array_map(
            fn($r) => $r['tipo_denunciante'],
            $this->where('id_usuario', $idUsuario)->findAll()
        );
    }
}
