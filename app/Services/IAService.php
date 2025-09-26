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
        $this->maxTokens   = (int)    (getenv('IA_MAX_TOKENS') ?: 800);
        $this->temperature = (float)  (getenv('IA_TEMPERATURE') ?: 0.4);
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

            $postData = [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un consultor senior en RRHH, seguridad y compliance. Entregas planes accionables, claros, cronológicos y medibles. Evitas teoría general; das instrucciones concretas aplicables en el sitio de trabajo.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens'  => $this->maxTokens,
                'temperature' => $this->temperature,
            ];

            if ($this->logRequests) {
                log_message(
                    'debug',
                    '[IAService] Enviando a OpenAI: model:{m} max_tokens:{mt} temp:{t}',
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
                $tokensUsados = (int) ($response['usage']['total_tokens'] ?? 0);

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
     * Construye un prompt que obliga a UNA sola respuesta, estilo plan accionable.
     */
    private function construirPrompt(array $d): string
    {
        // Inferencia simple de tipología
        $desc = mb_strtolower($d['descripcion'] ?? '');
        $tipologia = 'incidente laboral';
        $map = [
            'nalgad' => 'acoso sexual físico',
            'tocó'   => 'acoso sexual físico',
            'toque'  => 'acoso sexual físico',
            'manose' => 'acoso sexual físico',
            'golpe'  => 'agresión física',
            'empuj'  => 'agresión física',
            'insult' => 'acoso verbal',
            'grit'   => 'acoso verbal',
            'amenaz' => 'amenaza',
        ];
        foreach ($map as $needle => $label) {
            if (mb_strpos($desc, $needle) !== false) {
                $tipologia = $label;
                break;
            }
        }

        // Campos con fallback legible
        $folio       = $d['folio']               ?? 'N/A';
        $tipoDen     = $d['tipo_denunciante']    ?? 'N/A';
        $cat         = $d['categoria_nombre']    ?? 'Sin categoría asignada';
        $subcat      = $d['subcategoria_nombre'] ?? 'N/A';
        $depto       = $d['departamento_nombre'] ?? 'N/A';
        $sucursal    = $d['sucursal_nombre']     ?? 'N/A';
        $area        = $d['area_incidente']      ?? 'N/A';
        $fechaInc    = $d['fecha_incidente']     ?? 'N/A';
        $comoEnt     = $d['como_se_entero']      ?? 'N/A';
        $denunciado  = $d['denunciar_a_alguien'] ?? 'N/A';
        $descripcion = $d['descripcion']         ?? 'N/A';

        $prompt  = "Caso real de denuncia en entorno laboral. Genera UNA SOLA propuesta de resolución, tipo plan operativo, ";
        $prompt .= "con pasos concretos, responsables y tiempos. Evita teoría general.\n\n";

        $prompt .= "**Contexto del caso**\n";
        $prompt .= "- Folio: {$folio}\n";
        $prompt .= "- Tipo de denunciante: {$tipoDen} (posible tipología: {$tipologia})\n";
        $prompt .= "- Categoría/Subcategoría (si existieran): {$cat} / {$subcat}\n";
        $prompt .= "- Departamento: {$depto} | Sucursal: {$sucursal} | Área: {$area}\n";
        $prompt .= "- Fecha del incidente: {$fechaInc}\n";
        $prompt .= "- Cómo se enteró: {$comoEnt}\n";
        $prompt .= "- Persona denunciada o involucrada (si aplica): {$denunciado}\n";
        $prompt .= "- Descripción: \"{$descripcion}\"\n\n";

        $prompt .= "**Entrega exactamente estas secciones, con bullets y verbos de acción; no agregues otras secciones:**\n";
        $prompt .= "1) 0–24 horas (acciones inmediatas, responsables y tiempos)\n";
        $prompt .= "2) 24–72 horas (investigación: evidencia específica del sitio: CCTV/pasillos/cocina, bitácoras, turnos; entrevistas en orden)\n";
        $prompt .= "3) 3–7 días (decisión con criterios: corroborado / indicios / no corroborado; debidos procesos y sanciones posibles)\n";
        $prompt .= "4) Medidas cautelares (separación de partes, reubicación temporal, suspensión preventiva pagada si aplica)\n";
        $prompt .= "5) Aseguramiento de evidencia (qué solicitar, a quién y en qué ventana de tiempo)\n";
        $prompt .= "6) Entrevistas (orden, guion breve, confidencialidad, registro)\n";
        $prompt .= "7) Comunicación y no-represalias (mensajes internos neutros; canal para que el denunciante amplíe sin perder anonimato)\n";
        $prompt .= "8) KPIs de seguimiento (2–4 métricas medibles y plazos)\n\n";

        $prompt .= "**Reglas de estilo**\n";
        $prompt .= "- Español neutro. Máximo 450–500 palabras.\n";
        $prompt .= "- Una sola respuesta; nada de alternativas. Sin teoría ni párrafos vacíos. ";
        $prompt .= "Si un dato falta, indica el paso igual con una asunción razonable.\n";

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
     */
    public function calcularCostoEstimado(int $tokens): float
    {
        $precioInput  = 5.00;   // $/1M tokens
        $precioOutput = 15.00;  // $/1M tokens

        // Estimación simple 70/30
        $tokensInput  = $tokens * 0.7;
        $tokensOutput = $tokens * 0.3;

        $costoInput  = ($tokensInput / 1_000_000) * $precioInput;
        $costoOutput = ($tokensOutput / 1_000_000) * $precioOutput;

        return $costoInput + $costoOutput;
    }

    // ========= Helpers =========

    private function boolEnv(string $key, bool $default = false): bool
    {
        $val = getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        $val = strtolower((string) $val);
        return in_array($val, ['1', 'true', 'on', 'yes'], true);
    }

    private function truncateForLog(string $text, int $maxLen = 1000): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }
        return mb_substr($text, 0, $maxLen) . '…(truncado)';
    }
}
