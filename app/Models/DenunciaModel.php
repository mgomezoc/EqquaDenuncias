<?php

namespace App\Models;

use CodeIgniter\Model;

class DenunciaModel extends Model
{
    protected $table            = 'denuncias';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'id_cliente',
        'folio',
        'fecha_hora_reporte',
        'id_sucursal',
        'tipo_denunciante',
        'categoria',
        'subcategoria',
        'id_departamento',
        'anonimo',
        'fecha_incidente',
        'como_se_entero',
        'denunciar_a_alguien',
        'area_incidente',
        'descripcion',
        'medio_recepcion', // Nueva columna añadida aquí
        'estado_actual',
        'id_creador',
        'visible_para_agente',
        'visible_para_calidad',
        'visible_para_cliente'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $beforeInsert     = ['generateFolio', 'setDefaultValues'];
    protected $beforeUpdate     = ['setUpdateTimestamp'];

    protected function generateFolio(array $data): array
    {
        // Obtener la fecha en formato "ymd" (240901)
        $date = date('ymd'); // 'y' proporciona los últimos dos dígitos del año

        // Buscar la última denuncia que tenga un folio que empiece con la fecha actual
        $lastDenuncia = $this->select('folio')
            ->like('folio', "$date-", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        // Determinar el nuevo número de secuencia
        $newFolio = $lastDenuncia ? (int)substr($lastDenuncia['folio'], -4) + 1 : 1;

        // Formatear el número de secuencia a 4 dígitos (e.g., 0003)
        $data['data']['folio'] = "$date-" . str_pad($newFolio, 4, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function setDefaultValues(array $data): array
    {
        $data['data']['fecha_hora_reporte'] = date('Y-m-d H:i:s');
        $data['data']['estado_actual'] = 1; // Estado inicial 'Recepción'
        return $data;
    }

    protected function setUpdateTimestamp(array $data): array
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function getDenuncias(): array
    {
        return $this->select('denuncias.*, 
                              clientes.nombre_empresa AS cliente_nombre, 
                              sucursales.nombre AS sucursal_nombre, 
                              categorias_denuncias.nombre AS categoria_nombre, 
                              subcategorias_denuncias.nombre AS subcategoria_nombre, 
                              departamentos.nombre AS departamento_nombre, 
                              estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function getDenunciaById(int $id): ?array
    {
        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre,
                          sucursales.nombre AS sucursal_nombre,
                          categorias_denuncias.nombre AS categoria_nombre,
                          subcategorias_denuncias.nombre AS subcategoria_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->where('denuncias.id', $id)
            ->first();
    }

    public function cambiarEstado(int $id, int $estadoNuevo): bool
    {
        return $this->update($id, ['estado_actual' => $estadoNuevo]);
    }

    public function getDenunciasByCliente($clienteId)
    {
        return $this->select('denuncias.*, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->where('denuncias.id_cliente', $clienteId)
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function eliminarDenuncia(int $id): bool
    {
        // Iniciar una transacción para asegurar la consistencia de los datos
        $this->db->transStart();

        try {
            // Obtener los anexos relacionados con la denuncia
            $anexosModel = new \App\Models\AnexoDenunciaModel();
            $anexos = $anexosModel->where('id_denuncia', $id)->findAll();

            // Intentar eliminar los archivos anexos del sistema de archivos
            foreach ($anexos as $anexo) {
                $rutaArchivo = WRITEPATH . '../public/' . $anexo['ruta_archivo'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo); // Eliminar el archivo físico
                }
            }

            // Eliminar los registros de anexos relacionados con la denuncia
            $anexosModel->where('id_denuncia', $id)->delete();

            // Eliminar los registros de comentarios relacionados con la denuncia
            $comentariosModel = new \App\Models\ComentarioDenunciaModel();
            $comentariosModel->where('id_denuncia', $id)->delete();

            // Eliminar los registros de seguimiento de la denuncia
            $seguimientoModel = new \App\Models\SeguimientoDenunciaModel();
            $seguimientoModel->where('id_denuncia', $id)->delete();

            // Finalmente, eliminar la denuncia
            $this->delete($id);

            // Finalizar la transacción
            $this->db->transComplete();

            // Verificar si la transacción fue exitosa
            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Error al eliminar la denuncia.');
            }

            return true;
        } catch (\Exception $e) {
            // Si ocurre un error, revertir la transacción
            $this->db->transRollback();
            log_message('error', $e->getMessage());
            return false;
        }
    }

    public function getDenunciasByAgente($agenteId)
    {
        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('relacion_clientes_usuarios', 'relacion_clientes_usuarios.id_cliente = denuncias.id_cliente')
            ->where('relacion_clientes_usuarios.id_usuario', $agenteId)
            ->whereIn('denuncias.estado_actual', [1, 2]) // Filtrar por estados "Recepción" y "Clasificada"
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function getDenunciasParaCalidad()
    {
        // Supongamos que los estados relevantes para el supervisor de calidad son 2, 3 y 4
        $estadosRelevantes = [2, 3, 4]; // ID de los estados "Clasificada", "Revisada por Calidad", "Liberada al Cliente"

        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->whereIn('denuncias.estado_actual', $estadosRelevantes)
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }
}
