<?php
// ============================================================
//  api_albums.php  —  API JSON per operazioni sugli album
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'admin';
$db     = getDB();

switch ($action) {

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        // Verifica che l'album appartenga all'utente (o sia admin)
        $check = $db->prepare('SELECT user_id FROM albums WHERE id = ?');
        $check->execute([$id]);
        $row = $check->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Album non trovato.']);
            exit;
        }

        if ($row['user_id'] !== $userId && !$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Non autorizzato.']);
            exit;
        }

        $del = $db->prepare('DELETE FROM albums WHERE id = ?');
        $del->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'reorder':
        // $input['order'] = [id1, id2, id3, ...]
        $order = $input['order'] ?? [];
        foreach ($order as $position => $albumId) {
            $upd = $db->prepare('UPDATE albums SET wheel_order = ? WHERE id = ? AND user_id = ?');
            $upd->execute([$position, (int)$albumId, $userId]);
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida.']);
}