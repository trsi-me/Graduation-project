<?php
/**
 * بدء الجلسة وترميز UTF-8
 */
declare(strict_types=1);

require_once __DIR__ . '/config612.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_start();
}
