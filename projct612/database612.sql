-- =============================================================================
-- الملف الوحيد لقاعدة بيانات مشروع «رفيقُ الجوار» (تثبيت كامل + ترقية قديمة)
-- MySQL 5.7+ / 8+ — الترميز: utf8mb4
-- =============================================================================
-- التثبيت الجديد: نفّذ الملف من البداية للنهاية (قسم INSERT اختياري).
-- قاعدة قديمة: انظر «ترقية اختيارية» في **نهاية** الملف (أسطر ALTER معلّقة).
-- =============================================================================
CREATE DATABASE IF NOT EXISTS rafiq_al_jewar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE rafiq_al_jewar;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('senior', 'volunteer') NOT NULL,
    rank ENUM('refiq_ahd', 'haris_wudd', 'safir_hikma') DEFAULT 'refiq_ahd',
    tasks_count INT NOT NULL DEFAULT 0,
    rating_sum DECIMAL(8,1) NOT NULL DEFAULT 0,
    hikma_test_passed TINYINT(1) NOT NULL DEFAULT 0,
    rank_congrat_pending VARCHAR(32) NULL DEFAULT NULL,
    rating DECIMAL(2,1) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_phone (phone),
    UNIQUE KEY uk_users_email (email),
    INDEX idx_users_type (user_type),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

-- جدول جلسات المحادثة (اختياري — للتوسع لاحقاً)
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    senior_id INT NOT NULL,
    volunteer_id INT NOT NULL,
    scheduled_time DATETIME NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (senior_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- رسائل الدردشة (أنيس الروح)
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_sender (sender_id),
    INDEX idx_chat_receiver (receiver_id),
    INDEX idx_chat_ts (ts)
) ENGINE=InnoDB;

-- المواعيد (زيادة الود)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    senior_id INT NOT NULL,
    volunteer_id INT DEFAULT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (senior_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_appt_senior (senior_id),
    INDEX idx_appt_volunteer (volunteer_id),
    INDEX idx_appt_status (status),
    INDEX idx_appt_date (appointment_date)
) ENGINE=InnoDB;

-- تنبيهات طارئة / سجل سريع
CREATE TABLE IF NOT EXISTS emergency_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alert_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'responded', 'resolved', 'active') DEFAULT 'pending',
    response_time TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_alert_user (user_id),
    INDEX idx_alert_status (status)
) ENGINE=InnoDB;

-- طلبات المساعدة الفورية (تفاصيل كاملة)
CREATE TABLE IF NOT EXISTS help_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    volunteer_id INT NULL DEFAULT NULL,
    request_type VARCHAR(100) NOT NULL,
    description TEXT,
    location_lat DECIMAL(10, 8) DEFAULT NULL,
    location_lng DECIMAL(11, 8) DEFAULT NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_help_user (user_id),
    INDEX idx_help_volunteer (volunteer_id),
    INDEX idx_help_status (status),
    INDEX idx_help_created (created_at)
) ENGINE=InnoDB;

-- التقييمات (يمكن ربط تقييم بموعد: appointment_id لمنع الازدواج وتمكين تقييمات متعددة لنفس المستفيد/المتطوع)
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewed_id INT NOT NULL,
    session_id INT NULL,
    appointment_id INT NULL,
    rating_value INT CHECK (rating_value BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    UNIQUE KEY uk_rating_per_appointment (reviewer_id, appointment_id)
) ENGINE=InnoDB;

-- بيانات تجريبية (كلمة المرور لجميع الحسابات أدناه: demo12345)
INSERT INTO users (full_name, phone, email, password_hash, user_type, rating, rank) VALUES
('أحمد المستفيد', '0500000001', 'senior1@test.local', '$2y$10$l3xazMyankA/kDSP0UGeiOd5711JX/5YyYsRism.ObEDke.ixSedm', 'senior', 0, 'refiq_ahd'),
('فاطمة المتطوعة', '0500000002', 'volunteer1@test.local', '$2y$10$l3xazMyankA/kDSP0UGeiOd5711JX/5YyYsRism.ObEDke.ixSedm', 'volunteer', 4.5, 'haris_wudd'),
('خالد المتطوع', '0500000003', 'volunteer2@test.local', '$2y$10$l3xazMyankA/kDSP0UGeiOd5711JX/5YyYsRism.ObEDke.ixSedm', 'volunteer', 4.0, 'refiq_ahd')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- =============================================================================
-- ترقية اختيارية — قاعدة قديمة جاء جدول users فيها بلا أعمدة: tasks_count،
-- rating_sum، hikma_test_passed، rank_congrat_pending
-- =============================================================================
-- لا تُنفَّذ تلقائياً مع التثبيت الجديد (الجدول أعلاه يتضمّن الأعمدة).
-- لترقية يدوية: نفّذ سطراً واحداً في كل مرة، أو أزل -- ثم نفّذ.
-- عند الخطأ Duplicate column name فتجاهل — العمود موجود.
-- =============================================================================
-- ALTER TABLE users ADD COLUMN tasks_count INT NOT NULL DEFAULT 0;
-- ALTER TABLE users ADD COLUMN rating_sum DECIMAL(8,1) NOT NULL DEFAULT 0;
-- ALTER TABLE users ADD COLUMN hikma_test_passed TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE users ADD COLUMN rank_congrat_pending VARCHAR(32) NULL DEFAULT NULL;
--
-- ترقية help_requests + ratings (لقاعدة قديمة بلا volunteer_id / appointment_id):
-- انسخ الأسطر أدناه (بدون --) في phpMyAdmin → SQL، سطراً سطراً إن لزم، وتجاهل Duplicate…
-- ALTER TABLE help_requests ADD COLUMN volunteer_id INT NULL DEFAULT NULL AFTER user_id;
-- ALTER TABLE help_requests ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL;
-- ALTER TABLE help_requests ADD INDEX idx_help_volunteer (volunteer_id);
-- ALTER TABLE help_requests ADD CONSTRAINT fk_help_volunteer FOREIGN KEY (volunteer_id) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE ratings ADD COLUMN appointment_id INT NULL DEFAULT NULL AFTER session_id;
-- ALTER TABLE ratings ADD CONSTRAINT fk_ratings_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL;
-- ALTER TABLE ratings ADD UNIQUE KEY uk_rating_per_appointment (reviewer_id, appointment_id);
