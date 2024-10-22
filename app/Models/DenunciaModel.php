<?php

namespace App\Models;

use CodeIgniter\Model;

class DenunciaModel extends Model
{
    protected $table            = 'denuncias';
    protected $primaryKey       = 'id';
    protected $allowedFields = [
        'id_cliente',
        'folio',
        'fecha_hora_reporte',
        'id_sucursal',
        'tipo_denunciante',
        'categoria',
        'subcategoria',
        'id_departamento',
        'anonimo',
        'nombre_completo',
        'correo_electronico',
        'telefono',
        'fecha_incidente',
        'como_se_entero',
        'denunciar_a_alguien',
        'medio_recepcion',
        'area_incidente',
        'descripcion',
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
        $estadosPermitidos = [4, 5]; // Estados que el cliente puede ver

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
            ->whereIn('denuncias.estado_actual', $estadosPermitidos) // Filtrar por estados permitidos
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
            ->whereIn('denuncias.estado_actual', [1, 2]) // Filtrar por estados "Recepción" y "Clasificada"
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }


    public function getDenunciasParaCalidad()
    {
        // Estados que el supervisor de calidad puede ver
        $estadosRelevantes = [2, 3]; // Solo "Clasificada" y "Revisada por Calidad"

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
            ->whereIn('denuncias.estado_actual', $estadosRelevantes) // Filtrar por estados "Clasificada" y "Revisada por Calidad"
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }


    public function filtrarDenuncias($limit, $offset, array $filters, $sort = '', $order = 'asc')
    {
        // Construir la consulta principal
        $builder = $this->db->table($this->table);

        // Selección de campos y unión con otras tablas
        $builder->select('denuncias.*, 
                  clientes.nombre_empresa AS cliente_nombre, 
                  sucursales.nombre AS sucursal_nombre, 
                  departamentos.nombre AS departamento_nombre, 
                  estados_denuncias.nombre AS estado_nombre, 
                  usuarios.nombre_usuario AS creador_nombre,
                  subcategorias_denuncias.nombre AS subcategoria_nombre,
                  categorias_denuncias.nombre AS categoria_nombre');

        // Uniones con las tablas relacionadas
        $builder->join('clientes', 'clientes.id = denuncias.id_cliente', 'left');
        $builder->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left');
        $builder->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left');
        $builder->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left');
        $builder->join('usuarios', 'usuarios.id = denuncias.id_creador', 'left');
        $builder->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left');
        $builder->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left');

        // Aplicar filtros de fechas si están presentes
        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $builder->where('denuncias.fecha_hora_reporte >=', $filters['fecha_inicio'] . ' 00:00:00');
            $builder->where('denuncias.fecha_hora_reporte <=', $filters['fecha_fin'] . ' 23:59:59');
        }

        // Aplicar filtro de cliente si no es "todos"
        if (!empty($filters['id_cliente']) && $filters['id_cliente'] !== 'todos') {
            $builder->where('denuncias.id_cliente', $filters['id_cliente']);
        }

        // Aplicar filtro de sucursal si está presente
        if (!empty($filters['id_sucursal'])) {
            $builder->where('denuncias.id_sucursal', $filters['id_sucursal']);
        }

        // Aplicar filtro de departamento si está presente
        if (!empty($filters['id_departamento'])) {
            $builder->where('denuncias.id_departamento', $filters['id_departamento']);
        }

        // Aplicar filtro de medio de recepción si está presente
        if (!empty($filters['medio_recepcion'])) {
            $builder->where('denuncias.medio_recepcion', $filters['medio_recepcion']);
        }

        // Aplicar filtro de estado actual si está presente
        if (!empty($filters['estado_actual'])) {
            $builder->where('denuncias.estado_actual', $filters['estado_actual']);
        }

        // Aplicar filtro de creador si está presente
        if (!empty($filters['id_creador'])) {
            $builder->where('denuncias.id_creador', $filters['id_creador']);
        }

        // Aplicar búsqueda en múltiples columnas si se envió el parámetro 'search'
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('denuncias.folio', $search)
                ->orLike('clientes.nombre_empresa', $search)
                ->orLike('sucursales.nombre', $search)
                ->orLike('departamentos.nombre', $search)
                ->orLike('estados_denuncias.nombre', $search)
                ->orLike('usuarios.nombre_usuario', $search)
                ->orLike('denuncias.descripcion', $search)
                ->orLike('subcategorias_denuncias.nombre', $search)
                ->orLike('categorias_denuncias.nombre', $search)
                ->groupEnd();
        }

        // Clonar el builder antes de aplicar los límites para obtener el total
        $countBuilder = clone $builder;

