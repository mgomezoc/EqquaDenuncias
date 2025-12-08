<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDFReporteService - v5.1.0
 * Diseño exacto según PDF del cliente (Reporta_IA_Eqqua.pdf)
 */
class PDFReporteService
{
    private Dompdf $dompdf;
    private string $directorioSalida;

    // Paleta de colores exacta del diseño del cliente
    private const COLOR_AZUL_OSCURO   = '#1B4F72';
    private const COLOR_AZUL          = '#2874A6';
    private const COLOR_TURQUESA      = '#17A589';
    private const COLOR_AMARILLO      = '#F4D03F';
    private const COLOR_ROJO          = '#E74C3C';
    private const COLOR_ROSA          = '#EC7063';
    private const COLOR_MORADO        = '#8E44AD';
    private const COLOR_NARANJA       = '#E67E22';
    private const COLOR_VERDE         = '#27AE60';
    private const COLOR_TEXTO         = '#2C3E50';
    private const COLOR_TEXTO_CLARO   = '#7F8C8D';

    public function __construct()
    {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', true);
        $opciones->set('isHtml5ParserEnabled', true);
        $opciones->set('isFontSubsettingEnabled', true);
        $opciones->set('defaultFont', 'DejaVu Sans');
        $opciones->set('defaultMediaType', 'print');
        $opciones->set('isCssFloatEnabled', true);
        $opciones->set('dpi', 96);
        $opciones->set('chroot', FCPATH);

        $cacheFuentes = WRITEPATH . 'dompdf';
        if (!is_dir($cacheFuentes)) {
            @mkdir($cacheFuentes, 0755, true);
        }
        $opciones->set('fontDir', $cacheFuentes);
        $opciones->set('fontCache', $cacheFuentes);

        $this->dompdf = new Dompdf($opciones);

        $this->directorioSalida = 'uploads/reportes_ia/pdfs/';
        $rutaCompleta = FCPATH . $this->directorioSalida;
        if (!is_dir($rutaCompleta)) {
            @mkdir($rutaCompleta, 0755, true);
        }
    }

