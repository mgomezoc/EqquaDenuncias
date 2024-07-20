<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre_usuario', 'correo_electronico', 'contrasena', 'rol_id', 'ultima_conexion', 'id_cliente'];
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['contrasena'])) {
            $data['data']['contrasena'] = password_hash($data['data']['contrasena'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function getUserWithRole($email)
    {
        return $this->select('usuarios.*, roles.nombre AS rol_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('correo_electronico', $email)
            ->first();
    }

    public function updateLastLogin($id)
    {
        return $this->update($id, ['ultima_conexion' => date('Y-m-d H:i:s')]);
    }
}
