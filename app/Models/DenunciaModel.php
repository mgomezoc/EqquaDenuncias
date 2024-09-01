<?php

namespace App\Models;

use CodeIgniter\Model;

class DenunciaModel extends Model
{
    protected $table            = 'denuncias';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'id_cliente',
        'folio',
        'fecha_hora_reporte',
        'id_sucursal',
        'tipo_denunciante',
        'categoria',
        'subcategoria',
        'id_departamento',
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

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $beforeInsert     = ['generateFolio', 'setDefaultValues'];
    protected $beforeUpdate     = ['setUpdateTimestamp'];

    protected function generateFolio(array $data): array
    {
        $date = date('Ymd'); // Añadir día a la fecha para asegurar folios únicos por día
        $lastDenuncia = $this->select('folio')
            ->like('folio', "$date-", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $newFolio = $lastDenuncia ? (int)substr($lastDenuncia['folio'], -5) + 1 : 1;
        $data['data']['folio'] = "$date-" . str_pad($newFolio, 5, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function setDefaultValues(array $data): array
    {
        $data['data']['fecha_hora_reporte'] = date('Y-m-d H:i:s');
        $data['data']['estado_actual'] = 1; // Estado inicial 'Recepción'
        return $data;
    }

    protected function setUpdateTimestamp(array $data): array
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function getDenuncias(): array
    {
        return $this->select('denuncias.*, 
                              clientes.nombre_empresa AS cliente_nombre, 
                              sucursales.nombre AS sucursal_nombre, 
                              categorias_denuncias.nombre AS categoria_nombre, 
                              subcategorias_denuncias.nombre AS subcategoria_nombre, 
                              departamentos.nombre AS departamento_nombre, 
                              estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function getDenunciaById(int $id): ?array
    {
        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre,
                          sucursales.nombre AS sucursal_nombre,
                          categorias_denuncias.nombre AS categoria_nombre,
                          subcategorias_denuncias.nombre AS subcategoria_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->where('denuncias.id', $id)
            ->first();
    }


    public function cambiarEstado(int $id, int $estadoNuevo): bool
    {
        return $this->update($id, ['estado_actual' => $estadoNuevo]);
    }

    public function getDenunciasByCliente($clienteId)
    {
        return $this->select('denuncias.*, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->where('denuncias.id_cliente', $clienteId)
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }
}
