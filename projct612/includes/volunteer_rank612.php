<?php
/**
 * رتب المتطوعين، النقاط، وإعادة احتساب الترقية
 */
declare(strict_types=1);

/**
 * @return array<string, true> أسماء أعمدة جدول users (نسخة محفوظة لكل طلب)
 */
function rafiq612_users_table_columns(PDO $pdo): array
{
    static $cols = null;
    if ($cols !== null) {
        return $cols;
    }
    $cols = [];
    try {
        $st = $pdo->query('SHOW COLUMNS FROM users');
        if ($st) {
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $cols[(string) $r['Field']] = true;
            }
        }
    } catch (Throwable $e) {
        // تصرّف آمن: لا أعمدة اختيارية
    }

    return $cols;
}

function rafiq612_users_has_column(PDO $pdo, string $name): bool
{
    return isset(rafiq612_users_table_columns($pdo)[$name]);
}

/**
 * أعمدة SELECT لصف المستخدم (لوحات/ملف) مع دعم قواعد قديمة بلا tasks_count ونحوها
 * @return list<string>
 */
function rafiq612_users_select_columns_for_row(PDO $pdo): array
{
    $c = rafiq612_users_table_columns($pdo);
    $out = [
        'id', 'full_name', 'email', 'user_type', 'address', 'phone', '`rank` AS u_rank', 'rating',
    ];
    foreach (['tasks_count', 'rating_sum', 'hikma_test_passed', 'rank_congrat_pending'] as $opt) {
        if (isset($c[$opt])) {
            $out[] = $opt;
        }
    }

    return $out;
}

/**
 * يملأ القيم الافتراضية لصف مستخدم بعد fetch إن وُجدت أعمدة مفقودة
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function rafiq612_user_row_fill_defaults(PDO $pdo, array $row): array
{
    $c = rafiq612_users_table_columns($pdo);
    if (! isset($c['tasks_count'])) {
        $row['tasks_count'] = 0;
    }
    if (! isset($c['rating_sum'])) {
        $row['rating_sum'] = 0.0;
    }
    if (! isset($c['hikma_test_passed'])) {
        $row['hikma_test_passed'] = 0;
    }
    if (! isset($c['rank_congrat_pending'])) {
        $row['rank_congrat_pending'] = null;
    }

    return $row;
}

function rafiq612_rank_level(string $r): int
{
    return match ($r) {
        'refiq_ahd' => 1,
        'haris_wudd' => 2,
        'safir_hikma' => 3,
        default => 1,
    };
}

/**
 * @return array{name:string,icon:string,fa:string}
 */
function rafiq612_rank_meta(string $rank): array
{
    $m = [
        'refiq_ahd' => ['name' => 'رفيق العهد', 'icon' => '🌱', 'fa' => 'fa-seedling'],
        'haris_wudd' => ['name' => 'حارس الود', 'icon' => '🛡️', 'fa' => 'fa-shield-alt'],
        'safir_hikma' => ['name' => 'سفير الحكمة', 'icon' => '👑', 'fa' => 'fa-crown'],
    ];
    return $m[$rank] ?? $m['refiq_ahd'];
}

/**
 * هل جدول help_requests يحتوي عمود volunteer_id؟ (قواعد قديمة قد تفتقد الترقية)
 */
function rafiq612_help_requests_has_volunteer_id(PDO $pdo): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM help_requests LIKE 'volunteer_id'");
        $cached = (bool) $st->fetch();
    } catch (Throwable $e) {
        $cached = false;
    }

    return $cached;
}

/**
 * إعادة احتساب tasks_count فعلياً من المصدر: مواعيد مكتملة + طلبات مساعدة مكتملة
 */
function rafiq612_rebuild_volunteer_tasks_count(PDO $pdo, int $volunteerId): int
{
    $a = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE volunteer_id = ? AND status = 'completed'");
    $a->execute([$volunteerId]);
    $nA = (int) $a->fetchColumn();
    $nH = 0;
    if (rafiq612_help_requests_has_volunteer_id($pdo)) {
        $h = $pdo->prepare(
            "SELECT COUNT(*) FROM help_requests WHERE volunteer_id = ? AND status = 'completed'"
        );
        $h->execute([$volunteerId]);
        $nH = (int) $h->fetchColumn();
    }
    $total = $nA + $nH;
    if (rafiq612_users_has_column($pdo, 'tasks_count')) {
        $pdo->prepare('UPDATE users SET tasks_count = ? WHERE id = ? AND user_type = ?')
            ->execute([$total, $volunteerId, 'volunteer']);
    }
    return $total;
}

