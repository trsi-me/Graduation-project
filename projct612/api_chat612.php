<?php
/**
 * محادثات أنيس الروح
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (!rafiq612_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

$me = rafiq612_current_user_id();
$myType = rafiq612_current_user_type();
$pdo = rafiq612_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $peer = isset($_GET['peer']) ? (int) $_GET['peer'] : 0;
    if ($peer <= 0 || $peer === $me) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'معرّف غير صالح']);
        exit;
    }
    $st = $pdo->prepare('SELECT id, user_type FROM users WHERE id = ? AND is_active = 1');
    $st->execute([$peer]);
    $other = $st->fetch();
    if (!$other) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'المستخدم غير موجود']);
        exit;
    }
    if ($myType === $other['user_type']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'المحادثة متاحة بين كبير السن والمتطوع فقط']);
        exit;
    }
    try {
        $q = $pdo->prepare(
            'SELECT id, sender_id, receiver_id, message, ts, is_read FROM chat_messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY ts ASC'
        );
        $q->execute([$me, $peer, $peer, $me]);
        $rows = $q->fetchAll();
        $upd = $pdo->prepare(
            'UPDATE chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0'
        );
        $upd->execute([$me, $peer]);
        echo json_encode(['ok' => true, 'messages' => $rows, 'peer' => ['id' => $peer]]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'خطأ']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$peer = isset($_POST['peer_id']) ? (int) $_POST['peer_id'] : 0;
$message = rafiq612_clean_string($_POST['message'] ?? '', 4000);

if ($peer <= 0 || $peer === $me || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'محتوى غير صالح']);
    exit;
}

$st = $pdo->prepare('SELECT id, user_type FROM users WHERE id = ? AND is_active = 1');
$st->execute([$peer]);
$other = $st->fetch();
if (!$other || $myType === $other['user_type']) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'غير مسموح']);
    exit;
}

try {
    $ins = $pdo->prepare(
        'INSERT INTO chat_messages (sender_id, receiver_id, message, is_read) VALUES (?,?,?,0)'
    );
    $ins->execute([$me, $peer, $message]);
    $id = (int) $pdo->lastInsertId();
    $get = $pdo->prepare(
        'SELECT id, sender_id, receiver_id, message, ts, is_read FROM chat_messages WHERE id = ?'
    );
    $get->execute([$id]);
    $row = $get->fetch();
    echo json_encode(['ok' => true, 'message' => $row]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر الإرسال']);
}
