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

            // Armamos el payload base
            $postData = [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Eres un consultor amigable y experimentado en recursos humanos. Das consejos prácticos y naturales sobre cómo resolver situaciones laborales. Tu estilo es personal, comprensivo y directo, como un amigo con experiencia que da buenos consejos.'
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
     * Construye un prompt natural y personal para sugerencias de solución
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
            'golpe' => 'agresión',
            'empuj' => 'agresión',
            'insult' => 'acoso verbal',
            'grit' => 'trato irrespetuoso',
            'amenaz' => 'intimidación',
            'celular' => 'falta de atención al cliente',
            'jugando' => 'distracción en el trabajo'
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

        // Prompt natural y personal
        $prompt = "Hola, necesito tu consejo sobre una situación que pasó en el trabajo. ";
        $prompt .= "Es el caso {$folio} y se trata de {$tipologia}.\n\n";

        $prompt .= "**Los detalles son:**\n";
        $prompt .= "• Quién reporta: {$tipoDen}\n";
        $prompt .= "• Dónde pasó: {$deptoNom} - {$sucursalNom}, en {$area}\n";
        $prompt .= "• Cuándo: {$fechaInc}\n";
        $prompt .= "• Cómo se enteró: {$comoEnt}\n";
        $prompt .= "• Persona involucrada: {$denunciado}\n";
        $prompt .= "• Lo que pasó: \"{$descripcion}\"\n\n";

        $prompt .= "**¿Qué me recomiendas hacer?**\n";
        $prompt .= "Dame una sugerencia práctica y natural de cómo manejar esto. ";
        $prompt .= "No necesito un plan formal con pasos numerados, sino más bien un consejo amigable ";
        $prompt .= "sobre qué sería lo más sensato hacer en esta situación. ";
        $prompt .= "Habla como si fueras un compañero de trabajo con experiencia dando un buen consejo.\n\n";

        $prompt .= "**Mantén tu respuesta:**\n";
        $prompt .= "- Natural y conversacional\n";
        $prompt .= "- Práctica y aplicable\n";
        $prompt .= "- Entre 200-300 palabras\n";
        $prompt .= "- En español neutro\n";
        $prompt .= "- Sin listas numeradas o bullets formales\n";

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