/**
 * نقاط مُجتمَعَة: مهام مكتملة + مجموع (0.5 لكل 5 نجوم في rating_sum)
 */
function rafiq612_volunteer_points_total(array $u): float
{
    $t = (int) ($u['tasks_count'] ?? 0);
    $rs = (float) ($u['rating_sum'] ?? 0);
    return (float) ($t + $rs);
}

function rafiq612_has_positive_rating(PDO $pdo, int $volunteerId): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE reviewed_id = ? AND rating_value >= 4');
    $st->execute([$volunteerId]);
    return (int) $st->fetchColumn() > 0;
}

function rafiq612_avg_rating_from_reviews(PDO $pdo, int $volunteerId): ?float
{
    $st = $pdo->prepare('SELECT AVG(rating_value) FROM ratings WHERE reviewed_id = ?');
    $st->execute([$volunteerId]);
    $a = $st->fetchColumn();
    if ($a === null || $a === false) {
        return null;
    }
    return (float) $a;
}

function rafiq612_sync_volunteer_rating_stats(PDO $pdo, int $volunteerId): void
{
    if (! rafiq612_users_has_column($pdo, 'rating_sum')) {
        if (rafiq612_users_has_column($pdo, 'rating')) {
            $avg = rafiq612_avg_rating_from_reviews($pdo, $volunteerId);
            if ($avg !== null) {
                $pdo->prepare('UPDATE users SET rating = ? WHERE id = ? AND user_type = ?')
                    ->execute([round($avg, 1), $volunteerId, 'volunteer']);
            }
        }

        return;
    }
    $c5 = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE reviewed_id = ? AND rating_value = 5');
    $c5->execute([$volunteerId]);
    $n5 = (int) $c5->fetchColumn();
    $ratingSum = round(0.5 * $n5, 1);

    $avg = rafiq612_avg_rating_from_reviews($pdo, $volunteerId);
    if ($avg === null) {
        $st = $pdo->prepare('UPDATE users SET rating_sum = ? WHERE id = ? AND user_type = ?');
        $st->execute([$ratingSum, $volunteerId, 'volunteer']);
    } else {
        if (rafiq612_users_has_column($pdo, 'rating')) {
            $st = $pdo->prepare('UPDATE users SET rating_sum = ?, rating = ? WHERE id = ? AND user_type = ?');
            $st->execute([$ratingSum, round($avg, 1), $volunteerId, 'volunteer']);
        } else {
            $st = $pdo->prepare('UPDATE users SET rating_sum = ? WHERE id = ? AND user_type = ?');
            $st->execute([$ratingSum, $volunteerId, 'volunteer']);
        }
    }
}

/**
 * يحدّد الرتبة المستحقة حسب المهام والتقييمات والاختبار
 */
function rafiq612_compute_target_rank(
    int $tasksCount,
    bool $hikmaTestPassed,
    ?float $avgFromReviews,
    float $userRating,
    bool $hasPositive
): string {
    $avg = $avgFromReviews !== null ? $avgFromReviews : $userRating;

    if ($tasksCount >= 20 && $hikmaTestPassed && $avg >= 4.5) {
        return 'safir_hikma';
    }
    if ($tasksCount >= 5 && $hasPositive) {
        return 'haris_wudd';
    }
    return 'refiq_ahd';
}

/**
 * يعيد اسم الرتبة الجديدة إن رُقِّي، أو null إن لم يتغيّر
 */