    /**
     * Genera el PDF y retorna la ruta relativa
     */
    public function generarPDF(array $reporte)
    {
        try {
            if (!isset($reporte['charts']) && !empty($reporte['metricas'])) {
                $reporte['charts'] = $this->generarGraficasDesdeMetricas($reporte['metricas'], $reporte);
            }

            $html = $this->generarHTML($reporte);

            $this->dompdf->loadHtml($html);
            // 👉 El diseño del cliente es A4 sin márgenes
            $this->dompdf->setPaper('A4', 'portrait');
            $this->dompdf->render();

            $nombreArchivo = $this->generarNombreArchivo($reporte);
            $rutaArchivo = FCPATH . $this->directorioSalida . $nombreArchivo;

            file_put_contents($rutaArchivo, $this->dompdf->output());
            return $this->directorioSalida . $nombreArchivo;
        } catch (\Throwable $e) {
            log_message('error', '[PDFReporteService] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera el HTML completo del reporte
     */
    private function generarHTML(array $reporte): string
    {
        $cliente = $reporte['cliente_nombre'] ?? 'Cliente';
        $periodo = $reporte['periodo_nombre'] ?? 'Sin periodo';
        $logoClienteHtml = $this->obtenerLogoCliente($reporte);
        $logoEqquaHtml = $this->obtenerLogoEqqua();

        $css = $this->obtenerCSS();

        $paginaPortada   = $this->generarPortada($periodo, $logoClienteHtml, $logoEqquaHtml);
        $paginaContenido = $this->generarPaginaContenido($reporte, $logoEqquaHtml);
        $paginasAnalisis = $this->generarPaginasAnalisis($reporte, $logoEqquaHtml);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Análisis de Denuncias - {$cliente}</title>
<style>{$css}</style>
</head>
<body>
{$paginaPortada}
{$paginaContenido}
{$paginasAnalisis}
</body>
</html>
HTML;
    }

    /**
     * Genera la portada según diseño del cliente
     */
    private function generarPortada(string $periodo, string $logoClienteHtml, string $logoEqquaHtml): string
    {
        return <<<HTML
<div class="pagina portada">
    <!-- Decoración izquierda -->
    <div class="portada-decoracion-izq">
        <div class="deco-barra deco-morado"></div>
        <div class="deco-barra deco-turquesa"></div>
        <div class="deco-barra deco-amarillo"></div>
        <div class="deco-barra deco-rojo"></div>
    </div>
    
    <div class="portada-contenido">
        <div class="portada-periodo">{$periodo}</div>
        <h1 class="portada-titulo">Reporte de análisis<br>de denuncias</h1>
        <div class="portada-logo-cliente">{$logoClienteHtml}</div>
    </div>
    
    <div class="portada-footer">
        {$logoEqquaHtml}
    </div>
</div>
HTML;
    }

    /**
     * Genera la página de contenido textual (resumen, hallazgos, eficiencia)
     */
    private function generarPaginaContenido(array $reporte, string $logoEqquaHtml): string
    {
        $html = '<div class="pagina contenido">';

        // Resumen ejecutivo
        if (!empty($reporte['resumen_ejecutivo'])) {
            $resumen = $this->formatearTextoSeccion($reporte['resumen_ejecutivo']);
            $html .= <<<HTML
<div class="seccion">
    <div class="seccion-titulo azul">Resumen ejecutivo</div>
    <div class="seccion-contenido">{$resumen}</div>
</div>
HTML;
        }

        // Hallazgos principales
        if (!empty($reporte['hallazgos_principales'])) {
            $hallazgos = $this->formatearTextoSeccion($reporte['hallazgos_principales']);
            $html .= <<<HTML
<div class="seccion">
    <div class="seccion-titulo azul">Hallazgos principales</div>
    <div class="seccion-contenido">{$hallazgos}</div>
</div>
HTML;
        }

        // Eficiencia operativa
        if (!empty($reporte['eficiencia_operativa'])) {
            $eficiencia = $this->formatearTextoSeccion($reporte['eficiencia_operativa']);
            $html .= <<<HTML
<div class="seccion">
    <div class="seccion-titulo azul">Eficiencia operativa</div>
    <div class="seccion-contenido">{$eficiencia}</div>
</div>
HTML;
        }

        $html .= '<div class="pie-pagina">' . $logoEqquaHtml . '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Genera las páginas de análisis visual:
     * - Página 1: métricas + primera dona
     * - Páginas siguientes: cada dona restante en su propia hoja
     * - Otra página: barras (Top 5 Sucursales / Departamentos)
     * - Última página: Sugerencias proactivas SIEMPRE en hoja nueva
     */
    private function generarPaginasAnalisis(array $reporte, string $logoEqquaHtml): string
    {
        $graficas = $reporte['charts'] ?? [];
        if (empty($graficas)) {
            $graficas = $this->generarGraficasDesdeMetricas($reporte['metricas'] ?? [], $reporte);
        }

        $tarjetasMetricas = [];
        $donas = [];
        $barras = [];

        foreach ($graficas as $grafica) {
            switch ($grafica['tipo'] ?? '') {
                case 'metric_card':
                    $tarjetasMetricas[] = $grafica;
                    break;
                case 'donut':
                    $donas[] = $grafica;
                    break;
                case 'bar':
                    $barras[] = $grafica;
                    break;
            }
        }

        $htmlPaginas = '';

        /** ===================== PÁGINA 1 ANÁLISIS (MÉTRICAS + 1ª DONA) ===================== */
        if (!empty($tarjetasMetricas) || !empty($donas)) {
            $htmlPaginas .= '<div class="pagina contenido">';
            $htmlPaginas .= '<div class="seccion-titulo rojo">Análisis visual</div>';

            // Tarjetas de métricas (dos por fila)
            if (!empty($tarjetasMetricas)) {
                $htmlPaginas .= '<table class="metricas-grid"><tr>';
                foreach ($tarjetasMetricas as $indice => $metrica) {
                    $htmlPaginas .= '<td class="metrica-celda">' . $this->renderizarTarjetaMetrica($metrica) . '</td>';
                    if ($indice === 0 && count($tarjetasMetricas) === 1) {
                        // Espacio en blanco para mantener el equilibrio visual
                        $htmlPaginas .= '<td class="metrica-celda"></td>';
                    }
                }
                $htmlPaginas .= '</tr></table>';
            }

            // Primera dona (por diseño solo una en esta hoja)
            if (!empty($donas)) {
                $primeraDona = array_shift($donas);
                $htmlPaginas .= '<div class="separador-seccion"></div>';
                $htmlPaginas .= $this->renderizarSeccionDona($primeraDona);
            }

            $htmlPaginas .= '<div class="pie-pagina">' . $logoEqquaHtml . '</div>';
            $htmlPaginas .= '</div>';
        }

        /** ===================== PÁGINAS DE DONAS RESTANTES ===================== */
        if (!empty($donas)) {
            foreach ($donas as $dona) {
                $htmlPaginas .= '<div class="pagina contenido">';
                // En el diseño de ejemplo la segunda dona aparece con el título de la sección,
                // pero mantenemos la barra roja para consistencia visual.
                $htmlPaginas .= '<div class="seccion-titulo rojo">Análisis visual</div>';
                $htmlPaginas .= $this->renderizarSeccionDona($dona);
                $htmlPaginas .= '<div class="pie-pagina">' . $logoEqquaHtml . '</div>';
                $htmlPaginas .= '</div>';
            }
        }

        /** ===================== PÁGINA DE BARRAS (TOP 5) ===================== */
        if (!empty($barras)) {
            $htmlPaginas .= '<div class="pagina contenido">';
            $htmlPaginas .= '<div class="seccion-titulo rojo">Análisis visual</div>';

            foreach ($barras as $indice => $barra) {
                $htmlPaginas .= $this->renderizarSeccionBarras($barra);
                if ($indice < count($barras) - 1) {
                    $htmlPaginas .= '<div class="separador-seccion"></div>';
                }
            }

            $htmlPaginas .= '<div class="pie-pagina">' . $logoEqquaHtml . '</div>';
            $htmlPaginas .= '</div>';
        }

        /** ===================== PÁGINA EXCLUSIVA DE SUGERENCIAS ===================== */
        if (!empty($reporte['sugerencias_predictivas'])) {
            $sugerencias = $this->formatearTextoSeccion($reporte['sugerencias_predictivas']);

            $htmlPaginas .= '<div class="pagina contenido">';
            $htmlPaginas .= <<<HTML
<div class="seccion-sugerencias">
    <div class="sugerencias-titulo">Sugerencias proactivas y predictivas</div>
    <div class="sugerencias-nota">Este contenido fue generado por Inteligencia Artificial (GPT-4o). Revíselo antes de su aplicación.</div>
    <div class="sugerencias-contenido">{$sugerencias}</div>
</div>
HTML;
            $htmlPaginas .= '<div class="pie-pagina">' . $logoEqquaHtml . '</div>';
            $htmlPaginas .= '</div>';
        }

        return $htmlPaginas;
    }

    /**
     * Renderiza tarjeta de métrica con barra de progreso
     */
    private function renderizarTarjetaMetrica(array $metrica): string
    {
        $valor = (float)($metrica['data']['valor'] ?? 0);
        $maximo = (float)($metrica['data']['max'] ?? 10);
        $titulo = $metrica['titulo'] ?? 'Métrica';

        $porcentaje = $maximo > 0 ? round(($valor / $maximo) * 100) : 0;

        // Color según valor (amarillo para riesgo, turquesa para resolución)
        if (stripos($titulo, 'Riesgo') !== false) {
            $colorValor = self::COLOR_AMARILLO;
        } else {
            $colorValor = self::COLOR_TURQUESA;
        }

        return <<<HTML
<div class="tarjeta-metrica">
    <div class="metrica-titulo">{$titulo}</div>
    <div class="metrica-valor" style="color: {$colorValor}">{$valor}</div>
    <div class="metrica-de">de {$maximo}</div>
    <table class="metrica-barra-tabla">
        <tr>
            <td class="metrica-barra-td">
                <div class="metrica-barra-fondo">
                    <div class="metrica-barra-progreso" style="width: {$porcentaje}%; background-color: {$colorValor}"></div>
                </div>
            </td>
            <td class="metrica-porcentaje-td">{$porcentaje} %</td>
        </tr>
    </table>
</div>
HTML;
    }

    /**
     * Renderiza sección de dona (leyenda izquierda, gráfica derecha)
     */
    private function renderizarSeccionDona(array $dona): string
    {
        $titulo = $dona['titulo'] ?? 'Distribución';
        $datos = $dona['data'] ?? [];
        $total = array_sum($datos);

        if (empty($datos) || $total <= 0) {
            return '<div class="seccion-dona"><p class="sin-datos">Sin datos disponibles</p></div>';
        }

        $paleta = $this->obtenerPaletaDonas();
        $colores = [];
        $indice = 0;
        foreach ($datos as $etiqueta => $valor) {
            $colores[$etiqueta] = $paleta[$indice % count($paleta)];
            $indice++;
        }

        // SVG de la dona
        $svgDona = $this->crearSVGDona($datos, $colores, $total);

        // Leyenda como tabla
        $leyendaHtml = '<table class="dona-leyenda-tabla">';
        foreach ($datos as $etiqueta => $valor) {
            $color = $colores[$etiqueta];
            $leyendaHtml .= <<<HTML
<tr>
    <td class="leyenda-color-td"><span class="leyenda-color" style="background-color: {$color}"></span></td>
    <td class="leyenda-texto-td">{$etiqueta}</td>
    <td class="leyenda-valor-td">{$valor}</td>
</tr>
HTML;
        }
        $leyendaHtml .= '</table>';

        return <<<HTML
<div class="seccion-dona">
    <div class="dona-titulo">{$titulo}</div>
    <table class="dona-layout">
        <tr>
            <td class="dona-leyenda-celda">{$leyendaHtml}</td>
            <td class="dona-grafica-celda">{$svgDona}</td>
        </tr>
    </table>
</div>
HTML;
    }

    /**
     * Crea SVG de dona con porcentajes externos
     */
    private function crearSVGDona(array $datos, array $colores, int $total): string
    {
        $size = 200;
        $cx = 100;
        $cy = 100;
        $r = 70;
        $ri = 45;

        $segmentos = '';
        $etiquetas = '';
        $anguloInicio = -90;

        foreach ($datos as $etiqueta => $valor) {
            $porcentaje = ($valor / $total) * 100;
            $angulo = ($valor / $total) * 360;
            $anguloFin = $anguloInicio + $angulo;
            $anguloMedio = $anguloInicio + ($angulo / 2);

            $x1 = $cx + $r * cos(deg2rad($anguloInicio));
            $y1 = $cy + $r * sin(deg2rad($anguloInicio));
            $x2 = $cx + $r * cos(deg2rad($anguloFin));
            $y2 = $cy + $r * sin(deg2rad($anguloFin));

            $x1i = $cx + $ri * cos(deg2rad($anguloInicio));
            $y1i = $cy + $ri * sin(deg2rad($anguloInicio));
            $x2i = $cx + $ri * cos(deg2rad($anguloFin));
            $y2i = $cy + $ri * sin(deg2rad($anguloFin));

            $arcoGrande = ($angulo > 180) ? 1 : 0;
            $color = $colores[$etiqueta];

            $ruta = "M {$x1},{$y1} A {$r},{$r} 0 {$arcoGrande},1 {$x2},{$y2} L {$x2i},{$y2i} A {$ri},{$ri} 0 {$arcoGrande},0 {$x1i},{$y1i} Z";
            $segmentos .= "<path d='{$ruta}' fill='{$color}'/>";

            // Etiqueta de porcentaje fuera de la dona
            if ($porcentaje >= 5) {
                $radioEtiqueta = $r + 15;
                $xEtiqueta = $cx + $radioEtiqueta * cos(deg2rad($anguloMedio));
                $yEtiqueta = $cy + $radioEtiqueta * sin(deg2rad($anguloMedio));
                $textoPorc = number_format($porcentaje, 1) . '%';
                $etiquetas .= "<text x='{$xEtiqueta}' y='{$yEtiqueta}' text-anchor='middle' dominant-baseline='middle' fill='" . self::COLOR_TEXTO . "' font-size='9' font-weight='600'>{$textoPorc}</text>";
            }

            $anguloInicio = $anguloFin;
        }

        // Centro con total
        $centro = "<text x='{$cx}' y='" . ($cy - 5) . "' text-anchor='middle' font-size='28' font-weight='700' fill='" . self::COLOR_AZUL . "'>{$total}</text>";
        $centro .= "<text x='{$cx}' y='" . ($cy + 15) . "' text-anchor='middle' font-size='11' fill='" . self::COLOR_TEXTO_CLARO . "'>Total</text>";

        $svg = "<svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>{$segmentos}{$etiquetas}{$centro}</svg>";

        return "<img src='data:image/svg+xml;base64," . base64_encode($svg) . "' style='width:{$size}px;height:{$size}px;display:block;margin:0 auto;'>";
    }

    /**
     * Renderiza sección de barras horizontales
     */
    private function renderizarSeccionBarras(array $barra): string
    {
        $titulo = $barra['titulo'] ?? 'Top 5';
        $datos = $barra['data'] ?? [];
        $total = array_sum($datos);
        $maximo = !empty($datos) ? max($datos) : 1;

        if (empty($datos)) {
            return '<div class="seccion-barras"><p class="sin-datos">Sin datos disponibles</p></div>';
        }

        $paleta = $this->obtenerPaletaBarras();
        $barrasHtml = '<table class="barras-tabla">';
        $indice = 0;

        foreach ($datos as $etiqueta => $valor) {
            $porcentaje = $total > 0 ? round(($valor / $total) * 100, 1) : 0;
            $anchoBarra = $maximo > 0 ? round(($valor / $maximo) * 100) : 0;
            $color = $paleta[$indice % count($paleta)];
            $etiquetaCorta = mb_strtoupper(mb_substr($etiqueta, 0, 20));

            $barrasHtml .= <<<HTML
<tr class="barra-fila">
    <td class="barra-etiqueta-td">{$etiquetaCorta}</td>
    <td class="barra-contenedor-td">
        <div class="barra-fondo">
            <div class="barra-relleno" style="width: {$anchoBarra}%; background-color: {$color};">
                <span class="barra-porcentaje">{$porcentaje}%</span>
            </div>
        </div>
    </td>
    <td class="barra-valor-td">{$valor}</td>
</tr>
HTML;
            $indice++;
        }
        $barrasHtml .= '</table>';

        return <<<HTML
<div class="seccion-barras">
    <div class="barras-titulo">{$titulo}</div>
    {$barrasHtml}
</div>
HTML;
    }

    /**
     * Formatea texto para secciones
     */
    private function formatearTextoSeccion(?string $texto): string
    {
        if (empty($texto)) {
            return '<p class="sin-datos">Sin contenido disponible</p>';
        }

        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        // Convertir listas numeradas y bullets
        $texto = preg_replace('/^(\d+)\.\s+(.+)$/m', '<li>$2</li>', $texto);
        $texto = preg_replace('/^[\-\*•]\s+(.+)$/m', '<li>$1</li>', $texto);

        // Envolver listas
        $texto = preg_replace_callback('/(<li>.*?<\/li>\s*)+/s', function ($match) {
            return '<ul>' . $match[0] . '</ul>';
        }, $texto);

        // Convertir párrafos
        $partes = preg_split('/\n\s*\n/', $texto);
        $resultado = '';
        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (empty($parte)) continue;
            if (strpos($parte, '<ul>') === 0 || strpos($parte, '<li>') === 0) {
                $resultado .= $parte;
            } else {
                $resultado .= '<p>' . nl2br($parte) . '</p>';
            }
        }

        return $resultado ?: '<p class="sin-datos">Sin contenido disponible</p>';
    }

    /**
     * Genera gráficas desde métricas
     */
    private function generarGraficasDesdeMetricas(array $metricas, array $reporte): array
    {
        $graficas = [];

        if (isset($reporte['puntuacion_riesgo']) && is_numeric($reporte['puntuacion_riesgo'])) {
            $graficas[] = [
                'tipo'    => 'metric_card',
                'titulo'  => 'Nivel de Riesgo Global',
                'data'    => ['valor' => (float)$reporte['puntuacion_riesgo'], 'max' => 10],
            ];
        }

        if (isset($metricas['indice_resolucion']) && is_numeric($metricas['indice_resolucion'])) {
            $valor = (float)$metricas['indice_resolucion'];
            if ($valor > 10) {
                $valor = round(($valor / 100) * 10, 1);
            }
            $graficas[] = [
                'tipo'    => 'metric_card',
                'titulo'  => 'Tasa de Resolución',
                'data'    => ['valor' => $valor, 'max' => 10],
            ];
        }

        if (!empty($metricas['distribucion_categoria'])) {
            $datos = [];
            foreach ($metricas['distribucion_categoria'] as $item) {
                $cat = $item['categoria'] ?? $item['nombre'] ?? 'N/D';
                $total = (int)($item['total'] ?? $item['valor'] ?? 0);
                if ($total > 0) $datos[$cat] = $total;
            }
            if ($datos) {
                $graficas[] = ['tipo' => 'donut', 'titulo' => 'Distribución por categoría', 'data' => $datos];
            }
        }

        if (!empty($metricas['distribucion_medio'])) {
            $datos = [];
            foreach ($metricas['distribucion_medio'] as $item) {
                $medio = $item['medio'] ?? $item['nombre'] ?? 'N/D';
                $total = (int)($item['total'] ?? $item['valor'] ?? 0);
                if ($total > 0) $datos[$medio] = $total;
            }
            if ($datos) {
                $graficas[] = ['tipo' => 'donut', 'titulo' => 'Canales de reporte', 'data' => $datos];
            }
        }

        if (!empty($metricas['distribucion_sucursal'])) {
            $datos = [];
            foreach ($metricas['distribucion_sucursal'] as $i => $item) {
                if ($i >= 5) break;
                $suc = $item['sucursal'] ?? $item['nombre'] ?? 'N/D';
                $total = (int)($item['total'] ?? $item['valor'] ?? 0);
                if ($total > 0) $datos[$suc] = $total;
            }
            if ($datos) {
                $graficas[] = ['tipo' => 'bar', 'titulo' => 'Top 5 Sucursales', 'data' => $datos];
            }
        }

        if (!empty($metricas['distribucion_departamento'])) {
            $datos = [];
            foreach ($metricas['distribucion_departamento'] as $i => $item) {
                if ($i >= 5) break;
                $dep = $item['departamento'] ?? $item['nombre'] ?? 'N/D';
                $total = (int)($item['total'] ?? $item['valor'] ?? 0);
                if ($total > 0) $datos[$dep] = $total;
            }
            if ($datos) {
                $graficas[] = ['tipo' => 'bar', 'titulo' => 'Top 5 Departamentos', 'data' => $datos];
            }
        }

        return $graficas;
    }

    private function obtenerPaletaDonas(): array
    {
        return [
            self::COLOR_AZUL,
            self::COLOR_TURQUESA,
            self::COLOR_AMARILLO,
            self::COLOR_ROJO,
            self::COLOR_VERDE,
            self::COLOR_MORADO,
        ];
    }

    private function obtenerPaletaBarras(): array
    {
        return [
            self::COLOR_AZUL,
            self::COLOR_TURQUESA,
            self::COLOR_AMARILLO,
            self::COLOR_ROSA,
            self::COLOR_ROJO,
        ];
    }

    /**
     * Obtiene el logo del cliente desde la base de datos o del array del reporte
     */
    private function obtenerLogoCliente(array $reporte): string
    {
        // Prioridad 1: Logo ya proporcionado en el reporte (base64 o ruta)
        if (!empty($reporte['cliente_logo'])) {
            $data = $this->incrustarImagen($reporte['cliente_logo']);
            if ($data) {
                return '<img src="' . $data . '" alt="Logo Cliente" class="logo-cliente">';
            }
        }

        // Prioridad 2: Buscar logo en base de datos por id_cliente
        if (!empty($reporte['id_cliente'])) {
            $db = \Config\Database::connect();
            $builder = $db->table('clientes');
            $cliente = $builder->select('logo, nombre_empresa')
                ->where('id', $reporte['id_cliente'])
                ->get()
                ->getRowArray();

            if ($cliente && !empty($cliente['logo'])) {
                $data = $this->incrustarImagen($cliente['logo']);
                if ($data) {
                    return '<img src="' . $data . '" alt="Logo ' . htmlspecialchars($cliente['nombre_empresa']) . '" class="logo-cliente">';
                }
            }

            // Si hay nombre pero no logo, mostrar texto
            if ($cliente && !empty($cliente['nombre_empresa'])) {
                return '<div class="logo-cliente-texto">' . htmlspecialchars($cliente['nombre_empresa']) . '</div>';
            }
        }

        // Prioridad 3: Nombre del cliente desde el reporte
        if (!empty($reporte['cliente_nombre'])) {
            return '<div class="logo-cliente-texto">' . htmlspecialchars($reporte['cliente_nombre']) . '</div>';
        }

        // Fallback final
        return '<div class="logo-cliente-texto">Cliente</div>';
    }

    private function obtenerLogoEqqua(): string
    {
        $rutas = ['assets/images/logo.png', 'assets/images/eqqua logos-09.png', 'assets/images/logo_eqqua.png'];
        foreach ($rutas as $ruta) {
            $data = $this->incrustarImagen($ruta);
            if ($data) {
                return '<img src="' . $data . '" alt="Eqqua" class="logo-eqqua">';
            }
        }
        return '<div class="logo-eqqua-texto">eqqua</div>';
    }

    private function incrustarImagen(string $rutaRelativa): ?string
    {
        $rutaCompleta = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaRelativa);
        if (!is_file($rutaCompleta)) return null;
        $datos = @file_get_contents($rutaCompleta);
        if ($datos === false) return null;
        $extension = strtolower(pathinfo($rutaCompleta, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };
        return 'data:' . $mime . ';base64,' . base64_encode($datos);
    }

    private function generarNombreArchivo(array $reporte): string
    {
        $id = $reporte['id'] ?? 'sin-id';
        $fecha = date('Y-m-d_H-i-s');
        $periodo = preg_replace('/[^a-z0-9\-_]/i', '-', strtolower($reporte['periodo_nombre'] ?? 'reporte'));
        $periodo = preg_replace('/-+/', '-', trim($periodo, '-'));
        return "reporte_ia_{$id}_{$periodo}_{$fecha}.pdf";
    }

    private function obtenerCSS(): string
    {
        $azulOscuro = self::COLOR_AZUL_OSCURO;
        $azul = self::COLOR_AZUL;
        $turquesa = self::COLOR_TURQUESA;
        $amarillo = self::COLOR_AMARILLO;
        $rojo = self::COLOR_ROJO;
        $morado = self::COLOR_MORADO;
        $texto = self::COLOR_TEXTO;
        $textoClaro = self::COLOR_TEXTO_CLARO;

        return <<<CSS
@page {
    margin: 0;
    size: A4 portrait;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 11pt;
    color: {$texto};
    line-height: 1.5;
}

/* Cada bloque .pagina corresponde a UNA hoja */
.pagina {
    width: 100%;
    page-break-after: always;
    position: relative;
    padding-bottom: 60px; 
}

/* Eliminar salto de página en la última página */
.pagina:last-child {
    page-break-after: avoid;
}

/* Evitar cortes internos en los bloques importantes */
.seccion,
.seccion-dona,
.seccion-barras,
.seccion-sugerencias,
.tarjeta-metrica,
.metricas-grid,
.metricas-grid tr,
.metricas-grid td,
.dona-layout,
.dona-layout tr,
.dona-layout td,
.barras-tabla,
.barras-tabla tr,
.barras-tabla td {
    page-break-inside: avoid;
}

.contenido {
    padding: 0;
}

/* ========== PORTADA ========== */
.portada {
    padding: 0;
    position: relative;
}

.portada-decoracion-izq {
    position: absolute;
    left: 0;
    top: 80px;
    width: 20px;
}

.deco-barra {
    width: 12px;
    height: 80px;
    margin-bottom: 8px;
    border-radius: 0 6px 6px 0;
}

.deco-morado { background-color: {$morado}; }
.deco-turquesa { background-color: {$turquesa}; }
.deco-amarillo { background-color: {$amarillo}; }
.deco-rojo { background-color: {$rojo}; }

.portada-contenido {
    padding: 100px 60px 40px 60px;
    text-align: left;
}

.portada-periodo {
    font-size: 16pt;
    font-weight: 700;
    color: {$turquesa};
    margin-bottom: 10px;
}

.portada-titulo {
    font-size: 28pt;
    font-weight: 700;
    color: {$azulOscuro};
    line-height: 1.2;
    margin-bottom: 50px;
}

.portada-logo-cliente {
    margin: 40px 0;
}

.logo-cliente {
    max-width: 260px;
    max-height: 90px;
}

.logo-cliente-texto {
    font-size: 24pt;
    font-weight: 700;
    color: {$azul};
    padding: 15px 35px;
    border: 3px solid {$amarillo};
    border-radius: 8px;
    display: inline-block;
}

.portada-footer {
    position: absolute;
    bottom: 50px;
    left: 0;
    right: 0;
    text-align: center;
}

/* ========== SECCIONES TEXTO (Resumen / Hallazgos / Eficiencia) ========== */
.seccion {
    margin: 0;
    border-top: 6px solid #000;
}

.seccion-titulo {
    font-size: 14pt;
    font-weight: 700;
    padding: 14px 40px; 
    border-radius: 0; 
    text-align: center;
}

.seccion-titulo.azul {
    background-color: #1F7AC3;
    color: #fff;
}

.seccion-titulo.rojo {
    background-color: #E74545;
    color: #fff;
    margin: 0;
    border-radius: 0;
    padding: 14px 40px;
}

.seccion-contenido {
    padding: 18px 40px; 
}

.seccion-contenido p {
    margin-bottom: 12px;
    text-align: justify;
    line-height: 1.6;
}

.seccion-contenido ul {
    margin: 12px 0 12px 25px;
}

.seccion-contenido li {
    margin-bottom: 10px;
    line-height: 1.6;
}

/* ========== ANÁLISIS VISUAL ========== */
.metricas-grid {
    width: 100%;
    border-collapse: collapse;
    margin: 25px 0 15px 0;
    padding: 0 40px; 
}

.metrica-celda {
    width: 50%;
    padding: 10px 20px;
    vertical-align: top;
}

.tarjeta-metrica {
    text-align: center;
}

.metrica-titulo {
    font-size: 12pt;
    font-weight: 700;
    color: {$azulOscuro};
    margin-bottom: 10px;
}

.metrica-valor {
    font-size: 48pt;
    font-weight: 700;
    line-height: 1;
    margin: 5px 0;
}

.metrica-de {
    font-size: 12pt;
    color: {$turquesa};
    font-weight: 600;
    margin-bottom: 15px;
}

.metrica-barra-tabla {
    width: 100%;
    border-collapse: collapse;
}

.metrica-barra-td {
    width: 75%;
    padding-right: 10px;
}

.metrica-barra-fondo {
    height: 14px;
    background-color: #E5E7EB;
    border-radius: 7px;
    overflow: hidden;
}

.metrica-barra-progreso {
    height: 14px;
    border-radius: 7px;
}

.metrica-porcentaje-td {
    width: 25%;
    text-align: right;
    font-size: 12pt;
    font-weight: 600;
}

.separador-seccion {
    border-top: 2px dashed {$turquesa};
    margin: 22px 40px; 
}

/* Donas */
.seccion-dona {
    margin: 20px 40px; 
}

.dona-titulo {
    font-size: 14pt;
    font-weight: 700;
    color: {$azulOscuro};
    text-align: center;
    margin-bottom: 15px;
}

.dona-layout {
    width: 100%;
    border-collapse: collapse;
}

.dona-leyenda-celda {
    width: 55%;
    vertical-align: middle;
    padding-right: 20px;
}

.dona-grafica-celda {
    width: 45%;
    text-align: center;
    vertical-align: middle;
}

.dona-leyenda-tabla {
    width: 100%;
    border-collapse: collapse;
}

.dona-leyenda-tabla tr {
    border-bottom: 1px solid #E8E8E8;
}

.leyenda-color-td {
    width: 25px;
    padding: 10px 0;
}

.leyenda-color {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

.leyenda-texto-td {
    padding: 10px 12px;
    font-size: 10pt;
    color: {$texto};
}

.leyenda-valor-td {
    width: 40px;
    text-align: right;
    font-size: 14pt;
    font-weight: 700;
    padding: 10px 0;
    color: {$texto};
}

/* Barras */
.seccion-barras {
    margin: 20px 40px; 
}

.barras-titulo {
    font-size: 14pt;
    font-weight: 700;
    color: {$azulOscuro};
    text-align: center;
    margin-bottom: 18px;
}

.barras-tabla {
    width: 100%;
    border-collapse: collapse;
}

.barra-fila td {
    padding: 10px 0;
}

.barra-etiqueta-td {
    width: 28%;
    font-size: 9pt;
    font-weight: 700;
    color: {$azulOscuro};
    padding-right: 12px;
    vertical-align: middle;
}

.barra-contenedor-td {
    width: 60%;
    vertical-align: middle;
}

.barra-fondo {
    background-color: #F5F5F5;
    border-radius: 4px;
    overflow: hidden;
}

.barra-relleno {
    height: 30px;
    border-radius: 4px;
    position: relative;
    min-width: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.barra-porcentaje {
    color: #fff;
    font-size: 10pt;
    font-weight: 600;
    line-height: 1;
}

.barra-valor-td {
    width: 12%;
    text-align: right;
    font-size: 14pt;
    font-weight: 700;
    color: {$texto};
    padding-left: 12px;
    vertical-align: middle;
}

/* Sugerencias */
.seccion-sugerencias {
    margin: 0; 
    border: 4px solid #F39C12;
    border-top: 6px solid #000;
    border-radius: 0;
    overflow: hidden;
}

.sugerencias-titulo {
    background-color: #F39C12;
    color: #000;
    font-size: 14pt;
    font-weight: 700;
    padding: 14px 40px; 
    text-align: center;
}

.sugerencias-nota {
    padding: 12px 40px; 
    font-style: italic;
    font-size: 10pt;
    color: {$texto};
    background-color: #FEF5E7;
    border-bottom: 1px solid #F5E6C8;
}

.sugerencias-contenido {
    padding: 18px 40px; 
}

.sugerencias-contenido p {
    margin-bottom: 12px;
    line-height: 1.6;
}

.sugerencias-contenido ul {
    margin: 12px 0 12px 20px;
}

.sugerencias-contenido li {
    margin-bottom: 10px;
    line-height: 1.6;
}

/* Pie de página general */
.pie-pagina {
    position: absolute; 
    bottom: 25px;
    left: 0;
    right: 0;
    text-align: center;
}

.logo-eqqua {
    max-width: 100px;
    max-height: 40px;
}

.logo-eqqua-texto {
    font-size: 14pt;
    font-weight: 700;
    color: {$azul};
}

.sin-datos {
    color: {$textoClaro};
    font-style: italic;
    text-align: center;
    padding: 15px;
}
CSS;
    }
}
