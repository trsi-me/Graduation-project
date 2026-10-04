<?php
/**
 * مواعيد زيادة الود — عرض وإنشاء
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
header('Content-Type: application/json; charset=utf-8');

if (!rafiq612_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

$uid = rafiq612_current_user_id();
$type = rafiq612_current_user_type();
$pdo = rafiq612_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($type === 'senior') {
            $st = $pdo->prepare(
                'SELECT a.*, u.full_name AS volunteer_name FROM appointments a
                LEFT JOIN users u ON u.id = a.volunteer_id
                WHERE a.senior_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC'
            );
            $st->execute([$uid]);
        } else {
            $st = $pdo->prepare(
                'SELECT a.*, u.full_name AS senior_name FROM appointments a
                JOIN users u ON u.id = a.senior_id
                WHERE a.volunteer_id = ?
                ORDER BY a.appointment_date ASC, a.appointment_time ASC'
            );
            $st->execute([$uid]);
        }
        $rows = $st->fetchAll();
        echo json_encode(['ok' => true, 'appointments' => $rows]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'خطأ']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if ($type !== 'senior') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'حجز المواعيد متاح لكبار السن فقط']);
    exit;
}

$purpose = rafiq612_clean_string($_POST['purpose'] ?? '', 255);
$dt = $_POST['appointment_datetime'] ?? '';
$address = rafiq612_clean_string($_POST['address'] ?? '', 255);
$notes = rafiq612_clean_string($_POST['notes'] ?? '', 2000);
$volunteerId = isset($_POST['volunteer_id']) && $_POST['volunteer_id'] !== ''
    ? (int) $_POST['volunteer_id'] : null;

if ($purpose === '' || !is_string($dt) || strlen($dt) < 10) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'يرجى اختيار نوع الموعد والتاريخ']);
    exit;
}

if (!preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/', $dt, $m)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'صيغة التاريخ غير صالحة']);
    exit;
}
$d = $m[1];
$t = $m[2] . ':00';

try {
    if ($volunteerId) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE id = ? AND user_type = ? AND is_active = 1');
        $chk->execute([$volunteerId, 'volunteer']);
        if (!$chk->fetch()) {
            $volunteerId = null;
        }
    }
    if ($address !== '') {
        $up = $pdo->prepare('UPDATE users SET address = ? WHERE id = ?');
        $up->execute([$address, $uid]);
    }
    $notesFull = $notes;
    if ($address !== '') {
        $notesFull = ($notesFull !== '' ? $notesFull . ' | ' : '') . 'العنوان: ' . $address;
    }
    $ins = $pdo->prepare(
        'INSERT INTO appointments (senior_id, volunteer_id, appointment_date, appointment_time, purpose, status, notes)
         VALUES (?,?,?,?,?,?,?)'
    );
    $ins->execute([$uid, $volunteerId, $d, $t, $purpose, 'pending', $notesFull !== '' ? $notesFull : null]);
    echo json_encode(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => RAFIQ612_DEBUG ? $e->getMessage() : 'تعذر حفظ الموعد']);
}
