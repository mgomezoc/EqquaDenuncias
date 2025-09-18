<?php

namespace App\Controllers;

use App\Models\SugerenciaIAModel;
use App\Models\DenunciaModel;
use CodeIgniter\Controller;

class IAAdminController extends Controller
{
    protected $sugerenciaModel;

    public function __construct()
    {
        $this->sugerenciaModel = new SugerenciaIAModel();
    }

    /**
     * Dashboard principal de estadísticas de IA
     */
    public function dashboard()
    {
        // Verificar permisos de administrador
        if (session()->get('rol_slug') !== 'ADMIN') {
            return redirect()->to('/')->with('error', 'Acceso denegado');
        }

        $data = [
            'title' => 'Dashboard IA - Administración',
            'estadisticas' => $this->obtenerEstadisticasCompletas(),
            'graficos' => $this->obtenerDatosGraficos(),
            'alertas' => $this->verificarAlertas()
        ];

        return view('admin/ia_dashboard', $data);
    }

    /**
     * Configuración del servicio de IA
     */
    public function configuracion()
    {
        if (session()->get('rol_slug') !== 'ADMIN') {
            return redirect()->to('/')->with('error', 'Acceso denegado');
        }

        $data = [
            'title' => 'Configuración IA',
            'configuracion_actual' => $this->obtenerConfiguracionActual(),
            'modelos_disponibles' => $this->getModelosDisponibles()
        ];

        return view('admin/ia_configuracion', $data);
    }

