<?php
// ============================================================
//  mailer.php  —  Invio email tramite PHPMailer + Gmail SMTP
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $mail = new PHPMailer(true);

    try {
        // Server SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Mittente e destinatario
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Contenuto
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

// ------------------------------------------------------------
//  Template email reset password
// ------------------------------------------------------------
function sendResetEmail(string $toEmail, string $toName, string $resetLink): bool {
    $subject = 'Reset password — Nexus Music';

    $html = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"/></head>
<body style="margin:0;padding:0;background:#07070A;font-family:sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="padding:40px 20px;">
      <table width="520" cellpadding="0" cellspacing="0"
             style="background:#24272B;border-radius:16px;overflow:hidden;border:1px solid rgba(62,120,178,0.2);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#004BA8,#3E78B2);padding:32px 40px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:0.1em;text-transform:uppercase;">
              ⚡ Nexus Music
            </div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="color:#fff;font-size:1.1rem;font-weight:700;margin:0 0 12px;">
              Ciao, ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . '!
            </p>
            <p style="color:rgba(255,255,255,0.6);font-size:0.92rem;line-height:1.6;margin:0 0 28px;">
              Hai richiesto il reset della password per il tuo account Nexus Music.
              Clicca il pulsante qui sotto per crearne una nuova.
              Il link è valido per <strong style="color:#fff;">30 minuti</strong>.
            </p>

            <div style="text-align:center;margin-bottom:28px;">
              <a href="' . $resetLink . '"
                 style="display:inline-block;padding:14px 36px;
                        background:linear-gradient(135deg,#004BA8,#3E78B2);
                        color:#fff;font-weight:700;font-size:0.95rem;
                        border-radius:10px;text-decoration:none;
                        letter-spacing:0.04em;">
                Reimposta password →
              </a>
            </div>

            <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;line-height:1.5;margin:0;">
              Se non hai richiesto il reset, ignora questa email.<br/>
              Il link scadrà automaticamente tra 30 minuti.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:20px 40px;border-top:1px solid rgba(255,255,255,0.06);
                     text-align:center;color:rgba(255,255,255,0.2);font-size:0.75rem;">
            Nexus Music — nexus-music.xo.je
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';

    return sendMail($toEmail, $toName, $subject, $html);
}
