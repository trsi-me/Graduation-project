# رفيقُ الجوار

## 1. ما هو المشروع؟

منصة ويب عربية لمشروع تخرج تربط كبار السن (المستفيدون) بالمتطوعين. الكود داخل المجلد `projct612`. الاسم في README السابق: «رفيقُ الجوار». القاعدة `rafiq_al_jewar`.

الوحدات الظاهرة من أسماء الملفات: أنيس الروح (دردشة)، زيادة الود (مواعيد)، طلبات مساعدة، وتقييم، ورتب متطوعين.

## 2. لماذا يوجد هذا المشروع؟

README السابق والجداول يربطان مستفيدًا بمتطوع عبر رسائل ومواعيد ومساعدة فورية وتنبيه، مع رتب: رفيق العهد، حارس الود، سفير الحكمة. هذا ما ينفذه `includes/volunteer_rank612.php` والجداول، لا وصفًا تسويقيًا منفصلًا.

## 3. من يستخدمه؟

`users.user_type` قيمتان فقط: `senior` و `volunteer`.

| النوع | المعنى في الواجهة |
| --- | --- |
| `senior` | مستفيد |
| `volunteer` | متطوع برتبة |

لا قيمة `admin` في تعداد الجدول. حسابات البذرة في `database612.sql`:

| البريد | النوع | الاسم في البذرة |
| --- | --- | --- |
| `senior1@test.local` | senior | أحمد المستفيد |
| `volunteer1@test.local` | volunteer | فاطمة المتطوعة |
| `volunteer2@test.local` | volunteer | خالد المتطوع |

تعليق SQL يقول إن كلمة المرور المشتركة للحسابات الثلاثة موثقة في الملف نفسه (`demo12345`). التجزئة bcrypt مخزنة في العمود ولا تُنسخ هنا.

## 4. ماذا يستطيع النظام أن يفعل؟

| القدرة | الملفات |
| --- | --- |
| صفحة تعريف ثابتة | `index612.html` |
| دخول وتسجيل | `login612.php` `login612.html` `login612.js` `auth_login612.php` `auth_register612.php` |
| خروج | `logout612.php` |
| لوحة | `dashboard612.php` `dashboard612.html` `dashboard612.js` |
| دردشة أنيس الروح | `anis_al_rooh612.php` `chat612.php` `chat612.js` `api_chat612.php` `messages612.php` |
| مواعيد زيادة الود | `ziyada_wud612.php` `api_appointments612.php` `api_appointment_status612.php` `api_claim_appointment612.php` `appointment_detail612.php` |
| مساعدة | `api_help612.php` `api_help_action612.php` `api_help_status612.php` `api_pending_help612.php` `help_history612.php` |
| متطوعون ومستفيدون | `volunteers612.php` `seniors612.php` `api_volunteers612.php` |
| تقييم | `api_post_rating612.php` |
| رتبة واختبار سفير | `includes/volunteer_rank612.php` `safir_test612.php` |
| خريطة في الواجهة | `map612.js` |
| نوافذ قانونية | `includes/legal_modals612.php` |

## 5. كيف يعمل النظام؟

```
المتصفح
  -> HTML/JS أو صفحة PHP
  -> includes/bootstrap612.php (جلسة) و auth612.php
  -> includes/config612.php الدالة rafiq612_pdo()
  -> MySQL rafiq_al_jewar
  -> HTML أو JSON { ok: ... }
```

## 6. أمثلة واقعية

### تسجيل الدخول

1. فتح `login612.php`.
2. `login612.js` يرسل إلى `auth_login612.php`.
3. البحث في `users` والتحقق من `password_hash`.
4. الجلسة تُفتح ثم `dashboard612.php`.

### موعد يكتمل ويرفع الرتبة

1. موعد في `appointments` بحالات `pending` و `confirmed` و `completed` و `cancelled`.
2. `api_appointment_status612.php` عند الإكمال يعيد حساب المهام.
3. `rafiq612_rebuild_volunteer_tasks_count` يعد المواعيد المكتملة وطلبات المساعدة المكتملة.
4. `rafiq612_compute_target_rank` يختار الرتبة.
5. تُحدَّث أعمدة `tasks_count` و `rank`.

