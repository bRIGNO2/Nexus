<?php
// ============================================================
//  lastfm.php  —  Gestione API Last.fm
// ============================================================

require_once __DIR__ . '/config.php';

// ------------------------------------------------------------
//  Chiamata generica Last.fm
// ------------------------------------------------------------
function lastfmGet(array $params): array {
    $params['api_key'] = LASTFM_API_KEY;
    $params['format']  = 'json';

    $url = LASTFM_BASE_URL . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'NexusMusicApp/1.0',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        die('ERRORE cURL Last.fm: ' . $curlErr);
    }

    return json_decode($response, true) ?? [];
}

// ------------------------------------------------------------
//  Cerca album su Last.fm
//  Restituisce array di album ordinati per somiglianza
// ------------------------------------------------------------
function searchAlbums(string $query): array {
    $query = trim($query);
    if (empty($query)) return [];

    $data  = lastfmGet([
        'method' => 'album.search',
        'album'  => $query,
        'limit'  => 30,
    ]);

    $items = $data['results']['albummatches']['album'] ?? [];

    // Last.fm a volte torna un oggetto invece di array se c'è un solo risultato
    if (isset($items['name'])) $items = [$items];
    if (empty($items)) return [];

    $queryLower = mb_strtolower($query);
    $albums     = [];

    foreach ($items as $item) {
        $name   = $item['name']   ?? '';
        $artist = $item['artist'] ?? '';
        if (empty($name) || empty($artist)) continue;

        // Prendi la copertina migliore disponibile
        $cover = '';
        if (!empty($item['image'])) {
            foreach (array_reverse($item['image']) as $img) {
                if (!empty($img['#text'])) {
                    $cover = $img['#text'];
                    break;
                }
            }
        }

        // Se non c'è copertina usa placeholder
        if (empty($cover) || str_contains($cover, '2a96cbd8b46e442fc41c2b86b821562f')) {
            $cover = 'https://via.placeholder.com/300x300/24272B/3E78B2?text=' . urlencode($name);
        }

        $albums[] = [
            'id'         => md5($name . $artist), // ID univoco locale
            'name'       => $name,
            'artist'     => $artist,
            'cover_url'  => $cover,
            'lastfm_url' => $item['url'] ?? '#',
            'score'      => relevanceScore($name, $artist, $queryLower),
        ];
    }

    // Ordina per rilevanza
    usort($albums, fn($a, $b) => $b['score'] - $a['score']);

    // Rimuovi il campo score dall'output
    return array_map(function($a) {
        unset($a['score']);
        return $a;
    }, array_slice($albums, 0, 16));
}

// ------------------------------------------------------------
//  Score di rilevanza
// ------------------------------------------------------------
function relevanceScore(string $name, string $artist, string $queryLower): int {
    $score      = 0;
    $nameLower  = mb_strtolower($name);
    $artistLower = mb_strtolower($artist);

    if ($nameLower === $queryLower)                    $score += 100;
    if (str_starts_with($nameLower, $queryLower))      $score += 80;
    if (str_contains($nameLower, $queryLower))         $score += 50;
    if (str_starts_with($artistLower, $queryLower))    $score += 40;
    if (str_contains($artistLower, $queryLower))       $score += 20;

    return $score;
}

// ------------------------------------------------------------
//  Ottieni dettagli album singolo da Last.fm
// ------------------------------------------------------------
function getAlbumInfo(string $artist, string $album): ?array {
    $data = lastfmGet([
        'method' => 'album.getinfo',
        'artist' => $artist,
        'album'  => $album,
    ]);

    $item = $data['album'] ?? null;
    if (!$item) return null;

    $cover = '';
    if (!empty($item['image'])) {
        foreach (array_reverse($item['image']) as $img) {
            if (!empty($img['#text'])) { $cover = $img['#text']; break; }
        }
    }

    return [
        'id'         => md5($item['name'] . $item['artist']),
        'name'       => $item['name'],
        'artist'     => $item['artist'],
        'cover_url'  => $cover ?: 'https://via.placeholder.com/300x300',
        'lastfm_url' => $item['url'] ?? '#',
    ];
}