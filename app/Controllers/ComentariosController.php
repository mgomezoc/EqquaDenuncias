<?php

namespace App\Controllers;

use App\Models\ComentarioDenunciaModel;
use App\Models\DenunciaModel;

class ComentariosController extends BaseController
{
    public function guardar()
    {
        $comentarioModel = new ComentarioDenunciaModel();
        $denunciaModel = new DenunciaModel();

        $id_denuncia = $this->request->getVar('id_denuncia');
        $contenido = $this->request->getVar('contenido');

        // Obtener el estado actual de la denuncia
        $denuncia = $denunciaModel->find($id_denuncia);
        if (!$denuncia) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Denuncia no encontrada']);
        }

        $estado_denuncia = $denuncia['estado_actual'];

        $id_usuario = session()->get('id') ?? 18;
        // Guardar el comentario con el estado de la denuncia
        $data = [
            'id_denuncia' => $id_denuncia,
            'id_usuario' => $id_usuario,  // ID del usuario que hace el comentario
            'contenido' => $contenido,
            'estado_denuncia' => $estado_denuncia,
        ];

        if ($comentarioModel->save($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Comentario guardado correctamente'
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al guardar el comentario'
            ]);
        }
    }

    public function listar($id_denuncia)
    {
        $comentarioModel = new ComentarioDenunciaModel();

        // Obtener los comentarios relacionados con la denuncia
        $comentarios = $comentarioModel->getComentariosByDenuncia($id_denuncia);

        return $this->response->setJSON($comentarios);
    }
}
