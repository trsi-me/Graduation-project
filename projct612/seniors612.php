<?php
/**
 * المستفيدون — للمتطوع: اختيار كبير سن لبدء محادثة (أنيس الروح)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

if (rafiq612_current_user_type() !== 'volunteer') {
    header('Location: dashboard612.php', true, 302);
    exit;
}

$pdo = rafiq612_pdo();
$st = $pdo->query(
    "SELECT id, full_name, rating, rank FROM users WHERE user_type = 'senior' AND is_active = 1 ORDER BY full_name ASC"
);
$seniors = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المستفيدون — رفيقُ الجوار</title>
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
            <h2 class="section-title"><i class="fas fa-users"></i> المستفيدون</h2>
            <p class="section-subtitle">اختر مستفيداً لبدء حوار عبر «أنيس الروح»</p>
        </div>
        <div class="bridges-grid">
            <?php foreach ($seniors as $s): ?>
                <div class="bridge-card" style="text-align: center;">
                    <div class="bridge-icon"><i class="fas fa-user"></i></div>
                    <h3><?= htmlspecialchars((string) $s['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="color: var(--color-text-light);">التقييم: <?= htmlspecialchars((string) $s['rating'], ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="chat612.php?peer=<?= (int) $s['id'] ?>" class="btn btn-primary">بدء المحادثة</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($seniors)): ?>
            <p style="text-align: center; color: var(--color-text-light);">لا يوجد مستفيدون مفعّلون حالياً.</p>
        <?php endif; ?>
        <p style="text-align: center; margin-top: 2rem;"><a href="anis_al_rooh612.php" class="service-link">← العودة لأنيس الروح</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>

<script src="main612.js"></script>
<script src="modal612.js"></script>
</body>
</html>
