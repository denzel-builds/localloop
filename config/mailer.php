<?php
// ============================================================
// LocalLoop — PHPMailer Configuration
// ============================================================

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('MAIL_USERNAME',  'localloopsa@gmail.com');       
define('MAIL_PASSWORD',  'htjw sdxp amvq ooqp');       
define('MAIL_FROM_NAME', 'LocalLoop');

/**
 * Send a password reset email to a user.
 * Returns true on success, false on failure.
 */
function sendResetEmail(string $to_email, string $to_name, string $reset_link): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your LocalLoop Password';
        $mail->Body    = buildResetEmailHTML($to_name, $reset_link);
        $mail->AltBody = "Hi $to_name,\n\nReset your LocalLoop password using this link (expires in 1 hour):\n$reset_link\n\nIf you did not request this, ignore this email.\n\n— The LocalLoop Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('LocalLoop Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Build the HTML email body for the reset email.
 */
function buildResetEmailHTML(string $name, string $link): string {
    $year = date('Y');
    $safe_name = htmlspecialchars($name);
    $safe_link = htmlspecialchars($link);
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Arial,sans-serif;background:#f8faf9;margin:0;padding:20px;">
    <div style="max-width:540px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <div style="background:#1C1C2E;padding:28px 32px;text-align:center;">
            <div style="font-size:1.6rem;font-weight:700;color:#fff;">
                Local<span style="color:#F4A623;">Loop</span>
            </div>
            <div style="color:rgba(255,255,255,.5);font-size:13px;margin-top:4px;">
                Your Community Marketplace
            </div>
        </div>
        <div style="padding:36px 32px;">
            <h2 style="margin:0 0 16px;color:#1C1C2E;">Hi $safe_name,</h2>
            <p style="color:#555;line-height:1.7;margin:0 0 24px;">
                We received a request to reset the password for your LocalLoop account. 
                Click the button below to set a new password. This link is valid for 
                <strong>1 hour</strong>.
            </p>
            <div style="text-align:center;margin:32px 0;">
                <a href="$link" 
                   style="background:#1A7A5C;color:#fff;padding:14px 36px;border-radius:10px;
                          text-decoration:none;font-weight:700;font-size:15px;display:inline-block;">
                    Reset My Password
                </a>
            </div>
            <p style="color:#999;font-size:13px;line-height:1.6;margin:0 0 12px;">
                If you did not request a password reset, you can safely ignore this email. 
                Your password will not change.
            </p>
            <p style="color:#bbb;font-size:12px;word-break:break-all;margin:0;">
                Link not working? Copy and paste this into your browser:<br>
                $safe_link
            </p>
        </div>
        <div style="background:#f8faf9;padding:20px 32px;text-align:center;border-top:1px solid #eee;">
            <p style="color:#bbb;font-size:12px;margin:0;">
                &copy; $year LocalLoop &middot; Built for South Africa's informal economy
            </p>
        </div>
    </div>
    </body>
    </html>
HTML;
}
?>