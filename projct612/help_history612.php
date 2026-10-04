<?php
/**
 * سجل طلبات المساعدة الفورية — لكبار السن
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

if (rafiq612_current_user_type() !== 'senior') {
    header('Location: dashboard612.php', true, 302);
    exit;
}

$me = rafiq612_current_user_id();
$pdo = rafiq612_pdo();
require_once __DIR__ . '/includes/volunteer_rank612.php';
if (rafiq612_help_requests_has_volunteer_id($pdo)) {
    $st = $pdo->prepare(
        'SELECT h.id, h.request_type, h.description, h.status, h.created_at, h.responded_at,
                h.location_lat, h.location_lng, h.volunteer_id, v.full_name AS volunteer_name
         FROM help_requests h
         LEFT JOIN users v ON v.id = h.volunteer_id
         WHERE h.user_id = ? ORDER BY h.created_at DESC LIMIT 100'
    );
} else {
    $st = $pdo->prepare(
        'SELECT h.id, h.request_type, h.description, h.status, h.created_at, h.responded_at,
                h.location_lat, h.location_lng, NULL AS volunteer_id, NULL AS volunteer_name
         FROM help_requests h
         WHERE h.user_id = ? ORDER BY h.created_at DESC LIMIT 100'
    );
}
$st->execute([$me]);
$rows = $st->fetchAll();

$statusAr = [
    'pending' => 'قيد الانتظار',
    'accepted' => 'تم القبول',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل طلبات المساعدة — رفيقُ الجوار</title>
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
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<div class="container" style="padding: 2rem 0;">
    <h1 class="section-title"><i class="fas fa-history"></i> سجل طلبات المساعدة</h1>
    <p style="color: var(--color-text-light); margin-bottom: 1.5rem;">آخر طلباتك الفورية وحالتها</p>
    <?php if (empty($rows)): ?>
        <p style="color: var(--color-text-light);">لم تُرسل أي طلب بعد.</p>
    <?php else: ?>
        <div class="appt-list612"><?php foreach ($rows as $r): ?>
            <div class="appt-row612">
                <strong><?= htmlspecialchars((string) $r['request_type'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="appt-status612"><?= htmlspecialchars($statusAr[(string) $r['status']] ?? (string) $r['status'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($r['description'])): ?>
                    <p style="grid-column: 1 / -1; margin: 0; font-size: 0.9rem;"><?= htmlspecialchars((string) $r['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($r['volunteer_id'])): ?>
                    <span style="grid-column: 1 / -1; font-size: 0.9rem; color: var(--color-text-light);">
                        المتطوع: <?= htmlspecialchars((string) ($r['volunteer_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
                <?php
                $canMarkHelpDone = in_array((string) ($r['status'] ?? ''), ['accepted', 'in_progress'], true)
                    && !empty($r['volunteer_id']);
                if ($canMarkHelpDone): ?>
                    <div style="grid-column: 1 / -1;">
                        <button type="button" class="btn btn-secondary btn-sm612 help-mark-complete-612" data-help-id="<?= (int) $r['id'] ?>">تعليم الطلب كمكتمل</button>
                    </div>
                <?php endif; ?>
                <?php
                $lat = $r['location_lat'] ?? null;
                $lng = $r['location_lng'] ?? null;
                if ($lat !== null && $lng !== null && $lat !== '' && $lng !== ''):
                    $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode((string) $lat . ',' . (string) $lng);
                ?>
                    <p style="grid-column: 1 / -1; margin: 0;"><a href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="service-link">عرض موقع الطلب على الخريطة</a></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?></div>
    <?php endif; ?>
    <p style="margin-top: 2rem;"><a href="dashboard612.php" class="service-link">← لوحة التحكم</a></p>
</div>

<?php require __DIR__ . '/includes/footer612.php'; ?>
<?php require __DIR__ . '/includes/legal_modals612.php'; ?>
<script src="main612.js"></script>
<script src="modal612.js"></script>
<script>
(function () {
  document.querySelectorAll('.help-mark-complete-612').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      if (!confirm('تأكيد إكمال طلب المساعدة؟ سيُحتسب للمتطوع كمهمة موثّقة.')) return;
      var id = btn.getAttribute('data-help-id');
      var fd = new FormData();
      fd.append('help_id', id);
      fd.append('status', 'completed');
      try {
        var res = await fetch('api_help_status612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        var data = await res.json();
        if (data.ok) window.location.reload();
        else alert(data.error || 'تعذر الحفظ');
      } catch (e) { alert('تعذر الاتصال'); }
    });
  });
})();
</script>
</body>
</html>
