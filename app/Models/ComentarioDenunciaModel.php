<?php

namespace App\Models;

use CodeIgniter\Model;

class ComentarioDenunciaModel extends Model
{
    protected $table = 'comentarios_denuncias';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_denuncia', 'id_usuario', 'contenido', 'estado_denuncia', 'fecha_comentario'];

    public function getComentariosByDenuncia($id_denuncia)
    {
        return $this->select('comentarios_denuncias.*, usuarios.nombre_usuario, estados_denuncias.nombre AS estado_nombre')
            ->join('usuarios', 'usuarios.id = comentarios_denuncias.id_usuario')
            ->join('estados_denuncias', 'estados_denuncias.id = comentarios_denuncias.estado_denuncia')
            ->where('id_denuncia', $id_denuncia)
            ->orderBy('fecha_comentario', 'DESC')
            ->findAll();
    }

    public function getComentariosVisiblesParaCliente($id_denuncia)
    {
        $builder = $this->db->table('comentarios_denuncias cd');
        $builder->select('cd.*, u.nombre_usuario, ed.nombre AS estado_nombre');
        $builder->join('usuarios u', 'cd.id_usuario = u.id', 'left');
        $builder->join('estados_denuncias ed', 'cd.estado_denuncia = ed.id', 'left');
        $builder->where('cd.id_denuncia', $id_denuncia);
        $builder->whereIn('cd.estado_denuncia', [4, 5, 6]);
        $builder->orderBy('cd.fecha_comentario', 'ASC');

        $comentarios = $builder->get()->getResultArray();

        // Cargar anexos para cada comentario
        $anexoModel = new \App\Models\AnexoComentarioModel();
        foreach ($comentarios as &$comentario) {
            $comentario['archivos'] = $anexoModel
                ->where('id_comentario', $comentario['id'])
                ->where('visible_para_cliente', 1)
                ->findAll();
        }

        return $comentarios;
    }
}
