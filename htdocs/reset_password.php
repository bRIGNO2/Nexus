<?php
// ============================================================
//  reset_password.php  —  Nuova password tramite token
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

if (isLoggedIn()) { header('Location: wheel.php'); exit; }

$error   = '';
$success = '';
$token   = trim($_GET['token'] ?? '');
$valid   = false;
$userId  = null;

// Verifica token
if ($token) {
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT pr.id, pr.user_id, pr.expires_at, pr.used
        FROM password_resets pr
        WHERE pr.token = ?
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row && !$row['used'] && new DateTime() < new DateTime($row['expires_at'])) {
        $valid  = true;
        $userId = $row['user_id'];
    } else {
        $error = 'Link non valido o scaduto. Richiedi un nuovo reset.';
    }
}

// Salva nuova password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri.';
    } elseif ($password !== $password2) {
        $error = 'Le password non coincidono.';
    } else {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Aggiorna password
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
           ->execute([$hash, $userId]);

        // Invalida il token
        $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')
           ->execute([$token]);

        $success = 'Password aggiornata! Puoi ora accedere.';
        $valid   = false;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nuova password — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login.css"/>
  <style>
    .alert-error   { background:rgba(220,50,50,0.12); border:1px solid rgba(220,50,50,0.35); color:#ff6b6b; border-radius:10px; padding:12px 16px; font-size:0.85rem; margin-bottom:20px; }
    .alert-success { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7; border-radius:10px; padding:12px 16px; font-size:0.85rem; margin-bottom:20px; }
    .alert-success a { color:#6ee7b7; font-weight:600; }
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
        <h1>Crea una<br/>nuova<br/>password.</h1>
        <p>Scegli una password sicura di almeno 8 caratteri.</p>
      </div>
      <div class="stats">
        <div class="stat"><div class="stat-num">8+</div><div class="stat-label">Caratteri min</div></div>
        <div class="stat"><div class="stat-num">bcrypt</div><div class="stat-label">Algoritmo</div></div>
      </div>
    </div>

    <div class="panel-right">

      <div class="form-header">
        <div class="eyebrow">Nuova password</div>
        <h2>Reimposta password</h2>
      </div>

      <?php if ($error): ?>
        <div class="alert-error">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          <?php if (!$valid): ?>
            <br/><a href="forgot_password.php" style="color:#ff6b6b;font-weight:600;">← Richiedi nuovo link</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert-success">
          ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
          <br/><a href="login.php">Accedi ora →</a>
        </div>
      <?php elseif ($valid): ?>

        <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>">

          <div class="field">
            <label for="password">Nuova password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password"
                placeholder="Min. 8 caratteri" required/>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
          </div>

          <div class="field">
            <label for="password2">Conferma password</label>
            <div class="input-wrap">
              <input type="password" id="password2" name="password2"
                placeholder="Ripeti la password" required/>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
          </div>

          <button type="submit" class="btn-login">Salva nuova password →</button>

        </form>

      <?php elseif (!$token): ?>
        <p style="color:rgba(255,255,255,0.4);font-size:0.9rem;">
          Link non valido. <a href="forgot_password.php" style="color:#3E78B2;">Richiedi il reset →</a>
        </p>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>
