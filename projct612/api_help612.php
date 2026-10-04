<?php
/**
 * تسجيل طلب مساعدة فوري + تنبيه طارئ
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
    echo json_encode(['ok' => false, 'error' => 'هذا الطلب مخصص لكبار السن']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$uid = rafiq612_current_user_id();
$type = rafiq612_clean_string($_POST['request_type'] ?? '', 100);
$desc = rafiq612_clean_string($_POST['description'] ?? '', 2000);
$lat = isset($_POST['location_lat']) ? filter_var($_POST['location_lat'], FILTER_VALIDATE_FLOAT) : null;
$lng = isset($_POST['location_lng']) ? filter_var($_POST['location_lng'], FILTER_VALIDATE_FLOAT) : null;

if ($type === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'اختر نوع المساعدة']);
    exit;
}

$pdo = rafiq612_pdo();
try {
    $pdo->beginTransaction();
    $st = $pdo->prepare(
        'INSERT INTO help_requests (user_id, request_type, description, location_lat, location_lng, status) VALUES (?,?,?,?,?,?)'
    );
    $st->execute([$uid, $type, $desc !== '' ? $desc : null, $lat, $lng, 'pending']);
    $hid = (int) $pdo->lastInsertId();

    $notes = 'نوع: ' . $type . ($desc ? ' | ' . $desc : '') . ' | طلب رقم ' . $hid;
    $st2 = $pdo->prepare(
        'INSERT INTO emergency_alerts (user_id, status, notes) VALUES (?, ?, ?)'
    );
    $st2->execute([$uid, 'pending', $notes]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'id' => $hid, 'message' => 'تم استلام الطلب وسيُبلَّغ المتطوعون قريب منك.']);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر حفظ الطلب']);
}
