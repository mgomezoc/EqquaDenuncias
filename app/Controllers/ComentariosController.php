<?php

namespace App\Controllers;

use App\Models\AnexoComentarioModel;
use App\Models\ComentarioDenunciaModel;
use App\Models\DenunciaModel;
use App\Models\UsuarioModel;
use App\Services\EmailService;

class ComentariosController extends BaseController
{
    public function guardar()
    {
        $comentarioModel = new ComentarioDenunciaModel();
        $denunciaModel = new DenunciaModel();
        $usuarioModel = new UsuarioModel();
        $emailService = new EmailService();

        $id_denuncia = $this->request->getVar('id_denuncia');
        $contenido = $this->request->getVar('contenido');

        // Obtener la denuncia
        $denuncia = $denunciaModel->find($id_denuncia);
        if (!$denuncia) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Denuncia no encontrada']);
        }

        $estado_denuncia = $denuncia['estado_actual'];
        
        // Si no hay sesión activa (comentario desde portal público), usar usuario genérico id=18
        // El modelo ComentarioDenunciaModel se encarga de mostrar el nombre correcto:
        // - Si la denuncia NO es anónima: muestra el nombre_completo del denunciante
        // - Si la denuncia SÍ es anónima: muestra "Anónimo"
        $id_usuario = session()->get('id') ?? 18;

