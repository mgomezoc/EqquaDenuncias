<?php

namespace App\Services;

use CodeIgniter\Config\Services;

class EmailService
{
    /**
     * Enviar un correo electrónico con los detalles proporcionados.
     *
     * @param string $to          Destinatario del correo
     * @param string $subject     Asunto del correo
     * @param string $message     Cuerpo del correo (HTML)
     * @param string|null $from   Remitente (opcional, por defecto Eqqua Denuncias)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool|string        Devuelve true si se envía correctamente o un mensaje de error
     */
    public function sendEmail(string $to, string $subject, string $message, string $from = null, string $fromName = null)
    {
        $email = Services::email();

        // Usar remitente por defecto si no se proporciona uno
        $from = $from ?? 'denuncias@eqqua.mx';
        $fromName = $fromName ?? 'Eqqua Denuncias';

        // Configurar el correo
        $email->setFrom($from, $fromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message); // HTML message

        // Enviar correo y retornar el resultado
        if ($email->send()) {
            return true; // Envío exitoso
        } else {
            // Ocurrió un error al enviar, devolver información de depuración
            return $email->printDebugger(['headers']);
        }
    }
}
