<?php
/**
 * دوال الجلسة والمصادقة
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap612.php';

function rafiq612_is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

function rafiq612_require_login(): void
{
    if (!rafiq612_is_logged_in()) {
        header('Location: login612.php', true, 302);
        exit;
    }
}

function rafiq612_current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function rafiq612_current_user_type(): ?string
{
    return $_SESSION['user_type'] ?? null;
}

function rafiq612_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/**
 * تنقية نص إدخال عام
 */
function rafiq612_clean_string(?string $s, int $max = 5000): string
{
    if ($s === null) {
        return '';
    }
    $s = trim($s);
    if (mb_strlen($s) > $max) {
        $s = mb_substr($s, 0, $max);
    }
    return $s;
}
