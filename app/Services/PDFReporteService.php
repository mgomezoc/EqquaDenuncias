<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService
 *
 * Genera PDFs profesionales para reportes IA con Dompdf.
 * - Portada corporativa
 * - Encabezado/pie con numeración
 * - Limpieza/transformación de Markdown a HTML básico
 * - Inserción de logos (cliente / Eqqua) con data:base64
 *
 * @author Cesar
 * @version 2.0
 */
class PDFReporteService
{
    private Dompdf $dompdf;
    private string $outputDir;
    private string $colorPrimario;
    private string $colorSecundario;

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', FCPATH);
        $options->set('dpi', 120);

        $this->dompdf = new Dompdf($options);

        $this->outputDir = 'uploads/reportes_ia/pdfs/';
        $fullPath = FCPATH . $this->outputDir;
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0755, true);
        }

        // Paleta desde .env (fallbacks sobrios)
        $this->colorPrimario  = getenv('IA_PDF_PRIMARY_COLOR')  ?: '#1F3B5B'; // navy
        $this->colorSecundario = getenv('IA_PDF_SECONDARY_COLOR') ?: '#F2A33A'; // orange
    }

    /**
     * Genera un PDF a partir de un reporte.
     * @param array $reporte
     * @return string|false  Ruta relativa del PDF o false si falla
     */
    public function generarPDF(array $reporte)
    {
        try {
            $html = $this->generarHTML($reporte);

            // Carga y render
            $this->dompdf->loadHtml($html, 'UTF-8');
            $this->dompdf->setPaper('letter', 'portrait');
            $this->dompdf->render();

            // Numeración de páginas
            $this->inyectarNumeracion($reporte);

            // Guardado
            $filename     = $this->generarNombreArchivo($reporte);
            $rutaCompleta = FCPATH . $this->outputDir . $filename;
            file_put_contents($rutaCompleta, $this->dompdf->output());

            return $this->outputDir . $filename;
        } catch (\Throwable $e) {
            log_message('error', '[PDFReporteService] Error al generar PDF: ' . $e->getMessage());
            return false;
        }
    }

    /* ========================== HTML ========================== */

    private function generarHTML(array $reporte): string
    {
        $cliente          = $reporte['cliente_nombre']   ?? 'Cliente';
        $periodo          = $reporte['periodo_nombre']   ?? 'Sin periodo';
        $tipo             = ucfirst($reporte['tipo_reporte'] ?? 'Reporte');
        $fechaGeneracion  = $this->fmtFecha($reporte['created_at'] ?? 'now');
        $riesgo           = $reporte['puntuacion_riesgo'] ?? 'N/D';
        $estado           = strtolower($reporte['estado'] ?? 'generado');

        $logoDataUri      = $this->getLogoDataUri($reporte);
        $badgeRiesgoClass = $this->getRiesgoBadgeClass($riesgo);
        $badgeEstadoClass = $this->getEstadoBadgeClass($estado);

        $resumen          = $this->toHtml($reporte['resumen_ejecutivo'] ?? null);
        $hallazgos        = $this->toHtml($reporte['hallazgos_principales'] ?? null);
        $geo              = $this->toHtml($reporte['analisis_geografico'] ?? null);
        $cat              = $this->toHtml($reporte['analisis_categorico'] ?? null);
        $efi              = $this->toHtml($reporte['eficiencia_operativa'] ?? null);
        $sug              = $this->toHtml($reporte['sugerencias_predictivas'] ?? null);

        $css = $this->getCSS($this->colorPrimario, $this->colorSecundario);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte IA – {$periodo}</title>
  <style>{$css}</style>
</head>
<body>

<!-- ====== PORTADA ====== -->
<div class="cover">
  <div class="cover-logo">
    {$this->imgTag($logoDataUri, 'Logo', 'cover-logo-img')}
  </div>
  <h1>Reporte de Análisis de Denuncias</h1>
  <h2>{$tipo} · {$periodo}</h2>
  <table class="cover-meta">
    <tr>
      <td><strong>Cliente</strong></td><td>{$this->escape($cliente)}</td>
    </tr>
    <tr>
      <td><strong>Generado</strong></td><td>{$this->escape($fechaGeneracion)}</td>
    </tr>
    <tr>
      <td><strong>Riesgo</strong></td>
      <td><span class="badge {$badgeRiesgoClass}">{$this->escape((is_numeric($riesgo) ?$riesgo . '/10' : 'N/D'))}</span></td>
    </tr>
    <tr>
      <td><strong>Estado</strong></td>
      <td><span class="badge {$badgeEstadoClass}">{$this->escape(ucfirst($estado))}</span></td>
    </tr>
  </table>
</div>

<div class="page-break"></div>

<!-- ====== ENCABEZADO FIJO ====== -->
<div id="header">
  <div class="header-left">
    {$this->imgTag($logoDataUri, 'Eqqua', 'header-logo')}
  </div>
  <div class="header-right">
    <div class="hdr-title">{$this->escape($cliente)}</div>
    <div class="hdr-subtitle">{$this->escape($tipo)} — {$this->escape($periodo)}</div>
  </div>
</div>

<!-- ====== CONTENIDO ====== -->
<div id="content">

  <div class="section">
    <h3 class="section-title">Resumen ejecutivo</h3>
    <div class="content">{$resumen}</div>
  </div>

  <div class="section">
    <h3 class="section-title">Hallazgos principales</h3>
    <div class="content">{$hallazgos}</div>
  </div>

  <div class="section">
    <h3 class="section-title">Análisis geográfico (por sucursales)</h3>
    <div class="content">{$geo}</div>
  </div>

  <div class="section">
    <h3 class="section-title">Análisis por categorías</h3>
    <div class="content">{$cat}</div>
  </div>

  <div class="section">
    <h3 class="section-title">Eficiencia operativa</h3>
    <div class="content">{$efi}</div>
  </div>

  <div class="section alert">
    <h3 class="section-title">Sugerencias proactivas y predictivas</h3>
    <p class="alert-text">Este contenido fue generado por IA; valide antes de aplicar.</p>
    <div class="content">{$sug}</div>
  </div>

</div>

<!-- ====== PIE FIJO ====== -->
<div id="footer">
  <div class="f-left">Eqqua · Reporte IA</div>
  <div class="f-right">ID: {$this->escape((string)($reporte['id'] ?? '—'))} · {PAGE_NUM} / {PAGE_COUNT}</div>
</div>

</body>
</html>
HTML;
    }

    /* ========================== UTILIDADES RENDER ========================== */

    private function getCSS(string $c1, string $c2): string
    {
        return <<<CSS
@page {
  size: letter;
  margin: 100px 36px 70px 36px; /* top right bottom left */
}
* { box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; color:#333; font-size:11pt; line-height:1.55; }

#header {
  position: fixed; top: -70px; left: 0; right: 0; height: 70px;
  border-bottom: 2px solid {$c2};
}
.header-left { float:left; padding: 10px 0 0 6px; }
.header-right { float:right; text-align:right; padding: 14px 6px 0 0; }
.header-logo { height: 42px; }
.hdr-title { font-weight:700; font-size:12pt; color: {$c1}; }
.hdr-subtitle { font-size:9.5pt; color:#666; }

#footer {
  position: fixed; bottom: -50px; left: 0; right: 0; height: 50px;
  border-top: 1px solid #ddd; color:#666; font-size:9pt;
}
.f-left  { float:left; padding:8px 0 0 6px; }
.f-right { float:right; padding:8px 6px 0 0; }

#content { }

.cover {
  margin: 0; padding: 80px 36px 60px 36px; text-align:center;
  border-top: 12px solid {$c1};
}
.cover-logo-img { height: 72px; margin-bottom: 18px; }
.cover h1 { font-size:22pt; color: {$c1}; margin: 6px 0; }
.cover h2 { font-size:14pt; color: {$c2}; font-weight:600; margin:0 0 18px 0; }
.cover-meta { margin: 0 auto; border-collapse: collapse; font-size:11pt; }
.cover-meta td { padding: 6px 10px; border-bottom: 1px solid #eee; text-align:left; }
.cover-meta td:first-child { width: 120px; color:#666; }

.section { margin: 0 0 22px 0; page-break-inside: avoid; }
.section-title {
  background: {$c1}; color:#fff; font-weight:700; padding:10px 12px; border-radius:3px;
  font-size:13pt; margin:0 0 12px 0;
}
.content { padding: 0 6px; text-align: justify; hyphens: auto; }

.alert { background:#fff7e6; border-left:4px solid {$c2}; padding: 14px 10px; }
.alert .section-title { background: {$c2}; }
.alert-text { color:#8a6d3b; font-size:9.5pt; margin: 2px 0 8px 0; }

.badge {
  display:inline-block; padding:4px 10px; border-radius:3px; font-size:9pt; font-weight:700; text-transform:uppercase;
}
.badge-success { background:#2e7d32; color:#fff; }
.badge-warning { background:#ffca28; color:#333; }
.badge-danger  { background:#c62828; color:#fff; }
.badge-secondary{ background:#6c757d; color:#fff; }
.badge-generado { background:#6c757d; color:#fff; }
.badge-revisado { background:#17a2b8; color:#fff; }
.badge-publicado{ background:#2e7d32; color:#fff; }
.badge-archivado{ background:#343a40; color:#fff; }

ul, ol { margin: 0 0 10px 22px; }
li { margin: 4px 0; }
p { margin: 0 0 10px 0; }

.page-break { page-break-after: always; }
CSS;
    }

    private function toHtml(?string $txt): string
    {
        if (!$txt || trim($txt) === '') {
            return '<p class="text-muted">Sin contenido</p>';
        }

        // Normalizar saltos
        $t = str_replace(["\r\n", "\r"], "\n", $txt);

        // Quitar fences de código ```xxx ... ```
        $t = preg_replace('/```[\s\S]*?```/u', '', $t);

        // Encabezados markdown (#, ##, ###)
        $t = preg_replace('/^\s*###\s+(.+)$/um', '<h4>$1</h4>', $t);
        $t = preg_replace('/^\s*##\s+(.+)$/um',  '<h4>$1</h4>', $t);
        $t = preg_replace('/^\s*#\s+(.+)$/um',   '<h4>$1</h4>', $t);

        // Negritas e itálicas
        $t = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $t);
        $t = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/u', '<em>$1</em>', $t);

        // Listas con - o * o 1.
        $t = preg_replace('/^\s*[-*]\s+(.+)$/um', '<li>$1</li>', $t);
        $t = preg_replace('/^\s*\d+\.\s+(.+)$/um', '<li>$1</li>', $t);
        // Agrupar lis consecutivos en <ul>
        $t = preg_replace('/(?:<li>.*?<\/li>\s*){1,}/us', '<ul>$0</ul>', $t);

        // Escapar lo restante y mantener tags que acabamos de añadir
        $t = $this->safeHtml($t);

        // Párrafos para líneas sueltas
        $lines = array_filter(array_map('trim', explode("\n", $t)));
        $html  = '';
        foreach ($lines as $line) {
            if (preg_match('/^<(ul|ol|h4|p|li|strong|em)/i', $line)) {
                $html .= $line;
            } else {
                $html .= '<p>' . $line . '</p>';
            }
        }
        return $html;
    }

    private function safeHtml(string $html): string
    {
        // Permitimos tags básicos
        $allowed = '<p><ul><ol><li><strong><em><h4><br>';
        // Primero escapamos todo
        $escaped = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        // Revertimos las etiquetas que queremos permitir
        $escaped = str_replace(
            ['&lt;p&gt;', '&lt;/p&gt;', '&lt;ul&gt;', '&lt;/ul&gt;', '&lt;ol&gt;', '&lt;/ol&gt;', '&lt;li&gt;', '&lt;/li&gt;', '&lt;strong&gt;', '&lt;/strong&gt;', '&lt;em&gt;', '&lt;/em&gt;', '&lt;h4&gt;', '&lt;/h4&gt;', '&lt;br&gt;', '&lt;br /&gt;'],
            ['<p>', '</p>', '<ul>', '</ul>', '<ol>', '</ol>', '<li>', '</li>', '<strong>', '</strong>', '<em>', '</em>', '<h4>', '</h4>', '<br>', '<br>'],
            $escaped
        );
        return $escaped;
    }

    private function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    private function imgTag(string $dataUri, string $alt, string $class = ''): string
    {
        if ($dataUri === '') return '';
        $alt = $this->escape($alt);
        $classAttr = $class ? ' class="' . $class . '"' : '';
        return '<img src="' . $dataUri . '" alt="' . $alt . '"' . $classAttr . '>';
    }

    /* ========================== LOGOS ========================== */

    private function getLogoDataUri(array $reporte): string
    {
        // 1) logo cliente si existe
        if (!empty($reporte['cliente_logo'])) {
            $p = FCPATH . 'uploads/clientes/' . $reporte['cliente_logo'];
            if (is_file($p)) {
                return $this->fileToDataUri($p);
            }
        }

        // 2) buscar mejor logo de Eqqua en assets/images (nombres variables)
        $candidatos = [
            'assets/images/eqqua logos-05.png',
            'assets/images/eqqua logos-09.png',
            'assets/images/eqqua logos-03.png',
            'assets/images/logo_blanco.png',
            'assets/images/logo.png',
            'assets/images/favicon.png',
        ];
        // plus: glob por prefijo eqqua*
        $globs = glob(FCPATH . 'assets/images/eqqua*.*');
        if ($globs) {
            // priorizar png
            usort($globs, fn($a, $b) => (substr($b, -3) === 'png') <=> (substr($a, -3) === 'png'));
            foreach ($globs as $g) {
                if (is_file($g)) return $this->fileToDataUri($g);
            }
        }
        foreach ($candidatos as $rel) {
            $p = FCPATH . $rel;
            if (is_file($p)) return $this->fileToDataUri($p);
        }
        return '';
    }

    private function fileToDataUri(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg'  => 'image/svg+xml',
            default => 'application/octet-stream'
        };
        $data = @file_get_contents($path);
        if ($data === false) return '';
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /* ========================== FORMATO / BADGES ========================== */

    private function getRiesgoBadgeClass($riesgo): string
    {
        if (!is_numeric($riesgo)) return 'secondary';
        $r = (int)$riesgo;
        if ($r >= 7)  return 'danger';
        if ($r >= 4)  return 'warning';
        return 'success';
    }

    private function getEstadoBadgeClass(string $estado): string
    {
        return match ($estado) {
            'publicado' => 'badge-publicado',
            'revisado'  => 'badge-revisado',
            'archivado' => 'badge-archivado',
            default     => 'badge-generado'
        };
    }

    private function fmtFecha(string $ts): string
    {
        $t = is_numeric($ts) ? (int)$ts : strtotime($ts);
        return date('d/m/Y H:i', $t ?: time());
    }

    /* ========================== NUMERACIÓN ========================== */

    private function inyectarNumeracion(array $reporte): void
    {
        // Dompdf tiene tokens {PAGE_NUM} y {PAGE_COUNT} en el HTML del footer.
        // Si quieres tipografía/ubicación custom vía canvas, descomenta:
        /*
        $canvas = $this->dompdf->get_canvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text($w - 100, $h - 40, "Página {PAGE_NUM} / {PAGE_COUNT}", 'DejaVu Sans', 9, [0.4,0.4,0.4]);
        */
    }

    /* ========================== ARCHIVOS ========================== */

    private function generarNombreArchivo(array $reporte): string
    {
        $id     = $reporte['id'] ?? 'sin-id';
        $fecha  = date('Y-m-d_H-i-s');
        $periodo = $this->sanitizeFilename($reporte['periodo_nombre'] ?? 'reporte');
        return "reporte_ia_{$id}_{$periodo}_{$fecha}.pdf";
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = strtolower($filename);
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        $filename = preg_replace('/[^a-z0-9\-_]+/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        return trim($filename, '-');
    }
}
