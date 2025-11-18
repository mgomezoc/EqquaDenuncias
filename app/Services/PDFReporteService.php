<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService - v2.5.0 (DejaVu Sans only)
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
        // 👉 Usar SOLO DejaVu Sans
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultMediaType', 'print');
        $options->set('isCssFloatEnabled', true);
        $options->set('dpi', 96);
        $options->set('chroot', FCPATH);

        // Cache de fuentes en carpeta escribible
        $fontCache = WRITEPATH . 'dompdf';
        if (!is_dir($fontCache)) {
            @mkdir($fontCache, 0755, true);
        }
        $options->set('fontDir', $fontCache);
        $options->set('fontCache', $fontCache);

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
            $this->dompdf->setPaper('letter', 'portrait');
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

    /** Convierte métricas conocidas a un set de charts con orden optimizado */
    private function generarGraficasDesdeMetricas(array $metricas, array $reporte): array
    {
        $charts = [];

        // 1) Tarjetas de métrica (reemplazan gauges)
        if (isset($reporte['puntuacion_riesgo']) && is_numeric($reporte['puntuacion_riesgo'])) {
            $charts[] = [
                'tipo'    => 'metric_card',
                'titulo'  => 'Nivel de Riesgo Global',
                'leyenda' => 'Evaluación del período',
                'data'    => ['valor' => (float)$reporte['puntuacion_riesgo'], 'max' => 10],
            ];
        }

        if (isset($metricas['indice_resolucion']) && is_numeric($metricas['indice_resolucion'])) {
            $valor0a10 = (float)$metricas['indice_resolucion'];
            if ($valor0a10 > 10) {
                $valor0a10 = round(($valor0a10 / 100) * 10, 1);
            }
            $charts[] = [
                'tipo'    => 'metric_card',
                'titulo'  => 'Tasa de Resolución',
                'leyenda' => '% denuncias cerradas',
                'data'    => ['valor' => $valor0a10, 'max' => 10],
            ];
        }

        // 2) Donas
        if (!empty($metricas['distribucion_categoria'])) {
            $data = [];
            $c = 0;
            foreach ($metricas['distribucion_categoria'] as $it) {
                if ($c >= 8) break;
                $cat   = $it['categoria'] ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) {
                    $data[$cat] = $total;
                    $c++;
                }
            }
            if ($data) {
                $charts[] = [
                    'tipo'    => 'donut',
                    'titulo'  => 'Distribución por Categoría',
                    'leyenda' => 'Total de denuncias: ' . array_sum($data),
                    'data'    => $data,
                ];
            }
        }

        if (!empty($metricas['distribucion_medio'])) {
            $data = [];
            foreach ($metricas['distribucion_medio'] as $it) {
                $medio = $it['medio'] ?? $it['nombre'] ?? $it['label'] ?? 'N/D';
                $total = (int)($it['total'] ?? $it['valor'] ?? 0);
                if ($total > 0) $data[$medio] = $total;
            }
            if ($data) {
                $charts[] = [
                    'tipo'    => 'donut',
                    'titulo'  => 'Canales de Reporte',
                    'leyenda' => 'Medios utilizados',
                    'data'    => $data,
                ];
            }
        }

        // 3) Barras
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
                    'tipo'    => 'bar',
                    'titulo'  => 'Top 5 Sucursales',
                    'leyenda' => 'Mayor número de denuncias',
                    'data'    => $data,
                ];
            }
        }

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
                    'tipo'    => 'bar',
                    'titulo'  => 'Top 5 Departamentos',
                    'leyenda' => 'Departamentos con más incidencias',
                    'data'    => $data,
                ];
            }
        }

        return $charts;
    }

    /** Sección de gráficas reorganizada */
    private function generarSeccionGraficas(array $reporte): string
    {
        $charts = $reporte['charts'] ?? [];
        if (empty($charts)) return '';

        $metricCards = [];
        $donuts = [];
        $bars = [];

        foreach ($charts as $chart) {
            switch ($chart['tipo'] ?? '') {
                case 'metric_card':
                    $metricCards[] = $chart;
                    break;
                case 'donut':
                    $donuts[]      = $chart;
                    break;
                case 'bar':
                    $bars[]        = $chart;
                    break;
            }
        }

        $html  = '<div class="charts-section">';
        $html .= '<div class="charts-header">Análisis Visual</div>';
        $html .= '<div class="charts-container">';

        // Tarjetas de métrica (2 por fila)
        if (!empty($metricCards)) {
            $html .= '<table class="chart-grid"><tr>';
            foreach ($metricCards as $i => $chart) {
                if ($i > 0 && $i % 2 === 0) $html .= '</tr><tr>';
                $html .= '<td class="chart-cell">' . $this->renderChart($chart, 'small') . '</td>';
            }
            if (count($metricCards) % 2 !== 0) $html .= '<td class="chart-cell"></td>';
            $html .= '</tr></table>';
        }

        // Donas
        if (!empty($donuts)) {
            foreach ($donuts as $chart) {
                $itemCount = count($chart['data'] ?? []);
                if ($itemCount > 4) {
                    $html .= '<div class="chart-single">' . $this->renderChart($chart, 'large') . '</div>';
                } else {
                    $html .= '<table class="chart-grid"><tr><td class="chart-cell-full">' .
                        $this->renderChart($chart, 'medium') .
                        '</td></tr></table>';
                }
            }
        }

        // Barras (una por fila)
        if (!empty($bars)) {
            foreach ($bars as $chart) {
                $html .= '<div class="chart-single">' . $this->renderChart($chart, 'large') . '</div>';
            }
        }

        $html .= '</div></div>';
        return $html;
    }

    /** Render de un gráfico con tamaño variable */
    private function renderChart(array $chart, string $size = 'medium'): string
    {
        $tipo   = $chart['tipo']   ?? 'donut';
        $titulo = $chart['titulo'] ?? 'Gráfico';
        $data   = $chart['data']   ?? [];

        $html = '<div class="chart-box ' . $size . '">';
        $html .= '<div class="chart-title">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="center">';

        $legendHtml = '';
        $svg = '';

        switch ($tipo) {
            case 'metric_card':
                $valor = (float)($data['valor'] ?? 0);
                $max   = (float)($data['max']   ?? 10);
                $svg   = $this->metricCardSVG($valor, $max);
                break;

            case 'donut':
                $palette = $this->palette();
                [$svg, $legendHtml] = $this->donutSVGConLeyenda($data, $palette, $size);
                break;

            case 'bar':
                $palette = $this->palette();
                [$svg, $legendHtml] = $this->barsSVGConTabla($data, $palette);
                break;

            default:
                $html .= '<p class="text-muted">Tipo no soportado</p>';
        }

        if ($svg !== '') {
            $maxWidth = ($size === 'small') ? 240 : (($size === 'large') ? 400 : 320);
            $html .= $this->svgAImagen($svg, $maxWidth);
        }

        $html .= '</div>';

        if ($legendHtml) {
            $html .= $legendHtml;
        }

        if (!empty($chart['leyenda']) && $tipo !== 'bar') {
            $html .= '<div class="chart-subtitle">' . htmlspecialchars($chart['leyenda']) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /** Indicador de métrica tipo tarjeta ejecutiva */
    private function metricCardSVG(float $value, float $max = 10, string $label = ''): string
    {
        $max = $max > 0 ? $max : 10;
        $value = max(0.0, min($max, $value));

        $w = 240;
        $h = 120;

        // Color por valor
        if ($value >= 7)       $color = self::COLOR_DANGER;
        elseif ($value >= 4)   $color = self::COLOR_ACCENT;
        else                   $color = '#10b981';

        $percentage  = ($value / $max) * 100;
        $barWidth    = 200;
        $filledWidth = ($percentage / 100) * $barWidth;

        $svg  = "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>";
        $svg .= "<rect x='10' y='10' width='220' height='100' fill='#f8f9fa' stroke='#e9ecef' stroke-width='1' rx='6'/>";
        $svg .= "<text x='120' y='50' text-anchor='middle' font-size='36' font-weight='700' fill='$color'>" . number_format($value, 1) . "</text>";
        $svg .= "<text x='120' y='68' text-anchor='middle' font-size='12' fill='" . self::COLOR_TEXT_LIGHT . "'>de " . number_format($max, 0) . "</text>";
        $barY = 82;
        $barX = 20;
        $svg .= "<rect x='$barX' y='$barY' width='$barWidth' height='8' fill='#e5e7eb' rx='4'/>";
        $svg .= "<rect x='$barX' y='$barY' width='$filledWidth' height='8' fill='$color' rx='4'/>";
        $svg .= "<text x='120' y='103' text-anchor='middle' font-size='10' font-weight='600' fill='$color'>" . number_format($percentage, 1) . "%</text>";
        $svg .= "</svg>";
        return $svg;
    }

    /** Donut + leyenda (tabla o columnas) */
    private function donutSVGConLeyenda(array $data, array $palette, string $size = 'medium'): array
    {
        if (empty($data)) return ['', ''];

        $w = 280;
        $h = 240;
        $cx = 140;
        $cy = 120;
        $r = 85;
        $rInner = 55;
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

        $centerText  = "<text x='$cx' y='" . ($cy - 5) . "' text-anchor='middle' font-size='24' font-weight='700' fill='" . self::COLOR_PRIMARY . "'>$total</text>";
        $centerLabel = "<text x='$cx' y='" . ($cy + 12) . "' text-anchor='middle' font-size='11' fill='" . self::COLOR_TEXT_LIGHT . "'>Total</text>";
        $svg = "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$segments$centerText$centerLabel</svg>";

        $itemCount = count($data);
        $legendHtml = ($itemCount > 4)
            ? $this->renderLeyendaTablaColumnas($data, $colors, $total)
            : $this->renderLeyendaTabla($data, $colors, $total);

        return [$svg, $legendHtml];
    }

    /** Barras horizontales + tabla compacta */
    private function barsSVGConTabla(array $data, array $palette): array
    {
        if (empty($data)) return ['', ''];

        $w = 380;
        $labelWidth = 140;
        $barMaxWidth = 200;
        $barHeight = 24;
        $gap = 8;
        $h = (count($data) * ($barHeight + $gap)) + 20;

        $max = max($data);
        if ($max <= 0) return ['', ''];

        $bars = '';
        $y = 10;
        $i = 0;

        foreach ($data as $label => $value) {
            $barWidth = ($value / $max) * $barMaxWidth;
            $color = $palette[$i % count($palette)];
            $labelCorto = mb_strimwidth($label, 0, 20, '…', 'UTF-8');

            $bars .= "<text x='5' y='" . ($y + $barHeight / 2 + 4) . "' font-size='9' fill='" . self::COLOR_TEXT . "'>$labelCorto</text>";
            $bars .= "<rect x='{$labelWidth}' y='$y' width='$barWidth' height='$barHeight' fill='$color' opacity='0.9' rx='2'/>";
            $bars .= "<text x='" . ($labelWidth + $barWidth + 5) . "' y='" . ($y + $barHeight / 2 + 4) . "' font-size='10' font-weight='700' fill='" . self::COLOR_TEXT . "'>$value</text>";

            $y += $barHeight + $gap;
            $i++;
        }

        $svg = "<svg width='$w' height='$h' viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg'>$bars</svg>";

        $colors = [];
        $i = 0;
        foreach ($data as $label => $_) {
            $colors[$label] = $palette[$i % count($palette)];
            $i++;
        }
        $legendHtml = $this->renderLeyendaTablaCompacta($data, $colors, array_sum($data));

        return [$svg, $legendHtml];
    }

    /** Leyenda estándar (tabla) */
    private function renderLeyendaTabla(array $data, array $colors, int $total): string
    {
        if ($total <= 0) return '';
        $rows = '';
        foreach ($data as $label => $value) {
            $pct = ($value / $total) * 100;
            $color = $colors[$label] ?? self::COLOR_PRIMARY;
            $rows .= '<tr>'
                . '<td class="label"><span class="swatch" style="background:' . $color . '"></span>' . htmlspecialchars($label) . '</td>'
                . '<td class="value">' . (int)$value . '</td>'
                . '<td class="pct">' . number_format($pct, 1) . '%</td>'
                . '</tr>';
        }
        return '<div class="legend"><table>' . $rows . '</table></div>';
    }

    /** Leyenda en dos columnas (para muchas categorías) */
    private function renderLeyendaTablaColumnas(array $data, array $colors, int $total): string
    {
        if ($total <= 0) return '';

        $items = [];
        foreach ($data as $label => $value) {
            $items[] = [
                'label' => $label,
                'value' => (int)$value,
                'pct'   => ($value / $total) * 100,
                'color' => $colors[$label] ?? self::COLOR_PRIMARY,
            ];
        }

        $halfPoint = ceil(count($items) / 2);
        $column1 = array_slice($items, 0, $halfPoint);
        $column2 = array_slice($items, $halfPoint);

        $html = '<div class="legend-columns"><table><tr><td class="legend-col"><table class="legend-inner">';
        foreach ($column1 as $it) {
            $html .= '<tr>'
                . '<td><span class="swatch" style="background:' . $it['color'] . '"></span></td>'
                . '<td class="label">' . htmlspecialchars($it['label']) . '</td>'
                . '<td class="value">' . $it['value'] . '</td>'
                . '<td class="pct">' . number_format($it['pct'], 1) . '%</td>'
                . '</tr>';
        }
        $html .= '</table></td><td class="legend-col"><table class="legend-inner">';
        foreach ($column2 as $it) {
            $html .= '<tr>'
                . '<td><span class="swatch" style="background:' . $it['color'] . '"></span></td>'
                . '<td class="label">' . htmlspecialchars($it['label']) . '</td>'
                . '<td class="value">' . $it['value'] . '</td>'
                . '<td class="pct">' . number_format($it['pct'], 1) . '%</td>'
                . '</tr>';
        }
        $html .= '</table></td></tr></table></div>';

        return $html;
    }

    /** Leyenda compacta (para barras) */
    private function renderLeyendaTablaCompacta(array $data, array $colors, int $total): string
    {
        if ($total <= 0) return '';
        $rows = '';
        foreach ($data as $label => $value) {
            $pct = ($value / $total) * 100;
            $color = $colors[$label] ?? self::COLOR_PRIMARY;
            $rows .= '<tr>'
                . '<td class="label-compact"><span class="swatch" style="background:' . $color . '"></span>' . htmlspecialchars($label) . '</td>'
                . '<td class="value-compact">' . (int)$value . ' (' . number_format($pct, 1) . '%)</td>'
                . '</tr>';
        }
        return '<div class="legend-compact"><table>' . $rows . '</table></div>';
    }

    /** ---------- Estilos y utilidades ---------- */

    private function getCSS(): string
    {
        $c1 = self::COLOR_PRIMARY;
        $c2 = self::COLOR_SECONDARY;
        $c3 = self::COLOR_ACCENT;
        $cDanger = self::COLOR_DANGER;
        $cTxt = self::COLOR_TEXT;
        $cTxtLight = self::COLOR_TEXT_LIGHT;

        return <<<CSS
@page { margin: 2.5cm 2cm 3cm 2cm; }
* { box-sizing: border-box; margin:0; padding:0; }

/* 👉 DejaVu Sans como única fuente */
body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size:10.5pt; color:{$cTxt}; line-height:1.6; margin:12px; }

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
.info { background:#f8f9fa; border-left:5px solid {$c2}; padding:16px; margin-bottom:22px; border-radius:4px; }
.info-table { width:100%; border-collapse:collapse; }
.info-table .label { width:22%; color:{$cTxtLight}; font-weight:700; padding:6px 10px 6px 0; font-size:9pt; text-transform:uppercase; letter-spacing:.4px; }
.info-table .value { width:28%; padding:6px 10px; font-size:10pt; font-weight:600; }

/* Badges */
.badge { display:inline-block; padding:5px 10px; border-radius:4px; font-size:8.5pt; font-weight:800; text-transform:uppercase; letter-spacing:.4px; }
.badge.success { background:#10b981; color:#fff; }
.badge.warning { background:{$c3}; color:#000; }
.badge.danger  { background:{$cDanger}; color:#fff; }
.badge.secondary { background:#6c757d; color:#fff; }

/* Secciones */
.section { margin:20px 0; page-break-inside:avoid; }
.section-head { background:{$c1}; color:#fff; padding:10px 14px; font-weight:800; font-size:11pt; border-radius:4px 4px 0 0; letter-spacing:.2px; }
.section-body { padding:14px 16px; text-align:justify; background:#fff; border:1px solid #e9ecef; border-top:none; border-radius:0 0 4px 4px; }
.section-body p { margin:0 0 10px; }
.section-body ul { margin:8px 0 8px 20px; list-style:disc; }
.section-body li { margin-bottom:6px; }
.section-body strong { font-weight:700; color:{$c1}; }

/* Alerta */
.alert { background:#fffbf0; border:2px solid {$c3}; border-radius:4px; }
.alert-head { background:{$c3}; color:#000; font-weight:800; }
.alert-note { background:#fff3cd; color:#856404; padding:8px 14px; font-size:9pt; border-left:4px solid #ffc107; font-weight:600; font-style:italic; }

/* Gráficas */
.charts-section { margin:24px 0; page-break-inside:avoid; }
.charts-header { background:{$c2}; color:#fff; padding:10px 14px; font-weight:800; font-size:11pt; border-radius:4px 4px 0 0; }
.charts-container { background:#f8f9fa; padding:16px; border:1px solid #e9ecef; border-top:none; border-radius:0 0 4px 4px; }
.chart-grid { width:100%; border-collapse:separate; border-spacing:12px; }
.chart-cell { width:50%; vertical-align:top; }
.chart-cell-full { width:100%; vertical-align:top; }
.chart-single { margin:12px 0; }
.chart-box { background:#fff; border:1px solid #dee2e6; border-radius:6px; padding:12px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
.chart-box.small { padding:10px; }
.chart-box.large { padding:14px; }
.chart-title { font-weight:800; color:{$c1}; margin-bottom:10px; font-size:10pt; text-align:center; text-transform:uppercase; letter-spacing:.4px; }
.chart-subtitle { font-size:8pt; color:{$cTxtLight}; text-align:center; margin-top:6px; font-style:italic; }
.center { text-align:center; }
.text-muted { color:{$cTxtLight}; font-style:italic; }

/* Leyendas */
.legend { margin:10px auto 0; width:100%; }
.legend table { width:100%; border-collapse:collapse; font-size:8.5pt; }
.legend td { padding:3px 4px; }
.legend .swatch { display:inline-block; width:9px; height:9px; border-radius:2px; margin-right:5px; vertical-align:middle; }
.legend .label { width:60%; }
.legend .value { text-align:right; white-space:nowrap; font-weight:600; }
.legend .pct { text-align:right; white-space:nowrap; color:{$cTxtLight}; font-size:8pt; }

/* Leyenda en columnas */
.legend-columns { margin:10px auto 0; }
.legend-columns table { width:100%; border-collapse:collapse; }
.legend-col { width:50%; vertical-align:top; padding:0 5px; }
.legend-inner { width:100%; font-size:8pt; }
.legend-inner td { padding:2px 2px; }
.legend-inner .swatch { display:inline-block; width:8px; height:8px; border-radius:2px; margin-right:4px; vertical-align:middle; }
.legend-inner .label { font-size:8pt; }
.legend-inner .value { text-align:right; font-weight:600; font-size:8pt; }
.legend-inner .pct { text-align:right; color:{$cTxtLight}; font-size:7.5pt; }

/* Leyenda compacta */
.legend-compact { margin:8px auto 0; }
.legend-compact table { width:100%; font-size:8pt; }
.legend-compact td { padding:2px 4px; }
.label-compact { width:70%; }
.value-compact { text-align:right; font-weight:600; }
CSS;
    }

    private function agregarFooter(): void
    {
        $this->dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $left   = 'Eqqua · Reporte IA';
            $right  = "Página {$pageNumber} de {$pageCount}";
            // 👉 Usar DejaVu Sans también aquí
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
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

    private function svgAImagen(string $svg, int $maxWidth = 320): string
    {
        if (trim($svg) === '') return '<p class="text-muted">Sin datos</p>';
        $b64 = base64_encode($svg);
        return '<img alt="gráfica" style="display:block;margin:0 auto;max-width:' . $maxWidth . 'px;width:100%;height:auto" src="data:image/svg+xml;base64,' . $b64 . '">';
    }

    /** ------------------- HTML del reporte ------------------- */

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

    /**
     * Normaliza texto a párrafos y listas.
     */
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
}
