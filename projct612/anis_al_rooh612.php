<?php
/**
 * قسم أنيس الروح — محادثات + اختيار رفيق للحديث
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

$pdo = rafiq612_pdo();
$me = rafiq612_current_user_id();
$isSenior = rafiq612_current_user_type() === 'senior';

$stPeers = $pdo->prepare(
    'SELECT DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS oid
     FROM chat_messages WHERE sender_id = ? OR receiver_id = ?'
);
$stPeers->execute([$me, $me, $me]);
$ids = array_column($stPeers->fetchAll(), 'oid');

$conversations = [];
foreach ($ids as $oid) {
    $oid = (int) $oid;
    $u = $pdo->prepare('SELECT id, full_name, user_type FROM users WHERE id = ? AND is_active = 1');
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

if ($isSenior) {
    $volSt = $pdo->query(
        "SELECT id, full_name, rating, rank FROM users WHERE user_type = 'volunteer' AND is_active = 1 ORDER BY full_name ASC"
    );
    $pickList = $volSt->fetchAll();
    $pickTitle = 'اختر متطوعاً';
    $pickSubtitle = 'ابدأ محادثة آمنة مع رفيق يختاره قلبك';
    $pickHref = 'chat612.php?peer=';
} else {
    $senSt = $pdo->query(
        "SELECT id, full_name, rating, rank FROM users WHERE user_type = 'senior' AND is_active = 1 ORDER BY full_name ASC"
    );
    $pickList = $senSt->fetchAll();
    $pickTitle = 'اختر مستفيداً';
    $pickSubtitle = 'تواصل مع مستفيدك عبر المحادثة';
    $pickHref = 'chat612.php?peer=';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أنيس الروح — رفيقُ الجوار</title>
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
        <div class="nav-brand"><a href="dashboard612.php" class="logo"><span class="logo-icon"><span class="infinity-symbol">♾️</span></span><span class="logo-text">رفيقُ الجوار</span></a></div>
        <div class="nav-menu">
            <ul class="nav-links">
                <li><a href="dashboard612.php">لوحة التحكم</a></li>
                <li><a href="anis_al_rooh612.php" class="active">أنيس الروح</a></li>
                <li><a href="ziyada_wud612.php">زيادة الود</a></li>
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<section class="dashboard-bridges" style="padding-top: 2rem;">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-comments"></i> أنيس الروح</h1>
            <p class="section-subtitle">محادثاتك واختيار من تريد التحدث معه</p>
        </div>

        <h2 class="section-title anis-section-title612"><i class="fas fa-user-friends"></i> <?= htmlspecialchars($pickTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <p style="color: var(--color-text-light); margin: -0.5rem 0 1.25rem;"><?= htmlspecialchars($pickSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (empty($pickList)): ?>
            <p style="color: var(--color-text-light); margin-bottom: 2rem;">لا يوجد حسابات متاحة في هذه الفئة حالياً.</p>
        <?php else: ?>
            <div class="bridges-grid anis-pick-grid612"><?php foreach ($pickList as $p): ?>
                <a class="bridge-card anis-pick-card612" href="<?= $pickHref . (int) $p['id'] ?>">
                    <div class="bridge-icon"><i class="fas fa-comment-dots"></i></div>
                    <h3><?= htmlspecialchars((string) $p['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="color: var(--color-text-light);">التقييم: <?= htmlspecialchars((string) $p['rating'], ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="service-link">فتح المحادثة ←</span>
                </a>
            <?php endforeach; ?></div>
        <?php endif; ?>

        <h2 class="section-title anis-section-title612" style="margin-top: 2.5rem;"><i class="fas fa-inbox"></i> محادثاتي الأخيرة</h2>
        <p style="color: var(--color-text-light); margin: -0.5rem 0 1.25rem;">اضغط لفتح سجل الرسائل مع هذا الشخص</p>
        <?php if (empty($conversations)): ?>
            <p style="color: var(--color-text-light);">لا توجد محادثات بعد. اختر أحداً من الأعلى لبدء أول حديث.</p>
        <?php else: ?>
            <div class="appt-list612 anis-conv-list612"><?php foreach ($conversations as $c): ?>
                <a class="appt-row612 appt-row-link612 anis-conv-row612" href="chat612.php?peer=<?= (int) $c['id'] ?>">
                    <strong><?= htmlspecialchars((string) $c['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span style="font-size: 0.9rem; color: var(--color-text-light);"><?= htmlspecialchars(mb_substr((string) ($c['last_message'] ?? ''), 0, 80), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="appt-status612" style="font-size: 0.8rem;"><?= htmlspecialchars((string) ($c['last_ts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?></div>
        <?php endif; ?>

        <p style="margin-top: 2.5rem;">
            <?php if ($isSenior): ?>
                <a href="volunteers612.php" class="service-link" style="margin-left: 1rem;">عرض المتطوعين في صفحة مستقلة</a>
            <?php else: ?>
                <a href="seniors612.php" class="service-link" style="margin-left: 1rem;">عرض المستفيدين في صفحة مستقلة</a>
            <?php endif; ?>
            <a href="messages612.php" class="service-link">قائمة المحادثات الكلاسيكية</a>
        </p>
        <p style="margin-top: 1rem;"><a href="dashboard612.php#communication-bridges" class="service-link">← العودة لجسور التواصل</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="main612.js"></script>
<script src="modal612.js"></script>
</body>
</html>
