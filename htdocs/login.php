<?php
// ============================================================
//  login.php
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: wheel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $error = 'Richiesta non valida. Ricarica la pagina.';
    } else {
        $email    = $_POST['email']    ?? '';
        $password = $_POST['password'] ?? '';
        $result   = loginUser($email, $password);

        if ($result['success']) {
            header('Location: wheel.php');
            exit;
        } else {
            $error = $result['message'];
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
  <title>Login — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login2.css"/>
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
        <h1>Il tuo<br/>workspace<br/>ti aspetta.</h1>
        <p>Accedi alla piattaforma e riprendi da dove avevi lasciato, ovunque tu sia.</p>
      </div>
      <div class="stats">
        <div class="stat">
          <div class="stat-num">12k+</div>
          <div class="stat-label">Utenti attivi</div>
        </div>
        <div class="stat">
          <div class="stat-num">99.9%</div>
          <div class="stat-label">Uptime</div>
        </div>
        <div class="stat">
          <div class="stat-num">256‑bit</div>
          <div class="stat-label">Crittografia</div>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="panel-right">
      <form method="POST" action="login.php">

        <div class="form-header">
          <div class="eyebrow">Bentornato</div>
          <h2>Accedi all'account</h2>
        </div>

        <?php if ($error): ?>
          <div class="alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="nome@esempio.com"
              autocomplete="email"
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            />
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="m2 7 10 7 10-7"/>
            </svg>
          </div>
        </div>

        <div class="field">
          <div class="field-meta">
            <label for="password">Password</label>
            <a href="#" class="forgot" onclick="alert('Funzionalità in arrivo! Contatta l\'admin per il reset.'); return false;">Password dimenticata?</a>
          </div>
          <div class="input-wrap">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••••"
              autocomplete="current-password"
              required
            />
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
        </div>

        <div class="options-row">
          <label class="checkbox-wrap">
            <input type="checkbox" name="remember"/>
            <span>Ricordami</span>
          </label>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"/>

        <button type="submit" class="btn-login">Accedi →</button>

        <p style="text-align:center; margin-top:22px; font-size:0.82rem; color:rgba(255,255,255,0.3);">
          Non hai un account?
          <a href="register.php" style="color:#3E78B2; text-decoration:none; font-weight:600;">
            Registrati gratis
          </a>
        </p>

      </form>
    </div>

  </div>
</body>
</html>