<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService
 * 
 * Servicio para generar PDFs profesionales de los reportes generados por IA
 * 
 * @author Cesar M Gomez M
 * @version 1.0
 */
class PDFReporteService
{
    private Dompdf $dompdf;
    private string $outputDir;

    public function __construct()
    {
        // Configurar Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', FCPATH);

        $this->dompdf = new Dompdf($options);

        // Directorio para guardar PDFs
        $this->outputDir = 'uploads/reportes_ia/pdfs/';

        // Crear directorio si no existe
        $fullPath = FCPATH . $this->outputDir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    /**
     * Genera un PDF a partir de un reporte
     * 
     * @param array $reporte Datos completos del reporte
     * @return string|false Ruta relativa del PDF generado o false si falla
     */
    public function generarPDF(array $reporte)
    {
        try {
            // Generar HTML del reporte
            $html = $this->generarHTML($reporte);

            // Cargar HTML en Dompdf
            $this->dompdf->loadHtml($html);

            // Configurar tamaño de página
            $this->dompdf->setPaper('letter', 'portrait');

            // Renderizar PDF
            $this->dompdf->render();

            // Generar nombre de archivo único
            $filename = $this->generarNombreArchivo($reporte);
            $rutaCompleta = FCPATH . $this->outputDir . $filename;

            // Guardar PDF
            file_put_contents($rutaCompleta, $this->dompdf->output());

            // Retornar ruta relativa
            return $this->outputDir . $filename;
        } catch (\Throwable $e) {
            log_message('error', '[PDFReporteService] Error al generar PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera el nombre del archivo PDF
     */
    private function generarNombreArchivo(array $reporte): string
    {
        $id = $reporte['id'] ?? 'sin-id';
        $fecha = date('Y-m-d_H-i-s');
        $periodo = $this->sanitizeFilename($reporte['periodo_nombre'] ?? 'reporte');

        return "reporte_ia_{$id}_{$periodo}_{$fecha}.pdf";
    }

    /**
     * Sanitiza el nombre del archivo
     */
    private function sanitizeFilename(string $filename): string
    {
        // Convertir a minúsculas y reemplazar espacios con guiones
        $filename = strtolower($filename);
        $filename = preg_replace('/[^a-z0-9\-_]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');

        return substr($filename, 0, 50); // Limitar longitud
    }

    /**
     * Genera el HTML completo del reporte
     */
    private function generarHTML(array $reporte): string
    {
        $cliente = $reporte['cliente_nombre'] ?? 'Cliente';
        $periodo = $reporte['periodo_nombre'] ?? 'Sin periodo';
        $tipo = ucfirst($reporte['tipo_reporte'] ?? 'Reporte');
        $fechaGeneracion = date('d/m/Y H:i', strtotime($reporte['created_at'] ?? 'now'));
        $riesgo = $reporte['puntuacion_riesgo'] ?? 'N/D';

        // Colores según configuración o por defecto
        $colorPrimario = '#FF6B35';
        $colorSecundario = '#004E89';

        // Construir HTML
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte IA - {$periodo}</title>
    <style>
        {$this->getCSS($colorPrimario,$colorSecundario)}
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <div class="logo">
            <img src="{$this->getLogoPath($reporte)}" alt="Logo" style="max-height: 60px;">
        </div>
        <div class="header-info">
            <h1>Reporte de Análisis de Denuncias</h1>
            <h2>{$tipo} - {$periodo}</h2>
        </div>
    </div>

    <!-- Información del cliente -->
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td><strong>Cliente:</strong></td>
                <td>{$cliente}</td>
                <td><strong>Periodo:</strong></td>
                <td>{$periodo}</td>
            </tr>
            <tr>
                <td><strong>Tipo:</strong></td>
                <td>{$tipo}</td>
                <td><strong>Riesgo:</strong></td>
                <td><span class="badge badge-{$this->getRiesgoBadgeClass($riesgo)}">{$riesgo}/10</span></td>
            </tr>
            <tr>
                <td><strong>Generado:</strong></td>
                <td>{$fechaGeneracion}</td>
                <td><strong>Estado:</strong></td>
                <td><span class="badge badge-{$reporte['estado']}">{$reporte['estado']}</span></td>
            </tr>
        </table>
    </div>

    <!-- Resumen Ejecutivo -->
    <div class="section">
        <h3 class="section-title">Resumen Ejecutivo</h3>
        <div class="content">
            {$this->formatearTexto($reporte['resumen_ejecutivo'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Hallazgos Principales -->
    <div class="section">
        <h3 class="section-title">Hallazgos Principales</h3>
        <div class="content">
            {$this->formatearTexto($reporte['hallazgos_principales'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Análisis Geográfico -->
    <div class="section">
        <h3 class="section-title">Análisis Geográfico (Por Sucursales)</h3>
        <div class="content">
            {$this->formatearTexto($reporte['analisis_geografico'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Análisis Categórico -->
    <div class="section">
        <h3 class="section-title">Análisis por Categorías</h3>
        <div class="content">
            {$this->formatearTexto($reporte['analisis_categorico'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Eficiencia Operativa -->
    <div class="section">
        <h3 class="section-title">Eficiencia Operativa</h3>
        <div class="content">
            {$this->formatearTexto($reporte['eficiencia_operativa'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Sugerencias Predictivas -->
    <div class="section alert">
        <h3 class="section-title">Sugerencias Proactivas y Predictivas</h3>
        <p class="alert-text">⚠️ Este contenido fue generado por IA y debe revisarse antes de aplicarse.</p>
        <div class="content">
            {$this->formatearTexto($reporte['sugerencias_predictivas'] ?? 'Sin contenido')}
        </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p>Reporte generado con Inteligencia Artificial (GPT-4o)</p>
        <p>Fecha de generación: {$fechaGeneracion}</p>
        <p>ID Reporte: {$reporte['id']}</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Retorna el CSS del PDF
     */
    private function getCSS(string $colorPrimario, string $colorSecundario): string
    {
        return <<<CSS
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.6;
    color: #333;
    padding: 20px;
}

.header {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 3px solid {$colorPrimario};
    padding-bottom: 20px;
}

.header h1 {
    color: {$colorSecundario};
    font-size: 22pt;
    margin-bottom: 5px;
}

.header h2 {
    color: {$colorPrimario};
    font-size: 16pt;
    font-weight: normal;
}

.logo img {
    max-height: 60px;
    margin-bottom: 15px;
}

.info-box {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 25px;
    border-left: 4px solid {$colorPrimario};
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 8px;
    vertical-align: top;
}

.info-table td:nth-child(odd) {
    width: 20%;
    font-weight: bold;
}

.section {
    margin-bottom: 25px;
    page-break-inside: avoid;
}

.section-title {
    background-color: {$colorSecundario};
    color: white;
    padding: 10px 15px;
    font-size: 14pt;
    margin-bottom: 15px;
    border-radius: 3px;
}

.content {
    padding: 0 10px;
    text-align: justify;
}

.alert {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
}

.alert-text {
    color: #856404;
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 10pt;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 9pt;
    font-weight: bold;
    text-transform: uppercase;
}

.badge-success { background-color: #28a745; color: white; }
.badge-warning { background-color: #ffc107; color: #333; }
.badge-danger { background-color: #dc3545; color: white; }
.badge-generado { background-color: #6c757d; color: white; }
.badge-revisado { background-color: #17a2b8; color: white; }
.badge-publicado { background-color: #28a745; color: white; }
.badge-archivado { background-color: #343a40; color: white; }

.footer {
    margin-top: 50px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
    text-align: center;
    font-size: 9pt;
    color: #666;
}

.footer p {
    margin: 5px 0;
}

/* Saltos de página */
.page-break {
    page-break-after: always;
}
CSS;
    }

    /**
     * Obtiene la ruta del logo del cliente
     */
    private function getLogoPath(array $reporte): string
    {
        // Si el cliente tiene logo, usarlo
        if (!empty($reporte['cliente_logo'])) {
            $logoPath = FCPATH . 'uploads/clientes/' . $reporte['cliente_logo'];
            if (file_exists($logoPath)) {
                return $logoPath;
            }
        }

        // Logo por defecto de Eqqua
        $logoDefault = FCPATH . 'assets/images/logo_eqqua.png';
        if (file_exists($logoDefault)) {
            return $logoDefault;
        }

        // Sin logo
        return '';
    }

    /**
     * Obtiene la clase de badge según el nivel de riesgo
     */
    private function getRiesgoBadgeClass($riesgo): string
    {
        if ($riesgo === 'N/D' || !is_numeric($riesgo)) {
            return 'secondary';
        }

        $riesgo = (int) $riesgo;

        if ($riesgo >= 7) {
            return 'danger';
        } elseif ($riesgo >= 4) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Formatea el texto para HTML
     */
    private function formatearTexto(?string $texto): string
    {
        if (empty($texto)) {
            return '<p class="text-muted">Sin contenido</p>';
        }

        // Convertir saltos de línea a párrafos
        $texto = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));

        // Convertir listas con guiones o asteriscos
        $texto = preg_replace('/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $texto);
        $texto = preg_replace('/(<li>.*<\/li>(\s|<br\s*\/?>)*)+/s', '<ul>$0</ul>', $texto);

        // Envolver en párrafos si no tiene etiquetas
        if (strpos($texto, '<') === false) {
            $texto = '<p>' . $texto . '</p>';
        }

        return $texto;
    }
}
