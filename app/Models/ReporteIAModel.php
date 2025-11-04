<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ReporteIAModel
 *
 * Modelo para gestionar reportes generados con IA
 *
 * @author Cesar M Gomez M
 * @version 1.0
 */
class ReporteIAModel extends Model
{
    protected $table            = 'reportes_ia_generados';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'id_cliente',
        'tipo_reporte',
        'periodo_nombre',
        'fecha_inicio',
        'fecha_fin',
        'resumen_ejecutivo',
        'hallazgos_principales',
        'analisis_geografico',
        'analisis_categorico',
        'eficiencia_operativa',
        'sugerencias_predictivas',
        'puntuacion_riesgo',
        'metricas_json',
        'modelo_ia_usado',
        'tokens_utilizados',
        'costo_estimado',
        'tiempo_generacion',
        'prompt_usado',
        'generado_por',
        'estado',
        'fecha_publicacion',
        'publicado_por',
        'ruta_pdf',
        'hash_pdf',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'id_cliente'     => 'required|integer',
        'tipo_reporte'   => 'required|in_list[mensual,trimestral,semestral]',
        'periodo_nombre' => 'required|max_length[50]',
        'fecha_inicio'   => 'required|valid_date',
        'fecha_fin'      => 'required|valid_date',
    ];

    protected $validationMessages = [
        'id_cliente' => [
            'required' => 'El cliente es requerido',
            'integer'  => 'ID de cliente inválido',
        ],
        'tipo_reporte' => [
            'required' => 'El tipo de reporte es requerido',
            'in_list'  => 'Tipo de reporte inválido',
        ],
    ];

    /**
     * Obtiene reportes de un cliente con información adicional
     */
    public function getReportesPorCliente(int $idCliente, array $filtros = []): array
    {
        $builder = $this->select(
            'reportes_ia_generados.*,
             clientes.nombre_empresa AS cliente_nombre,
             u1.nombre_usuario AS generado_por_nombre,
             u2.nombre_usuario AS publicado_por_nombre'
        )
            ->join('clientes', 'clientes.id = reportes_ia_generados.id_cliente')
            ->join('usuarios u1', 'u1.id = reportes_ia_generados.generado_por', 'left')
            ->join('usuarios u2', 'u2.id = reportes_ia_generados.publicado_por', 'left')
            ->where('reportes_ia_generados.id_cliente', $idCliente);

        if (!empty($filtros['tipo_reporte'])) {
            $builder->where('reportes_ia_generados.tipo_reporte', $filtros['tipo_reporte']);
        }
        if (!empty($filtros['estado'])) {
            $builder->where('reportes_ia_generados.estado', $filtros['estado']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $builder->where('reportes_ia_generados.fecha_inicio >=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $builder->where('reportes_ia_generados.fecha_fin <=', $filtros['fecha_hasta']);
        }

        return $builder->orderBy('reportes_ia_generados.created_at', 'DESC')->findAll();
    }

    /**
     * Obtiene un reporte completo por ID
     */
    public function getReporteCompleto(int $idReporte): ?array
    {
        $reporte = $this->select(
            'reportes_ia_generados.*,
             clientes.nombre_empresa AS cliente_nombre,
             clientes.logo AS cliente_logo,
             u1.nombre_usuario AS generado_por_nombre,
             u2.nombre_usuario AS publicado_por_nombre'
        )
            ->join('clientes', 'clientes.id = reportes_ia_generados.id_cliente')
            ->join('usuarios u1', 'u1.id = reportes_ia_generados.generado_por', 'left')
            ->join('usuarios u2', 'u2.id = reportes_ia_generados.publicado_por', 'left')
            ->where('reportes_ia_generados.id', $idReporte)
            ->first();

        if (!$reporte) {
            return null;
        }

        $reporte['metricas'] = [];
        if (!empty($reporte['metricas_json'])) {
            $dec = json_decode($reporte['metricas_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                $reporte['metricas'] = $dec;
            }
        }

        return $reporte;
    }

    /**
     * Obtiene todos los reportes con información resumida
     */
    public function getReportesResumen(array $filtros = []): array
    {
        $builder = $this->select(
            'reportes_ia_generados.id,
             reportes_ia_generados.periodo_nombre,
             reportes_ia_generados.tipo_reporte,
             reportes_ia_generados.fecha_inicio,
             reportes_ia_generados.fecha_fin,
             reportes_ia_generados.puntuacion_riesgo,
             reportes_ia_generados.estado,
             reportes_ia_generados.created_at,
             clientes.nombre_empresa AS cliente_nombre,
             u1.nombre_usuario AS generado_por_nombre'
        )
            ->join('clientes', 'clientes.id = reportes_ia_generados.id_cliente')
            ->join('usuarios u1', 'u1.id = reportes_ia_generados.generado_por', 'left');

        if (!empty($filtros['id_cliente'])) {
            $builder->where('reportes_ia_generados.id_cliente', $filtros['id_cliente']);
        }
        if (!empty($filtros['tipo_reporte'])) {
            $builder->where('reportes_ia_generados.tipo_reporte', $filtros['tipo_reporte']);
        }
        if (!empty($filtros['estado'])) {
            $builder->where('reportes_ia_generados.estado', $filtros['estado']);
        }

        return $builder->orderBy('reportes_ia_generados.created_at', 'DESC')->findAll();
    }

    /**
     * Verifica si ya existe un reporte para el mismo periodo
     */
    public function existeReportePeriodo(int $idCliente, string $tipoReporte, string $fechaInicio, string $fechaFin): bool
    {
        $count = $this->where('id_cliente', $idCliente)
            ->where('tipo_reporte', $tipoReporte)
            ->where('fecha_inicio', $fechaInicio)
            ->where('fecha_fin', $fechaFin)
            ->countAllResults();

        return $count > 0;
    }

    /**
     * Actualiza el estado de un reporte
     */
    public function cambiarEstado(int $idReporte, string $nuevoEstado, int $idUsuario = null): bool
    {
        $datos = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'publicado' && $idUsuario) {
            $datos['publicado_por']    = $idUsuario;
            $datos['fecha_publicacion'] = date('Y-m-d H:i:s');
        }

        return $this->update($idReporte, $datos);
    }

    /**
     * Guarda la ruta del PDF generado
     */
    public function guardarRutaPDF(int $idReporte, string $rutaPDF, ?string $hashPDF = null): bool
    {
        return $this->update($idReporte, [
            'ruta_pdf' => $rutaPDF,
            'hash_pdf' => $hashPDF,
        ]);
    }

    /**
     * Obtiene estadísticas de reportes generados
     */
    public function getEstadisticas(int $idCliente = null): array
    {
        // --- total ---
        $bTotal = $this->db->table($this->table);
        if ($idCliente) {
            $bTotal->where('id_cliente', $idCliente);
        }
        $totalReportes = $bTotal->countAllResults(); // resetea el builder

        $stats = [
            'total_reportes' => (int)$totalReportes,
            'por_tipo'       => [],
            'por_estado'     => [],
            'costo_total'    => 0.0,
            'tokens_total'   => 0,
        ];

        // --- por tipo ---
        $bTipo = $this->db->table($this->table)
            ->select('tipo_reporte, COUNT(*) AS total')
            ->groupBy('tipo_reporte');
        if ($idCliente) {
            $bTipo->where('id_cliente', $idCliente);
        }
        foreach ($bTipo->get()->getResultArray() as $row) {
            $stats['por_tipo'][$row['tipo_reporte'] ?: '—'] = (int)$row['total'];
        }

        // --- por estado ---
        $bEstado = $this->db->table($this->table)
            ->select('estado, COUNT(*) AS total')
            ->groupBy('estado');
        if ($idCliente) {
            $bEstado->where('id_cliente', $idCliente);
        }
        foreach ($bEstado->get()->getResultArray() as $row) {
            $stats['por_estado'][$row['estado'] ?: '—'] = (int)$row['total'];
        }

        // --- totales de costo y tokens ---
        $bTot = $this->db->table($this->table)
            ->select('SUM(COALESCE(costo_estimado,0)) AS costo_total, SUM(COALESCE(tokens_utilizados,0)) AS tokens_total');
        if ($idCliente) {
            $bTot->where('id_cliente', $idCliente);
        }
        $r = $bTot->get()->getRowArray() ?: [];
        $stats['costo_total']  = (float)($r['costo_total']  ?? 0);
        $stats['tokens_total'] = (int)  ($r['tokens_total'] ?? 0);

        return $stats;
    }


    /**
     * Obtiene reportes recientes
     */
    public function getReportesRecientes(int $limite = 10, int $idCliente = null): array
    {
        $builder = $this->select(
            'reportes_ia_generados.id,
             reportes_ia_generados.periodo_nombre,
             reportes_ia_generados.tipo_reporte,
             reportes_ia_generados.puntuacion_riesgo,
             reportes_ia_generados.estado,
             reportes_ia_generados.created_at,
             clientes.nombre_empresa AS cliente_nombre'
        )
            ->join('clientes', 'clientes.id = reportes_ia_generados.id_cliente');

        if ($idCliente) {
            $builder->where('reportes_ia_generados.id_cliente', $idCliente);
        }

        return $builder->orderBy('reportes_ia_generados.created_at', 'DESC')
            ->limit($limite)
            ->findAll();
    }

    /**
     * Elimina un reporte (las métricas históricas deberían tener FK ON DELETE CASCADE)
     */
    public function eliminarReporte(int $idReporte): bool
    {
        return $this->delete($idReporte);
    }

    /**
     * Obtiene métricas históricas de un reporte
     * (asegúrate de crear la tabla `reportes_metricas_historicas` si la vas a usar)
     */
    public function getMetricasHistoricas(int $idReporte): array
    {
        return $this->db->table('reportes_metricas_historicas')
            ->where('id_reporte', $idReporte)
            ->orderBy('categoria', 'ASC')
            ->orderBy('metrica_nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Compara métricas entre dos reportes
     */
    public function compararReportes(int $idReporte1, int $idReporte2): array
    {
        $reporte1 = $this->find($idReporte1);
        $reporte2 = $this->find($idReporte2);

        if (!$reporte1 || !$reporte2) {
            return [];
        }

        $metricas1 = json_decode($reporte1['metricas_json'] ?? '[]', true) ?: [];
        $metricas2 = json_decode($reporte2['metricas_json'] ?? '[]', true) ?: [];

        return [
            'reporte1' => [
                'id'      => $idReporte1,
                'periodo' => $reporte1['periodo_nombre'] ?? '',
                'metricas' => $metricas1,
            ],
            'reporte2' => [
                'id'      => $idReporte2,
                'periodo' => $reporte2['periodo_nombre'] ?? '',
                'metricas' => $metricas2,
            ],
            'variaciones' => $this->calcularVariaciones($metricas1, $metricas2),
        ];
    }

    /**
     * Calcula variaciones entre dos conjuntos de métricas
     */
    private function calcularVariaciones(array $metricas1, array $metricas2): array
    {
        $variaciones = [];
        $metricasComparables = [
            'total_denuncias',
            'denuncias_cerradas',
            'indice_resolucion',
            'tiempo_promedio_cierre_dias',
        ];

        foreach ($metricasComparables as $metrica) {
            if (isset($metricas1[$metrica], $metricas2[$metrica])) {
                $val1 = (float) $metricas1[$metrica];
                $val2 = (float) $metricas2[$metrica];
                $dif  = $val2 - $val1;
                $pct  = $val1 > 0 ? (($val2 - $val1) / $val1) * 100 : 0;

                $variaciones[$metrica] = [
                    'anterior'   => $val1,
                    'actual'     => $val2,
                    'diferencia' => $dif,
                    'porcentaje' => round($pct, 1),
                ];
            }
        }

        return $variaciones;
    }
}
