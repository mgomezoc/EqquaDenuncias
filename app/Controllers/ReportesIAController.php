<?php

namespace App\Controllers;

use App\Models\ReporteIAModel;
use App\Models\ClienteModel;
use App\Services\ReporteIAService;

class ReportesIAController extends BaseController
{
    protected ReporteIAModel $reporteModel;
    protected ClienteModel $clienteModel;

    public function __construct()
    {
        $this->reporteModel = new ReporteIAModel();
        $this->clienteModel = new ClienteModel();
    }

    /* =======================
     * VISTAS
     * ======================= */

    // Listado principal
    public function index()
    {
        $filtros = [
            'tipo_reporte' => $this->request->getGet('tipo_reporte'),
            'estado'       => $this->request->getGet('estado'),
            'id_cliente'   => $this->request->getGet('id_cliente'),
        ];
        $filtros = array_filter($filtros, static fn($v) => $v !== null && $v !== '');

        $data = [
            'title'       => 'Reportes de IA',
            'controlador' => 'Reportes IA',
            'vista'       => 'Listado',
            'reportes'    => $this->reporteModel->getReportesResumen($filtros),
            'clientes'    => $this->clienteModel->findAll(),
            'filtros'     => $filtros,
        ];

        return view('reportes_ia/index', $data);
    }

    // Formulario: Generar
    public function generar()
    {
        $data = [
            'title'        => 'Generar Reporte IA',
            'controlador'  => 'Reportes IA',
            'vista'        => 'Generar',
            'clientes'     => $this->clienteModel->findAll(),
            'es_cliente'   => false,
            'id_cliente_fijo' => null,
        ];

        return view('reportes_ia/generar', $data);
    }

    // Estadísticas generales
    public function estadisticas()
    {
        $data = [
            'title'              => 'Estadísticas de Reportes IA',
            'controlador'        => 'Reportes IA',
            'vista'              => 'Estadísticas',
            'estadisticas'       => $this->reporteModel->getEstadisticas(),
            'reportes_recientes' => $this->reporteModel->getReportesRecientes(10),
        ];

        return view('reportes_ia/estadisticas', $data);
    }

    // Detalle
    public function ver(int $idReporte)
    {
        $reporte = $this->reporteModel->getReporteCompleto($idReporte);
        if (!$reporte) {
            return redirect()->to('/reportes-ia')->with('error', 'Reporte no encontrado');
        }

        $data = [
            'title'       => 'Reporte IA - ' . ($reporte['periodo_nombre'] ?? ''),
            'controlador' => 'Reportes IA',
            'vista'       => 'Detalle',
            'reporte'     => $reporte,
        ];

        return view('reportes_ia/ver', $data);
    }

    /* =======================
     * APIS / ACCIONES
     * ======================= */

