<?php
/**
 * تحديث حالة طلب المساعدة — إكمال من المستفيد (موثّق للمتطوع المقبول)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (! rafiq612_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

if (rafiq612_current_user_type() !== 'senior') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'للمستفيدين فقط']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$hid = isset($_POST['help_id']) ? (int) $_POST['help_id'] : 0;
$status = rafiq612_clean_string($_POST['status'] ?? '', 20);
$allowed = ['completed', 'cancelled'];
if ($hid <= 0 || ! in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'بيانات غير صالحة']);
    exit;
}

$me = rafiq612_current_user_id();
$pdo = rafiq612_pdo();

$st = $pdo->prepare(
    'SELECT id, user_id, volunteer_id, status AS prev_status FROM help_requests WHERE id = ?'
);
$st->execute([$hid]);
$row = $st->fetch();
if (! $row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'الطلب غير موجود']);
    exit;
}
if ((int) $row['user_id'] !== $me) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'غير مسموح']);
    exit;
}

$prev = (string) ($row['prev_status'] ?? '');
$vid = (int) ($row['volunteer_id'] ?? 0);
if (in_array($prev, ['completed', 'cancelled'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'تمت معالجة هذا الطلب مسبقاً']);
    exit;
}
if ($status === 'completed' && $vid <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'لا يُعتبر مكتملاً بلا متطوعٍ وافق مسبقاً']);
    exit;
}
if ($status === 'completed' && ! in_array($prev, ['accepted', 'in_progress'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'الحالة غير مناسبة للإكمال']);
    exit;
}

try {
    require_once __DIR__ . '/includes/volunteer_rank612.php';
    $pdo->beginTransaction();
    $comAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    $up = $pdo->prepare('UPDATE help_requests SET status = ?, completed_at = ? WHERE id = ?');
    $up->execute([$status, $comAt, $hid]);
    if ($status === 'completed' && $prev !== 'completed' && $vid > 0) {
        rafiq612_rebuild_volunteer_tasks_count($pdo, $vid);
        rafiq612_recompute_volunteer_rank($pdo, $vid);
    }
    $pdo->commit();
    echo json_encode(['ok' => true, 'volunteer_id' => $vid]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر التحديث']);
}
