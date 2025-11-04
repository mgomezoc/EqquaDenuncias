<?php

namespace App\Services;

use Exception;

/**
 * ReporteIAService (v2)
 *
 * Generación de reportes (mensual, trimestral, semestral) con IA asegurando:
 * - Fechas válidas (no futuro)
 * - Métricas completas para gráficas
 * - Formato de salida robusto (JSON preferente + fallback Markdown)
 * - Parser de secciones tolerante a variaciones de encabezados
 *
 * @author Cesar M Gomez
 */
class ReporteIAService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    // Configuración base
    private string $modelo;
    private int    $maxTokensDefault;
    private float  $temperature;
    private int    $timeout;

    // Logging
    private bool $logRequests;
    private bool $logResponses;

    // BD
    private $db;

    // Control de formato de respuesta
    private bool $forceJson = true; // siempre pedimos JSON; caemos a fallback si falla

    public function __construct()
    {
        // API Key
        $this->apiKey = (string) getenv('OPENAI_API_KEY');
        if (!$this->apiKey) {
            log_message('error', '[ReporteIAService] OPENAI_API_KEY no configurada');
            throw new Exception('API Key de OpenAI no configurada');
        }

        // Config .env (con defaults más amplios)
        $this->modelo            = (string) (getenv('IA_MODELO_USADO') ?: 'gpt-4o');
        $this->maxTokensDefault  = (int)    (getenv('IA_MAX_TOKENS_REPORTE') ?: 3000);
        $this->temperature       = (float)  (getenv('IA_TEMPERATURE') ?: 0.4);
        $this->timeout           = (int)    (getenv('IA_TIMEOUT_SEGUNDOS') ?: 120);

        $this->logRequests       = $this->boolEnv('IA_LOG_REQUESTS', true);
        $this->logResponses      = $this->boolEnv('IA_LOG_RESPONSES', false);
        $this->forceJson         = $this->boolEnv('IA_FORCE_JSON', true);

        $this->db = \Config\Database::connect();
    }

    /**
     * Punto de entrada principal
     */
    public function generarReporte(
        int $idCliente,
        string $tipoReporte,
        string $fechaInicio,
        string $fechaFin,
        int $idUsuario
    ): array {
        try {
            if (!in_array($tipoReporte, ['mensual', 'trimestral', 'semestral'], true)) {
                return ['success' => false, 'error' => 'Tipo de reporte inválido'];
            }

            // Normaliza fechas y bloquea futuro
            [$fechaInicio, $fechaFin, $finReal] = $this->normalizarFechas($fechaInicio, $fechaFin);

            log_message('info', '[ReporteIAService] Generación {tipo} Cliente:{id} Periodo:{i}..{f} (finReal:{fr})', [
                'tipo' => $tipoReporte,
                'id' => $idCliente,
                'i' => $fechaInicio,
                'f' => $fechaFin,
                'fr' => $finReal
            ]);

            // Métricas (siempre completas, aunque sean 0/arrays vacíos)
            $metricas = $this->recopilarMetricas($idCliente, $fechaInicio, $fechaFin, $tipoReporte);

            // Si no hay datos de denuncias en el periodo, generamos reporte mínimo sin IA
            $tieneDatos = (int)($metricas['total_denuncias'] ?? 0) > 0;

            $periodoNombre = $this->generarNombrePeriodo($tipoReporte, $fechaInicio, $fechaFin);
            $tokensUsados = 0;
            $tiempoGeneracion = 0.0;
            $costoEstimado = 0.0;
            $prompt = '';
            $secciones = [
                'resumen_ejecutivo'       => '',
                'hallazgos_principales'   => '',
                'analisis_geografico'     => '',
                'analisis_categorico'     => '',
                'eficiencia_operativa'    => '',
                'sugerencias_predictivas' => '',
                'puntuacion_riesgo'       => 0,
            ];

            if ($tieneDatos) {
                // Prompt robusto (JSON estricto)
                $prompt = $this->construirPromptJson($tipoReporte, $metricas, $fechaInicio, $finReal);

                // Tokens por tipo (override .env si existe)
                $maxTokens = $this->resolverMaxTokens($tipoReporte);

                // Llamado IA (JSON preferente + fallback a Markdown)
                $t0 = microtime(true);
                $resp = $this->llamarOpenAI($prompt, $maxTokens, $this->forceJson);
                $t1 = microtime(true);

                $tiempoGeneracion = round($t1 - $t0, 3);

                if (!$resp['success']) {
                    // Fallback duro: genera un resumen mínimo con base en métricas (sin IA)
                    log_message('warning', '[ReporteIAService] Fallback sin IA: {err}', ['err' => $resp['error'] ?? '']);
                    $secciones = $this->seccionesMinimasDesdeMetricas($metricas, $tipoReporte, $fechaInicio, $finReal);
                } else {
                    $tokensUsados = (int) ($resp['tokens_usados'] ?? 0);
                    $costoEstimado = $this->calcularCosto($tokensUsados);
                    $secciones = $resp['secciones']; // ya mapeadas
                }
            } else {
                // Sin datos: texto mínimo explícito (no se llama a IA)
                $secciones = $this->seccionesMinimasSinDatos($metricas, $tipoReporte, $fechaInicio, $finReal);
            }

            // Guardar reporte
            $datosReporte = [
                'id_cliente'              => $idCliente,
                'tipo_reporte'            => $tipoReporte,
                'periodo_nombre'          => $periodoNombre,
                'fecha_inicio'            => $fechaInicio,
                'fecha_fin'               => $fechaFin,
                'resumen_ejecutivo'       => $secciones['resumen_ejecutivo'] ?: null,
                'hallazgos_principales'   => $secciones['hallazgos_principales'] ?: null,
                'analisis_geografico'     => $secciones['analisis_geografico'] ?: null,
                'analisis_categorico'     => $secciones['analisis_categorico'] ?: null,
                'eficiencia_operativa'    => $secciones['eficiencia_operativa'] ?: null,
                'sugerencias_predictivas' => $secciones['sugerencias_predictivas'] ?: null,
                'puntuacion_riesgo'       => $secciones['puntuacion_riesgo'] ?? 0,
                'metricas_json'           => json_encode($metricas, JSON_UNESCAPED_UNICODE),
                'modelo_ia_usado'         => $this->modelo,
                'tokens_utilizados'       => $tokensUsados,
                'costo_estimado'          => $costoEstimado,
                'tiempo_generacion'       => $tiempoGeneracion,
                'prompt_usado'            => $prompt ?: 'REPORTE_MINIMO_AUTOGENERADO',
                'generado_por'            => $idUsuario,
                'estado'                  => 'generado',
            ];

            $idReporte = $this->guardarReporte($datosReporte);
            if (!$idReporte) {
                return ['success' => false, 'error' => 'Error al guardar el reporte en base de datos'];
            }

            // Persistir KPIs básicos para comparativas
            $this->guardarMetricas($idReporte, $metricas);

            log_message('info', '[ReporteIAService] Reporte generado. ID:{id} tokens:{t} costo:{c}', [
                'id' => $idReporte,
                't' => $tokensUsados,
                'c' => $costoEstimado
            ]);

            return [
                'success'           => true,
                'id_reporte'        => $idReporte,
                'mensaje'           => 'Reporte generado correctamente',
                'tokens_usados'     => $tokensUsados,
                'costo_estimado'    => $costoEstimado,
                'tiempo_generacion' => $tiempoGeneracion,
            ];
        } catch (Exception $e) {
            log_message('error', '[ReporteIAService] Error: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error al generar el reporte: ' . $e->getMessage()];
        }
    }

    /* ===================== Métricas (siempre listas para gráficas) ===================== */

    private function recopilarMetricas(int $idCliente, string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        $metricas = [];

        // Cliente
        $cliente = $this->db->table('clientes')
            ->select('nombre_empresa')
            ->where('id', $idCliente)
            ->get()
            ->getRowArray();
        $metricas['cliente'] = $cliente['nombre_empresa'] ?? 'Cliente';

        // Totales
        $totalDenuncias = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $fechaInicio)
            ->where('fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->countAllResults();
        $metricas['total_denuncias'] = (int) $totalDenuncias;

        // Cerradas
        $denunciasCerradas = $this->db->table('denuncias')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual')
            ->where('denuncias.id_cliente', $idCliente)
            ->where('denuncias.fecha_hora_reporte >=', $fechaInicio)
            ->where('denuncias.fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->where('estados_denuncias.nombre', 'Cerrada')
            ->countAllResults();
        $metricas['denuncias_cerradas'] = (int) $denunciasCerradas;
        $metricas['indice_resolucion']  = $totalDenuncias > 0 ? round(($denunciasCerradas / $totalDenuncias) * 100, 1) : 0.0;

        // Tiempo promedio cierre (en días) – solo cerradas
        $tiemposQuery = "
            SELECT AVG(TIMESTAMPDIFF(DAY, fecha_hora_reporte, updated_at)) as promedio_dias
            FROM denuncias 
            JOIN estados_denuncias ON estados_denuncias.id = denuncias.estado_actual
            WHERE denuncias.id_cliente = ?
              AND denuncias.fecha_hora_reporte >= ?
              AND denuncias.fecha_hora_reporte <= ?
              AND estados_denuncias.nombre = 'Cerrada'
        ";
        $tiempos = $this->db->query($tiemposQuery, [$idCliente, $fechaInicio, $fechaFin . ' 23:59:59'])->getRowArray();
        $metricas['tiempo_promedio_cierre_dias'] = round((float)($tiempos['promedio_dias'] ?? 0), 1);

        // Variaciones vs periodo anterior
        $metricas = array_merge($metricas, $this->calcularVariaciones($idCliente, $fechaInicio, $fechaFin, $tipoReporte));

        // Distribuciones (siempre arrays, aunque queden vacíos)
        $metricas['distribucion_sucursal']     = $this->obtenerDistribucionSucursal($idCliente, $fechaInicio, $fechaFin)     ?: [];
        $metricas['distribucion_categoria']    = $this->obtenerDistribucionCategoria($idCliente, $fechaInicio, $fechaFin)    ?: [];
        $metricas['distribucion_departamento'] = $this->obtenerDistribucionDepartamento($idCliente, $fechaInicio, $fechaFin) ?: [];
        $metricas['distribucion_medio']        = $this->obtenerDistribucionMedio($idCliente, $fechaInicio, $fechaFin)        ?: [];
        $metricas['distribucion_estatus']      = $this->obtenerDistribucionEstatus($idCliente, $fechaInicio, $fechaFin)      ?: [];
        $metricas['casos_antiguos_pendientes'] = $this->obtenerCasosAntiguosPendientes($idCliente, $fechaFin)                ?: [];

        return $metricas;
    }

    private function calcularVariaciones(int $idCliente, string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        $periodoAnterior = $this->calcularPeriodoAnterior($fechaInicio, $fechaFin, $tipoReporte);

        $totalActual = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $fechaInicio)
            ->where('fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->countAllResults();

        $totalAnterior = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $periodoAnterior['inicio'])
            ->where('fecha_hora_reporte <=', $periodoAnterior['fin'] . ' 23:59:59')
            ->countAllResults();

        $variacion = 0.0;
        $variacionTexto = '0%';
        if ($totalAnterior > 0) {
            $variacion = (($totalActual - $totalAnterior) / $totalAnterior) * 100.0;
            $signo = $variacion > 0 ? '+' : '';
            $variacionTexto = $signo . round($variacion, 1) . '%';
        }

        return [
            'total_periodo_anterior' => (int) $totalAnterior,
            'variacion_porcentual'   => round($variacion, 1),
            'variacion_texto'        => $variacionTexto,
        ];
    }

    /**
     * Calcula el periodo anterior según el tipo de reporte.
     * Devuelve fechas en formato Y-m-d (sin hora).
     */
    private function calcularPeriodoAnterior(string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        $inicio = new \DateTime($fechaInicio);
        $fin    = new \DateTime($fechaFin);

        switch ($tipoReporte) {
            case 'mensual':
                $inicio->modify('-1 month');
                $fin->modify('-1 month');
                break;

            case 'trimestral':
                $inicio->modify('-3 months');
                $fin->modify('-3 months');
                break;

            case 'semestral':
                $inicio->modify('-6 months');
                $fin->modify('-6 months');
                break;

            default:
                // Fallback: un periodo de la misma longitud que el actual, inmediatamente anterior
                $diff = $inicio->diff($fin);
                $inicio->sub($diff);
                $fin->sub($diff);
                break;
        }

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fin'    => $fin->format('Y-m-d'),
        ];
    }


    private function obtenerDistribucionSucursal(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $q = "
            SELECT s.nombre as sucursal,
                   COUNT(d.id) as total,
                   ROUND((COUNT(d.id) * 100.0 / (SELECT COUNT(*) FROM denuncias 
                        WHERE id_cliente = ? 
                          AND fecha_hora_reporte >= ? 
                          AND fecha_hora_reporte <= ?)), 1) as porcentaje
            FROM denuncias d
            JOIN sucursales s ON s.id = d.id_sucursal
            WHERE d.id_cliente = ?
              AND d.fecha_hora_reporte >= ?
              AND d.fecha_hora_reporte <= ?
            GROUP BY s.id, s.nombre
            ORDER BY total DESC
            LIMIT 10
        ";
        $f = $fechaFin . ' 23:59:59';
        return $this->db->query($q, [$idCliente, $fechaInicio, $f, $idCliente, $fechaInicio, $f])->getResultArray();
    }

    private function obtenerDistribucionCategoria(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $q = "
            SELECT c.nombre as categoria,
                   COUNT(d.id) as total,
                   ROUND((COUNT(d.id) * 100.0 / (SELECT COUNT(*) FROM denuncias 
                        WHERE id_cliente = ? 
                          AND fecha_hora_reporte >= ? 
                          AND fecha_hora_reporte <= ?)), 1) as porcentaje
            FROM denuncias d
            JOIN categorias_denuncias c ON c.id = d.categoria
            WHERE d.id_cliente = ?
              AND d.fecha_hora_reporte >= ?
              AND d.fecha_hora_reporte <= ?
            GROUP BY c.id, c.nombre
            ORDER BY total DESC
            LIMIT 10
        ";
        $f = $fechaFin . ' 23:59:59';
        return $this->db->query($q, [$idCliente, $fechaInicio, $f, $idCliente, $fechaInicio, $f])->getResultArray();
    }

    private function obtenerDistribucionDepartamento(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $q = "
            SELECT dep.nombre as departamento,
                   COUNT(d.id) as total,
                   ROUND((COUNT(d.id) * 100.0 / (SELECT COUNT(*) FROM denuncias 
                        WHERE id_cliente = ? 
                          AND fecha_hora_reporte >= ? 
                          AND fecha_hora_reporte <= ?)), 1) as porcentaje
            FROM denuncias d
            JOIN departamentos dep ON dep.id = d.id_departamento
            WHERE d.id_cliente = ?
              AND d.fecha_hora_reporte >= ?
              AND d.fecha_hora_reporte <= ?
            GROUP BY dep.id, dep.nombre
            ORDER BY total DESC
            LIMIT 10
        ";
        $f = $fechaFin . ' 23:59:59';
        return $this->db->query($q, [$idCliente, $fechaInicio, $f, $idCliente, $fechaInicio, $f])->getResultArray();
    }

    private function obtenerDistribucionMedio(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $q = "
            SELECT medio_recepcion as medio,
                   COUNT(id) as total,
                   ROUND((COUNT(id) * 100.0 / (SELECT COUNT(*) FROM denuncias 
                        WHERE id_cliente = ? 
                          AND fecha_hora_reporte >= ? 
                          AND fecha_hora_reporte <= ?)), 1) as porcentaje
            FROM denuncias
            WHERE id_cliente = ?
              AND fecha_hora_reporte >= ?
              AND fecha_hora_reporte <= ?
            GROUP BY medio_recepcion
            ORDER BY total DESC
        ";
        $f = $fechaFin . ' 23:59:59';
        return $this->db->query($q, [$idCliente, $fechaInicio, $f, $idCliente, $fechaInicio, $f])->getResultArray();
    }

    private function obtenerDistribucionEstatus(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $q = "
            SELECT e.nombre as estatus,
                   COUNT(d.id) as total,
                   ROUND((COUNT(d.id) * 100.0 / (SELECT COUNT(*) FROM denuncias 
                        WHERE id_cliente = ? 
                          AND fecha_hora_reporte >= ? 
                          AND fecha_hora_reporte <= ?)), 1) as porcentaje
            FROM denuncias d
            JOIN estados_denuncias e ON e.id = d.estado_actual
            WHERE d.id_cliente = ?
              AND d.fecha_hora_reporte >= ?
              AND d.fecha_hora_reporte <= ?
            GROUP BY e.id, e.nombre
            ORDER BY total DESC
        ";
        $f = $fechaFin . ' 23:59:59';
        return $this->db->query($q, [$idCliente, $fechaInicio, $f, $idCliente, $fechaInicio, $f])->getResultArray();
    }

    private function obtenerCasosAntiguosPendientes(int $idCliente, string $fechaFin): array
    {
        $q = "
            SELECT d.folio,
                   d.fecha_hora_reporte,
                   DATEDIFF(?, d.fecha_hora_reporte) as dias_pendiente,
                   e.nombre as estatus
            FROM denuncias d
            JOIN estados_denuncias e ON e.id = d.estado_actual
            WHERE d.id_cliente = ?
              AND d.fecha_hora_reporte < ?
              AND e.nombre NOT IN ('Cerrada', 'Desechada')
            ORDER BY d.fecha_hora_reporte ASC
            LIMIT 10
        ";
        return $this->db->query($q, [$fechaFin, $idCliente, $fechaFin])->getResultArray();
    }

    /* ===================== Construcción de prompts ===================== */

    private function construirPromptJson(string $tipo, array $metricas, string $inicio, string $finReal): string
    {
        $datosJSON = json_encode($metricas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $contexto  = "Eres un analista senior de cumplimiento y gestión de denuncias corporativas.\n";
        $contexto .= "Dispones de DATOS REALES de la empresa '{$metricas['cliente']}' del periodo {$inicio} a {$finReal}.\n";
        $contexto .= "No inventes cifras; si falta información deja el campo vacío (\"\").\n";
        $contexto .= "Si el periodo aún no concluye, deja claro que los datos están calculados hasta {$finReal}.\n\n";

        $objetivoPorTipo = [
            'mensual'     => "TIPO: MENSUAL (táctico/operacional)",
            'trimestral'  => "TIPO: TRIMESTRAL (gestión y tendencia, comparación QoQ)",
            'semestral'   => "TIPO: SEMESTRAL (estratégico/auditoría, comparación YoY)",
        ];

        $estructura = <<<MD
Devuelve EXCLUSIVAMENTE **JSON** con la forma EXACTA:

{
  "resumen_ejecutivo": "150-300 palabras, cifras clave (total, % cerradas, tiempo promedio), variación vs periodo anterior, hallazgo principal. Incluye al final la línea 'PUNTUACIÓN DE RIESGO: X/10'.",
  "hallazgos_principales": "3-5 insights con números REALES.",
  "analisis_geografico": "Top sucursales y focos de riesgo.",
  "analisis_categorico": "Categorías frecuentes y su evolución.",
  "eficiencia_operativa": "Distribución por estatus y tiempos; cuellos de botella.",
  "sugerencias_predictivas": "3-5 recomendaciones accionables (sin inventar cifras).",
  "puntuacion_riesgo": 0
}

- No incluyas Markdown, encabezados ni texto fuera del JSON.
- "puntuacion_riesgo" es un entero 0..10. Si no hay datos, usa 0.
MD;

        return $contexto
            . $objetivoPorTipo[$tipo] . "\n\n"
            . "DATOS DEL PERIODO EN JSON:\n```json\n{$datosJSON}\n```\n\n"
            . $estructura;
    }

    private function resolverMaxTokens(string $tipo): int
    {
        // Permite override por env por tipo
        $byTypeEnv = [
            'mensual'    => (int) (getenv('IA_MAX_TOKENS_REPORTE_MENSUAL')    ?: 3000),
            'trimestral' => (int) (getenv('IA_MAX_TOKENS_REPORTE_TRIMESTRAL') ?: 5000),
            'semestral'  => (int) (getenv('IA_MAX_TOKENS_REPORTE_SEMESTRAL')  ?: 6000),
        ];
        // Si hay IA_MAX_TOKENS_REPORTE global, tiene prioridad
        $global = (int) (getenv('IA_MAX_TOKENS_REPORTE') ?: 0);
        return $global > 0 ? $global : ($byTypeEnv[$tipo] ?? $this->maxTokensDefault);
    }

    /* ===================== Call IA (JSON preferente + fallback Markdown) ===================== */

    private function llamarOpenAI(string $prompt, int $maxTokens, bool $forceJson): array
    {
        try {
            $postData = [
                'model'       => $this->modelo,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un analista senior experto en compliance y análisis de datos. Respondes EXACTAMENTE en el formato solicitado.'
                    ],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $this->temperature,
            ];

            if ($this->usaMaxCompletionTokens($this->modelo)) {
                $postData['max_completion_tokens'] = $maxTokens;
            } else {
                $postData['max_tokens'] = $maxTokens;
            }

            if ($forceJson) {
                // Pedimos JSON estricto; si el modelo no soporta, el servidor ignorará el campo y hacemos fallback abajo.
                $postData['response_format'] = ['type' => 'json_object'];
            }

            if ($this->logRequests) {
                log_message('debug', '[ReporteIAService] OpenAI model:{m} maxT:{t} forceJson:{fj}', [
                    'm' => $this->modelo,
                    't' => $maxTokens,
                    'fj' => $forceJson ? 'yes' : 'no'
                ]);
            }

            $response = $this->realizarPeticionAPI($postData);

            if ($this->logResponses) {
                log_message('debug', '[ReporteIAService] Respuesta recibida de OpenAI');
            }

            // ¿Intento JSON primero?
            $raw = $response['choices'][0]['message']['content'] ?? '';
            $tokensUsados = (int) ($response['usage']['total_tokens'] ?? 0);

            // 1) Intentar parsear JSON
            $parsed = $this->parseJsonSecciones($raw);
            if ($parsed['ok']) {
                return [
                    'success'       => true,
                    'secciones'     => $parsed['data'],
                    'tokens_usados' => $tokensUsados
                ];
            }

            // 2) Fallback: parsear Markdown con el parser robusto
            $sec = $this->extraerSeccionesMarkdown($raw);
            if (!empty($sec)) {
                return [
                    'success'       => true,
                    'secciones'     => $sec,
                    'tokens_usados' => $tokensUsados
                ];
            }

            // 3) Reintento corto: prompt de reparación para JSON
            $repairPrompt = "Convierte estrictamente el siguiente texto a JSON con las llaves exigidas (sin texto extra):\n\n{$raw}";
            $postData['messages'][] = ['role' => 'user', 'content' => $repairPrompt];
            $response2 = $this->realizarPeticionAPI($postData);
            $raw2 = $response2['choices'][0]['message']['content'] ?? '';
            $parsed2 = $this->parseJsonSecciones($raw2);

            if ($parsed2['ok']) {
                $tokensUsados2 = (int) ($response2['usage']['total_tokens'] ?? 0);
                return [
                    'success'       => true,
                    'secciones'     => $parsed2['data'],
                    'tokens_usados' => max($tokensUsados, $tokensUsados2),
                ];
            }

            return ['success' => false, 'error' => 'No se pudo interpretar la respuesta de la IA'];
        } catch (Exception $e) {
            log_message('error', '[ReporteIAService] Error OpenAI: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function realizarPeticionAPI(array $postData): ?array
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($postData),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error cURL: ' . $error);
        }
        if ($httpCode !== 200) {
            // Devolvemos payload para que el caller pueda hacer fallback
            $decoded = json_decode((string) $response, true);
            $msg = $decoded['error']['message'] ?? 'HTTP ' . $httpCode;
            throw new Exception('Error HTTP: ' . $msg);
        }

        return json_decode((string) $response, true);
    }

    /* ===================== Parsing helpers ===================== */

    private function parseJsonSecciones(string $raw): array
    {
        $raw = trim($raw);
        // Si vino envuelto en ```json ... ```
        if (preg_match('/```json\s*([\s\S]*?)```/i', $raw, $m)) {
            $raw = trim($m[1]);
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return ['ok' => false];
        }

        $secciones = [
            'resumen_ejecutivo'       => (string) ($data['resumen_ejecutivo'] ?? ''),
            'hallazgos_principales'   => (string) ($data['hallazgos_principales'] ?? ''),
            'analisis_geografico'     => (string) ($data['analisis_geografico'] ?? ''),
            'analisis_categorico'     => (string) ($data['analisis_categorico'] ?? ''),
            'eficiencia_operativa'    => (string) ($data['eficiencia_operativa'] ?? ''),
            'sugerencias_predictivas' => (string) ($data['sugerencias_predictivas'] ?? ''),
            'puntuacion_riesgo'       => (int)    ($data['puntuacion_riesgo'] ?? 0),
        ];

        return ['ok' => true, 'data' => $secciones];
    }

    /**
     * Parser Markdown robusto (headers ##..######, con/sin numeración, acentos).
     */
    private function extraerSeccionesMarkdown(string $contenido): array
    {
        $secciones = [];

        $txt = preg_replace("/\r\n|\r/", "\n", (string) $contenido);

        // Captura encabezado (##..######) y su bloque hasta el siguiente encabezado del mismo rango
        preg_match_all('/^(#{2,6})\s+(?:\d+\.\s*)?([^\n]+)\n+([\s\S]*?)(?=^#{2,6}\s+|\z)/m', $txt, $matches, PREG_SET_ORDER);

        $map = [
            'resumen_ejecutivo'       => ['resumen ejecutivo'],
            'hallazgos_principales'   => ['hallazgos principales', 'insights', 'hallazgos'],
            'analisis_geografico'     => ['análisis geográfico', 'analisis geografico', 'geográfico', 'geografico', 'sucursal', 'ubicación', 'ubicacion'],
            'analisis_categorico'     => ['análisis por categoría', 'analisis por categoria', 'análisis categórico', 'analisis categorico', 'categoría', 'categoria'],
            'eficiencia_operativa'    => ['eficiencia operativa', 'estatus', 'proceso', 'tiempo de cierre', 'cuellos de botella'],
            'sugerencias_predictivas' => ['sugerencias proactivas', 'recomendaciones', 'predicciones', 'sugerencias', 'acciones'],
        ];

        $norm = function (string $s): string {
            $s = mb_strtolower(trim($s), 'UTF-8');
            $s = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ä', 'ë', 'ï', 'ö', 'ü'], ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'], $s);
            return $s;
        };

        foreach ($matches as $m) {
            $titulo = $norm($m[2]);
            $cuerpo = trim($m[3]);

            foreach ($map as $campo => $claves) {
                foreach ($claves as $kw) {
                    if (mb_strpos($titulo, $norm($kw)) !== false) {
                        if (empty($secciones[$campo])) {
                            $secciones[$campo] = $cuerpo;
                        }
                    }
                }
            }

            if (!isset($secciones['puntuacion_riesgo'])) {
                if (preg_match('/PUNTUACI[OÓ]N\s+DE\s+RIESGO[^:]*:\s*(\d+)\s*\/\s*10/iu', $cuerpo, $r)) {
                    $secciones['puntuacion_riesgo'] = (int) $r[1];
                }
            }
        }

        if (empty($secciones['resumen_ejecutivo'])) {
            if (preg_match('/resumen\s+ejecutivo[\s:\-]*\n+([\s\S]{300,1200})/iu', $txt, $r)) {
                $secciones['resumen_ejecutivo'] = trim($r[1]);
            } else {
                $secciones['resumen_ejecutivo'] = mb_substr($txt, 0, 1000, 'UTF-8');
            }
        }

        // Asegura llaves faltantes
        $secciones += [
            'hallazgos_principales'   => '',
            'analisis_geografico'     => '',
            'analisis_categorico'     => '',
            'eficiencia_operativa'    => '',
            'sugerencias_predictivas' => '',
            'puntuacion_riesgo'       => $secciones['puntuacion_riesgo'] ?? 0,
        ];

        return $secciones;
    }

    /* ===================== Guardado / utilidades ===================== */

    private function guardarReporte(array $datos): ?int
    {
        $builder = $this->db->table('reportes_ia_generados');
        if ($builder->insert($datos)) {
            return (int) $this->db->insertID();
        }
        return null;
    }

    private function guardarMetricas(int $idReporte, array $metricas): void
    {
        $builder = $this->db->table('reportes_metricas_historicas');

        $metricasBasicas = [
            ['metrica_nombre' => 'total_denuncias',               'metrica_valor' => (int)   ($metricas['total_denuncias'] ?? 0),               'categoria' => 'KPI'],
            ['metrica_nombre' => 'denuncias_cerradas',            'metrica_valor' => (int)   ($metricas['denuncias_cerradas'] ?? 0),            'categoria' => 'KPI'],
            ['metrica_nombre' => 'indice_resolucion',             'metrica_valor' => (float) ($metricas['indice_resolucion'] ?? 0),             'categoria' => 'KPI'],
            ['metrica_nombre' => 'tiempo_promedio_cierre_dias',   'metrica_valor' => (float) ($metricas['tiempo_promedio_cierre_dias'] ?? 0),   'categoria' => 'KPI'],
            ['metrica_nombre' => 'variacion_porcentual',          'metrica_valor' => (float) ($metricas['variacion_porcentual'] ?? 0),          'categoria' => 'KPI'],
        ];

        foreach ($metricasBasicas as $m) {
            $m['id_reporte'] = $idReporte;
            $builder->insert($m);
        }
    }

    private function generarNombrePeriodo(string $tipoReporte, string $fechaInicio, string $fechaFin): string
    {
        $inicio = new \DateTime($fechaInicio);

        switch ($tipoReporte) {
            case 'mensual':
                $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
                return $meses[(int)$inicio->format('n')] . ' ' . $inicio->format('Y');
            case 'trimestral':
                $trimestre = (int) ceil($inicio->format('n') / 3);
                return "Q{$trimestre} " . $inicio->format('Y');
            case 'semestral':
                $sem = ((int)$inicio->format('n') <= 6) ? '1H' : '2H';
                return "{$sem} " . $inicio->format('Y');
            default:
                return $fechaInicio . ' - ' . $fechaFin;
        }
    }

    private function calcularCosto(int $tokens): float
    {
        // Estimación conservadora (por millón de tokens)
        $precioInput  = 2.50;
        $precioOutput = 10.00;

        $tokensInput  = $tokens * 0.6;
        $tokensOutput = $tokens * 0.4;

        $costoInput  = ($tokensInput  / 1_000_000) * $precioInput;
        $costoOutput = ($tokensOutput / 1_000_000) * $precioOutput;

        return round($costoInput + $costoOutput, 4);
    }

    private function usaMaxCompletionTokens(string $model): bool
    {
        $m = strtolower($model);
        return (
            str_starts_with($m, 'gpt-5') ||
            str_starts_with($m, 'o1')   ||
            str_starts_with($m, 'o3')
        );
    }

    private function boolEnv(string $key, bool $default = false): bool
    {
        $val = getenv($key);
        if ($val === false || $val === null || $val === '') return $default;
        $val = strtolower((string)$val);
        return in_array($val, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Normaliza fechas y bloquea periodos a futuro.
     * Retorna [inicio, fin, finRealUsadoParaDatos]
     */
    private function normalizarFechas(string $inicio, string $fin): array
    {
        $dIni = new \DateTime($inicio);
        $dFin = new \DateTime($fin);
        $hoy  = new \DateTime('today');

        if ($dFin > $hoy) {
            $dFin = clone $hoy;
        }
        if ($dIni > $dFin) {
            // si vinieron invertidas, intercambia
            [$dIni, $dFin] = [$dFin, $dIni];
        }

        return [$dIni->format('Y-m-d'), $dFin->format('Y-m-d'), $dFin->format('Y-m-d')];
    }

    /* ===================== Fallbacks de secciones sin IA ===================== */

    private function seccionesMinimasSinDatos(array $m, string $tipo, string $ini, string $fin): array
    {
        $tipoTxt = ucfirst($tipo);
        $res = "**{$tipoTxt} sin datos**\n\n" .
            "No se registraron denuncias para {$m['cliente']} en el periodo del {$ini} al {$fin}. " .
            "Las gráficas se muestran vacías y la puntuación de riesgo es 0/10.\n";
        return [
            'resumen_ejecutivo'       => $res,
            'hallazgos_principales'   => "Sin contenido: no hubo casos en el periodo.",
            'analisis_geografico'     => "Sin contenido: no hubo casos en el periodo.",
            'analisis_categorico'     => "Sin contenido: no hubo casos en el periodo.",
            'eficiencia_operativa'    => "Sin contenido: no hubo casos en el periodo.",
            'sugerencias_predictivas' => "Sin contenido. Recomendación general: continuar campañas de awareness y mantener canales abiertos.",
            'puntuacion_riesgo'       => 0,
        ];
    }

    private function seccionesMinimasDesdeMetricas(array $m, string $tipo, string $ini, string $fin): array
    {
        $res  = "Reporte {$tipo} para **{$m['cliente']}** ({$ini} – {$fin}).\n\n";
        $res .= "Total de denuncias: {$m['total_denuncias']}. Cerradas: {$m['denuncias_cerradas']} ({$m['indice_resolucion']}%). ";
        $res .= "Tiempo promedio de cierre: {$m['tiempo_promedio_cierre_dias']} días. ";
        $res .= "Variación vs periodo anterior: {$m['variacion_texto']}.\n\n";
        $res .= "PUNTUACIÓN DE RIESGO: 0/10\n";

        return [
            'resumen_ejecutivo'       => $res,
            'hallazgos_principales'   => "Generación mínima sin IA: revisa las categorías y sucursales con mayor volumen para acciones puntuales.",
            'analisis_geografico'     => "Consulta el panel de gráficas para ver la distribución por sucursal.",
            'analisis_categorico'     => "Consulta el panel de gráficas para ver la distribución por categoría.",
            'eficiencia_operativa'    => "Revisa la distribución por estatus y el tiempo promedio de cierre.",
            'sugerencias_predictivas' => "Reforzar seguimiento de casos abiertos y capacitar en las categorías más frecuentes.",
            'puntuacion_riesgo'       => 0,
        ];
    }
}
