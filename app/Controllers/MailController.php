<?php

namespace App\Controllers;

use App\Services\EmailService;
use CodeIgniter\Controller;

class MailController extends Controller
{
    public function sendEmail()
    {
        // Instanciar el servicio de email
        $emailService = new EmailService();

        // Información del correo
        $to = '0013zkr@gmail.com';  // Cambia esto por un correo válido
        $subject = 'Prueba de Envío con Mailgun';
        $message = '<p>Este es un correo de prueba enviado usando <strong>Mailgun</strong> con CodeIgniter 4.</p>';

        // Enviar el correo
        $resultado = $emailService->sendEmail($to, $subject, $message);

        // Mostrar el resultado
        if ($resultado === true) {
            echo 'Correo enviado exitosamente';
        } else {
            echo 'Error al enviar correo: ' . $resultado;
        }
    }
}
