<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService - Versión Final
 * 
 * Versión 2.1 - Mejoras:
 * - Márgenes más amplios (menos pegado a los bordes)
 * - Generación automática de gráficas desde array metricas
 * - Paleta corporativa Eqqua
 * - Diseño profesional con Raleway
 */
class PDFReporteService
{
    private Dompdf $dompdf;
    private string $outputDir;

    // Paleta de colores corporativos Eqqua
    private const COLOR_PRIMARY = '#004E89';      // Azul oscuro principal
    private const COLOR_SECONDARY = '#1CBEC6';    // Turquesa/Verde agua
    private const COLOR_ACCENT = '#FFB703';       // Amarillo/Naranja
    private const COLOR_DANGER = '#E84855';       // Rojo/Rosa
    private const COLOR_TEXT = '#2c3e50';         // Texto oscuro
    private const COLOR_TEXT_LIGHT = '#6c757d';   // Texto secundario

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'Raleway');
        $options->set('defaultMediaType', 'print');
        $options->set('isCssFloatEnabled', true);
        $options->set('dpi', 96);
        $options->set('chroot', FCPATH);

        $this->dompdf = new Dompdf($options);

        $this->outputDir = 'uploads/reportes_ia/pdfs/';
        $full = FCPATH . $this->outputDir;
        if (!is_dir($full)) {
            @mkdir($full, 0755, true);
        }
    }

    /** Genera el PDF y retorna la ruta relativa del archivo */
    public function generarPDF(array $reporte)
    {
        try {
            // Generar gráficas automáticamente si hay métricas
            if (!isset($reporte['charts']) && isset($reporte['metricas'])) {
                $reporte['charts'] = $this->generarGraficasDesdeMetricas($reporte['metricas'], $reporte);
            }

            $html = $this->generarHTML($reporte);

            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('letter', 'portrait');
            $this->dompdf->render();

            // Numeración real de páginas
            $this->agregarNumeracionPaginas((string)($reporte['id'] ?? ''));

            $filename = $this->generarNombreArchivo($reporte);
            $path = FCPATH . $this->outputDir . $filename;

            file_put_contents($path, $this->dompdf->output());
            return $this->outputDir . $filename;
        } catch (\Throwable $e) {
            log_message('error', '[PDFReporteService] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera gráficas automáticamente desde el array de métricas
     */
    private function generarGraficasDesdeMetricas(array $metricas, array $reporte): array
    {
        $charts = [];

        // Gráfica 1: Distribución por Categoría (Donut)
        if (!empty($metricas['distribucion_categoria'])) {
            $dataCategoria = [];
            $count = 0;
            foreach ($metricas['distribucion_categoria'] as $item) {
                if ($count >= 6) break; // Máximo 6 categorías para legibilidad
                $dataCategoria[$item['categoria']] = (int)$item['total'];
                $count++;
            }

            if (!empty($dataCategoria)) {
                $charts[] = [
                    'tipo' => 'donut',
                    'titulo' => 'Distribución por Categoría',
                    'leyenda' => 'Total de denuncias: ' . array_sum($dataCategoria),
                    'data' => $dataCategoria
                ];
            }
        }

        // Gráfica 2: Nivel de Riesgo (Gauge)
        if (isset($reporte['puntuacion_riesgo'])) {
            $charts[] = [
                'tipo' => 'gauge',
                'titulo' => 'Nivel de Riesgo Global',
                'leyenda' => 'Evaluación del período analizado',
                'data' => [
                    'valor' => floatval($reporte['puntuacion_riesgo']),
                    'max' => 10
                ]
            ];
        }

        // Gráfica 3: Top 5 Sucursales (Barras)
        if (!empty($metricas['distribucion_sucursal'])) {
            $dataSucursal = [];
            $count = 0;
            foreach ($metricas['distribucion_sucursal'] as $item) {
                if ($count >= 5) break; // Top 5
                $dataSucursal[$item['sucursal']] = (int)$item['total'];
                $count++;
            }

            if (!empty($dataSucursal)) {
                $charts[] = [
                    'tipo' => 'bar',
                    'titulo' => 'Top 5 Sucursales',
                    'leyenda' => 'Sucursales con mayor número de denuncias',
                    'data' => $dataSucursal
                ];
            }
        }

        // Gráfica 4: Canales de Reporte (Donut)
        if (!empty($metricas['distribucion_medio'])) {
            $dataMedio = [];
            foreach ($metricas['distribucion_medio'] as $item) {
                $dataMedio[$item['medio']] = (int)$item['total'];
            }

            if (!empty($dataMedio)) {
                $charts[] = [
                    'tipo' => 'donut',
                    'titulo' => 'Canales de Reporte',
                    'leyenda' => 'Medios utilizados para reportar',
                    'data' => $dataMedio
                ];
            }
        }

        // Gráfica 5: Top 5 Departamentos (Barras)
        if (!empty($metricas['distribucion_departamento'])) {
            $dataDepartamento = [];
            $count = 0;
            foreach ($metricas['distribucion_departamento'] as $item) {
                if ($count >= 5) break; // Top 5
                $dataDepartamento[$item['departamento']] = (int)$item['total'];
                $count++;
            }

            if (!empty($dataDepartamento)) {
                $charts[] = [
                    'tipo' => 'bar',
                    'titulo' => 'Top 5 Departamentos',
                    'leyenda' => 'Departamentos con más incidencias',
                    'data' => $dataDepartamento
                ];
            }
        }

        // Gráfica 6: Tasa de Resolución (Gauge)
        if (isset($metricas['indice_resolucion'])) {
            $charts[] = [
                'tipo' => 'gauge',
                'titulo' => 'Tasa de Resolución',
                'leyenda' => 'Porcentaje de denuncias cerradas',
                'data' => [
                    'valor' => floatval($metricas['indice_resolucion']) / 10, // Convertir 97.7% a escala de 10
                    'max' => 10
                ]
            ];
        }

        return $charts;
    }

    /** Footer con numeración de páginas e ID */
    private function agregarNumeracionPaginas(string $id): void
    {
        $canvas = $this->dompdf->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($id) {
            $left   = "Eqqua · Reporte IA";
            $center = "ID: " . ($id ?: 'N/D');
            $right  = "Página {$pageNumber} de {$pageCount}";

            $font = $fontMetrics->getFont('Raleway', 'normal');
            $size = 8;
            $y    = $canvas->get_height() - 28;
            $color = [0.4, 0.4, 0.4];

            // izquierda
            $canvas->text(36, $y, $left, $font, $size, $color);
            // centro
            $wCenter = $fontMetrics->getTextWidth($center, $font, $size);
            $canvas->text(($canvas->get_width() - $wCenter) / 2, $y, $center, $font, $size, $color);
            // derecha
            $wRight = $fontMetrics->getTextWidth($right, $font, $size);
            $canvas->text($canvas->get_width() - 36 - $wRight, $y, $right, $font, $size, $color);
        });
    }

    private function generarNombreArchivo(array $reporte): string
    {
        $id     = $reporte['id'] ?? 'sin-id';
        $fecha  = date('Y-m-d_H-i-s');
        $periodo = $this->sanitizeFilename($reporte['periodo_nombre'] ?? 'reporte');
        return "reporte_ia_{$id}_{$periodo}_{$fecha}.pdf";
    }

    private function sanitizeFilename(string $f): string
    {
        $f = strtolower($f);
        $f = preg_replace('/[^a-z0-9\-_]/', '-', $f);
        $f = preg_replace('/-+/', '-', $f);
        return trim(substr($f, 0, 60), '-');
    }

    /** Devuelve data URI base64 para una imagen relativa a FCPATH */
    private function embedImage(string $relativePath): ?string
    {
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($full)) return null;

        $data = @file_get_contents($full);
        if ($data === false) return null;

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** HTML completo del reporte */
    private function generarHTML(array $reporte): string
    {
        $cliente         = $reporte['cliente_nombre'] ?? 'Cliente';
        $periodo         = $reporte['periodo_nombre'] ?? 'Sin periodo';
        $tipo            = ucfirst($reporte['tipo_reporte'] ?? 'Reporte');
        $fechaGeneracion = date('d/m/Y H:i', strtotime($reporte['created_at'] ?? 'now'));
        $riesgo          = $reporte['puntuacion_riesgo'] ?? 'N/D';
        $estado          = $this->formatearEstado($reporte['estado'] ?? 'generado');

        // Logo
        $logoData = $this->resolveLogoDataUri($reporte);
        $logoHtml = $this->renderLogoData($logoData);

        // Íconos de secciones
        $iconResumen    = $this->embedImage('assets/icons/resumen.png');
        $iconHallazgos  = $this->embedImage('assets/icons/hallazgo.png');
        $iconEficiencia = $this->embedImage('assets/icons/eficiencia.png');

        $icoResumenHtml    = $iconResumen    ? "<img src=\"{$iconResumen}\" class=\"icon-img\">"    : '📊';
        $icoHallazgosHtml  = $iconHallazgos  ? "<img src=\"{$iconHallazgos}\" class=\"icon-img\">"  : '🔍';
        $icoEficienciaHtml = $iconEficiencia ? "<img src=\"{$iconEficiencia}\" class=\"icon-img\">" : '⚡';

        // Badge de riesgo
        $riesgoBadgeClass = $this->getRiesgoBadgeClass($riesgo);
        $riesgoFormatted  = $this->formatRiesgo($riesgo);

        // Gráficas
        $htmlCharts = $this->generarSeccionGraficas($reporte);

        // CSS con márgenes aumentados
        $css = $this->getCSS();

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Análisis de Denuncias - {$cliente}</title>
    <style>{$css}</style>
</head>
<body>

<!-- Header con logo y título -->
<table class="header">
  <tr>
    <td class="logo-cell">{$logoHtml}</td>
    <td class="title-cell">
      <div class="title">Reporte de Análisis de Denuncias</div>
      <div class="subtitle">{$tipo} · {$periodo}</div>
    </td>
  </tr>
</table>

<div class="divider"></div>

<!-- Información del reporte -->
<div class="info">
  <table class="info-table">
    <tr>
      <td class="label">Cliente:</td>
      <td class="value"><strong>{$cliente}</strong></td>
      <td class="label">Período:</td>
      <td class="value">{$periodo}</td>
    </tr>
    <tr>
      <td class="label">Tipo de reporte:</td>
      <td class="value">{$tipo}</td>
      <td class="label">Nivel de riesgo:</td>
      <td class="value"><span class="badge {$riesgoBadgeClass}">{$riesgoFormatted}</span></td>
    </tr>
    <tr>
      <td class="label">Generado:</td>
      <td class="value">{$fechaGeneracion}</td>
      <td class="label">Estado:</td>
      <td class="value"><span class="badge estado">{$estado}</span></td>
    </tr>
  </table>
</div>

{$this->seccion('Resumen ejecutivo',$reporte['resumen_ejecutivo'] ?? null,$icoResumenHtml)}
{$this->seccion('Hallazgos principales',$reporte['hallazgos_principales'] ?? null,$icoHallazgosHtml)}
{$this->seccion('Eficiencia operativa',$reporte['eficiencia_operativa'] ?? null,$icoEficienciaHtml)}

{$htmlCharts}

<div class="section alert">
  <div class="section-head alert-head">⚠ Sugerencias proactivas y predictivas</div>
  <div class="alert-note">Este contenido fue generado por Inteligencia Artificial (GPT-4o). Se recomienda revisarlo antes de su aplicación.</div>
  <div class="section-body">{$this->formatearTexto($reporte['sugerencias_predictivas'] ?? null)}</div>
</div>

</body>
</html>
HTML;
    }

    private function getCSS(): string
    {
        $c1   = self::COLOR_PRIMARY;
        $c2   = self::COLOR_SECONDARY;
        $c3   = self::COLOR_ACCENT;
        $cTxt = self::COLOR_TEXT;
        $cTxtLight = self::COLOR_TEXT_LIGHT;

        return <<<CSS
@import url('https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap');

@page { 
    margin: 2.5cm 2cm 3cm 2cm;
}

* { 
    box-sizing: border-box; 
    margin: 0;
    padding: 0;
}

body { 
    font-family: 'Raleway', 'Arial', sans-serif; 
    font-size: 10.5pt; 
    color: {$cTxt};
    line-height: 1.6;
    font-weight: 400;
}

/* Header */
.header { 
    width: 100%; 
    border-collapse: collapse; 
    margin-bottom: 8px;
}

.logo-cell { 
    width: 192px; 
    vertical-align: middle; 
    padding-right: 20px;
}

.title-cell { 
    vertical-align: middle; 
    text-align: left; 
}

.logo img { 
    max-width: 160px; 
    max-height: 60px; 
    display: block;
}

.title { 
    font-size: 22pt; 
    color: {$c1}; 
    font-weight: 700; 
    line-height: 1.2; 
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}

.subtitle { 
    font-size: 13pt; 
    color: {$c2}; 
    font-weight: 500;
}

/* Divider */
.divider { 
    height: 4px; 
    margin: 14px 0 20px; 
    background: linear-gradient(90deg, {$c1} 0%, {$c2} 50%, {$c3} 100%);
    border-radius: 2px;
}

/* Info */
.info { 
    background: #f8f9fa; 
    border-left: 5px solid {$c2}; 
    padding: 16px 18px; 
    margin-bottom: 24px;
    border-radius: 4px;
}

.info-table { 
    width: 100%; 
    border-collapse: collapse; 
}

.info-table .label { 
    width: 22%; 
    color: {$cTxtLight}; 
    font-weight: 600; 
    padding: 7px 12px 7px 0; 
    font-size: 9pt;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-table .value { 
    width: 28%; 
    padding: 7px 12px; 
    font-size: 10pt;
    font-weight: 500;
}

/* Badges */
.badge { 
    display: inline-block; 
    padding: 5px 12px; 
    border-radius: 4px; 
    font-size: 8.5pt; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 0.5px;
}

.badge.success { 
    background: #10b981; 
    color: #fff; 
}

.badge.warning { 
    background: {$c3}; 
    color: #000; 
}

.badge.danger { 
    background: {self::COLOR_DANGER}; 
    color: #fff; 
}

.badge.secondary { 
    background: #6c757d; 
    color: #fff; 
}

.badge.estado { 
    background: {$c2}; 
    color: #fff; 
}

/* Secciones */
.section { 
    margin: 22px 0; 
    page-break-inside: avoid; 
}

.section-head { 
    background: {$c1}; 
    color: #fff; 
    padding: 12px 16px; 
    font-weight: 700; 
    font-size: 11pt;
    border-radius: 4px;
    letter-spacing: 0.3px;
}

.section-body { 
    padding: 14px 16px; 
    text-align: justify; 
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 4px 4px;
}

.section-body p { 
    margin: 0 0 12px;
    line-height: 1.7;
}

.section-body p:last-child {
    margin-bottom: 0;
}

.section-body ul { 
    margin: 10px 0 10px 20px;
    list-style-type: disc;
}

.section-body li { 
    margin-bottom: 8px;
    line-height: 1.6;
}

.section-body strong {
    font-weight: 700;
    color: {$c1};
}

/* Alerta */
.alert { 
    background: #fffbf0; 
    border: 2px solid {$c3};
    border-radius: 4px;
}

.alert-head { 
    background: {$c3}; 
    color: #000;
    font-weight: 700;
}

.alert-note { 
    background: #fff3cd; 
    color: #856404; 
    padding: 10px 16px; 
    font-size: 9pt; 
    border-left: 4px solid #ffc107;
    font-weight: 500;
    font-style: italic;
}

/* Íconos */
.section-head .icon-img {
    width: 13pt;
    height: 13pt;
    margin-right: 8px;
    vertical-align: -2px;
    filter: brightness(3) invert(1) contrast(1.1);
}

/* Gráficas */
.charts-section {
    margin: 26px 0;
    page-break-inside: avoid;
}

.charts-header {
    background: {$c2};
    color: #fff;
    padding: 12px 16px;
    font-weight: 700;
    font-size: 11pt;
    border-radius: 4px 4px 0 0;
    letter-spacing: 0.3px;
}

.charts-container {
    background: #f8f9fa;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 4px 4px;
}

.chart-grid { 
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 16px;
}

.chart-cell { 
    width: 50%; 
    vertical-align: top; 
}

.chart-box { 
    background: #ffffff;
    border: 2px solid #e9ecef; 
    border-radius: 6px; 
    padding: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.chart-title { 
    font-weight: 700; 
    color: {$c1}; 
    margin-bottom: 12px; 
    font-size: 10.5pt;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.chart-subtitle {
    font-size: 8.5pt;
    color: {$cTxtLight};
    text-align: center;
    margin-top: 8px;
    font-style: italic;
}

.center { 
    text-align: center; 
}

.text-muted {
    color: {$cTxtLight};
    font-style: italic;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    color: {$c1};
    margin-bottom: 10px;
}
CSS;
    }

    private function resolveLogoDataUri(array $reporte): ?string
    {
        if (!empty($reporte['cliente_logo'])) {
            $data = $this->embedImage($reporte['cliente_logo']);
            if ($data) return $data;
        }

        $candidates = [
            'assets/images/logo.png',
            'assets/images/eqqua logos-09.png',
            'assets/images/eqqua logos-05.png',
            'assets/images/logo_eqqua.png',
        ];
        foreach ($candidates as $rel) {
            $data = $this->embedImage($rel);
            if ($data) return $data;
        }
        return null;
    }

    private function renderLogoData(?string $data): string
    {
        if ($data) {
            return '<div class="logo"><img src="' . $data . '" alt="Logo"></div>';
        }
        return '<div class="logo" style="font-weight:700;color:' . self::COLOR_PRIMARY . ';font-size:18pt;">EQQUA</div>';
    }

    private function getRiesgoBadgeClass($riesgo): string
    {
        if ($riesgo === 'N/D' || !is_numeric($riesgo)) return 'secondary';
        $r = (float) $riesgo;
        if ($r >= 7) return 'danger';
        if ($r >= 4) return 'warning';
        return 'success';
    }

    private function formatRiesgo($riesgo): string
    {
        if (!is_numeric($riesgo)) return 'N/D';
        return number_format((float)$riesgo, 1) . '/10';
    }

    private function formatearEstado(string $estado): string
    {
        $map = [
            'generado' => 'Generado',
            'revisado' => 'Revisado',
            'publicado' => 'Publicado',
            'archivado' => 'Archivado'
        ];
        return $map[strtolower($estado)] ?? ucfirst($estado);
    }

    private function seccion(string $titulo, ?string $contenido, string $icon = ''): string
    {
        if (!$contenido) return '';
        $txt = $this->formatearTexto($contenido);
        return <<<HTML
<div class="section">
  <div class="section-head">{$icon} {$titulo}</div>
  <div class="section-body">{$txt}</div>
</div>
HTML;
    }

    private function formatearTexto(?string $texto): string
    {
        if (!$texto) return '<p class="text-muted">Sin contenido</p>';

        $t = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        // listas numeradas o con viñetas
        $t = preg_replace('/^(\d+)\.\s+(.+)$/m', '<li>$2</li>', $t);
        $t = preg_replace('/^[\-\*•]\s+(.+)$/m', '<li>$1</li>', $t);
        $t = preg_replace('/(<li>.*?<\/li>(\s*\n\s*)?)+/s', '<ul>$0</ul>', $t);

        // párrafos por doble salto
        $parts = preg_split('/\n\s*\n/', $t);
        $out = '';
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (preg_match('/<(ul|ol|li|p|div)/', $p)) {
                $out .= $p;
            } else {
                $out .= '<p>' . nl2br($p) . '</p>';
            }
        }
        $out = preg_replace('/<p>\s*<\/p>/', '', $out);
        return $out ?: '<p class="text-muted">Sin contenido</p>';
    }

    /**
     * Genera la sección completa de gráficas
     */
    private function generarSeccionGraficas(array $reporte): string
    {
        $charts = $reporte['charts'] ?? [];
        if (empty($charts)) return '';

        $html = '<div class="charts-section">';
        $html .= '<div class="charts-header">📊 Análisis Visual</div>';
        $html .= '<div class="charts-container">';
        $html .= '<table class="chart-grid"><tr>';

        $i = 0;
        foreach ($charts as $chart) {
            if ($i > 0 && $i % 2 === 0) {
                $html .= '</tr><tr>';
            }

            $html .= '<td class="chart-cell">';
            $html .= $this->renderChart($chart);
            $html .= '</td>';
            $i++;
        }

        // Cerrar última fila
        if ($i % 2 !== 0) {
            $html .= '<td class="chart-cell"></td>';
        }

        $html .= '</tr></table>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Renderiza un gráfico individual
     */
    private function renderChart(array $chart): string
    {
        $tipo = $chart['tipo'] ?? 'donut';
        $titulo = $chart['titulo'] ?? 'Gráfico';
        $data = $chart['data'] ?? [];

        $html = '<div class="chart-box">';
        $html .= '<div class="chart-title">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="center">';

        switch ($tipo) {
            case 'donut':
                $html .= $this->donutSVG($data);
                break;
            case 'gauge':
                $valor = $data['valor'] ?? 0;
                $max = $data['max'] ?? 10;
                $html .= $this->gaugeSVG($valor, $max);
                break;
            case 'bar':
                $html .= $this->barsSVG($data);
                break;
            default:
                $html .= '<p class="text-muted">Tipo no soportado</p>';
        }

        $html .= '</div>';

        if (!empty($chart['leyenda'])) {
            $html .= '<div class="chart-subtitle">' . htmlspecialchars($chart['leyenda']) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Gráfico de dona SVG
     */
    private function donutSVG(array $data): string
    {
        if (empty($data)) return '';

        $w = 280;
        $h = 280;
        $cx = 140;
        $cy = 140;
        $r = 100;
        $rInner = 65;

        $palette = [
            self::COLOR_PRIMARY,
            self::COLOR_SECONDARY,
            self::COLOR_ACCENT,
            self::COLOR_DANGER,
            '#10b981',
            '#8b5cf6',
            '#f59e0b',
            '#ef4444'
        ];

        $total = array_sum($data);
        if ($total <= 0) return '';

        $segments = '';
        $startAngle = -90;

        $i = 0;
        foreach ($data as $label => $value) {
            $angle = ($value / $total) * 360;
            $endAngle = $startAngle + $angle;

            $x1 = $cx + $r * cos(deg2rad($startAngle));
            $y1 = $cy + $r * sin(deg2rad($startAngle));
            $x2 = $cx + $r * cos(deg2rad($endAngle));
            $y2 = $cy + $r * sin(deg2rad($endAngle));

            $x1i = $cx + $rInner * cos(deg2rad($startAngle));
            $y1i = $cy + $rInner * sin(deg2rad($startAngle));
            $x2i = $cx + $rInner * cos(deg2rad($endAngle));
            $y2i = $cy + $rInner * sin(deg2rad($endAngle));

            $largeArcFlag = $angle > 180 ? 1 : 0;

            $color = $palette[$i % count($palette)];

            $path = "M $x1,$y1 A $r,$r 0 $largeArcFlag,1 $x2,$y2 ";
            $path .= "L $x2i,$y2i A $rInner,$rInner 0 $largeArcFlag,0 $x1i,$y1i Z";

            $segments .= "<path d='$path' fill='$color' opacity='0.9'/>";

            $startAngle = $endAngle;
            $i++;
        }

        $centerText = "<text x='$cx' y='" . ($cy - 5) . "' text-anchor='middle' font-size='28' font-weight='700' fill='" . self::COLOR_PRIMARY . "'>$total</text>";
        $centerLabel = "<text x='$cx' y='" . ($cy + 15) . "' text-anchor='middle' font-size='12' fill='" . self::COLOR_TEXT_LIGHT . "'>Total</text>";

        return "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$segments$centerText$centerLabel</svg>";
    }

    /**
     * Gauge semicircular
     */
    private function gaugeSVG(float $value, float $max = 10): string
    {
        $value = max(0.0, min($max, $value));
        $w = 280;
        $h = 170;
        $cx = 140;
        $cy = 140;
        $r = 95;

        $color = self::COLOR_PRIMARY;
        if ($value >= 7) {
            $color = self::COLOR_DANGER;
        } elseif ($value >= 4) {
            $color = self::COLOR_ACCENT;
        } else {
            $color = '#10b981';
        }

        $bg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 1,1 " . ($cx + $r) . ",$cy' fill='none' stroke='#e5e7eb' stroke-width='16' stroke-linecap='round'/>";

        $ang = M_PI * ($value / $max);
        $x = $cx - $r * cos($ang);
        $y = $cy - $r * sin($ang);
        $largeArc = $ang > M_PI ? 1 : 0;
        $fg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 $largeArc,1 $x,$y' fill='none' stroke='$color' stroke-width='16' stroke-linecap='round'/>";

        $txt = "<text x='$cx' y='" . ($cy - 15) . "' text-anchor='middle' font-size='32' font-weight='700' fill='$color'>" . number_format($value, 1) . "</text>";
        $subtxt = "<text x='$cx' y='" . ($cy + 10) . "' text-anchor='middle' font-size='14' fill='" . self::COLOR_TEXT_LIGHT . "'>de " . number_format($max, 0) . "</text>";

        return "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$bg$fg$txt$subtxt</svg>";
    }

    /**
     * Gráfico de barras
     */
    private function barsSVG(array $data): string
    {
        if (empty($data)) return '';

        $w = 280;
        $barHeight = 30;
        $gap = 12;
        $h = (count($data) * ($barHeight + $gap)) + 40;

        $max = max($data);
        if ($max <= 0) return '';

        $bars = '';
        $y = 20;
        $i = 0;

        $palette = [
            self::COLOR_PRIMARY,
            self::COLOR_SECONDARY,
            self::COLOR_ACCENT,
            self::COLOR_DANGER
        ];

        foreach ($data as $label => $value) {
            $barWidth = ($value / $max) * 200;
            $color = $palette[$i % count($palette)];

            $bars .= "<rect x='70' y='$y' width='$barWidth' height='$barHeight' fill='$color' opacity='0.85' rx='3'/>";
            $bars .= "<text x='5' y='" . ($y + $barHeight / 2 + 5) . "' font-size='10' fill='" . self::COLOR_TEXT . "'>" . htmlspecialchars(substr($label, 0, 15)) . "</text>";
            $bars .= "<text x='" . (75 + $barWidth) . "' y='" . ($y + $barHeight / 2 + 5) . "' font-size='10' font-weight='700' fill='" . self::COLOR_TEXT . "'>$value</text>";

            $y += $barHeight + $gap;
            $i++;
        }

        return "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$bars</svg>";
    }
}
