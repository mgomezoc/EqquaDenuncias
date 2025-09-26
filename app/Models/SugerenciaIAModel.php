<?php

namespace App\Models;

use CodeIgniter\Model;

class SugerenciaIAModel extends Model
{
    protected $table      = 'sugerencias_ia_denuncias';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'id_denuncia',
        'sugerencia_generada',
        'prompt_usado',
        'sugerencia_agente',
        'tokens_utilizados',
        'costo_estimado',
        'modelo_ia_usado',
        'tiempo_generacion',
        'estado_sugerencia',
        'evaluacion_usuario',
        'comentarios_usuario',
        'editado_por',
        'editado_at',
        'publicado',
        'publicado_por',
        'publicado_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $returnType = 'array';

    protected $validationRules = [
        'id_denuncia'        => 'required|integer',
        'sugerencia_generada' => 'required|min_length[10]',
        'modelo_ia_usado'    => 'required|max_length[50]',
        'estado_sugerencia'  => 'required|in_list[generada,vista,evaluada]',
        'publicado'          => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'id_denuncia' => [
            'required' => 'El ID de la denuncia es obligatorio',
            'integer'  => 'El ID de la denuncia debe ser un número entero',
        ],
        'sugerencia_generada' => [
            'required'   => 'La sugerencia generada es obligatoria',
            'min_length' => 'La sugerencia debe tener al menos 10 caracteres',
        ],
    ];

    /**
     * Obtiene la sugerencia más reciente de una denuncia específica
     */
    public function getSugerenciaPorDenuncia(int $idDenuncia): ?array
    {
        return $this->where('id_denuncia', $idDenuncia)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Guarda una nueva sugerencia de IA.
     * - Guarda el prompt usado (si se envía).
     * - Inicializa 'sugerencia_agente' con la generada para que el agente la edite.
     */
    public function guardarSugerencia(array $data): bool
    {
        $sugerenciaData = [
            'id_denuncia'         => $data['id_denuncia'],
            'sugerencia_generada' => $data['sugerencia'],
            'prompt_usado'        => $data['prompt_usado'] ?? null,
            'sugerencia_agente'   => $data['sugerencia'] ?? null, // copia inicial
            'tokens_utilizados'   => $data['tokens_usados'] ?? 0,
            'costo_estimado'      => $data['costo_estimado'] ?? 0.0,
            'modelo_ia_usado'     => $data['modelo'] ?? 'gpt-4o',
            'tiempo_generacion'   => $data['tiempo_generacion'] ?? null,
            'estado_sugerencia'   => 'generada',
            'publicado'           => 0,
            // publicado queda 0 por default, campos de auditoría nulos
        ];

        return $this->save($sugerenciaData);
    }

    /**
     * Permite que un agente edite la sugerencia (plantilla) antes de publicarla.
     * También marca quién y cuándo la editó.
     */
    public function actualizarEdicionAgente(int $idSugerencia, string $contenido, int $idAgente): bool
    {
        return $this->update($idSugerencia, [
            'sugerencia_agente' => $contenido,
            'editado_por'       => $idAgente,
            'editado_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Publica o despublica la sugerencia ya revisada por el agente.
     * - Si $publicar = true, marca publicado=1 y setea publicado_por/at.
     * - Si $publicar = false, vuelve a publicado=0 y limpia publicado_por/at.
     */
    public function publicarSugerencia(int $idSugerencia, int $idAgente, bool $publicar = true): bool
    {
        if ($publicar) {
            return $this->update($idSugerencia, [
                'publicado'     => 1,
                'publicado_por' => $idAgente,
                'publicado_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->update($idSugerencia, [
            'publicado'     => 0,
            'publicado_por' => null,
            'publicado_at'  => null,
        ]);
    }

    /**
     * Actualiza la evaluación del usuario sobre la sugerencia.
     */
    public function evaluarSugerencia(int $id, int $evaluacion, string $comentarios = ''): bool
    {
        return $this->update($id, [
            'evaluacion_usuario' => $evaluacion,
            'comentarios_usuario' => $comentarios,
            'estado_sugerencia'  => 'evaluada',
        ]);
    }

    /**
     * Marca una sugerencia como vista.
     */
    public function marcarComoVista(int $id): bool
    {
        return $this->update($id, ['estado_sugerencia' => 'vista']);
    }

    /**
     * Obtiene estadísticas de uso de las sugerencias de IA.
     * @param int|null  $clienteId        Si se pasa, filtra por cliente (JOIN con denuncias).
     * @param bool      $soloPublicadas   Si true, cuenta solo sugerencias publicadas.
     */
    public function getEstadisticasUso(int $clienteId = null, bool $soloPublicadas = false): array
    {
        $builder = $this->db->table($this->table . ' sia');
        $builder->select('
            COUNT(*)                                   AS total_sugerencias,
            AVG(sia.tokens_utilizados)                 AS promedio_tokens,
            SUM(sia.costo_estimado)                    AS costo_total,
            AVG(sia.evaluacion_usuario)                AS evaluacion_promedio,
            COUNT(CASE WHEN sia.evaluacion_usuario >= 4 THEN 1 END) AS evaluaciones_positivas
        ');

        if ($clienteId) {
            $builder->join('denuncias d', 'd.id = sia.id_denuncia');
            $builder->where('d.id_cliente', $clienteId);
        }

        if ($soloPublicadas) {
            $builder->where('sia.publicado', 1);
        }

        // Últimos 30 días
        $builder->where('sia.created_at >=', date('Y-m-d', strtotime('-30 days')));

        return $builder->get()->getRowArray() ?? [
            'total_sugerencias'   => 0,
            'promedio_tokens'     => 0,
            'costo_total'         => 0,
            'evaluacion_promedio' => null,
            'evaluaciones_positivas' => 0,
        ];
    }
}
