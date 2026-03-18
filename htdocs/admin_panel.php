<?php
// ============================================================
//  admin_panel.php  —  Pannello admin
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$db = getDB();

// Lista utenti con conteggio album
$users = $db->query('
    SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at,
           COUNT(a.id) AS album_count
    FROM users u
    LEFT JOIN albums a ON a.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
')->fetchAll();

// Azioni admin
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active') {
        $cur = $db->prepare('SELECT is_active FROM users WHERE id = ?');
        $cur->execute([$uid]);
        $row = $cur->fetch();
        if ($row) {
            $newVal = $row['is_active'] ? 0 : 1;
            $db->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newVal, $uid]);
            $msg = 'Utente aggiornato.';
        }
    } elseif ($action === 'delete_user') {
        $db->prepare('DELETE FROM users WHERE id = ? AND role != "admin"')->execute([$uid]);
        $msg = 'Utente eliminato.';
    }

    header('Location: admin_panel.php' . ($msg ? '?msg=' . urlencode($msg) : ''));
    exit;
}

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&family=DM+Mono:wght@400&display=swap" rel="stylesheet"/>
  <style>
    :root { --cerulean:#3E78B2; --cobalt:#004BA8; --iron:#4A525A; --shadow:#24272B; --black:#07070A; }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { background:var(--black); color:#fff; font-family:'DM Sans',sans-serif; min-height:100vh; }
    body::before {
      content:''; position:fixed; top:-20%; right:-10%;
      width:50vw; height:50vw; border-radius:50%;
      background:radial-gradient(circle,rgba(0,75,168,0.18) 0%,transparent 70%);
      pointer-events:none; z-index:0;
    }
    nav {
      position:relative; z-index:10;
      display:flex; align-items:center; justify-content:space-between;
      padding:20px 40px;
      border-bottom:1px solid rgba(255,255,255,0.06);
      background:rgba(7,7,10,0.8); backdrop-filter:blur(12px);
    }
    .nav-brand { display:flex; align-items:center; gap:10px; font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem; letter-spacing:0.06em; text-transform:uppercase; }
    .nav-brand svg { width:22px; height:22px; }
    .badge-admin { background:rgba(255,193,7,0.15); border:1px solid rgba(255,193,7,0.3); color:#fcd34d; padding:4px 10px; border-radius:6px; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; }
    .btn { padding:8px 18px; border-radius:9px; font-family:'DM Sans',sans-serif; font-size:0.82rem; font-weight:500; cursor:pointer; text-decoration:none; border:none; transition:all 0.2s; display:inline-block; }
    .btn-ghost { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1)!important; color:rgba(255,255,255,0.7); }
    .btn-ghost:hover { background:rgba(255,255,255,0.1); color:#fff; }
    main { position:relative; z-index:1; max-width:1100px; margin:0 auto; padding:40px 24px; }
    .page-header { margin-bottom:32px; }
    h1 { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:6px; }
    .sub { color:rgba(255,255,255,0.4); font-size:0.9rem; }
    .stats-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; margin-bottom:36px; }
    .stat-box { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:14px; padding:20px 24px; }
    .stat-box .num { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:#fff; }
    .stat-box .label { font-size:0.75rem; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.08em; margin-top:4px; }
    .alert-success { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7; border-radius:10px; padding:12px 16px; font-size:0.85rem; margin-bottom:24px; }
    table { width:100%; border-collapse:collapse; }
    .table-wrap { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; overflow:hidden; }
    thead tr { border-bottom:1px solid rgba(255,255,255,0.07); }
    th { padding:14px 18px; text-align:left; font-size:0.72rem; font-weight:500; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.1em; }
    td { padding:14px 18px; font-size:0.875rem; border-bottom:1px solid rgba(255,255,255,0.04); }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:rgba(255,255,255,0.02); }
    .mono { font-family:'DM Mono',monospace; font-size:0.82rem; color:rgba(255,255,255,0.5); }
    .role-admin { background:rgba(255,193,7,0.12); color:#fcd34d; padding:3px 9px; border-radius:5px; font-size:0.72rem; font-weight:600; text-transform:uppercase; }
    .role-user  { background:rgba(62,120,178,0.12); color:#93c5fd; padding:3px 9px; border-radius:5px; font-size:0.72rem; font-weight:600; text-transform:uppercase; }
    .status-on  { background:rgba(16,185,129,0.12); color:#6ee7b7; padding:3px 9px; border-radius:5px; font-size:0.72rem; }
    .status-off { background:rgba(220,50,50,0.12);  color:#ff6b6b;  padding:3px 9px; border-radius:5px; font-size:0.72rem; }
    .actions { display:flex; gap:8px; align-items:center; }
    .btn-sm { padding:5px 12px; font-size:0.75rem; border-radius:7px; }
    .btn-view   { background:rgba(62,120,178,0.15); border:1px solid rgba(62,120,178,0.25)!important; color:#93c5fd; }
    .btn-view:hover { background:rgba(62,120,178,0.28); }
    .btn-toggle { background:rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.25)!important; color:#fcd34d; }
    .btn-toggle:hover { background:rgba(255,193,7,0.22); }
    .btn-del    { background:rgba(220,50,50,0.1); border:1px solid rgba(220,50,50,0.2)!important; color:#ff6b6b; }
    .btn-del:hover { background:rgba(220,50,50,0.22); }
  </style>
</head>
<body>

<nav>
  <div class="nav-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
    </svg>
    Nexus <span class="badge-admin" style="margin-left:8px">Admin</span>
  </div>
  <div style="display:flex;gap:10px">
    <a href="wheel.php" class="btn btn-ghost">La mia ruota</a>
    <a href="logout.php" class="btn btn-ghost">Esci</a>
  </div>
</nav>

<main>
  <div class="page-header">
    <h1>👑 Pannello Admin</h1>
    <p class="sub">Gestisci tutti gli utenti e le loro ruote.</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert-success"><?= $msg ?></div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-box">
      <div class="num"><?= count($users) ?></div>
      <div class="label">Utenti totali</div>
    </div>
    <div class="stat-box">
      <div class="num"><?= array_sum(array_column($users, 'album_count')) ?></div>
      <div class="label">Album totali</div>
    </div>
    <div class="stat-box">
      <div class="num"><?= count(array_filter($users, fn($u) => $u['is_active'])) ?></div>
      <div class="label">Utenti attivi</div>
    </div>
  </div>

  <!-- TABELLA UTENTI -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Email</th>
          <th>Ruolo</th>
          <th>Stato</th>
          <th>Album</th>
          <th>Registrato</th>
          <th>Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="mono"><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td class="mono"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="role-<?= $u['role'] === 'admin' ? 'admin' : 'user' ?>"><?= $u['role'] ?></span></td>
            <td><span class="status-<?= $u['is_active'] ? 'on' : 'off' ?>"><?= $u['is_active'] ? 'Attivo' : 'Bloccato' ?></span></td>
            <td><?= $u['album_count'] ?></td>
            <td class="mono"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="actions">
                <a href="wheel.php?user_id=<?= $u['id'] ?>" class="btn btn-sm btn-view">👁 Ruota</a>
                <?php if ($u['role'] !== 'admin'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_active"/>
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                    <button type="submit" class="btn btn-sm btn-toggle">
                      <?= $u['is_active'] ? '🔒 Blocca' : '🔓 Sblocca' ?>
                    </button>
                  </form>
                  <form method="POST" style="display:inline"
                    onsubmit="return confirm('Eliminare definitivamente questo utente?')">
                    <input type="hidden" name="action" value="delete_user"/>
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                    <button type="submit" class="btn btn-sm btn-del">✕ Elimina</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>
