<?php

namespace App\Services;

use CodeIgniter\Config\Services;

class EmailService
{
    public function sendEmail(string $to, string $subject, string $message, string $from = null, string $fromName = null)
    {
        $email = Services::email();

        if (ENVIRONMENT === 'development') {
            $email->initialize([
                'protocol'    => 'smtp',
                'SMTPHost'    => 'sandbox.smtp.mailtrap.io',
                'SMTPUser'    => 'ab90aa809a743d',
                'SMTPPass'    => '62fe538bcae16b',
                'SMTPPort'    => 2525,
                'SMTPCrypto'  => 'tls',
                'mailType'    => 'html',
                'charset'     => 'UTF-8',
                'wordWrap'    => true,
                'newline'     => "\r\n",
                'CRLF'        => "\r\n"
            ]);
        } else {
            $email->initialize(config('Email'));
        }

        $from = $from ?? config('Email')->fromEmail;
        $fromName = $fromName ?? config('Email')->fromName;

        $email->setFrom($from, $fromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);

        return $email->send() ?: $email->printDebugger(['headers']);
    }
}
