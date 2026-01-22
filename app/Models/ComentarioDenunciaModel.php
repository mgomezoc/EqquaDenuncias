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
        $builder = $this->db->table('comentarios_denuncias cd');
        $builder->select('cd.*, u.nombre_usuario, ed.nombre AS estado_nombre, d.anonimo, d.nombre_completo AS nombre_denunciante');
        $builder->join('usuarios u', 'u.id = cd.id_usuario', 'left');
        $builder->join('estados_denuncias ed', 'ed.id = cd.estado_denuncia', 'left');
        $builder->join('denuncias d', 'd.id = cd.id_denuncia', 'left');
        $builder->where('cd.id_denuncia', $id_denuncia);
        $builder->orderBy('cd.fecha_comentario', 'DESC');
        
        $comentarios = $builder->get()->getResultArray();
        
        // Ajustar nombre para comentarios de denunciantes
        foreach ($comentarios as &$comentario) {
            // Si el comentario es del usuario "Anónimo" (id=18), verificar si la denuncia NO es anónima
            if ($comentario['id_usuario'] == 18) {
                if ($comentario['anonimo'] == 0 && !empty($comentario['nombre_denunciante'])) {
                    // La denuncia NO es anónima, mostrar el nombre real del denunciante
                    $comentario['nombre_usuario'] = $comentario['nombre_denunciante'];
                } else {
                    // La denuncia SÍ es anónima, mantener "Anónimo"
                    $comentario['nombre_usuario'] = 'Anónimo';
                }
            }
        }
        
        return $comentarios;
    }

    public function getComentariosVisiblesParaCliente($id_denuncia)
    {
        $builder = $this->db->table('comentarios_denuncias cd');
        $builder->select('cd.*, u.nombre_usuario, ed.nombre AS estado_nombre, d.anonimo, d.nombre_completo AS nombre_denunciante');
        $builder->join('usuarios u', 'cd.id_usuario = u.id', 'left');
        $builder->join('estados_denuncias ed', 'cd.estado_denuncia = ed.id', 'left');
        $builder->join('denuncias d', 'd.id = cd.id_denuncia', 'left');
        $builder->where('cd.id_denuncia', $id_denuncia);
        $builder->whereIn('cd.estado_denuncia', [4, 5, 6]);
        $builder->orderBy('cd.fecha_comentario', 'ASC');

        $comentarios = $builder->get()->getResultArray();

        // Cargar anexos para cada comentario y ajustar nombre para comentarios de denunciantes
        $anexoModel = new \App\Models\AnexoComentarioModel();
        foreach ($comentarios as &$comentario) {
            // Si el comentario es del usuario "Anónimo" (id=18), verificar si la denuncia NO es anónima
            if ($comentario['id_usuario'] == 18) {
                if ($comentario['anonimo'] == 0 && !empty($comentario['nombre_denunciante'])) {
                    // La denuncia NO es anónima, mostrar el nombre real del denunciante
                    $comentario['nombre_usuario'] = $comentario['nombre_denunciante'];
                } else {
                    // La denuncia SÍ es anónima, mantener "Anónimo"
                    $comentario['nombre_usuario'] = 'Anónimo';
                }
            }
            
            $comentario['archivos'] = $anexoModel
                ->where('id_comentario', $comentario['id'])
                ->where('visible_para_cliente', 1)
                ->findAll();
        }

        return $comentarios;
    }
}
