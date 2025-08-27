<?php

namespace App\Controllers;

use App\Models\AnexoDenunciaModel;
use App\Models\DenunciaModel;
use App\Models\ClienteModel;
use App\Models\ComentarioDenunciaModel;
use App\Models\SucursalModel;
use App\Models\DepartamentoModel;
use App\Models\UsuarioModel;
use App\Models\EstadoDenunciaModel;
use App\Models\SeguimientoDenunciaModel;
use Config\Database;
use CodeIgniter\Controller;

class ReportesController extends Controller
{
    protected $denunciaModel;
    protected $anexoDenunciaModel;
    protected $comentarioDenunciaModel;
    protected $seguimientoDenunciaModel;
    protected $sucursalModel;
    protected $db;

    public function __construct()
    {
        $this->denunciaModel = new DenunciaModel();
        $this->anexoDenunciaModel = new AnexoDenunciaModel();
        $this->comentarioDenunciaModel = new ComentarioDenunciaModel();
        $this->seguimientoDenunciaModel = new SeguimientoDenunciaModel();
        $this->sucursalModel = new SucursalModel();
        $this->db = Database::connect();
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

        // Trae hasta 1000 (ajústalo si necesitas más)
        $result = $this->denunciaModel->filtrarDenuncias(1000, 0, $filters);

        // Models para comentarios y seguimiento
        $comentarioModel  = new \App\Models\ComentarioDenunciaModel();
        $seguimientoModel = new \App\Models\SeguimientoDenunciaModel();

        // Encabezados amigables (se agregan 2 nuevas columnas)
        $columnMap = [
            'folio'                  => 'Folio',
            'cliente_nombre'         => 'Cliente',
            'sucursal_nombre'        => 'Sucursal',
            'departamento_nombre'    => 'Departamento',
            'estado_nombre'          => 'Estado',
            'creador_nombre'         => 'Creador',
            'fecha_hora_reporte'     => 'Fecha Reporte',
            'medio_recepcion'        => 'Medio de Recepción',
            'descripcion'            => 'Descripción',
            'tipo_denunciante'       => 'Tipo de Denunciante',
            'anonimo'                => 'Denuncia Anónima',
            'nombre_completo'        => 'Nombre Completo',
            'correo_electronico'     => 'Correo Electrónico',
            'telefono'               => 'Teléfono',
            'fecha_incidente'        => 'Fecha del Incidente',
            'como_se_entero'         => '¿Cómo se enteró?',
            'denunciar_a_alguien'    => '¿Denuncia a Alguien?',
            'area_incidente'         => 'Área del Incidente',
            'estado_actual'          => 'Estado Actual',
            'created_at'             => 'Fecha de Creación',
            'updated_at'             => 'Fecha de Actualización',
            // nuevas columnas:
            'comentarios_csv'        => 'Comentarios',
            'seguimiento_csv'        => 'Seguimiento',
        ];

        $filename = 'reporte_denuncias_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename=' . $filename);

        $output = fopen('php://output', 'w');
        // BOM UTF-8
        fputs($output, "\xEF\xBB\xBF");

        // Encabezados
        fputcsv($output, array_values($columnMap));

