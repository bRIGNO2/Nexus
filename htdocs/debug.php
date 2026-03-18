<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1 — PHP OK<br>";

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

echo "Step 2 — PHPMailer OK<br>";

require_once __DIR__ . '/config.php';
echo "Step 3 — Config OK<br>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);

    $mail->SMTPDebug  = 2; // debug verboso
    $mail->Debugoutput = function($str, $level) {
        echo "<small style='color:#888'>SMTP: " . htmlspecialchars($str) . "</small><br>";
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_USERNAME);

    $mail->isHTML(true);
    $mail->Subject = 'Test Nexus Music';
    $mail->Body    = '<h2>Test funzionante!</h2>';

    echo "Step 4 — Configurazione OK, invio...<br>";

    $mail->send();
    echo "<strong style='color:green'>✅ Email inviata! Controlla " . MAIL_USERNAME . "</strong><br>";

} catch (Exception $e) {
    echo "<strong style='color:red'>❌ Errore: " . htmlspecialchars($mail->ErrorInfo) . "</strong><br>";
}

echo "<p style='color:red'>⚠️ CANCELLA QUESTO FILE!</p>";
?>