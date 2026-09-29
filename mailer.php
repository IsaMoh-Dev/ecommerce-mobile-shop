<?php
/**
 * Mailer — Isa & Dagi Mobile Shop
 * ─────────────────────────────────────────────────────────────────────────────
 * HOW IT WORKS:
 *   - On LOCALHOST (127.0.0.1 / ::1): emails are saved to logs/emails.log
 *     so you can review them without a real mail server.
 *   - On PRODUCTION: emails are sent using PHP mail() automatically.
 *     No extra setup needed for basic sending.
 *
 * TO USE GMAIL SMTP (recommended for reliable delivery):
 *   1. Go to your Google Account → Security → App Passwords
 *   2. Generate an App Password for "Mail"
 *   3. Set $use_smtp = true below
 *   4. Fill in $smtp_user and $smtp_pass
 *   5. Run: composer require phpmailer/phpmailer  (in this folder)
 * ─────────────────────────────────────────────────────────────────────────────
 */

function sendMail($to, $subject, $message, $from_name = 'Isa & Dagi Mobile Shop') {

    // ── SMTP CONFIG — values come from config.php ─────────────────────────────
    if (!defined('USE_SMTP')) {
        $config = __DIR__ . '/config.php';
        if (file_exists($config)) require_once $config;
    }
    $use_smtp   = defined('USE_SMTP')   ? USE_SMTP   : false;
    $smtp_host  = defined('SMTP_HOST')  ? SMTP_HOST  : 'smtp.gmail.com';
    $smtp_port  = defined('SMTP_PORT')  ? SMTP_PORT  : 587;
    $smtp_user  = defined('SMTP_USER')  ? SMTP_USER  : '';
    $smtp_pass  = defined('SMTP_PASS')  ? SMTP_PASS  : '';
    $from_email = $smtp_user;
    // ─────────────────────────────────────────────────────────────────────────

    // ── SMTP via PHPMailer ────────────────────────────────────────────────────
    if ($use_smtp) {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            // PHPMailer not installed — fall through to mail() fallback
            error_log('[Mailer] PHPMailer not found. Run: composer require phpmailer/phpmailer');
        } else {
            require_once $autoload;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtp_port;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom($smtp_user, $from_name);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->Body    = $message;
                $mail->send();
                _logEmail($to, $subject, $message, 'SMTP');
                return true;
            } catch (Exception $e) {
                error_log('[Mailer] SMTP error: ' . $e->getMessage());
                // Fall through to mail() fallback
            }
        }
    }

    // ── Localhost: log to file instead of sending ─────────────────────────────
    $is_local = in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])
             || in_array($_SERVER['HTTP_HOST']   ?? '', ['localhost', '127.0.0.1']);

    if ($is_local) {
        _logEmail($to, $subject, $message, 'LOG (localhost)');
        return true;
    }

    // ── Production: send via PHP mail() ──────────────────────────────────────
    $from_email_prod = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'isadagi.com');
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email_prod}>\r\n";
    $headers .= "Reply-To: {$from_email_prod}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $sent = @mail($to, $subject, $message, $headers);
    _logEmail($to, $subject, $message, $sent ? 'mail() sent' : 'mail() FAILED');
    return $sent;
}

/**
 * Internal helper — write email to log file
 */
function _logEmail($to, $subject, $message, $method = '') {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $entry = "=== " . date('Y-m-d H:i:s') . " [{$method}] ===\n"
           . "To:      {$to}\n"
           . "Subject: {$subject}\n"
           . "Message:\n{$message}\n"
           . str_repeat('-', 60) . "\n\n";
    file_put_contents($log_dir . '/emails.log', $entry, FILE_APPEND);
}
