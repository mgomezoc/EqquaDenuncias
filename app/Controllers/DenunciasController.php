<?php

namespace App\Controllers;

use App\Models\DenunciaModel;
use App\Models\ClienteModel;
use App\Models\EstadoDenunciaModel;
use App\Models\CategoriaDenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use App\Models\AnexoDenunciaModel;
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
            'subcategorias' => $subcategorias, // Añadir subcategorías aquí
        ];

        return view('denuncias/index', $data);
    }

    public function listar()
    {
        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->select('denuncias.*, clientes.nombre_empresa AS cliente_nombre, estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->findAll();

        return $this->response->setJSON($denuncias);
    }

    public function detalle($id)
    {
        $denunciaModel = new DenunciaModel();
        $denuncia = $denunciaModel->find($id);

        return $this->response->setJSON($denuncia);
    }

    public function guardar()
    {
        $denunciaModel = new DenunciaModel();
        $id = $this->request->getVar('id');

        $data = [
            'id_cliente' => $this->request->getVar('id_cliente'),
            'folio' => $this->request->getVar('folio'),
            'fecha_hora_reporte' => $this->request->getVar('fecha_hora_reporte'),
            'id_sucursal' => $this->request->getVar('id_sucursal'),
            'tipo_denunciante' => $this->request->getVar('tipo_denunciante'),
            'categoria' => $this->request->getVar('categoria'),
            'subcategoria' => $this->request->getVar('subcategoria'),
            'departamento' => $this->request->getVar('departamento'),
            'anonimo' => $this->request->getVar('anonimo'),
            'fecha_incidente' => $this->request->getVar('fecha_incidente'),
            'como_se_entero' => $this->request->getVar('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getVar('denunciar_a_alguien'),
            'area_incidente' => $this->request->getVar('area_incidente'),
            'descripcion' => $this->request->getVar('descripcion'),
            'estado_actual' => $this->request->getVar('estado_actual'),
            'id_creador' => session()->get('id'),
        ];

        if ($id) {
            $denunciaModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de denuncia', 'ID: ' . $id);
        } else {
            $denunciaModel->save($data);
            $newId = $denunciaModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de denuncia', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Denuncia guardada correctamente']);
    }

    public function eliminar($id)
    {
        $denunciaModel = new DenunciaModel();
        $denunciaModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de denuncia', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente']);
    }

    public function cambiarEstado()
    {
        $denunciaModel = new DenunciaModel();
        $id = $this->request->getVar('id');
        $estado_nuevo = $this->request->getVar('estado_nuevo');

        $denunciaModel->update($id, ['estado_actual' => $estado_nuevo]);

        // Registrar en seguimiento
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
}
