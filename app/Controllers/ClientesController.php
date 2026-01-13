<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\UsuarioModel;
use CodeIgniter\Controller;

class ClientesController extends Controller
{
    // Catálogo permitido (para SET/ENUM)
    private const TIPOS_DENUNCIANTE = ['Cliente', 'Colaborador', 'Proveedor'];

    public function index()
    {
        $session = session();

        $data = [
            'title'       => 'Administración de Clientes',
            'controlador' => 'Clientes',
            'vista'       => 'Clientes',
            'rol_slug'    => $session->get('rol_slug'),
        ];

        return view('clientes/index', $data);
    }

    public function listar()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        return $this->response->setJSON($clientes);
    }

    public function guardar()
    {
        $session      = session();
        $rolSlug      = $session->get('rol_slug');
        $clienteModel = new ClienteModel();

        $id = $this->request->getVar('id');

        // 1) Leer campos base
        $data = [
            'nombre_empresa'        => $this->request->getVar('nombre_empresa'),
            'numero_identificacion' => $this->request->getVar('numero_identificacion'),
            'correo_contacto'       => $this->request->getVar('correo_contacto'),
            'telefono_contacto'     => $this->request->getVar('telefono_contacto'),
            'direccion'             => $this->request->getVar('direccion'),
            'slug'                  => $this->request->getVar('slug'),
            'logo'                  => $this->request->getVar('logo'),
            'banner'                => $this->request->getVar('banner'),
            'saludo'                => $this->request->getVar('saludo'),
            'whatsapp'              => $this->request->getVar('whatsapp'),
            'primary_color'         => $this->request->getVar('primary_color'),
            'secondary_color'       => $this->request->getVar('secondary_color'),
            'link_color'            => $this->request->getVar('link_color'),
        ];

        // 2) Política anonimato (0/1/2)
        $politica = $this->request->getVar('politica_anonimato');
        if ($politica !== null) {
            $politica = (int) $politica;
            if (!in_array($politica, [0, 1, 2], true)) {
                $politica = 0;
            }
            $data['politica_anonimato'] = $politica;
        }

        // 3) Mostrar/ocultar tipo denunciante público (0/1)
        $mostrarTipo = $this->request->getVar('mostrar_tipo_denunciante_publico');
        if ($mostrarTipo !== null) {
            $valorNormalizado = strtolower(trim((string) $mostrarTipo));
            $data['mostrar_tipo_denunciante_publico'] = in_array($valorNormalizado, ['1', 'true', 'on', 'si', 'yes'], true) ? 1 : 0;
        }

        // 4) Nuevos campos: default + permitidos (solo si vienen en request)
        //    NOTA: aunque no vengan, luego aplicamos reglas de coherencia si cambió el flag "mostrar"
        $defaultRaw = $this->request->getVar('tipo_denunciante_publico_default');
        if ($defaultRaw !== null) {
            $default = $this->normalizarTipoDenunciante($defaultRaw);
            $data['tipo_denunciante_publico_default'] = $default;
        }

        $permitidosRaw = $this->request->getVar('tipos_denunciante_publico_permitidos');
        if ($permitidosRaw !== null) {
            $permitidos = $this->normalizarPermitidos($permitidosRaw);
            // Guardado en formato SET: "Cliente,Colaborador"
            $data['tipos_denunciante_publico_permitidos'] = implode(',', $permitidos);
        }

        // 5) Permisos:
        // CLIENTE sólo puede actualizar su propio registro y únicamente configuraciones.
        if ($rolSlug === 'CLIENTE') {
            $miClienteId = (int) ($session->get('id_cliente') ?? 0);
            if ((int) $id !== $miClienteId) {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'No autorizado.']);
            }

            $data = array_intersect_key($data, array_flip([
                'politica_anonimato',
                'mostrar_tipo_denunciante_publico',
                'tipo_denunciante_publico_default',
                'tipos_denunciante_publico_permitidos',
            ]));
        }

        // 6) Reglas de coherencia de configuración (solo si el request tocó algo relacionado)
        // Determinar valor final de mostrar:
        $mostrarFinal = null;
        if (array_key_exists('mostrar_tipo_denunciante_publico', $data)) {
            $mostrarFinal = (int)$data['mostrar_tipo_denunciante_publico'];
        } elseif ($id) {
            $c = $clienteModel->find($id);
            $mostrarFinal = (int)($c['mostrar_tipo_denunciante_publico'] ?? 0);
        } else {
            $mostrarFinal = 0; // nuevo cliente: si no mandan, asume oculto
        }

        if ($mostrarFinal === 0) {
            // Si NO se muestra el combo, forzamos configuración consistente:
            $data['tipo_denunciante_publico_default'] = 'Colaborador';
            $data['tipos_denunciante_publico_permitidos'] = 'Colaborador';
        } else {
            // Si se muestra, asegurar permitidos válidos y que default esté dentro:
            // Tomar permitidos desde data o desde DB (si edita)
            $permitidosActual = null;

            if (array_key_exists('tipos_denunciante_publico_permitidos', $data)) {
                $permitidosActual = $this->normalizarPermitidos($data['tipos_denunciante_publico_permitidos']);
            } elseif ($id) {
                $c = $clienteModel->find($id);
                $permitidosActual = $this->normalizarPermitidos($c['tipos_denunciante_publico_permitidos'] ?? '');
            } else {
                $permitidosActual = self::TIPOS_DENUNCIANTE; // nuevo: default todos
            }

            if (empty($permitidosActual)) {
                // fallback duro: al menos Colaborador
                $permitidosActual = ['Colaborador'];
            }

            // Asegurar default
            $defaultActual = null;
            if (array_key_exists('tipo_denunciante_publico_default', $data)) {
                $defaultActual = $this->normalizarTipoDenunciante($data['tipo_denunciante_publico_default']);
            } elseif ($id) {
                $c = $clienteModel->find($id);
                $defaultActual = $this->normalizarTipoDenunciante($c['tipo_denunciante_publico_default'] ?? 'Colaborador');
            } else {
                $defaultActual = 'Colaborador';
            }

            if (!in_array($defaultActual, $permitidosActual, true)) {
                // Si el default no está en permitidos, usamos el primero permitido
                $defaultActual = $permitidosActual[0];
            }

            $data['tipos_denunciante_publico_permitidos'] = implode(',', $permitidosActual);
            $data['tipo_denunciante_publico_default'] = $defaultActual;
        }

        // 7) Validación de unicidad (solo ADMIN)
        if ($id) {
            $clienteExistente = $clienteModel->where('id !=', $id)
                ->groupStart()
                ->where('nombre_empresa', $this->request->getVar('nombre_empresa'))
                ->orWhere('correo_contacto', $this->request->getVar('correo_contacto'))
                ->orWhere('slug', $this->request->getVar('slug'))
                ->groupEnd()
                ->first();
        } else {
            $clienteExistente = $clienteModel->groupStart()
                ->where('nombre_empresa', $this->request->getVar('nombre_empresa'))
                ->orWhere('correo_contacto', $this->request->getVar('correo_contacto'))
                ->orWhere('slug', $this->request->getVar('slug'))
                ->groupEnd()
                ->first();
        }

        if ($clienteExistente && $rolSlug === 'ADMIN') {
            $message = [];
            if ($clienteExistente['nombre_empresa'] == $this->request->getVar('nombre_empresa')) {
                $message[] = 'El nombre de la empresa ya está en uso';
            }
            if ($clienteExistente['correo_contacto'] == $this->request->getVar('correo_contacto')) {
                $message[] = 'El correo de contacto ya está en uso';
            }
            if ($clienteExistente['slug'] == $this->request->getVar('slug')) {
                $message[] = 'El slug ya está en uso';
            }
            return $this->response->setStatusCode(409)->setJSON(['message' => implode(', ', $message)]);
        }

        // 8) Guardado
        if ($id) {
            // Filtrar campos vacíos, conservando '0'
            foreach ($data as $key => $value) {
                if ($value === null || $value === '') {
                    unset($data[$key]);
                }
            }

            if (!empty($data)) {
                $clienteModel->update($id, $data);
                registrarAccion(session()->get('id'), 'Actualización de cliente', 'ID: ' . $id);
            }
        } else {
            // Alta (sólo ADMIN)
            if ($rolSlug !== 'ADMIN') {
                return $this->response->setStatusCode(403)->setJSON(['message' => 'No autorizado.']);
            }

            // Defaults razonables para alta si no llegaron
            if (!isset($data['mostrar_tipo_denunciante_publico'])) {
                $data['mostrar_tipo_denunciante_publico'] = 0;
            }

            if ((int)$data['mostrar_tipo_denunciante_publico'] === 0) {
                $data['tipo_denunciante_publico_default'] = 'Colaborador';
                $data['tipos_denunciante_publico_permitidos'] = 'Colaborador';
            } else {
                if (!isset($data['tipos_denunciante_publico_permitidos'])) {
                    $data['tipos_denunciante_publico_permitidos'] = implode(',', self::TIPOS_DENUNCIANTE);
                }
                if (!isset($data['tipo_denunciante_publico_default'])) {
                    $data['tipo_denunciante_publico_default'] = 'Colaborador';
                }
            }

            $clienteModel->save($data);
            $newId = $clienteModel->insertID();
            registrarAccion(session()->get('id'), 'Creación de cliente', 'ID: ' . $newId);
        }

        return $this->response->setJSON(['message' => 'Cliente guardado correctamente']);
    }

    public function obtener($id)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->find($id);

        registrarAccion(session()->get('id'), 'Visualización de cliente', 'ID: ' . $id);

        return $this->response->setJSON($cliente);
    }

    public function eliminar($id)
    {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->where('id_cliente', $id)->first();

        if ($usuario) {
            return $this->response->setStatusCode(409)->setJSON(['message' => 'No se puede eliminar el cliente porque tiene usuarios asociados']);
        }

        $clienteModel = new ClienteModel();
        $clienteModel->delete($id);

        registrarAccion(session()->get('id'), 'Eliminación de cliente', 'ID: ' . $id);

        return $this->response->setJSON(['message' => 'Cliente eliminado correctamente']);
    }

    public function subirImagen()
    {
        $file = $this->request->getFile('file');

        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . '../public/assets/images/clientes', $newName);
            return $this->response->setJSON(['filename' => $newName]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo subir la imagen.']);
    }

    public function validarUnico()
    {
        $clienteModel = new ClienteModel();
        $id = $this->request->getVar('id');
        $nombre_empresa = $this->request->getVar('nombre_empresa');
        $numero_identificacion = $this->request->getVar('numero_identificacion');
        $correo_contacto = $this->request->getVar('correo_contacto');
        $slug = $this->request->getVar('slug');

        $conditions = [];
        if ($nombre_empresa) $conditions['nombre_empresa'] = $nombre_empresa;
        if ($numero_identificacion) $conditions['numero_identificacion'] = $numero_identificacion;
        if ($correo_contacto) $conditions['correo_contacto'] = $correo_contacto;
        if ($slug) $conditions['slug'] = $slug;

        if (!empty($conditions)) {
            $clienteModel->groupStart();
            foreach ($conditions as $field => $value) {
                $clienteModel->orWhere($field, $value);
            }
            $clienteModel->groupEnd();

            if ($id) {
                $clienteModel->where('id !=', $id);
            }

            $cliente = $clienteModel->first();
            if ($cliente) {
                return $this->response->setJSON(false);
            }
        }

        return $this->response->setJSON(true);
    }

    /* =========================
     * Helpers privados
     * ========================= */

    private function normalizarTipoDenunciante($valor): string
    {
        $v = strtolower(trim((string)$valor));
        return match ($v) {
            'cliente'     => 'Cliente',
            'colaborador' => 'Colaborador',
            'proveedor'   => 'Proveedor',
            default       => 'Colaborador',
        };
    }

    /**
     * Acepta:
     * - array: ['cliente','proveedor']
     * - string: "cliente, proveedor" o "cliente|proveedor"
     * - string ya en formato SET: "Cliente,Colaborador"
     */
    private function normalizarPermitidos($raw): array
    {
        $arr = [];

        if (is_array($raw)) {
            $arr = $raw;
        } else {
            $s = trim((string)$raw);
            if ($s === '') return [];
            $s = str_replace(['|', ';'], ',', $s);
            $arr = array_map('trim', explode(',', $s));
        }

        $norm = [];
        foreach ($arr as $item) {
            if ($item === '' || $item === null) continue;
            $tipo = $this->normalizarTipoDenunciante($item);
            if (in_array($tipo, self::TIPOS_DENUNCIANTE, true)) {
                $norm[] = $tipo;
            }
        }

        // Quitar duplicados preservando orden
        $norm = array_values(array_unique($norm));

        return $norm;
    }
}