### تقييم بعد موعد

1. المستفيد يستدعي `api_post_rating612.php`.
2. يُدرج صف في `ratings` بقيمة من 1 إلى 5.
3. مفتاح فريد `uk_rating_per_appointment` يمنع تكرار تقييم نفس المراجع لنفس الموعد.
4. `rafiq612_sync_volunteer_rating_stats` يحدّث `rating_sum` ثم تُعاد الرتبة.

### طلب مساعدة

1. `api_help612.php` ينشئ `help_requests` بحالة `pending`.
2. الأنواع اللاحقة في الجدول: `accepted` و `in_progress` و `completed` و `cancelled`.
3. الإحداثيات اختيارية: `location_lat` و `location_lng`.
4. المتطوع يقبل عبر ملفات `api_help_action612.php` و `api_claim` للمواعيد.

## 7. رحلة المستخدم

1. `index612.html` أو صفحة الدخول.
2. تسجيل أو دخول.
3. اللوحة `dashboard612.php`.
4. اختيار أنيس الروح أو زيادة الود أو المساعدة.
5. الحفظ في الجدول المقابل.
6. النتيجة في الصفحة أو JSON للسكربت.
7. `logout612.php` ينهي الجلسة.

## 8. الوحدات والأقسام

| الوحدة | الوظيفة | العلاقة |
| --- | --- | --- |
| الحساب | `users` وتسجيل ودخول | أساس كل المفاتيح الأجنبية |
| أنيس الروح | `chat_messages` و `chat_sessions` | بين `senior_id` و `volunteer_id` |
| زيادة الود | `appointments` | مستفيد ومتطوع اختياري |
| المساعدة | `help_requests` | مستفيد ثم متطوع |
| الطوارئ | `emergency_alerts` | مستخدم وحالة |
| التقييم | `ratings` | مراجع ومُراجَع وموعد |
| الرتب | أعمدة في `users` | تُحسب من المهام والتقييم والاختبار |
| الخريطة | `map612.js` | إحداثيات طلب المساعدة حيث تُرسل |

## 9. الشركات والكيانات

غير موجود في الملفات الحالية. كيان واحد هو المنصة.

## 10. الصلاحيات

النوع `senior` أو `volunteer` يحدد ما تعرضه الصفحات التي تفحص الجلسة عبر `includes/auth612.php`. لا جدول صلاحيات أدق. المتطوع يرى رتبته. المستفيد ينشئ الطلبات والتقييم حسب مسارات API.

قواعد الرتبة في `rafiq612_compute_target_rank`:

| الشرط | الرتبة | الاسم الظاهر |
| --- | --- | --- |
| أقل من شروط الحارس | `refiq_ahd` | رفيق العهد |
| `tasks_count` >= 5 مع تقييم موجب | `haris_wudd` | حارس الود |
| المهام >= 20 واختبار الحكمة ناجح ومتوسط >= 4.5 | `safir_hikma` | سفير الحكمة |

اختبار الحكمة علم `hikma_test_passed`. صفحة `safir_test612.php` مرتبطة بهذا المسار.

## 11. الأتمتة وWorkflows

لا Cron. السلسلة التلقائية داخل الطلب: تغيير حالة الموعد أو التقييم يعيد حساب `tasks_count` و `rank`. `rank_congrat_pending` عمود لتنبيه الترقية في الواجهة.

حالات المساعدة والموعد والدردشة تنتقل بقيم التعداد عند استدعاء API لا بمهام خلفية.

## 12. التكامل بين الوحدات

