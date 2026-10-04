<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_require_login();

require_once __DIR__ . '/includes/volunteer_rank612.php';

$pdo = rafiq612_pdo();
$selCols = implode(', ', rafiq612_users_select_columns_for_row($pdo));
$st = $pdo->prepare("SELECT {$selCols} FROM users WHERE id = ? AND is_active = 1");
$st->execute([rafiq612_current_user_id()]);
$user = $st->fetch();
if (!$user) {
    rafiq612_logout();
    header('Location: login612.php', true, 302);
    exit;
}
$user = rafiq612_user_row_fill_defaults($pdo, $user);
$user['tasks_count'] = (int) ($user['tasks_count'] ?? 0);
$user['rating_sum'] = (float) ($user['rating_sum'] ?? 0);
$user['hikma_test_passed'] = (int) ($user['hikma_test_passed'] ?? 0);

if ((string) $user['user_type'] === 'volunteer') {
    $user['tasks_count'] = rafiq612_rebuild_volunteer_tasks_count($pdo, (int) $user['id']);
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
$nameEsc = htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8');
$isSenior = $user['user_type'] === 'senior';

$volRankPromo = null;
if (! $isSenior) {
    $volRankPromo = $user['rank_congrat_pending'] ?? null;
    if (! empty($volRankPromo) && rafiq612_users_has_column($pdo, 'rank_congrat_pending')) {
        $cl = $pdo->prepare('UPDATE users SET rank_congrat_pending = NULL WHERE id = ?');
        $cl->execute([(int) $user['id']]);
    }
    $vStats = [
        'id' => (int) $user['id'],
        'rank' => (string) ($user['u_rank'] ?? 'refiq_ahd'),
        'tasks_count' => (int) ($user['tasks_count'] ?? 0),
        'rating' => (float) ($user['rating'] ?? 0),
        'hikma_test_passed' => (int) ($user['hikma_test_passed'] ?? 0),
    ];
    $rankMeta = rafiq612_rank_meta($vStats['rank']);
    $progBar = rafiq612_volunteer_progress_bar($pdo, $vStats);
    $rankMotivation = rafiq612_volunteer_progress_message($pdo, $vStats);
    $avgForHikma = rafiq612_avg_rating_from_reviews($pdo, (int) $user['id']) ?? (float) $user['rating'];
    $showHikmaLink = $vStats['rank'] !== 'safir_hikma'
        && $vStats['tasks_count'] >= 20
        && $avgForHikma >= 4.5
        && ! $vStats['hikma_test_passed'];
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - رفيقُ الجوار</title>
    <link rel="stylesheet" href="colors612.css">
    <link rel="stylesheet" href="main612.css">
    <link rel="stylesheet" href="responsive612.css">
    <link rel="stylesheet" href="modal612.css">
    <link rel="stylesheet" href="dashboard612.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="nav-brand"><a href="dashboard612.php" class="logo"><span class="logo-icon"></span><span class="logo-icon gradient">♾️</span><span class="logo-text">رفيقُ الجوار</span></a></div>
        <div class="nav-menu">
            <ul class="nav-links">
                <li><a href="index612.html">الرئيسية</a></li>
                <li><a href="#ranks">نظام مراتب العطاء</a></li>
                <li><a href="#communication-bridges" class="active">جسور التواصل</a></li>
            </ul>
            <div class="nav-actions"><a href="logout612.php" class="btn btn-outline">تسجيل خروج</a></div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<header class="hero-section" id="heroSection">
    <div class="hero-background" id="heroBackground"></div>
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">مرحباً <?= $nameEsc ?> — لوحة رفيقِ الجوار</h1>
            <p class="hero-subtitle">
                نستمر معاً على جسر الأنس؛ هذه مساحتك للتواصل والمرافقة والطمأنينة.
            </p>
        </div>
    </div>
</header>

<section id="communication-bridges" class="dashboard-bridges">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-bridge"></i> جسور التواصل</h2>
            <p class="section-subtitle">خدمات مخصصة لك</p>
        </div>
        <div class="bridges-grid">
            <?php if ($isSenior): ?>
            <div class="bridge-card emergency-alert">
                <div class="bridge-icon"><i class="fas fa-bell"></i></div>
                <h3>تنبيه فوري</h3>
                <p>طلب مساعدة سريع مع تحديد الموقع</p>
                <button type="button" id="emergencyBtn" class="btn btn-danger emergency-btn">
                    <i class="fas fa-exclamation-triangle"></i> طلب مساعدة فورية
                </button>
                <a href="help_history612.php" class="service-link" style="display:block;margin-top:12px;">سجل طلباتي السابقة</a>
                <div id="alertMessage" style="margin-top: 12px; font-size: 0.9rem; color: var(--color-danger); display: none;"></div>
            </div>
            <div class="bridge-card">
                <div class="bridge-icon"><i class="fas fa-comments"></i></div>
                <h3>أنيس الروح</h3>
                <p>محادثة آمنة مع متطوع تختاره أنت — قائمة ومحادثات في صفحة واحدة</p>
                <a href="anis_al_rooh612.php" class="service-link">دخول قسم أنيس الروح ←</a>
                <a href="volunteers612.php" class="service-link" style="display:block;margin-top:8px;">عرض المتطوعين فقط</a>
                <a href="messages612.php" class="service-link" style="display:block;margin-top:8px;">محادثاتي (قائمة)</a>
            </div>
            <div class="bridge-card">
                <div class="bridge-icon"><i class="fas fa-calendar-plus"></i></div>
                <h3>زيادة الود</h3>
                <p>قائمة مواعيدك كالحجز — اضغط على أي موعد للتفاصيل</p>
                <a href="ziyada_wud612.php" class="service-link">عرض المواعيد والحجز ←</a>
                <a href="#" class="service-link" data-open-appointment612 style="display:block;margin-top:8px;">حجز سريع من اللوحة</a>
            </div>
            <?php else: ?>
            <div class="bridge-card emergency-alert">
                <div class="bridge-icon"><i class="fas fa-inbox"></i></div>
                <h3>تنبيهات المساعدة</h3>
                <p>طلبات قيد الانتظار من كبار السن</p>
                <div id="volunteerPendingHelp612" class="volunteer-pending-612">جاري التحميل…</div>
            </div>
            <div class="bridge-card">
                <div class="bridge-icon"><i class="fas fa-comments"></i></div>
                <h3>أنيس الروح</h3>
                <p>ردودك تُبنى الأنس — محادثات واختيار مستفيد من صفحة القسم</p>
                <a href="anis_al_rooh612.php" class="service-link">دخول قسم أنيس الروح ←</a>
                <a href="seniors612.php" class="service-link" style="display:block;margin-top:8px;">قائمة المستفيدين</a>
                <a href="messages612.php" class="service-link" style="display:block;margin-top:8px;">محادثاتي (قائمة)</a>
            </div>
            <div class="bridge-card">
                <div class="bridge-icon"><i class="fas fa-calendar-plus"></i></div>
                <h3>زيادة الود</h3>
                <p>مواعيدك مع المستفيدين — قائمة تفاعلية وصفحة لكل موعد</p>
                <a href="ziyada_wud612.php" class="service-link">عرض مواعيد زيادة الود ←</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="appointments-list612" class="ranks-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-calendar-alt"></i> مواعيدي</h2>
            <p class="section-subtitle">القادمة ثم السابقة — حسب تاريخك على المنصة</p>
        </div>
        <h3 class="section-title" style="font-size:1.1rem;margin-bottom:1rem;">المواعيد القادمة</h3>
        <?php if (empty($upcoming)): ?>
            <p style="color: var(--color-text-light); margin-bottom: 2rem;">لا توجد مواعيد قادمة بعد.</p>
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
        <h3 class="section-title" style="font-size:1.1rem;margin:2rem 0 1rem;">المواعيد السابقة</h3>
        <?php if (empty($past)): ?>
            <p style="color: var(--color-text-light);">لا توجد مواعيد منتهية مسجلة.</p>
        <?php else: ?>
            <div class="appt-list612"><?php foreach ($past as $r): ?>
                <a class="appt-row612 appt-past612 appt-row-link612" href="appointment_detail612.php?id=<?= (int) $r['id'] ?>">
                    <strong><?= htmlspecialchars((string) ($r['purpose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars((string) $r['appointment_date'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="appt-status612"><?= htmlspecialchars((string) ($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?></div>
        <?php endif; ?>
        <?php if ($isSenior): ?>
        <p style="margin-top:2rem;"><a href="#" class="btn btn-secondary" data-open-appointment612>حجز موعد جديد</a></p>
        <?php endif; ?>
    </div>
</section>

    <section id="ranks" class="ranks-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-medal"></i> نظام مراتب العطاء</h2>
                <p class="section-subtitle">تدرج معنوي لضمان جودة الخدمة</p>
            </div>
            <div class="ranks-goal-block">
                <h3 class="ranks-goal-title">الهدف من النظام</h3>
                <p class="ranks-goal-text">تحفيز المتطوعين على الاستمرار والعطاء، وطمأنة كبار السن بأن المتطوعين ذوو خبرة وتقدير. النظام بسيط، نفسي، وغير معقّد.</p>
            </div>
            <h3 class="ranks-block-title">أولاً: الرتب الثلاث (من الأدنى إلى الأعلى)</h3>
            <div class="ranks-container" role="list">
                <article class="rank-card" role="listitem">
                    <div class="rank-icon beginner" aria-hidden="true"><i class="fas fa-seedling"></i></div>
                    <h3>رفيق العهد</h3>
                    <p class="rank-card-tagline">للمبادر الجديد في رحلة العطاء</p>
                    <ul class="rank-features">
                        <li>تدريب مكثف</li>
                        <li>إشراف مستمر</li>
                        <li>مهام محدودة</li>
                    </ul>
                </article>
                <article class="rank-card" role="listitem">
                    <div class="rank-icon intermediate" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
                    <h3>حارس الود</h3>
                    <p class="rank-card-tagline">للمتطوع الموثوق ذو التقييمات العالية</p>
                    <ul class="rank-features">
                        <li>صلاحيات أوسع</li>
                        <li>تدريب متقدم</li>
                        <li>مهام متنوعة</li>
                    </ul>
                </article>
                <article class="rank-card" role="listitem">
                    <div class="rank-icon expert" aria-hidden="true"><i class="fas fa-crown"></i></div>
                    <h3>سفير الحكمة</h3>
                    <p class="rank-card-tagline">للمختصين والقادة الاجتماعيين</p>
                    <ul class="rank-features">
                        <li>مستشار رسمي</li>
                        <li>تدريب المتطوعين</li>
                        <li>صلاحيات إدارية</li>
                    </ul>
                </article>
            </div>
            <p class="ranks-hint" style="margin-top: 1rem;">شروط الاستحقاق في المنصة: <strong>رفيق العهد</strong> عند التسجيل؛ <strong>حارس الود</strong> بعد 5 مهام موثّقة وتقييم إيجابي (4 نجوم فأعلى)؛ <strong>سفير الحكمة</strong> بعد 20 مهمة ومتوسط تقييم 4.5 واجتياز اختبار بسيط.</p>
            <h3 class="ranks-block-title">ثانياً: الميزات التي تظهر عند كل رتبة</h3>
            <h4 class="ranks-subtitle-2">🔹 رفيق العهد (البداية)</h4>
            <div class="ranks-block-list">
                <ul>
                    <li>شارة شخصية تظهر بجانب اسمه في ملفه الشخصي</li>
                    <li>يستطيع التطوع في المهام البسيطة فقط (محادثة أسبوعية، قراءة)</li>
                    <li>يحصل على رسالة ترحيبية وتوجيهية</li>
                    <li>يظهر له شريط تقدم نحو «حارس الود» (مثلاً: 0 من 5 مهام)</li>
                </ul>
            </div>
            <h4 class="ranks-subtitle-2">🔸 حارس الود (الوسط)</h4>
            <div class="ranks-block-list">
                <ul>
                    <li>شارة مميّزة وملوّنة</li>
                    <li>صلاحية التطوع في جميع أنواع الخدمات (محادثات، مرافقات، طلبات مساعدة)</li>
                    <li>يمكنه تقييم المستفيدين (بشكل إيجابي فقط)</li>
                    <li>يحصل على إشعار تحفيزي عند المهام: «أنت أقرب إلى مرتبة سفير الحكمة»</li>
                </ul>
            </div>
            <h4 class="ranks-subtitle-2">🔹 سفير الحكمة (القمة)</h4>
            <div class="ranks-block-list">
                <ul>
                    <li>شارة خاصة بتأثير ذهبي</li>
                    <li>يُظهر اسمه في قائمة «الموجّهين» للمتطوعين الجدد</li>
                    <li>يمكنه حضور دورات تدريبية متقدمة داخل المنصة</li>
                    <li>يحصل على شهادة تقدير رقمية قابلة للتحميل</li>
                </ul>
            </div>
            <h3 class="ranks-block-title">ثالثاً: تقدّمك هنا (لوحة التحكم)</h3>
            <p class="ranks-hint">للمتطوع: تظهر أدناه بطاقتك الحالية (الشارة، شريط التقدّم، رسالة تحفيزية) وتهنئة عند الترقية.</p>
            <h3 class="ranks-block-title">رابعاً: احتساب الترقية</h3>
            <div class="ranks-block-list">
                <ul>
                    <li>كل مهمة مكتملة يقرّها المستفيد = +1 (موعد موثّق في «زيادة الود»)</li>
                    <li>كل تقييم 5 نجوم = +0.5 نقطة إضافية تُسجّل في ملف تقدّمك</li>
                    <li>الترقية تلقائية عند استيفاء الشروط</li>
                </ul>
            </div>
            <?php if (! $isSenior): ?>
            <div class="volunteer-rank-dashboard-card">
                <div class="vr-header">
                    <div class="vr-badge">
                        <span class="vr-icon" aria-hidden="true"><?= htmlspecialchars($rankMeta['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars($rankMeta['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span class="vr-meta">المهام الموثّقة: <?= (int) $vStats['tasks_count'] ?> &nbsp;|&nbsp; نقاط 5 نجوم: <?= htmlspecialchars((string) ($user['rating_sum'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="vr-meta"><?= htmlspecialchars($rankMotivation, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="rank-bar-612 <?= htmlspecialchars($progBar['barClass'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="rank-bar-fill" style="width: <?= (int) $progBar['percent'] ?>%;"></div>
                </div>
                <p class="vr-meta" style="font-size:0.85rem;"><?= htmlspecialchars($progBar['label'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (! empty($showHikmaLink)): ?>
                <a class="btn btn-secondary vr-hikma" href="safir_test612.php">اجتياز اختبار سفير الحكمة (بسيط)</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php $rafiq612_footer_mode = 'dashboard'; require __DIR__ . '/includes/footer612.php'; ?>

<div id="modalEmergency612" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>طلب مساعدة فورية</h2>
            <span class="close-modal" data-close-modal612="modalEmergency612">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formEmergency612">
                <div class="form-group">
                    <label>نوع المساعدة</label>
                    <select name="request_type" required>
                        <option value="">— اختر —</option>
                        <option>توصيل دواء</option>
                        <option>مساعدة في المنزل</option>
                        <option>توصيل طعام</option>
                        <option>توصيل لمستشفى</option>
                        <option>مشورة عاجلة</option>
                        <option>أخرى</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>تفاصيل إضافية</label>
                    <textarea name="description" rows="3" maxlength="2000" placeholder="صف احتياجك باختصار"></textarea>
                </div>
                <input type="hidden" name="location_lat" id="helpLat612" value="">
                <input type="hidden" name="location_lng" id="helpLng612" value="">
                <button type="button" class="btn btn-secondary" id="btnShareLocation612"><i class="fas fa-location-arrow"></i> مشاركة موقعي على الخريطة</button>
                <div id="mapHelp612" class="map-help-612"></div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem;">إرسال الطلب</button>
            </form>
        </div>
    </div>
</div>

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

<?php require __DIR__ . '/includes/legal_modals612.php'; ?>

<?php
if (! $isSenior && $volRankPromo) {
    $pMeta = rafiq612_rank_meta((string) $volRankPromo);
    ?>
<div id="rankCongratModal612" class="modal" style="display:flex;">
    <div class="modal-content" style="max-width: 420px; text-align: center;">
        <div class="modal-header" style="justify-content: center;">
            <h2>تهانينا</h2>
        </div>
        <div class="modal-body">
            <p style="font-size:1.2rem; margin-bottom:0.5rem;">لقد رُقِّيت إلى</p>
            <p style="font-size:1.5rem; font-weight:700; color: var(--color-primary);"><?= htmlspecialchars($pMeta['icon'] . ' ' . $pMeta['name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="ranks-hint" style="margin-top:1rem;">نُكمل معاً بإذن الله، وعطاءك نورٌ لمن حولك.</p>
            <button type="button" class="btn btn-primary" style="margin-top:1rem;" id="closeRankCongrat612">متابعة</button>
        </div>
    </div>
</div>
    <?php
}
?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="main612.js"></script>
<script src="modal612.js"></script>
<script src="map612.js"></script>
<script src="dashboard612.js"></script>
<?php if (! $isSenior && $volRankPromo): ?>
<script>
document.getElementById('closeRankCongrat612')?.addEventListener('click', function () {
  var m = document.getElementById('rankCongratModal612');
  if (m) { m.style.display = 'none'; document.body.style.overflow = 'auto'; }
});
document.getElementById('rankCongratModal612')?.addEventListener('click', function (e) {
  if (e.target === this) { this.style.display = 'none'; document.body.style.overflow = 'auto'; }
});
</script>
<?php endif; ?>
</body>
</html>