    /**
     * Guarda la configuración de IA
     */
    public function guardarConfiguracion()
    {
        if (session()->get('rol_slug') !== 'ADMIN') {
            return $this->response->setJSON(['success' => false, 'message' => 'Acceso denegado']);
        }

        try {
            $configuracion = [
                'generacion_automatica' => $this->request->getPost('generacion_automatica') ? 'true' : 'false',
                'modelo_usado' => $this->request->getPost('modelo_usado'),
                'max_tokens' => $this->request->getPost('max_tokens'),
                'temperature' => $this->request->getPost('temperature'),
                'limite_diario_tokens' => $this->request->getPost('limite_diario_tokens'),
                'limite_mensual_costo' => $this->request->getPost('limite_mensual_costo')
            ];

            // Validaciones
            if ($configuracion['max_tokens'] < 100 || $configuracion['max_tokens'] > 4000) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Max tokens debe estar entre 100 y 4000'
                ]);
            }

            if ($configuracion['temperature'] < 0 || $configuracion['temperature'] > 1) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Temperature debe estar entre 0 y 1'
                ]);
            }

            // Guardar en archivo de configuración o base de datos
            $this->guardarConfiguracionEnArchivo($configuracion);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configuración guardada exitosamente'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar configuración IA: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * Muestra logs de actividad de IA
     */
    public function logs()
    {
        if (session()->get('rol_slug') !== 'ADMIN') {
            return redirect()->to('/')->with('error', 'Acceso denegado');
        }

        $fechaInicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');

        $data = [
            'title' => 'Logs de IA',
            'logs' => $this->obtenerLogs($fechaInicio, $fechaFin),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ];

        return view('admin/ia_logs', $data);
    }

    /**
     * Reporte de costos de IA
     */
    public function reporteCostos()
    {
        if (session()->get('rol_slug') !== 'ADMIN') {
            return redirect()->to('/')->with('error', 'Acceso denegado');
        }

        $periodo = $this->request->getGet('periodo') ?? 'mensual';

        $data = [
            'title' => 'Reporte de Costos IA',
            'costos' => $this->obtenerReporteCostos($periodo),
            'periodo' => $periodo,
            'resumen' => $this->obtenerResumenCostos()
        ];

        return view('admin/ia_costos', $data);
    }

    /**
     * API: Obtiene estadísticas en tiempo real
     */
    public function estadisticasAPI()
    {
        if (session()->get('rol_slug') !== 'ADMIN') {
            return $this->response->setJSON(['error' => 'Acceso denegado']);
        }

        return $this->response->setJSON([
            'estadisticas' => $this->obtenerEstadisticasCompletas(),
            'alertas' => $this->verificarAlertas(),
            'ultimo_update' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Métodos auxiliares privados
     */

    private function obtenerEstadisticasCompletas(): array
    {
        $builder = $this->sugerenciaModel->builder();

        // Estadísticas generales
        $stats = [
            'total_sugerencias' => $builder->countAllResults(false),
            'sugerencias_hoy' => $builder->where('DATE(created_at)', date('Y-m-d'))->countAllResults(false),
            'sugerencias_mes' => $builder->where('MONTH(created_at)', date('m'))
                ->where('YEAR(created_at)', date('Y'))
                ->countAllResults(false)
        ];

        // Estadísticas de evaluación
        $evaluaciones = $builder->select('AVG(evaluacion_usuario) as promedio, COUNT(evaluacion_usuario) as total_evaluadas')
            ->where('evaluacion_usuario IS NOT NULL')
            ->get()->getRowArray();

        $stats['evaluacion_promedio'] = round($evaluaciones['promedio'] ?? 0, 2);
        $stats['total_evaluadas'] = $evaluaciones['total_evaluada'] ?? 0;

        // Estadísticas de tokens y costos
        $tokens = $builder->select('SUM(tokens_utilizados) as total_tokens, AVG(tokens_utilizados) as promedio_tokens')
            ->get()->getRowArray();

        $stats['total_tokens'] = $tokens['total_tokens'] ?? 0;
        $stats['promedio_tokens'] = round($tokens['promedio_tokens'] ?? 0, 0);

        $costos = $builder->select('SUM(costo_estimado) as costo_total')
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'))
            ->get()->getRowArray();

        $stats['costo_mensual'] = round($costos['costo_total'] ?? 0, 4);

        return $stats;
    }

    private function obtenerDatosGraficos(): array
    {
        // Datos para gráficos de los últimos 30 días
        $builder = $this->sugerenciaModel->builder();

        $sugerenciasPorDia = $builder->select('DATE(created_at) as fecha, COUNT(*) as cantidad')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('DATE(created_at)')
            ->orderBy('fecha', 'ASC')
            ->get()->getResultArray();

        $evaluacionesDistribucion = $builder->select('evaluacion_usuario, COUNT(*) as cantidad')
            ->where('evaluacion_usuario IS NOT NULL')
            ->groupBy('evaluacion_usuario')
            ->get()->getResultArray();

        return [
            'sugerencias_por_dia' => $sugerenciasPorDia,
            'distribucion_evaluaciones' => $evaluacionesDistribucion
        ];
    }

    private function verificarAlertas(): array
    {
        $alertas = [];

        // Verificar límite diario de tokens
        $limiteDiario = getenv('IA_LIMITE_DIARIO_TOKENS') ?? 50000;
        $tokensHoy = $this->sugerenciaModel->builder()
            ->select('SUM(tokens_utilizados) as total')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->get()->getRowArray()['total'] ?? 0;

        if ($tokensHoy > $limiteDiario * 0.8) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => "Uso de tokens diario al {$this->calcularPorcentaje($tokensHoy,$limiteDiario)}%"
            ];
        }

        // Verificar límite mensual de costo
        $limiteMensual = getenv('IA_LIMITE_MENSUAL_COSTO') ?? 100.00;
        $costoMensual = $this->sugerenciaModel->builder()
            ->select('SUM(costo_estimado) as total')
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'))
            ->get()->getRowArray()['total'] ?? 0;

        if ($costoMensual > $limiteMensual * 0.8) {
            $alertas[] = [
                'tipo' => 'danger',
                'mensaje' => "Costo mensual al {$this->calcularPorcentaje($costoMensual,$limiteMensual)}%"
            ];
        }

        return $alertas;
    }

    private function calcularPorcentaje($actual, $limite): int
    {
        return $limite > 0 ? round(($actual / $limite) * 100) : 0;
    }

    private function obtenerConfiguracionActual(): array
    {
        return [
            'generacion_automatica' => getenv('IA_GENERACION_AUTOMATICA') === 'true',
            'modelo_usado' => getenv('IA_MODELO_USADO') ?? 'gpt-4o',
            'max_tokens' => getenv('IA_MAX_TOKENS') ?? 1000,
            'temperature' => getenv('IA_TEMPERATURE') ?? 0.7,
            'limite_diario_tokens' => getenv('IA_LIMITE_DIARIO_TOKENS') ?? 50000,
            'limite_mensual_costo' => getenv('IA_LIMITE_MENSUAL_COSTO') ?? 100.00
        ];
    }

    private function getModelosDisponibles(): array
    {
        return [
            'gpt-4o' => 'GPT-4o (Recomendado)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Económico)'
        ];
    }

    private function obtenerLogs(string $fechaInicio, string $fechaFin): array
    {
        return $this->sugerenciaModel->builder()
            ->select('sia.*, d.folio, d.categoria, u.nombre_usuario')
            ->join('denuncias d', 'd.id = sia.id_denuncia', 'left')
            ->join('usuarios u', 'u.id = d.id_creador', 'left')
            ->where('DATE(sia.created_at) >=', $fechaInicio)
            ->where('DATE(sia.created_at) <=', $fechaFin)
            ->orderBy('sia.created_at', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();
    }

    private function obtenerReporteCostos(string $periodo): array
    {
        $builder = $this->sugerenciaModel->builder();

        switch ($periodo) {
            case 'diario':
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                $whereCondition = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case 'semanal':
                $groupBy = 'YEARWEEK(created_at)';
                $dateFormat = '%Y-W%u';
                $whereCondition = 'created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)';
                break;
            default: // mensual
                $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")';
                $dateFormat = '%Y-%m';
                $whereCondition = 'created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)';
        }

        return $builder->select("
                            $groupBy as periodo,
                            COUNT(*) as total_sugerencias,
                            SUM(tokens_utilizados) as total_tokens,
                            SUM(costo_estimado) as costo_total,
                            AVG(evaluacion_usuario) as evaluacion_promedio
                        ")
            ->where($whereCondition)
            ->groupBy($groupBy)
            ->orderBy('periodo', 'DESC')
            ->get()
            ->getResultArray();
    }

    private function obtenerResumenCostos(): array
    {
        $builder = $this->sugerenciaModel->builder();

        // Costo total histórico
        $costoTotal = $builder->select('SUM(costo_estimado) as total')->get()->getRowArray()['total'] ?? 0;

        // Costo del mes actual
        $costoMesActual = $builder->select('SUM(costo_estimado) as total')
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'))
            ->get()->getRowArray()['total'] ?? 0;

        // Proyección mensual basada en el promedio diario
        $diasTranscurridos = date('j');
        $proyeccionMensual = $diasTranscurridos > 0 ? ($costoMesActual / $diasTranscurridos) * date('t') : 0;

        return [
            'costo_total_historico' => round($costoTotal, 4),
            'costo_mes_actual' => round($costoMesActual, 4),
            'proyeccion_mensual' => round($proyeccionMensual, 4),
            'limite_mensual' => getenv('IA_LIMITE_MENSUAL_COSTO') ?? 100.00
        ];
    }

    private function guardarConfiguracionEnArchivo(array $configuracion): void
    {
        $envFile = ROOTPATH . '.env';
        $envContent = file_get_contents($envFile);

        foreach ($configuracion as $key => $value) {
            $envKey = 'IA_' . strtoupper($key);
            $pattern = "/^{$envKey}=.*/m";
            $replacement = "{$envKey}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}
