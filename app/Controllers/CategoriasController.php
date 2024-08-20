<?php

namespace App\Controllers;

use App\Models\CategoriaDenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use CodeIgniter\Controller;

class CategoriasController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Administración de Categorías',
            'controlador' => 'Categorías',
            'vista' => 'Categorías',
        ];

        return view('categorias/index', $data);
    }

    public function listarCategorias()
    {
        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        return $this->response->setJSON($categorias);
    }

    public function listarSubcategorias()
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->getSubcategorias();

        return $this->response->setJSON($subcategorias);
    }

    public function guardarCategoria()
    {
        $categoriaModel = new CategoriaDenunciaModel();
        $id = $this->request->getVar('id');

        $data = [
            'nombre' => $this->request->getVar('nombre')
        ];

        if ($id) {
            $categoriaModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de categoría', 'ID: ' . $id);
        } else {
            $categoriaModel->save($data);
            $newId = $categoriaModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de categoría', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Categoría guardada correctamente']);
    }

    public function guardarSubcategoria()
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $id = $this->request->getVar('id');

        $data = [
            'nombre' => $this->request->getVar('nombre'),
            'id_categoria' => $this->request->getVar('id_categoria')
        ];

        if ($id) {
            $subcategoriaModel->update($id, $data);
            registrarAccion(session()->get('id'), 'Actualización de subcategoría', 'ID: ' . $id);
        } else {
            $subcategoriaModel->save($data);
            $newId = $subcategoriaModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de subcategoría', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Subcategoría guardada correctamente']);
    }

    public function eliminarCategoria($id)
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        // Primero, eliminar las subcategorías asociadas
        $subcategoriaModel->where('id_categoria', $id)->delete();

        // Luego, eliminar la categoría
        $categoriaModel = new CategoriaDenunciaModel();
        $categoriaModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de categoría y sus subcategorías', 'ID: ' . $id);

        return $this->response->setJSON([
            'success' => 'Categoría y sus subcategorías eliminadas correctamente.'
        ]);
    }

    public function eliminarSubcategoria($id)
    {
        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategoriaModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de subcategoría', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Subcategoría eliminada correctamente']);
    }
}
