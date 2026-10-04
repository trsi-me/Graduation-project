<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

$peer = isset($_GET['peer']) ? (int) $_GET['peer'] : 0;
$me = rafiq612_current_user_id();
$myType = rafiq612_current_user_type();

if ($peer <= 0 || $peer === $me) {
    header('Location: dashboard612.php', true, 302);
    exit;
}

$pdo = rafiq612_pdo();
$st = $pdo->prepare('SELECT id, full_name, user_type FROM users WHERE id = ? AND is_active = 1');
$st->execute([$peer]);
$other = $st->fetch();

if (!$other || $myType === $other['user_type']) {
    header('Location: dashboard612.php', true, 302);
    exit;
}

$name = htmlspecialchars((string) $other['full_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محادثة — <?= $name ?> | رفيقُ الجوار</title>
    <link rel="stylesheet" href="colors612.css">
    <link rel="stylesheet" href="main612.css">
    <link rel="stylesheet" href="responsive612.css">
    <link rel="stylesheet" href="modal612.css">
    <link rel="stylesheet" href="dashboard612.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="chat612-body">
<nav class="navbar">
    <div class="container">
        <div class="nav-brand"><a href="dashboard612.php" class="logo"><span class="logo-icon"><span class="infinity-symbol">♾️</span></span><span class="logo-text">رفيقُ الجوار</span></a></div>
        <div class="nav-actions">
            <a href="anis_al_rooh612.php" class="btn btn-outline">أنيس الروح</a>
            <a href="dashboard612.php" class="btn btn-outline">لوحة التحكم</a>
            <a href="logout612.php" class="btn btn-outline">تسجيل خروج</a>
        </div>
    </div>
</nav>

<div class="container chat612-wrap">
    <div class="chat612-header">
        <h2><i class="fas fa-comments"></i> <?= $name ?></h2>
        <button type="button" id="chat612EndBtn" class="btn btn-outline">إنهاء المحادثة</button>
    </div>
    <div id="chat612Messages" class="chat612-messages" data-me="<?= (int) $me ?>" data-peer="<?= (int) $peer ?>" data-my-type="<?= htmlspecialchars($myType, ENT_QUOTES, 'UTF-8') ?>"></div>
    <form id="chat612Form" class="chat612-form">
        <textarea id="chat612Input" rows="2" maxlength="4000" placeholder="اكتب رسالتك..." required></textarea>
        <button type="submit" class="btn btn-primary">إرسال</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="chat612.js"></script>
<script src="main612.js"></script>
<script src="modal612.js"></script>
</body>
</html>
