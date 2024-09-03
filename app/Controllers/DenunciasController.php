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
use CodeIgniter\Controller;

class DenunciasController extends Controller
{
    public function index()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        $estadoModel = new EstadoDenunciaModel();
        $estados = $estadoModel->findAll();

        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->findAll();

        $data = [
            'title' => 'Administración de Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Denuncias',
            'clientes' => $clientes,
            'estados' => $estados,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
        ];

        return view('denuncias/index', $data);
    }

    public function misDenunciasAgente()
    {
        $clienteModel = new ClienteModel();
        $estadoModel = new EstadoDenunciaModel();
        $categoriaModel = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        // Obtener el ID del agente desde la sesión
        $agenteId = session()->get('id');

        // Obtener solo los clientes relacionados con el agente
        $clientes = $clienteModel->getClientesByAgente($agenteId);

        $data = [
            'title' => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Mis Denuncias Agente',
            'clientes' => $clientes,
            'estados' => $estadoModel->findAll(),
            'categorias' => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
        ];

        return view('denuncias/mis_denuncias_agente', $data);
    }

    public function listar()
    {
        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->getDenuncias();

        return $this->response->setJSON($denuncias);
    }

    public function listarDenunciasAgente()
    {
        $denunciaModel = new DenunciaModel();
        $agenteId = session()->get('id');

        // Filtra las denuncias según los clientes que el agente puede ver
        $denuncias = $denunciaModel->getDenunciasByAgente($agenteId);

        return $this->response->setJSON($denuncias);
    }


    public function detalle($id)
    {
        $denunciaModel = new DenunciaModel();
        $seguimientoModel = new SeguimientoDenunciaModel(); // Instancia el modelo de seguimiento

        $denuncia = $denunciaModel->getDenunciaById($id);
        $seguimientos = $seguimientoModel->getSeguimientoByDenunciaId($id); // Obtén el historial de seguimiento

        // Adjunta los seguimientos al array de denuncia
        $denuncia['seguimientos'] = $seguimientos;

        return $this->response->setJSON($denuncia);
    }

    public function guardar()
    {
        $denunciaModel = new DenunciaModel();
        $anexoModel = new AnexoDenunciaModel();
        $id = $this->request->getVar('id');

        // Verifica que la sesión esté iniciada y el usuario esté autenticado
        $idCreador = session()->get('id');
        if (!$idCreador) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Usuario no autenticado o sesión no iniciada']);
        }

        // Recopilar solo los datos enviados, incluyendo estado_actual
        $data = array_filter([
            'id_cliente' => $this->request->getVar('id_cliente'),
            'id_sucursal' => $this->request->getVar('id_sucursal'),
            'tipo_denunciante' => $this->request->getVar('tipo_denunciante'),
            'categoria' => $this->request->getVar('categoria'),
            'subcategoria' => $this->request->getVar('subcategoria'),
            'id_departamento' => $this->request->getVar('id_departamento') ?: null,
            'anonimo' => $this->request->getVar('anonimo'),
            'fecha_incidente' => $this->request->getVar('fecha_incidente'),
            'como_se_entero' => $this->request->getVar('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getVar('denunciar_a_alguien'),
            'area_incidente' => $this->request->getVar('area_incidente'),
            'descripcion' => $this->request->getVar('descripcion'),
            'estado_actual' => $this->request->getVar('estado_actual'),  // Capturar estado_actual
            'id_creador' => $idCreador, // Obtiene el ID del creador desde la sesión
        ], function ($value) {
            return $value !== null; // Filtrar solo valores no nulos
        });

        $db = \Config\Database::connect();
        $db->transStart(); // Inicia una transacción

        try {
            // Guardar o actualizar la denuncia
            if ($id) {
                // Actualizar solo los campos que se han proporcionado en la solicitud
                if (!$denunciaModel->update($id, $data)) {
                    throw new \RuntimeException('Error al actualizar la denuncia.');
                }
                registrarAccion($idCreador, 'Actualización de denuncia', 'ID: ' . $id);
            } else {
                if (!$denunciaModel->save($data)) {
                    throw new \RuntimeException('Error al guardar la denuncia.');
                }
                $newId = $denunciaModel->insertID();
                registrarAccion($idCreador, 'Creación de denuncia', 'ID: ' . $newId);
                $id = $newId; // Usa el nuevo ID para la inserción de anexos
            }

            // Procesa los archivos adjuntos (anexos) desde los inputs ocultos
            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    // Obtener el nombre del archivo desde la ruta
                    $nombreArchivo = basename($rutaArchivo);

                    // Guarda la información del anexo en la base de datos
                    if (!$anexoModel->save([
                        'id_denuncia' => $id,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $rutaArchivo,
                        'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            $db->transComplete(); // Finaliza la transacción

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }
        } catch (\Exception $e) {
            $db->transRollback(); // Revertir la transacción en caso de error
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al guardar la denuncia y los archivos adjuntos. Error: ' . $e->getMessage()]);
        }

        return $this->response->setJSON(['message' => 'Denuncia guardada correctamente']);
    }

    public function eliminar($id)
    {
        $denunciaModel = new DenunciaModel();

        if ($denunciaModel->eliminarDenuncia($id)) {
            return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente']);
        } else {
            return $this->response->setStatusCode(500)
                ->setJSON(['message' => 'Error al eliminar la denuncia']);
        }
    }


    public function cambiarEstado()
    {
        $denunciaModel = new DenunciaModel();
        $id = $this->request->getVar('id');
        $estado_nuevo = $this->request->getVar('estado_nuevo');

        // Obtener el estado anterior antes de realizar el cambio
        $denuncia = $denunciaModel->find($id);
        $estado_anterior = $denuncia['estado_actual'];

        // Realizar el cambio de estado
        $denunciaModel->cambiarEstado($id, $estado_nuevo);

        // Guardar en el historial de seguimiento
        $seguimientoModel = new SeguimientoDenunciaModel();
        $seguimientoModel->save([
            'id_denuncia' => $id,
            'estado_anterior' => $estado_anterior, // Aquí se asegura que no sea null
            'estado_nuevo' => $estado_nuevo,
            'comentario' => $this->request->getVar('comentario'),
            'id_usuario' => session()->get('id'),
        ]);

        registrarAccion(session()->get('id'), 'Cambio de estado de denuncia', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Estado actualizado correctamente']);
    }


    public function subirAnexo()
    {
        $file = $this->request->getFile('file');

        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . '../public/assets/denuncias', $newName);
            return $this->response->setJSON(['filename' => $newName]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo subir el anexo.']);
    }

    public function obtenerSucursalesPorCliente($id_cliente)
    {
        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->where('id_cliente', $id_cliente)->findAll();
        return $this->response->setJSON($sucursales);
    }

    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();
        $departamentos = $departamentoModel->where('id_sucursal', $id_sucursal)->findAll();

        return $this->response->setJSON($departamentos);
    }

    public function obtenerEstados()
    {
        $estadoModel = new EstadoDenunciaModel();
        $estados = $estadoModel->getEstados();

        return $this->response->setJSON($estados);
    }

    public function obtenerAnexos($id_denuncia)
    {
        $anexoModel = new \App\Models\AnexoDenunciaModel();
        $anexos = $anexoModel->getAnexosByDenunciaId($id_denuncia);

        return $this->response->setJSON($anexos);
    }

    public function misDenuncias()
    {
        $clienteId = session()->get('id_cliente'); // Asegurándote que 'id_cliente' esté en la sesión
        $denunciaModel = new DenunciaModel();

        // Verificar si la solicitud es AJAX
        if ($this->request->isAJAX()) {
            // Buscar denuncias por cliente y devolver en formato JSON
            $denuncias = $denunciaModel->getDenunciasByCliente($clienteId);
            return $this->response->setJSON($denuncias);
        }

        // Si no es AJAX, cargar la vista normalmente
        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        $data = [
            'title' => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Mis Denuncias',
            'categorias' => $categorias,
        ];

        return view('mis_denuncias/index', $data);
    }

    public function actualizarAnexos()
    {
        $denunciaId = $this->request->getVar('id');
        $anexoModel = new AnexoDenunciaModel();

        // Iniciar la transacción
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Procesar los nuevos archivos adjuntos desde los inputs ocultos
            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    $nombreArchivo = basename($rutaArchivo);

                    // Guardar la información del anexo en la base de datos
                    if (!$anexoModel->save([
                        'id_denuncia' => $denunciaId,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $rutaArchivo,
                        'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            // Completar la transacción
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }

            return $this->response->setJSON(['message' => 'Archivos actualizados correctamente']);
        } catch (\Exception $e) {
            $db->transRollback(); // Revertir la transacción en caso de error
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al actualizar los archivos adjuntos. Error: ' . $e->getMessage()]);
        }
    }

    public function eliminarAnexo($id)
    {
        $anexoModel = new AnexoDenunciaModel();

        try {
            // Obtener el anexo antes de eliminarlo para eliminar el archivo físicamente
            $anexo = $anexoModel->getAnexoById($id);
            if (!$anexo) {
                throw new \RuntimeException('Anexo no encontrado.');
            }

            // Eliminar el anexo de la base de datos
            if (!$anexoModel->deleteAnexo($id)) {
                throw new \RuntimeException('Error al eliminar el anexo.');
            }

            // Eliminar el archivo físicamente del servidor
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
}
