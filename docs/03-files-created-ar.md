# 03 — قائمة الملفات المضافة والمعدَّلة

---

## نظرة عامة

| # | مسار الملف | النوع | الحالة |
|---|---|---|---|
| 1 | `public/install.php` | PHP خالص | ✅ جديد |
| 2 | `app/Http/Controllers/InstallerController.php` | Laravel Controller | ✅ جديد |
| 3 | `resources/views/installer/setup.blade.php` | Blade View | ✅ جديد |
| 4 | `resources/views/installer/success.blade.php` | Blade View | ✅ جديد |
| 5 | `routes/web.php` | Laravel Routes | ✏️ معدَّل |
| 6 | `generate-installer-token.php` | PHP Script | ✅ جديد |
| 7 | `storage/app/installer-token.txt` | ملف بيانات | 🔄 يُنشأ عند التوليد |
| 8 | `storage/app/installed.lock` | ملف قفل | 🔄 يُنشأ بعد التثبيت |
| 9 | `.env` | ملف إعداد | 🔄 يُكتَب بواسطة المثبِّت |
| 10 | `.env.example` | قالب الإعداد | 📖 يُقرأ فقط |

---

## 1. `public/install.php`

**الغرض:** نقطة الدخول الأولى للمثبِّت. يعمل بـ PHP خالص خارج إطار Laravel.

**المسؤوليات:**
- يتحقق من `installed.lock` → HTTP 404 إذا وُجد.
- يتحقق من `installer-token.txt` → HTTP 403 إذا غاب أو كان فارغاً.
- يكشف HTTPS/HTTP تلقائياً ويبني رابط التوجيه.
- يُعيد التوجيه (302) إلى `/installer/{token}`.

**ملاحظات:**
- يُحذَف تلقائياً بعد التثبيت. إذا فشل الحذف، احذفه يدوياً.
- لا يعرض التوكن في صفحة الاستجابة — يمرره في الـ redirect فقط.

---

## 2. `app/Http/Controllers/InstallerController.php`

**الغرض:** قلب نظام التثبيت — يتحقق من الأمان وينفذ جميع خطوات الإعداد.

**الدوال الرئيسية:**

| الدالة | الغرض |
|---|---|
| `guardNotInstalled()` | abort(404) إذا وُجد installed.lock |
| `guardToken(string)` | abort(403) إذا كان التوكن خاطئاً |
| `showForm()` | عرض نموذج الإعداد |
| `runInstall()` | تنفيذ التثبيت الكامل |
| `writeEnv()` | كتابة .env من القالب |
| `quoteEnvValue()` | تغليف القيم الحساسة بالاقتباسات |

**ملاحظات:**
- لا يستخدم `shell_exec` أو `exec` إطلاقاً.
- يستخدم `hash_equals()` لمنع هجمات Timing Attack.
- `storage:link` غير حرجة — فشلها لا يوقف التثبيت.

---

## 3. `resources/views/installer/setup.blade.php`

**الغرض:** نموذج HTML بتصميم داكن احترافي لإدخال بيانات الإعداد.

**الحقول:**

| الحقل | النوع | مطلوب |
|---|---|---|
| `app_name` | text | نعم |
| `app_url` | url | نعم — مُعبَّأ مسبقاً |
| `db_host` | text | نعم — افتراضي: `127.0.0.1` |
| `db_port` | number | نعم — افتراضي: `3306` |
| `db_database` | text | نعم |
| `db_username` | text | نعم |
| `db_password` | password | لا |

**ملاحظات:**
- يحتوي `@csrf` لحماية CSRF.
- CSS وJS مضمَّنان في الملف (لا يعتمد على Vite).
- يعرض أخطاء التحقق من `$errors` bag.

---

## 4. `resources/views/installer/success.blade.php`

**الغرض:** صفحة تأكيد اكتمال التثبيت.

**المحتوى:**
- رسالة نجاح مع قائمة الخطوات المُنجزة.
- تحذير أمني بشأن حذف `install.php` يدوياً إذا لم يُحذَف تلقائياً.
- زر للانتقال إلى التطبيق.

---

## 5. `routes/web.php`

**الحالة:** معدَّل — أُضيف إليه مسارا المثبِّت.

**ما أُضيف:**

```php
use App\Http\Controllers\InstallerController;

Route::get('/installer/{token}', [InstallerController::class, 'showForm'])
    ->name('installer.show')
    ->where('token', '[a-f0-9]{64}');

Route::post('/installer/{token}', [InstallerController::class, 'runInstall'])
    ->name('installer.run')
    ->where('token', '[a-f0-9]{64}');
```

**ملاحظات:**
- المسارات خارج أي Middleware Group.
- قيد `where` يمنع أي توكن بصيغة مختلفة من المرور.
- لم يُعدَّل أي مسار قائم.

---

## 6. `generate-installer-token.php`

**الغرض:** سكريبت CLI لتوليد توكن آمن وحفظه.

**الاستخدام:**

```bash
php generate-installer-token.php --base-url=https://yourdomain.com
```

**ملاحظات:**
- يُولِّد توكناً بـ `bin2hex(random_bytes(32))` — 64 حرفاً hex آمناً تشفيرياً.
- يتحقق من `installed.lock` قبل التوليد.
- يُشغَّل مرة واحدة فقط قبل النشر.

---

## 7. `storage/app/installer-token.txt`

**الغرض:** حفظ التوكن الآمن بين عملية التوليد وعملية التثبيت.

**المحتوى:** سلسلة 64 حرفاً hex.

**ملاحظات:**
- ⚠️ ملف حساس — من يمتلكه يستطيع تشغيل المثبِّت.
- يُحذَف تلقائياً بعد اكتمال التثبيت.
- يجب إضافته لـ `.gitignore`.

---

## 8. `storage/app/installed.lock`

**الغرض:** قفل يمنع إعادة تشغيل المثبِّت بعد اكتماله.

**المحتوى:** تاريخ ووقت التثبيت.

**ملاحظات:**
- لا يُحذَف تلقائياً أبداً.
- يُتحقق منه في كل طلب على مسارات المثبِّت.
- احذفه يدوياً فقط إذا أردت إعادة التثبيت من الصفر.

---

## 9. `.env`

**الغرض:** ملف إعداد البيئة — يُكتَب بواسطة المثبِّت.

**المفاتيح التي يكتبها المثبِّت:**

```env
APP_NAME=your_app_name
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
LOG_LEVEL=error
```

**ملاحظات:**
- ⚠️ ملف سري — لا يُرفع على Git أبداً.
- `APP_KEY` يُضبَط بواسطة `key:generate` بعد كتابة الملف.

---

## 10. `.env.example`

**الغرض:** قالب `.env` — يُقرأ فقط ولا يُعدَّل.

**ملاحظات:**
- يضمن الحفاظ على جميع المفاتيح الأخرى (SESSION, CACHE, MAIL, ...).
- إذا لم يكن موجوداً، يبدأ المثبِّت من ملف فارغ.
- يجب رفعه دائماً مع المشروع.

---

*السابق: [02 — تدفق التثبيت](./02-installer-flow-ar.md) | التالي: [04 — ملاحظات الأمان](./04-security-notes-ar.md)*
