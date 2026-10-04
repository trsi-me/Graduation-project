<?php
/**
 * تذييل الموقع الموحّد — يُضمَّن في جميع صفحات الواجهة.
 *
 * قبل الاستدعاء يمكن ضبط $rafiq612_footer_mode:
 * - 'public'  : صفحات عامة (رابط مراتب العطاء → index612.html#ranks)
 * - 'dashboard' : لوحة التحكم (مراتب #ر + جسور التواصل)
 * - 'app'     : صفحات المستخدم المسجّل (افتراضي: مراتب + أنيس الروح)
 */
declare(strict_types=1);

$rafiq612_footer_mode = $rafiq612_footer_mode ?? 'app';

$ranksHref = match ($rafiq612_footer_mode) {
    'public' => 'index612.html#ranks',
    'dashboard' => '#ranks',
    default => 'dashboard612.php#ranks',
};

$homeHref = 'index612.html';
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="logo"><span class="logo-icon"><span class="infinity-symbol">♾️</span></span><span class="logo-text">رفيقُ الجوار</span></a>
                <p class="footer-description">جسر إنساني تقني يربط بين كبار السن والمتطوعين المتخصصين بأسلوب السيادة والأمان.</p>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">روابط سريعة</h3>
                <ul class="footer-links">
                    <li><a href="<?= htmlspecialchars($ranksHref, ENT_QUOTES, 'UTF-8') ?>">نظام مراتب العطاء</a></li>
                    <?php if ($rafiq612_footer_mode === 'dashboard'): ?>
                        <li><a href="dashboard612.php" class="active">جسور التواصل</a></li>
                    <?php elseif ($rafiq612_footer_mode === 'app'): ?>
                        <li><a href="anis_al_rooh612.php">أنيس الروح</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">الدعم والمساعدة</h3>
                <ul class="footer-links">
                    <li><a href="#" id="openFaqBtn">الأسئلة الشائعة</a></li>
                    <li><a href="#" id="openSupportBtn">الدعم الفني</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">تواصل معنا</h3>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link" aria-label="facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link" aria-label="instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="youtube"><i class="fab fa-youtube"></i></a>
                </div>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> 920000000</p>
                    <p><i class="fas fa-envelope"></i> contact@rafiqaljewar.com</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="copyright">جميع الحقوق محفوظة © 2026 - منصة رفيقُ الجوار | <span class="project-credits">مشروع تخرج 2026</span></div>
            <div class="footer-legal">
                <a href="#" id="privacyLink">سياسة الخصوصية</a>
                <a href="#" id="termsLink">شروط الاستخدام</a>
                <a href="#" id="disclaimerLink">إخلاء المسؤولية</a>
            </div>
        </div>
    </div>
</footer>
