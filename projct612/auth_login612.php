<?php
/**
 * معالج تسجيل الدخول (JSON)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config612.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !is_string($password) || $password === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'بيانات ناقصة']);
    exit;
}

try {
    $pdo = rafiq612_pdo();
    $st = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, user_type, is_active FROM users WHERE email = ? LIMIT 1'
    );
    $st->execute([$email]);
    $row = $st->fetch();
    if (!$row || empty($row['password_hash']) || !(bool) $row['is_active']) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'البريد أو كلمة المرور غير صحيحة']);
        exit;
    }
    if (!password_verify($password, $row['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'البريد أو كلمة المرور غير صحيحة']);
        exit;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['user_name'] = $row['full_name'];
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_type'] = $row['user_type'];
    echo json_encode([
        'ok' => true,
        'redirect' => 'dashboard612.php',
        'user' => [
            'id' => (int) $row['id'],
            'name' => $row['full_name'],
            'type' => $row['user_type'],
        ],
    ]);
} catch (Throwable $e) {
    if (RAFIQ612_DEBUG) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'خطأ في الخادم']);
    }
}
