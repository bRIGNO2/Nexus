<?php
// ============================================================
//  add_album.php  —  Cerca e aggiungi album da Last.fm
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lastfm.php';

requireLogin();

$userId       = $_SESSION['user_id'];
$isAdmin      = $_SESSION['role'] === 'admin';
$targetUserId = $userId;

if ($isAdmin && isset($_GET['user_id'])) {
    $targetUserId = (int)$_GET['user_id'];
}

$results = [];
$error   = '';
$added   = '';
$query   = '';

// ---- Ricerca album ----
if (isset($_GET['q']) && strlen(trim($_GET['q'])) > 0) {
    $query = trim($_GET['q']);
    try {
        $results = searchAlbums($query);
        if (empty($results)) {
            $error = 'Nessun album trovato per "' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '". Prova con un termine diverso.';
        }
    } catch (Exception $e) {
        $error = '⚠️ Errore Last.fm: ' . $e->getMessage();
    }
}

// ---- Aggiunta album ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastfmId  = trim($_POST['lastfm_id']  ?? '');
    $name      = trim($_POST['name']       ?? '');
    $artist    = trim($_POST['artist']     ?? '');
    $coverUrl  = trim($_POST['cover_url']  ?? '');
    $lastfmUrl = trim($_POST['lastfm_url'] ?? '');
    $mbid      = trim($_POST['mbid']       ?? '');

    if ($name && $artist) {
        $db = getDB();

        // Controllo duplicati su nome + artista (case insensitive)
        $check = $db->prepare("SELECT id FROM albums WHERE user_id = ? AND LOWER(name) = LOWER(?) AND LOWER(artist) = LOWER(?) LIMIT 1");
        $check->execute([$targetUserId, $name, $artist]);

        if ($check->fetch()) {
            $error = "Hai già questo album nella ruota!";
        } else {
            // ID univoco con timestamp per evitare collisioni md5
            $uniqueId = md5($artist . "_" . $name . "_" . microtime(true));
            $ins = $db->prepare("INSERT INTO albums (user_id, external_id, name, artist, cover_url, external_url) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$targetUserId, $uniqueId, $name, $artist, $coverUrl, $lastfmUrl]);
            $added = $name . " — " . $artist;
        }
    }

    // Mantieni risultati dopo aggiunta
    if (isset($_GET['q'])) {
        $query = trim($_GET['q']);
        try { $results = searchAlbums($query); } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Aggiungi Album — Nexus</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    :root { --cerulean:#3E78B2; --cobalt:#004BA8; --iron:#4A525A; --shadow:#24272B; --black:#07070A; }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body { background:var(--black); color:#fff; font-family:'DM Sans',sans-serif; min-height:100vh; }
    body::before {
      content:''; position:fixed; top:-20%; left:-10%;
      width:60vw; height:60vw; border-radius:50%;
      background:radial-gradient(circle,rgba(0,75,168,0.2) 0%,transparent 70%);
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
    .btn { padding:9px 20px; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:0.85rem; font-weight:500; cursor:pointer; text-decoration:none; border:none; transition:all 0.2s; }
    .btn-ghost { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1)!important; color:rgba(255,255,255,0.7); }
    .btn-ghost:hover { background:rgba(255,255,255,0.1); color:#fff; }
    main { position:relative; z-index:1; max-width:960px; margin:0 auto; padding:40px 24px; }
    h1 { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:8px; }
    .sub { color:rgba(255,255,255,0.4); font-size:0.9rem; margin-bottom:32px; }
    .search-bar { display:flex; gap:10px; margin-bottom:28px; }
    .search-bar input {
      flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
      border-radius:12px; padding:14px 18px; color:#fff; font-family:'DM Sans',sans-serif;
      font-size:0.95rem; outline:none; transition:border-color 0.2s, box-shadow 0.2s;
    }
    .search-bar input:focus { border-color:var(--cerulean); box-shadow:0 0 0 4px rgba(62,120,178,0.12); }
    .search-bar input::placeholder { color:rgba(255,255,255,0.2); }
    .search-bar button {
      padding:14px 28px; background:linear-gradient(135deg,var(--cobalt),var(--cerulean));
      border:none; border-radius:12px; color:#fff; font-family:'Syne',sans-serif;
      font-weight:700; font-size:0.9rem; cursor:pointer; transition:all 0.2s;
      box-shadow:0 4px 20px rgba(0,75,168,0.35); white-space:nowrap;
    }
    .search-bar button:hover { transform:translateY(-1px); }
    .alert { border-radius:10px; padding:14px 18px; font-size:0.875rem; margin-bottom:24px; }
    .alert-error   { background:rgba(220,50,50,0.12);  border:1px solid rgba(220,50,50,0.35);  color:#ff6b6b; }
    .alert-success { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3);  color:#6ee7b7; }
    .hint {
      background:rgba(62,120,178,0.08); border:1px solid rgba(62,120,178,0.2);
      border-radius:12px; padding:14px 18px; font-size:0.82rem;
      color:rgba(255,255,255,0.45); margin-bottom:24px; line-height:1.6;
    }
    .hint strong { color:rgba(255,255,255,0.7); }
    .results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(175px,1fr)); gap:16px; }
    .result-card {
      background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07);
      border-radius:14px; overflow:hidden; transition:transform 0.2s, border-color 0.2s;
    }
    .result-card:hover { transform:translateY(-4px); border-color:rgba(62,120,178,0.3); }
    .result-card img { width:100%; aspect-ratio:1; object-fit:cover; display:block; background:#1a1a1a; }
    .result-card-info { padding:12px; }
    .result-card-name   { font-size:0.83rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:3px; }
    .result-card-artist { font-size:0.74rem; color:rgba(255,255,255,0.4); margin-bottom:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .btn-add {
      width:100%; padding:9px; border-radius:8px; border:none;
      background:linear-gradient(135deg,var(--cobalt),var(--cerulean));
      color:#fff; font-family:'DM Sans',sans-serif; font-weight:600;
      font-size:0.8rem; cursor:pointer; transition:opacity 0.2s;
    }
    .btn-add:hover { opacity:0.85; }
    .empty { text-align:center; padding:60px 0; color:rgba(255,255,255,0.25); font-size:0.95rem; }
  </style>
</head>
<body>

<nav>
  <div class="nav-brand">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
    </svg>
    Nexus
  </div>
  <a href="wheel.php<?= $isAdmin && $targetUserId !== $userId ? '?user_id='.$targetUserId : '' ?>"
     class="btn btn-ghost">← Torna alla ruota</a>
</nav>

<main>
  <h1>Aggiungi un album</h1>
  <p class="sub">Cerca un album e aggiungilo alla tua ruota.</p>

  <div class="hint">
    💡 Cerca per <strong>nome album</strong> o <strong>nome artista</strong>.
    Esempio: <strong>Igor</strong> oppure <strong>Tyler the Creator</strong>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($added): ?>
    <div class="alert alert-success">
      ✓ Aggiunto: <strong><?= htmlspecialchars($added, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
  <?php endif; ?>

  <form method="GET" action="add_album.php" class="search-bar">
    <?php if ($isAdmin && $targetUserId !== $userId): ?>
      <input type="hidden" name="user_id" value="<?= $targetUserId ?>"/>
    <?php endif; ?>
    <input
      type="text" name="q"
      placeholder="Cerca album o artista..."
      value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
      autocomplete="off" autofocus
    />
    <button type="submit">🔍 Cerca</button>
  </form>

  <?php if (count($results) > 0): ?>
    <div class="results-grid">
      <?php foreach ($results as $r): ?>
        <div class="result-card">
          <img
            src="<?= htmlspecialchars($r['cover_url'], ENT_QUOTES, 'UTF-8') ?>"
            alt="<?= htmlspecialchars($r['name'],      ENT_QUOTES, 'UTF-8') ?>"
            loading="lazy"
            onerror="this.src='https://via.placeholder.com/300x300?text=No+Cover'"
          />
          <div class="result-card-info">
            <div class="result-card-name"><?= htmlspecialchars($r['name'],   ENT_QUOTES, 'UTF-8') ?></div>
            <div class="result-card-artist"><?= htmlspecialchars($r['artist'], ENT_QUOTES, 'UTF-8') ?></div>
            <form method="POST" action="add_album.php?q=<?= urlencode($query) ?><?= $isAdmin && $targetUserId !== $userId ? '&user_id='.$targetUserId : '' ?>">
              <input type="hidden" name="lastfm_id"  value="<?= htmlspecialchars($r['lastfm_id'],  ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="mbid"       value="<?= htmlspecialchars($r['mbid'],       ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="name"       value="<?= htmlspecialchars($r['name'],       ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="artist"     value="<?= htmlspecialchars($r['artist'],     ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="cover_url"  value="<?= htmlspecialchars($r['cover_url'],  ENT_QUOTES, 'UTF-8') ?>"/>
              <input type="hidden" name="lastfm_url" value="<?= htmlspecialchars($r['lastfm_url'], ENT_QUOTES, 'UTF-8') ?>"/>
              <button type="submit" class="btn-add">+ Aggiungi alla ruota</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php elseif (!$query): ?>
    <div class="empty">Inizia cercando un album o un artista ↑</div>
  <?php endif; ?>

</main>
</body>
</html>