        // Si está cerrada, verificar fecha de cierre
        if ($estado_denuncia == 6 && isset($denuncia['fecha_cierre'])) {
            $fechaCierre = new \DateTime($denuncia['fecha_cierre']);
            $ahora = new \DateTime();

            if ($ahora->diff($fechaCierre)->days > 15) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['message' => 'Ya no se pueden agregar comentarios. Han pasado más de 15 días desde el cierre de la denuncia.']);
            }
        }

        // Guardar el comentario
        $data = [
            'id_denuncia' => $id_denuncia,
            'id_usuario' => $id_usuario,
            'contenido' => $contenido,
            'estado_denuncia' => $estado_denuncia,
        ];

        if ($comentarioModel->save($data)) {
            $id_comentario = $comentarioModel->getInsertID();

            // Procesar archivo si viene uno
            $archivo = $this->request->getFile('archivo_comentario');
            if ($archivo && $archivo->isValid() && !$archivo->hasMoved()) {
                $mime = $archivo->getMimeType();
                $extension = strtolower($archivo->getExtension());

                $esPeligroso = in_array($extension, ['php', 'exe', 'js', 'sh', 'bat']);
                $esMuyPesado = $archivo->getSize() > 10 * 1024 * 1024;

                if (!$esMuyPesado && !$esPeligroso) {
                    $folio = $denuncia['folio'];
                    $nombre = $archivo->getRandomName();
                    $rutaRelativa = "uploads/denuncias/{$folio}/comentarios/{$id_comentario}/";
                    $rutaCompleta = WRITEPATH . "../public/{$rutaRelativa}";

                    if (!is_dir($rutaCompleta)) {
                        mkdir($rutaCompleta, 0755, true);
                    }

                    if ($archivo->move($rutaCompleta, $nombre)) {
                        $anexoComentarioModel = new AnexoComentarioModel();
                        $anexoComentarioModel->save([
                            'id_comentario' => $id_comentario,
                            'nombre_archivo' => $nombre,
                            'ruta_archivo' => $rutaRelativa . $nombre,
                            'tipo_mime' => $mime,
                            'visible_para_cliente' => 1,
                        ]);
                    }
                }
            }

            // Notificar usuarios
            $usuariosInvolucrados = $this->obtenerUsuariosInvolucrados($id_denuncia);
            foreach ($usuariosInvolucrados as $usuario) {
                $this->enviarCorreoNotificacion($usuario['correo_electronico'], $usuario['nombre_usuario'], $denuncia, $contenido);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Comentario guardado correctamente y notificaciones enviadas'
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al guardar el comentario'
            ]);
        }
    }

    public function listar($id_denuncia)
    {
        $comentarioModel = new ComentarioDenunciaModel();
        $anexoComentarioModel = new AnexoComentarioModel();

        $comentarios = $comentarioModel->getComentariosByDenuncia($id_denuncia);

        foreach ($comentarios as &$comentario) {
            $archivos = $anexoComentarioModel
                ->where('id_comentario', $comentario['id'])
                ->findAll();

            $comentario['archivos'] = $archivos;
        }

        return $this->response->setJSON($comentarios);
    }



    public function listarCliente($id_denuncia)
    {
        $comentarioModel = new ComentarioDenunciaModel();
        $comentarios = $comentarioModel->getComentariosVisiblesParaCliente($id_denuncia);
        return $this->response->setJSON($comentarios);
    }

    private function obtenerUsuariosInvolucrados($id_denuncia)
    {
        $usuarioModel = new UsuarioModel();
        $denunciaModel = new DenunciaModel();
        $usuarios = [];

        $denuncia = $denunciaModel->find($id_denuncia);
        if (!$denuncia) {
            return [];
        }

        $estadoActual = $denuncia['estado_actual'];

        // Creador
        $creador = $usuarioModel->query("
            SELECT u.id, u.nombre_usuario, u.correo_electronico
            FROM denuncias d
            INNER JOIN usuarios u ON d.id_creador = u.id
            WHERE d.id = ?", [$id_denuncia])->getResultArray();

        $usuarios = array_merge($usuarios, $creador);

        // Supervisores
        $supervisores = $usuarioModel->query("
            SELECT DISTINCT u.id, u.nombre_usuario, u.correo_electronico
            FROM seguimiento_denuncias sd
            INNER JOIN usuarios u ON sd.id_usuario = u.id
            WHERE sd.id_denuncia = ? AND u.rol_id = 3", [$id_denuncia])->getResultArray();

        $usuarios = array_merge($usuarios, $supervisores);

        // Clientes
        if (in_array($estadoActual, [4, 5, 6])) {
            $clientes = $usuarioModel->query("
                SELECT DISTINCT u.id, u.nombre_usuario, u.correo_electronico
                FROM denuncias d
                INNER JOIN relacion_clientes_usuarios rcu ON d.id_cliente = rcu.id_cliente
                INNER JOIN usuarios u ON rcu.id_usuario = u.id
                WHERE d.id = ? AND u.rol_id = 4", [$id_denuncia])->getResultArray();

            $usuarios = array_merge($usuarios, $clientes);
        }

        // Quitar duplicados
        $usuariosUnicos = [];
        foreach ($usuarios as $usuario) {
            $usuariosUnicos[$usuario['id']] = $usuario;
        }

        return array_values($usuariosUnicos);
    }

    private function enviarCorreoNotificacion($email, $nombreUsuario, $denuncia, $contenidoComentario)
    {
        $emailService = new EmailService();

        $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nuevo Comentario en Denuncia</title>
                <style>
                    body { font-family: "Arial", sans-serif; background-color: #f4f4f4; color: #333; }
                    table { max-width: 600px; margin: auto; background: #fff; border-collapse: collapse; }
                    .header { background-color: #0047ba; color: #fff; padding: 20px; text-align: center; }
                    .body-content { padding: 20px; }
                    .footer { background-color: #0047ba; color: #fff; text-align: center; padding: 10px; font-size: 14px; }
                </style>
            </head>
            <body>
                <table>
                    <tr><td class="header"><h1>Nuevo Comentario en la Denuncia ' . esc($denuncia['folio']) . '</h1></td></tr>
                    <tr><td class="body-content">
                        <p>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</p>
                        <p>Se ha agregado un nuevo comentario en la denuncia con folio <strong>' . esc($denuncia['folio']) . '</strong>.</p>
                        <p><strong>Comentario:</strong> ' . esc($contenidoComentario) . '</p>
                        <p>Para más detalles, acceda a <a href="' . base_url() . '">Eqqua Denuncias</a>.</p>
                    </td></tr>
                </table>
            </body>
            </html>';

        $emailService->sendEmail($email, 'Nuevo Comentario en Denuncia ' . esc($denuncia['folio']), $mensaje);
    }

    public function eliminar($id)
    {
        $comentarioModel = new ComentarioDenunciaModel();
        $comentario = $comentarioModel->find($id);
        if (!$comentario) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Comentario no encontrado']);
        }

        if ($comentarioModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Comentario eliminado correctamente']);
        } else {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Error al eliminar el comentario']);
        }
    }
}
