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

        // Lee configuración desde .env con defaults orientados a respuesta breve y natural
        $this->modelo      = (string) (getenv('IA_MODELO_USADO') ?: 'gpt-4o');
        $this->maxTokens   = (int)    (getenv('IA_MAX_TOKENS') ?: 350); // antes 800
        $this->temperature = (float)  (getenv('IA_TEMPERATURE') ?: 0.6); // antes 0.4
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
                        // Tono conversacional y práctico; evita burocracia y listas largas
                        'content' => 'Eres un asistente empático y claro. Das consejos breves, prácticos y realistas en un tono humano y respetuoso. Evitas listas largas, jerga y lenguaje burocrático. Prefieres 1–2 párrafos y, si aporta, hasta 3 ideas puntuales.'
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
     * Prompt conversacional: pide consejo breve y natural (no plan ni secciones).
     */
    private function construirPrompt(array $d): string
    {
        // Inferencia simple de tipología para dar contexto, con mapeo ampliado
        $desc = mb_strtolower($d['descripcion'] ?? '');
        $tipologia = 'situación laboral';
        $map = [
            'nalgad' => 'acoso físico',
            'tocó'   => 'contacto inapropiado',
            'toque'  => 'contacto inapropiado',
            'manose' => 'acoso físico',
            'golpe'  => 'agresión',
            'empuj'  => 'agresión',
            'insult' => 'trato irrespetuoso',
            'grit'   => 'trato irrespetuoso',
            'amenaz' => 'intimidación',
            'celular' => 'falta de atención al cliente',
            'jugando' => 'distracción en el trabajo',
            'no me hacia caso' => 'falta de atención al cliente',
            'no me hacía caso' => 'falta de atención al cliente',
        ];
        foreach ($map as $needle => $label) {
            if ($needle !== '' && mb_strpos($desc, $needle) !== false) {
                $tipologia = $label;
                break;
            }
        }

        // Campos con fallback legible
        $folio       = $d['folio']               ?? 'Sin folio';
        $tipoDen     = $d['tipo_denunciante']    ?? 'No especificado';
        $cat         = $d['categoria_nombre']    ?? 'Sin categorizar';
        $subcat      = $d['subcategoria_nombre'] ?? 'N/A';
        $depto       = $d['departamento_nombre'] ?? 'No especificado';
        $sucursal    = $d['sucursal_nombre']     ?? 'No especificada';
        $area        = $d['area_incidente']      ?? 'área no especificada';
        $fechaInc    = $d['fecha_incidente']     ?? 'fecha no especificada';
        $comoEnt     = $d['como_se_entero']      ?? 'no especificado';
        $denunciado  = $d['denunciar_a_alguien'] ?? 'persona no identificada';
        $descripcion = $d['descripcion']         ?? 'Sin descripción';

        // Prompt natural y personal. Permite (opcional) hasta 3 bullets cortos si aporta.
        $prompt = <<<TXT
Te pido un consejo breve y natural (no un plan formal) sobre un caso real del trabajo. 
Piensa como un compañero con experiencia que quiere ayudar sin burocracia.

Contexto:
- Folio: {$folio}
- Denunciante: {$tipoDen}
- Sucursal/Área: {$sucursal} / {$area}
- Departamento: {$depto}
- Categoría/Subcategoría: {$cat} / {$subcat}
- Fecha del incidente: {$fechaInc}
- Cómo se enteró: {$comoEnt}
- Persona involucrada: {$denunciado}
- Tipología aproximada: {$tipologia}
- Descripción de la persona: "{$descripcion}"

Qué necesito exactamente:
- Una sola respuesta, en tono humano y empático.
- 1–2 párrafos claros con una sugerencia realista de qué podría hacerse.
- Si agrega valor, incluye hasta 3 ideas puntuales como viñetas (máximo 3 bullets).
- Evita pasos cronológicos, KPIs, secciones numeradas o lenguaje legal/burocrático.
- Mantente breve (~160–220 palabras), español neutro.
TXT;

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
