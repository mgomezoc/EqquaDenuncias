<?php

namespace App\Controllers;

use App\Models\SucursalModel;
use App\Models\ClienteModel;
use App\Models\ImagenSucursalModel;
use CodeIgniter\Controller;

class SucursalesController extends Controller
{
    protected $sucursalModel;
    protected $clienteModel;
    protected $imagenSucursalModel;

    public function __construct()
    {
        $this->sucursalModel = new SucursalModel();
        $this->clienteModel = new ClienteModel();
        $this->imagenSucursalModel = new ImagenSucursalModel();
    }

    public function index()
    {
        $clientes = $this->clienteModel->findAll();

        $data = [
            'title' => 'Administración de Sucursales',
            'controlador' => 'Sucursales',
            'vista' => 'Sucursales',
            'clientes' => $clientes
        ];

        return view('sucursales/index', $data);
    }

    public function listar()
    {
        $sucursales = $this->sucursalModel->select('sucursales.*, clientes.nombre_empresa AS cliente_nombre')
            ->join('clientes', 'clientes.id = sucursales.id_cliente', 'left')
            ->findAll();

        return $this->response->setJSON($sucursales);
    }

    public function guardar()
    {
        $id = $this->request->getVar('id');

        $data = [
            'id_cliente' => $this->request->getVar('id_cliente'),
            'nombre' => $this->request->getVar('nombre'),
            'direccion' => $this->request->getVar('direccion'),
        ];

        if ($id) {
            $this->sucursalModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de sucursal', 'ID: ' . $id);
        } else {
            $this->sucursalModel->save($data);
            $newId = $this->sucursalModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de sucursal', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Sucursal guardada correctamente']);
    }

    public function obtener($id)
    {
        $sucursal = $this->sucursalModel->find($id);

        registrarAccion(session()->get('id'), 'Visualización de sucursal', 'ID: ' . $id);

        return $this->response->setJSON($sucursal);
    }

    public function eliminar($id)
    {
        $this->sucursalModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de sucursal', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Sucursal eliminada correctamente']);
    }

    /**
     * Subir imagen de sucursal
     */
    /**
     * Subir imagen de sucursal
     */
    public function subirImagen()
    {
        $file = $this->request->getFile('file');
        $id_sucursal = $this->request->getPost('id_sucursal');

        // Verificar si el archivo es válido y no ha sido movido
        if (!$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'El archivo es inválido o ya ha sido procesado.']);
        }

        // Validar el tipo MIME del archivo
        $mimeType = $file->getMimeType();
        $mimeAllowed = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($mimeType, $mimeAllowed)) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'El archivo debe ser una imagen válida (JPEG, PNG o GIF).']);
        }

        // Validar el tamaño del archivo (máximo 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'El archivo no debe exceder los 2MB.']);
        }

        // Crear una ruta única para almacenar el archivo
        $nombreArchivo = $file->getRandomName();
        $rutaArchivo = WRITEPATH . '../public/assets/images/sucursales/' . $id_sucursal;

        // Asegurarse de que la carpeta existe
        if (!is_dir($rutaArchivo)) {
            mkdir($rutaArchivo, 0777, true);
        }

        // Mover el archivo a su ubicación final
        if (!$file->move($rutaArchivo, $nombreArchivo)) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Error al guardar el archivo.']);
        }

        // Registrar la imagen en la base de datos
        $idImagen = $this->imagenSucursalModel->guardarImagen([
            'id_sucursal' => $id_sucursal,
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo' => "assets/images/sucursales/$id_sucursal/$nombreArchivo",
            'tipo' => $mimeType,
        ]);

        registrarAccion(session()->get('id'), 'Subida de imagen', "Sucursal ID: $id_sucursal, Archivo: $nombreArchivo");

        // Respuesta al cliente
        return $this->response->setJSON([
            'id' => $idImagen,
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo' => base_url("assets/images/sucursales/$id_sucursal/$nombreArchivo"),
            'message' => 'Imagen subida correctamente',
        ]);
    }


    /**
     * Listar imágenes de una sucursal
     */
    public function listarImagenes($id_sucursal)
    {
        $imagenes = $this->imagenSucursalModel->obtenerImagenes($id_sucursal);

        return $this->response->setJSON($imagenes);
    }

    /**
     * Eliminar imagen de sucursal
     */
    public function eliminarImagen()
    {
        $id = $this->request->getPost('id'); // Obtener el ID de la imagen desde la solicitud

        // Buscar la imagen en la base de datos
        $imagen = $this->imagenSucursalModel->find($id);

        if (!$imagen) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Imagen no encontrada.']);
        }

        // Eliminar archivo físico si existe
        $rutaArchivo = WRITEPATH . '../public/' . $imagen['ruta_archivo'];
        if (file_exists($rutaArchivo)) {
            if (!unlink($rutaArchivo)) {
                return $this->response->setStatusCode(500)->setJSON(['message' => 'Error al eliminar el archivo del servidor.']);
            }
        }

        // Eliminar referencia de la base de datos
        if (!$this->imagenSucursalModel->eliminarImagen($id)) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Error al eliminar el registro de la imagen en la base de datos.']);
        }

        registrarAccion(session()->get('id'), 'Eliminación de imagen', "Imagen ID: $id, Archivo: {$imagen['nombre_archivo']}");

        return $this->response->setJSON(['message' => 'Imagen eliminada correctamente.']);
    }
}
