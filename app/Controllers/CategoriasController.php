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
        $id_categoria = $this->request->getVar('id_categoria');

        // Verifica que se haya pasado la categoría
        if ($id_categoria) {
            $subcategoriaModel = new SubcategoriaDenunciaModel();

            // Filtra las subcategorías por el id_categoria
            $subcategorias = $subcategoriaModel->where('id_categoria', $id_categoria)->findAll();

            return $this->response->setJSON($subcategorias);
        } else {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Categoría no especificada']);
        }
    }

    public function guardarCategoria()
    {
        $categoriaModel = new CategoriaDenunciaModel();
        $id = $this->request->getVar('id');

        $data = ['nombre' => $this->request->getVar('nombre')];

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

        // Eliminar subcategorías asociadas primero
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

    public function listarCategoriasYSubcategorias()
    {
        $categoriaModel = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        $categorias = $categoriaModel->findAll();
        foreach ($categorias as &$categoria) {
            $subcategorias = $subcategoriaModel->where('id_categoria', $categoria['id'])->findAll();
            $categoria['subcategorias'] = $subcategorias;
            $categoria['subcategorias_total'] = count($subcategorias);  // Contar subcategorías
        }

        return $this->response->setJSON($categorias);
    }
}
