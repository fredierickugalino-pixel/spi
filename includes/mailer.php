<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function password_reset_mail_settings(): array
{
    $localConfigPath = __DIR__ . '/../config/mail.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
    return [
        'host' => trim((string)(getenv('SPI_SMTP_HOST') ?: ($localConfig['host'] ?? ''))),
        'port' => (int)(getenv('SPI_SMTP_PORT') ?: ($localConfig['port'] ?? 587)),
        'username' => (string)(getenv('SPI_SMTP_USERNAME') ?: ($localConfig['username'] ?? '')),
        'password' => preg_replace('/\s+/', '', (string)(getenv('SPI_SMTP_PASSWORD') ?: ($localConfig['password'] ?? ''))),
        'encryption' => strtolower(trim((string)(getenv('SPI_SMTP_ENCRYPTION') ?: ($localConfig['encryption'] ?? 'tls')))),
        'from' => trim((string)(getenv('SPI_MAIL_FROM') ?: ($localConfig['from'] ?? ($localConfig['username'] ?? '')))),
        'from_name' => (string)(getenv('SPI_MAIL_FROM_NAME') ?: ($localConfig['from_name'] ?? 'St. Peregrine Institute')),
    ];
}

function password_reset_mail_is_configured(): bool
{
    $settings = password_reset_mail_settings();
    foreach (['host', 'username', 'password', 'from'] as $key) {
        if (stripos($settings[$key], 'YOUR_') !== false || stripos($settings[$key], 'PASTE_') !== false) {
            return false;
        }
    }
    return $settings['host'] !== '' && $settings['from'] !== ''
        && (($settings['username'] === '' && $settings['password'] === '')
            || ($settings['username'] !== '' && $settings['password'] !== ''));
}

function send_password_reset_pin_email(string $recipient, string $pin): void
{
    $settings = password_reset_mail_settings();
    if (!password_reset_mail_is_configured()) {
        throw new RuntimeException('SMTP host and sender email must be configured.');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $settings['host'];
    $mailer->Port = $settings['port'];
    $mailer->SMTPAuth = $settings['username'] !== '' || $settings['password'] !== '';
    if ($mailer->SMTPAuth) {
        $mailer->Username = $settings['username'];
        $mailer->Password = $settings['password'];
    }

    if ($settings['encryption'] === 'tls') {
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($settings['encryption'] === 'ssl') {
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($settings['encryption'] !== 'none') {
        throw new RuntimeException('SPI_SMTP_ENCRYPTION must be tls, ssl, or none.');
    }

    $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($settings['from'], $settings['from_name']);
    $mailer->addAddress($recipient);
    $mailer->isHTML(true);
    $mailer->Subject = 'SPI password reset PIN';
    $mailer->Body = '<p>We received a request to reset your SPI password.</p><p>Your four-digit reset PIN is:</p><p style="font-size:28px;font-weight:bold;letter-spacing:8px">'.htmlspecialchars($pin, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p><p>This PIN expires in one hour and can be tried up to five times. If you did not request this, you can ignore this message.</p>';
    $mailer->AltBody = "We received a request to reset your SPI password.\n\nYour four-digit reset PIN is: ".$pin."\n\nThis PIN expires in one hour and can be tried up to five times. If you did not request this, you can ignore this message.";
    $mailer->send();
}
