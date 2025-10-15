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
        'visible_para_cliente',
        'tiempo_atencion_cliente',
        'id_sexo',
        'fecha_cierre',
        'created_at'
    ];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $beforeInsert     = ['generateFolio', 'setDefaultValues'];
    protected $beforeUpdate     = ['setUpdateTimestamp'];

    protected function generateFolio(array $data): array
    {
        $date = date('ymd');
        $lastDenuncia = $this->select('folio')
            ->like('folio', "$date-", 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $newFolio = $lastDenuncia ? (int)substr($lastDenuncia['folio'], -4) + 1 : 1;
        $data['data']['folio'] = "$date-" . str_pad($newFolio, 4, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function setDefaultValues(array $data): array
    {
        $data['data']['fecha_hora_reporte'] = date('Y-m-d H:i:s');
        $data['data']['estado_actual'] = 1;
        return $data;
    }

    protected function setUpdateTimestamp(array $data): array
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    /** Tipos de denunciante permitidos para el usuario cliente actual */
    private function tiposPermitidosUsuarioActual(): array
    {
        // En tu sesión viene rol_slug=CLIENTE (y rol_nombre=Cliente)
        $rolSlug = strtoupper((string) (session()->get('rol_slug') ?? ''));
        $rolNombre = strtoupper((string) (session()->get('rol_nombre') ?? ''));

        // Sólo aplicar el filtro si el usuario es CLIENTE
        if ($rolSlug !== 'CLIENTE' && $rolNombre !== 'CLIENTE') {
            return [];
        }

        $userId = (int) (session()->get('id') ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $permModel = new \App\Models\UsuarioPermisoDenuncianteModel();
        // Debe devolver un arreglo simple con los nombres/ids que guardas en denuncias.tipo_denunciante
        return $permModel->getTiposByUsuario($userId);
    }


    public function getDenuncias(): array
    {
        return $this->select('denuncias.*, 
        clientes.nombre_empresa AS cliente_nombre, 
        sucursales.nombre AS sucursal_nombre, 
        categorias_denuncias.nombre AS categoria_nombre, 
        subcategorias_denuncias.nombre AS subcategoria_nombre, 
        departamentos.nombre AS departamento_nombre, 
        estados_denuncias.nombre AS estado_nombre,
        sexos_denunciante.nombre AS sexo_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sexos_denunciante', 'sexos_denunciante.id = denuncias.id_sexo', 'left')
            ->where('denuncias.estado_actual !=', 7)
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
            subcategorias_denuncias.nombre AS subcategoria_nombre,
            sexos_denunciante.nombre AS sexo_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('sexos_denunciante', 'sexos_denunciante.id = denuncias.id_sexo', 'left')
            ->where('denuncias.id', $id)
            ->first();
    }

    public function cambiarEstado(int $id, int $estadoNuevo): bool
    {
        $data = ['estado_actual' => $estadoNuevo];
        if ($estadoNuevo === 6) {
            $data['fecha_cierre'] = date('Y-m-d H:i:s');
        }
        return $this->update($id, $data);
    }

    public function getDenunciasByCliente($clienteId)
    {
        $estadosPermitidos = [4, 5];

        $builder = $this->select('denuncias.*, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre,
                          sexos_denunciante.nombre AS sexo_nombre')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sexos_denunciante', 'sexos_denunciante.id = denuncias.id_sexo', 'left')
            ->where('denuncias.id_cliente', $clienteId)
            ->whereIn('denuncias.estado_actual', $estadosPermitidos);

        $tiposPermitidos = $this->tiposPermitidosUsuarioActual();
        if (!empty($tiposPermitidos)) {
            $builder->whereIn('denuncias.tipo_denunciante', $tiposPermitidos);
        }

        return $builder->orderBy('denuncias.fecha_hora_reporte', 'DESC')->findAll();
    }

    public function eliminarDenuncia(int $id): bool
    {
        $this->db->transStart();

        try {
            $anexosModel = new \App\Models\AnexoDenunciaModel();
            $anexos = $anexosModel->where('id_denuncia', $id)->findAll();
            foreach ($anexos as $anexo) {
                $rutaArchivo = WRITEPATH . '../public/' . $anexo['ruta_archivo'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
            $anexosModel->where('id_denuncia', $id)->delete();

            $comentariosModel = new \App\Models\ComentarioDenunciaModel();
            $comentariosModel->where('id_denuncia', $id)->delete();

            $seguimientoModel = new \App\Models\SeguimientoDenunciaModel();
            $seguimientoModel->where('id_denuncia', $id)->delete();

            $this->delete($id);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \Exception('Error al eliminar la denuncia.');
            }

            return true;
        } catch (\Exception $e) {
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
                          estados_denuncias.nombre AS estado_nombre,
                          sexos_denunciante.nombre AS sexo_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sexos_denunciante', 'sexos_denunciante.id = denuncias.id_sexo', 'left')
            ->whereIn('denuncias.estado_actual', [1, 2])
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function getDenunciasParaCalidad()
    {
        $estadosRelevantes = [1, 2, 3, 4, 5, 6];

        return $this->select('denuncias.*, 
                          clientes.nombre_empresa AS cliente_nombre, 
                          sucursales.nombre AS sucursal_nombre, 
                          categorias_denuncias.nombre AS categoria_nombre, 
                          subcategorias_denuncias.nombre AS subcategoria_nombre, 
                          departamentos.nombre AS departamento_nombre, 
                          estados_denuncias.nombre AS estado_nombre,
                          sexos_denunciante.nombre AS sexo_nombre')
            ->join('clientes', 'clientes.id = denuncias.id_cliente', 'left')
            ->join('sucursales', 'sucursales.id = denuncias.id_sucursal', 'left')
            ->join('categorias_denuncias', 'categorias_denuncias.id = denuncias.categoria', 'left')
            ->join('subcategorias_denuncias', 'subcategorias_denuncias.id = denuncias.subcategoria', 'left')
            ->join('departamentos', 'departamentos.id = denuncias.id_departamento', 'left')
            ->join('estados_denuncias', 'estados_denuncias.id = denuncias.estado_actual', 'left')
            ->join('sexos_denunciante', 'sexos_denunciante.id = denuncias.id_sexo', 'left')
            ->whereIn('denuncias.estado_actual', $estadosRelevantes)
            ->orderBy('denuncias.fecha_hora_reporte', 'DESC')
            ->findAll();
    }

    public function filtrarDenuncias($limit, $offset, array $filters, $sort = '', $order = 'asc')
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

        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $builder->where('denuncias.fecha_hora_reporte >=', $filters['fecha_inicio'] . ' 00:00:00');
            $builder->where('denuncias.fecha_hora_reporte <=', $filters['fecha_fin'] . ' 23:59:59');
        }

        if (!empty($filters['id_cliente']) && $filters['id_cliente'] !== 'todos') {
            $builder->where('denuncias.id_cliente', $filters['id_cliente']);
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

        if (!empty($filters['estado_actual'])) {
            $builder->where('denuncias.estado_actual', $filters['estado_actual']);
        }

        if (!empty($filters['id_creador'])) {
            $builder->where('denuncias.id_creador', $filters['id_creador']);
        }

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

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

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

        $builder->limit($limit, $offset);
        $result = $builder->get()->getResultArray();

        return [
            'total' => $total,
            'rows'  => $result
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

        $builder->where('denuncias.id_cliente', $clienteId);

        $estadosVisibles = [4, 5, 6];
        $builder->whereIn('denuncias.estado_actual', $estadosVisibles);

        // Filtro por tipos permitidos si el usuario cliente tiene permisos configurados
        $tiposPermitidos = $this->tiposPermitidosUsuarioActual();
        if (!empty($tiposPermitidos)) {
            $builder->whereIn('denuncias.tipo_denunciante', $tiposPermitidos);
        }

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

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults(false);

        $builder->limit($limit, $offset);
        $result = $builder->get()->getResultArray();

        return [
            'total' => $total,
            'rows'  => $result
        ];
    }

    public function getSubcategoriasPorCategoria($categoriaId, $filters = [])
    {
        $builder = $this->db->table('denuncias d');
        $builder->select('scd.id, scd.nombre, COUNT(d.id) as total')
            ->join('subcategorias_denuncias scd', 'scd.id = d.subcategoria', 'left')
            ->where('d.categoria', $categoriaId)
            ->groupBy('scd.id');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $builder->where('d.fecha_hora_reporte >=', $filters['start_date'] . ' 00:00:00');
            $builder->where('d.fecha_hora_reporte <=', $filters['end_date'] . ' 23:59:59');
        }

        if (!empty($filters['cliente'])) {
            $builder->where('d.id_cliente', $filters['cliente']);
        }

        if (!empty($filters['sucursal'])) {
            $builder->where('d.id_sucursal', $filters['sucursal']);
        }

        if (!empty($filters['departamento'])) {
            $builder->where('d.id_departamento', $filters['departamento']);
        }

        if ($filters['anonimo'] !== '') {
            $builder->where('d.anonimo', $filters['anonimo']);
        }

        return $builder->get()->getResultArray();
    }

    public function getResumenCategoriasConFiltros($filters = [])
    {
        $builder = $this->db->table('denuncias d');

        $builder->select('cd.id, cd.nombre AS categoria, COUNT(d.id) AS total_denuncias, COUNT(DISTINCT sd.id) AS total_subcategorias');
        $builder->join('categorias_denuncias cd', 'cd.id = d.categoria', 'left');
        $builder->join('subcategorias_denuncias sd', 'sd.id = d.subcategoria', 'left');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $builder->where('d.fecha_hora_reporte >=', $filters['start_date'] . ' 00:00:00');
            $builder->where('d.fecha_hora_reporte <=', $filters['end_date'] . ' 23:59:59');
        }

        if (!empty($filters['cliente'])) {
            $builder->where('d.id_cliente', $filters['cliente']);
        }

        if (!empty($filters['sucursal'])) {
            $builder->where('d.id_sucursal', $filters['sucursal']);
        }

        if (!empty($filters['departamento'])) {
            $builder->where('d.id_departamento', $filters['departamento']);
        }

        if ($filters['anonimo'] !== '') {
            $builder->where('d.anonimo', $filters['anonimo']);
        }

        $builder->groupBy('cd.id');
        $builder->orderBy('cd.nombre');

        return $builder->get()->getResultArray();
    }
}
