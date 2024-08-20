<?php

namespace App\Models;

use CodeIgniter\Model;

class DenunciaModel extends Model
{
    protected $table = 'denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id_cliente',
        'folio',
        'fecha_hora_reporte',
        'id_sucursal',
        'tipo_denunciante',
        'categoria',
        'subcategoria',
        'departamento',
        'anonimo',
        'fecha_incidente',
        'como_se_entero',
        'denunciar_a_alguien',
        'area_incidente',
        'descripcion',
        'estado_actual',
        'id_creador',
        'visible_para_agente',
        'visible_para_calidad',
        'visible_para_cliente'
    ];

    public function getDenuncias()
    {
        return $this->select('denuncias.*, clientes.nombre_empresa AS cliente_nombre, estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->findAll();
    }

    public function getDenunciaById($id)
    {
        return $this->select('denuncias.*, clientes.nombre_empresa AS cliente_nombre, estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->where('denuncias.id', $id)
            ->first();
    }

    public function createDenuncia($data)
    {
        return $this->insert($data);
    }

    public function updateDenuncia($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteDenuncia($id)
    {
        return $this->delete($id);
    }

    public function cambiarEstado($id, $estadoNuevo)
    {
        return $this->update($id, ['estado_actual' => $estadoNuevo]);
    }
}