موعد مكتمل يزيد مهام المتطوع. تقييم الموعد يحدّث المتوسط فيؤثر في سفير الحكمة. طلب مساعدة مكتمل يدخل في عدّ المهام عبر `rafiq612_rebuild_volunteer_tasks_count`. الدردشة جدول مستقل `chat_messages` بحقول `sender_id` و `receiver_id` و `is_read`. `chat_sessions` جدول إضافي بحالات `pending` و `active` و `completed` و `cancelled`، والتعليق في SQL يصفه بأنه للتوسع.

## 13. المصطلحات

| المصطلح | المعنى |
| --- | --- |
| أنيس الروح | الدردشة |
| زيادة الود | المواعيد |
| رفيق العهد / حارس الود / سفير الحكمة | رتب `refiq_ahd` / `haris_wudd` / `safir_hikma` |
| `rafiq_al_jewar` | اسم القاعدة |
| اللاحقة `612` | لاحقة أسماء الملفات في `projct612` |

## 14. الأسئلة الشائعة

**أين أشغّل الملفات؟**  
من داخل `projct612` أو بجذر ويب يشير إليه. README السابق يشرح XAMPP وإضافة VS Code «PHP Server». مجلد `.vscode` غير موجود في الملفات الحالية، لذلك أوامر تلك الإضافة غير مجهزة داخل المستودع الآن.

**هل أستورد SQL أكثر من مرة؟**  
الإنشاء `IF NOT EXISTS`. أوامر `ALTER` في نهاية `database612.sql` معلّقة بشرطات لتحديث قاعدة قديمة، ولا تُنفَّذ مع الاستيراد العادي.

**ما إصدار PHP؟**  
README السابق يطلب 8 أو أحدث بسبب `declare(strict_types=1)`.

## 15. المعمارية

```
projct612
  صفحات PHP/HTML + JS/CSS
        |
        v
  includes/bootstrap612.php
  includes/auth612.php
  includes/config612.php -> PDO
        |
        v
  MySQL rafiq_al_jewar
```

`Graduation-project-main/README.md` هو التوثيق في جذر المستودع. الكود ليس في الجذر.

## 16. التقنيات

PHP مع PDO MySQL، HTML، CSS (`main612.css` و `dashboard612.css` و `colors612.css` و `modal612.css` و `responsive612.css`)، JavaScript (`main612.js` و `dashboard612.js` و `login612.js` و `chat612.js` و `map612.js` و `modal612.js`). لا إطار PHP ظاهر. README السابق يذكر جلسة PHP و `json`.

## 17. هيكل المشروع

```
Graduation-project-main/
  README.md
  projct612/
    index612.html login612.html login612.php login612.js
    auth_login612.php auth_register612.php logout612.php
    dashboard612.php dashboard612.html dashboard612.js dashboard612.css
    anis_al_rooh612.php chat612.php chat612.js messages612.php
    ziyada_wud612.php appointment_detail612.php
    seniors612.php volunteers612.php help_history612.php safir_test612.php
    api_*.php
    database612.sql
    main612.css main612.js map612.js modal612.js
    colors612.css responsive612.css
    assets/images/hero-bg.jpg612.jpg
    includes/
      auth612.php bootstrap612.php config612.php
      footer612.php legal_modals612.php volunteer_rank612.php
```

`.vscode` المذكور في README السابق غير موجود في المجلد الحالي.

## 18. الواجهة

صفحات PHP ترسم HTML بعد الجلسة، وصفحات HTML ثابتة (`index612.html` و `login612.html` و `dashboard612.html`). السكربت يستدعي `api_*612.php` ويتوقع JSON فيه `ok`. `responsive612.css` للتنسيق المتجاوب. `modal612.js` للنوافذ. `map612.js` للخريطة. صورة البطل: `assets/images/hero-bg.jpg612.jpg`.

أيقونة تبويب غير ظاهرة في قائمة الملفات المفحوصة.

## 19. الخادم

`rafiq612_pdo()` في `config612.php`. الثوابت تقرأ البيئة ثم افتراضًا:

