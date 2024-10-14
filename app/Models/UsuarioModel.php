<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'nombre_usuario',
        'correo_electronico',
        'contrasena',
        'password_reset_token',
        'token_expiry',
        'rol_id',
        'ultima_conexion',
        'id_cliente',
        'activo'
    ];
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash la contraseña antes de insertar o actualizar el usuario.
     *
     * @param array $data Los datos a insertar/actualizar.
     * @return array Los datos modificados.
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['contrasena'])) {
            $data['data']['contrasena'] = password_hash($data['data']['contrasena'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Obtiene un usuario junto con su rol, solo si está activo.
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
            ->where('usuarios.activo', 1) // Solo usuarios activos
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

    /**
     * Activa o desactiva un usuario.
     *
     * @param int $id El ID del usuario.
     * @param bool $estado El estado a establecer (true para activar, false para desactivar).
     * @return bool Si la actualización fue exitosa o no.
     */
    public function setActivo($id, bool $estado)
    {
        return $this->update($id, ['activo' => $estado ? 1 : 0]);
    }

    /**
     * Obtiene todos los usuarios de un cliente específico que estén activos.
     *
     * @param int $clienteId El ID del cliente.
     * @return array Lista de usuarios activos del cliente.
     */
    public function getUsuariosActivosPorCliente($clienteId)
    {
        return $this->select('usuarios.id, usuarios.nombre_usuario, usuarios.correo_electronico, usuarios.ultima_conexion, roles.nombre AS rol_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('usuarios.id_cliente', $clienteId)
            ->where('usuarios.activo', 1) // Solo usuarios activos
            ->findAll();
    }
}
