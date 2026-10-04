<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth612.php';
if (rafiq612_is_logged_in()) {
    header('Location: dashboard612.php', true, 302);
    exit;
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - رفيقُ الجوار</title>
    <link rel="stylesheet" href="colors612.css">
    <link rel="stylesheet" href="main612.css">
    <link rel="stylesheet" href="responsive612.css">
    <link rel="stylesheet" href="modal612.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index612.html" class="logo">
                    <span class="logo-icon"><span class="infinity-symbol">♾️</span></span>
                    <span class="logo-text">رفيقُ الجوار</span>
                </a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index612.html" class="active">الرئيسية</a></li>
                    <li><a href="index612.html#ranks">نظام مراتب العطاء</a></li>
                </ul>
                <div class="nav-actions">
                    <a href="login612.php?type=request" class="btn btn-primary">التمسْ رفيقَ جوار</a>
                    <a href="login612.php?type=volunteer" class="btn btn-secondary">كنْ أنت رفيقَ الجوار</a>
                </div>
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </nav>
<div class="login-page">
    <div class="container">
        <div class="login-container">
            <h2 class="login-title">مرحباً بك في رفيقُ الجوار</h2>
            <p class="login-subtitle">سجل دخولك لتتمكن من استخدام خدماتنا</p>
            <div class="login-tabs"><button type="button" class="tab-btn active" data-tab="login">تسجيل الدخول</button><button type="button" class="tab-btn" data-tab="register">إنشاء حساب جديد</button></div>
            
            <form id="loginForm" class="login-form active" autocomplete="on">
                <div class="form-group"><label><i class="fas fa-envelope"></i> البريد الإلكتروني</label><input type="email" id="loginEmail" required placeholder="example@domain.com"></div>
                <div class="form-group"><label><i class="fas fa-lock"></i> كلمة المرور</label><input type="password" id="loginPassword" required placeholder="********"></div>
                <div id="loginError" style="display:none;color:var(--color-danger);margin-bottom:1rem;"></div>
                <button type="submit" class="btn btn-primary btn-block">تسجيل الدخول</button>
                <div class="form-footer"><a href="#">نسيت كلمة المرور؟</a></div>
            </form>
            
            <form id="registerForm" class="login-form" autocomplete="on">
                <input type="hidden" id="regUserType" name="user_type" value="senior">
                <div class="form-group"><label><i class="fas fa-user"></i> الاسم الكامل</label><input type="text" id="regName" required></div>
                <div class="form-group"><label><i class="fas fa-phone"></i> رقم الهاتف</label><input type="tel" id="regPhone" required></div>
                <div class="form-group"><label><i class="fas fa-envelope"></i> البريد الإلكتروني</label><input type="email" id="regEmail" required></div>
                <div class="form-group"><label><i class="fas fa-lock"></i> كلمة المرور</label><input type="password" id="regPassword" required></div>
                <div class="form-group"><label><i class="fas fa-check-circle"></i> تأكيد كلمة المرور</label><input type="password" id="regConfirmPassword" required></div>
                <div id="registerError" style="display:none;color:var(--color-danger);margin-bottom:1rem;"></div>
                <button type="submit" class="btn btn-primary btn-block">إنشاء حساب</button>
                <div class="form-footer"><span>بالتسجيل أنت توافق على <a href="#" id="termsLinkReg">الشروط</a> و <a href="#" id="privacyLinkReg">الخصوصية</a></span></div>
            </form>
        </div>
    </div>
</div>

    <?php $rafiq612_footer_mode = 'public'; require __DIR__ . '/includes/footer612.php'; ?>
    <?php require __DIR__ . '/includes/legal_modals612.php'; ?>
    <script src="main612.js"></script>
    <script src="modal612.js"></script>
    <script src="login612.js"></script>
</body>
</html>
