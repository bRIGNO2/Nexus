<?php
// ============================================================
//  wheel.php  —  Ruota degli album dell'utente
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$isAdmin  = $_SESSION['role'] === 'admin';

// Admin può vedere la ruota di qualsiasi utente
$viewUserId = $userId;
$viewUsername = $username;
if ($isAdmin && isset($_GET['user_id'])) {
    $viewUserId = (int)$_GET['user_id'];
    $db = getDB();
    $u  = $db->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $u->execute([$viewUserId]);
    $row = $u->fetch();
    if ($row) $viewUsername = $row['username'];
}

$db = getDB();
$stmt = $db->prepare('
    SELECT id, external_id, name, artist, cover_url, external_url, wheel_order
    FROM albums
    WHERE user_id = ?
    ORDER BY wheel_order ASC, created_at ASC
');
$stmt->execute([$viewUserId]);
$albums = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ruota — <?= htmlspecialchars($viewUsername, ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --cerulean: #3E78B2;
      --cobalt:   #004BA8;
      --iron:     #4A525A;
      --shadow:   #24272B;
      --black:    #07070A;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--black);
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Background */
    body::before {
      content: '';
      position: fixed; top: -20%; left: -10%;
      width: 60vw; height: 60vw; border-radius: 50%;
      background: radial-gradient(circle, rgba(0,75,168,0.2) 0%, transparent 70%);
      pointer-events: none; z-index: 0;
    }

    /* NAV */
    nav {
      position: relative; z-index: 10;
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 40px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      background: rgba(7,7,10,0.8);
      backdrop-filter: blur(12px);
    }

    .nav-brand {
      display: flex; align-items: center; gap: 10px;
      font-family: 'Syne', sans-serif; font-weight: 800;
      font-size: 1.1rem; letter-spacing: 0.06em; text-transform: uppercase;
    }

    .nav-brand svg { width: 22px; height: 22px; }

    .nav-actions { display: flex; align-items: center; gap: 12px; }

    .btn {
      padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem; font-weight: 500; cursor: pointer;
      text-decoration: none; border: none; transition: all 0.2s;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--cobalt), var(--cerulean));
      color: #fff; box-shadow: 0 4px 20px rgba(0,75,168,0.35);
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(0,75,168,0.5); }
    .btn-ghost {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.7);
    }
    .btn-ghost:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .btn-danger {
      background: rgba(220,50,50,0.12);
      border: 1px solid rgba(220,50,50,0.25); color: #ff6b6b;
    }
    .btn-danger:hover { background: rgba(220,50,50,0.22); }

    /* MAIN */
    main {
      position: relative; z-index: 1;
      max-width: 1100px; margin: 0 auto; padding: 40px 24px;
    }

    .page-title {
      font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800;
      margin-bottom: 6px;
    }
    .page-sub { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 40px; }
    .page-sub span { color: var(--cerulean); }

    /* WHEEL CONTAINER */
    .wheel-section {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      gap: 40px;
      margin-bottom: 60px;
      flex-wrap: wrap;
    }

    .wheel-wrap {
      position: relative;
      width: min(460px, 90vw);
      height: min(460px, 90vw);
      flex-shrink: 0;
    }

    #wheelCanvas {
      width: 100%; height: 100%;
      border-radius: 50%;
      box-shadow: 0 0 60px rgba(0,75,168,0.3), 0 0 0 2px rgba(62,120,178,0.2);
      cursor: pointer;
      display: block;
    }

    /* Freccia a DESTRA della ruota */
    .wheel-pointer {
      position: absolute;
      right: -22px;
      top: 50%;
      transform: translateY(-50%);
      width: 0; height: 0;
      border-top: 14px solid transparent;
      border-bottom: 14px solid transparent;
      border-right: 28px solid var(--cerulean);
      filter: drop-shadow(0 0 8px rgba(62,120,178,0.8));
      z-index: 5;
    }

    /* PANNELLO PREVIEW a destra */
    .wheel-side {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
      min-width: 240px;
    }

    /* PREVIEW CARD */
    .preview-card {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(62,120,178,0.15);
      border-radius: 20px;
      padding: 0;
      text-align: center;
      width: 240px;
      transition: border-color 0.3s, box-shadow 0.3s;
      overflow: hidden;
      position: relative;
    }

    .preview-card.spinning {
      border-color: rgba(62,120,178,0.4);
      box-shadow: 0 0 20px rgba(62,120,178,0.12);
    }

    .preview-card.result {
      border-color: rgba(62,120,178,0.7);
      box-shadow: 0 0 50px rgba(62,120,178,0.3), 0 0 0 1px rgba(62,120,178,0.4);
      animation: popIn 0.5s cubic-bezier(0.16,1,0.3,1);
    }

    /* Copertina grande che riempie il top */
    .preview-cover-wrap {
      position: relative;
      width: 100%;
      aspect-ratio: 1;
      overflow: hidden;
    }

    .preview-cover {
      width: 100%; height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s;
    }

    .preview-card.result .preview-cover {
      transform: scale(1.04);
    }

    .preview-cover-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, transparent 50%, rgba(7,7,10,0.85) 100%);
    }

    .preview-label {
      position: absolute;
      top: 10px; left: 50%;
      transform: translateX(-50%);
      font-size: 0.65rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(6px);
      color: rgba(255,255,255,0.7);
      padding: 4px 10px;
      border-radius: 99px;
      border: 1px solid rgba(255,255,255,0.1);
      white-space: nowrap;
    }

    .preview-card.result .preview-label {
      background: rgba(62,120,178,0.7);
      color: #fff;
      border-color: rgba(62,120,178,0.8);
    }

    .preview-info {
      padding: 14px 16px 18px;
    }

    .preview-name {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      line-height: 1.2;
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .preview-artist {
      font-size: 0.78rem;
      color: rgba(255,255,255,0.4);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      margin-bottom: 12px;
    }

    .preview-link {
      display: none;
      width: 100%;
      padding: 9px;
      border-radius: 8px;
      background: linear-gradient(135deg, #004BA8, #3E78B2);
      color: #fff;
      font-weight: 700;
      font-size: 0.8rem;
      text-decoration: none;
      letter-spacing: 0.04em;
      transition: opacity 0.2s;
    }
    .preview-link:hover { opacity: 0.85; }
    .preview-link.show { display: block; }

    /* Pulse animazione spinning */
    @keyframes pulse-border {
      0%, 100% { box-shadow: 0 0 12px rgba(62,120,178,0.1); }
      50%       { box-shadow: 0 0 24px rgba(62,120,178,0.3); }
    }
    .preview-card.spinning {
      animation: pulse-border 0.8s ease-in-out infinite;
    }

    .wheel-controls { display: flex; gap: 12px; align-items: center; }

    @keyframes popIn {
      from { opacity: 0; transform: scale(0.88); }
      to   { opacity: 1; transform: scale(1); }
    }

    /* ALBUM GRID */
    .section-title {
      font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
      margin-bottom: 20px;
      display: flex; align-items: center; justify-content: space-between;
    }

    .album-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 16px;
    }

    .album-card {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 14px; overflow: hidden;
      transition: transform 0.2s, border-color 0.2s;
    }
    .album-card:hover {
      transform: translateY(-4px);
      border-color: rgba(62,120,178,0.3);
    }
    .album-card img {
      width: 100%; aspect-ratio: 1; object-fit: cover; display: block;
    }
    .album-card-info { padding: 12px; }
    .album-card-name {
      font-size: 0.82rem; font-weight: 600;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .album-card-artist {
      font-size: 0.74rem; color: rgba(255,255,255,0.4);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      margin-bottom: 10px;
    }
    .album-card-actions { display: flex; gap: 6px; }
    .album-card-actions a,
    .album-card-actions button {
      flex: 1; padding: 6px; border-radius: 7px;
      font-size: 0.72rem; font-weight: 500;
      text-align: center; cursor: pointer; border: none;
      text-decoration: none; transition: all 0.15s;
    }
    .btn-lastfm {
      background: rgba(209,18,18,0.15); color: #e5383b;
      border: 1px solid rgba(209,18,18,0.2) !important;
    }
    .btn-lastfm:hover { background: rgba(209,18,18,0.25); }
    .btn-del {
      background: rgba(220,50,50,0.1); color: #ff6b6b;
      border: 1px solid rgba(220,50,50,0.2) !important;
    }
    .btn-del:hover { background: rgba(220,50,50,0.22); }

    /* EMPTY STATE */
    .empty-state {
      text-align: center; padding: 60px 20px;
      color: rgba(255,255,255,0.3);
    }
    .empty-state svg { width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.3; }
    .empty-state p { font-size: 0.95rem; }

    @media (max-width: 600px) {
      nav { padding: 16px 20px; }
      main { padding: 28px 16px; }
      .wheel-section { flex-direction: column; }
    }

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
  <div class="nav-actions">
    <?php if ($isAdmin): ?>
      <a href="admin_panel.php" class="btn btn-ghost">👑 Admin</a>
    <?php endif; ?>
    <a href="add_album.php<?= $isAdmin && $viewUserId !== $userId ? '?user_id='.$viewUserId : '' ?>" class="btn btn-primary">+ Aggiungi album</a>
    <a href="logout.php" class="btn btn-ghost">Esci</a>
  </div>
</nav>

<main>
  <div class="page-title">
    La ruota di <span style="color:var(--cerulean)"><?= htmlspecialchars($viewUsername, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <p class="page-sub">
    <span><?= count($albums) ?></span> album nella ruota
    <?php if ($isAdmin && $viewUserId !== $userId): ?>
      — <a href="wheel.php" style="color:var(--cerulean);text-decoration:none;">← Torna alla tua ruota</a>
    <?php endif; ?>
  </p>

  <?php if (count($albums) > 0): ?>

  <!-- RUOTA -->
  <div class="wheel-section">

    <div class="wheel-wrap">
      <div class="wheel-pointer"></div>
      <canvas id="wheelCanvas" width="500" height="500"></canvas>
    </div>

    <!-- PANNELLO DESTRA -->
    <div class="wheel-side">
      <div class="preview-card" id="previewCard">
        <div class="preview-cover-wrap">
          <img src="" alt="" class="preview-cover" id="previewCover"
               onerror="this.src='https://via.placeholder.com/240x240?text=?'"/>
          <div class="preview-cover-overlay"></div>
          <div class="preview-label" id="previewLabel">In palio</div>
        </div>
        <div class="preview-info">
          <div class="preview-name"   id="previewName">—</div>
          <div class="preview-artist" id="previewArtist">Gira la ruota!</div>
          <a href="#" target="_blank" class="preview-link" id="previewLink">▶ Ascolta su Last.fm</a>
        </div>
      </div>
      <button class="btn btn-primary" onclick="spinWheel()" id="spinBtn">
        🎲 Gira la ruota
      </button>
    </div>

  </div>

  <?php endif; ?>

  <!-- ALBUM LIST -->
  <div class="section-title">
    I tuoi album
  </div>

  <?php if (count($albums) === 0): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
      </svg>
      <p>Nessun album ancora. <a href="add_album.php" style="color:var(--cerulean)">Aggiungine uno!</a></p>
    </div>
  <?php else: ?>
    <div class="album-grid">
      <?php foreach ($albums as $album): ?>
        <div class="album-card">
          <img src="<?= htmlspecialchars($album['cover_url'], ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($album['name'], ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy"/>
          <div class="album-card-info">
            <div class="album-card-name"><?= htmlspecialchars($album['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="album-card-artist"><?= htmlspecialchars($album['artist'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="album-card-actions">
              <a href="<?= htmlspecialchars($album['external_url'], ENT_QUOTES, 'UTF-8') ?>"
                 target="_blank" class="btn-lastfm">▶ Spotify</a>
              <?php if ($viewUserId === $userId || $isAdmin): ?>
                <button class="btn-del"
                  onclick="deleteAlbum(<?= $album['id'] ?>)">✕ Rimuovi</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<script>
// ---- Dati album passati da PHP ----
const albums = <?= json_encode(array_values($albums), JSON_HEX_TAG) ?>;

// ---- Canvas Wheel ----
const canvas  = document.getElementById('wheelCanvas');
const ctx     = canvas.getContext('2d');
const colors  = ['#004BA8','#3E78B2','#1a3a6e','#2d5fa0','#0a2d7a','#1e4d8c'];
let   currentAngle = 0;
let   spinning     = false;

// Cache immagini precaricate
const imgCache = {};

function fixUrl(url) {
  // Forza HTTPS per evitare mixed content block
  return url ? url.replace(/^http:\/\//i, 'https://') : '';
}

// Cache immagini
const imgCache = {};
function loadImg(url) {
  if (!imgCache[url]) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = url;
    img.onload = () => drawWheel(currentAngle);
    imgCache[url] = img;
  }
  return imgCache[url];
}

function drawWheel(angle) {
  const cx = canvas.width  / 2;
  const cy = canvas.height / 2;
  const r  = cx - 10;
  const n  = albums.length;

  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (n === 0) {
    ctx.fillStyle = '#24272B';
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.font = 'bold 16px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Aggiungi album', cx, cy);
    return;
  }

  const slice = (Math.PI * 2) / n;

  albums.forEach((album, i) => {
    const start    = angle + i * slice;
    const end      = start + slice;
    const midAngle = start + slice / 2;

    // ---- Riempi lo spicchio con la copertina ----
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, r, start, end);
    ctx.closePath();

    const img = loadImg(fixUrl(album.cover_url));

    if (img.complete && img.naturalWidth > 0) {
      // Crea pattern: ruota l'immagine per riempire lo spicchio dal centro
      // Usiamo clip + drawImage posizionando la copertina centrata sullo spicchio
      ctx.clip();

      // Posizione centro spicchio
      const coverR  = r * 0.55;
      const cx2     = cx + coverR * Math.cos(midAngle);
      const cy2     = cy + coverR * Math.sin(midAngle);
      const size    = r * (slice > 0.8 ? 0.9 : Math.min(1.2, (2 * r * Math.sin(slice / 2)) * 1.1));

      ctx.drawImage(img, cx2 - size / 2, cy2 - size / 2, size, size);

      // Overlay scuro per separare gli spicchi
      ctx.fillStyle = 'rgba(0,0,0,0.28)';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    } else {
      // Fallback colore se immagine non caricata
      ctx.fillStyle = colors[i % colors.length];
      ctx.fill();
    }

    ctx.restore();

    // ---- Bordo spicchio ----
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, r, start, end);
    ctx.closePath();
    ctx.strokeStyle = 'rgba(255,255,255,0.18)';
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.restore();

    // ---- Nome album ruotato lungo il bordo esterno ----
    if (n <= 16) {
      ctx.save();
      ctx.translate(cx, cy);
      ctx.rotate(midAngle);
      // Sfondo testo
      const label    = album.name.length > 13 ? album.name.substring(0, 12) + '…' : album.name;
      const fontSize = Math.max(8, Math.min(12, r / n * 0.85));
      ctx.font       = `bold ${fontSize}px sans-serif`;
      const tw       = ctx.measureText(label).width;
      ctx.fillStyle  = 'rgba(0,0,0,0.55)';
      ctx.fillRect(r - tw - 18, -fontSize * 0.8, tw + 10, fontSize * 1.6);
      ctx.fillStyle  = '#fff';
      ctx.textAlign  = 'right';
      ctx.textBaseline = 'middle';
      ctx.fillText(label, r - 10, 0);
      ctx.restore();
    }
  });

  // ---- Centro ----
  ctx.beginPath();
  ctx.arc(cx, cy, 28, 0, Math.PI * 2);
  ctx.fillStyle = '#07070A';
  ctx.fill();
  ctx.strokeStyle = 'rgba(62,120,178,0.7)';
  ctx.lineWidth = 3;
  ctx.stroke();

  ctx.fillStyle = '#3E78B2';
  ctx.font = 'bold 16px sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('⚡', cx, cy);
}

function preloadAndDraw() {
  // Precarica tutte le immagini
  albums.forEach(a => loadImg(fixUrl(a.cover_url)));
  drawWheel(currentAngle);
}
preloadAndDraw();

// ---- Helpers preview ----
function getAlbumAtPointer(angle) {
  // Freccia a destra → punta a angolo 0 (destra)
  const n     = albums.length;
  const slice = (Math.PI * 2) / n;
  const pointerAngle = (Math.PI * 2) - angle;
  const normalized   = ((pointerAngle % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
  return albums[Math.floor(normalized / slice) % n];
}

function updatePreview(album, state) {
  // state: 'idle' | 'spinning' | 'result'
  const card    = document.getElementById('previewCard');
  const cover   = document.getElementById('previewCover');
  const name    = document.getElementById('previewName');
  const artist  = document.getElementById('previewArtist');
  const label   = document.getElementById('previewLabel');
  const link    = document.getElementById('previewLink');

  cover.src         = fixUrl(album.cover_url);
  name.textContent  = album.name;
  artist.textContent= album.artist;
  link.href         = album.external_url;

  card.classList.remove('spinning', 'result');
  link.classList.remove('show');

  if (state === 'spinning') {
    card.classList.add('spinning');
    label.textContent = '🎲 Potrebbe uscire...';
  } else if (state === 'result') {
    card.classList.add('result');
    label.textContent = '🎯 Estratto!';
    link.classList.add('show');
  } else {
    label.textContent = 'In palio';
  }
}

// Init preview con primo album
if (albums.length > 0) {
  albums[0].cover_url = fixUrl(albums[0].cover_url);
  updatePreview(albums[0], 'idle');
}

let previewInterval = null;
let lastPreviewIdx  = -1;

function spinWheel() {
  if (spinning || albums.length === 0) return;
  spinning = true;
  document.getElementById('spinBtn').disabled = true;

  const extraSpins  = (Math.random() * 5 + 5) * Math.PI * 2;
  const targetAngle = currentAngle + extraSpins;
  const duration    = 4500;
  const start       = performance.now();
  const startA      = currentAngle;

  function animate(now) {
    const elapsed  = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const ease     = 1 - Math.pow(1 - progress, 4);
    currentAngle   = startA + (targetAngle - startA) * ease;

    drawWheel(currentAngle);

    // Aggiorna preview in tempo reale quando cambia album
    const current = getAlbumAtPointer(currentAngle);
    const idx     = albums.indexOf(current);
    if (idx !== lastPreviewIdx) {
      lastPreviewIdx = idx;
      updatePreview(current, 'spinning');
    }

    if (progress < 1) {
      requestAnimationFrame(animate);
    } else {
      currentAngle = currentAngle % (Math.PI * 2);
      spinning     = false;
      document.getElementById('spinBtn').disabled = false;
      showResult();
    }
  }

  requestAnimationFrame(animate);
}

function showResult() {
  const album = getAlbumAtPointer(currentAngle);
  updatePreview(album, 'result');
}

// ---- Elimina album ----
function deleteAlbum(id) {
  if (!confirm('Rimuovere questo album dalla ruota?')) return;
  fetch('api_albums.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', id: id })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) location.reload();
    else alert(data.message || 'Errore.');
  });
}
</script>

</body>
</html>