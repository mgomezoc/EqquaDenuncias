<?php

namespace App\Models;

use CodeIgniter\Model;

class RelacionClientesUsuariosModel extends Model
{
    protected $table = 'relacion_clientes_usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_usuario', 'id_cliente'];
}
