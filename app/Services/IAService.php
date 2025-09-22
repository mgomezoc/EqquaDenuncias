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
            // Log explícito si falta la API key
            log_message('error', '[IAService] OPENAI_API_KEY no configurada en .env');
            throw new Exception('API Key de OpenAI no configurada en el archivo .env');
        }
    }

    /**
     * Genera una sugerencia de solución (una sola respuesta, accionable)
     */
    public function generarSugerenciaSolucion(array $denunciaData): array
    {
        try {
            $prompt = $this->construirPrompt($denunciaData);

            $postData = [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un consultor senior en RRHH, seguridad y compliance. Entregas planes accionables, claros, cronológicos y medibles. Evitas teoría general; das instrucciones concretas aplicables en el sitio de trabajo.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.4
            ];

            log_message('debug', '[IAService] Solicitando a OpenAI. Campos denuncia: {keys}', ['keys' => implode(',', array_keys($denunciaData))]);

            $response = $this->realizarPeticionAPI($postData);

            if ($response && isset($response['choices'][0]['message']['content'])) {
                $sugerencia   = trim($response['choices'][0]['message']['content']);
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

    /**
     * Construye un prompt que obliga a una ÚNICA respuesta, tipo plan accionable.
     * Usa solo los campos que ya tienes en la BD/joins actuales.
     */
    private function construirPrompt(array $d): string
    {
        // Inferencia simple de tipología (no se guarda en BD, solo para enriquecer el prompt)
        $desc = mb_strtolower($d['descripcion'] ?? '');
        $tipologia = 'incidente laboral';
        $map = [
            'nalgad' => 'acoso sexual físico',
            'tocó' => 'acoso sexual físico',
            'toque' => 'acoso sexual físico',
            'manose' => 'acoso sexual físico',
            'golpe' => 'agresión física',
            'empuj' => 'agresión física',
            'insult' => 'acoso verbal',
            'grit' => 'acoso verbal',
            'amenaz' => 'amenaza'
        ];
        foreach ($map as $needle => $label) {
            if (mb_strpos($desc, $needle) !== false) {
                $tipologia = $label;
                break;
            }
        }

        // Campos con fallback legible
        $folio         = $d['folio']               ?? 'N/A';
        $tipoDen       = $d['tipo_denunciante']    ?? 'N/A';
        $cat           = $d['categoria_nombre']    ?? 'Sin categoría asignada';
        $subcat        = $d['subcategoria_nombre'] ?? 'N/A';
        $depto         = $d['departamento_nombre'] ?? 'N/A';
        $sucursal      = $d['sucursal_nombre']     ?? 'N/A';
        $area          = $d['area_incidente']      ?? 'N/A';
        $fechaInc      = $d['fecha_incidente']     ?? 'N/A';
        $comoEnt       = $d['como_se_entero']      ?? 'N/A';
        $denunciado    = $d['denunciar_a_alguien'] ?? 'N/A';
        $descripcion   = $d['descripcion']         ?? 'N/A';

        // Prompt: una sola propuesta de resolución, con secciones exigidas
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

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
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
     * Validación mínima: con que haya descripción basta (flujo público puede no tener categoría aún)
     */
    public function validarDatosMinimos(array $data): bool
    {
        return !empty($data['descripcion']);
    }

    /**
     * Estimación costo por tokens (gpt-4o referencia)
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
}
