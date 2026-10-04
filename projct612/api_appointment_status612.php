<?php
/**
 * تحديث حالة الموعد — إكمال أو إلغاء (كبير السن أو المتطوع المعيَّن)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (!rafiq612_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
$status = rafiq612_clean_string($_POST['status'] ?? '', 20);
$allowed = ['completed', 'cancelled'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'بيانات غير صالحة']);
    exit;
}

$me = rafiq612_current_user_id();
$type = rafiq612_current_user_type();
$pdo = rafiq612_pdo();

$st = $pdo->prepare('SELECT id, senior_id, volunteer_id, status AS prev_status FROM appointments WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'الموعد غير موجود']);
    exit;
}

$ok = false;
if ($type === 'senior' && (int) $row['senior_id'] === $me) {
    $ok = true;
}
if ($type === 'volunteer' && (int) ($row['volunteer_id'] ?? 0) === $me) {
    $ok = true;
}

if (!$ok) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'غير مسموح']);
    exit;
}

try {
    $pdo->beginTransaction();
    $up = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
    $up->execute([$status, $id]);
    if ($status === 'completed' && (string) ($row['prev_status'] ?? '') !== 'completed') {
        $vid = (int) ($row['volunteer_id'] ?? 0);
        if ($vid > 0) {
            require_once __DIR__ . '/includes/volunteer_rank612.php';
            rafiq612_rebuild_volunteer_tasks_count($pdo, $vid);
            rafiq612_recompute_volunteer_rank($pdo, $vid);
        }
    }
    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر التحديث']);
}
