<?php

namespace App\Controllers;

use App\Models\DenunciaModel;
use App\Models\ClienteModel;
use App\Models\EstadoDenunciaModel;
use App\Models\CategoriaDenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use App\Models\SucursalModel;
use App\Models\AnexoDenunciaModel;
use App\Models\DepartamentoModel;
use App\Models\SeguimientoDenunciaModel;
use App\Models\SugerenciaIAModel;
use App\Models\UsuarioModel;
use App\Services\EmailService;
use App\Services\IAService;
use CodeIgniter\Controller;

class DenunciasController extends Controller
{
    /* ===============================
     * Views
     * =============================== */
    public function index()
    {
        $clienteModel     = new ClienteModel();
        $estadoModel      = new EstadoDenunciaModel();
        $categoriaModel   = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        $data = [
            'title'         => 'Administración de Denuncias',
            'controlador'   => 'Denuncias',
            'vista'         => 'Denuncias',
            'clientes'      => $clienteModel->findAll(),
            'estados'       => $estadoModel->findAll(),
            'categorias'    => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
        ];

        return view('denuncias/index', $data);
    }

    public function misDenunciasAgente()
    {
        $clienteModel     = new ClienteModel();
        $estadoModel      = new EstadoDenunciaModel();
        $categoriaModel   = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        $data = [
            'title'         => 'Mis Denuncias',
            'controlador'   => 'Denuncias',
            'vista'         => 'Mis Denuncias Agente',
            'clientes'      => $clienteModel->findAll(),
            'estados'       => $estadoModel->findAll(),
            'categorias'    => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
        ];

        return view('denuncias/mis_denuncias_agente', $data);
    }

    public function gestionSupervisor()
    {
        $clienteModel     = new ClienteModel();
        $estadoModel      = new EstadoDenunciaModel();
        $categoriaModel   = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        $clienteId  = session()->get('id_cliente');
        $denunciaModel = new DenunciaModel();
        $denuncias  = $denunciaModel->getDenunciasParaCalidad($clienteId);

        $data = [
            'title'         => 'Mis Denuncias',
            'controlador'   => 'Denuncias',
            'vista'         => 'Gestión de Denuncias Supervisor',
            'clientes'      => $clienteModel->findAll(),
            'estados'       => $estadoModel->findAll(),
            'categorias'    => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
            'denuncias'     => $denuncias,
        ];

        return view('denuncias/gestion_supervisor', $data);
    }

    public function misDenunciasCliente()
    {
        $clienteId = session()->get('id_cliente');
        if (!$clienteId) {
            return redirect()->to('/login')->with('error', 'Debe iniciar sesión para ver sus denuncias');
        }

        $solo_lectura    = session()->get('solo_lectura');
        $clienteModel    = new ClienteModel();
        $denunciaModel   = new DenunciaModel();
        $estadoModel     = new EstadoDenunciaModel();
        $categoriaModel  = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        $data = [
            'title'         => 'Denuncias Activas',
            'controlador'   => 'Denuncias',
            'vista'         => 'Mis Denuncias Cliente',
            'cliente'       => $clienteModel->getClienteById($clienteId),
            'estados'       => $estadoModel->findAll(),
            'categorias'    => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
            'denuncias'     => $denunciaModel->getDenunciasByCliente($clienteId),
            'solo_lectura'  => $solo_lectura
        ];

        return view('denuncias/mis_denuncias_cliente', $data);
    }

    /* ===============================
     * Listados / detalle
     * =============================== */
    public function listar()
    {
        $denunciaModel = new DenunciaModel();
        return $this->response->setJSON($denunciaModel->getDenuncias());
    }

    public function listarDenunciasAgente()
    {
        $denunciaModel = new DenunciaModel();
        $agenteId = session()->get('id');
        return $this->response->setJSON($denunciaModel->getDenunciasByAgente($agenteId));
    }

