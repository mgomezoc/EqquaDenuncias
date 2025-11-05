<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PDFReporteService
{
    private Dompdf $dompdf;
    private string $outputDir;

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
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
            $html = $this->generarHTML($reporte);

            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('letter', 'portrait');
            $this->dompdf->render();

            // Numeración real de páginas (corrige {PAGE_NUM}/{PAGE_COUNT})
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

    /** Footer con numeración de páginas e ID */
    private function agregarNumeracionPaginas(string $id): void
    {
        $canvas = $this->dompdf->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($id) {
            $left   = "Eqqua · Reporte IA";
            $center = "ID: " . ($id ?: 'N/D');
            $right  = "Página {$pageNumber} de {$pageCount}";

            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $size = 8;
            $y    = $canvas->get_height() - 28;

            // izquierda
            $canvas->text(36, $y, $left, $font, $size, [0.4, 0.4, 0.4]);
            // centro
            $wCenter = $fontMetrics->getTextWidth($center, $font, $size);
            $canvas->text(($canvas->get_width() - $wCenter) / 2, $y, $center, $font, $size, [0.4, 0.4, 0.4]);
            // derecha
            $wRight = $fontMetrics->getTextWidth($right, $font, $size);
            $canvas->text($canvas->get_width() - 36 - $wRight, $y, $right, $font, $size, [0.4, 0.4, 0.4]);
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

        // Colores corporativos
        $c1   = '#004E89'; // azul principal
        $c2   = '#FFB703'; // acento
        $cTxt = '#2c3e50';

        // Logo: si existe cliente_logo lo incrustamos, si no; logo Eqqua
        $logoData = $this->resolveLogoDataUri($reporte);

        // Íconos de secciones (base64 para evitar problemas de ruta)
        $iconResumen    = $this->embedImage('assets/icons/resumen.png');
        $iconHallazgos  = $this->embedImage('assets/icons/hallazgo.png'); // ajusta al nombre real del archivo
        $iconEficiencia = $this->embedImage('assets/icons/eficiencia.png');

        $icoResumenHtml    = $iconResumen    ? "<img src=\"{$iconResumen}\" class=\"icon-img\">"    : '';
        $icoHallazgosHtml  = $iconHallazgos  ? "<img src=\"{$iconHallazgos}\" class=\"icon-img\">"  : '';
        $icoEficienciaHtml = $iconEficiencia ? "<img src=\"{$iconEficiencia}\" class=\"icon-img\">" : '';

        // Gráficas (opcional)
        $charts     = $reporte['charts'] ?? [];
        $htmlCharts = $this->renderCharts($charts, $c1, $c2);

        $css = $this->getCSS($c1, $c2, $cTxt);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte IA - {$periodo}</title>
<style>{$css}</style>
</head>
<body>

<table class="header">
  <tr>
    <td class="logo-cell">{$this->renderLogoData($logoData)}</td>
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
      <td class="label">Cliente:</td><td class="value">{$cliente}</td>
      <td class="label">Periodo:</td><td class="value">{$periodo}</td>
    </tr>
    <tr>
      <td class="label">Tipo de reporte:</td><td class="value">{$tipo}</td>
      <td class="label">Nivel de riesgo:</td>
      <td class="value">
        <span class="badge {$this->getRiesgoBadgeClass($riesgo)}">{$this->formatRiesgo($riesgo)}</span>
      </td>
    </tr>
    <tr>
      <td class="label">Generado:</td><td class="value">{$fechaGeneracion}</td>
      <td class="label">Estado:</td><td class="value"><span class="badge estado">{$estado}</span></td>
    </tr>
  </table>
</div>

{$this->seccion('Resumen ejecutivo',$reporte['resumen_ejecutivo'] ?? null,$icoResumenHtml)}
{$this->seccion('Hallazgos principales',$reporte['hallazgos_principales'] ?? null,$icoHallazgosHtml)}
{$this->seccion('Eficiencia operativa',$reporte['eficiencia_operativa'] ?? null,$icoEficienciaHtml)}
{$htmlCharts}

<div class="section alert">
  <div class="section-head alert-head">&#9888; Sugerencias proactivas y predictivas</div>
  <div class="alert-note">Este contenido fue generado por IA (GPT-4o). Revíselo antes de aplicarlo.</div>
  <div class="section-body">{$this->formatearTexto($reporte['sugerencias_predictivas'] ?? null)}</div>
</div>

</body>
</html>
HTML;

        return $html;
    }

    private function getCSS(string $c1, string $c2, string $cTxt): string
    {
        return <<<CSS
@page { margin: 2cm 1.5cm 2.5cm 1.5cm; }

*{ box-sizing:border-box; }
body{ font-family:'DejaVu Sans','Arial',sans-serif; font-size:10.5pt; color:{$cTxt}; }

.header{ width:100%; border-collapse:collapse; }
.logo-cell{ width:192px; vertical-align:middle; }
.title-cell{ vertical-align:middle; text-align:left; }
.logo img{ max-width:160px; max-height:60px; }
.title{ font-size:20pt; color:{$c1}; font-weight:700; line-height:1.2; }
.subtitle{ font-size:12pt; color:{$c2}; }

.divider{ height:3px; margin:14px 0 20px; background:linear-gradient(90deg, {$c1} 0%, {$c2} 100%); }

.info{ background:#f8f9fa; border-left:4px solid {$c1}; padding:12px 14px; margin-bottom:18px; }
.info-table{ width:100%; border-collapse:collapse; }
.info-table .label{ width:22%; color:#6c757d; font-weight:700; padding:6px 10px 6px 0; font-size:9pt; }
.info-table .value{ width:28%; padding:6px 10px; font-size:9pt; }

.badge{ display:inline-block; padding:4px 10px; border-radius:3px; font-size:8.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.25px; }
.badge.success{ background:#2fb171; color:#fff; }
.badge.warning{ background:#ffcf33; color:#000; }
.badge.danger{ background:#dc3545; color:#fff; }
.badge.secondary{ background:#6c757d; color:#fff; }
.badge.estado{ background:#0ea5e9; color:#fff; }

.section{ margin:20px 0; page-break-inside:avoid; }
.section-head{ background:{$c1}; color:#fff; padding:10px 14px; font-weight:700; border-radius:3px; }
.section-body{ padding:8px 12px; text-align:justify; }
.section-body p{ margin:0 0 10px; }
.section-body ul{ margin:8px 0 8px 18px; }
.section-body li{ margin-bottom:6px; }

/* encabezado de alerta */
.alert{ background:#fff8e1; border:2px solid {$c2}; }
.alert-head{ background:{$c2}; color:#000; }
.alert-note{ background:#fff3cd; color:#7a5b00; padding:8px 12px; font-size:9pt; border-left:4px solid #ffc107; }

/* Íconos: aclaramos (invert) para verse sobre header azul */
.section-head .icon-img{
    width: 12pt;
    height: 12pt;
    margin-right: 8px;
    vertical-align: -2px;
    filter: brightness(3) invert(1) contrast(1.1);
}

/* Gráficas */
.chart-grid{ width:100%; border-collapse:separate; border-spacing:14px; }
.chart-cell{ width:50%; vertical-align:top; }
.chart-box{ border:1px solid #eaeaea; border-radius:4px; padding:10px; }
.chart-title{ font-weight:700; color:{$c1}; margin-bottom:8px; font-size:10pt; }

.center{text-align:center;}
CSS;
    }

    /** Si hay logo de cliente lo usa, si no: uno de Eqqua. Devuelve data URI o null */
    private function resolveLogoDataUri(array $reporte): ?string
    {
        if (!empty($reporte['cliente_logo'])) {
            $data = $this->embedImage('uploads/clientes/' . $reporte['cliente_logo']);
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
        return '<div class="logo" style="font-weight:700;color:#004E89;font-size:16pt;">EQQUA</div>';
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
        $map = ['generado' => 'Generado', 'revisado' => 'Revisado', 'publicado' => 'Publicado', 'archivado' => 'Archivado'];
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

    /* ====================  GRÁFICAS SVG OPCIONALES  ==================== */

    private function renderCharts(array $charts, string $c1, string $c2): string
    {
        $hasAny =
            $this->hasSeries($charts['por_sucursal'] ?? null) ||
            $this->hasSeries($charts['por_categoria'] ?? null) ||
            $this->hasSeries($charts['por_estado'] ?? null) ||
            $this->hasSeries($charts['medio'] ?? null) ||
            isset($charts['riesgo']['value']);

        if (!$hasAny) return '';

        $html = '<div class="section"><div class="section-head">Gráficas</div><div class="section-body">';
        $html .= '<table class="chart-grid"><tr>';

        // primera columna
        $html .= '<td class="chart-cell">';
        if ($this->hasSeries($charts['por_sucursal'] ?? null)) {
            $html .= $this->chartBox('Por sucursal', $this->barSVG($charts['por_sucursal'], $c1));
        }
        if ($this->hasSeries($charts['por_categoria'] ?? null)) {
            $html .= $this->chartBox('Por categoría', $this->barSVG($charts['por_categoria'], $c1));
        }
        $html .= '</td>';

        // segunda columna
        $html .= '<td class="chart-cell">';
        if ($this->hasSeries($charts['por_estado'] ?? null)) {
            $html .= $this->chartBox('Por estatus', $this->pieSVG($charts['por_estado'], $c1, $c2));
        }
        if ($this->hasSeries($charts['medio'] ?? null)) {
            $html .= $this->chartBox('Por medio de recepción', $this->pieSVG($charts['medio'], $c1, $c2));
        }
        if (isset($charts['riesgo']['value'])) {
            $html .= $this->chartBox('Riesgo', $this->gaugeSVG((float)$charts['riesgo']['value'], (float)($charts['riesgo']['max'] ?? 10), $c1, $c2));
        }
        $html .= '</td>';

        $html .= '</tr></table></div></div>';
        return $html;
    }

    private function hasSeries(?array $s): bool
    {
        return !empty($s['labels']) && !empty($s['values']) && count($s['labels']) === count($s['values']);
    }

    private function chartBox(string $title, string $svg): string
    {
        return '<div class="chart-box"><div class="chart-title">' . $title . '</div>' . $svg . '</div>';
    }

    /** Barras horizontales simples en SVG */
    private function barSVG(array $series, string $color): string
    {
        $labels = $series['labels'];
        $values = array_map('floatval', $series['values']);
        $w = 520;
        $h = max(120, 26 * count($values) + 30);
        $max = max(1, max($values));
        $x0 = 140;
        $y0 = 20;
        $barH = 18;
        $gap = 8;

        $bars = '';
        foreach ($values as $i => $v) {
            $y  = $y0 + $i * ($barH + $gap);
            $bw = ($w - $x0 - 20) * ($v / $max);
            $label = htmlspecialchars((string)$labels[$i], ENT_QUOTES, 'UTF-8');
            $valTxt = number_format($v, 0);
            $bars .= "<text x='10' y='" . ($y + $barH - 2) . "' font-size='9'>{$label}</text>";
            $bars .= "<rect x='{$x0}' y='{$y}' width='{$bw}' height='{$barH}' fill='{$color}' opacity='0.9'/>";
            $bars .= "<text x='" . ($x0 + $bw + 6) . "' y='" . ($y + $barH - 2) . "' font-size='9'>{$valTxt}</text>";
        }
        return "<svg width='{$w}' height='{$h}' aria-hidden='true'>{$bars}</svg>";
    }

    /** Pie/dona simple en SVG */
    private function pieSVG(array $series, string $c1, string $c2): string
    {
        $values = array_map('floatval', $series['values']);
        $labels = $series['labels'];
        if (array_sum($values) <= 0) $values = array_fill(0, count($values), 1);

        $w = 240;
        $h = 180;
        $r = 60;
        $cx = 90;
        $cy = 90;
        $total = array_sum($values);
        $angle = -M_PI / 2;
        $segments = '';
        $palette = [$c1, $c2, '#6c757d', '#2fb171', '#d9534f', '#9b59b6', '#2aa1d6', '#ff8f00'];

        foreach ($values as $i => $v) {
            $theta = ($v / $total) * 2 * M_PI;
            $x1 = $cx + $r * cos($angle);
            $y1 = $cy + $r * sin($angle);
            $angle += $theta;
            $x2 = $cx + $r * cos($angle);
            $y2 = $cy + $r * sin($angle);
            $laf = $theta > M_PI ? 1 : 0;
            $color = $palette[$i % count($palette)];
            $segments .= "<path d='M {$cx},{$cy} L {$x1},{$y1} A {$r},{$r} 0 {$laf},1 {$x2},{$y2} Z' fill='{$color}' opacity='0.9'/>";
        }
        return "<svg width='{$w}' height='{$h}' aria-hidden='true'>{$segments}</svg>";
    }

    /** Gauge semicircular para riesgo */
    private function gaugeSVG(float $value, float $max, string $c1, string $c2): string
    {
        $value = max(0.0, min($max, $value));
        $w = 260;
        $h = 160;
        $cx = 130;
        $cy = 130;
        $r = 90;

        $bg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 1,1 " . ($cx + $r) . ",$cy' fill='none' stroke='#e5e7eb' stroke-width='14' />";
        $ang = M_PI * ($value / $max);
        $x = $cx - $r * cos($ang);
        $y = $cy - $r * sin($ang);
        $fg = "<path d='M " . ($cx - $r) . ",$cy A $r,$r 0 " . ($ang > M_PI ? 1 : 0) . ",1 {$x},{$y}' fill='none' stroke='{$c2}' stroke-width='14' />";
        $txt = "<text x='{$cx}' y='" . ($cy - 10) . "' text-anchor='middle' font-size='18' font-weight='700' fill='{$c1}'>" . number_format($value, 1) . "/" . number_format($max, 0) . "</text>";

        return "<svg width='{$w}' height='{$h}' aria-hidden='true'>{$bg}{$fg}{$txt}</svg>";
    }
}
