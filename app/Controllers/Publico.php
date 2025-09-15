<?php

namespace App\Controllers;

use App\Models\CategoriaDenunciaModel;
use App\Models\ClienteModel;
use App\Models\SucursalModel;
use App\Models\DenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use App\Models\AnexoDenunciaModel;
use App\Models\ComentarioDenunciaModel;

class Publico extends BaseController
{
    /**
     * Muestra la página principal del cliente
     */
    public function verCliente($slug)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'title' => 'Inicio - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente
        ];

        return view('publico/ver_cliente', $data);
    }

    /**
     * Muestra el formulario de denuncia pública para un cliente
     */
    public function formularioDenuncia($slug)
    {
        $clienteModel = new ClienteModel();
        $categoriaModel = new CategoriaDenunciaModel();
        $sucursalModel = new SucursalModel();

        // Obtener el cliente por slug
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'title' => 'Registrar Denuncia - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente,
            'categorias' => $categoriaModel->findAll(),
            'sucursales' => $sucursalModel->where('id_cliente', $cliente['id'])->findAll()
        ];

        return view('publico/formulario_denuncia', $data);
    }

    /**
     * Guarda una denuncia pública enviada desde el formulario de denuncia pública
     */
    public function guardarDenunciaPublica()
    {
        $denunciaModel = new DenunciaModel();
        $clienteModel  = new ClienteModel();

        // Identificar cliente y su política
        $idCliente = (int) $this->request->getPost('id_cliente');
        $cliente   = $clienteModel->find($idCliente);
        if (!$cliente) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Cliente no encontrado.']);
        }
        $politica = (int) ($cliente['politica_anonimato'] ?? 0); // 0=opcional, 1=forzar anónimas, 2=forzar identificadas

        // Valor enviado desde el form (por si es opcional)
        $anonimoInput = $this->request->getPost('anonimo');
        $anonimo      = ($anonimoInput === null) ? null : (int) $anonimoInput;

        // Campos de identificación (podrían venir o no)
        $nombre  = $this->request->getPost('nombre_completo');
        $correo  = $this->request->getPost('correo_electronico');
        $tel     = $this->request->getPost('telefono');
        $id_sexo = $this->request->getPost('id_sexo');

        // Aplicar política
        switch ($politica) {
            case 1: // FORZAR ANÓNIMAS
                $anonimo = 1;
                $nombre = $correo = $tel = $id_sexo = null; // limpiar por seguridad
                break;

            case 2: // FORZAR IDENTIFICADAS
                $anonimo = 0;
                // Validar identificación: nombre y (correo o teléfono)
                if (empty(trim((string)$nombre)) || (empty(trim((string)$correo)) && empty(trim((string)$tel)))) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'Este cliente requiere denuncias identificadas: nombre y (correo o teléfono) son obligatorios.'
                    ]);
                }
                break;

            default: // OPCIONAL
                $anonimo = (int) ($anonimo ?? 1);
                if ($anonimo === 0) {
                    if (empty(trim((string)$nombre)) || (empty(trim((string)$correo)) && empty(trim((string)$tel)))) {
                        return $this->response->setStatusCode(422)->setJSON([
                            'success'   => false,
                            'message'   => 'Para denuncias no anónimas indique su nombre y al menos correo o teléfono.'
                        ]);
                    }
                } else {
                    // Si eligió anónimo, por seguridad limpiamos
                    $nombre = $correo = $tel = $id_sexo = null;
                }
                break;
        }

        // Construir payload
        $data = [
            'id_cliente'         => $idCliente,
            'id_sucursal'        => $this->request->getPost('id_sucursal'),
            'tipo_denunciante'   => $anonimo ? 'Anónimo' : 'No anónimo',
            'id_departamento'    => $this->request->getPost('id_departamento'),
            'anonimo'            => $anonimo,
            'nombre_completo'    => $nombre,
            'correo_electronico' => $correo,
            'telefono'           => $tel,
            'id_sexo'            => $id_sexo,
            'fecha_incidente'    => convertir_fecha($this->request->getPost('fecha_incidente')),
            'como_se_entero'     => $this->request->getPost('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getPost('denunciar_a_alguien'),
            'area_incidente'     => $this->request->getPost('area_incidente'),
            'descripcion'        => $this->request->getPost('descripcion'),
            'medio_recepcion'    => 'Plataforma Pública',
            'estado_actual'      => 1,
            'id_creador'         => null
        ];

        if (!$denunciaModel->save($data)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Error al guardar la denuncia']);
        }

        $denunciaId = $denunciaModel->getInsertID();
        $folio      = $denunciaModel->find($denunciaId)['folio'];

        // Anexos “normales”
        $anexos = $this->request->getPost('archivos');
        if ($anexos && is_array($anexos)) {
            $anexoModel = new AnexoDenunciaModel();
            foreach ($anexos as $rutaArchivo) {
                $anexoModel->save([
                    'id_denuncia'    => $denunciaId,
                    'nombre_archivo' => basename($rutaArchivo),
                    'ruta_archivo'   => $rutaArchivo,
                    'tipo'           => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                ]);
            }
        }

        // Anexo de audio (si viene)
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
                    return $this->response->setStatusCode(400)->setJSON(['message' => 'No se pudo subir el archivo de audio']);
                }
            } else {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Archivo de audio no válido o demasiado grande',
                ]);
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Denuncia guardada correctamente',
            'folio'   => $folio
        ]);
    }


    /**
     * Subir archivo anexo para la denuncia usando Dropzone
     */
    public function subirAnexoPublico()
    {
        $file = $this->request->getFile('file');

        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();

            // Mover el archivo a la carpeta pública
            if ($file->move(WRITEPATH . '../public/uploads/denuncias', $newName)) {
                return $this->response->setJSON([
                    'filename' => $newName,
                    'message' => 'Archivo subido correctamente'
                ]);
            } else {
                return $this->response->setStatusCode(400)
                    ->setJSON(['message' => 'No se pudo subir el archivo']);
            }
        } else {
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Archivo inválido']);
        }
    }

    /**
     * Seguimiento de denuncia pública para un cliente
     */
    public function seguimientoDenuncia($slug)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->where('slug', $slug)->first();

        if (!$cliente) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $folio = $this->request->getGet('folio');

        $data = [
            'title' => 'Seguimiento de Denuncia - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente,
            'folio' => $folio
        ];

        return view('publico/seguimiento_denuncia', $data);
    }

    /**
     * Obtener subcategorías según la categoría seleccionada (usado por el formulario)
     */
    public function obtenerSubcategorias($categoriaId)
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->where('id_categoria', $categoriaId)->findAll();

        return $this->response->setJSON($subcategorias);
    }

    /**
     * Obtener departamentos según la sucursal seleccionada (usado por el formulario)
     */
    public function obtenerDepartamentos($sucursalId)
    {
        $sucursalModel = new SucursalModel();
        $departamentos = $sucursalModel->obtenerDepartamentosPorSucursal($sucursalId);

        return $this->response->setJSON($departamentos);
    }

    public function consultarDenuncia()
    {
        $folio = $this->request->getGet('folio');
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
        $comentarios = $comentarioModel->getComentariosByDenuncia($denuncia['id']);
        $comentariosVisibles = array_filter($comentarios, function ($comentario) {
            return in_array($comentario['estado_denuncia'], [4, 5, 6]);
        });

        // Obtener archivos de denuncia directamente
        $anexoModel = new AnexoDenunciaModel();
        $archivosDenuncia = $anexoModel->where('id_denuncia', $denuncia['id'])->findAll();

        // Obtener archivos de comentarios visibles para el cliente
        $anexoComentarioModel = new \App\Models\AnexoComentarioModel();
        $idsComentariosVisibles = array_column($comentariosVisibles, 'id');
        $archivosComentarios = [];

        if (!empty($idsComentariosVisibles)) {
            $archivosComentariosRaw = $anexoComentarioModel
                ->whereIn('id_comentario', $idsComentariosVisibles)
                ->where('visible_para_cliente', 1)
                ->findAll();

            // Agrupar por id_comentario
            foreach ($archivosComentariosRaw as $archivo) {
                $id_comentario = $archivo['id_comentario'];
                if (!isset($archivosComentarios[$id_comentario])) {
                    $archivosComentarios[$id_comentario] = [];
                }
                $archivosComentarios[$id_comentario][] = $archivo;
            }
        }

        // Agregar archivos al comentario correspondiente
        foreach ($comentariosVisibles as &$comentario) {
            $comentario['archivos'] = $archivosComentarios[$comentario['id']] ?? [];
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Denuncia encontrada con éxito.',
            'denuncia' => $denuncia,
            'comentarios' => array_values($comentariosVisibles), // reindexar
            'archivos' => $archivosDenuncia
        ]);
    }


    private function convertirFecha($fecha)
    {
        if (!$fecha) return null;

        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0]; // yyyy-mm-dd
        }

        return null;
    }
}
