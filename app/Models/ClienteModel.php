<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'nombre_empresa',
        'numero_identificacion',
        'correo_contacto',
        'telefono_contacto',
        'direccion',
        'slug',
        'logo',
        'banner',
        'saludo',
        'whatsapp',
        'primary_color',
        'secondary_color',
        'link_color',
        'politica_anonimato',
        'created_at'
    ];

    public function getClientes()
    {
        return $this->findAll();
    }

    public function getClienteById($id)
    {
        return $this->find($id);
    }

    public function createCliente($data)
    {
        return $this->insert($data);
    }

    public function updateCliente($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteCliente($id)
    {
        return $this->delete($id);
    }

    public function isUnique($field, $value, $id = null)
    {
        $this->where($field, $value);
        if ($id) {
            $this->where('id !=', $id);
        }
        return $this->countAllResults() === 0;
    }

    public function getClientesByAgente($agenteId)
    {
        return $this->select('clientes.*')
            ->join('relacion_clientes_usuarios', 'clientes.id = relacion_clientes_usuarios.id_cliente')
            ->where('relacion_clientes_usuarios.id_usuario', $agenteId)
            ->findAll();
    }
}
