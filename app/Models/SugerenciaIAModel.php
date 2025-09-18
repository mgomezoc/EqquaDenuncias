<?php

namespace App\Models;

use CodeIgniter\Model;

class SugerenciaIAModel extends Model
{
    protected $table = 'sugerencias_ia_denuncias';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'id_denuncia',
        'sugerencia_generada',
        'tokens_utilizados',
        'costo_estimado',
        'modelo_ia_usado',
        'tiempo_generacion',
        'estado_sugerencia',
        'evaluacion_usuario',
        'comentarios_usuario'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'id_denuncia' => 'required|integer',
        'sugerencia_generada' => 'required|min_length[10]',
        'modelo_ia_usado' => 'required|max_length[50]',
        'estado_sugerencia' => 'required|in_list[generada,vista,evaluada]'
    ];

    protected $validationMessages = [
        'id_denuncia' => [
            'required' => 'El ID de la denuncia es obligatorio',
            'integer' => 'El ID de la denuncia debe ser un número entero'
        ],
        'sugerencia_generada' => [
            'required' => 'La sugerencia generada es obligatoria',
            'min_length' => 'La sugerencia debe tener al menos 10 caracteres'
        ]
    ];

    /**
     * Obtiene la sugerencia de una denuncia específica
     */
    public function getSugerenciaPorDenuncia(int $idDenuncia): ?array
    {
        return $this->where('id_denuncia', $idDenuncia)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Guarda una nueva sugerencia de IA
     */
    public function guardarSugerencia(array $data): bool
    {
        $sugerenciaData = [
            'id_denuncia' => $data['id_denuncia'],
            'sugerencia_generada' => $data['sugerencia'],
            'tokens_utilizados' => $data['tokens_usados'] ?? 0,
            'costo_estimado' => $data['costo_estimado'] ?? 0.0,
            'modelo_ia_usado' => $data['modelo'] ?? 'gpt-4o',
            'tiempo_generacion' => $data['tiempo_generacion'] ?? null,
            'estado_sugerencia' => 'generada'
        ];

        return $this->save($sugerenciaData);
    }

    /**
     * Actualiza la evaluación del usuario sobre la sugerencia
     */
    public function evaluarSugerencia(int $id, int $evaluacion, string $comentarios = ''): bool
    {
        return $this->update($id, [
            'evaluacion_usuario' => $evaluacion,
            'comentarios_usuario' => $comentarios,
            'estado_sugerencia' => 'evaluada'
        ]);
    }

    /**
     * Marca una sugerencia como vista
     */
    public function marcarComoVista(int $id): bool
    {
        return $this->update($id, [
            'estado_sugerencia' => 'vista'
        ]);
    }

    /**
     * Obtiene estadísticas de uso de las sugerencias de IA
     */
    public function getEstadisticasUso(int $clienteId = null): array
    {
        $builder = $this->db->table($this->table . ' sia');
        $builder->select('
            COUNT(*) as total_sugerencias,
            AVG(sia.tokens_utilizados) as promedio_tokens,
            SUM(sia.costo_estimado) as costo_total,
            AVG(sia.evaluacion_usuario) as evaluacion_promedio,
            COUNT(CASE WHEN sia.evaluacion_usuario >= 4 THEN 1 END) as evaluaciones_positivas
        ');

        if ($clienteId) {
            $builder->join('denuncias d', 'd.id = sia.id_denuncia');
            $builder->where('d.id_cliente', $clienteId);
        }

        $builder->where('sia.created_at >=', date('Y-m-d', strtotime('-30 days')));

        return $builder->get()->getRowArray();
    }
}
