<?php

namespace App\Services;

use Exception;

class IAService
{
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY');

        if (!$this->apiKey) {
            // Importante: log explícito si falta la API key
            log_message('error', '[IAService] OPENAI_API_KEY no configurada en .env');
            throw new Exception('API Key de OpenAI no configurada en el archivo .env');
        }
    }

    public function generarSugerenciaSolucion(array $denunciaData): array
    {
        try {
            $prompt = $this->construirPrompt($denunciaData);

            $postData = [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un experto consultor en recursos humanos y compliance empresarial. Tu trabajo es analizar denuncias laborales y proporcionar sugerencias de solución profesionales, constructivas y enfocadas en la resolución de conflictos.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7
            ];

            log_message('debug', '[IAService] Solicitando a OpenAI. Campos denuncia: {keys}', ['keys' => implode(',', array_keys($denunciaData))]);

            $response = $this->realizarPeticionAPI($postData);

            if ($response && isset($response['choices'][0]['message']['content'])) {
                $sugerencia  = trim($response['choices'][0]['message']['content']);
                $tokensUsados = (int)($response['usage']['total_tokens'] ?? 0);

                log_message('debug', '[IAService] Respuesta OpenAI tokens:{t} len:{l}', ['t' => $tokensUsados, 'l' => strlen($sugerencia)]);

                return [
                    'success'       => true,
                    'sugerencia'    => $sugerencia,
                    'tokens_usados' => $tokensUsados
                ];
            }

            log_message('warning', '[IAService] Respuesta sin contenido utilizable');
            return ['success' => false, 'error' => 'No se pudo generar la sugerencia'];
        } catch (Exception $e) {
            log_message('error', '[IAService] Excepción generarSugerenciaSolucion: {err}', ['err' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error interno del servicio de IA'];
        }
    }

    private function construirPrompt(array $data): string
    {
        // Usa placeholders si faltan datos (p.ej. flujo público sin categoría)
        $prompt  = "Analiza la siguiente denuncia y proporciona una sugerencia de solución profesional:\n\n";
        $prompt .= "**INFORMACIÓN DE LA DENUNCIA:**\n";
        $prompt .= "- Folio: " . ($data['folio'] ?? 'N/A') . "\n";
        $prompt .= "- Tipo de denunciante: " . ($data['tipo_denunciante'] ?? 'N/A') . "\n";
        $prompt .= "- Categoría: " . ($data['categoria_nombre'] ?? 'Sin categoría asignada') . "\n";
        $prompt .= "- Subcategoría: " . ($data['subcategoria_nombre'] ?? 'N/A') . "\n";
        $prompt .= "- Departamento afectado: " . ($data['departamento_nombre'] ?? 'N/A') . "\n";
        $prompt .= "- Sucursal: " . ($data['sucursal_nombre'] ?? 'N/A') . "\n";
        $prompt .= "- Área del incidente: " . ($data['area_incidente'] ?? 'N/A') . "\n";
        $prompt .= "- Fecha del incidente: " . ($data['fecha_incidente'] ?? 'N/A') . "\n";
        $prompt .= "- Descripción: " . ($data['descripcion'] ?? 'N/A') . "\n\n";

        $prompt .= "**SOLICITUD:**\n";
        $prompt .= "Basándote en la información proporcionada, genera una sugerencia de solución que incluya:\n";
        $prompt .= "1. **Acciones inmediatas** a tomar\n";
        $prompt .= "2. **Investigación recomendada** (qué aspectos investigar)\n";
        $prompt .= "3. **Medidas preventivas** para evitar situaciones similares\n";
        $prompt .= "4. **Seguimiento sugerido** para monitorear la resolución\n\n";

        $prompt .= "**IMPORTANTE:**\n";
        $prompt .= "- La sugerencia debe ser profesional y constructiva\n";
        $prompt .= "- Enfócate en resolver el conflicto, no en culpar\n";
        $prompt .= "- Considera las mejores prácticas de recursos humanos\n";
        $prompt .= "- Mantén un tono neutral y objetivo\n";
        $prompt .= "- La respuesta debe ser en español\n";
        $prompt .= "- Máximo 500 palabras\n";

        return $prompt;
    }

    private function realizarPeticionAPI(array $postData): ?array
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', '[IAService] cURL error: {err}', ['err' => $error]);
            throw new Exception('Error cURL: ' . $error);
        }

        if ($httpCode !== 200) {
            log_message('error', '[IAService] HTTP {code} cuerpo:{body}', ['code' => $httpCode, 'body' => $response]);
            throw new Exception('Error HTTP: ' . $httpCode . ' - ' . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Ahora solo exigimos descripción (categoría opcional para flujo público)
     */
    public function validarDatosMinimos(array $data): bool
    {
        if (empty($data['descripcion'])) {
            return false;
        }
        return true;
    }

    public function calcularCostoEstimado(int $tokens): float
    {
        $precioInput  = 5.00;   // $/1M tokens
        $precioOutput = 15.00;  // $/1M tokens

        $tokensInput  = $tokens * 0.7;
        $tokensOutput = $tokens * 0.3;

        $costoInput  = ($tokensInput / 1_000_000) * $precioInput;
        $costoOutput = ($tokensOutput / 1_000_000) * $precioOutput;

        return $costoInput + $costoOutput;
    }
}
