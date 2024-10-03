<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    // Remitente del correo
    public string $fromEmail  = 'denuncias@eqqua.mx';  // Correo de remitente
    public string $fromName   = 'Eqqua Denuncias';     // Nombre de remitente
    public string $recipients = '';                    // Opcional, destinatarios generales

    // Agente de usuario
    public string $userAgent = 'CodeIgniter';

    // Protocolo de correo
    public string $protocol = 'smtp';                 // Cambiado a SMTP para usar Mailtrap

    // Ruta de sendmail (no es necesaria para SMTP, pero se deja por si cambias en el futuro)
    public string $mailPath = '/usr/sbin/sendmail';

    // Configuración del servidor SMTP (Mailtrap)
    public string $SMTPHost = 'sandbox.smtp.mailtrap.io';
    public string $SMTPUser = 'a73fbb37618f88';        // Tu usuario de Mailtrap
    public string $SMTPPass = '0d353759a87d0a';        // Tu contraseña de Mailtrap
    public int $SMTPPort = 2525;                       // Puerto SMTP de Mailtrap

    // Timeout del SMTP
    public int $SMTPTimeout = 10;                      // Tiempo de espera ajustado a 10 segundos

    // Conexiones persistentes
    public bool $SMTPKeepAlive = false;

    // Cifrado SMTP
    public string $SMTPCrypto = 'tls';                 // Cifrado TLS

    // Configuración de WordWrap
    public bool $wordWrap = true;
    public int $wrapChars = 76;

    // Tipo de correo: Enviar correos como HTML
    public string $mailType = 'html';                  // Envío en formato HTML

    // Charset
    public string $charset = 'UTF-8';                  // UTF-8 para compatibilidad

    // Validar correos
    public bool $validate = true;                      // Habilitar validación de correos

    // Prioridad del correo
    public int $priority = 3;                          // Prioridad estándar

    // Caracteres para nueva línea
    public string $CRLF = "\r\n";
    public string $newline = "\r\n";                   // Ajuste importante para SMTP

    // Modo BCC por lotes
    public bool $BCCBatchMode = false;
    public int $BCCBatchSize = 200;

    // Notificación del servidor (DSN)
    public bool $DSN = false;
}
