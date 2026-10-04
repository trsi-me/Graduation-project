<?php
/**
 * قسم زيادة الود — قائمة مواعيد + حجز جديد
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

$pdo = rafiq612_pdo();
$st = $pdo->prepare('SELECT id, full_name, email, user_type, address, phone FROM users WHERE id = ? AND is_active = 1');
$st->execute([rafiq612_current_user_id()]);
$user = $st->fetch();
if (!$user) {
    rafiq612_logout();
    header('Location: login612.php', true, 302);
    exit;
}

$appointments = [];
if ($user['user_type'] === 'senior') {
    $qa = $pdo->prepare(
        'SELECT a.*, u.full_name AS volunteer_name FROM appointments a
         LEFT JOIN users u ON u.id = a.volunteer_id WHERE a.senior_id = ?
         ORDER BY a.appointment_date DESC, a.appointment_time DESC'
    );
    $qa->execute([(int) $user['id']]);
    $appointments = $qa->fetchAll();
} else {
    $qa = $pdo->prepare(
        'SELECT a.*, u.full_name AS senior_name FROM appointments a
         JOIN users u ON u.id = a.senior_id WHERE a.volunteer_id = ?
         ORDER BY a.appointment_date DESC, a.appointment_time DESC'
    );
    $qa->execute([(int) $user['id']]);
    $appointments = $qa->fetchAll();
}

$today = date('Y-m-d');
$upcoming = [];
$past = [];
foreach ($appointments as $a) {
    $d = (string) $a['appointment_date'];
    $stc = (string) ($a['status'] ?? '');
    if ($d >= $today && !in_array($stc, ['cancelled', 'completed'], true)) {
        $upcoming[] = $a;
    } else {
        $past[] = $a;
    }
}

$addrEsc = htmlspecialchars((string) ($user['address'] ?? ''), ENT_QUOTES, 'UTF-8');
$isSenior = $user['user_type'] === 'senior';

$pool = [];
if (!$isSenior) {
    $qp = $pdo->query(
        "SELECT a.*, u.full_name AS senior_name, u.phone AS senior_phone FROM appointments a
         INNER JOIN users u ON u.id = a.senior_id
         WHERE a.volunteer_id IS NULL AND a.status = 'pending'
         ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 50"
    );
    $pool = $qp->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زيادة الود — رفيقُ الجوار</title>
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
                <li><a href="anis_al_rooh612.php">أنيس الروح</a></li>
                <li><a href="ziyada_wud612.php" class="active">زيادة الود</a></li>
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<section class="dashboard-bridges" style="padding-top: 2rem;">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-heart"></i> زيادة الود</h1>
            <p class="section-subtitle">مواعيد المرافقة والزيارات — اضغط على أي موعد لعرض التفاصيل</p>
        </div>
        <?php if ($isSenior): ?>
            <p style="margin-bottom: 1.5rem;">
                <button type="button" class="btn btn-primary" data-open-appointment612><i class="fas fa-calendar-plus"></i> حجز موعد جديد</button>
            </p>
        <?php else: ?>
            <h2 class="section-title" style="font-size:1.15rem;margin:1.5rem 0 1rem;"><i class="fas fa-hand-holding-heart"></i> مواعيد بانتظار متطوع</h2>
            <?php if (empty($pool)): ?>
                <p style="color: var(--color-text-light); margin-bottom: 2rem;">لا توجد مواعيد مفتوحة حالياً. ستظهر هنا عندما يحجز مستفيد موعداً دون اختيار متطوع.</p>
            <?php else: ?>
                <div class="appt-list612" style="margin-bottom: 2.5rem;"><?php foreach ($pool as $p): ?>
                    <div class="pending-help-item612">
                        <strong><?= htmlspecialchars((string) ($p['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="ph-time612"><?= htmlspecialchars((string) $p['appointment_date'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $p['appointment_time'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p style="margin:0.35rem 0 0;"><?= htmlspecialchars((string) ($p['senior_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string) ($p['senior_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="ph-actions612">
                            <button type="button" class="btn btn-primary btn-sm612" data-claim-appt612="<?= (int) $p['id'] ?>">أنا أتولّى الموعد</button>
                        </div>
                    </div>
                <?php endforeach; ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="section-title" style="font-size:1.15rem;margin:1.5rem 0 1rem;"><?= $isSenior ? 'المواعيد القادمة' : 'مواعيدي المؤكدة القادمة' ?></h2>
        <?php if (empty($upcoming)): ?>
            <p style="color: var(--color-text-light); margin-bottom: 2rem;">لا توجد مواعيد قادمة.</p>
        <?php else: ?>
            <div class="appt-list612"><?php foreach ($upcoming as $r): ?>
                <a class="appt-row612 appt-row-link612" href="appointment_detail612.php?id=<?= (int) $r['id'] ?>">
                    <strong><?= htmlspecialchars((string) ($r['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars((string) $r['appointment_date'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) $r['appointment_time'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="appt-status612"><?= htmlspecialchars((string) ($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <small style="grid-column: 1 / -1;"><?= $isSenior ? htmlspecialchars((string) ($r['volunteer_name'] ?? 'لم يُعيَّن بعد'), ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) ($r['senior_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </a>
            <?php endforeach; ?></div>
        <?php endif; ?>

        <h2 class="section-title" style="font-size:1.15rem;margin:2rem 0 1rem;">المواعيد السابقة</h2>
        <?php if (empty($past)): ?>
            <p style="color: var(--color-text-light);">لا توجد مواعيد منتهية.</p>
        <?php else: ?>
            <div class="appt-list612"><?php foreach ($past as $r): ?>
                <a class="appt-row612 appt-past612 appt-row-link612" href="appointment_detail612.php?id=<?= (int) $r['id'] ?>">
                    <strong><?= htmlspecialchars((string) ($r['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars((string) $r['appointment_date'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="appt-status612"><?= htmlspecialchars((string) ($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?></div>
        <?php endif; ?>

        <p style="margin-top: 2rem;"><a href="dashboard612.php#communication-bridges" class="service-link">← العودة لجسور التواصل</a></p>
    </div>
</section>

<?php if ($isSenior): ?>
<div id="modalAppointment612" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>حجز موعد — زيادة الود</h2>
            <span class="close-modal" data-close-modal612="modalAppointment612">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formAppointment612">
                <div class="form-group">
                    <label>نوع الموعد</label>
                    <select name="purpose" id="appointmentPurpose612" required>
                        <option value="">— اختر —</option>
                        <option>مرافقة للحديقة</option>
                        <option>شرب قهوة</option>
                        <option>مرافقة لمستشفى</option>
                        <option>زيارة منزلية</option>
                        <option>جلسة استماع</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>التاريخ والوقت</label>
                    <input type="datetime-local" id="appointmentDatetime612" required>
                </div>
                <div class="form-group">
                    <label>عنوان المستفيد</label>
                    <input type="text" name="address" value="<?= $addrEsc ?>" placeholder="الحي، الشارع" autocomplete="street-address">
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" rows="2" maxlength="2000"></textarea>
                </div>
                <input type="hidden" name="volunteer_id" value="">
                <p class="note-box" style="font-size:0.9rem;">يُخزَّن الموعد بحالة «قيد الانتظار» حتى يتم التنسيق مع متطوع.</p>
                <button type="submit" class="btn btn-primary btn-block">تأكيد الحجز</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="main612.js"></script>
<script src="modal612.js"></script>
<script src="dashboard612.js"></script>
<?php if (!$isSenior): ?>
<script>
(function () {
    document.addEventListener('click', async function (e) {
        var btn = e.target.closest('[data-claim-appt612]');
        if (!btn) return;
        var id = btn.getAttribute('data-claim-appt612');
        if (!confirm('تأكيد استلام هذا الموعد وربطه بحسابك؟')) return;
        btn.disabled = true;
        try {
            var fd = new FormData();
            fd.append('appointment_id', id);
            var res = await fetch('api_claim_appointment612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            var data = await res.json();
            if (data.ok) {
                window.location.reload();
            } else {
                alert(data.error || 'تعذر الاستلام');
                btn.disabled = false;
            }
        } catch (err) {
            alert('تعذر الاتصال بالخادم');
            btn.disabled = false;
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
