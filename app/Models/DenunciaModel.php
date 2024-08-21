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

    protected $useTimestamps    = true; // Activar manejo de timestamps
    protected $createdField     = 'created_at'; // Campo para la fecha de creación
    protected $updatedField     = 'updated_at'; // Campo para la fecha de actualización
    protected $beforeInsert     = ['generateFolio', 'setDefaultValues'];
    protected $beforeUpdate     = ['setUpdateTimestamp'];

    /**
     * Genera un folio único antes de insertar la denuncia.
     *
     * @param array $data
     * @return array
     */
    protected function generateFolio(array $data): array
    {
        $yearMonth = date('Ym');
        $lastDenuncia = $this->select('folio')
            ->like('folio', "DEN-$yearMonth", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $newFolio = $lastDenuncia ? (int)substr($lastDenuncia['folio'], -5) + 1 : 1;
        $data['data']['folio'] = "$yearMonth-" . str_pad($newFolio, 5, '0', STR_PAD_LEFT);

        return $data;
    }

    /**
     * Establece valores predeterminados antes de insertar una denuncia.
     *
     * @param array $data
     * @return array
     */
    protected function setDefaultValues(array $data): array
    {
        $data['data']['fecha_hora_reporte'] = date('Y-m-d H:i:s');
        $data['data']['estado_actual'] = 1; // ID del estado 'Recepción'
        return $data;
    }

    /**
     * Establece la fecha de actualización antes de actualizar una denuncia.
     *
     * @param array $data
     * @return array
     */
    protected function setUpdateTimestamp(array $data): array
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    /**
     * Obtiene una lista de denuncias con sus relaciones.
     *
     * @return array
     */
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

    /**
     * Obtiene una denuncia específica por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getDenunciaById(int $id): ?array
    {
        return $this->select('denuncias.*, 
                              clientes.nombre_empresa AS cliente_nombre, 
                              departamentos.nombre AS departamento_nombre, 
                              estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->where('denuncias.id', $id)
            ->first();
    }

    /**
     * Cambia el estado de una denuncia.
     *
     * @param int $id
     * @param int $estadoNuevo
     * @return bool
     */
    public function cambiarEstado(int $id, int $estadoNuevo): bool
    {
        return $this->update($id, ['estado_actual' => $estadoNuevo]);
    }

    /**
     * Busca denuncias por cliente.
     *
     * @param int $id_cliente
     * @return array
     */
    public function buscarPorCliente(int $id_cliente): array
    {
        return $this->where('id_cliente', $id_cliente)->findAll();
    }

    /**
     * Busca denuncias por estado.
     *
     * @param int $estado
     * @return array
     */
    public function buscarPorEstado(int $estado): array
    {
        return $this->where('estado_actual', $estado)->findAll();
    }
}
