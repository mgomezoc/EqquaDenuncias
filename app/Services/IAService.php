<?php

namespace App\Services;

use Exception;

class IAService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    // Config .env
    private string $modelo;
    private int    $maxTokens;
    private float  $temperature;
    private int    $timeout;

    // Flags de log
    private bool $logRequests;
    private bool $logResponses;
    private bool $logPrompts;

    public function __construct()
    {
        $this->apiKey = (string) getenv('OPENAI_API_KEY');
        if (!$this->apiKey) {
            log_message('error', '[IAService] OPENAI_API_KEY no configurada en .env');
            throw new Exception('API Key de OpenAI no configurada');
        }

        // Lee configuración desde .env con defaults razonables
        $this->modelo      = (string) (getenv('IA_MODELO_USADO') ?: 'gpt-4o');
        $this->maxTokens   = (int)    (getenv('IA_MAX_TOKENS') ?: 1000);
        $this->temperature = (float)  (getenv('IA_TEMPERATURE') ?: 0.4); // ajuste fino sugerido
        $this->timeout     = (int)    (getenv('IA_TIMEOUT_SEGUNDOS') ?: 30);

        // Flags de logging
        $this->logRequests  = $this->boolEnv('IA_LOG_REQUESTS', true);
        $this->logResponses = $this->boolEnv('IA_LOG_RESPONSES', false);
        $this->logPrompts   = $this->boolEnv('IA_LOG_PROMPTS', true);
    }

    /**
     * Genera una sugerencia de solución y devuelve:
     * - success, sugerencia, tokens_usados, prompt_usado
     */
    public function generarSugerenciaSolucion(array $denunciaData): array
    {
        try {
            $prompt = $this->construirPrompt($denunciaData);

            // Armamos el payload base
            $postData = [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un consultor senior en relaciones laborales y cumplimiento (RH/operación) para empresas en México. Tu objetivo es entregar recomendaciones PERSONALIZADAS, basadas estrictamente en los hechos descritos por el denunciante y en los metadatos del caso. 
- Tono: profesional, empático, no imperativo; evita listas genéricas. 
- Cita frases breves entre comillas de la descripción (máx. 12 palabras por cita) para fundamentar cada recomendación.
- Si hay incertidumbre, dilo explícitamente y sugiere cómo resolverla con acciones concretas.
- No inventes datos. No emitas conclusiones legales; limita “Marco normativo” a consideraciones prácticas de política interna y buenas prácticas.
- Entrega entre 260–420 palabras en español neutro con subtítulos en **negritas** y viñetas cortas. 
- Estructura: 1) **Diagnóstico específico** (qué se observa en ESTE caso), 2) **Riesgos y severidad** (por qué importa y a quién impacta), 3) **Acciones inmediatas ancladas al caso** (24–48 h, responsables y evidencias concretas a recabar), 4) **Opciones de resolución** con pros y contras, 5) **SLA recomendado** (tiempos realistas), 6) **Comunicación** (qué decir y a quién), 7) **Aclaraciones pendientes** (solo si faltan datos, en checklist).'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $this->temperature,
            ];

            // MODELOS NUEVOS (gpt-5*, o1/o3) usan max_completion_tokens en vez de max_tokens
            if ($this->usaMaxCompletionTokens($this->modelo)) {
                $postData['max_completion_tokens'] = $this->maxTokens;
            } else {
                $postData['max_tokens'] = $this->maxTokens;
            }

            if ($this->logRequests) {
                log_message(
                    'debug',
                    '[IAService] Enviando a OpenAI: model:{m} maxtok:{mt} temp:{t}',
                    ['m' => $this->modelo, 'mt' => $this->maxTokens, 't' => $this->temperature]
                );
            }
            if ($this->logPrompts) {
                log_message('debug', '[IAService] Prompt usado (truncado): {p}', [
                    'p' => $this->truncateForLog($prompt, 2000)
                ]);
            }

            $response = $this->realizarPeticionAPI($postData);

            if ($this->logResponses) {
                log_message('debug', '[IAService] Respuesta OpenAI (truncada): {r}', [
                    'r' => $this->truncateForLog(json_encode($response), 2000)
                ]);
            }

            if ($response && isset($response['choices'][0]['message']['content'])) {
                $sugerencia   = trim((string) $response['choices'][0]['message']['content']);
                // usage puede variar por modelo; intentamos mapear robusto
                $tokensUsados = (int) (
                    $response['usage']['total_tokens']
                    ?? $response['usage']['output_tokens']
                    ?? $response['usage']['completion_tokens']
                    ?? 0
                );

                log_message('debug', '[IAService] OK tokens:{t} len:{l}', [
                    't' => $tokensUsados,
                    'l' => strlen($sugerencia)
                ]);

                return [
                    'success'       => true,
                    'sugerencia'    => $sugerencia,
                    'tokens_usados' => $tokensUsados,
                    'prompt_usado'  => $prompt,
                ];
            }

            log_message('warning', '[IAService] Respuesta sin contenido utilizable');
            return ['success' => false, 'error' => 'No se pudo generar la sugerencia'];
        } catch (Exception $e) {
            log_message('error', '[IAService] Excepción generarSugerenciaSolucion: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error interno del servicio de IA'];
        }
    }

    /**
     * Construye un prompt profesional, anclado al caso específico.
     */
    private function construirPrompt(array $d): string
    {
        // Normaliza y recoge campos
        $descRaw      = (string)($d['descripcion'] ?? '');
        $desc         = mb_strtolower($descRaw);
        $folio        = $d['folio']               ?? 'Sin folio';
        $tipoDen      = $d['tipo_denunciante']    ?? 'No especificado';
        $catNom       = $d['categoria_nombre']    ?? 'Sin categorizar';
        $subcatNom    = $d['subcategoria_nombre'] ?? null;
        $deptoNom     = $d['departamento_nombre'] ?? 'No especificado';
        $sucursalNom  = $d['sucursal_nombre']     ?? 'No especificada';
        $area         = $d['area_incidente']      ?? 'Área no especificada';
        $fechaInc     = $d['fecha_incidente']     ?? 'No especificada';
        $comoEnt      = $d['como_se_entero']      ?? 'No especificado';
        $denunciado   = $d['denunciar_a_alguien'] ?? 'No identificado';
        $clienteNom   = $d['cliente_nombre']      ?? 'N/A';
        $medio        = $d['medio_recepcion']     ?? 'N/A';

        // Tipologías múltiples (buscamos varias coincidencias)
        $map = [
            'acoso sexual'              => ['acoso sexual', 'tocó', 'toque', 'manose', 'nalgad'],
            'acoso verbal'              => ['insult', 'grit', 'hostig'],
            'trato irrespetuoso'        => ['grit', 'faltas de respeto'],
            'agresión física'           => ['golpe', 'empuj', 'agresión'],
            'intimidación'              => ['amenaz', 'represal'],
            'discriminación'            => ['discrimin'],
            'fraude/corrupción'         => ['fraude', 'soborno', 'mordida', 'corrup'],
            'robo/hurto'                => ['robo', 'hurto'],
            'incumplimiento de normas'  => ['falta', 'procedim', 'política', 'protocolo'],
            'jornada/horarios/pagos'    => ['jornada', 'turno', 'hora', 'pago', 'salario', 'horas extra'],
            'seguridad/operación'       => ['equipo', 'herramienta', 'incidente', 'accidente', 'riesgo', 'patio', 'unidad'],
        ];
        $tags = [];
        foreach ($map as $label => $needles) {
            foreach ($needles as $needle) {
                if (mb_strpos($desc, $needle) !== false) {
                    $tags[$label] = true;
                    break;
                }
            }
        }
        if (empty($tags)) {
            $tags['situación laboral'] = true;
        }
        $tipologias = implode(', ', array_keys($tags));

        // Heurística simple de severidad
        $sev = 'Baja';
        $sevScore = 0;
        $buckets = [
            'Alta' => ['acoso sexual', 'agresión física', 'intimidación', 'fraude/corrupción', 'seguridad/operación'],
            'Media' => ['acoso verbal', 'robo/hurto', 'jornada/horarios/pagos', 'discriminación'],
        ];
        foreach ($buckets['Alta'] as $k) {
            if (isset($tags[$k])) $sevScore += 2;
        }
        foreach ($buckets['Media'] as $k) {
            if (isset($tags[$k])) $sevScore += 1;
        }
        if ($sevScore >= 2) $sev = 'Alta';
        elseif ($sevScore === 1) $sev = 'Media';

        // Extrae 2–4 frases cortas literales (máx. 140 chars por fragmento)
        $frases = [];
        foreach (preg_split('/[\r\n]+/', $descRaw) as $p) {
            $p = trim($p);
            if (mb_strlen($p) >= 25 && mb_strlen($p) <= 180) {
                $frases[] = '“' . mb_substr($p, 0, 140) . '”';
            }
            if (count($frases) >= 4) break;
        }
        if (empty($frases)) {
            $len = mb_strlen($descRaw);
            if ($len > 40) {
                $frases[] = '“' . mb_substr($descRaw, 0, min(120, $len)) . '”';
            }
        }
        $evidencias = empty($frases) ? '“Sin fragmentos literales disponibles.”' : implode("\n• ", $frases);

        // Construcción del prompt (contexto + hechos + solicitud)
        $prompt  = "## Caso real para asesoría personalizada\n";
        $prompt .= "**Cliente:** {$clienteNom} | **Folio:** {$folio}\n";
        $prompt .= "**Tipologías detectadas:** {$tipologias} | **Severidad estimada:** {$sev}\n\n";

        $prompt .= "**Metadatos del caso**\n";
        $prompt .= "• Categoría: {$catNom}" . ($subcatNom ? " → {$subcatNom}" : "") . "\n";
        $prompt .= "• Denunciante: {$tipoDen} | Medio de recepción: {$medio}\n";
        $prompt .= "• Ubicación: {$deptoNom} – {$sucursalNom} | Área: {$area}\n";
        $prompt .= "• Fecha del incidente: {$fechaInc} | Cómo se enteró: {$comoEnt}\n";
        $prompt .= "• Persona(s) señalada(s): {$denunciado}\n\n";

        $prompt .= "**Descripción del denunciante (resumen literal en fragmentos):**\n";
        $prompt .= "• {$evidencias}\n\n";

        // Instrucciones enfocadas en ESTE caso (no genéricas)
        $prompt .= "**Qué necesitamos de ti**\n";
        $prompt .= "- Entrega una **recomendación específica para este caso**, evitando plantillas genéricas.\n";
        $prompt .= "- Ancla cada acción a **hechos del relato** o a **metadatos** (cliente/sucursal/área/fecha/personas).\n";
        $prompt .= "- Si faltan elementos críticos, incluye un bloque final de **Aclaraciones pendientes** (máx. 4 bullets).\n\n";

        $prompt .= "**Formato de respuesta (obligatorio):**\n";
        $prompt .= "1) **Diagnóstico específico** (qué patrón ves aquí y por qué; cita 1–2 fragmentos breves)\n";
        $prompt .= "2) **Riesgos y severidad** (impacto en operación/seguridad/reputación)\n";
        $prompt .= "3) **Acciones inmediatas (24–48 h)** con responsables internos sugeridos y **evidencias concretas a recabar** (documentos, bitácoras, CCTV, testigos, nómina, etc.)\n";
        $prompt .= "4) **Opciones de resolución** (2–3 rutas) con **pros y contras** y **criterios de decisión**\n";
        $prompt .= "5) **SLA recomendado** (tiempos realistas por etapa)\n";
        $prompt .= "6) **Comunicación** (a quién, qué decir; mantener confidencialidad)\n";
        $prompt .= "7) **Aclaraciones pendientes** (solo si aplican)\n";

        return $prompt;
    }

    /**
     * Llamada HTTP a OpenAI
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
            log_message('error', '[IAService] cURL error: {err}', ['err' => $error]);
            throw new Exception('Error cURL: ' . $error);
        }

        if ($httpCode !== 200) {
            log_message('error', '[IAService] HTTP {code} body:{body}', [
                'code' => $httpCode,
                'body' => $this->truncateForLog((string) $response, 2000),
            ]);
            throw new Exception('Error HTTP: ' . $httpCode);
        }

        return json_decode((string) $response, true);
    }

    /**
     * Validación mínima: basta con descripción.
     */
    public function validarDatosMinimos(array $data): bool
    {
        return !empty($data['descripcion']);
    }

    /**
     * Estimación de costo por tokens (gpt-4o referencia).
     * Precios aproximados a octubre 2025
     */
    public function calcularCostoEstimado(int $tokens): float
    {
        // Precios por 1M tokens para gpt-4o (ajustar según modelo)
        $precioInput  = 2.50;   // $/1M tokens input
        $precioOutput = 10.00;  // $/1M tokens output

        // Estimación simple 60/40 (input/output)
        $tokensInput  = $tokens * 0.6;
        $tokensOutput = $tokens * 0.4;

        $costoInput  = ($tokensInput / 1_000_000) * $precioInput;
        $costoOutput = ($tokensOutput / 1_000_000) * $precioOutput;

        return $costoInput + $costoOutput;
    }

    // ========= Helpers =========

    /**
     * Determina si el modelo requiere max_completion_tokens en lugar de max_tokens
     */
    private function usaMaxCompletionTokens(string $model): bool
    {
        $m = strtolower($model);
        return (
            str_starts_with($m, 'gpt-5') ||   // gpt-5, gpt-5-nano, etc.
            str_starts_with($m, 'o1')    ||   // o1, o1-mini...
            str_starts_with($m, 'o3')    ||   // o3, o3-mini...
            str_contains($m, '4.1')      ||   // gpt-4.1 familias recientes
            str_starts_with($m, 'o4')  ||     // o4, o4-mini...
            str_contains($m, '4o-mini')       // minis recientes
        );
    }

    /**
     * Convierte valor de env a booleano
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

    /**
     * Trunca texto para logs
     */
    private function truncateForLog(string $text, int $maxLen = 1000): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }
        return mb_substr($text, 0, $maxLen) . '…(truncado)';
    }
}
