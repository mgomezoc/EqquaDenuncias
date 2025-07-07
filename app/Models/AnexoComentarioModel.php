<?php

namespace App\Models;

use CodeIgniter\Model;

class AnexoComentarioModel extends Model
{
    protected $table            = 'anexos_comentarios_denuncias';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'id_comentario',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_mime',
        'visible_para_cliente',
        'fecha_subida',
    ];

    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    // Puedes agregar reglas de validación si luego quieres usar save() directamente
    protected $validationRules  = [
        'id_comentario' => 'required|integer',
        'nombre_archivo' => 'required|string|max_length[255]',
        'ruta_archivo' => 'required|string|max_length[255]',
    ];
}