        foreach ($result['rows'] as $denuncia) {
            $idDenuncia = $denuncia['id'] ?? null;

            // ---- Comentarios
            $comentarios = [];
            if ($idDenuncia) {
                $rowsComentarios = $comentarioModel->getComentariosByDenuncia($idDenuncia);
                foreach ($rowsComentarios as $c) {
                    // Formato: 2025-07-28 12:27 | Estado | Usuario: Contenido
                    $fecha   = $c['fecha_comentario'] ?? '';
                    $estado  = $c['estado_nombre'] ?? '';
                    $usuario = $c['nombre_usuario'] ?? '';
                    $texto   = $c['contenido'] ?? '';
                    $comentarios[] = $this->sanitizeCsvCell("{$fecha} | {$estado} | {$usuario}: {$texto}");
                }
            }
            $comentariosCsv = implode(' || ', $comentarios); // separador entre items

            // ---- Seguimiento
            $seguimientos = [];
            if ($idDenuncia) {
                $rowsSeg = $seguimientoModel->getSeguimientoByDenunciaId($idDenuncia);
                foreach ($rowsSeg as $s) {
                    // Formato: 2025-07-28 12:27 | De: X -> A: Y | Comentario | Por: Usuario
                    $fecha = $s['fecha'] ?? '';
                    $de    = $s['estado_anterior_nombre'] ?? '';
                    $a     = $s['estado_nuevo_nombre'] ?? '';
                    $coment = $s['comentario'] ?? '';
                    $por   = $s['usuario_nombre'] ?? '';
                    $seguimientos[] = $this->sanitizeCsvCell("{$fecha} | De: {$de} -> A: {$a} | {$coment} | Por: {$por}");
                }
            }
            $seguimientoCsv = implode(' || ', $seguimientos);

            // Armar fila respetando el orden de $columnMap
            $row = [];
            foreach (array_keys($columnMap) as $key) {
                if ($key === 'comentarios_csv') {
                    $row[] = $comentariosCsv;
                } elseif ($key === 'seguimiento_csv') {
                    $row[] = $seguimientoCsv;
                } else {
                    $row[] = isset($denuncia[$key]) ? $this->sanitizeCsvCell($denuncia[$key]) : '';
                }
            }

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Normaliza el contenido para una celda CSV.
     * - El CSV acepta comas y barras verticales si el campo va entre comillas (fputcsv lo hace).
     * - Quitamos saltos de línea y tabs que pueden romper visualización en Excel.
     * - Opcional: sustituimos pipes internos si prefieres que no aparezcan.
     */
    private function sanitizeCsvCell($value)
    {
        $str = (string) $value;
        // Reemplaza saltos de línea/tabs por espacio
        $str = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $str);
        // Compacta espacios múltiples
        $str = preg_replace('/\s{2,}/', ' ', $str);
        // Si quieres evitar pipes internos, descomenta la siguiente línea:
        // $str = str_replace('|', '¦', $str);
        return trim($str);
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

        // Definir los estados permitidos para el cliente (4, 5, 6)
        $estadosPermitidos = [4, 5, 6];

        // Obtener solo los estados permitidos para los filtros
        $estados = $estadoModel->whereIn('id', $estadosPermitidos)->findAll();

        // Mapeo de los nombres de los estados a nombres amigables para el cliente
        $estadosAmigables = [];
        foreach ($estados as $estado) {
            switch ($estado['id']) {
                case 4:
                    $estado['nombre'] = 'Nueva'; // Estado 4 mapeado a "Nueva"
                    break;
                case 5:
                    $estado['nombre'] = 'En Revisión'; // Estado 5 mapeado a "En Revisión"
                    break;
                case 6:
                    $estado['nombre'] = 'Cerrada'; // Estado 6 mantiene el mismo nombre
                    break;
            }
            $estadosAmigables[] = $estado;
        }

        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->obtenerSucursalesPorCliente($clienteId);

        $data = [
            'clienteId' => $clienteId,
            'estados' => $estadosAmigables,
            'sucursales' => $sucursales,
            'title' => 'Reporte de Denuncias'
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

    /**
     * Eliminar una denuncia, sus anexos, comentarios y seguimiento
     */
    public function eliminarDenuncia($id)
    {
        // Iniciar transacción para asegurar la consistencia de los datos
        $this->db->transBegin();

        // Eliminar comentarios de la denuncia
        $this->comentarioDenunciaModel->where('id_denuncia', $id)->delete();

        // Eliminar seguimientos de la denuncia
        $this->seguimientoDenunciaModel->deleteSeguimientoByDenunciaId($id);

        // Obtener los anexos relacionados a la denuncia para eliminarlos físicamente
        $anexos = $this->anexoDenunciaModel->getAnexosByDenunciaId($id);
        foreach ($anexos as $anexo) {
            $rutaArchivo = WRITEPATH . '../public/' . $anexo['ruta_archivo'];
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo); // Eliminar el archivo físicamente
            }
        }

        // Eliminar anexos de la denuncia
        $this->anexoDenunciaModel->deleteAnexosByDenunciaId($id);

        // Eliminar la denuncia
        $this->denunciaModel->delete($id);

        // Verificar si la transacción fue exitosa
        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Error al eliminar la denuncia.']);
        }

        $this->db->transCommit();
        return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente.']);
    }
}