| ثابت | بيئة | افتراض README السابق |
| --- | --- | --- |
| المضيف | `RAFIQ_DB_HOST` | `localhost` |
| القاعدة | `RAFIQ_DB_NAME` | `rafiq_al_jewar` |
| المستخدم | `RAFIQ_DB_USER` | `root` |
| كلمة المرور | `RAFIQ_DB_PASS` | فارغة في XAMPP |

`RAFIQ612_DEBUG` في الملف يتحكم بعرض الأخطاء. README السابق يطلب `false` في الإنتاج.

دوال الرتبة في `volunteer_rank612.php` منها `rafiq612_rank_meta` و `rafiq612_recompute_volunteer_rank` و `rafiq612_volunteer_progress_message`.

## 20. مسار الطلب

```
login612.js
  -> POST auth_login612.php
  -> rafiq612_pdo()
  -> users
  -> جلسة
  -> dashboard612.php

dashboard612.js
  -> fetch api_help612.php أو api_chat612.php
  -> JSON
  -> تحديث الواجهة
```

## 21. قاعدة البيانات

MySQL `utf8mb4` الاسم `rafiq_al_jewar`.

| الجدول | دور |
| --- | --- |
| `users` | حساب، نوع، رتبة، `tasks_count`، `rating_sum`، `hikma_test_passed`، هاتف وبريد فريدان |
| `chat_sessions` | جلسة محادثة مجدولة |
| `chat_messages` | رسائل أنيس الروح |
| `appointments` | مواعيد زيادة الود |
| `emergency_alerts` | تنبيه بحالات `pending` `responded` `resolved` `active` |
| `help_requests` | مساعدة فورية مع موقع اختياري |
| `ratings` | تقييم 1 إلى 5 مربوط بموعد |

ملفات ترحيل منفصلة غير موجودة. نهاية `database612.sql` أوامر `ALTER` معلّقة.

## 22. واجهة البرمجة

ملفات ترجع JSON. المصادقة جلسة PHP في المسارات التي تضم `auth612.php`. الطريقة POST أو GET بحسب كل ملف كما ذكر README السابق.

| الملف | الغرض |
| --- | --- |
| `auth_login612.php` | دخول |
| `auth_register612.php` | تسجيل في `users` |
| `api_chat612.php` | رسائل |
| `api_appointments612.php` | مواعيد |
| `api_appointment_status612.php` | حالة موعد وإعادة الرتبة عند الإكمال |
| `api_claim_appointment612.php` | التقاط موعد |
| `api_help612.php` | إنشاء مساعدة |
| `api_help_action612.php` | إجراء على المساعدة |
| `api_help_status612.php` | حالة المساعدة |
| `api_pending_help612.php` | المعلّق |
| `api_post_rating612.php` | تقييم |
| `api_volunteers612.php` | قائمة متطوعين |

الاستجابة نمط `ok` كما في README السابق.

## 23. المصادقة والصلاحيات

جلسة PHP عبر `bootstrap612.php`. كلمة المرور في `password_hash` (تجزئة bcrypt في البذرة تبدأ بنمط `$2y$`). النوع يفرز المستفيد عن المتطوع. الخروج `logout612.php`.

## 24. الأمان

الموجود: تجزئة كلمة المرور، مفاتيح فريدة للبريد والهاتف، قيود أجنبية، تقييم مقيّد 1 إلى 5، منع تقييم مكرر لنفس الموعد، علم تصحيح `RAFIQ612_DEBUG`.

غير الموجود في الفحص: CSRF موثق، حد محاولات، أيقونة تبويب. README السابق يذكر HTTPS في الإنتاج كخطوة لاحقة لا كإعداد ملف.

رسائل الخطأ التفصيلية تبقى مغلقة عندما يكون التصحيح `false`.

## 25. الإعدادات

`projct612/includes/config612.php` ومتغيرات البيئة `RAFIQ_DB_HOST` و `RAFIQ_DB_NAME` و `RAFIQ_DB_USER` و `RAFIQ_DB_PASS`. لا ملف `.env` في الشجرة الحالية. لا تُكتب كلمة مرور القاعدة هنا.

