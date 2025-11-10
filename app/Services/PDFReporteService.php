<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService - v2.3.0
 * - Leyendas completas en donas (categorías/canales) con colores y porcentajes
 * - Barras con margen de etiquetas fijo (sin sobreponer) + tabla de apoyo
 * - Gauge de riesgo con trazo seguro (siempre visible)
 * - Misma estética para todas las gráficas (SVG -> <img> base64)
 */
class PDFReporteService
{
    private Dompdf $dompdf;
    private string $outputDir;

    // Paleta Eqqua
    private const COLOR_PRIMARY       = '#004E89';
    private const COLOR_SECONDARY     = '#1CBEC6';
    private const COLOR_ACCENT        = '#FFB703';
    private const COLOR_DANGER        = '#E84855';
    private const COLOR_TEXT          = '#2c3e50';
    private const COLOR_TEXT_LIGHT    = '#6c757d';

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

    /** Genera el PDF y retorna la ruta relativa */
    public function generarPDF(array $reporte)
    {
        try {
            if (!isset($reporte['charts']) && !empty($reporte['metricas'])) {
                $reporte['charts'] = $this->generarGraficasDesdeMetricas($reporte['metricas'], $reporte);
            }

            $html = $this->generarHTML($reporte);

            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('letter', 'landscape');
            $this->dompdf->render();

            $this->agregarFooter();

            $filename = $this->generarNombreArchivo($reporte);
            $path     = FCPATH . $this->outputDir . $filename;

            file_put_contents($path, $this->dompdf->output());
            return $this->outputDir . $filename;
        } catch (\Throwable $e) {
            log_message('error', '[PDFReporteService] ' . $e->getMessage());
            return false;
        }
    }

    /** Convierte métricas conocidas a un set de charts */
    private function generarGraficasDesdeMetricas(array $metricas, array $reporte): array
    {
        $charts = [];

        // Donut: Distribución por Categoría
        if (!empty($metricas['distribucion_categoria'])) {
            $data = [];
            $c = 0;
            foreach ($metricas['distribucion_categoria'] as $it) {
                if ($c >= 6) break;
                $cat   = $it['categoria']   ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) {
                    $data[$cat] = $total;
                    $c++;
                }
            }
            if ($data) {
                $charts[] = [
                    'tipo' => 'donut',
                    'titulo' => 'Distribución por Categoría',
                    'leyenda' => 'Total de denuncias: ' . array_sum($data),
                    'data' => $data
                ];
            }
        }

        // Gauge: Nivel de riesgo global
        if (isset($reporte['puntuacion_riesgo']) && is_numeric($reporte['puntuacion_riesgo'])) {
            $charts[] = [
                'tipo' => 'gauge',
                'titulo' => 'Nivel de Riesgo Global',
                'leyenda' => 'Evaluación del período analizado',
                'data' => [
                    'valor' => (float)$reporte['puntuacion_riesgo'],
                    'max' => 10
                ]
            ];
        }

