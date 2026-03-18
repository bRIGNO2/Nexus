<?php
// ============================================================
//  forgot_password.php  —  Richiesta reset password
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

startSecureSession();

if (isLoggedIn()) { header('Location: wheel.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $error = 'Richiesta non valida. Ricarica la pagina.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserisci un indirizzo email valido.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id, username FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Risposta generica per sicurezza (no user enumeration)
            $success = 'Se questa email è registrata, riceverai le istruzioni a breve.';

            if ($user) {
                // Invalida token precedenti
                $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

                // Genera token sicuro
                $token     = bin2hex(random_bytes(32));
                $expiresAt = (new DateTime())->modify('+30 minutes')->format('Y-m-d H:i:s');

                $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
                   ->execute([$user['id'], $token, $expiresAt]);

                $resetLink = BASE_URL . '/reset_password.php?token=' . $token;
                sendResetEmail($email, $user['username'], $resetLink);
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Password dimenticata — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login.css"/>
  <style>
    .alert-error   { background:rgba(220,50,50,0.12); border:1px solid rgba(220,50,50,0.35); color:#ff6b6b; border-radius:10px; padding:12px 16px; font-size:0.85rem; margin-bottom:20px; }
    .alert-success { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7; border-radius:10px; padding:12px 16px; font-size:0.85rem; margin-bottom:20px; }
  </style>
</head>
<body>
  <div class="bg"></div>
  <div class="grid-overlay"></div>

  <div class="wrapper">
    <div class="panel-left">
      <div class="brand">
        <div class="brand-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
          </svg>
        </div>
        <span class="brand-name">Nexus</span>
      </div>
      <div class="panel-headline">
        <h1>Recupera<br/>il tuo<br/>account.</h1>
        <p>Inserisci la tua email e ti mandiamo un link per reimpostare la password.</p>
      </div>
      <div class="stats">
        <div class="stat"><div class="stat-num">30'</div><div class="stat-label">Validità link</div></div>
        <div class="stat"><div class="stat-num">100%</div><div class="stat-label">Sicuro</div></div>
      </div>
    </div>

    <div class="panel-right">
      <form method="POST" action="forgot_password.php">

        <div class="form-header">
          <div class="eyebrow">Recupero account</div>
          <h2>Password dimenticata?</h2>
        </div>

        <?php if ($error): ?>
          <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input type="email" id="email" name="email" placeholder="nome@esempio.com"
              autocomplete="email" required
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"/>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/>
            </svg>
          </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"/>

        <button type="submit" class="btn-login">Invia link di reset →</button>

        <p class="signup-line" style="margin-top:20px;">
          Ricordi la password? <a href="login.php">Accedi</a>
        </p>

      </form>
    </div>
  </div>
</body>
</html>