## 26. التكاملات

غير موجود كبريد أو دفع أو خرائط Google موثقة بمفتاح. `map612.js` منطق خريطة محلي في الواجهة. لا Cloudflare في الملفات.

## 27. المهام المجدولة

غير موجود في الملفات الحالية.

## 28. تخزين الملفات

صورة `assets/images/hero-bg.jpg612.jpg`. لا مجلد رفع. الرسائل نص في `chat_messages`.

## 29. السجلات والمراقبة

لا جدول audit منفصل. حالات الطلبات والموعد والتنبيه هي سجل العمل. أخطاء PHP تعتمد على `RAFIQ612_DEBUG` وسجل الخادم. غير موثق كملفات log داخل المشروع.

## 30. التثبيت

1. PHP 8 مع `pdo_mysql` و `session` و `json`، و MySQL 5.7 أو 8 كما في README السابق.
2. شغّل MySQL.
3. استورد `projct612/database612.sql` من phpMyAdmin أو عميل SQL.
4. اضبط البيئة أو اترك افتراض XAMPP في `config612.php`.
5. انسخ `projct612` تحت جذر الويب، مثل `htdocs/Graduation-project-main/projct612`.
6. افتح `login612.php` أو `index612.html`.
7. بديل من الطرفية، كما في README السابق: `php -S` على المنفذ 8080 من مجلد `projct612`. مجلد `.vscode` الذي كان يضبط إضافة PHP Server غير موجود الآن.

حسابات التجربة في القسم 3. كلمة المرور المشتركة مذكورة في تعليق `database612.sql`.

## 31. دليل التطوير

- صفحة: ملف `*612.php` يضم `includes/bootstrap612.php` أو `auth612.php`.
- API: ملف `api_*612.php` يرجع JSON بحقل `ok`.
- جدول: عدّل `database612.sql`. للقاعدة القديمة استخدم `ALTER` المعلّق في نهاية الملف بعد مراجعة.
- رتبة: لا تحسبها في الواجهة فقط. الدوال في `volunteer_rank612.php`.
- اللاحقة `612` نمط التسمية الحالي.

## 32. النشر

غير موثق كمنصة. README السابق يطلب HTTPS وإخفاء أخطاء PHP عبر `RAFIQ612_DEBUG=false` ومراجعة صلاحيات الملفات. لا `Dockerfile`.

## 33. النسخ الاحتياطي

غير موجود كسكربت. README السابق يقترح تصدير SQL من phpMyAdmin. الاستعادة بإعادة الاستيراد مع الانتباه لأوامر `ALTER` المعلّقة.

## 34. استكشاف الأخطاء

| العرض | المطابق لـ README السابق والكود |
| --- | --- |
| Access denied للمستخدم root | كلمة مرور MySQL لا تطابق `config612.php` أو `RAFIQ_DB_PASS` |
| Unknown database | لم يُستورد `database612.sql` |
| صفحة بيضاء | فعّل التصحيح مؤقتًا في الإعداد |
| الجلسة لا تبقى | مسار URL لا يمر على نفس ملفات الجلسة |
| رتبة لا تتغير | المهام أقل من 5، أو لا تقييم موجب، أو اختبار الحكمة غير مسجّل لسفير الحكمة |
| أوامر إضافة VS Code غير موجودة | مجلد `.vscode` غير موجود في النسخة الحالية |
| Live Server لا ينفّذ PHP | استخدم PHP حقيقي كما نبّه README السابق |

## 35. الاعتماديات

PHP 8 وامتداد PDO MySQL حسب README السابق. لا Composer. JavaScript بلا `package.json`.

## 36. القيود المعروفة

- نوعان فقط من المستخدمين.
- `.vscode` الموعود في README السابق غير موجود.
- ترقيات القاعدة القديمة تعليقات SQL لا تشغيل تلقائي.
- لا أيقونة تبويب في الشجرة.
- لا نسخ احتياطي مبرمج.
- اسم ملف الصورة ينتهي بـ `jpg612.jpg`.

