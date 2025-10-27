<?php

namespace App\Controllers;

use App\Models\ReporteIAModel;
use App\Models\ClienteModel;
use App\Services\ReporteIAService;
use CodeIgniter\Controller;

/**
 * ReportesController
 * 
 * Controlador para gestionar reportes generados con IA
 * 
 * @author Cesar M Gomez M
 * @version 1.0
 */
class ReportesIAController extends Controller
{
    protected $reporteModel;
    protected $clienteModel;
    protected $session;

    public function __construct()
    {
        $this->reporteModel = new ReporteIAModel();
        $this->clienteModel = new ClienteModel();
        $this->session = session();

        // Middleware: Verificar autenticación
        if (!$this->session->has('usuario_id')) {
            redirect()->to('/login')->send();
            exit;
        }
    }

    /**
     * Vista principal: Listado de reportes
     */
    public function index()
    {
        $usuarioRol = $this->session->get('rol_id');
        $idCliente = $this->session->get('id_cliente');

        // Filtros
        $filtros = [
            'tipo_reporte' => $this->request->getGet('tipo_reporte'),
            'estado' => $this->request->getGet('estado'),
        ];

        // Si es cliente, solo ve sus reportes
        if ($usuarioRol == 4 && $idCliente) {
            $filtros['id_cliente'] = $idCliente;
        } else {
            $filtros['id_cliente'] = $this->request->getGet('id_cliente');
        }

        // Limpiar filtros vacíos
        $filtros = array_filter($filtros);

        $data = [
            'title'      => 'Reportes de IA',
            'controlador' => 'Reportes',
            'vista'      => 'Reportes IA',
            'reportes'   => $this->reporteModel->getReportesResumen($filtros),
            'clientes'   => $this->clienteModel->findAll(),
            'filtros'    => $filtros,
        ];

        return view('reportes/index', $data);
    }

    /**
     * Vista: Generar nuevo reporte
     */
    public function generar()
    {
        $usuarioRol = $this->session->get('rol_id');
        $idCliente = $this->session->get('id_cliente');

        $data = [
            'title'      => 'Generar Reporte IA',
            'controlador' => 'Reportes',
            'vista'      => 'Generar Reporte',
            'clientes'   => $this->clienteModel->findAll(),
            'es_cliente' => ($usuarioRol == 4),
            'id_cliente_fijo' => $idCliente,
        ];

        return view('reportes/generar', $data);
    }

    /**
     * API: Procesa generación de reporte
     */
    public function procesarGeneracion()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            // Validar datos
            $rules = [
                'id_cliente'    => 'required|integer',
                'tipo_reporte'  => 'required|in_list[mensual,trimestral,semestral]',
                'fecha_inicio'  => 'required|valid_date',
                'fecha_fin'     => 'required|valid_date',
            ];

