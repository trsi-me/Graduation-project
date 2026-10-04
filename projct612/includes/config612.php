<?php
/**
 * إعداد الاتصال بقاعدة البيانات — رفيقُ الجوار
 * يمكن ضبط القيم عبر متغيرات البيئة أو تعديل الثوابت أدناه.
 */
declare(strict_types=1);

/** وضع التطوير: true يعرض الأخطاء — عطّله في الإنتاج */
const RAFIQ612_DEBUG = true;

if (RAFIQ612_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

define('RAFIQ612_DB_HOST', getenv('RAFIQ_DB_HOST') ?: 'localhost');
define('RAFIQ612_DB_NAME', getenv('RAFIQ_DB_NAME') ?: 'rafiq_al_jewar');
define('RAFIQ612_DB_USER', getenv('RAFIQ_DB_USER') ?: 'root');
define('RAFIQ612_DB_PASS', getenv('RAFIQ_DB_PASS') !== false ? getenv('RAFIQ_DB_PASS') : '');
define('RAFIQ612_DB_CHARSET', 'utf8mb4');

/**
 * @return PDO
 */
function rafiq612_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . RAFIQ612_DB_HOST . ';dbname=' . RAFIQ612_DB_NAME . ';charset=' . RAFIQ612_DB_CHARSET;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . RAFIQ612_DB_CHARSET,
    ];
    $pdo = new PDO($dsn, RAFIQ612_DB_USER, RAFIQ612_DB_PASS, $opts);
    return $pdo;
}
