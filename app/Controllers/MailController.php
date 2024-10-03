<?php

namespace App\Controllers;

use App\Services\EmailService;
use CodeIgniter\Controller;

class MailController extends Controller
{
    public function sendEmail()
    {
        $emailService = new EmailService();
        // Información del correo
        $to = 'cliente@example.com';
        $subject = 'Notificación de Denuncia';
        $message = '<p>Estimado cliente,</p><p>Su denuncia ha sido registrada exitosamente.</p>';

        // Enviar correo
        $resultado = $emailService->sendEmail($to, $subject, $message);

        // Verificar el resultado
        if ($resultado === true) {
            echo 'Correo enviado exitosamente';
        } else {
            echo 'Error al enviar correo: ' . $resultado;
        }
    }
}
