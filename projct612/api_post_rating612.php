<?php
/**
 * تقييم المستفيد للمتطوع (بعد تعاون موثّق) — يحدّث النقاط والترقية
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (!rafiq612_is_logged_in()) {
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

$appointmentId = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
$reviewed = isset($_POST['volunteer_id']) ? (int) $_POST['volunteer_id'] : 0;
$val = isset($_POST['rating_value']) ? (int) $_POST['rating_value'] : 0;
if ($appointmentId <= 0 || $reviewed <= 0 || $val < 1 || $val > 5) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'تقييم غير صالح']);
    exit;
}

$me = rafiq612_current_user_id();
$pdo = rafiq612_pdo();

$chk = $pdo->prepare(
    "SELECT 1 FROM users WHERE id = ? AND user_type = 'volunteer' AND is_active = 1 LIMIT 1"
);
$chk->execute([$reviewed]);
if (!$chk->fetchColumn()) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'متطوع غير صالح']);
    exit;
}

$ap = $pdo->prepare(
    "SELECT id, senior_id, volunteer_id, status FROM appointments WHERE id = ? LIMIT 1"
);
$ap->execute([$appointmentId]);
$appt = $ap->fetch();
if (! $appt) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'الموعد غير موجود']);
    exit;
}
if ((int) $appt['senior_id'] !== $me) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'غير مسموح']);
    exit;
}
if ((int) ($appt['volunteer_id'] ?? 0) !== $reviewed) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'المتطوع لا يطابق هذا الموعد']);
    exit;
}
if ((string) ($appt['status'] ?? '') !== 'completed') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'يُتاح التقييم بعد إكمال الموعد فقط']);
    exit;
}

$dup = $pdo->prepare('SELECT 1 FROM ratings WHERE reviewer_id = ? AND appointment_id = ? LIMIT 1');
$dup->execute([$me, $appointmentId]);
if ($dup->fetchColumn()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'سبق تسجيل تقييم لهذا الموعد']);
    exit;
}

try {
    require_once __DIR__ . '/includes/volunteer_rank612.php';
    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO ratings (reviewer_id, reviewed_id, session_id, appointment_id, rating_value, comment) VALUES (?, ?, NULL, ?, ?, NULL)'
    );
    $ins->execute([$me, $reviewed, $appointmentId, $val]);
    rafiq612_sync_volunteer_rating_stats($pdo, $reviewed);
    $newRank = rafiq612_recompute_volunteer_rank($pdo, $reviewed);
    $pdo->commit();
    echo json_encode(['ok' => true, 'promoted' => $newRank]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر حفظ التقييم']);
}
