<?php
/**
 * معالج إنشاء حساب (JSON)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$name = rafiq612_clean_string($_POST['full_name'] ?? '', 100);
$phone = preg_replace('/\s+/', '', (string) ($_POST['phone'] ?? ''));
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$pass = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$userType = $_POST['user_type'] ?? 'senior';

if (!in_array($userType, ['senior', 'volunteer'], true)) {
    $userType = 'senior';
}

if ($name === '' || strlen($phone) < 8 || !$email || strlen((string) $pass) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'يرجى تعبئة جميع الحقول بشكل صحيح (كلمة المرور 8 أحرف على الأقل).']);
    exit;
}
if ($pass !== $confirm) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'كلمتا المرور غير متطابقتين']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'البريد الإلكتروني غير صالح']);
    exit;
}

$hash = password_hash((string) $pass, PASSWORD_DEFAULT);

try {
    $pdo = rafiq612_pdo();
    $st = $pdo->prepare(
        'INSERT INTO users (full_name, phone, email, password_hash, user_type) VALUES (?,?,?,?,?)'
    );
    $st->execute([$name, $phone, $email, $hash, $userType]);
    $id = (int) $pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_type'] = $userType;
    echo json_encode(['ok' => true, 'redirect' => 'dashboard612.php']);
} catch (PDOException $e) {
    if ((int) $e->getCode() === 23000) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'البريد أو الهاتف مسجّل مسبقاً']);
        exit;
    }
    if (RAFIQ612_DEBUG) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'تعذر إنشاء الحساب']);
    }
}
