<?php
/**
 * إجراءات على طلب المساعدة — قبول من المتطوع
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (!rafiq612_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

if (rafiq612_current_user_type() !== 'volunteer') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'للمتطوعين فقط']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$id = isset($_POST['help_id']) ? (int) $_POST['help_id'] : 0;
$action = rafiq612_clean_string($_POST['action'] ?? 'accept', 20);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'معرّف غير صالح']);
    exit;
}

if ($action !== 'accept') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'إجراء غير مدعوم']);
    exit;
}

$pdo = rafiq612_pdo();
try {
    $vid = rafiq612_current_user_id();
    $st = $pdo->prepare(
        "UPDATE help_requests SET status = 'accepted', volunteer_id = ?, responded_at = NOW() WHERE id = ? AND status = 'pending'"
    );
    $st->execute([$vid, $id]);
    if ($st->rowCount() === 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'الطلب غير متاح أو تمت معالجته']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'تم قبول الطلب. يُفضّل التواصل مع صاحب الطلب فوراً.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر التحديث']);
}
