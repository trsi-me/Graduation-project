<?php
/**
 * قائمة المتطوعين النشطين (لكبار السن)
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
    echo json_encode(['ok' => false, 'error' => 'هذه القائمة مخصصة لكبار السن']);
    exit;
}

try {
    $pdo = rafiq612_pdo();
    $st = $pdo->query(
        "SELECT id, full_name, rating, rank FROM users WHERE user_type = 'volunteer' AND is_active = 1 ORDER BY full_name ASC"
    );
    $rows = $st->fetchAll();
    echo json_encode(['ok' => true, 'volunteers' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'خطأ']);
}
