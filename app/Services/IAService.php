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
        $this->temperature = (float)  (getenv('IA_TEMPERATURE') ?: 0.5);
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
                        'content' => 'Eres un consultor experto en gestión de recursos humanos y resolución de conflictos laborales. Tu función es asesorar a administradores y supervisores sobre cómo investigar, gestionar y resolver denuncias laborales de manera profesional, justa y efectiva. Proporcionas recomendaciones prácticas, accionables y alineadas con mejores prácticas de RH. Tu lenguaje es profesional pero accesible, enfocado en la acción y los resultados.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $this->temperature,
            ];

            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            // MODELOS NUEVOS (gpt-5*, o1/o3) usan max_completion_tokens en vez de max_tokens
            if ($this->usaMaxCompletionTokens($this->modelo)) {
                $postData['max_completion_tokens'] = $this->maxTokens;
            } else {
                $postData['max_tokens'] = $this->maxTokens;
            }
            // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

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
     * Construye un prompt profesional orientado al ADMINISTRADOR/AGENTE
     * que debe resolver la denuncia
     */
    private function construirPrompt(array $d): string
    {
        // Inferencia simple de tipología para contexto
        $desc = mb_strtolower($d['descripcion'] ?? '');
        $tipologia = 'situación laboral';
        $map = [
            'nalgad' => 'acoso físico',
            'tocó' => 'contacto inapropiado',
            'toque' => 'contacto inapropiado',
            'manose' => 'acoso físico',
            'golpe' => 'agresión física',
            'empuj' => 'agresión física',
            'insult' => 'acoso verbal',
            'grit' => 'trato irrespetuoso',
            'amenaz' => 'intimidación',
            'acoso sexual' => 'acoso sexual',
            'discrimin' => 'discriminación',
            'celular' => 'falta de atención al cliente',
            'jugando' => 'distracción en el trabajo',
            'robo' => 'robo o hurto',
            'fraude' => 'fraude',
            'soborno' => 'corrupción',
            'falta' => 'incumplimiento de normas',
            'ausencia' => 'ausentismo'
        ];

        foreach ($map as $needle => $label) {
            if (mb_strpos($desc, $needle) !== false) {
                $tipologia = $label;
                break;
            }
        }

        // Campos con fallback legible
        $folio         = $d['folio']               ?? 'Sin folio';
        $tipoDen       = $d['tipo_denunciante']    ?? 'No especificado';
        $catNom        = $d['categoria_nombre']    ?? 'Sin categorizar';
        $subcatNom     = $d['subcategoria_nombre'] ?? 'N/A';
        $deptoNom      = $d['departamento_nombre'] ?? 'No especificado';
        $sucursalNom   = $d['sucursal_nombre']     ?? 'No especificada';
        $area          = $d['area_incidente']      ?? 'área no especificada';
        $fechaInc      = $d['fecha_incidente']     ?? 'fecha no especificada';
        $comoEnt       = $d['como_se_entero']      ?? 'no especificado';
        $denunciado    = $d['denunciar_a_alguien'] ?? 'persona no identificada';
        $descripcion   = $d['descripcion']         ?? 'Sin descripción';

        // Prompt enfocado para el ADMINISTRADOR/SUPERVISOR/AGENTE que gestiona la denuncia
        $prompt = "Eres un consultor especializado en resolución de conflictos laborales y gestión de denuncias corporativas. ";
        $prompt .= "Tu rol es asesorar a administradores, supervisores y personal de RH sobre las mejores acciones para investigar y resolver casos.\n\n";

        $prompt .= "**DENUNCIA RECIBIDA - FOLIO: {$folio}**\n";
        $prompt .= "**Tipo de caso identificado:** {$tipologia}\n\n";

        $prompt .= "**INFORMACIÓN DEL CASO:**\n";
        $prompt .= "• **Categoría:** {$catNom}";
        if ($subcatNom !== 'N/A') {
            $prompt .= " → {$subcatNom}";
        }
        $prompt .= "\n";
        $prompt .= "• **Reportado por:** {$tipoDen}\n";
        $prompt .= "• **Ubicación:** {$deptoNom} - {$sucursalNom}\n";
        $prompt .= "• **Área del incidente:** {$area}\n";
        $prompt .= "• **Fecha del incidente:** {$fechaInc}\n";
        $prompt .= "• **Forma de conocimiento:** {$comoEnt}\n";
        $prompt .= "• **Persona(s) involucrada(s):** {$denunciado}\n\n";

        $prompt .= "**DESCRIPCIÓN COMPLETA DEL INCIDENTE:**\n";
        $prompt .= "\"{$descripcion}\"\n\n";

        $prompt .= "---\n\n";
        $prompt .= "**SOLICITUD DE ASESORÍA:**\n";
        $prompt .= "Como administrador responsable de gestionar esta denuncia, necesito una recomendación profesional y práctica sobre cómo proceder. ";
        $prompt .= "Por favor, proporciona una sugerencia que incluya:\n\n";

        $prompt .= "1. **Acciones inmediatas:** Primeros pasos que debo tomar en las próximas 24-48 horas\n";
        $prompt .= "2. **Investigación:** Qué evidencias recabar, testimonios a solicitar, documentos a revisar\n";
        $prompt .= "3. **Personas/áreas a involucrar:** RH, legal, supervisores directos, comité de ética, etc.\n";
        $prompt .= "4. **Medidas cautelares:** Si aplican medidas de protección o separación temporal\n";
        $prompt .= "5. **Marco normativo:** Consideraciones legales, políticas internas o reglamentos aplicables\n";
        $prompt .= "6. **Plazo estimado:** Tiempo razonable para la resolución del caso\n";
        $prompt .= "7. **Posibles resoluciones:** Acciones correctivas, disciplinarias o soluciones recomendadas según hallazgos\n";
        $prompt .= "8. **Comunicación:** Cómo mantener informadas a las partes involucradas respetando confidencialidad\n\n";

        $prompt .= "**FORMATO DE RESPUESTA:**\n";
        $prompt .= "• Usa un tono profesional, directo y orientado a la acción\n";
        $prompt .= "• Sé específico y proporciona pasos concretos y accionables\n";
        $prompt .= "• Prioriza siempre la seguridad, dignidad y derechos de todas las personas involucradas\n";
        $prompt .= "• Mantén la respuesta entre 300-450 palabras\n";
        $prompt .= "• Escribe en español neutro y profesional\n";
        $prompt .= "• Puedes usar formato markdown con **negritas** para destacar puntos críticos\n";
        $prompt .= "• Estructura la información de forma clara y escaneable\n";

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
        // Modelos que actualmente requieren max_completion_tokens en Chat Completions
        $m = strtolower($model);
        return (
            str_starts_with($m, 'gpt-5') ||   // gpt-5, gpt-5-nano, etc.
            str_starts_with($m, 'o1')    ||   // o1, o1-mini...
            str_starts_with($m, 'o3')    ||   // o3, o3-mini...
            str_contains($m, '4.1')      ||   // gpt-4.1 familias recientes
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
