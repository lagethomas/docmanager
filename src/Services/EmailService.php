<?php
declare(strict_types=1);

namespace App\Services;

class EmailService
{
    /**
     * Envia um e-mail em formato HTML usando as configurações do .env.
     */
    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        if ($toEmail === '') {
            return false;
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>';
        $headers[] = 'Reply-To: ' . SMTP_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        // Se houver host SMTP configurado para conexões diretas via socket (ex: Mailtrap/SendGrid)
        // pode utilizar fsockopen ou a função mail() padronizada do PHP com cabeçalhos MIME.
        try {
            return @mail($toEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            return false;
        }
    }
}
