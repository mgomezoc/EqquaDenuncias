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
        // Instanciar el modelo de Denuncia
        $denunciaModel = new DenunciaModel();

        // Determinar el valor de 'id_creador'. Si es anónimo, usar NULL.
        $id_creador = ($this->request->getPost('anonimo') == 1) ? null : 0; // Ajusta el valor 0 si tienes un usuario especial, o usa null.

        // Recoger datos del formulario
        $data = [
            'id_cliente' => $this->request->getPost('id_cliente'),
            'id_sucursal' => $this->request->getPost('id_sucursal'),
            'tipo_denunciante' => 'Anonimo', // Fijo para la parte pública
            'categoria' => $this->request->getPost('categoria'),
            'subcategoria' => $this->request->getPost('subcategoria'),
            'id_departamento' => $this->request->getPost('id_departamento'),
            'anonimo' => $this->request->getPost('anonimo'),
            'fecha_incidente' => $this->request->getPost('fecha_incidente'),
            'como_se_entero' => $this->request->getPost('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getPost('denunciar_a_alguien'),
            'area_incidente' => $this->request->getPost('area_incidente'),
            'descripcion' => $this->request->getPost('descripcion'),
            'medio_recepcion' => 'Plataforma Pública', // Medio de recepción público
            'estado_actual' => 1, // Estado inicial (Recepción)
            'id_creador' => $id_creador, // Ajuste en la creación del ID
        ];

        // Guardar la denuncia
        if (!$denunciaModel->save($data)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Error al guardar la denuncia']);
        }

        // Obtener el ID de la denuncia recién creada
        $denunciaId = $denunciaModel->getInsertID();

        // Procesar archivos adjuntos (si existen)
        $anexos = $this->request->getPost('archivos');
        if ($anexos && is_array($anexos)) {
            $anexoModel = new AnexoDenunciaModel();
            foreach ($anexos as $rutaArchivo) {
                // Guardar cada anexo en la base de datos
                $anexoModel->save([
                    'id_denuncia' => $denunciaId,
                    'nombre_archivo' => basename($rutaArchivo),
                    'ruta_archivo' => $rutaArchivo,
                    'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                ]);
            }
        }

        return $this->response->setJSON(['message' => 'Denuncia guardada correctamente']);
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

        $data = [
            'title' => 'Seguimiento de Denuncia - ' . esc($cliente['nombre_empresa']),
            'cliente' => $cliente
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
        // Obtener el folio de la solicitud GET
        $folio = $this->request->getGet('folio');

        // Validar si se proporcionó un folio
        if (!$folio) {
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Debe proporcionar un número de folio.']);
        }

        // Buscar la denuncia en la base de datos por el folio, usando joins para traer nombres asociados
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
            ->first();

        // Verificar si se encontró la denuncia
        if (!$denuncia) {
            return $this->response->setStatusCode(404)
                ->setJSON(['message' => 'Denuncia no encontrada.']);
        }

        // Obtener los comentarios visibles para el cliente (en estados 4, 5 y 6)
        $comentarioModel = new ComentarioDenunciaModel();
        $comentarios = $comentarioModel->getComentariosByDenuncia($denuncia['id']);

        // Filtrar comentarios visibles solo si el estado está en [4, 5, 6]
        $comentariosVisibles = array_filter($comentarios, function ($comentario) {
            return in_array($comentario['estado_denuncia'], [4, 5, 6]);
        });

        // Obtener los anexos asociados a la denuncia
        $anexoModel = new AnexoDenunciaModel();
        $archivos = $anexoModel->where('id_denuncia', $denuncia['id'])->findAll();

        // Responder con los detalles de la denuncia, los comentarios y los archivos adjuntos
        return $this->response->setJSON([
            'denuncia' => $denuncia,
            'comentarios' => $comentariosVisibles,
            'archivos' => $archivos
        ]);
    }
}