        // Barras: Top 5 Sucursales
        if (!empty($metricas['distribucion_sucursal'])) {
            $data = [];
            foreach ($metricas['distribucion_sucursal'] as $i => $it) {
                if ($i >= 5) break;
                $suc   = $it['sucursal'] ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) $data[$suc] = $total;
            }
            if ($data) {
                $charts[] = [
                    'tipo' => 'bar',
                    'titulo' => 'Top 5 Sucursales',
                    'leyenda' => 'Sucursales con mayor número de denuncias',
                    'data' => $data
                ];
            }
        }

        // Donut: Canales de reporte
        if (!empty($metricas['distribucion_medio'])) {
            $data = [];
            foreach ($metricas['distribucion_medio'] as $it) {
                $medio = $it['medio'] ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) $data[$medio] = $total;
            }
            if ($data) {
                $charts[] = [
                    'tipo' => 'donut',
                    'titulo' => 'Canales de Reporte',
                    'leyenda' => 'Medios utilizados para reportar',
                    'data' => $data
                ];
            }
        }

        // Barras: Top 5 Departamentos
        if (!empty($metricas['distribucion_departamento'])) {
            $data = [];
            foreach ($metricas['distribucion_departamento'] as $i => $it) {
                if ($i >= 5) break;
                $dep   = $it['departamento'] ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) $data[$dep] = $total;
            }
            if ($data) {
                $charts[] = [
                    'tipo' => 'bar',
                    'titulo' => 'Top 5 Departamentos',
                    'leyenda' => 'Departamentos con más incidencias',
                    'data' => $data
                ];
            }
        }

        // Gauge: Tasa de resolución (escala 0–10)
        if (isset($metricas['indice_resolucion']) && is_numeric($metricas['indice_resolucion'])) {
            $valor0a10 = ((float)$metricas['indice_resolucion']);
            if ($valor0a10 > 10) {
                $valor0a10 = round(($valor0a10 / 100) * 10, 1);
            }
            $charts[] = [
                'tipo' => 'gauge',
                'titulo' => 'Tasa de Resolución',
                'leyenda' => 'Porcentaje de denuncias cerradas',
                'data' => [
                    'valor' => $valor0a10,
                    'max' => 10
                ]
            ];
        }

        return $charts;
    }

    /** Footer con numeración (sin ID) */
    private function agregarFooter(): void
    {
        $this->dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $left   = 'Eqqua · Reporte IA';
            $right  = "Página {$pageNumber} de {$pageCount}";
            $font = $fontMetrics->getFont('Raleway', 'normal');
            $size = 8;
            $y    = $canvas->get_height() - 28;
            $color = [0.4, 0.4, 0.4];
            $canvas->text(36, $y, $left, $font, $size, $color);
            $wRight = $fontMetrics->getTextWidth($right, $font, $size);
            $canvas->text($canvas->get_width() - 36 - $wRight, $y, $right, $font, $size, $color);
        });
    }

    private function generarNombreArchivo(array $reporte): string
    {
        $id      = $reporte['id'] ?? 'sin-id';
        $fecha   = date('Y-m-d_H-i-s');
        $periodo = $this->sanearNombre($reporte['periodo_nombre'] ?? 'reporte');
        return "reporte_ia_{$id}_{$periodo}_{$fecha}.pdf";
    }

    private function sanearNombre(string $f): string
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
        $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** HTML del reporte */
    private function generarHTML(array $reporte): string
    {
        $cliente         = $reporte['cliente_nombre'] ?? 'Cliente';
        $periodo         = $reporte['periodo_nombre'] ?? 'Sin periodo';
        $tipo            = ucfirst($reporte['tipo_reporte'] ?? 'Reporte');
        $fechaGeneracion = date('d/m/Y H:i', strtotime($reporte['created_at'] ?? 'now'));
        $riesgo          = $reporte['puntuacion_riesgo'] ?? 'N/D';

        $logoData = $this->resolveLogoDataUri($reporte);
        $logoHtml = $this->renderLogoData($logoData);

        $riesgoBadgeClass = $this->getRiesgoBadgeClass($riesgo);
        $riesgoFormatted  = $this->formatRiesgo($riesgo);

        $htmlCharts = $this->generarSeccionGraficas($reporte);

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
      <td class="label"></td>
      <td class="value"></td>
    </tr>
  </table>
</div>

{$this->seccion('Resumen ejecutivo',$reporte['resumen_ejecutivo'] ?? null)}
{$this->seccion('Hallazgos principales',$reporte['hallazgos_principales'] ?? null)}
{$this->seccion('Eficiencia operativa',$reporte['eficiencia_operativa'] ?? null)}

{$htmlCharts}

<div class="section alert">
  <div class="section-head alert-head">Sugerencias proactivas y predictivas</div>
  <div class="alert-note">Este contenido fue generado por Inteligencia Artificial (GPT-4o). Revíselo antes de su aplicación.</div>
  <div class="section-body">{$this->formatearTexto($reporte['sugerencias_predictivas'] ?? null)}</div>
</div>

</body>
</html>
HTML;
    }

    private function getCSS(): string
    {
        $c1 = self::COLOR_PRIMARY;
        $c2 = self::COLOR_SECONDARY;
        $c3 = self::COLOR_ACCENT;
        $cDanger = self::COLOR_DANGER;
        $cTxt = self::COLOR_TEXT;
        $cTxtLight = self::COLOR_TEXT_LIGHT;

        return <<<CSS
@page { margin: 2.7cm 2.2cm 3.2cm 2.2cm; }

* { box-sizing: border-box; margin:0; padding:0; }

body {
    font-family: 'Raleway', 'DejaVu Sans', Arial, sans-serif;
    font-size: 10.6pt;
    color: {$cTxt};
    line-height: 1.65;
    margin:16px;
}

/* Header */
.header { width:100%; border-collapse:collapse; margin-bottom:10px; }
.logo-cell { width:180px; vertical-align:middle; padding-right:20px; }
.title-cell { vertical-align:middle; text-align:left; }
.logo img { max-width:160px; max-height:60px; display:block; }
.title { font-size:22pt; color:{$c1}; font-weight:700; line-height:1.2; margin-bottom:4px; letter-spacing:-0.3px; }
.subtitle { font-size:12pt; color:{$c2}; font-weight:600; }

/* Divider */
.divider { height:4px; margin:16px 0 22px; background:linear-gradient(90deg, {$c1} 0%, {$c2} 50%, {$c3} 100%); border-radius:2px; }

/* Info */
.info { background:#f8f9fa; border-left:5px solid {$c2}; padding:18px; margin-bottom:26px; border-radius:4px; }
.info-table { width:100%; border-collapse:collapse; }
.info-table .label { width:22%; color:{$cTxtLight}; font-weight:700; padding:8px 12px 8px 0; font-size:9pt; text-transform:uppercase; letter-spacing:0.4px; }
.info-table .value { width:28%; padding:8px 12px; font-size:10pt; font-weight:600; }

/* Badges */
.badge { display:inline-block; padding:6px 12px; border-radius:4px; font-size:8.6pt; font-weight:800; text-transform:uppercase; letter-spacing:0.4px; }
.badge.success { background:#10b981; color:#fff; }
.badge.warning { background:{$c3}; color:#000; }
.badge.danger  { background:{$cDanger}; color:#fff; }
.badge.secondary { background:#6c757d; color:#fff; }

/* Secciones */
.section { margin:24px 0; page-break-inside:avoid; }
.section-head { background:{$c1}; color:#fff; padding:12px 16px; font-weight:800; font-size:11pt; border-radius:4px 4px 0 0; letter-spacing:0.2px; }
.section-body { padding:16px 18px; text-align:justify; background:#fff; border:1px solid #e9ecef; border-top:none; border-radius:0 0 4px 4px; }
.section-body p { margin:0 0 12px; }
.section-body ul { margin:10px 0 10px 22px; list-style:disc; }
.section-body li { margin-bottom:7px; }
.section-body strong { font-weight:700; color:{$c1}; }

/* Alerta */
.alert { background:#fffbf0; border:2px solid {$c3}; border-radius:4px; }
.alert-head { background:{$c3}; color:#000; font-weight:800; }
.alert-note { background:#fff3cd; color:#856404; padding:10px 16px; font-size:9pt; border-left:4px solid #ffc107; font-weight:600; font-style:italic; }

/* Gráficas */
.charts-section { margin:28px 0; page-break-inside:avoid; }
.charts-header { background:{$c2}; color:#fff; padding:12px 16px; font-weight:800; font-size:11pt; border-radius:4px 4px 0 0; }
.charts-container { background:#f8f9fa; padding:20px; border:1px solid #e9ecef; border-top:none; border-radius:0 0 4px 4px; }
.chart-grid { width:100%; border-collapse:separate; border-spacing:16px; }
.chart-cell { width:50%; vertical-align:top; }
.chart-box { background:#fff; border:2px solid #e9ecef; border-radius:6px; padding:14px; box-shadow:0 2px 4px rgba(0,0,0,.05); }
.chart-title { font-weight:800; color:{$c1}; margin-bottom:12px; font-size:10.5pt; text-align:center; text-transform:uppercase; letter-spacing:.5px; }
.chart-subtitle { font-size:8.5pt; color:{$cTxtLight}; text-align:center; margin-top:8px; font-style:italic; }
.center { text-align:center; }
.text-muted { color:{$cTxtLight}; font-style:italic; }

/* Leyendas de donas/barras */
.legend { margin:12px auto 0; width:100%; }
.legend table { width:100%; border-collapse:collapse; }
.legend td { padding:4px 6px; font-size:9pt; }
.legend .swatch { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:6px; vertical-align:middle; }
.legend .value { text-align:right; white-space:nowrap; }
.legend .label { width:70%; }
.legend .pct { text-align:right; white-space:nowrap; color:{$cTxtLight}; }

CSS;
    }

    private function resolveLogoDataUri(array $reporte): ?string
    {
        if (!empty($reporte['cliente_logo'])) {
            $data = $this->embedImage($reporte['cliente_logo']);
            if ($data) return $data;
        }
        foreach (['assets/images/logo.png', 'assets/images/eqqua logos-09.png', 'assets/images/eqqua logos-05.png', 'assets/images/logo_eqqua.png'] as $rel) {
            $data = $this->embedImage($rel);
            if ($data) return $data;
        }
        return null;
    }

    private function renderLogoData(?string $data): string
    {
        if ($data) return '<div class="logo"><img src="' . $data . '" alt="Logo"></div>';
        return '<div class="logo" style="font-weight:800;color:' . self::COLOR_PRIMARY . ';font-size:18pt;">EQQUA</div>';
    }

    private function getRiesgoBadgeClass($riesgo): string
    {
        if ($riesgo === 'N/D' || !is_numeric($riesgo)) return 'secondary';
        $r = (float)$riesgo;
        if ($r >= 7) return 'danger';
        if ($r >= 4) return 'warning';
        return 'success';
    }

    private function formatRiesgo($riesgo): string
    {
        if (!is_numeric($riesgo)) return 'N/D';
        return number_format((float)$riesgo, 1) . '/10';
    }

    private function seccion(string $titulo, ?string $contenido): string
    {
        if (!$contenido) return '';
        $txt = $this->formatearTexto($contenido);
        return <<<HTML
<div class="section">
  <div class="section-head">{$titulo}</div>
  <div class="section-body">{$txt}</div>
</div>
HTML;
    }

    private function formatearTexto(?string $texto): string
    {
        if (!$texto) return '<p class="text-muted">Sin contenido</p>';

        $texto = preg_replace('/\s+(\d+)\.\s+/', "\n$1. ", $texto);

        $t = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        $t = preg_replace('/^(\d+)\.\s+(.+)$/m', '<li>$2</li>', $t);
        $t = preg_replace('/^[\-\*•]\s+(.+)$/m', '<li>$1</li>', $t);
        $t = preg_replace_callback('/(?:<li>.*?<\/li>\s*)+/s', function ($m) {
            return '<ul>' . $m[0] . '</ul>';
        }, $t);

        $partes = preg_split('/\n\s*\n/', $t);
        $out = '';
        foreach ($partes as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (preg_match('/^<(ul|ol|li|p|div)\b/i', $p)) {
                $out .= $p;
            } else {
                $out .= '<p>' . nl2br($p) . '</p>';
            }
        }
        $out = preg_replace('/<p>\s*<\/p>/', '', $out);

        return $out ?: '<p class="text-muted">Sin contenido</p>';
    }

    /** Sección de gráficas */
    private function generarSeccionGraficas(array $reporte): string
    {
        $charts = $reporte['charts'] ?? [];
        if (empty($charts)) return '';

        $html  = '<div class="charts-section">';
        $html .= '<div class="charts-header">Análisis Visual</div>';
        $html .= '<div class="charts-container">';
        $html .= '<table class="chart-grid"><tr>';

        $i = 0;
        foreach ($charts as $chart) {
            if ($i > 0 && $i % 2 === 0) $html .= '</tr><tr>';
            $html .= '<td class="chart-cell">';
            $html .= $this->renderChart($chart);
            $html .= '</td>';
            $i++;
        }

        if ($i % 2 !== 0) $html .= '<td class="chart-cell"></td>';

        $html .= '</tr></table></div></div>';

        return $html;
    }

    /** Render de un gráfico */
    private function renderChart(array $chart): string
    {
        $tipo   = $chart['tipo']   ?? 'donut';
        $titulo = $chart['titulo'] ?? 'Gráfico';
        $data   = $chart['data']   ?? [];

        $html = '<div class="chart-box">';
        $html .= '<div class="chart-title">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="center">';

        $legendHtml = '';
        $svg = '';

        switch ($tipo) {
            case 'donut':
                $palette = $this->palette();
                [$svg, $legendHtml] = $this->donutSVGConLeyenda($data, $palette);
                break;
            case 'gauge':
                $valor = (float)($data['valor'] ?? 0);
                $max   = (float)($data['max']   ?? 10);
                $svg   = $this->gaugeSVG($valor, $max);
                break;
            case 'bar':
                $palette = $this->palette();
                [$svg, $legendHtml] = $this->barsSVGConTabla($data, $palette);
                break;
            default:
                $html .= '<p class="text-muted">Tipo no soportado</p>';
        }

        if ($svg !== '') {
            $html .= $this->svgAImagen($svg, 320);
        }

        $html .= '</div>';

        if ($legendHtml) {
            $html .= $legendHtml;
        }

        if (!empty($chart['leyenda'])) {
            $html .= '<div class="chart-subtitle">' . htmlspecialchars($chart['leyenda']) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function svgAImagen(string $svg, int $maxWidth = 280): string
    {
        if (trim($svg) === '') return '<p class="text-muted">Sin datos</p>';
        $b64 = base64_encode($svg);
        return '<img alt="gráfica" style="display:block;margin:0 auto;max-width:' . $maxWidth . 'px;width:100%;height:auto" src="data:image/svg+xml;base64,' . $b64 . '">';
    }

    /** Paleta cíclica */
    private function palette(): array
    {
        return [
            self::COLOR_PRIMARY,
            self::COLOR_SECONDARY,
            self::COLOR_ACCENT,
            self::COLOR_DANGER,
            '#10b981',
            '#8b5cf6',
            '#f59e0b',
            '#ef4444'
        ];
    }

    /** Dona + leyenda detallada */
    private function donutSVGConLeyenda(array $data, array $palette): array
    {
        if (empty($data)) return ['', ''];

        // SVG
        $w = 320;
        $h = 280;
        $cx = 160;
        $cy = 140;
        $r = 105;
        $rInner = 68;
        $total = array_sum($data);
        if ($total <= 0) return ['', ''];

        $segments = '';
        $startAngle = -90;
        $i = 0;
        $colors = [];

        foreach ($data as $label => $value) {
            $angle = ($value / $total) * 360;
            $endAngle = $startAngle + $angle;

            $x1  = $cx + $r * cos(deg2rad($startAngle));
            $y1  = $cy + $r * sin(deg2rad($startAngle));
            $x2  = $cx + $r * cos(deg2rad($endAngle));
            $y2  = $cy + $r * sin(deg2rad($endAngle));
            $x1i = $cx + $rInner * cos(deg2rad($startAngle));
            $y1i = $cy + $rInner * sin(deg2rad($startAngle));
            $x2i = $cx + $rInner * cos(deg2rad($endAngle));
            $y2i = $cy + $rInner * sin(deg2rad($endAngle));

            $largeArcFlag = ($angle > 180) ? 1 : 0;
            $color = $palette[$i % count($palette)];
            $colors[$label] = $color;

            $path  = "M $x1,$y1 A $r,$r 0 $largeArcFlag,1 $x2,$y2 ";
            $path .= "L $x2i,$y2i A $rInner,$rInner 0 $largeArcFlag,0 $x1i,$y1i Z";

            $segments .= "<path d='$path' fill='$color' opacity='0.9'/>";

            $startAngle = $endAngle;
            $i++;
        }

        $centerText  = "<text x='$cx' y='" . ($cy - 5) . "' text-anchor='middle' font-size='28' font-weight='700' fill='" . self::COLOR_PRIMARY . "'>$total</text>";
        $centerLabel = "<text x='$cx' y='" . ($cy + 15) . "' text-anchor='middle' font-size='12' fill='" . self::COLOR_TEXT_LIGHT . "'>Total</text>";
        $svg = "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$segments$centerText$centerLabel</svg>";

        // Leyenda (nombres completos + valor + %)
        $legendHtml = $this->renderLeyendaTabla($data, $colors, $total);

        return [$svg, $legendHtml];
    }

    /** Gauge semicircular robusto */
    private function gaugeSVG(float $value, float $max = 10): string
    {
        $max = $max > 0 ? $max : 10;
        $value = max(0.0, min($max, $value));

        $w = 320;
        $h = 180;
        $cx = 160;
        $cy = 140;
        $r = 100;

        $bg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 1,1 " . ($cx + $r) . ",$cy' fill='none' stroke='#e5e7eb' stroke-width='16' stroke-linecap='round'/>";

        // Color por valor
        if ($value >= 7)       $color = self::COLOR_DANGER;
        elseif ($value >= 4)   $color = self::COLOR_ACCENT;
        else                   $color = '#10b981';

        // Progreso (mínimo visible para evitar que parezca vacío)
        $minVisible = max($value, 0.15);
        $ang = M_PI * ($minVisible / $max);
        $x = $cx - $r * cos($ang);
        $y = $cy - $r * sin($ang);
        $largeArc = ($ang > M_PI) ? 1 : 0;

        $fg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 $largeArc,1 $x,$y' fill='none' stroke='$color' stroke-width='16' stroke-linecap='round'/>";

        $txt    = "<text x='$cx' y='" . ($cy - 15) . "' text-anchor='middle' font-size='32' font-weight='700' fill='$color'>" . number_format($value, 1) . "</text>";
        $subtxt = "<text x='$cx' y='" . ($cy + 10) . "' text-anchor='middle' font-size='14' fill='" . self::COLOR_TEXT_LIGHT . "'>de " . number_format($max, 0) . "</text>";

        return "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$bg$fg$txt$subtxt</svg>";
    }

    /** Barras horizontales + tabla con nombres completos */
    private function barsSVGConTabla(array $data, array $palette): array
    {
        if (empty($data)) return ['', ''];

        // SVG con margen de etiquetas a la izquierda
        $w = 320;
        $labelWidth = 120;   // área fija para etiqueta (no se sobrepone)
        $barMaxWidth = 180;  // ancho máximo de barra
        $barHeight = 28;
        $gap = 12;
        $h = (count($data) * ($barHeight + $gap)) + 30;

        $max = max($data);
        if ($max <= 0) return ['', ''];

        $bars = '';
        $y = 15;
        $i = 0;

        foreach ($data as $label => $value) {
            $barWidth = ($value / $max) * $barMaxWidth;
            $color = $palette[$i % count($palette)];

            // Etiqueta corta visual (no se monta sobre barra)
            $labelCorto = mb_strimwidth($label, 0, 18, '…', 'UTF-8');

            $bars .= "<text x='0' y='" . ($y + $barHeight / 2 + 5) . "' font-size='10' fill='" . self::COLOR_TEXT . "'>$labelCorto</text>";
            $bars .= "<rect x='{$labelWidth}' y='$y' width='$barWidth' height='$barHeight' fill='$color' opacity='0.9' rx='3'/>";
            $bars .= "<text x='" . ($labelWidth + $barWidth + 6) . "' y='" . ($y + $barHeight / 2 + 5) . "' font-size='10' font-weight='700' fill='" . self::COLOR_TEXT . "'>$value</text>";

            $y += $barHeight + $gap;
            $i++;
        }

        $svg = "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$bars</svg>";

        // Tabla con nombres completos (evita pérdida de información)
        $colors = [];
        $i = 0;
        foreach ($data as $label => $_) {
            $colors[$label] = $palette[$i % count($palette)];
            $i++;
        }
        $legendHtml = $this->renderLeyendaTabla($data, $colors, array_sum($data));

        return [$svg, $legendHtml];
    }

    /** Tabla de leyenda reutilizable (nombre completo + valor + %) */
    private function renderLeyendaTabla(array $data, array $colors, int $total): string
    {
        if ($total <= 0) return '';
        $rows = '';
        foreach ($data as $label => $value) {
            $pct = ($value / $total) * 100;
            $color = $colors[$label] ?? self::COLOR_PRIMARY;
            $rows .= '<tr>'
                . '<td class="label"><span class="swatch" style="background:' . $color . '"></span>' . htmlspecialchars($label) . '</td>'
                . '<td class="value"><strong>' . (int)$value . '</strong></td>'
                . '<td class="pct">' . number_format($pct, 1) . '%</td>'
                . '</tr>';
        }
        return '<div class="legend"><table>' . $rows . '</table></div>';
    }
}
