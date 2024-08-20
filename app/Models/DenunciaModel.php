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

    protected $beforeInsert = ['generateFolio', 'setDefaultValues'];

    protected function generateFolio(array $data)
    {
        $yearMonth = date('Ym');
        $lastDenuncia = $this->select('folio')
            ->like('folio', "DEN-$yearMonth", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastDenuncia) {
            $lastFolio = substr($lastDenuncia['folio'], -5);
            $newFolio = (int)$lastFolio + 1;
        } else {
            $newFolio = 1;
        }

        $data['data']['folio'] = "DEN-$yearMonth-" . str_pad($newFolio, 5, '0', STR_PAD_LEFT);
        return $data;
    }

    protected function setDefaultValues(array $data)
    {
        $data['data']['fecha_hora_reporte'] = date('Y-m-d H:i:s');
        $data['data']['estado_actual'] = 1;  // ID del estado 'Recepción'
        return $data;
    }

    public function getDenuncias()
    {
        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
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

    public function buscarPorCliente($id_cliente)
    {
        return $this->where('id_cliente', $id_cliente)->findAll();
    }

    public function buscarPorEstado($estado)
    {
        return $this->where('estado_actual', $estado)->findAll();
    }
}
