<?php

namespace App\Controllers;

use App\Models\CategoriaDenunciaModel;
use App\Models\ClienteModel;
use App\Models\SucursalModel;
use App\Models\DenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use App\Models\AnexoDenunciaModel;
use App\Models\ComentarioDenunciaModel;
use App\Models\SugerenciaIAModel;
use App\Services\IAService;

class Publico extends BaseController
{
    public function verCliente($slug)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'title'   => 'Inicio - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente
        ];

        return view('publico/ver_cliente', $data);
    }

    public function formularioDenuncia($slug)
    {
        $clienteModel   = new ClienteModel();
        $categoriaModel = new CategoriaDenunciaModel();
        $sucursalModel  = new SucursalModel();

        $cliente = $clienteModel->where('slug', $slug)->first();
        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'title'      => 'Registrar Denuncia - ' . esc($cliente['nombre_empresa']),
            'cliente'    => $cliente,
            'categorias' => $categoriaModel->findAll(),
            'sucursales' => $sucursalModel->where('id_cliente', $cliente['id'])->findAll()
        ];

        return view('publico/formulario_denuncia', $data);
    }

    /**
     * Guarda denuncia pública
     * - Respeta política de anonimato
     * - Adjunta archivos (incluido audio)
     * - Genera sugerencia IA si está habilitada
     */
    public function guardarDenunciaPublica()
    {
        $denunciaModel = new DenunciaModel();
        $clienteModel  = new ClienteModel();

        // 1) Cliente y política
        $idCliente = (int) $this->request->getPost('id_cliente');
        $cliente   = $clienteModel->find($idCliente);
        if (!$cliente) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Cliente no encontrado.'
            ]);
        }
        $politica = (int) ($cliente['politica_anonimato'] ?? 0); // 0 opcional, 1 forzar anón., 2 forzar ident.

        // 2) Identidad/anonimato
        $anonimoInput = $this->request->getPost('anonimo');
        $anonimo      = ($anonimoInput === null) ? null : (int) $anonimoInput;

        $nombre  = $this->request->getPost('nombre_completo');
        $correo  = $this->request->getPost('correo_electronico');
        $tel     = $this->request->getPost('telefono');
        $id_sexo = $this->request->getPost('id_sexo');

        switch ($politica) {
            case 1: // forzar anónimas
                $anonimo = 1;
                $nombre = $correo = $tel = $id_sexo = null;
                break;
            case 2: // forzar identificadas
                $anonimo = 0;
                if (empty(trim((string)$nombre)) || (empty(trim((string)$correo)) && empty(trim((string)$tel)))) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'Este cliente requiere denuncias identificadas: nombre y (correo o teléfono) son obligatorios.'
                    ]);
                }
                break;
            default: // opcional
                $anonimo = (int) ($anonimo ?? 1);
                if ($anonimo === 0) {
                    if (empty(trim((string)$nombre)) || (empty(trim((string)$correo)) && empty(trim((string)$tel)))) {
                        return $this->response->setStatusCode(422)->setJSON([
                            'success' => false,
                            'message' => 'Para denuncias no anónimas indique su nombre y al menos correo o teléfono.'
                        ]);
                    }
                } else {
                    $nombre = $correo = $tel = $id_sexo = null;
                }
                break;
        }

        // 3) Payload (NO enviamos campos vacíos para no romper NOT NULL)
        $raw = [
            'id_cliente'          => $idCliente,
            'id_sucursal'         => $this->request->getPost('id_sucursal'),
            'id_departamento'     => $this->request->getPost('id_departamento'),
            // OJO: en tu BD `categoria`/`subcategoria` pueden ser NOT NULL,
            // pero tu formulario público no los envía. No los incluimos aquí
            // para que el modelo/BD resuelvan sus defaults. Si quieres forzar
            // un ID por defecto, descomenta estas 2 líneas:
            // 'categoria'           => getenv('DEFAULT_CATEGORIA_ID') ?: null,
            // 'subcategoria'        => getenv('DEFAULT_SUBCATEGORIA_ID') ?: null,
            'tipo_denunciante'    => $anonimo ? 'Anónimo' : 'No anónimo',
            'anonimo'             => $anonimo,
            'nombre_completo'     => $nombre,
            'correo_electronico'  => $correo,
            'telefono'            => $tel,
            'id_sexo'             => $id_sexo,
            'fecha_incidente'     => convertir_fecha($this->request->getPost('fecha_incidente')),
            'como_se_entero'      => $this->request->getPost('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getPost('denunciar_a_alguien'),
            'area_incidente'      => $this->request->getPost('area_incidente'),
            'descripcion'         => $this->request->getPost('descripcion'),
            'medio_recepcion'     => 'Plataforma Pública',
            'estado_actual'       => 1,
            'id_creador'          => null,
        ];

        // Limpia nulos/vacíos para no enviar NULL a columnas NOT NULL
        $data = array_filter($raw, static function ($v) {
            return $v !== null && $v !== '';
        });

        // 4) Transacción: denuncia + anexos
        $db = \Config\Database::connect();
        $db->transStart();

        if (!$denunciaModel->save($data)) {
            $db->transRollback();
            // Ayuda de depuración: muestra validaciones del modelo
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Error al guardar la denuncia',
                'debug'   => $denunciaModel->errors() // <— quítalo en producción
            ]);
        }

        $denunciaId = (int)$denunciaModel->getInsertID();
        $denuncia   = $denunciaModel->find($denunciaId);
        $folio      = $denuncia['folio'] ?? '';

        // Anexos “normales” (rutas desde Dropzone)
        $anexos = $this->request->getPost('archivos');
        if ($anexos && is_array($anexos)) {
            $anexoModel = new AnexoDenunciaModel();
            foreach ($anexos as $rutaArchivo) {
                $anexoModel->save([
                    'id_denuncia'    => $denunciaId,
                    'nombre_archivo' => basename($rutaArchivo),
                    'ruta_archivo'   => $rutaArchivo,
                    'tipo'           => @mime_content_type(WRITEPATH . '../public/' . $rutaArchivo) ?: 'application/octet-stream',
                ]);
            }
        }

        // Audio opcional
        $audioFile = $this->request->getFile('audio_file');
        if ($audioFile && $audioFile->isValid()) {
            $mimeType = $audioFile->getMimeType();
            if ($audioFile->getSize() <= 5 * 1024 * 1024 && in_array($mimeType, ['audio/wav', 'audio/mpeg', 'audio/ogg', 'video/webm'])) {
                $newAudioName = $audioFile->getRandomName();
                if ($audioFile->move(WRITEPATH . '../public/uploads/denuncias', $newAudioName)) {
                    $anexoModel = new AnexoDenunciaModel();
                    $anexoModel->save([
                        'id_denuncia'    => $denunciaId,
                        'nombre_archivo' => $newAudioName,
                        'ruta_archivo'   => 'uploads/denuncias/' . $newAudioName,
                        'tipo'           => $mimeType,
                    ]);
                } else {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON(['message' => 'No se pudo subir el archivo de audio']);
                }
            } else {
                $db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Archivo de audio no válido o demasiado grande',
                ]);
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al completar la transacción.'
            ]);
        }

        // 5) Sugerencia IA automática (no bloquea la respuesta)
        $this->generarSugerenciaAutomatica($denunciaId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Denuncia guardada correctamente',
            'folio'   => $folio
        ]);
    }

    public function subirAnexoPublico()
    {
        $file = $this->request->getFile('file');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();

            if ($file->move(WRITEPATH . '../public/uploads/denuncias', $newName)) {
                return $this->response->setJSON([
                    'filename' => $newName,
                    'message'  => 'Archivo subido correctamente'
                ]);
            } else {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'No se pudo subir el archivo']);
            }
        }

        return $this->response->setStatusCode(400)->setJSON(['message' => 'Archivo inválido']);
    }

    public function seguimientoDenuncia($slug)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $folio = $this->request->getGet('folio');

        $data = [
            'title'   => 'Seguimiento de Denuncia - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente,
            'folio'   => $folio
        ];

        return view('publico/seguimiento_denuncia', $data);
    }

    public function obtenerSubcategorias($categoriaId)
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->where('id_categoria', $categoriaId)->findAll();

        return $this->response->setJSON($subcategorias);
    }

    public function obtenerDepartamentos($sucursalId)
    {
        $sucursalModel = new SucursalModel();
        $departamentos = $sucursalModel->obtenerDepartamentosPorSucursal($sucursalId);

        return $this->response->setJSON($departamentos);
    }

    public function consultarDenuncia()
    {
        $folio      = $this->request->getGet('folio');
        $id_cliente = $this->request->getGet('id_cliente');

        if (!$folio || !$id_cliente) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Debe proporcionar un número de folio e ID de cliente.'
            ]);
        }

        $denunciaModel = new DenunciaModel();
        $denuncia = $denunciaModel
            ->select('denuncias.*, 
            clientes.nombre_empresa AS cliente_nombre, 
            sucursales.nombre AS sucursal_nombre, 
            categorias_denuncias.nombre AS categoria_nombre, 
            subcategorias_denuncias.nombre AS subcategoria_nombre, 
            departamentos.nombre AS departamento_nombre, 
            estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->where('denuncias.folio', $folio)
            ->where('denuncias.id_cliente', $id_cliente)
            ->first();

        if (!$denuncia) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Denuncia no encontrada o no pertenece al cliente proporcionado.'
            ]);
        }

        $comentarioModel = new ComentarioDenunciaModel();
        $comentarios     = $comentarioModel->getComentariosByDenuncia($denuncia['id']);
        $comentariosVisibles = array_filter($comentarios, function ($comentario) {
            return in_array($comentario['estado_denuncia'], [4, 5, 6]);
        });

        $anexoModel        = new AnexoDenunciaModel();
        $archivosDenuncia  = $anexoModel->where('id_denuncia', $denuncia['id'])->findAll();

        $anexoComentarioModel   = new \App\Models\AnexoComentarioModel();
        $idsComentariosVisibles = array_column($comentariosVisibles, 'id');
        $archivosComentarios    = [];

        if (!empty($idsComentariosVisibles)) {
            $archivosComentariosRaw = $anexoComentarioModel
                ->whereIn('id_comentario', $idsComentariosVisibles)
                ->where('visible_para_cliente', 1)
                ->findAll();

            foreach ($archivosComentariosRaw as $archivo) {
                $id_comentario = $archivo['id_comentario'];
                if (!isset($archivosComentarios[$id_comentario])) {
                    $archivosComentarios[$id_comentario] = [];
                }
                $archivosComentarios[$id_comentario][] = $archivo;
            }
        }

        foreach ($comentariosVisibles as &$comentario) {
            $comentario['archivos'] = $archivosComentarios[$comentario['id']] ?? [];
        }

        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Denuncia encontrada con éxito.',
            'denuncia'   => $denuncia,
            'comentarios' => array_values($comentariosVisibles),
            'archivos'   => $archivosDenuncia
        ]);
    }

    // =======================
    //  IA (flujo público)
    // =======================

    public function generarSugerenciaIA($idDenuncia)
    {
        try {
            $denunciaModel   = new DenunciaModel();
            $iaService       = new IAService();
            $sugerenciaModel = new SugerenciaIAModel();

            $existente = $sugerenciaModel->getSugerenciaPorDenuncia((int)$idDenuncia);
            if ($existente) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Ya existe una sugerencia para esta denuncia'
                ]);
            }

            $denuncia = $denunciaModel->select('
                denuncias.*,
                categorias_denuncias.nombre    AS categoria_nombre,
                subcategorias_denuncias.nombre AS subcategoria_nombre,
                departamentos.nombre           AS departamento_nombre,
                sucursales.nombre              AS sucursal_nombre
            ')
                ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
                ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
                ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
                ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
                ->find((int)$idDenuncia);

            if (!$denuncia) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Denuncia no encontrada'
                ]);
            }

            if (!$iaService->validarDatosMinimos($denuncia)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Datos insuficientes para generar sugerencia'
                ]);
            }

            $t0        = microtime(true);
            $resultado = $iaService->generarSugerenciaSolucion($denuncia);
            $elapsed   = round(microtime(true) - $t0, 3);

            if ($resultado['success'] ?? false) {
                $costoEstimado = $iaService->calcularCostoEstimado((int)($resultado['tokens_usados'] ?? 0));

                $ok = $sugerenciaModel->guardarSugerencia([
                    'id_denuncia'      => (int)$idDenuncia,
                    'sugerencia'       => $resultado['sugerencia'],
                    'tokens_usados'    => (int)($resultado['tokens_usados'] ?? 0),
                    'costo_estimado'   => $costoEstimado,
                    'modelo'           => 'gpt-4o',
                    'tiempo_generacion' => $elapsed
                ]);

                if ($ok) {
                    return $this->response->setJSON([
                        'success'          => true,
                        'message'          => 'Sugerencia generada exitosamente',
                        'tokens_usados'    => (int)($resultado['tokens_usados'] ?? 0),
                        'costo_estimado'   => number_format($costoEstimado, 6),
                        'tiempo_generacion' => $elapsed
                    ]);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al guardar la sugerencia'
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => $resultado['error'] ?? 'No se pudo generar la sugerencia'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error en generarSugerenciaIA (público): ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    protected function generarSugerenciaAutomatica(int $idDenuncia): void
    {
        try {
            $habilitada = getenv('IA_GENERACION_AUTOMATICA') === 'true';
            if (!$habilitada) return;

            if (!$this->verificarLimitesIA()) {
                log_message('warning', 'Límites IA alcanzados (público); no se generará sugerencia automática.');
                return;
            }

            $this->generarSugerenciaIA($idDenuncia);
        } catch (\Throwable $e) {
            log_message('error', 'Error en generación automática IA (público): ' . $e->getMessage());
        }
    }

    private function verificarLimitesIA(): bool
    {
        try {
            $sugerenciaModel = new SugerenciaIAModel();
            $limiteDiario    = (int)(getenv('IA_LIMITE_DIARIO_TOKENS') ?: 50000);

            $row = $sugerenciaModel->builder()
                ->select('COALESCE(SUM(tokens_utilizados), 0) AS total')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->get()
                ->getRowArray();

            $tokensHoy = (int)($row['total'] ?? 0);
            return $tokensHoy < $limiteDiario;
        } catch (\Throwable $e) {
            log_message('error', 'Error al verificar límites IA (público): ' . $e->getMessage());
            return false;
        }
    }

    private function convertirFecha($fecha)
    {
        if (!$fecha) return null;
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        return null;
    }
}
