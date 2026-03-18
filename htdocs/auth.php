<?php
// ============================================================
//  auth.php  —  Logica di autenticazione
// ============================================================

require_once __DIR__ . '/db.php';

// ------------------------------------------------------------
//  Avvio sessione sicuro
// ------------------------------------------------------------
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => true,      // solo HTTPS
            'httponly' => true,      // non accessibile da JS
            'samesite' => 'Strict',  // protezione CSRF
        ]);
        session_start();
    }
}

// ------------------------------------------------------------
//  Login
//  Restituisce ['success' => bool, 'message' => string]
// ------------------------------------------------------------
function loginUser(string $email, string $password): array {
    $email    = trim($email);
    $password = trim($password);

    // Validazione base input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Compila tutti i campi.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email non valida.'];
    }

    $db = getDB();

    // Recupera utente — prepared statement → immune a SQL injection
    $stmt = $db->prepare('
        SELECT id, username, email, password_hash, role,
               is_active, failed_attempts, locked_until
        FROM users
        WHERE email = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Utente non trovato — risposta generica (no user enumeration)
    if (!$user) {
        return ['success' => false, 'message' => 'Credenziali non valide.'];
    }

    // Account disabilitato
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account disabilitato. Contatta il supporto.'];
    }

    // Controllo lock brute-force
    if ($user['locked_until'] !== null && new DateTime() < new DateTime($user['locked_until'])) {
        $remaining = (new DateTime($user['locked_until']))->diff(new DateTime());
        return [
            'success' => false,
            'message' => "Account bloccato. Riprova tra {$remaining->i} minuti.",
        ];
    }

    // Verifica password (bcrypt)
    if (!password_verify($password, $user['password_hash'])) {
        // Incrementa tentativi falliti
        $newAttempts = $user['failed_attempts'] + 1;
        $lockUntil   = null;

        if ($newAttempts >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil   = (new DateTime())
                ->modify('+' . LOCK_DURATION_MINUTES . ' minutes')
                ->format('Y-m-d H:i:s');
            $newAttempts = 0; // reset dopo lock
        }

        $upd = $db->prepare('
            UPDATE users
            SET failed_attempts = ?, locked_until = ?
            WHERE id = ?
        ');
        $upd->execute([$newAttempts, $lockUntil, $user['id']]);

        return ['success' => false, 'message' => 'Credenziali non valide.'];
    }

    // Password corretta — reset tentativi
    $reset = $db->prepare('
        UPDATE users
        SET failed_attempts = 0, locked_until = NULL
        WHERE id = ?
    ');
    $reset->execute([$user['id']]);

    // Rigenera session ID per prevenire session fixation
    startSecureSession();
    session_regenerate_id(true);

    // Salva dati in sessione
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['ip']        = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['ua']        = $_SERVER['HTTP_USER_AGENT'] ?? '';

    return ['success' => true, 'message' => 'Login effettuato.'];
}

// ------------------------------------------------------------
//  Logout
// ------------------------------------------------------------
function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();

    // Cancella il cookie di sessione
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// ------------------------------------------------------------
//  Controlla se l'utente è loggato
// ------------------------------------------------------------
function isLoggedIn(): bool {
    startSecureSession();

    if (empty($_SESSION['logged_in'])) return false;

    // Verifica IP e User-Agent non siano cambiati (hijacking protection)
    if (
        ($_SESSION['ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '') ||
        ($_SESSION['ua'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')
    ) {
        logoutUser();
        return false;
    }

    return true;
}

// ------------------------------------------------------------
//  Richiedi autenticazione (da usare nelle pagine protette)
// ------------------------------------------------------------
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// ------------------------------------------------------------
//  Richiedi ruolo admin
// ------------------------------------------------------------
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Accesso negato.');
    }
}
