<?php
/**
 * طلبات المساعدة المعلقة — للمتطوعين (لوحة التنبيهات)
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

try {
    $pdo = rafiq612_pdo();
    $st = $pdo->query(
        "SELECT h.id, h.request_type, h.description, h.status, h.created_at, h.location_lat, h.location_lng,
                u.full_name AS requester_name, u.phone
         FROM help_requests h
         JOIN users u ON u.id = h.user_id
         WHERE h.status = 'pending'
         ORDER BY h.created_at DESC
         LIMIT 50"
    );
    echo json_encode(['ok' => true, 'requests' => $st->fetchAll()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'خطأ']);
}
