<?php
/**
 * قائمة المحادثات الأخيرة
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

$pdo = rafiq612_pdo();
$me = rafiq612_current_user_id();

$st = $pdo->prepare(
    'SELECT DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS oid
     FROM chat_messages WHERE sender_id = ? OR receiver_id = ?'
);
$st->execute([$me, $me, $me]);
$ids = array_column($st->fetchAll(), 'oid');

$conversations = [];
foreach ($ids as $oid) {
    $oid = (int) $oid;
    $u = $pdo->prepare('SELECT id, full_name, user_type FROM users WHERE id = ?');
    $u->execute([$oid]);
    $row = $u->fetch();
    if ($row) {
        $lm = $pdo->prepare(
            'SELECT message, ts FROM chat_messages
            WHERE (sender_id IN (?,?) AND receiver_id IN (?,?))
            ORDER BY ts DESC LIMIT 1'
        );
        $lm->execute([$me, $oid, $me, $oid]);
        $last = $lm->fetch();
        $row['last_message'] = $last['message'] ?? '';
        $row['last_ts'] = $last['ts'] ?? '';
        $conversations[] = $row;
    }
}

$userName = htmlspecialchars((string) ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محادثاتي - رفيقُ الجوار</title>
    <link rel="stylesheet" href="colors612.css">
    <link rel="stylesheet" href="main612.css">
    <link rel="stylesheet" href="responsive612.css">
    <link rel="stylesheet" href="modal612.css">
    <link rel="stylesheet" href="dashboard612.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <div class="nav-brand"><a href="index612.html" class="logo"><span class="logo-icon"></span><span class="logo-icon gradient">♾️</span><span class="logo-text">رفيقُ الجوار</span></a></div>
        <div class="nav-menu">
            <ul class="nav-links">
                <li><a href="index612.html">الرئيسية</a></li>
                <li><a href="dashboard612.php#ranks">نظام مراتب العطاء</a></li>
                <li><a href="dashboard612.php#communication-bridges">جسور التواصل</a></li>
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<div class="container" style="padding: 2rem 0;">
    <p style="margin-bottom: 1rem;"><a href="anis_al_rooh612.php" class="service-link"><i class="fas fa-th-large"></i> قسم أنيس الروح (قائمة ومحادثات)</a></p>
    <h1 class="section-title" style="margin-bottom: 1rem;"><i class="fas fa-comments"></i> محادثاتي</h1>
    <?php if (empty($conversations)): ?>
        <p style="color: var(--color-text-light);">لا توجد محادثات بعد. ابدأ من لوحة التحكم أو من صفحة المتطوعين.</p>
        <a href="dashboard612.php" class="btn btn-primary">العودة للوحة التحكم</a>
    <?php else: ?>
        <div class="bridges-grid" style="grid-template-columns: 1fr;">
            <?php foreach ($conversations as $c): ?>
                <div class="bridge-card" style="text-align: right;">
                    <h3><?= htmlspecialchars((string) $c['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="color: var(--color-text-light); font-size: 0.9rem;">
                        <?= htmlspecialchars(mb_substr((string) ($c['last_message'] ?? ''), 0, 120), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <a class="btn btn-primary" href="chat612.php?peer=<?= (int) $c['id'] ?>">فتح المحادثة</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="main612.js"></script>
<script src="modal612.js"></script>
</body>
</html>
