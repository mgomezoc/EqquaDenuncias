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
     * @param string|null $from   Remitente (opcional)
     * @param string|null $fromName Nombre del remitente (opcional)
     * @return bool|string        Devuelve true si se envía correctamente o un mensaje de error
     */
    public function sendEmail(string $to, string $subject, string $message, string $from = null, string $fromName = null)
    {
        // Cargar el servicio de email de CodeIgniter
        $email = Services::email();

        // Usar remitente por defecto si no se proporciona uno
        $from = $from ?? config('Email')->fromEmail;
        $fromName = $fromName ?? config('Email')->fromName;

        // Configurar el correo
        $email->setFrom($from, $fromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);  // Mensaje en formato HTML

        // Enviar correo y retornar el resultado
        if ($email->send()) {
            return true;
        } else {
            // Devolver el error si el correo falla
            return $email->printDebugger(['headers']);
        }
    }
}
