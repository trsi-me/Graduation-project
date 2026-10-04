<?php
/**
 * المتطوع يستلم موعداً لم يُعيَّن له أحد بعد
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

$apid = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
if ($apid <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'معرّف غير صالح']);
    exit;
}

$vid = rafiq612_current_user_id();
$pdo = rafiq612_pdo();
try {
    $st = $pdo->prepare(
        "UPDATE appointments SET volunteer_id = ?, status = 'confirmed'
         WHERE id = ? AND volunteer_id IS NULL AND status = 'pending'"
    );
    $st->execute([$vid, $apid]);
    if ($st->rowCount() === 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'الموعد غير متاح أو تم تعيين متطوع آخر']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'تم ربط الموعد بك كمتطوع مؤكد']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر الحفظ']);
}
