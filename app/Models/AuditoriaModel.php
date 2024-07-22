<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaModel extends Model
{
    protected $table = 'auditoria';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_usuario', 'accion', 'detalle', 'fecha'];

    protected $useTimestamps = true;
    protected $createdField  = 'fecha';
    protected $updatedField  = '';
}