## 37. حالة النظام الحالية

| الحالة | التفاصيل |
| --- | --- |
| موجود | صفحات الوحدات، API، سبعة جداول، رتب، بذرة |
| يعمل مع PHP و MySQL | الدخول والعمليات |
| غير موجود رغم التوثيق السابق | `.vscode` وإعدادات إضافة PHP Server |
| غير موثق | نطاق إنتاج واسم الجهة الأكاديمية خارج عنوان مشروع التخرج |

## 38. القرارات المعمارية

استنتاج من الكود: كل الملفات تحمل اللاحقة `612` داخل مجلد واحد بلا إطار. الرتبة تُعاد حسابها من المواعيد المكتملة والمساعدة المكتملة لا من عداد يُزاد يدويًا فقط (`rafiq612_rebuild_volunteer_tasks_count`). التقييم مرة واحدة لكل موعد عبر المفتاح الفريد. `chat_sessions` معلّم في SQL كجدول توسع بجانب الرسائل المباشرة.

## 39. سجل التغييرات

لا ملف إصدارات. README السابق مؤرخ كنص «مشروع تخرج 2026». نهاية `database612.sql` توثق ترقية قواعد أقدم لأعمدة `users` ولجداول المساعدة والتقييم. هذا الدليل يستبدل README السابق في الجذر ويُبقي الحقائق التي أكدها SQL والكود، ويسقط افتراض وجود `.vscode`.

## System Overview

```
مستفيد أو متطوع
    -> login612 / dashboard612
         |
         +-> أنيس الروح chat_messages
         +-> زيادة الود appointments
         +-> help_requests + emergency_alerts
         +-> ratings
         |
         v
    volunteer_rank612
    refiq_ahd | haris_wudd | safir_hikma
         |
         v
    MySQL rafiq_al_jewar
```

## Quick Reference

| الجزء | التقنية | الموقع | الوظيفة |
| --- | --- | --- | --- |
| الدخول | PHP/JS | `login612.php` `auth_login612.php` | جلسة |
| اللوحة | PHP/JS | `dashboard612.php` | بعد الدخول |
| الدردشة | PHP/JS | `chat612.php` `api_chat612.php` | أنيس الروح |
| المواعيد | PHP | `ziyada_wud612.php` `api_appointments612.php` | زيادة الود |
| المساعدة | PHP | `api_help612.php` | طلب فوري |
| الرتب | PHP | `includes/volunteer_rank612.php` | ثلاث رتب |
| القاعدة | SQL | `database612.sql` | `rafiq_al_jewar` |
| الإعداد | PHP | `includes/config612.php` | PDO |

## Quick Start

استورد `projct612/database612.sql`، شغّل MySQL وPHP، افتح `projct612/login612.php`.

## For Non-Technical Users

- ما هو النظام؟ منصة رفيقُ الجوار تصل المستفيد بالمتطوع.
- ماذا يفعل؟ دردشة، مواعيد، طلب مساعدة، تقييم، ورتب للمتطوع.
- كيف يُستخدم؟ الدخول من صفحة تسجيل الدخول ثم اختيار القسم في اللوحة.
- أهم الأقسام: أنيس الروح، زيادة الود، المساعدة، المتطوعون والمستفيدون.
- حسابات التجربة الثلاثة مذكورة في ملف القاعدة مع كلمة مرور واحدة مكتوبة هناك.

## For Developers

- التقنيات: PHP و MySQL و HTML و CSS و JavaScript.
- المعمارية: صفحات و`api_*612.php` عبر PDO.
- قاعدة البيانات: `rafiq_al_jewar` بسبعة جداول.
- API: ملفات `api_` و `auth_` في `projct612`.
- أهم الملفات: `includes/config612.php` و `includes/auth612.php` و `includes/volunteer_rank612.php` و `database612.sql`.
- التطوير داخل `projct612` مع اللاحقة `612`. مجلد `.vscode` غير موجود في النسخة الحالية.