        // Obtener el total de registros que cumplen con los filtros (sin aplicar los límites)
        $total = $countBuilder->countAllResults(false); // El false evita que se resetee el builder

        // Ordenar por la columna solicitada, si está presente
        $validColumns = [
            'folio',
            'cliente_nombre',
            'sucursal_nombre',
            'departamento_nombre',
            'estado_nombre',
            'creador_nombre',
            'subcategoria_nombre',
            'categoria_nombre',
            'fecha_hora_reporte',
            'fecha_incidente'
        ];
        if (!empty($sort) && in_array($sort, $validColumns)) {
            $builder->orderBy($sort, $order);
        } else {
            $builder->orderBy('denuncias.fecha_hora_reporte', 'desc');
        }

        // Aplicar los límites para la paginación
        $builder->limit($limit, $offset);

        // Obtener los resultados con paginación
        $result = $builder->get()->getResultArray();

        // Devolver el total y las filas paginadas
        return [
            'total' => $total,  // Total de registros antes de la paginación
            'rows' => $result   // Registros paginados
        ];
    }




    public function filtrarDenunciasParaCliente($clienteId, $limit, $offset, array $filters, $sort = '', $order = 'asc')
    {
        $builder = $this->db->table($this->table);

        $builder->select('denuncias.*, 
                  clientes.nombre_empresa AS cliente_nombre, 
                  sucursales.nombre AS sucursal_nombre, 
                  departamentos.nombre AS departamento_nombre, 
                  estados_denuncias.nombre AS estado_nombre, 
                  usuarios.nombre_usuario AS creador_nombre,
                  subcategorias_denuncias.nombre AS subcategoria_nombre,
                  categorias_denuncias.nombre AS categoria_nombre');
        $builder->join('clientes', 'clientes.id = denuncias.id_cliente', 'left');
        $builder->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left');
        $builder->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left');
        $builder->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left');
        $builder->join('usuarios', 'usuarios.id = denuncias.id_creador', 'left');
        $builder->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left');
        $builder->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left');

        // Filtro obligatorio para que el cliente solo vea sus propias denuncias
        $builder->where('denuncias.id_cliente', $clienteId);

        // Filtrar siempre por estados 4, 5 y 6 (visible para el cliente)
        $estadosVisibles = [4, 5, 6];
        $builder->whereIn('denuncias.estado_actual', $estadosVisibles);

        // Filtrar por estado_actual si se proporciona y está dentro de los estados permitidos
        if (!empty($filters['estado_actual']) && in_array($filters['estado_actual'], $estadosVisibles)) {
            $builder->where('denuncias.estado_actual', $filters['estado_actual']);
        }

        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $builder->where('denuncias.fecha_hora_reporte >=', $filters['fecha_inicio'] . ' 00:00:00');
            $builder->where('denuncias.fecha_hora_reporte <=', $filters['fecha_fin'] . ' 23:59:59');
        }

        if (!empty($filters['id_sucursal'])) {
            $builder->where('denuncias.id_sucursal', $filters['id_sucursal']);
        }

        if (!empty($filters['id_departamento'])) {
            $builder->where('denuncias.id_departamento', $filters['id_departamento']);
        }

        if (!empty($filters['medio_recepcion'])) {
            $builder->where('denuncias.medio_recepcion', $filters['medio_recepcion']);
        }

        if (!empty($filters['id_creador'])) {
            $builder->where('denuncias.id_creador', $filters['id_creador']);
        }

        // Aplicar búsqueda en múltiples columnas si se envió el parámetro 'search'
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('denuncias.folio', $search)
                ->orLike('clientes.nombre_empresa', $search)
                ->orLike('sucursales.nombre', $search)
                ->orLike('departamentos.nombre', $search)
                ->orLike('estados_denuncias.nombre', $search)
                ->orLike('usuarios.nombre_usuario', $search)
                ->orLike('denuncias.descripcion', $search)
                ->orLike('subcategorias_denuncias.nombre', $search)
                ->orLike('categorias_denuncias.nombre', $search)
                ->groupEnd();
        }

        // Ordenar y validar el tipo de columna a ordenar
        $validColumns = [
            'folio',
            'sucursal_nombre',
            'departamento_nombre',
            'estado_nombre',
            'creador_nombre',
            'subcategoria_nombre',
            'categoria_nombre',
            'fecha_hora_reporte',
            'fecha_incidente'
        ];
        if (!empty($sort) && in_array($sort, $validColumns)) {
            $builder->orderBy($sort, $order);
        } else {
            $builder->orderBy('denuncias.fecha_hora_reporte', 'desc');
        }

        $builder->limit($limit, $offset);

        $result = $builder->get()->getResultArray();

        $total = count($result);

        return [
            'total' => $total,
            'rows' => $result
        ];
    }
}
