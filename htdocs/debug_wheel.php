<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

echo "<pre>";
echo "Step 1 — config/db/auth OK\n";

// Simula login check
if (!isLoggedIn()) {
    echo "❌ Non loggato!\n";
    exit;
}
echo "Step 2 — Loggato come: " . $_SESSION['username'] . " (role: " . $_SESSION['role'] . ")\n";

$db = getDB();

// Controlla colonna onboarding_done
try {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Step 3 — Colonne users: " . implode(', ', $cols) . "\n";
    if (in_array('onboarding_done', $cols)) {
        echo "✅ Colonna onboarding_done esiste\n";
    } else {
        echo "❌ Colonna onboarding_done MANCANTE — esegui onboarding.sql!\n";
    }
} catch (Exception $e) {
    echo "❌ Errore colonne: " . $e->getMessage() . "\n";
}

// Controlla album
try {
    $stmt = $db->prepare('SELECT id, name, artist FROM albums WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $albums = $stmt->fetchAll();
    echo "Step 4 — Album trovati: " . count($albums) . "\n";
    foreach ($albums as $a) {
        echo "   → {$a['name']} — {$a['artist']}\n";
    }
} catch (Exception $e) {
    echo "❌ Errore album: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p style='color:red'>⚠️ CANCELLA QUESTO FILE!</p>";
?>
