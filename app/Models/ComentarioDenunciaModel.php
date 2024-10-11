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
        // Definimos los estados que el cliente puede ver
        $estadosVisibles = [4, 5, 6];

        return $this->select('comentarios_denuncias.*, usuarios.nombre_usuario, estados_denuncias.nombre AS estado_nombre')
            ->join('usuarios', 'usuarios.id = comentarios_denuncias.id_usuario')
            ->join('estados_denuncias', 'estados_denuncias.id = comentarios_denuncias.estado_denuncia') // Corregido aquí
            ->where('comentarios_denuncias.id_denuncia', $id_denuncia)
            ->whereIn('comentarios_denuncias.estado_denuncia', $estadosVisibles) // Filtrar por estados 4, 5 y 6
            ->orderBy('comentarios_denuncias.fecha_comentario', 'DESC')
            ->findAll();
    }
}
