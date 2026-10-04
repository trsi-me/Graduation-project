<?php
/**
 * تفاصيل موعد — زيادة الود
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ziyada_wud612.php', true, 302);
    exit;
}

$me = rafiq612_current_user_id();
$type = rafiq612_current_user_type();
$pdo = rafiq612_pdo();

if ($type === 'senior') {
    $st = $pdo->prepare(
        'SELECT a.*, u.full_name AS volunteer_name FROM appointments a
         LEFT JOIN users u ON u.id = a.volunteer_id
         WHERE a.id = ? AND a.senior_id = ?'
    );
    $st->execute([$id, $me]);
} else {
    $st = $pdo->prepare(
        'SELECT a.*, u.full_name AS senior_name FROM appointments a
         JOIN users u ON u.id = a.senior_id
         WHERE a.id = ? AND a.volunteer_id = ?'
    );
    $st->execute([$id, $me]);
}

$row = $st->fetch();
if (!$row) {
    header('Location: ziyada_wud612.php', true, 302);
    exit;
}

$hasRated = false;
if ($type === 'senior' && (string) ($row['status'] ?? '') === 'completed' && !empty($row['volunteer_id'])) {
    $rchk = $pdo->prepare('SELECT 1 FROM ratings WHERE reviewer_id = ? AND appointment_id = ?');
    $rchk->execute([$me, (int) $row['id']]);
    $hasRated = (bool) $rchk->fetchColumn();
}

$statusAr = [
    'pending' => 'قيد الانتظار',
    'confirmed' => 'مؤكد',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى',
];
$stLabel = $statusAr[(string) ($row['status'] ?? 'pending')] ?? htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8');

$title = htmlspecialchars((string) ($row['purpose'] ?? 'موعد'), ENT_QUOTES, 'UTF-8');
$otherName = $type === 'senior'
    ? htmlspecialchars((string) ($row['volunteer_name'] ?? 'لم يُعيَّن بعد'), ENT_QUOTES, 'UTF-8')
    : htmlspecialchars((string) ($row['senior_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$notesEsc = htmlspecialchars((string) ($row['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
$rawStatus = (string) ($row['status'] ?? 'pending');
$canAct = !in_array($rawStatus, ['completed', 'cancelled'], true);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> — زيادة الود | رفيقُ الجوار</title>
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
        <div class="nav-brand"><a href="index612.html" class="logo"><span class="logo-icon"><span class="infinity-symbol">♾️</span></span><span class="logo-text">رفيقُ الجوار</span></a></div>
        <div class="nav-menu">
            <ul class="nav-links">
                <li><a href="dashboard612.php">لوحة التحكم</a></li>
                <li><a href="ziyada_wud612.php" class="active">زيادة الود</a></li>
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<div class="container" style="padding: 2rem 0 3rem; max-width: 640px;">
    <p style="margin-bottom: 1rem;"><a href="ziyada_wud612.php" class="service-link"><i class="fas fa-arrow-right"></i> العودة لقائمة المواعيد</a></p>
    <div class="bridge-card" style="text-align: right;">
        <div class="bridge-icon" style="margin: 0 0 1rem auto;"><i class="fas fa-calendar-check"></i></div>
        <h1 class="section-title" style="font-size: 1.35rem; margin-bottom: 0.5rem;"><?= $title ?></h1>
        <p style="color: var(--color-text-light); margin-bottom: 1rem;">
            <span class="appt-status612"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 2;">
            <li><i class="fas fa-calendar-alt" style="color: var(--color-primary); width: 1.5rem;"></i>
                <?= htmlspecialchars((string) $row['appointment_date'], ENT_QUOTES, 'UTF-8') ?>
                — <?= htmlspecialchars(substr((string) $row['appointment_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
            </li>
            <li><i class="fas fa-user" style="color: var(--color-primary); width: 1.5rem;"></i>
                <?= $type === 'senior' ? 'المتطوع: ' : 'المستفيد: ' ?><?= $otherName ?>
            </li>
        </ul>
        <?php if ($notesEsc !== ''): ?>
            <div style="margin-top: 1.25rem; padding: 1rem; background: var(--color-background); border-radius: var(--radius-md);">
                <strong>ملاحظات</strong>
                <p style="margin: 0.5rem 0 0; white-space: pre-wrap;"><?= $notesEsc ?></p>
            </div>
        <?php endif; ?>
        <?php if ($type === 'senior' && !empty($row['volunteer_id']) && (string) $rawStatus === 'completed' && !$hasRated): ?>
            <div style="margin-top:1.5rem; padding:1rem; background: var(--color-background); border-radius: var(--radius-md);">
                <strong>تقييم المتطوع</strong> (يُساعد في الترقية والجودة)
                <div style="margin-top:0.75rem; display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                    <label>النجوم:</label>
                    <select id="rateVolunteer612" style="padding:0.25rem 0.5rem;">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                        <option value="<?= $s ?>"><?= $s ?> ⭐</option>
                        <?php endfor; ?>
                    </select>
                    <button type="button" class="btn btn-primary" id="btnSendRating612" data-vid="<?= (int) $row['volunteer_id'] ?>">إرسال التقييم</button>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($type === 'senior' && (string) $rawStatus === 'completed' && $hasRated): ?>
            <p style="margin-top:1rem; color: var(--color-text-light); font-size:0.9rem;">شكراً — سبق تسجيل تقييمك لهذا الموعد.</p>
        <?php endif; ?>
        <?php if ($type === 'senior' && !empty($row['volunteer_id'])): ?>
            <p style="margin-top: 1.5rem;">
                <a class="btn btn-outline" href="chat612.php?peer=<?= (int) $row['volunteer_id'] ?>"><i class="fas fa-comments"></i> مراسلة المتطوع</a>
            </p>
        <?php elseif ($type === 'volunteer'): ?>
            <p style="margin-top: 1.5rem;">
                <a class="btn btn-outline" href="chat612.php?peer=<?= (int) $row['senior_id'] ?>"><i class="fas fa-comments"></i> مراسلة المستفيد</a>
            </p>
        <?php endif; ?>
        <?php if ($canAct): ?>
            <div class="appt-actions612" style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <?php if ($rawStatus === 'confirmed'): ?>
                    <button type="button" class="btn btn-secondary" id="apptMarkDone612">تعليم الموعد كمكتمل</button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline" id="apptCancel612" style="border-color: var(--color-danger); color: var(--color-danger);">إلغاء الموعد</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>

<script src="main612.js"></script>
<script src="modal612.js"></script>
<?php if ($canAct): ?>
<script>
(function () {
    var apptId = '<?= (int) $row['id'] ?>';
    async function postStatus(status) {
        var fd = new FormData();
        fd.append('appointment_id', apptId);
        fd.append('status', status);
        var res = await fetch('api_appointment_status612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        return res.json();
    }
    var doneBtn = document.getElementById('apptMarkDone612');
    if (doneBtn) {
        doneBtn.addEventListener('click', async function () {
            if (!confirm('تأكيد إكمال الموعد؟')) return;
            try {
                var data = await postStatus('completed');
                if (data.ok) window.location.reload();
                else alert(data.error || 'تعذر الحفظ');
            } catch (e) { alert('تعذر الاتصال'); }
        });
    }
    document.getElementById('apptCancel612').addEventListener('click', async function () {
        if (!confirm('تأكيد إلغاء الموعد؟')) return;
        try {
            var data = await postStatus('cancelled');
            if (data.ok) window.location.reload();
            else alert(data.error || 'تعذر الحفظ');
        } catch (e) { alert('تعذر الاتصال'); }
    });
})();
</script>
<?php endif; ?>
<?php if ($type === 'senior' && (string) $rawStatus === 'completed' && !empty($row['volunteer_id']) && !$hasRated): ?>
<script>
(function () {
  var btn = document.getElementById('btnSendRating612');
  if (!btn) return;
  btn.addEventListener('click', async function () {
    var vid = btn.getAttribute('data-vid');
    var stars = document.getElementById('rateVolunteer612');
    var v = parseInt(stars && stars.value ? stars.value : '5', 10);
    var apptIdR = '<?= (int) $row['id'] ?>';
    var fd = new FormData();
    fd.append('appointment_id', apptIdR);
    fd.append('volunteer_id', vid);
    fd.append('rating_value', String(v));
    try {
      var res = await fetch('api_post_rating612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      var data = await res.json();
      if (data.ok) { window.location.reload(); }
      else { alert(data.error || 'تعذر الحفظ'); }
    } catch (e) { alert('تعذر الاتصال'); }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
