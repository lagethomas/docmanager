<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Envio de e-mails via SMTP (PHPMailer) utilizando as credenciais salvas no .env.
 * Falhas nunca interrompem o fluxo de negócio — tudo é logado em storage/logs/email.log.
 */
class EmailService
{
    private const LOG_FILE = '/storage/logs/email.log';

    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::log('skipped', "Destinatario invalido ou vazio: '{$toEmail}'", $subject);
            Logger::log('email_skipped', "E-mail invalido ou ausente: '{$toEmail}' | assunto: {$subject}");
            return false;
        }

        $host = trim(defined('SMTP_HOST') ? (string) SMTP_HOST : (string) ($_ENV['SMTP_HOST'] ?? ''));
        $port = defined('SMTP_PORT') ? (int) SMTP_PORT : (int) ($_ENV['SMTP_PORT'] ?? 25);
        self::log('attempt', "Para: {$toEmail} | host={$host} | port={$port}", $subject);

        // 1) Envio via SMTP (credenciais do .env)
        if ($host !== '') {
            try {
                if (self::sendViaSmtp($toEmail, $subject, $htmlBody)) {
                    self::log('sent_smtp', "OK via SMTP para {$toEmail}", $subject);
                    return true;
                }
                self::log('smtp_failed', "PHPMailer retornou false para {$toEmail}", $subject);
            } catch (\Throwable $e) {
                self::log('smtp_error', get_class($e) . ': ' . $e->getMessage() . " | para: {$toEmail}", $subject);
                Logger::log('email_error', 'Falha SMTP: ' . $e->getMessage() . ' | assunto: ' . $subject);
            }
        } else {
            self::log('smtp_skipped', 'SMTP_HOST vazio no .env', $subject);
        }

        // 2) Fallback: mail() nativo do PHP
        $ok = self::sendViaMail($toEmail, $subject, $htmlBody);
        if ($ok) {
            self::log('sent_mail', "OK via mail() para {$toEmail}", $subject);
            return true;
        }

        self::log('failed', "Falhou SMTP e mail() para {$toEmail}", $subject);
        return false;
    }

    /**
     * URL absoluta para links em e-mails. Usa SITE_URL do env; fallback para o host da requisição atual.
     */
    public static function absoluteUrl(string $path): string
    {
        $base = rtrim(defined('SITE_URL') ? (string) SITE_URL : (string) ($_ENV['SITE_URL'] ?? ''), '/');
        if ($base === '' && isset($_SERVER['HTTP_HOST'])) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $base = ($https ? 'https' : 'http') . '://' . (string) $_SERVER['HTTP_HOST'];
        }
        return $base . $path;
    }

    /**
     * Trecho curto do conteúdo Markdown para o corpo do e-mail.
     */
    public static function excerpt(string $markdown, int $maxLen = 220): string
    {
        $text = $markdown;
        try {
            $parsedown = new \Parsedown();
            $html = $parsedown->text($markdown);
            $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } catch (\Throwable) {
            $text = preg_replace('/[#*_`>\-\[\]()!]/u', ' ', $markdown) ?? $markdown;
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
            $text = trim($text);
        }

        if (mb_strlen($text) > $maxLen) {
            $text = rtrim(mb_substr($text, 0, $maxLen)) . '…';
        }
        return $text;
    }

    /**
     * Renderiza um template de src/Views/emails/.
     *
     * @param array<string, mixed> $data
     */
    public static function renderTemplate(string $name, array $data = []): string
    {
        $file = ROOT_DIR . '/src/Views/emails/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Template de e-mail nao encontrado: {$name}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private static function sendViaSmtp(string $toEmail, string $subject, string $htmlBody): bool
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            self::log('smtp_error', 'PHPMailer nao instalado (vendor)', $subject);
            return false;
        }

        $host = trim(defined('SMTP_HOST') ? (string) SMTP_HOST : (string) ($_ENV['SMTP_HOST'] ?? ''));
        if ($host === '') {
            return false;
        }

        $port = defined('SMTP_PORT') ? (int) SMTP_PORT : (int) ($_ENV['SMTP_PORT'] ?? 25);
        $encryption = strtolower(defined('SMTP_ENCRYPTION') ? (string) SMTP_ENCRYPTION : (string) ($_ENV['SMTP_ENCRYPTION'] ?? 'tls'));
        $user = defined('SMTP_USER') ? (string) SMTP_USER : (string) ($_ENV['SMTP_USER'] ?? '');
        $pass = defined('SMTP_PASS') ? (string) SMTP_PASS : (string) ($_ENV['SMTP_PASS'] ?? '');
        $definedFrom = defined('SMTP_FROM') ? (string) SMTP_FROM : '';
        $from = $definedFrom !== '' ? $definedFrom : ($user !== '' ? $user : 'nao-responda@docmanager.local');
        $fromName = defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : (string) ($_ENV['SMTP_FROM_NAME'] ?? 'DocManager');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port > 0 ? $port : 25;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Timeout = 10;

        // Configuração explícita de SSL/TLS para evitar erros de handshake com o PHP OpenSSL
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ],
        ];

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        if ($user !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            // Se for App Password com espaços (ex: Gmail "gazv itcy..."), remove espaços
            $mail->Password = str_contains($host, 'gmail') ? str_replace(' ', '', $pass) : $pass;
        }

        $mail->setFrom($from, $fromName);
        $mail->addAddress($toEmail);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;

        try {
            return $mail->send();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Could not authenticate') || str_contains($msg, 'Authentication unsuccessful')) {
                $msg .= ' (535 5.7.3: Habilite SMTP AUTH para a conta no Microsoft 365 Admin Center ou use App Password)';
            } elseif (str_contains($msg, 'Connection reset by peer')) {
                $msg .= ' (Nota de Rede: O firewall da empresa Fortinet/FortiGate bloqueia conexões SMTP externas TLS para este provedor. Utilize o Office 365 com SMTP AUTH ativo)';
            }
            self::log('smtp_error', get_class($e) . ': ' . $msg . " | para: {$toEmail}", $subject);
            Logger::log('email_error', 'Falha SMTP: ' . $msg . ' | assunto: ' . $subject);
            return false;
        }
    }

    private static function sendViaMail(string $toEmail, string $subject, string $htmlBody): bool
    {
        $fromName = defined('SMTP_FROM_NAME') ? (string) SMTP_FROM_NAME : 'DocManager';
        $definedFrom = defined('SMTP_FROM') ? (string) SMTP_FROM : '';
        $from = $definedFrom !== '' ? $definedFrom : 'nao-responda@docmanager.local';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
        $headers[] = 'Reply-To: ' . $from;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        try {
            $ok = @mail($toEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
            if (!$ok) {
                $error = error_get_last();
                $errStr = (string) ($error['message'] ?? 'sendmail nao instalado ou falhou');
                self::log('mail_false', "mail() retornou false para {$toEmail} | erro: {$errStr}", $subject);
                Logger::log('email_error', "mail() retornou false para {$toEmail} ({$errStr}) | assunto: {$subject}");
            }
            return $ok;
        } catch (\Throwable $e) {
            self::log('mail_error', get_class($e) . ': ' . $e->getMessage(), $subject);
            Logger::log('email_error', 'mail() excecao: ' . $e->getMessage());
            return false;
        }
    }

    private static function log(string $level, string $message, string $subject = ''): void
    {
        $line = sprintf(
            "[%s] %-12s | %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $subject !== '' ? ' | assunto: ' . $subject : ''
        );
        @file_put_contents(ROOT_DIR . self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}