function rafiq612_recompute_volunteer_rank(PDO $pdo, int $volunteerId): ?string
{
    $hasTc = rafiq612_users_has_column($pdo, 'tasks_count');
    $hasHikma = rafiq612_users_has_column($pdo, 'hikma_test_passed');
    $hasRankPending = rafiq612_users_has_column($pdo, 'rank_congrat_pending');
    $sel = 'id, user_type, `rank` AS r, rating';
    if ($hasTc) {
        $sel .= ', tasks_count';
    }
    if ($hasHikma) {
        $sel .= ', hikma_test_passed';
    }
    $q = $pdo->prepare("SELECT {$sel} FROM users WHERE id = ?");
    $q->execute([$volunteerId]);
    $row = $q->fetch();
    if (!$row || (string) $row['user_type'] !== 'volunteer') {
        return null;
    }

    $old = (string) $row['r'];
    $tasks = $hasTc ? (int) $row['tasks_count'] : rafiq612_rebuild_volunteer_tasks_count($pdo, $volunteerId);
    $hikma = $hasHikma ? (int) $row['hikma_test_passed'] === 1 : false;
    $userRating = (float) $row['rating'];
    $avgR = rafiq612_avg_rating_from_reviews($pdo, $volunteerId);
    $pos = rafiq612_has_positive_rating($pdo, $volunteerId) || $userRating >= 4.0;

    $new = rafiq612_compute_target_rank($tasks, $hikma, $avgR, $userRating, $pos);
    if (rafiq612_rank_level($new) > rafiq612_rank_level($old)) {
        if ($hasRankPending) {
            $pdo->prepare('UPDATE users SET `rank` = ?, rank_congrat_pending = ? WHERE id = ?')
                ->execute([$new, $new, $volunteerId]);
        } else {
            $pdo->prepare('UPDATE users SET `rank` = ? WHERE id = ?')
                ->execute([$new, $volunteerId]);
        }
        return $new;
    }
    return null;
}

/**
 * رسالة تشجيعية للوحة التحكم
 * @param array{id:int,rank:string,tasks_count:int,rating:float|int,hikma_test_passed?:int} $u
 */
function rafiq612_volunteer_progress_message(PDO $pdo, array $u): string
{
    $id = (int) $u['id'];
    $r = (string) $u['rank'];
    $t = (int) $u['tasks_count'];
    $avgR = rafiq612_avg_rating_from_reviews($pdo, $id);
    $ur = (float) $u['rating'];
    $avg = $avgR !== null ? $avgR : $ur;
    $pos = rafiq612_has_positive_rating($pdo, $id) || $ur >= 4.0;

    if ($r === 'refiq_ahd') {
        if ($t < 5) {
            $left = 5 - $t;
            return "أنت على بُعد {$left} " . ($left === 1 ? 'مهمة' : 'مهام') . " موثّقة للوصول إلى حارس الود";
        }
        if (! $pos) {
            return 'أكملت 5 مهام — أضف تقييماً إيجابياً (4 نجوم فأعلى) من المستفيد لترقية حارس الود';
        }
        return 'أنت مؤهّل لمرتبة أعلى — سيتم تحديث الرتبة تلقائياً عند استيفاء الشروط';
    }
    if ($r === 'haris_wudd') {
        if ($t < 20) {
            $left = 20 - $t;
            return "أنت أقرب إلى سفير الحكمة — باقٍ {$left} مهمة موثّقة" . ($left === 1 ? '' : 'ات');
        }
        if ($avg < 4.5) {
            return 'تدرّب على التميّز — المطلوب تقييم ممتاز (متوسط 4.5) لسفير الحكمة';
        }
        if (empty($u['hikma_test_passed'])) {
            return 'بقي عليك اجتياز الاختبار البسيط لسفير الحكمة (الرابط أسفل هذه البطاقة)';
        }
        return 'واصل — أنت عند باب القمّة';
    }
    return 'شكراً لعطائك — أنت سفير حكمة على المنصة';
}

/**
 * تقدم نحو الرتبة التالية (0–100) للواجهة
 * @return array{label:string, percent:int, barClass:string}
 */
function rafiq612_volunteer_progress_bar(PDO $pdo, array $u): array
{
    $r = (string) $u['rank'];
    $t = (int) $u['tasks_count'];
    $id = (int) $u['id'];
    $pos = rafiq612_has_positive_rating($pdo, $id) || (float) $u['rating'] >= 4.0;

    if ($r === 'refiq_ahd') {
        $pct = (int) min(100, round(100 * $t / 5));
        if ($t >= 5 && ! $pos) {
            $pct = 90;
        }
        return ['label' => "المهام الموثّقة: {$t} من 5", 'percent' => $pct, 'barClass' => 'rank-bar--beginner'];
    }
    if ($r === 'haris_wudd') {
        $pct = (int) min(100, round(100 * $t / 20));
        return ['label' => "المهام: {$t} من 20 نحو سفير الحكمة", 'percent' => $pct, 'barClass' => 'rank-bar--mid'];
    }
    return ['label' => 'أنت وصلت لأعلى مرتبة في النظام', 'percent' => 100, 'barClass' => 'rank-bar--expert'];
}
