<?php

namespace App\Models;

use CodeIgniter\Model;

class SubcategoriasModel extends Model
{
    protected $table = 'subcategorias_denuncias';
    protected $primaryKey = 'id';

    // Campos que se pueden insertar o actualizar en la base de datos
    protected $allowedFields = ['nombre', 'id_categoria'];

    // Si tienes habilitadas las marcas de tiempo en la tabla
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at'; // Si usas soft deletes

    // Reglas de validación para los campos
    protected $validationRules = [
        'nombre'      => 'required|min_length[3]|max_length[255]',
        'id_categoria' => 'required|integer|is_not_unique[categorias_denuncias.id]', // Asegura que la categoría exista
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre de la subcategoría es obligatorio.',
            'min_length' => 'El nombre debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre no puede exceder los 255 caracteres.'
        ],
        'id_categoria' => [
            'required'     => 'La categoría es obligatoria.',
            'integer'      => 'El ID de la categoría debe ser un número entero.',
            'is_not_unique' => 'La categoría seleccionada no existe.'
        ]
    ];

    // Habilitar eliminación lógica (soft deletes)
    protected $useSoftDeletes = true;

    // Establece si el modelo debería realizar validaciones automáticamente
    protected $skipValidation = false;
}
