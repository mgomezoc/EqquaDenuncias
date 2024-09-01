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

    public function listar()
    {
        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->getDenuncias();

        return $this->response->setJSON($denuncias);
    }

    public function detalle($id)
    {
        $denunciaModel = new DenunciaModel();
        $denuncia = $denunciaModel->getDenunciaById($id);

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

        // Datos principales de la denuncia
        $data = [
            'id_cliente' => $this->request->getVar('id_cliente'),
            'id_sucursal' => $this->request->getVar('id_sucursal'),
            'tipo_denunciante' => $this->request->getVar('tipo_denunciante'),
            'categoria' => $this->request->getVar('categoria'),
            'subcategoria' => $this->request->getVar('subcategoria'),
            'id_departamento' => $this->request->getVar('id_departamento'),
            'anonimo' => $this->request->getVar('anonimo'),
            'fecha_incidente' => $this->request->getVar('fecha_incidente'),
            'como_se_entero' => $this->request->getVar('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getVar('denunciar_a_alguien'),
            'area_incidente' => $this->request->getVar('area_incidente'),
            'descripcion' => $this->request->getVar('descripcion'),
            'id_creador' => $idCreador, // Obtiene el ID del creador desde la sesión
        ];

        $db = \Config\Database::connect();
        $db->transStart(); // Inicia una transacción

        // Guardar o actualizar la denuncia
        if ($id) {
            $denunciaModel->update($id, $data);
            registrarAccion($idCreador, 'Actualización de denuncia', 'ID: ' . $id);
        } else {
            $denunciaModel->save($data);
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
                $anexoModel->save([
                    'id_denuncia' => $id,
                    'nombre_archivo' => $nombreArchivo,
                    'ruta_archivo' => $rutaArchivo,
                    'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                ]);
            }
        }

        $db->transComplete(); // Finaliza la transacción

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al guardar la denuncia y los archivos adjuntos']);
        }

        return $this->response->setJSON(['message' => 'Denuncia guardada correctamente']);
    }


    public function eliminar($id)
    {
        $denunciaModel = new DenunciaModel();
        $denunciaModel->deleteDenuncia($id);

        registrarAccion(session()->get('id'), 'Eliminación de denuncia', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente']);
    }

    public function cambiarEstado()
    {
        $denunciaModel = new DenunciaModel();
        $id = $this->request->getVar('id');
        $estado_nuevo = $this->request->getVar('estado_nuevo');

        $denunciaModel->cambiarEstado($id, $estado_nuevo);

        $seguimientoModel = new SeguimientoDenunciaModel();
        $seguimientoModel->save([
            'id_denuncia' => $id,
            'estado_anterior' => $this->request->getVar('estado_anterior'),
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
}
