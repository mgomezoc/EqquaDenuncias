<?php

namespace App\Services;

use Exception;

/**
 * ReporteIAService
 * 
 * Servicio para generar reportes periódicos (mensual, trimestral, semestral)
 * utilizando Inteligencia Artificial (OpenAI GPT-4o)
 * 
 * @author Cesar M Gomez M
 * @version 1.0
 */
class ReporteIAService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    // Configuración
    private string $modelo;
    private int    $maxTokens;
    private float  $temperature;
    private int    $timeout;

    // Flags de logging
    private bool $logRequests;
    private bool $logResponses;

    // Base de datos
    private $db;

    public function __construct()
    {
        // Cargar API Key
        $this->apiKey = (string) getenv('OPENAI_API_KEY');
        if (!$this->apiKey) {
            log_message('error', '[ReporteIAService] OPENAI_API_KEY no configurada');
            throw new Exception('API Key de OpenAI no configurada');
        }

        // Configuración desde .env
        $this->modelo      = (string) (getenv('IA_MODELO_USADO') ?: 'gpt-4o');
        $this->maxTokens   = (int)    (getenv('IA_MAX_TOKENS_REPORTE') ?: 3000); // Más tokens para reportes
        $this->temperature = (float)  (getenv('IA_TEMPERATURE') ?: 0.4);
        $this->timeout     = (int)    (getenv('IA_TIMEOUT_SEGUNDOS') ?: 60); // Más tiempo para reportes

        // Flags de logging
        $this->logRequests  = $this->boolEnv('IA_LOG_REQUESTS', true);
        $this->logResponses = $this->boolEnv('IA_LOG_RESPONSES', false);

        // Conexión a base de datos
        $this->db = \Config\Database::connect();
    }

    /**
     * Genera un reporte completo con IA
     * 
     * @param int $idCliente ID del cliente
     * @param string $tipoReporte mensual|trimestral|semestral
     * @param string $fechaInicio Fecha inicio (YYYY-MM-DD)
     * @param string $fechaFin Fecha fin (YYYY-MM-DD)
     * @param int $idUsuario ID del usuario que genera el reporte
     * @return array ['success' => bool, 'id_reporte' => int, 'mensaje' => string]
     */
    public function generarReporte(
        int $idCliente,
        string $tipoReporte,
        string $fechaInicio,
        string $fechaFin,
        int $idUsuario
    ): array {
        try {
            // Validar tipo de reporte
            if (!in_array($tipoReporte, ['mensual', 'trimestral', 'semestral'])) {
                return ['success' => false, 'error' => 'Tipo de reporte inválido'];
            }

            log_message('info', '[ReporteIAService] Iniciando generación de reporte {tipo} para cliente {id}', [
                'tipo' => $tipoReporte,
                'id' => $idCliente
            ]);

            // 1. Recopilar métricas del periodo
            $metricas = $this->recopilarMetricas($idCliente, $fechaInicio, $fechaFin, $tipoReporte);

            if (empty($metricas)) {
                return ['success' => false, 'error' => 'No hay datos suficientes para generar el reporte'];
            }

            // 2. Construir prompt según tipo de reporte
            $prompt = $this->construirPrompt($tipoReporte, $metricas, $fechaInicio, $fechaFin);

            // 3. Llamar a OpenAI
            $t0 = microtime(true);
            $respuestaIA = $this->llamarOpenAI($prompt);
            $t1 = microtime(true);

            if (!$respuestaIA['success']) {
                return ['success' => false, 'error' => $respuestaIA['error']];
            }

            // 4. Procesar respuesta de la IA
            $contenidoReporte = $respuestaIA['contenido'];
            $tokensUsados = $respuestaIA['tokens_usados'];
            $tiempoGeneracion = round($t1 - $t0, 3);

            // 5. Extraer secciones del reporte
            $secciones = $this->extraerSecciones($contenidoReporte, $tipoReporte);

            // 6. Calcular costo
            $costoEstimado = $this->calcularCosto($tokensUsados);

            // 7. Guardar reporte en base de datos
            $periodoNombre = $this->generarNombrePeriodo($tipoReporte, $fechaInicio, $fechaFin);

            $datosReporte = [
                'id_cliente'              => $idCliente,
                'tipo_reporte'            => $tipoReporte,
                'periodo_nombre'          => $periodoNombre,
                'fecha_inicio'            => $fechaInicio,
                'fecha_fin'               => $fechaFin,
                'resumen_ejecutivo'       => $secciones['resumen_ejecutivo'] ?? null,
                'hallazgos_principales'   => $secciones['hallazgos_principales'] ?? null,
                'analisis_geografico'     => $secciones['analisis_geografico'] ?? null,
                'analisis_categorico'     => $secciones['analisis_categorico'] ?? null,
                'eficiencia_operativa'    => $secciones['eficiencia_operativa'] ?? null,
                'sugerencias_predictivas' => $secciones['sugerencias_predictivas'] ?? null,
                'puntuacion_riesgo'       => $secciones['puntuacion_riesgo'] ?? null,
                'metricas_json'           => json_encode($metricas, JSON_UNESCAPED_UNICODE),
                'modelo_ia_usado'         => $this->modelo,
                'tokens_utilizados'       => $tokensUsados,
                'costo_estimado'          => $costoEstimado,
                'tiempo_generacion'       => $tiempoGeneracion,
                'prompt_usado'            => $prompt,
                'generado_por'            => $idUsuario,
                'estado'                  => 'generado',
            ];

            $idReporte = $this->guardarReporte($datosReporte);

            if (!$idReporte) {
                return ['success' => false, 'error' => 'Error al guardar el reporte en base de datos'];
            }

            // 8. Guardar métricas históricas
            $this->guardarMetricas($idReporte, $metricas);

            log_message('info', '[ReporteIAService] Reporte generado exitosamente. ID: {id}', ['id' => $idReporte]);

            return [
                'success'           => true,
                'id_reporte'        => $idReporte,
                'mensaje'           => 'Reporte generado exitosamente',
                'tokens_usados'     => $tokensUsados,
                'costo_estimado'    => $costoEstimado,
                'tiempo_generacion' => $tiempoGeneracion,
            ];
        } catch (Exception $e) {
            log_message('error', '[ReporteIAService] Error: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error al generar el reporte: ' . $e->getMessage()];
        }
    }

    /**
     * Recopila métricas del periodo para el reporte
     */
    private function recopilarMetricas(int $idCliente, string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        $metricas = [];

        // Nombre del cliente
        $cliente = $this->db->table('clientes')
            ->select('nombre_empresa')
            ->where('id', $idCliente)
            ->get()
            ->getRowArray();

        $metricas['cliente'] = $cliente['nombre_empresa'] ?? 'Cliente';


        // ========== MÉTRICAS PRINCIPALES ==========

        // Total de denuncias del periodo
        $totalDenuncias = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $fechaInicio)
            ->where('fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->countAllResults();

        $metricas['total_denuncias'] = $totalDenuncias;

        // Denuncias cerradas
        $denunciasCerradas = $this->db->table('denuncias')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual') // <-- antes: id_estado
            ->where('denuncias.id_cliente', $idCliente)
            ->where('denuncias.fecha_hora_reporte >=', $fechaInicio)
            ->where('denuncias.fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->where('estados_denuncias.nombre', 'Cerrada')
            ->countAllResults();


        $metricas['denuncias_cerradas'] = $denunciasCerradas;
        $metricas['indice_resolucion'] = $totalDenuncias > 0
            ? round(($denunciasCerradas / $totalDenuncias) * 100, 1)
            : 0;

        // Tiempo promedio de cierre (solo denuncias cerradas)
        $tiemposQuery = "
            SELECT AVG(TIMESTAMPDIFF(DAY, fecha_hora_reporte, updated_at)) as promedio_dias,
                   STDDEV(TIMESTAMPDIFF(DAY, fecha_hora_reporte, updated_at)) as desviacion
            FROM denuncias 
            JOIN estados_denuncias ON estados_denuncias.id = denuncias.estado_actual
            WHERE denuncias.id_cliente = ?
            AND denuncias.fecha_hora_reporte >= ?
            AND denuncias.fecha_hora_reporte <= ?
            AND estados_denuncias.nombre = 'Cerrada'
        ";

        $tiempos = $this->db->query($tiemposQuery, [$idCliente, $fechaInicio, $fechaFin . ' 23:59:59'])
            ->getRowArray();

        $metricas['tiempo_promedio_cierre_dias'] = round($tiempos['promedio_dias'] ?? 0, 1);

        // ========== COMPARACIÓN CON PERIODO ANTERIOR ==========
        $metricas = array_merge($metricas, $this->calcularVariaciones($idCliente, $fechaInicio, $fechaFin, $tipoReporte));

        // ========== DISTRIBUCIÓN POR SUCURSAL ==========
        $metricas['distribucion_sucursal'] = $this->obtenerDistribucionSucursal($idCliente, $fechaInicio, $fechaFin);

        // ========== DISTRIBUCIÓN POR CATEGORÍA ==========
        $metricas['distribucion_categoria'] = $this->obtenerDistribucionCategoria($idCliente, $fechaInicio, $fechaFin);

        // ========== DISTRIBUCIÓN POR DEPARTAMENTO ==========
        $metricas['distribucion_departamento'] = $this->obtenerDistribucionDepartamento($idCliente, $fechaInicio, $fechaFin);

        // ========== DISTRIBUCIÓN POR MEDIO DE RECEPCIÓN ==========
        $metricas['distribucion_medio'] = $this->obtenerDistribucionMedio($idCliente, $fechaInicio, $fechaFin);

        // ========== DISTRIBUCIÓN POR ESTATUS ==========
        $metricas['distribucion_estatus'] = $this->obtenerDistribucionEstatus($idCliente, $fechaInicio, $fechaFin);

        // ========== CASOS ANTIGUOS PENDIENTES ==========
        $metricas['casos_antiguos_pendientes'] = $this->obtenerCasosAntiguosPendientes($idCliente, $fechaFin);

        return $metricas;
    }

    /**
     * Calcula variaciones respecto al periodo anterior
     */
    private function calcularVariaciones(int $idCliente, string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        // Calcular periodo anterior según tipo
        $periodoAnterior = $this->calcularPeriodoAnterior($fechaInicio, $fechaFin, $tipoReporte);

        // Total periodo actual
        $totalActual = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $fechaInicio)
            ->where('fecha_hora_reporte <=', $fechaFin . ' 23:59:59')
            ->countAllResults();

        // Total periodo anterior
        $totalAnterior = $this->db->table('denuncias')
            ->where('id_cliente', $idCliente)
            ->where('fecha_hora_reporte >=', $periodoAnterior['inicio'])
            ->where('fecha_hora_reporte <=', $periodoAnterior['fin'] . ' 23:59:59')
            ->countAllResults();

        $variacion = 0;
        $variacionTexto = '0%';

        if ($totalAnterior > 0) {
            $variacion = (($totalActual - $totalAnterior) / $totalAnterior) * 100;
            $signo = $variacion > 0 ? '+' : '';
            $variacionTexto = $signo . round($variacion, 1) . '%';
        }

        return [
            'total_periodo_anterior' => $totalAnterior,
            'variacion_porcentual'   => $variacion,
            'variacion_texto'        => $variacionTexto,
        ];
    }

    /**
     * Obtiene distribución por sucursal
     */
    private function obtenerDistribucionSucursal(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $query = "
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

        $fechaFinCompleta = $fechaFin . ' 23:59:59';

        return $this->db->query($query, [
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta,
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta
        ])->getResultArray();
    }

    /**
     * Obtiene distribución por categoría
     */
    private function obtenerDistribucionCategoria(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $query = "
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

        $fechaFinCompleta = $fechaFin . ' 23:59:59';

        return $this->db->query($query, [
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta,
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta
        ])->getResultArray();
    }

    /**
     * Obtiene distribución por departamento
     */
    private function obtenerDistribucionDepartamento(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $query = "
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

        $fechaFinCompleta = $fechaFin . ' 23:59:59';

        return $this->db->query($query, [
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta,
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta
        ])->getResultArray();
    }

    /**
     * Obtiene distribución por medio de recepción
     */
    private function obtenerDistribucionMedio(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $query = "
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

        $fechaFinCompleta = $fechaFin . ' 23:59:59';

        return $this->db->query($query, [
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta,
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta
        ])->getResultArray();
    }

    /**
     * Obtiene distribución por estatus
     */
    private function obtenerDistribucionEstatus(int $idCliente, string $fechaInicio, string $fechaFin): array
    {
        $query = "
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

        $fechaFinCompleta = $fechaFin . ' 23:59:59';

        return $this->db->query($query, [
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta,
            $idCliente,
            $fechaInicio,
            $fechaFinCompleta
        ])->getResultArray();
    }

    /**
     * Obtiene casos antiguos que siguen pendientes
     */
    private function obtenerCasosAntiguosPendientes(int $idCliente, string $fechaFin): array
    {
        $query = "
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

        return $this->db->query($query, [$fechaFin, $idCliente, $fechaFin])->getResultArray();
    }

    /**
     * Calcula el periodo anterior según el tipo de reporte
     */
    private function calcularPeriodoAnterior(string $fechaInicio, string $fechaFin, string $tipoReporte): array
    {
        $inicio = new \DateTime($fechaInicio);
        $fin = new \DateTime($fechaFin);

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
        }

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fin'    => $fin->format('Y-m-d'),
        ];
    }

    /**
     * Construye el prompt para la IA según el tipo de reporte
     */
    private function construirPrompt(string $tipoReporte, array $metricas, string $fechaInicio, string $fechaFin): string
    {
        $datosJSON = json_encode($metricas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $promptBase = "Eres un analista senior de cumplimiento y gestión de denuncias corporativas. ";
        $promptBase .= "Tienes acceso a datos REALES de denuncias de la empresa '{$metricas['cliente']}' ";
        $promptBase .= "del periodo: {$fechaInicio} al {$fechaFin}.\n\n";

        switch ($tipoReporte) {
            case 'mensual':
                return $this->construirPromptMensual($promptBase, $datosJSON, $metricas);
            case 'trimestral':
                return $this->construirPromptTrimestral($promptBase, $datosJSON, $metricas);
            case 'semestral':
                return $this->construirPromptSemestral($promptBase, $datosJSON, $metricas);
            default:
                return $promptBase . $datosJSON;
        }
    }

    /**
     * Construye prompt para reporte mensual
     */
    private function construirPromptMensual(string $base, string $datosJSON, array $metricas): string
    {
        $prompt = $base;
        $prompt .= "**TIPO DE REPORTE: MENSUAL (Táctico y Operacional)**\n\n";
        $prompt .= "**OBJETIVO:** Generar un reporte ejecutivo mensual que identifique problemas inmediatos, ";
        $prompt .= "velocidad de respuesta y acciones correctivas para el siguiente mes.\n\n";

        $prompt .= "**DATOS DEL PERIODO:**\n";
        $prompt .= "```json\n{$datosJSON}\n```\n\n";

        $prompt .= "**ESTRUCTURA OBLIGATORIA DEL REPORTE:**\n\n";

        $prompt .= "### 1. RESUMEN EJECUTIVO\n";
        $prompt .= "- Narrativa clara y concisa (150-200 palabras)\n";
        $prompt .= "- Cifras clave: Total denuncias, % cerradas, tiempo promedio cierre\n";
        $prompt .= "- Variación respecto al mes anterior (usar datos reales)\n";
        $prompt .= "- Hallazgo más relevante del mes\n";
        $prompt .= "- PUNTUACIÓN DE RIESGO (1-10) con justificación\n\n";

        $prompt .= "### 2. HALLAZGOS PRINCIPALES\n";
        $prompt .= "- 3-5 insights basados en los datos REALES\n";
        $prompt .= "- Identificar tendencias y patrones\n";
        $prompt .= "- Mencionar cifras específicas\n\n";

        $prompt .= "### 3. ANÁLISIS GEOGRÁFICO\n";
        $prompt .= "- Top 3 sucursales con más denuncias\n";
        $prompt .= "- Identificar focos de riesgo por ubicación\n";
        $prompt .= "- Análisis de concentración geográfica\n\n";

        $prompt .= "### 4. ANÁLISIS POR CATEGORÍA\n";
        $prompt .= "- Categorías más frecuentes\n";
        $prompt .= "- Categorías en aumento/disminución\n";
        $prompt .= "- Correlación entre categoría y canal de recepción\n\n";

        $prompt .= "### 5. EFICIENCIA OPERATIVA\n";
        $prompt .= "- Distribución por estatus\n";
        $prompt .= "- Análisis del tiempo de cierre\n";
        $prompt .= "- Identificar cuellos de botella\n\n";

        $prompt .= "### 6. SUGERENCIAS PROACTIVAS\n";
        $prompt .= "- 3-5 recomendaciones accionables y específicas\n";
        $prompt .= "- Predicciones para el siguiente mes\n";
        $prompt .= "- Acciones preventivas concretas\n\n";

        $prompt .= "**REGLAS IMPORTANTES:**\n";
        $prompt .= "- USA SOLO los datos proporcionados, NO inventes cifras\n";
        $prompt .= "- Escribe en español neutro, tono profesional\n";
        $prompt .= "- Usa formato Markdown con headers (###) y listas\n";
        $prompt .= "- Sé específico con números y porcentajes\n";
        $prompt .= "- La PUNTUACIÓN DE RIESGO debe estar entre 1-10 y aparecer como: **PUNTUACIÓN DE RIESGO: X/10**\n";
        $prompt .= "- Longitud total: 800-1200 palabras\n";

        return $prompt;
    }

    /**
     * Construye prompt para reporte trimestral
     */
    private function construirPromptTrimestral(string $base, string $datosJSON, array $metricas): string
    {
        $prompt = $base;
        $prompt .= "**TIPO DE REPORTE: TRIMESTRAL (Gestión y Tendencia)**\n\n";
        $prompt .= "**OBJETIVO:** Identificar tendencias de mediano plazo, estabilidad en riesgos y ";
        $prompt .= "efectividad del proceso de gestión (comparación QoQ).\n\n";

        $prompt .= "**DATOS DEL TRIMESTRE:**\n";
        $prompt .= "```json\n{$datosJSON}\n```\n\n";

        $prompt .= "**ESTRUCTURA OBLIGATORIA:**\n\n";

        $prompt .= "### 1. RESUMEN EJECUTIVO TRIMESTRAL\n";
        $prompt .= "- Análisis de la tendencia de los últimos 3 meses\n";
        $prompt .= "- Comparación con trimestre anterior (QoQ)\n";
        $prompt .= "- Estabilidad o volatilidad en volumen de denuncias\n";
        $prompt .= "- PUNTUACIÓN DE RIESGO TRIMESTRAL (1-10)\n\n";

        $prompt .= "### 2. TENDENCIA DE VOLUMEN\n";
        $prompt .= "- Evolución mensual dentro del trimestre\n";
        $prompt .= "- Identificar si hay crecimiento sostenido o picos aislados\n\n";

        $prompt .= "### 3. EFECTIVIDAD DEL PROCESO\n";
        $prompt .= "- Tasa de cierre trimestral\n";
        $prompt .= "- Capacidad de resolución del equipo\n";
        $prompt .= "- Comparación con trimestre anterior\n\n";

        $prompt .= "### 4. CONSISTENCIA DEL RIESGO\n";
        $prompt .= "- Top 3 categorías y su evolución\n";
        $prompt .= "- Top 3 sucursales y su estabilidad\n";
        $prompt .= "- Concentración vs dispersión de riesgos\n\n";

        $prompt .= "### 5. CANALES Y CONFIANZA\n";
        $prompt .= "- Distribución por canal de recepción\n";
        $prompt .= "- Análisis de denuncias anónimas vs identificadas\n";
        $prompt .= "- Accesibilidad del sistema\n\n";

        $prompt .= "### 6. RECOMENDACIONES ESTRATÉGICAS\n";
        $prompt .= "- Acciones para el siguiente trimestre\n";
        $prompt .= "- Áreas que requieren atención sostenida\n\n";

        $prompt .= "**REGLAS:**\n";
        $prompt .= "- Enfoque en tendencias, no en eventos aislados\n";
        $prompt .= "- Comparar con datos del trimestre anterior\n";
        $prompt .= "- Formato Markdown, tono ejecutivo\n";
        $prompt .= "- La PUNTUACIÓN DE RIESGO debe aparecer como: **PUNTUACIÓN DE RIESGO TRIMESTRAL: X/10**\n";
        $prompt .= "- Longitud: 1000-1500 palabras\n";

        return $prompt;
    }

    /**
     * Construye prompt para reporte semestral
     */
    private function construirPromptSemestral(string $base, string $datosJSON, array $metricas): string
    {
        $prompt = $base;
        $prompt .= "**TIPO DE REPORTE: SEMESTRAL (Estratégico y de Auditoría)**\n\n";
        $prompt .= "**OBJETIVO:** Análisis de riesgos sistémicos, eficacia de gestión a largo plazo ";
        $prompt .= "y evaluación estratégica (comparación YoY).\n\n";

        $prompt .= "**DATOS DEL SEMESTRE:**\n";
        $prompt .= "```json\n{$datosJSON}\n```\n\n";

        $prompt .= "**ESTRUCTURA OBLIGATORIA:**\n\n";

        $prompt .= "### 1. RESUMEN EJECUTIVO SEMESTRAL\n";
        $prompt .= "- Visión panorámica de los últimos 6 meses\n";
        $prompt .= "- Comparación con semestre anterior o mismo periodo año anterior\n";
        $prompt .= "- Evaluación de la salud organizacional\n";
        $prompt .= "- PUNTUACIÓN DE RIESGO SEMESTRAL (1-10)\n\n";

        $prompt .= "### 2. RIESGO SISTÉMICO\n";
        $prompt .= "- Matriz cruzada: Departamento x Sucursal\n";
        $prompt .= "- Identificar patrones estructurales\n";
        $prompt .= "- Riesgos que requieren cambios en políticas\n\n";

        $prompt .= "### 3. EVALUACIÓN DE IMPACTO\n";
        $prompt .= "- Resumen de casos cerrados y hallazgos\n";
        $prompt .= "- Eficacia del sistema de denuncias\n";
        $prompt .= "- Impacto en la cultura organizacional\n\n";

        $prompt .= "### 4. REVISIÓN ESTRATÉGICA\n";
        $prompt .= "- Áreas con mejora sostenida\n";
        $prompt .= "- Áreas con deterioro o riesgo creciente\n";
        $prompt .= "- Efectividad de acciones correctivas previas\n\n";

        $prompt .= "### 5. RECOMENDACIONES DE LARGO PLAZO\n";
        $prompt .= "- Cambios en políticas y procedimientos\n";
        $prompt .= "- Inversión en capacitación y prevención\n";
        $prompt .= "- Roadmap para los próximos 6 meses\n\n";

        $prompt .= "**REGLAS:**\n";
        $prompt .= "- Visión estratégica, no operativa\n";
        $prompt .= "- Identificar patrones de comportamiento organizacional\n";
        $prompt .= "- Recomendaciones de alto nivel\n";
        $prompt .= "- La PUNTUACIÓN DE RIESGO debe aparecer como: **PUNTUACIÓN DE RIESGO SEMESTRAL: X/10**\n";
        $prompt .= "- Formato Markdown profesional\n";
        $prompt .= "- Longitud: 1500-2000 palabras\n";

        return $prompt;
    }

    /**
     * Llama a la API de OpenAI
     */
    private function llamarOpenAI(string $prompt): array
    {
        try {
            $postData = [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un analista senior experto en compliance, gestión de denuncias corporativas y análisis de datos. Generas reportes ejecutivos profesionales basados exclusivamente en datos reales proporcionados.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $this->temperature,
            ];

            // Usar max_completion_tokens o max_tokens según modelo
            if ($this->usaMaxCompletionTokens($this->modelo)) {
                $postData['max_completion_tokens'] = $this->maxTokens;
            } else {
                $postData['max_tokens'] = $this->maxTokens;
            }

            if ($this->logRequests) {
                log_message('debug', '[ReporteIAService] Enviando a OpenAI: {m} tokens:{t}', [
                    'm' => $this->modelo,
                    't' => $this->maxTokens
                ]);
            }

            $response = $this->realizarPeticionAPI($postData);

            if ($this->logResponses) {
                log_message('debug', '[ReporteIAService] Respuesta recibida');
            }

            if ($response && isset($response['choices'][0]['message']['content'])) {
                $contenido = trim($response['choices'][0]['message']['content']);
                $tokensUsados = (int) ($response['usage']['total_tokens'] ?? 0);

                return [
                    'success'       => true,
                    'contenido'     => $contenido,
                    'tokens_usados' => $tokensUsados,
                ];
            }

            return ['success' => false, 'error' => 'Respuesta vacía de OpenAI'];
        } catch (Exception $e) {
            log_message('error', '[ReporteIAService] Error OpenAI: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Realiza petición HTTP a OpenAI
     */
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
            throw new Exception('Error HTTP: ' . $httpCode);
        }

        return json_decode((string) $response, true);
    }

    /**
     * Extrae las secciones del reporte generado por la IA
     */
    private function extraerSecciones(string $contenido, string $tipoReporte): array
    {
        $secciones = [];

        // Extraer puntuación de riesgo
        if (preg_match('/PUNTUACI[OÓ]N DE RIESGO[^:]*:\s*(\d+)\/10/iu', $contenido, $matches)) {
            $secciones['puntuacion_riesgo'] = (int) $matches[1];
        }

        // Dividir contenido por headers de nivel 3 (###)
        $partes = preg_split('/###\s+/u', $contenido);

        foreach ($partes as $parte) {
            $lineas = explode("\n", trim($parte), 2);
            if (count($lineas) < 2) continue;

            $titulo = strtolower(trim($lineas[0]));
            $contenidoSeccion = trim($lineas[1]);

            // Mapear títulos a campos de la BD
            if (stripos($titulo, 'resumen ejecutivo') !== false) {
                $secciones['resumen_ejecutivo'] = $contenidoSeccion;
            } elseif (stripos($titulo, 'hallazgos principales') !== false) {
                $secciones['hallazgos_principales'] = $contenidoSeccion;
            } elseif (stripos($titulo, 'geográfico') !== false || stripos($titulo, 'geografico') !== false) {
                $secciones['analisis_geografico'] = $contenidoSeccion;
            } elseif (stripos($titulo, 'categoría') !== false || stripos($titulo, 'categoria') !== false || stripos($titulo, 'categórico') !== false) {
                $secciones['analisis_categorico'] = $contenidoSeccion;
            } elseif (stripos($titulo, 'eficiencia') !== false || stripos($titulo, 'operativa') !== false) {
                $secciones['eficiencia_operativa'] = $contenidoSeccion;
            } elseif (stripos($titulo, 'sugerencias') !== false || stripos($titulo, 'recomendaciones') !== false || stripos($titulo, 'predictiv') !== false) {
                $secciones['sugerencias_predictivas'] = $contenidoSeccion;
            }
        }

        // Si no se extrajo resumen ejecutivo, usar los primeros párrafos
        if (empty($secciones['resumen_ejecutivo'])) {
            $secciones['resumen_ejecutivo'] = substr($contenido, 0, 1000);
        }

        return $secciones;
    }

    /**
     * Guarda el reporte en la base de datos
     */
    private function guardarReporte(array $datos): ?int
    {
        $builder = $this->db->table('reportes_ia_generados');

        if ($builder->insert($datos)) {
            return (int) $this->db->insertID();
        }

        return null;
    }

    /**
     * Guarda métricas en tabla histórica
     */
    private function guardarMetricas(int $idReporte, array $metricas): void
    {
        $builder = $this->db->table('reportes_metricas_historicas');

        $metricasBasicas = [
            ['metrica_nombre' => 'total_denuncias', 'metrica_valor' => $metricas['total_denuncias'], 'categoria' => 'KPI'],
            ['metrica_nombre' => 'denuncias_cerradas', 'metrica_valor' => $metricas['denuncias_cerradas'], 'categoria' => 'KPI'],
            ['metrica_nombre' => 'indice_resolucion', 'metrica_valor' => $metricas['indice_resolucion'], 'categoria' => 'KPI'],
            ['metrica_nombre' => 'tiempo_promedio_cierre_dias', 'metrica_valor' => $metricas['tiempo_promedio_cierre_dias'], 'categoria' => 'KPI'],
            ['metrica_nombre' => 'variacion_porcentual', 'metrica_valor' => $metricas['variacion_porcentual'] ?? 0, 'categoria' => 'KPI'],
        ];

        foreach ($metricasBasicas as $metrica) {
            $metrica['id_reporte'] = $idReporte;
            $builder->insert($metrica);
        }
    }

    /**
     * Genera nombre del periodo
     */
    private function generarNombrePeriodo(string $tipoReporte, string $fechaInicio, string $fechaFin): string
    {
        $inicio = new \DateTime($fechaInicio);
        $fin = new \DateTime($fechaFin);

        switch ($tipoReporte) {
            case 'mensual':
                $meses = [
                    1 => 'Enero',
                    2 => 'Febrero',
                    3 => 'Marzo',
                    4 => 'Abril',
                    5 => 'Mayo',
                    6 => 'Junio',
                    7 => 'Julio',
                    8 => 'Agosto',
                    9 => 'Septiembre',
                    10 => 'Octubre',
                    11 => 'Noviembre',
                    12 => 'Diciembre'
                ];
                return $meses[(int)$inicio->format('n')] . ' ' . $inicio->format('Y');

            case 'trimestral':
                $trimestre = ceil((int)$inicio->format('n') / 3);
                return "Q{$trimestre} " . $inicio->format('Y');

            case 'semestral':
                $semestre = ((int)$inicio->format('n') <= 6) ? '1H' : '2H';
                return "{$semestre} " . $inicio->format('Y');

            default:
                return $inicio->format('Y-m-d') . ' - ' . $fin->format('Y-m-d');
        }
    }

    /**
     * Calcula costo estimado
     */
    private function calcularCosto(int $tokens): float
    {
        $precioInput  = 2.50;
        $precioOutput = 10.00;

        $tokensInput  = $tokens * 0.6;
        $tokensOutput = $tokens * 0.4;

        $costoInput  = ($tokensInput / 1_000_000) * $precioInput;
        $costoOutput = ($tokensOutput / 1_000_000) * $precioOutput;

        return $costoInput + $costoOutput;
    }

    /**
     * Determina si el modelo usa max_completion_tokens
     */
    private function usaMaxCompletionTokens(string $model): bool
    {
        $m = strtolower($model);
        return (
            str_starts_with($m, 'gpt-5') ||
            str_starts_with($m, 'o1') ||
            str_starts_with($m, 'o3')
        );
    }

    /**
     * Convierte variable de entorno a booleano
     */
    private function boolEnv(string $key, bool $default = false): bool
    {
        $val = getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        $val = strtolower((string) $val);
        return in_array($val, ['1', 'true', 'on', 'yes'], true);
    }
}
