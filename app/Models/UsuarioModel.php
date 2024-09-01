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

    /**
     * Obtiene un usuario junto con su rol.
     *
     * @param string $email El correo electrónico del usuario.
     * @return array|null Los datos del usuario junto con el rol, o null si no se encuentra.
     */
    public function getUserWithRole($email)
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, roles.slug as rol_slug, clientes.id as id_cliente, clientes.nombre_empresa as nombre_cliente')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->join('clientes', 'clientes.id = usuarios.id_cliente', 'left')
            ->where('usuarios.correo_electronico', $email)
            ->first();
    }


    /**
     * Actualiza la última conexión de un usuario.
     *
     * @param int $id El ID del usuario.
     * @return bool Si la actualización fue exitosa o no.
     */
    public function updateLastLogin($id)
    {
        return $this->update($id, ['ultima_conexion' => date('Y-m-d H:i:s')]);
    }
}
