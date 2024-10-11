<?php

namespace App\Controllers;

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

        // Obtener el estado actual de la denuncia
        $denuncia = $denunciaModel->find($id_denuncia);
        if (!$denuncia) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Denuncia no encontrada']);
        }

        $estado_denuncia = $denuncia['estado_actual'];
        $id_usuario = session()->get('id') ?? 18;

        // Guardar el comentario con el estado de la denuncia
        $data = [
            'id_denuncia' => $id_denuncia,
            'id_usuario' => $id_usuario, // ID del usuario que hace el comentario
            'contenido' => $contenido,
            'estado_denuncia' => $estado_denuncia,
        ];

        if ($comentarioModel->save($data)) {
            // Obtener usuarios involucrados
            $usuariosInvolucrados = $this->obtenerUsuariosInvolucrados($id_denuncia);

            // Enviar correos de notificación
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

        // Obtener los comentarios relacionados con la denuncia
        $comentarios = $comentarioModel->getComentariosByDenuncia($id_denuncia);

        return $this->response->setJSON($comentarios);
    }

    private function obtenerUsuariosInvolucrados($id_denuncia)
    {
        $usuarioModel = new UsuarioModel();
        $denunciaModel = new DenunciaModel();
        $usuarios = [];

        // Obtener el estado actual de la denuncia
        $denuncia = $denunciaModel->find($id_denuncia);
        if (!$denuncia) {
            return []; // Si no se encuentra la denuncia, regresar un array vacío
        }

        $estadoActual = $denuncia['estado_actual'];

        // 1. Obtener el agente o creador de la denuncia
        $creador = $usuarioModel->query("
        SELECT u.id, u.nombre_usuario, u.correo_electronico
        FROM denuncias d
        INNER JOIN usuarios u ON d.id_creador = u.id
        WHERE d.id = ?", [$id_denuncia])->getResultArray();

        $usuarios = array_merge($usuarios, $creador);

        // 2. Obtener supervisores de calidad que cambiaron el estado
        $supervisores = $usuarioModel->query("
        SELECT DISTINCT u.id, u.nombre_usuario, u.correo_electronico
        FROM seguimiento_denuncias sd
        INNER JOIN usuarios u ON sd.id_usuario = u.id
        WHERE sd.id_denuncia = ? AND u.rol_id = 3", [$id_denuncia])->getResultArray();

        $usuarios = array_merge($usuarios, $supervisores);

        // 3. Solo obtener clientes si el estado es 4, 5 o 6
        if (in_array($estadoActual, [4, 5, 6])) {
            $clientes = $usuarioModel->query("
            SELECT DISTINCT u.id, u.nombre_usuario, u.correo_electronico
            FROM denuncias d
            INNER JOIN relacion_clientes_usuarios rcu ON d.id_cliente = rcu.id_cliente
            INNER JOIN usuarios u ON rcu.id_usuario = u.id
            WHERE d.id = ? AND u.rol_id = 4", [$id_denuncia])->getResultArray();

            $usuarios = array_merge($usuarios, $clientes);
        }

        // Filtrar duplicados (por si un usuario está en más de una consulta)
        $usuariosUnicos = [];
        foreach ($usuarios as $usuario) {
            $usuariosUnicos[$usuario['id']] = $usuario;
        }

        return array_values($usuariosUnicos);
    }


    private function enviarCorreoNotificacion($email, $nombreUsuario, $denuncia, $contenidoComentario)
    {
        $emailService = new EmailService();

        // Crear el mensaje de notificación
        $mensaje = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nuevo Comentario en Denuncia</title>
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
                            <h1>Nuevo Comentario en la Denuncia ' . esc($denuncia['folio']) . '</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-content">
                            <p>Estimado/a <strong>' . esc($nombreUsuario) . '</strong>,</p>
                            <p>Se ha agregado un nuevo comentario en la denuncia con folio <strong>' . esc($denuncia['folio']) . '</strong>.</p>
                            <p><strong>Comentario:</strong> ' . esc($contenidoComentario) . '</p>
                            <p>Para más detalles, acceda a su cuenta en <a href="' . base_url() . '">Eqqua Denuncias</a>.</p>
                            <p>Saludos cordiales,<br><strong>Eqqua Denuncias</strong></p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        // Enviar el correo
        $emailService->sendEmail($email, 'Nuevo Comentario en Denuncia ' . esc($denuncia['folio']), $mensaje);
    }
}
