<?php
/**
 * اختبار بسيط لمرتبة سفير الحكمة (سؤال واحد)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

if (rafiq612_current_user_type() !== 'volunteer') {
    header('Location: dashboard612.php', true, 302);
    exit;
}

require_once __DIR__ . '/includes/volunteer_rank612.php';

$pdo = rafiq612_pdo();
$sel = ['id', 'full_name', 'rating'];
if (rafiq612_users_has_column($pdo, 'tasks_count')) {
    $sel[] = 'tasks_count';
}
if (rafiq612_users_has_column($pdo, 'hikma_test_passed')) {
    $sel[] = 'hikma_test_passed';
}
$st = $pdo->prepare('SELECT ' . implode(', ', $sel) . ' FROM users WHERE id = ? AND user_type = ? AND is_active = 1');
$st->execute([rafiq612_current_user_id(), 'volunteer']);
$me = $st->fetch();
if (! $me) {
    header('Location: login612.php', true, 302);
    exit;
}
$me = rafiq612_user_row_fill_defaults($pdo, $me);

$vid = (int) $me['id'];
$avg = rafiq612_avg_rating_from_reviews($pdo, $vid);
$eff = $avg !== null ? $avg : (float) $me['rating'];
$tasksCount = rafiq612_users_has_column($pdo, 'tasks_count')
    ? (int) $me['tasks_count']
    : rafiq612_rebuild_volunteer_tasks_count($pdo, $vid);
$eligible = $tasksCount >= 20 && $eff >= 4.5 && (int) $me['hikma_test_passed'] === 0;

$error = '';
$done = (int) $me['hikma_test_passed'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! $done && $eligible) {
    $ans = (int) ($_POST['q1'] ?? 0);
    if ($ans === 2) {
        if (rafiq612_users_has_column($pdo, 'hikma_test_passed')) {
            $pdo->prepare('UPDATE users SET hikma_test_passed = 1 WHERE id = ?')->execute([$vid]);
        }
        rafiq612_recompute_volunteer_rank($pdo, $vid);
        header('Location: safir_test612.php?ok=1', true, 302);
        exit;
    }
    $error = 'إجابة غير صحيحة — تمعّن في خيار يعكس الاحترام والأمان مع كبار السن.';
}

$ok = isset($_GET['ok']) && (string) $_GET['ok'] === '1';
$justPassed = $ok;
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار سفير الحكمة — رفيقُ الجوار</title>
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
            <ul class="nav-links"><li><a href="dashboard612.php" class="active">لوحة التحكم</a></li></ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
        </div>
    </div>
</nav>
<div class="container" style="padding:2rem 0 3rem; max-width: 640px;">
    <h1 class="section-title" style="font-size:1.3rem; margin-bottom:1rem;">اختبار بسيط — سفير الحكمة</h1>
    <?php if ($justPassed): ?>
        <div class="note-box" style="margin-top:0;">
            <i class="fas fa-check-circle"></i>
            <p>تم تسجيل اجتياز الاختبار. راجع <a href="dashboard612.php#ranks">رتبتك</a> في لوحة التحكم.</p>
        </div>
    <?php elseif ($done): ?>
        <div class="note-box" style="margin-top:0;">
            <i class="fas fa-check-circle"></i>
            <p>سبق أن اجتزت هذا الاختبار. ارجع إلى <a href="dashboard612.php#ranks">لوحة التحكم</a>.</p>
        </div>
    <?php elseif (! $eligible): ?>
        <p class="ranks-hint">يُتاح هذا الاختبار عند <strong>20 مهمة</strong> موثّقة على الأقل و<strong>معدل تقييم 4.5</strong> فأعلى. واصل العطاء ثم عُد.</p>
    <?php else: ?>
        <?php if ($error !== ''): ?><p style="color: var(--color-danger); margin-bottom:1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form method="post" class="bridge-card" style="text-align:right;">
            <p style="margin-bottom:1rem;">سؤال واحد: ما أهم أولوية عند زيارة كبير سن في بيته؟</p>
            <div class="form-group">
                <label><input type="radio" name="q1" value="1" required> الإسراع بإنهاء الزيارة</label>
            </div>
            <div class="form-group">
                <label><input type="radio" name="q1" value="2" required> الطمأنينة والاحترام لخصوصيته وإيقاعه</label>
            </div>
            <div class="form-group">
                <label><input type="radio" name="q1" value="3" required> تقديم نصائح طبية مباشرة</label>
            </div>
            <button type="submit" class="btn btn-primary">إرسال</button>
        </form>
    <?php endif; ?>
    <p style="margin-top:1.5rem;"><a href="dashboard612.php" class="service-link">← العودة للوحة التحكم</a></p>
</div>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="main612.js"></script>
<script src="modal612.js"></script>
</body>
</html>
