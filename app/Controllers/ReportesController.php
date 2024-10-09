<?php

namespace App\Controllers;

use App\Models\DenunciaModel;
use App\Models\ClienteModel;
use App\Models\SucursalModel;
use App\Models\DepartamentoModel;
use App\Models\UsuarioModel;
use App\Models\EstadoDenunciaModel;
use CodeIgniter\Controller;

class ReportesController extends Controller
{
    protected $denunciaModel;

    public function __construct()
    {
        $this->denunciaModel = new DenunciaModel();
    }

    /**
     * Cargar la vista principal de reportes.
     */
    public function index()
    {
        $clienteModel = new ClienteModel();
        $estadoModel = new EstadoDenunciaModel();
        $usuarioModel = new UsuarioModel();

        // Obtener los clientes, estados y usuarios para los filtros
        $clientes = $clienteModel->findAll();
        $estados = $estadoModel->findAll();
        $usuarios = $usuarioModel->findAll();

        $data = [
            'clientes' => $clientes,
            'estados' => $estados,
            'usuarios' => $usuarios,
            'title' => 'Reporte de Denuncias'
        ];

        return view('reportes/index', $data);
    }

    /**
     * Listar las denuncias con paginación y filtrado.
     */
    public function listar()
    {
        $postData = json_decode($this->request->getBody(), true);

        $limit = $postData['limit'] ?? 10;
        $offset = $postData['offset'] ?? 0;
        $sort = $postData['sort'] ?? '';
        $order = $postData['order'] ?? 'asc';

        $filters = [
            'search' => $postData['search'] ?? '',
            'fecha_inicio' => $postData['fecha_inicio'] ?? '',
            'fecha_fin' => $postData['fecha_fin'] ?? '',
            'id_cliente' => $postData['id_cliente'] ?? '',
            'id_sucursal' => $postData['id_sucursal'] ?? '',
            'id_departamento' => $postData['id_departamento'] ?? '',
            'medio_recepcion' => $postData['medio_recepcion'] ?? '',
            'estado_actual' => $postData['estado_actual'] ?? '',
            'id_creador' => $postData['id_creador'] ?? '',
        ];

        $result = $this->denunciaModel->filtrarDenuncias($limit, $offset, $filters, $sort, $order);

        return $this->response->setJSON($result);
    }


    /**
     * Exportar denuncias a CSV.
     */
    public function exportarCSV()
    {
        $filters = $this->request->getPost();
        $result = $this->denunciaModel->filtrarDenuncias(1000, 0, $filters); // Llama a filtrarDenuncias con parámetros de paginación grandes

        // Definir los títulos de las columnas de forma amigable
        $columnMap = [
            'folio' => 'Folio',
            'cliente_nombre' => 'Cliente',
            'sucursal_nombre' => 'Sucursal',
            'departamento_nombre' => 'Departamento',
            'estado_nombre' => 'Estado',
            'creador_nombre' => 'Creador',
            'fecha_hora_reporte' => 'Fecha Reporte',
            'medio_recepcion' => 'Medio de Recepción',
            'descripcion' => 'Descripción',
            'tipo_denunciante' => 'Tipo de Denunciante',
            'anonimo' => 'Denuncia Anónima',
            'nombre_completo' => 'Nombre Completo',
            'correo_electronico' => 'Correo Electrónico',
            'telefono' => 'Teléfono',
            'fecha_incidente' => 'Fecha del Incidente',
            'como_se_entero' => '¿Cómo se enteró?',
            'denunciar_a_alguien' => '¿Denuncia a Alguien?',
            'area_incidente' => 'Área del Incidente',
            'estado_actual' => 'Estado Actual',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Fecha de Actualización'
        ];

        // Crear contenido CSV
        $filename = 'reporte_denuncias_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename=' . $filename);

        // Añadir el BOM UTF-8
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // Esto añade el BOM

        // Escribir los encabezados basados en el mapeo
        fputcsv($output, array_values($columnMap));

        // Escribir los datos de las denuncias
        foreach ($result['rows'] as $denuncia) {
            $row = [];
            foreach (array_keys($columnMap) as $key) {
                $row[] = $denuncia[$key] ?? ''; // Usar un valor vacío si la clave no existe
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function cliente()
    {
        $estadoModel = new EstadoDenunciaModel();
        $usuarioId = session()->get('usuario_id');
        $clienteId = session()->get('id_cliente');

        // Validar que el usuario autenticado pertenece a un cliente
        if (empty($clienteId)) {
            return redirect()->to('/noautorizado');
        }

        // Obtener los estados para los filtros
        $estados = $estadoModel->findAll();

        $data = [
            'clienteId' => $clienteId,
            'estados' => $estados,
            'title' => 'Reporte de Denuncias para Cliente'
        ];

        return view('reportes/cliente', $data);
    }

    public function listarParaCliente()
    {
        $clienteId = session()->get('id_cliente'); // Obtener el ID del cliente desde la sesión
        $postData = json_decode($this->request->getBody(), true);

        $limit = $postData['limit'] ?? 10;
        $offset = $postData['offset'] ?? 0;
        $sort = $postData['sort'] ?? '';
        $order = $postData['order'] ?? 'asc';

        $filters = [
            'search' => $postData['search'] ?? '',
            'fecha_inicio' => $postData['fecha_inicio'] ?? '',
            'fecha_fin' => $postData['fecha_fin'] ?? '',
            'id_sucursal' => $postData['id_sucursal'] ?? '',
            'id_departamento' => $postData['id_departamento'] ?? '',
            'medio_recepcion' => $postData['medio_recepcion'] ?? '',
            'estado_actual' => $postData['estado_actual'] ?? '',
            'id_creador' => $postData['id_creador'] ?? '',
        ];

        $result = $this->denunciaModel->filtrarDenunciasParaCliente($clienteId, $limit, $offset, $filters, $sort, $order);

        return $this->response->setJSON($result);
    }
}
