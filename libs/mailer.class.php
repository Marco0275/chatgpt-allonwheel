<?php
// ============================================================
// libs/mailer.class.php
// Punto UNICO di invio e-mail dell'app. Astrae il trasporto:
//  - SMTP autenticato via PHPMailer  (se MAIL_TRANSPORT=smtp,
//    SMTP_HOST valorizzato e PHPMailer disponibile);
//  - altrimenti fallback su mail()   (comportamento identico a prima:
//    nessuna regressione finche' SMTP non e' configurato).
//
// CONFIG VIA ENVIRONMENT (come config/database.php — MAI credenziali
// nei file, MAI su Git). Variabili lette:
//   MAIL_TRANSPORT   'smtp' | 'mail'   (default 'mail')
//   SMTP_HOST        es. smtp.sendgrid.net
//   SMTP_PORT        es. 587            (default 587)
//   SMTP_USER        utente SMTP
//   SMTP_PASS        password SMTP
//   SMTP_ENCRYPTION  'tls' | 'ssl' | '' (default 'tls')
//   MAIL_FROM        es. info@allonwheel.com (default)
//   MAIL_FROM_NAME   es. All on Wheel Ltd   (default)
//
// Per ATTIVARE l'SMTP: installare PHPMailer (Composer:
//   composer require phpmailer/phpmailer  — oppure vendorizzare i file
//   in libs/PHPMailer/ e includerli) e impostare le env qui sopra.
// La classe rileva PHPMailer da sola via class_exists().
// ============================================================

// Vendoring PHPMailer (libs/PHPMailer/src): rende class_exists() vero
// senza Composer. Caricato solo se presente; nessun effetto se assente.
$__pm_dir = __DIR__ . '/PHPMailer/src/';
if (is_file($__pm_dir . 'PHPMailer.php') && !class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    require_once $__pm_dir . 'Exception.php';
    require_once $__pm_dir . 'PHPMailer.php';
    require_once $__pm_dir . 'SMTP.php';
}

class Mailer
{
    /** Config di invio, letta dall'environment. */
    private static function cfg(): array
    {
        return [
            'transport'  => getenv('MAIL_TRANSPORT') ?: 'mail',
            'host'       => getenv('SMTP_HOST') ?: '',
            'port'       => (int)(getenv('SMTP_PORT') ?: 587),
            'user'       => getenv('SMTP_USER') ?: '',
            'pass'       => getenv('SMTP_PASS') ?: '',
            'encryption' => getenv('SMTP_ENCRYPTION') !== false ? getenv('SMTP_ENCRYPTION') : 'tls',
            'from_email' => getenv('MAIL_FROM') ?: 'info@allonwheel.com',
            'from_name'  => getenv('MAIL_FROM_NAME') ?: 'All on Wheel Ltd',
        ];
    }

    /**
     * Invia una e-mail HTML. Ritorna true se accettata per l'invio.
     * $replyTo opzionale (es. e-mail dell'acquirente nelle RFQ).
     */
    public static function send(string $to, string $subject, string $htmlBody, string $replyTo = '', string $toName = ''): bool
    {
        $cfg = self::cfg();

        if ($cfg['transport'] === 'smtp' && $cfg['host'] !== '' && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendSmtp($cfg, $to, $subject, $htmlBody, $replyTo, $toName);
        }
        return self::sendMail($cfg, $to, $subject, $htmlBody, $replyTo);
    }

    /** Trasporto SMTP via PHPMailer (caricato da Composer o vendorizzato). */
    private static function sendSmtp(array $cfg, string $to, string $subject, string $htmlBody, string $replyTo, string $toName): bool
    {
        try {
            $cls  = 'PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $cls(true);
            $mail->isSMTP();
            $mail->Host     = $cfg['host'];
            $mail->Port     = $cfg['port'];
            $mail->SMTPAuth = ($cfg['user'] !== '');
            if ($cfg['user'] !== '') {
                $mail->Username = $cfg['user'];
                $mail->Password = $cfg['pass'];
            }
            if ($cfg['encryption'] !== '') {
                $mail->SMTPSecure = $cfg['encryption']; // 'tls' | 'ssl'
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to, $toName);
            if ($replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('[Allonwheel] SMTP send error: ' . $e->getMessage());
            return false;
        }
    }

    /** Fallback su mail() con header corretti (From sul dominio, Reply-To). */
    private static function sendMail(array $cfg, string $to, string $subject, string $htmlBody, string $replyTo): bool
    {
        $from     = $cfg['from_email'];
        $headers  = 'From: ' . self::encodeHeader($cfg['from_name']) . ' <' . $from . '>' . "\r\n";
        if ($replyTo !== '') {
            $headers .= 'Reply-To: ' . $replyTo . "\r\n";
        }
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

        return @mail($to, self::encodeHeader($subject), $htmlBody, $headers);
    }

    /** RFC 2047 solo se la stringa contiene byte non ASCII. */
    private static function encodeHeader(string $s): string
    {
        return preg_match('/[\x80-\xFF]/', $s)
            ? '=?UTF-8?B?' . base64_encode($s) . '?='
            : $s;
    }
}