    // POST /reportes-ia/procesar
    public function procesarGeneracion()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método inválido']);
        }

        try {
            $rules = [
                'id_cliente'   => 'required|is_natural_no_zero',
                'tipo_reporte' => 'required|in_list[mensual,trimestral,semestral]',
                'fecha_inicio' => 'required|valid_date[Y-m-d]',
                'fecha_fin'    => 'required|valid_date[Y-m-d]',
            ];
            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors'  => $this->validator->getErrors(),
                ]);
            }

            $idCliente   = (int) $this->request->getPost('id_cliente');
            $tipoReporte = (string) $this->request->getPost('tipo_reporte');
            $fechaInicio = (string) $this->request->getPost('fecha_inicio');
            $fechaFin    = (string) $this->request->getPost('fecha_fin');

            // usuario_id o id (según cómo guardes sesión)
            $idUsuario = (int) (session()->get('usuario_id') ?? session()->get('id') ?? 0);

            // Evitar duplicados exactos
            if ($this->reporteModel->existeReportePeriodo($idCliente, $tipoReporte, $fechaInicio, $fechaFin)) {
                return $this->response->setJSON([
                    'success' => false,
                    'existe'  => true,
                    'message' => 'Ya existe un reporte para este periodo.',
                ]);
            }

            $service   = new ReporteIAService();
            $resultado = $service->generarReporte($idCliente, $tipoReporte, $fechaInicio, $fechaFin, $idUsuario);

            if (!empty($resultado['success'])) {
                return $this->response->setJSON([
                    'success'        => true,
                    'message'        => 'Reporte generado exitosamente',
                    'id_reporte'     => $resultado['id_reporte'] ?? null,
                    'tokens_usados'  => $resultado['tokens_usados'] ?? 0,
                    'costo_estimado' => isset($resultado['costo_estimado']) ? number_format((float)$resultado['costo_estimado'], 6) : '0.000000',
                    'tiempo'         => ($resultado['tiempo_generacion'] ?? 0) . ' segundos',
                ]);
            }

            // Propagar motivo claro si el servicio lo retorna
            return $this->response->setJSON([
                'success'      => false,
                'message'      => $resultado['error'] ?? 'Error al generar el reporte',
                'error_detail' => (env('CI_ENVIRONMENT') === 'development') ? ($resultado['error'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error en procesarGeneracion: {msg} en {file}:{line}', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_detail' => (env('CI_ENVIRONMENT') === 'development')
                    ? ($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine())
                    : null,
            ]);
        }
    }

    // POST /reportes-ia/cambiar-estado
    public function cambiarEstado()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método inválido']);
        }

        $idReporte   = (int) $this->request->getPost('id_reporte');
        $nuevoEstado = (string) $this->request->getPost('estado');
        $idUsuario   = (int) (session()->get('usuario_id') ?? session()->get('id') ?? 0);

        $validos = ['generado', 'revisado', 'publicado', 'archivado'];
        if (!in_array($nuevoEstado, $validos, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Estado inválido']);
        }

        if ($this->reporteModel->cambiarEstado($idReporte, $nuevoEstado, $idUsuario)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Estado actualizado correctamente']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo actualizar el estado']);
    }

    // GET /reportes-ia/descargar/{id}
    public function descargarPDF(int $idReporte)
    {
        $reporte = $this->reporteModel->getReporteCompleto($idReporte);
        if (!$reporte) {
            return redirect()->to('/reportes-ia')->with('error', 'Reporte no encontrado');
        }

        try {
            if (!empty($reporte['ruta_pdf']) && file_exists(FCPATH . $reporte['ruta_pdf'])) {
                return $this->response->download(FCPATH . $reporte['ruta_pdf'], null);
            }

            $pdfService = new \App\Services\PDFReporteService();
            $rutaPDF    = $pdfService->generarPDF($reporte);

            if ($rutaPDF) {
                $hashPDF = hash_file('sha256', FCPATH . $rutaPDF);
                $this->reporteModel->guardarRutaPDF($idReporte, $rutaPDF, $hashPDF);
                return $this->response->download(FCPATH . $rutaPDF, null);
            }

            return redirect()->back()->with('error', 'Error al generar el PDF');
        } catch (\Throwable $e) {
            log_message('error', 'Error en descargarPDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al procesar la descarga');
        }
    }

    // POST /reportes-ia/eliminar
    public function eliminar()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Método inválido']);
        }

        $idReporte = (int) $this->request->getPost('id_reporte');

        if ($this->reporteModel->eliminarReporte($idReporte)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Reporte eliminado correctamente']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo eliminar el reporte']);
    }

    // GET /reportes-ia/periodos
    public function getPeriodosDisponibles()
    {
        $tipo = (string) $this->request->getGet('tipo_reporte');
        $ref  = (string) ($this->request->getGet('fecha_referencia') ?: date('Y-m-d'));
        $periodos = $this->calcularPeriodos($tipo, $ref);

        return $this->response->setJSON(['success' => true, 'periodos' => $periodos]);
    }

    /* =======================
     * UTILIDADES
     * ======================= */

    private function calcularPeriodos(string $tipoReporte, string $fechaReferencia): array
    {
        $periodos = [];
        $fechaRef = new \DateTime($fechaReferencia);

        switch ($tipoReporte) {
            case 'mensual':
                for ($i = 0; $i < 12; $i++) {
                    $inicio = (clone $fechaRef)->modify("first day of -{$i} month");
                    $fin    = (clone $inicio)->modify('last day of this month');
                    $periodos[] = [
                        'nombre'       => $inicio->format('F Y'),
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin'    => $fin->format('Y-m-d'),
                    ];
                }
                break;

            case 'trimestral':
                $y = (int)$fechaRef->format('Y');
                $q = (int)ceil(((int)$fechaRef->format('n')) / 3);
                for ($i = 0; $i < 4; $i++) {
                    $qIdx = $q - $i;
                    $year = $y;
                    while ($qIdx <= 0) {
                        $qIdx += 4;
                        $year--;
                    }
                    $startMonth = 1 + 3 * ($qIdx - 1);
                    $inicio = (new \DateTime("{$year}-{$startMonth}-01"))->modify('first day of this month');
                    $fin    = (clone $inicio)->modify('+2 months')->modify('last day of this month');
                    $periodos[] = [
                        'nombre'       => 'Q' . $qIdx . ' ' . $year,
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin'    => $fin->format('Y-m-d'),
                    ];
                }
                break;

            case 'semestral':
                $y = (int)$fechaRef->format('Y');
                $s = ((int)$fechaRef->format('n') <= 6) ? 1 : 2;
                for ($i = 0; $i < 2; $i++) {
                    $sIdx = $s - $i;
                    $year = $y;
                    while ($sIdx <= 0) {
                        $sIdx += 2;
                        $year--;
                    }
                    if ($sIdx === 1) {
                        $inicio = new \DateTime("{$year}-01-01");
                        $fin = new \DateTime("{$year}-06-30");
                    } else {
                        $inicio = new \DateTime("{$year}-07-01");
                        $fin = new \DateTime("{$year}-12-31");
                    }
                    $periodos[] = [
                        'nombre'       => 'H' . $sIdx . ' ' . $year,
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin'    => $fin->format('Y-m-d'),
                    ];
                }
                break;
        }

        return $periodos;
    }
}
