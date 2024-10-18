<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    // Remitente del correo
    public string $fromEmail  = 'notificaciones@eqqua.mx';  // Tu correo de Mailgun
    public string $fromName   = 'Eqqua Denuncias';          // Nombre del remitente
    public string $recipients = '';                        // Destinatarios generales opcionales

    // Agente de usuario
    public string $userAgent = 'CodeIgniter';

    // Protocolo de correo
    public string $protocol = 'smtp';  // Usaremos SMTP con Mailgun

    // Ruta de sendmail (no es necesaria para SMTP, pero se deja por si cambias en el futuro)
    public string $mailPath = '/usr/sbin/sendmail';

    // Configuración del servidor SMTP de Mailgun
    public string $SMTPHost = 'smtp.mailgun.org';        // Servidor SMTP de Mailgun
    public string $SMTPUser = 'notificaciones@eqqua.mx'; // Tu usuario (correo de Mailgun)
    public string $SMTPPass = 'Rrasec13!';               // Contraseña SMTP de Mailgun
    public int $SMTPPort = 587;                          // Puerto SMTP recomendado para TLS (puedes usar 25 o 465 si es necesario)

    // Timeout del SMTP
    public int $SMTPTimeout = 10;  // Tiempo de espera ajustado a 10 segundos

    // Conexiones persistentes
    public bool $SMTPKeepAlive = false;

    // Cifrado SMTP
    public string $SMTPCrypto = 'tls';  // TLS (cifrado recomendado)

    // Configuración de WordWrap
    public bool $wordWrap = true;
    public int $wrapChars = 76;

    // Tipo de correo: Enviar correos como HTML
    public string $mailType = 'html';  // Enviar en formato HTML

    // Charset
    public string $charset = 'UTF-8';  // Charset UTF-8 para compatibilidad

    // Validar correos
    public bool $validate = true;  // Habilitar validación de correos

    // Prioridad del correo
    public int $priority = 3;  // Prioridad estándar

    // Caracteres para nueva línea (ajuste importante para SMTP)
    public string $CRLF = "\r\n";
    public string $newline = "\r\n";

    // Modo BCC por lotes (opcional, útil para muchos correos)
    public bool $BCCBatchMode = false;
    public int $BCCBatchSize = 200;

    // Notificación del servidor (DSN)
    public bool $DSN = false;
}
