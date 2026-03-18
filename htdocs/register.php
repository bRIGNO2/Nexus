<?php
// ============================================================
//  register.php  —  Registrazione utente
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: wheel.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $error = 'Richiesta non valida. Ricarica la pagina.';
    } else {
        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = trim($_POST['password']  ?? '');
        $password2 = trim($_POST['password2'] ?? '');

        // Validazione
        if (empty($username) || empty($email) || empty($password) || empty($password2)) {
            $error = 'Compila tutti i campi.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'Username: 3-30 caratteri, solo lettere, numeri e underscore.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email non valida.';
        } elseif (strlen($password) < 8) {
            $error = 'La password deve essere di almeno 8 caratteri.';
        } elseif ($password !== $password2) {
            $error = 'Le password non coincidono.';
        } else {
            $db = getDB();

            // Controlla duplicati
            $check = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $check->execute([$email, $username]);

            if ($check->fetch()) {
                $error = 'Email o username già in uso.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $db->prepare('
                    INSERT INTO users (username, email, password_hash, role)
                    VALUES (?, ?, ?, "user")
                ');
                $ins->execute([$username, $email, $hash]);

                // Auto-login dopo registrazione
                startSecureSession();
                session_regenerate_id(true);
                $_SESSION['user_id']   = $db->lastInsertId();
                $_SESSION['username']  = $username;
                $_SESSION['role']      = 'user';
                $_SESSION['logged_in'] = true;
                $_SESSION['ip']        = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['ua']        = $_SERVER['HTTP_USER_AGENT'] ?? '';

                header('Location: wheel.php');
                exit;
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
  <title>Registrati — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login.css"/>
  <style>
    .alert-error {
      background: rgba(220,50,50,0.12);
      border: 1px solid rgba(220,50,50,0.35);
      color: #ff6b6b;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.85rem;
      margin-bottom: 20px;
    }
    .alert-success {
      background: rgba(16,185,129,0.12);
      border: 1px solid rgba(16,185,129,0.3);
      color: #6ee7b7;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.85rem;
      margin-bottom: 20px;
    }
    .alert-success a { color: #6ee7b7; font-weight: 600; }
  </style>
</head>
<body>
  <div class="bg"></div>
  <div class="grid-overlay"></div>

  <div class="wrapper">

    <!-- LEFT -->
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
        <h1>La tua<br/>ruota<br/>musicale.</h1>
        <p>Crea il tuo account e inizia a costruire la tua collezione di album personale.</p>
      </div>
      <div class="stats">
        <div class="stat">
          <div class="stat-num">∞</div>
          <div class="stat-label">Album</div>
        </div>
        <div class="stat">
          <div class="stat-num">100%</div>
          <div class="stat-label">Tuo</div>
        </div>
        <div class="stat">
          <div class="stat-num">Gratis</div>
          <div class="stat-label">Per sempre</div>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="panel-right">
      <form method="POST" action="register.php">

        <div class="form-header">
          <div class="eyebrow">Nuovo account</div>
          <h2>Crea il tuo profilo</h2>
        </div>

        <?php if ($error): ?>
          <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a href="login.php">Accedi →</a></div>
        <?php endif; ?>

        <div class="field">
          <label for="username">Username</label>
          <div class="input-wrap">
            <input type="text" id="username" name="username" placeholder="il_tuo_nome" required
              value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"/>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
          </div>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input type="email" id="email" name="email" placeholder="nome@esempio.com" required
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"/>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/>
            </svg>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" placeholder="Min. 8 caratteri" required/>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
        </div>

        <div class="field">
          <label for="password2">Conferma Password</label>
          <div class="input-wrap">
            <input type="password" id="password2" name="password2" placeholder="Ripeti la password" required/>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"/>

        <button type="submit" class="btn-login" style="margin-top:8px;">Crea account →</button>

        <p class="signup-line" style="margin-top:20px;">
          Hai già un account? <a href="login.php">Accedi</a>
        </p>

      </form>
    </div>

  </div>
</body>
</html>