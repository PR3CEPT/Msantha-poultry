<?php

declare(strict_types=1);

/**
 * helpers/mailer.php
 * Msantha Investments & General Suppliers (MIGS) — Gmail SMTP mailer using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ─── SMTP Configuration ────────────────────────────────────────────────────
if (!defined('MAIL_FROM'))      define('MAIL_FROM',      $_ENV['MAIL_FROM_ADDRESS'] ?? 'chipetaisaac@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Msantha Investments and General Suppliers (MIGS)');
if (!defined('MAIL_HOST'))      define('MAIL_HOST',      $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
if (!defined('MAIL_PORT'))      define('MAIL_PORT',      (int)($_ENV['MAIL_PORT'] ?? 587));
if (!defined('MAIL_USER'))      define('MAIL_USER',      $_ENV['MAIL_USERNAME'] ?? 'chipetaisaac@gmail.com');
if (!defined('MAIL_PASS'))      define('MAIL_PASS',      $_ENV['MAIL_PASSWORD'] ?? 'whkg peqw mnjq afhu');
if (!defined('SITE_URL'))       define('SITE_URL',       $_ENV['APP_URL'] ?? 'http://localhost');

/**
 * Low-level SMTP send (raw HTML body).
 */
function _smtpSend(string $to, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

        $logoPath = dirname(__DIR__, 2) . '/public/assets/img/logo.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'msanthalogo');
        }

        $mail->send();
        $sent = true;
    } catch (Exception $e) {
        $sent = false;
        _mailLog($to, $subject, 'FAILED: ' . $mail->ErrorInfo);
    }

    _mailLog($to, $subject, $sent ? 'SENT' : 'FAILED');
    return $sent;
}

/**
 * Build branded HTML email template for Msantha Poultry.
 */
function buildEmailTemplate(string $preheader, string $bodyHtml): string
{
    $year = date('Y');
    $logo = 'cid:msanthalogo';
    $site = SITE_URL;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Msantha Investments (MIGS)</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background:#f4f6f5;font-family:'Plus Jakarta Sans','Helvetica Neue',Arial,sans-serif}
  table{border-spacing:0}
  img{border:0;outline:none;text-decoration:none;display:block}
  a{color:#1b6d39}
  .wrapper{width:100%;background:#f4f6f5;padding:32px 0}
  .email-card{max-width:600px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)}
  .header{background:linear-gradient(135deg,#0b2914 0%,#1b6d39 100%);padding:28px 40px;text-align:center}
  .header img{height:64px;margin:0 auto 10px}
  .header-title{color:#ffffff;font-size:13px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin:0}
  .content{padding:36px 40px}
  .content h1{margin:0 0 20px;font-size:22px;font-weight:700;color:#1e293b}
  .content p{margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569}
  .detail-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin:20px 0}
  .detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e2e8f0;font-size:14px}
  .detail-row:last-child{border-bottom:none}
  .detail-label{color:#64748b;font-weight:600}
  .detail-value{color:#1e293b;font-weight:700;text-align:right}
  .btn{display:inline-block;background:#1b6d39;color:#ffffff!important;text-decoration:none;padding:13px 28px;border-radius:8px;font-weight:700;font-size:14px;margin:16px 0}
  .footer{background:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0}
  .footer p{margin:0;font-size:12px;color:#94a3b8;line-height:1.8}
  .footer a{color:#1b6d39;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<span style="display:none;max-height:0;overflow:hidden;">{$preheader}</span>
<div class="wrapper">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
  <tr><td>
  <div class="email-card">
    <div class="header">
      <img src="{$logo}" alt="MIGS Logo">
      <p class="header-title">Msantha Investments and General Suppliers (MIGS)</p>
    </div>
    <div class="content">
      {$bodyHtml}
    </div>
    <div class="footer">
      <p><strong>Msantha Investments and General Suppliers</strong><br>
         Liwonde, Mawira Area by Chipamba Road, Machinga District, Malawi<br>
         <a href="tel:+265888899620">+265 8888 996 20</a> &nbsp;|&nbsp;
         <a href="tel:+265999899620">+265 9998 996 20</a>
      </p>
      <p style="margin-top:12px;font-size:11px;color:#cbd5e1;">
        Automated executive notification &bull; &copy; {$year} Msantha Investments. All rights reserved.
      </p>
    </div>
  </div>
  </td></tr>
  </table>
</div>
</body>
</html>
HTML;
}

/**
 * Main public function — sendEmail().
 */
function sendEmail(string $to, string $subject, string $body): bool
{
    if (empty(trim($to))) return false;

    if (strip_tags($body) === $body) {
        $bodyHtml = '<p>' . nl2br(htmlspecialchars($body)) . '</p>';
    } else {
        $bodyHtml = $body;
    }

    $html = buildEmailTemplate(strip_tags($body), $bodyHtml);
    return _smtpSend($to, "[Msantha] {$subject}", $html);
}

/**
 * Send a rich structured email with detail rows.
 */
function sendStructuredEmail(
    string $to,
    string $subject,
    string $heading,
    string $intro,
    array  $details    = [],
    string $ctaText    = '',
    string $ctaUrl     = '',
    string $footerNote = ''
): bool {
    if (empty(trim($to))) return false;

    $detailRows = '';
    foreach ($details as $label => $value) {
        $detailRows .= "
        <div class='detail-row'>
            <span class='detail-label'>" . htmlspecialchars((string)$label) . "</span>
            <span class='detail-value'>" . htmlspecialchars((string)$value) . "</span>
        </div>";
    }

    $detailBox = $detailRows ? "<div class='detail-box'>{$detailRows}</div>" : '';
    $cta = ($ctaText && $ctaUrl)
        ? "<div style='text-align:center;margin:24px 0;'><a class='btn' href='{$ctaUrl}'>{$ctaText}</a></div>"
        : '';
    $footerHtml = $footerNote
        ? "<p style='font-size:13px;color:#94a3b8;margin-top:20px;'>{$footerNote}</p>"
        : '';

    $bodyHtml = "
        <h1>{$heading}</h1>
        <p>{$intro}</p>
        {$detailBox}
        {$cta}
        {$footerHtml}
    ";

    $html = buildEmailTemplate($heading . ' — ' . $intro, $bodyHtml);
    return _smtpSend($to, "[Msantha] {$subject}", $html);
}

/**
 * Log email events to storage/logs/emails.log
 */
function _mailLog(string $to, string $subject, string $status): void
{
    $logDir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    @file_put_contents(
        $logDir . '/emails.log',
        '[' . date('Y-m-d H:i:s') . "] TO:{$to} | {$subject} | {$status}\n",
        FILE_APPEND
    );
}

/**
 * Global sendSMS() function matching exact user specifications.
 */
function sendSMS(string $phone, string $message): bool
{
    $provider = new \App\Services\SMS\AfricasTalkingProvider();
    $res = $provider->send($phone, $message);
    return !empty($res['success']);
}