            if (!$this->validate($rules)) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors'  => $this->validator->getErrors(),
                ]);
            }

            $idCliente   = (int) $this->request->getPost('id_cliente');
            $tipoReporte = $this->request->getPost('tipo_reporte');
            $fechaInicio = $this->request->getPost('fecha_inicio');
            $fechaFin    = $this->request->getPost('fecha_fin');
            $idUsuario   = $this->session->get('usuario_id');

            // Verificar si ya existe reporte para este periodo
            if ($this->reporteModel->existeReportePeriodo($idCliente, $tipoReporte, $fechaInicio, $fechaFin)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ya existe un reporte para este periodo. ¿Desea regenerarlo?',
                    'existe'  => true,
                ]);
            }

            // Generar reporte
            $reporteService = new ReporteIAService();
            $resultado = $reporteService->generarReporte(
                $idCliente,
                $tipoReporte,
                $fechaInicio,
                $fechaFin,
                $idUsuario
            );

            if ($resultado['success']) {
                // Registrar en auditoría
                $this->registrarAuditoria('Generación de reporte IA', "ID: {$resultado['id_reporte']}");

                return $this->response->setJSON([
                    'success'        => true,
                    'message'        => 'Reporte generado exitosamente',
                    'id_reporte'     => $resultado['id_reporte'],
                    'tokens_usados'  => $resultado['tokens_usados'],
                    'costo_estimado' => number_format($resultado['costo_estimado'], 6),
                    'tiempo'         => $resultado['tiempo_generacion'] . ' segundos',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => $resultado['error'] ?? 'Error al generar el reporte',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en procesarGeneracion: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error interno del servidor',
            ]);
        }
    }

    /**
     * Vista: Ver detalle de un reporte
     */
    public function ver($idReporte)
    {
        $reporte = $this->reporteModel->getReporteCompleto($idReporte);

        if (!$reporte) {
            return redirect()->to('/reportes')->with('error', 'Reporte no encontrado');
        }

        // Verificar permisos
        $usuarioRol = $this->session->get('rol_id');
        $idCliente = $this->session->get('id_cliente');

        if ($usuarioRol == 4 && $reporte['id_cliente'] != $idCliente) {
            return redirect()->to('/reportes')->with('error', 'No tiene permisos para ver este reporte');
        }

        $data = [
            'title'      => 'Ver Reporte - ' . $reporte['periodo_nombre'],
            'controlador' => 'Reportes',
            'vista'      => 'Detalle Reporte',
            'reporte'    => $reporte,
        ];

        return view('reportes/ver', $data);
    }

    /**
     * API: Cambiar estado de reporte
     */
    public function cambiarEstado()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $idReporte = (int) $this->request->getPost('id_reporte');
        $nuevoEstado = $this->request->getPost('estado');
        $idUsuario = $this->session->get('usuario_id');

        if (!in_array($nuevoEstado, ['generado', 'revisado', 'publicado', 'archivado'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Estado inválido',
            ]);
        }

        if ($this->reporteModel->cambiarEstado($idReporte, $nuevoEstado, $idUsuario)) {
            $this->registrarAuditoria('Cambio estado reporte', "Reporte ID: {$idReporte} -> {$nuevoEstado}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al actualizar el estado',
        ]);
    }

    /**
     * Descarga: Generar y descargar PDF
     */
    public function descargarPDF($idReporte)
    {
        $reporte = $this->reporteModel->getReporteCompleto($idReporte);

        if (!$reporte) {
            return redirect()->to('/reportes')->with('error', 'Reporte no encontrado');
        }

        // Verificar permisos
        $usuarioRol = $this->session->get('rol_id');
        $idCliente = $this->session->get('id_cliente');

        if ($usuarioRol == 4 && $reporte['id_cliente'] != $idCliente) {
            return redirect()->to('/reportes')->with('error', 'No tiene permisos');
        }

        try {
            // Verificar si ya existe PDF generado
            if (!empty($reporte['ruta_pdf']) && file_exists(FCPATH . $reporte['ruta_pdf'])) {
                // Descargar PDF existente
                return $this->response->download(FCPATH . $reporte['ruta_pdf'], null);
            }

            // Generar nuevo PDF
            $pdfService = new \App\Services\PDFReporteService();
            $rutaPDF = $pdfService->generarPDF($reporte);

            if ($rutaPDF) {
                // Guardar ruta en BD
                $hashPDF = hash_file('sha256', FCPATH . $rutaPDF);
                $this->reporteModel->guardarRutaPDF($idReporte, $rutaPDF, $hashPDF);

                // Descargar
                return $this->response->download(FCPATH . $rutaPDF, null);
            }

            return redirect()->back()->with('error', 'Error al generar el PDF');
        } catch (\Exception $e) {
            log_message('error', 'Error en descargarPDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al procesar la descarga');
        }
    }

    /**
     * API: Eliminar reporte
     */
    public function eliminar()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        // Solo admin puede eliminar
        if ($this->session->get('rol_id') != 5) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No tiene permisos para eliminar reportes',
            ]);
        }

        $idReporte = (int) $this->request->getPost('id_reporte');

        if ($this->reporteModel->eliminarReporte($idReporte)) {
            $this->registrarAuditoria('Eliminación de reporte', "ID: {$idReporte}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Reporte eliminado correctamente',
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Error al eliminar el reporte',
        ]);
    }

    /**
     * Vista: Estadísticas de reportes
     */
    public function estadisticas()
    {
        $usuarioRol = $this->session->get('rol_id');
        $idCliente = $this->session->get('id_cliente');

        // Cliente solo ve sus stats
        $filtroCliente = ($usuarioRol == 4) ? $idCliente : null;

        $data = [
            'title'       => 'Estadísticas de Reportes',
            'controlador' => 'Reportes',
            'vista'       => 'Estadísticas',
            'estadisticas' => $this->reporteModel->getEstadisticas($filtroCliente),
            'reportes_recientes' => $this->reporteModel->getReportesRecientes(10, $filtroCliente),
        ];

        return view('reportes/estadisticas', $data);
    }

    /**
     * API: Obtener periodos disponibles para reportes
     */
    public function getPeriodosDisponibles()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $tipoReporte = $this->request->getGet('tipo_reporte');
        $fechaReferencia = $this->request->getGet('fecha_referencia') ?: date('Y-m-d');

        $periodos = $this->calcularPeriodos($tipoReporte, $fechaReferencia);

        return $this->response->setJSON([
            'success' => true,
            'periodos' => $periodos,
        ]);
    }

    /**
     * Calcula periodos disponibles según tipo de reporte
     */
    private function calcularPeriodos(string $tipoReporte, string $fechaReferencia): array
    {
        $periodos = [];
        $fecha = new \DateTime($fechaReferencia);

        switch ($tipoReporte) {
            case 'mensual':
                // Últimos 12 meses
                for ($i = 0; $i < 12; $i++) {
                    $inicio = clone $fecha;
                    $inicio->modify("first day of -{$i} month");
                    $fin = clone $inicio;
                    $fin->modify('last day of this month');

                    $periodos[] = [
                        'nombre' => $inicio->format('F Y'),
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin' => $fin->format('Y-m-d'),
                    ];
                }
                break;

            case 'trimestral':
                // Últimos 4 trimestres
                for ($i = 0; $i < 4; $i++) {
                    $mesActual = (int) $fecha->format('n');
                    $trimestre = ceil($mesActual / 3);
                    $inicio = clone $fecha;
                    $inicio->modify("first day of january this year");
                    $inicio->modify('+' . (($trimestre - 1 - $i) * 3) . ' months');
                    $fin = clone $inicio;
                    $fin->modify('+2 months');
                    $fin->modify('last day of this month');

                    $periodos[] = [
                        'nombre' => 'Q' . ($trimestre - $i) . ' ' . $inicio->format('Y'),
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin' => $fin->format('Y-m-d'),
                    ];
                }
                break;

            case 'semestral':
                // Últimos 2 semestres
                for ($i = 0; $i < 2; $i++) {
                    $mesActual = (int) $fecha->format('n');
                    $semestre = ($mesActual <= 6) ? 1 : 2;
                    $inicio = clone $fecha;

                    if ($semestre == 1) {
                        $inicio->modify("first day of january this year");
                        $fin = clone $inicio;
                        $fin->modify('first day of june this year');
                        $fin->modify('last day of this month');
                    } else {
                        $inicio->modify("first day of july this year");
                        $fin = clone $inicio;
                        $fin->modify('first day of december this year');
                        $fin->modify('last day of this month');
                    }

                    if ($i > 0) {
                        $inicio->modify('-6 months');
                        $fin->modify('-6 months');
                    }

                    $periodos[] = [
                        'nombre' => (($semestre - $i) . 'H ') . $inicio->format('Y'),
                        'fecha_inicio' => $inicio->format('Y-m-d'),
                        'fecha_fin' => $fin->format('Y-m-d'),
                    ];
                }
                break;
        }

        return $periodos;
    }

    /**
     * Registra acción en auditoría
     */
    private function registrarAuditoria(string $accion, string $detalle = ''): void
    {
        $auditoriaModel = new \App\Models\AuditoriaModel();
        $auditoriaModel->registrar(
            $this->session->get('usuario_id'),
            $accion,
            $detalle
        );
    }
}