    public function listarDenunciasCalidad()
    {
        $clienteId = session()->get('id_cliente');
        $denunciaModel = new DenunciaModel();
        return $this->response->setJSON($denunciaModel->getDenunciasParaCalidad($clienteId));
    }

    public function listarDenunciasCliente()
    {
        $clienteId = session()->get('id_cliente');
        if (!$clienteId) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Acceso denegado. Cliente no autenticado.']);
        }

        $denunciaModel = new DenunciaModel();
        return $this->response->setJSON($denunciaModel->getDenunciasByCliente($clienteId));
    }

    public function detalle($id)
    {
        $denunciaModel     = new DenunciaModel();
        $seguimientoModel  = new SeguimientoDenunciaModel();

        $denuncia      = $denunciaModel->getDenunciaById($id);
        $seguimientos  = $seguimientoModel->getSeguimientoByDenunciaId($id);

        $anexoModel        = new \App\Models\AnexoDenunciaModel();
        $archivosDenuncia  = $anexoModel->where('id_denuncia', $id)->findAll();

        $denuncia['seguimientos'] = $seguimientos;
        $denuncia['archivos']     = $archivosDenuncia;

        return $this->response->setJSON($denuncia);
    }

    /* ===============================
     * CRUD Denuncias
     * =============================== */
    public function guardar()
    {
        $denunciaModel = new DenunciaModel();
        $anexoModel    = new AnexoDenunciaModel();
        $id            = $this->request->getVar('id');

        $idCreador = session()->get('id');
        if (!$idCreador) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Usuario no autenticado o sesión no iniciada']);
        }

        $esNueva = empty($id);

        $data = array_filter([
            'id_cliente'        => $this->request->getVar('id_cliente'),
            'id_sucursal'       => $this->request->getVar('id_sucursal'),
            'tipo_denunciante'  => $this->request->getVar('tipo_denunciante'),
            'categoria'         => $this->request->getVar('categoria'),
            'subcategoria'      => $this->request->getVar('subcategoria'),
            'id_departamento'   => $this->request->getVar('id_departamento') ?: null,
            'anonimo'           => $this->request->getVar('anonimo'),
            'fecha_incidente'   => $this->request->getVar('fecha_incidente'),
            'como_se_entero'    => $this->request->getVar('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getVar('denunciar_a_alguien'),
            'area_incidente'    => $this->request->getVar('area_incidente'),
            'descripcion'       => $this->request->getVar('descripcion'),
            'estado_actual'     => $this->request->getVar('estado_actual'),
            'medio_recepcion'   => $this->request->getVar('medio_recepcion'),
            'nombre_completo'   => $this->request->getVar('nombre_completo'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'telefono'          => $this->request->getVar('telefono'),
            'id_creador'        => $idCreador,
            'id_sexo'           => $this->request->getVar('id_sexo'),
            'created_at'        => $this->request->getVar('created_at'),
        ], fn($v) => $v !== null && $v !== '');

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($id) {
                if (!$denunciaModel->update($id, $data)) {
                    throw new \RuntimeException('Error al actualizar la denuncia.');
                }
                registrarAccion($idCreador, 'Actualización de denuncia', 'ID: ' . $id);
            } else {
                if (!$denunciaModel->save($data)) {
                    throw new \RuntimeException('Error al guardar la denuncia.');
                }
                $id = $denunciaModel->insertID();
                registrarAccion($idCreador, 'Creación de denuncia', 'ID: ' . $id);
            }

            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    $nombreArchivo = basename($rutaArchivo);
                    if (!$anexoModel->save([
                        'id_denuncia'   => $id,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo'  => $rutaArchivo,
                        'tipo'          => @mime_content_type(WRITEPATH . '../public/' . $rutaArchivo) ?: 'application/octet-stream',
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al guardar la denuncia y los archivos adjuntos. Error: ' . $e->getMessage()]);
        }

        if ($esNueva && !empty($id)) {
            $this->generarSugerenciaAutomatica((int)$id);
        }

        return $this->response->setJSON([
            'message'     => 'Denuncia guardada correctamente',
            'id_denuncia' => (int)$id
        ]);
    }

    public function eliminar($id)
    {
        $denunciaModel = new DenunciaModel();
        if ($denunciaModel->eliminarDenuncia($id)) {
            return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente']);
        }
        return $this->response->setStatusCode(500)->setJSON(['message' => 'Error al eliminar la denuncia']);
    }

    public function cambiarEstado()
    {
        $denunciaModel    = new DenunciaModel();
        $seguimientoModel = new SeguimientoDenunciaModel();
        $comentarioModel  = new \App\Models\ComentarioDenunciaModel();

        $id           = $this->request->getVar('id');
        $estado_nuevo = $this->request->getVar('estado_nuevo');

        $denuncia         = $denunciaModel->find($id);
        $estado_anterior  = $denuncia['estado_actual'];

        if ($estado_anterior == 4 && $estado_nuevo == 5) {
            $seguimiento = $seguimientoModel
                ->where('id_denuncia', $id)
                ->where('estado_nuevo', 4)
                ->orderBy('fecha', 'DESC')
                ->first();
            if ($seguimiento) {
                $fechaLiberacion = strtotime($seguimiento['fecha']);
                $tiempoAtencion  = time() - $fechaLiberacion;
                $denunciaModel->update($id, ['tiempo_atencion_cliente' => $tiempoAtencion]);
            }
        }

        $denunciaModel->cambiarEstado($id, $estado_nuevo);

        $seguimientoModel->save([
            'id_denuncia'     => $id,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo'    => $estado_nuevo,
            'comentario'      => $this->request->getVar('comentario'),
            'id_usuario'      => session()->get('id'),
            'fecha'           => date('Y-m-d H:i:s')
        ]);

        registrarAccion(session()->get('id'), 'Cambio de estado de denuncia', 'ID: ' . $id);

        if ($estado_nuevo == 4) {
            $comentarioModel->insert([
                'id_denuncia'     => $id,
                'id_usuario'      => 1,
                'contenido'       => 'Su denuncia está siendo atendida. Favor de revisar en 48 horas.',
                'estado_denuncia' => $estado_nuevo,
                'fecha_comentario' => date('Y-m-d H:i:s')
            ]);

            $usuarioModel = new UsuarioModel();
            $usuarios = $usuarioModel
                ->where('id_cliente', $denuncia['id_cliente'])
                ->where('recibe_notificaciones', 1)
                ->findAll();

            $erroresCorreo = [];
            foreach ($usuarios as $usuario) {
                $correoDestino = !empty($usuario['correo_notificaciones'])
                    ? $usuario['correo_notificaciones']
                    : $usuario['correo_electronico'];

                $resultado = $this->enviarCorreoLiberacionCliente($correoDestino, $usuario['nombre_usuario'], $denuncia);
                if ($resultado !== true) {
                    $erroresCorreo[] = [
                        'usuario' => $usuario['nombre_usuario'],
                        'correo'  => $correoDestino,
                        'error'   => $resultado
                    ];
                }
            }
        }

        return $this->response->setJSON([
            'message'       => 'Estado actualizado correctamente',
            'erroresCorreo' => $erroresCorreo ?? []
        ]);
    }

    public function subirAnexo()
    {
        $file = $this->request->getFile('file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . '../public/assets/denuncias', $newName);
            return $this->response->setJSON(['filename' => $newName]);
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo subir el anexo.']);
    }

    public function obtenerSucursalesPorCliente($id_cliente)
    {
        $sucursalModel = new SucursalModel();
        return $this->response->setJSON($sucursalModel->where('id_cliente', $id_cliente)->findAll());
    }

    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();
        return $this->response->setJSON($departamentoModel->where('id_sucursal', $id_sucursal)->findAll());
    }

    public function obtenerEstados()
    {
        $estadoModel = new EstadoDenunciaModel();
        return $this->response->setJSON($estadoModel->getEstados());
    }

    public function obtenerAnexos($id_denuncia)
    {
        $anexoModel = new \App\Models\AnexoDenunciaModel();
        return $this->response->setJSON($anexoModel->getAnexosByDenunciaId($id_denuncia));
    }

    public function misDenuncias()
    {
        $clienteId = session()->get('id_cliente');
        $denunciaModel = new DenunciaModel();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($denunciaModel->getDenunciasByCliente($clienteId));
        }

        $categoriaModel = new CategoriaDenunciaModel();
        $data = [
            'title'       => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista'       => 'Mis Denuncias',
            'categorias'  => $categoriaModel->findAll(),
        ];

        return view('mis_denuncias/index', $data);
    }

    public function actualizarAnexos()
    {
        $denunciaId = $this->request->getVar('id');
        $anexoModel = new AnexoDenunciaModel();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    $nombreArchivo = basename($rutaArchivo);
                    if (!$anexoModel->save([
                        'id_denuncia'   => $denunciaId,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo'  => $rutaArchivo,
                        'tipo'          => @mime_content_type(WRITEPATH . '../public/' . $rutaArchivo) ?: 'application/octet-stream',
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }

            return $this->response->setJSON(['message' => 'Archivos actualizados correctamente']);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al actualizar los archivos adjuntos. Error: ' . $e->getMessage()]);
        }
    }

    public function eliminarAnexo($id)
    {
        $anexoModel = new AnexoDenunciaModel();

        try {
            $anexo = $anexoModel->getAnexoById($id);
            if (!$anexo) {
                throw new \RuntimeException('Anexo no encontrado.');
            }

            if (!$anexoModel->deleteAnexo($id)) {
                throw new \RuntimeException('Error al eliminar el anexo.');
            }

            $filePath = WRITEPATH . '../public/' . $anexo['ruta_archivo'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return $this->response->setJSON(['message' => 'Anexo eliminado correctamente']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al eliminar el anexo. Error: ' . $e->getMessage()]);
        }
    }

    /* =======================
     * Notificación por correo
     * ======================= */
    private function enviarCorreoLiberacionCliente($email, $nombreUsuario, $denuncia)
    {
        try {
            $emailService = new EmailService();

            $mensaje = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Denuncia Liberada</title>
            <style>
                body { font-family: "Arial", sans-serif; background-color: #f4f4f4; color: #333; margin:0; padding:0; width:100%; }
                table { max-width:600px; width:100%; margin:0 auto; background-color:#fff; border-collapse:collapse; }
                .header { background-color:#0047ba; padding:20px; text-align:center; color:#fff; }
                .body-content { padding:20px; }
                .footer { background-color:#0047ba; color:#fff; text-align:center; padding:10px 20px; font-size:14px; }
                .footer a { color:#fff; text-decoration:underline; }
            </style>
        </head>
        <body>
            <table>
                <tr><td class="header"><h1>Denuncia Liberada</h1></td></tr>
                <tr>
                    <td class="body-content">
                        <p>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</p>
                        <p>Le informamos que la denuncia registrada bajo el folio <strong>' . esc($denuncia['folio']) . '</strong> ha sido liberada.</p>
                        <p>Puede acceder directamente al portal: <a href="' . base_url() . '">Eqqua Denuncias</a>.</p>
                        <p>Atentamente,<br><strong>Equipo de Eqqua</strong></p>
                    </td>
                </tr>
                <tr><td class="footer">&copy; ' . date('Y') . ' Eqqua</td></tr>
            </table>
        </body>
        </html>';

            $emailService->sendEmail($email, 'Nueva Denuncia: ' . esc($denuncia['folio']), $mensaje);
            return true;
        } catch (\Throwable $t) {
            log_message('error', 'Error enviando correo de liberación: ' . $t->getMessage());
            return 'Error al enviar correo: ' . $t->getMessage();
        }
    }

    /* =======================
     * IA: Sugerencias
     * ======================= */

    public function generarSugerenciaIA($idDenuncia)
    {
        try {
            $denunciaModel   = new DenunciaModel();
            $iaService       = new IAService();
            $sugerenciaModel = new SugerenciaIAModel();

            // Evitar duplicados
            $sugerenciaExistente = $sugerenciaModel->getSugerenciaPorDenuncia($idDenuncia);
            if ($sugerenciaExistente) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ya existe una sugerencia para esta denuncia'
                ]);
            }

            // Datos enriquecidos
            $denuncia = $denunciaModel->select('
                denuncias.*,
                categorias_denuncias.nombre      AS categoria_nombre,
                subcategorias_denuncias.nombre   AS subcategoria_nombre,
                departamentos.nombre             AS departamento_nombre,
                sucursales.nombre                AS sucursal_nombre
            ')
                ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
                ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
                ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
                ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
                ->find($idDenuncia);

            if (!$denuncia) {
                return $this->response->setJSON(['success' => false, 'message' => 'Denuncia no encontrada']);
            }

            if (!(new IAService())->validarDatosMinimos($denuncia)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Datos insuficientes para generar sugerencia']);
            }

            $t0        = microtime(true);
            $resultado = $iaService->generarSugerenciaSolucion($denuncia);
            $t1        = microtime(true);

            if ($resultado['success']) {
                $tiempoGeneracion = round($t1 - $t0, 3);
                $costoEstimado    = $iaService->calcularCostoEstimado($resultado['tokens_usados']);

                // Guardamos incluyendo prompt_usado
                $sugerenciaData = [
                    'id_denuncia'      => $idDenuncia,
                    'sugerencia'       => $resultado['sugerencia'],
                    'tokens_usados'    => $resultado['tokens_usados'],
                    'costo_estimado'    => $costoEstimado,
                    'modelo'            => 'gpt-4o',
                    'tiempo_generacion' => $tiempoGeneracion,
                    'prompt_usado'            => $resultado['prompt_usado'] ?? null,   // <-- nuevo
                ];

                if ($sugerenciaModel->guardarSugerencia($sugerenciaData)) {
                    return $this->response->setJSON([
                        'success'           => true,
                        'message'           => 'Sugerencia generada exitosamente',
                        'sugerencia'        => $resultado['sugerencia'],
                        'tokens_usados'     => $resultado['tokens_usados'],
                        'costo_estimado'    => number_format($costoEstimado, 6),
                        'tiempo_generacion' => $tiempoGeneracion
                    ]);
                } else {
                    log_message('error', 'Sugerencia IA no guardada: {e}', ['e' => json_encode($sugerenciaModel->errors())]);
                    return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar la sugerencia']);
                }

                return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar la sugerencia']);
            }

            return $this->response->setJSON(['success' => false, 'message' => $resultado['error'] ?? 'No se pudo generar la sugerencia']);
        } catch (\Exception $e) {
            log_message('error', 'Error en generarSugerenciaIA: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function obtenerSugerenciaIA($idDenuncia)
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $sugerencia      = $sugerenciaModel->getSugerenciaPorDenuncia($idDenuncia);

            if (!$sugerencia) {
                return $this->response->setJSON(['success' => false, 'message' => 'No se encontró sugerencia para esta denuncia']);
            }

            if ($sugerencia['estado_sugerencia'] === 'generada') {
                $sugerenciaModel->marcarComoVista($sugerencia['id']);
            }

            return $this->response->setJSON(['success' => true, 'sugerencia' => $sugerencia]);
        } catch (\Exception $e) {
            log_message('error', 'Error en obtenerSugerenciaIA: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function evaluarSugerenciaIA()
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();

            $idSugerencia = (int)$this->request->getPost('id_sugerencia');
            $evaluacion   = (int)$this->request->getPost('evaluacion');
            $comentarios  = $this->request->getPost('comentarios') ?? '';

            if (!$idSugerencia || !$evaluacion) {
                return $this->response->setJSON(['success' => false, 'message' => 'Datos incompletos']);
            }
            if ($evaluacion < 1 || $evaluacion > 5) {
                return $this->response->setJSON(['success' => false, 'message' => 'La evaluación debe estar entre 1 y 5']);
            }

            if ($sugerenciaModel->evaluarSugerencia($idSugerencia, $evaluacion, $comentarios)) {
                return $this->response->setJSON(['success' => true, 'message' => 'Evaluación guardada exitosamente']);
            }

            return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar la evaluación']);
        } catch (\Exception $e) {
            log_message('error', 'Error en evaluarSugerenciaIA: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function regenerarSugerenciaIA($idDenuncia)
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();

            $sugerenciaAnterior = $sugerenciaModel->getSugerenciaPorDenuncia($idDenuncia);
            if ($sugerenciaAnterior) {
                $sugerenciaModel->delete($sugerenciaAnterior['id']);
            }

            return $this->generarSugerenciaIA($idDenuncia);
        } catch (\Exception $e) {
            log_message('error', 'Error en regenerarSugerenciaIA: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    public function estadisticasIA()
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $clienteId = session()->get('id_cliente');

            $estadisticas = $sugerenciaModel->getEstadisticasUso($clienteId);
            return $this->response->setJSON(['success' => true, 'estadisticas' => $estadisticas]);
        } catch (\Exception $e) {
            log_message('error', 'Error en estadisticasIA: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Edición por agente de la sugerencia (guardar como template ajustado).
     * POST: id_sugerencia, texto (sugerencia_agente)
     */
    public function guardarEdicionSugerencia()
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $idSugerencia    = (int)$this->request->getPost('id_sugerencia');
            $texto           = trim((string)$this->request->getPost('texto'));

            if (!$idSugerencia || $texto === '') {
                return $this->response->setJSON(['success' => false, 'message' => 'Datos incompletos']);
            }

            $ok = $sugerenciaModel->guardarEdicionAgente($idSugerencia, $texto, $this->currentUserId());
            return $this->response->setJSON(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            log_message('error', 'Error en guardarEdicionSugerencia: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Publicar/despublicar para cliente.
     * POST: id_sugerencia, publicar (0|1)
     */
    public function publicarSugerencia()
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $idSugerencia = (int)$this->request->getPost('id_sugerencia');
            $publicar     = (int)$this->request->getPost('publicar');

            if (!$idSugerencia || ($publicar !== 0 && $publicar !== 1)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Datos incompletos']);
            }

            $ok = $sugerenciaModel->publicarSugerencia($idSugerencia, $this->currentUserId(), $publicar === 1);
            return $this->response->setJSON(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            log_message('error', 'Error en publicarSugerencia: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /* =======================
     * Generación automática y límites
     * ======================= */
    protected function generarSugerenciaAutomatica(int $idDenuncia): void
    {
        try {
            $generacionAutomatica = getenv('IA_GENERACION_AUTOMATICA') === 'true';
            if (!$generacionAutomatica) return;

            if (!$this->verificarLimitesIA()) {
                log_message('warning', 'Límites de IA alcanzados, no se generará sugerencia automática');
                return;
            }

            $this->generarSugerenciaIA($idDenuncia);
        } catch (\Exception $e) {
            log_message('error', 'Error en generación automática de sugerencia: ' . $e->getMessage());
        }
    }

    private function verificarLimitesIA(): bool
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $limiteDiario = (int)(getenv('IA_LIMITE_DIARIO_TOKENS') ?: 50000);

            $row = $sugerenciaModel->builder()
                ->select('COALESCE(SUM(tokens_utilizados), 0) AS total') // <-- columna correcta
                ->where('DATE(created_at)', date('Y-m-d'))
                ->get()
                ->getRowArray();

            $tokensHoy = (int)($row['total'] ?? 0);
            return $tokensHoy < $limiteDiario;
        } catch (\Exception $e) {
            log_message('error', 'Error al verificar límites IA: ' . $e->getMessage());
            return false;
        }
    }

    /* =======================
     * Helpers
     * ======================= */
    private function currentUserId(): ?int
    {
        $id = session()->get('id');
        return $id ? (int)$id : null;
    }
}
