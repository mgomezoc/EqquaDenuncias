<?php

namespace App\Controllers;

use App\Models\DenunciaModel;
use App\Models\ClienteModel;
use App\Models\EstadoDenunciaModel;
use App\Models\CategoriaDenunciaModel;
use App\Models\SubcategoriaDenunciaModel;
use App\Models\SucursalModel;
use App\Models\AnexoDenunciaModel;
use App\Models\DepartamentoModel;
use App\Models\SeguimientoDenunciaModel;
use App\Models\UsuarioModel;
use App\Services\EmailService;
use CodeIgniter\Controller;

class DenunciasController extends Controller
{
    public function index()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        $estadoModel = new EstadoDenunciaModel();
        $estados = $estadoModel->findAll();

        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->findAll();

        $data = [
            'title' => 'Administración de Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Denuncias',
            'clientes' => $clientes,
            'estados' => $estados,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
        ];

        return view('denuncias/index', $data);
    }

    public function misDenunciasAgente()
    {
        $clienteModel = new ClienteModel();
        $estadoModel = new EstadoDenunciaModel();
        $categoriaModel = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        // Obtener el ID del agente desde la sesión
        $agenteId = session()->get('id');

        $clientes = $clienteModel->findAll();

        $data = [
            'title' => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Mis Denuncias Agente',
            'clientes' => $clientes,
            'estados' => $estadoModel->findAll(),
            'categorias' => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
        ];

        return view('denuncias/mis_denuncias_agente', $data);
    }

    public function gestionSupervisor()
    {
        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->findAll();

        $estadoModel = new EstadoDenunciaModel();
        $estados = $estadoModel->findAll();

        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        $subcategoriaModel = new SubcategoriaDenunciaModel();
        $subcategorias = $subcategoriaModel->findAll();

        // Obtener solo las denuncias que están en los estados relevantes para el supervisor de calidad
        $clienteId = session()->get('id_cliente');

        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->getDenunciasParaCalidad($clienteId);

        $data = [
            'title' => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Gestión de Denuncias Supervisor',
            'clientes' => $clientes,
            'estados' => $estados,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'denuncias' => $denuncias,  // Pasar las denuncias relevantes a la vista
        ];

        return view('denuncias/gestion_supervisor', $data);
    }

    public function misDenunciasCliente()
    {
        $clienteId = session()->get('id_cliente');

        // Verifica que haya un cliente autenticado
        if (!$clienteId) {
            return redirect()->to('/login')->with('error', 'Debe iniciar sesión para ver sus denuncias');
        }

        $solo_lectura = session()->get('solo_lectura');

        // Instancias de los modelos
        $clienteModel = new ClienteModel();
        $denunciaModel = new DenunciaModel();
        $estadoModel = new EstadoDenunciaModel();
        $categoriaModel = new CategoriaDenunciaModel();
        $subcategoriaModel = new SubcategoriaDenunciaModel();

        // Obtener la información del cliente
        $cliente = $clienteModel->getClienteById($clienteId);

        // Obtener las denuncias solo relacionadas con el cliente actual
        $denuncias = $denunciaModel->getDenunciasByCliente($clienteId);

        $data = [
            'title' => 'Denuncias Activas',
            'controlador' => 'Denuncias',
            'vista' => 'Mis Denuncias Cliente',
            'cliente' => $cliente,  // Información del cliente
            'estados' => $estadoModel->findAll(),
            'categorias' => $categoriaModel->findAll(),
            'subcategorias' => $subcategoriaModel->findAll(),
            'denuncias' => $denuncias,
            'solo_lectura' => $solo_lectura
        ];

        return view('denuncias/mis_denuncias_cliente', $data);
    }

    public function listar()
    {
        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->getDenuncias();

        return $this->response->setJSON($denuncias);
    }

    public function listarDenunciasAgente()
    {
        $denunciaModel = new DenunciaModel();
        $agenteId = session()->get('id');

        // Filtra las denuncias según los clientes que el agente puede ver
        $denuncias = $denunciaModel->getDenunciasByAgente($agenteId);

        return $this->response->setJSON($denuncias);
    }

    public function listarDenunciasCalidad()
    {
        $clienteId = session()->get('id_cliente');
        $denunciaModel = new DenunciaModel();
        $denuncias = $denunciaModel->getDenunciasParaCalidad($clienteId);

        return $this->response->setJSON($denuncias);
    }

    public function listarDenunciasCliente()
    {
        $denunciaModel = new DenunciaModel();
        $clienteId = session()->get('id_cliente'); // Asegúrate de que 'id_cliente' esté en la sesión

        if (!$clienteId) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Acceso denegado. Cliente no autenticado.']);
        }

        // Filtra las denuncias según el cliente autenticado
        $denuncias = $denunciaModel->getDenunciasByCliente($clienteId);

        return $this->response->setJSON($denuncias);
    }

    public function detalle($id)
    {
        $denunciaModel = new DenunciaModel();
        $seguimientoModel = new SeguimientoDenunciaModel(); // Instancia el modelo de seguimiento

        $denuncia = $denunciaModel->getDenunciaById($id);
        $seguimientos = $seguimientoModel->getSeguimientoByDenunciaId($id); // Obtén el historial de seguimiento

        // Adjunta los seguimientos al array de denuncia
        $denuncia['seguimientos'] = $seguimientos;

        return $this->response->setJSON($denuncia);
    }

    public function guardar()
    {
        $denunciaModel = new DenunciaModel();
        $anexoModel = new AnexoDenunciaModel();
        $id = $this->request->getVar('id');

        // Verifica que la sesión esté iniciada y el usuario esté autenticado
        $idCreador = session()->get('id');
        if (!$idCreador) {
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Usuario no autenticado o sesión no iniciada']);
        }

        // Recopilar solo los datos enviados, incluyendo estado_actual y medio_recepcion
        $data = array_filter([
            'id_cliente' => $this->request->getVar('id_cliente'),
            'id_sucursal' => $this->request->getVar('id_sucursal'),
            'tipo_denunciante' => $this->request->getVar('tipo_denunciante'),
            'categoria' => $this->request->getVar('categoria'),
            'subcategoria' => $this->request->getVar('subcategoria'),
            'id_departamento' => $this->request->getVar('id_departamento') ?: null,
            'anonimo' => $this->request->getVar('anonimo'),
            'fecha_incidente' => $this->request->getVar('fecha_incidente'),
            'como_se_entero' => $this->request->getVar('como_se_entero'),
            'denunciar_a_alguien' => $this->request->getVar('denunciar_a_alguien'),
            'area_incidente' => $this->request->getVar('area_incidente'),
            'descripcion' => $this->request->getVar('descripcion'),
            'estado_actual' => $this->request->getVar('estado_actual'),
            'medio_recepcion' => $this->request->getVar('medio_recepcion'),
            'nombre_completo' => $this->request->getVar('nombre_completo'),
            'correo_electronico' => $this->request->getVar('correo_electronico'),
            'telefono' => $this->request->getVar('telefono'),
            'id_creador' => $idCreador,
            'id_sexo' => $this->request->getVar('id_sexo'),
        ], function ($value) {
            return $value !== null;
        });



        $db = \Config\Database::connect();
        $db->transStart(); // Inicia una transacción

        try {
            // Guardar o actualizar la denuncia
            if ($id) {
                // Actualizar solo los campos que se han proporcionado en la solicitud
                if (!$denunciaModel->update($id, $data)) {
                    throw new \RuntimeException('Error al actualizar la denuncia.');
                }
                registrarAccion($idCreador, 'Actualización de denuncia', 'ID: ' . $id);
            } else {
                if (!$denunciaModel->save($data)) {
                    throw new \RuntimeException('Error al guardar la denuncia.');
                }
                $newId = $denunciaModel->insertID();
                registrarAccion($idCreador, 'Creación de denuncia', 'ID: ' . $newId);
                $id = $newId; // Usa el nuevo ID para la inserción de anexos
            }

            // Procesa los archivos adjuntos (anexos) desde los inputs ocultos
            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    // Obtener el nombre del archivo desde la ruta
                    $nombreArchivo = basename($rutaArchivo);

                    // Guarda la información del anexo en la base de datos
                    if (!$anexoModel->save([
                        'id_denuncia' => $id,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $rutaArchivo,
                        'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            $db->transComplete(); // Finaliza la transacción

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }
        } catch (\Exception $e) {
            $db->transRollback(); // Revertir la transacción en caso de error
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al guardar la denuncia y los archivos adjuntos. Error: ' . $e->getMessage()]);
        }

        return $this->response->setJSON(['message' => 'Denuncia guardada correctamente']);
    }

    public function eliminar($id)
    {
        $denunciaModel = new DenunciaModel();

        if ($denunciaModel->eliminarDenuncia($id)) {
            return $this->response->setJSON(['message' => 'Denuncia eliminada correctamente']);
        } else {
            return $this->response->setStatusCode(500)
                ->setJSON(['message' => 'Error al eliminar la denuncia']);
        }
    }

    public function cambiarEstado()
    {
        $denunciaModel = new DenunciaModel();
        $seguimientoModel = new SeguimientoDenunciaModel();
        $comentarioModel = new \App\Models\ComentarioDenunciaModel(); // <-- Importar el modelo si no está arriba

        $id = $this->request->getVar('id');
        $estado_nuevo = $this->request->getVar('estado_nuevo');

        // Obtener el estado anterior antes de realizar el cambio
        $denuncia = $denunciaModel->find($id);
        $estado_anterior = $denuncia['estado_actual'];

        // Si el nuevo estado es 5 y el anterior era 4, calcular tiempo de atención
        if ($estado_anterior == 4 && $estado_nuevo == 5) {
            $seguimiento = $seguimientoModel
                ->where('id_denuncia', $id)
                ->where('estado_nuevo', 4)
                ->orderBy('fecha', 'DESC')
                ->first();

            if ($seguimiento) {
                $fechaLiberacion = strtotime($seguimiento['fecha']);
                $tiempoAtencion = time() - $fechaLiberacion;
                $denunciaModel->update($id, ['tiempo_atencion_cliente' => $tiempoAtencion]);
            }
        }

        // Realizar el cambio de estado
        $denunciaModel->cambiarEstado($id, $estado_nuevo);

        // Guardar en el historial de seguimiento
        $seguimientoModel->save([
            'id_denuncia' => $id,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado_nuevo,
            'comentario' => $this->request->getVar('comentario'),
            'id_usuario' => session()->get('id'),
            'fecha' => date('Y-m-d H:i:s')
        ]);

        registrarAccion(session()->get('id'), 'Cambio de estado de denuncia', 'ID: ' . $id);

        // Verificar si el estado es "Liberada al Cliente" (estado 4)
        if ($estado_nuevo == 4) {

            // 🔹 Insertar comentario automático
            $comentarioModel->insert([
                'id_denuncia'     => $id,
                'id_usuario'      => 1,
                'contenido'       => 'Su denuncia está siendo atendida. Favor de revisar en 48 horas.',
                'estado_denuncia' => $estado_nuevo,
                'fecha_comentario' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->response->setJSON(['message' => 'Estado actualizado correctamente']);
    }



    public function subirAnexo()
    {
        $file = $this->request->getFile('file');

        if ($file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . '../public/assets/denuncias', $newName);
            return $this->response->setJSON(['filename' => $newName]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'No se pudo subir el anexo.']);
    }

    public function obtenerSucursalesPorCliente($id_cliente)
    {
        $sucursalModel = new SucursalModel();
        $sucursales = $sucursalModel->where('id_cliente', $id_cliente)->findAll();
        return $this->response->setJSON($sucursales);
    }

    public function listarDepartamentosPorSucursal($id_sucursal)
    {
        $departamentoModel = new DepartamentoModel();
        $departamentos = $departamentoModel->where('id_sucursal', $id_sucursal)->findAll();

        return $this->response->setJSON($departamentos);
    }

    public function obtenerEstados()
    {
        $estadoModel = new EstadoDenunciaModel();
        $estados = $estadoModel->getEstados();

        return $this->response->setJSON($estados);
    }

    public function obtenerAnexos($id_denuncia)
    {
        $anexoModel = new \App\Models\AnexoDenunciaModel();
        $anexos = $anexoModel->getAnexosByDenunciaId($id_denuncia);

        return $this->response->setJSON($anexos);
    }

    public function misDenuncias()
    {
        $clienteId = session()->get('id_cliente'); // Asegurándote que 'id_cliente' esté en la sesión
        $denunciaModel = new DenunciaModel();

        // Verificar si la solicitud es AJAX
        if ($this->request->isAJAX()) {
            // Buscar denuncias por cliente y devolver en formato JSON
            $denuncias = $denunciaModel->getDenunciasByCliente($clienteId);
            return $this->response->setJSON($denuncias);
        }

        // Si no es AJAX, cargar la vista normalmente
        $categoriaModel = new CategoriaDenunciaModel();
        $categorias = $categoriaModel->findAll();

        $data = [
            'title' => 'Mis Denuncias',
            'controlador' => 'Denuncias',
            'vista' => 'Mis Denuncias',
            'categorias' => $categorias,
        ];

        return view('mis_denuncias/index', $data);
    }

    public function actualizarAnexos()
    {
        $denunciaId = $this->request->getVar('id');
        $anexoModel = new AnexoDenunciaModel();

        // Iniciar la transacción
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Procesar los nuevos archivos adjuntos desde los inputs ocultos
            $anexos = $this->request->getVar('archivos');
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $rutaArchivo) {
                    $nombreArchivo = basename($rutaArchivo);

                    // Guardar la información del anexo en la base de datos
                    if (!$anexoModel->save([
                        'id_denuncia' => $denunciaId,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta_archivo' => $rutaArchivo,
                        'tipo' => mime_content_type(WRITEPATH . '../public/' . $rutaArchivo),
                    ])) {
                        throw new \RuntimeException('Error al guardar el anexo.');
                    }
                }
            }

            // Completar la transacción
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al completar la transacción.');
            }

            return $this->response->setJSON(['message' => 'Archivos actualizados correctamente']);
        } catch (\Exception $e) {
            $db->transRollback(); // Revertir la transacción en caso de error
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al actualizar los archivos adjuntos. Error: ' . $e->getMessage()]);
        }
    }

    public function eliminarAnexo($id)
    {
        $anexoModel = new AnexoDenunciaModel();

        try {
            // Obtener el anexo antes de eliminarlo para eliminar el archivo físicamente
            $anexo = $anexoModel->getAnexoById($id);
            if (!$anexo) {
                throw new \RuntimeException('Anexo no encontrado.');
            }

            // Eliminar el anexo de la base de datos
            if (!$anexoModel->deleteAnexo($id)) {
                throw new \RuntimeException('Error al eliminar el anexo.');
            }

            // Eliminar el archivo físicamente del servidor
            $filePath = WRITEPATH . '../public/' . $anexo['ruta_archivo'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return $this->response->setJSON(['message' => 'Anexo eliminado correctamente']);
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Ocurrió un error al eliminar el anexo. Error: ' . $e->getMessage()]);
        }
    }

    // Función para enviar el correo al cliente cuando la denuncia es liberada
    private function enviarCorreoLiberacionCliente($email, $nombreUsuario, $denuncia)
    {
        $emailService = new EmailService();

        // Crear el mensaje de notificación
        $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Denuncia Liberada</title>
                <style>
                    /* Estilos generales */
                    body {
                        font-family: "Arial", sans-serif;
                        background-color: #f4f4f4;
                        color: #333333;
                        margin: 0;
                        padding: 0;
                        width: 100%;
                    }
                    table {
                        max-width: 600px;
                        width: 100%;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-collapse: collapse;
                    }
                    h1, h2, h3, p {
                        margin: 0;
                    }
                    .header {
                        background-color: #0047ba; /* Color primario del sistema */
                        padding: 20px;
                        text-align: center;
                        color: #ffffff;
                    }
                    .body-content {
                        padding: 20px;
                    }
                    .footer {
                        background-color: #0047ba;
                        color: #ffffff;
                        text-align: center;
                        padding: 10px 20px;
                        font-size: 14px;
                    }
                    .footer a {
                        color: #ffffff;
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <table>
                    <tr>
                        <td class="header">
                            <h1>Nueva Denuncia!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-content">
                            <p>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</p>
                            <p>Le informamos que su denuncia con el folio <strong>' . esc($denuncia['folio']) . '</strong> ha sido liberada.</p>
                            <p>Ahora tiene acceso a la información completa y a los resultados de la investigación. Puede revisar los detalles accediendo a su cuenta en el sistema.</p>
                            <p>Para más detalles, ingrese a su cuenta en <a href="' . base_url() . '">Eqqua Denuncias</a>.</p>
                            <p>Saludos cordiales,<br><strong>Eqqua Denuncias</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p>&copy; ' . date('Y') . ' Eqqua</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        // Enviar el correo
        $emailService->sendEmail($email, 'Nueva Denuncia: ' . esc($denuncia['folio']), $mensaje);
    }
}
