<?php

namespace App\Models;

use CodeIgniter\Model;

class ImagenSucursalModel extends Model
{
    protected $table = 'imagenes_sucursales'; // Tabla asociada
    protected $primaryKey = 'id'; // Clave primaria
    protected $allowedFields = ['id_sucursal', 'nombre_archivo', 'ruta_archivo', 'tipo', 'created_at']; // Campos permitidos para operaciones

    /**
     * Guardar una imagen asociada a una sucursal
     *
     * @param array $data Datos de la imagen
     * @return bool|int
     */
    public function guardarImagen(array $data)
    {
        return $this->insert($data); // Inserta y devuelve el ID
    }

    /**
     * Obtener imágenes asociadas a una sucursal
     *
     * @param int $id_sucursal ID de la sucursal
     * @return array
     */
    public function obtenerImagenes($id_sucursal)
    {
        return $this->where('id_sucursal', $id_sucursal)->findAll(); // Filtra imágenes por sucursal
    }

    /**
     * Eliminar una imagen específica
     *
     * @param int $id ID de la imagen
     * @return bool
     */
    public function eliminarImagen($id)
    {
        return $this->delete($id); // Elimina la imagen por su ID
    }
}
