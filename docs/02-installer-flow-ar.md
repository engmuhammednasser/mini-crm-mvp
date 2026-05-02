# 02 — تدفق عملية التثبيت خطوة بخطوة

---

## قبل رفع المشروع (على الجهاز المحلي)

### الخطوة 1 — التأكد من اكتمال المشروع

تأكد من وجود الملفات التالية قبل الرفع:

```
✅ .env.example           (قالب ملف الإعداد)
✅ public/install.php     (نقطة دخول المثبِّت)
✅ routes/web.php         (يحتوي مسارات /installer)
✅ app/Http/Controllers/InstallerController.php
✅ resources/views/installer/setup.blade.php
✅ resources/views/installer/success.blade.php
✅ generate-installer-token.php
```

### الخطوة 2 — تثبيت حزم PHP (بدون حزم التطوير)

```bash
composer install --no-dev --optimize-autoloader
```

### الخطوة 3 — بناء ملفات الـ Frontend

```bash
npm ci
npm run build
```

---

## توليد التوكن

### الخطوة 4 — توليد توكن التثبيت

نفِّذ السكريبت التالي من جذر المشروع:

```bash
php generate-installer-token.php --base-url=https://yourdomain.com
```

**ما يفعله السكريبت:**

1. يتحقق من عدم وجود `storage/app/installed.lock` (حماية من إعادة التوليد بعد التثبيت).
2. يُولِّد توكناً عشوائياً بطول 64 حرفاً (hex) باستخدام `bin2hex(random_bytes(32))`.
3. يحفظ التوكن في `storage/app/installer-token.txt`.
4. يطبع الروابط المطلوبة:

```
──────────────────────────────────────────────────────────────────────
  ✅  Installer token generated successfully!
──────────────────────────────────────────────────────────────────────

  Token saved to : storage/app/installer-token.txt
  Token          : [توكن مخفي — لا يُنشر]

  Installer URLs :
    Via install.php  →  https://yourdomain.com/install.php
    Direct URL       →  https://yourdomain.com/installer/[token]
```

> ⚠️ احتفظ بالتوكن في مكان آمن. لا تنشره أو تشاركه.

---

## رفع الملفات والإعداد الأولي

### الخطوة 5 — رفع ملفات المشروع

ارفع جميع ملفات المشروع على السيرفر بما فيها:

```
storage/app/installer-token.txt    ← مهم جداً
.env.example                       ← مطلوب كقالب
```

> ❌ **لا ترفع ملف `.env` الخاص بالبيئة المحلية.**

### الخطوة 6 — توجيه الدومين

يجب أن يشير الدومين إلى مجلد `public/` فقط وليس إلى جذر المشروع.

```
❌ خاطئ:  /home/user/public_html/mini-crm/
✅ صحيح:  /home/user/public_html/mini-crm/public/
```

### الخطوة 7 — إنشاء قاعدة البيانات

أنشئ قاعدة بيانات MySQL من لوحة تحكم الاستضافة (cPanel أو غيرها) مع تعيين المستخدم وصلاحية كاملة.

---

## فتح المثبِّت

### الخطوة 8 — فتح `install.php` في المتصفح

```
https://yourdomain.com/install.php
```

**ما يحدث داخلياً:**

```
install.php
    │
    ├─ يقرأ dirname(__DIR__) ← مجلد جذر المشروع
    ├─ يتحقق من: storage/app/installed.lock
    │     إذا موجود → HTTP 404 "Not Found."
    ├─ يتحقق من: storage/app/installer-token.txt
    │     إذا غائب → HTTP 403 "Forbidden."
    ├─ يقرأ التوكن من installer-token.txt
    │     إذا فارغ → HTTP 403 "Forbidden."
    └─ يُعيد التوجيه (302) إلى:
          https://yourdomain.com/installer/{token}
```

### الخطوة 9 — عرض نموذج الإعداد

بعد التوجيه، يستقبل Laravel الطلب عبر:

```
GET /installer/{token}
```

يُنفِّذ `InstallerController::showForm()`:
1. يتحقق من `installed.lock` → abort(404) إذا موجود.
2. يقرأ التوكن من الملف ويقارنه بـ `hash_equals()` → abort(403) إذا لم يطابق.
3. يستخرج `$appUrl` من `$request->getSchemeAndHttpHost()` (يُعبَّأ تلقائياً في حقل App URL).
4. يعرض `resources/views/installer/setup.blade.php`.

---

## إرسال بيانات قاعدة البيانات

### الخطوة 10 — ملء النموذج وإرساله

يُعبِّئ المستخدم الحقول التالية:

| الحقل | القيمة المتوقعة |
|---|---|
| Application Name | اسم التطبيق (مثال: `Mini CRM`) |
| Application URL | عنوان URL الكامل (مثال: `https://yourdomain.com`) |
| DB Host | عادةً `127.0.0.1` |
| DB Port | عادةً `3306` |
| Database Name | اسم قاعدة البيانات التي أنشأتها |
| DB Username | مستخدم قاعدة البيانات |
| DB Password | كلمة المرور (اختيارية إذا لم تكن مطلوبة) |

بعد النقر على **Run Installation**، يُرسَل طلب:

```
POST /installer/{token}
```

---

## تنفيذ التثبيت

### الخطوة 11 — ما يحدث بعد إرسال النموذج

يُنفِّذ `InstallerController::runInstall()` التسلسل التالي:

#### أ) التحقق من المدخلات (Validation)

```
app_name    → required, string, max:100
app_url     → required, url, max:255
db_host     → required, string, max:255
db_port     → required, integer, min:1, max:65535
db_database → required, string, max:255
db_username → required, string, max:255
db_password → nullable, string, max:255
```

إذا فشل التحقق → يُعاد عرض النموذج مع رسائل الخطأ.

#### ب) اختبار الاتصال بقاعدة البيانات

```php
new PDO(
    'mysql:host=...;port=...;dbname=...;charset=utf8mb4',
    username,
    password,
    [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION]
)
```

- Timeout: 5 ثوانٍ.
- إذا فشل الاتصال → يُعاد النموذج مع رسالة الخطأ.
- إذا نجح → يُغلَق الاتصال فوراً (حماية من الاتصالات المفتوحة).

#### ج) كتابة ملف `.env`

```
قراءة .env.example كقالب
         │
تحديث/إضافة المفاتيح:
  APP_NAME    = [من النموذج]
  APP_ENV     = production
  APP_DEBUG   = false
  APP_URL     = [من النموذج]
  DB_CONNECTION = mysql
  DB_HOST     = [من النموذج]
  DB_PORT     = [من النموذج]
  DB_DATABASE = [من النموذج]
  DB_USERNAME = [من النموذج]
  DB_PASSWORD = [من النموذج]
  LOG_LEVEL   = error
         │
كتابة الناتج إلى .env
```

#### د) تنفيذ أوامر Artisan

تُنفَّذ بالترتيب التالي عبر `Artisan::call()`:

```
1. config:clear          → مسح الـ cache القديم
2. key:generate --force  → توليد APP_KEY وحفظه في .env
3. migrate --force       → إنشاء جداول قاعدة البيانات
4. db:seed --force       → تعبئة البيانات الأولية
5. storage:link          → ربط public/storage بـ storage/app/public
6. optimize              → تحسين الأداء (config/route cache)
```

إذا فشل أي أمر من 1 إلى 4 أو 6 → يتوقف التثبيت ويُعرض الخطأ.  
إذا فشل الأمر 5 (`storage:link`) → يُسجَّل تحذير في `laravel.log` ويكمل التثبيت.

---

## اكتمال التثبيت وتعطيل المثبِّت

### الخطوة 12 — خطوات الإنهاء

بعد نجاح جميع الأوامر:

```
1. إنشاء storage/app/installed.lock
   المحتوى: تاريخ ووقت التثبيت (مثال: 2026-05-02 14:30:00)

2. حذف storage/app/installer-token.txt
   (التوكن لم يعد ضرورياً ويُشكِّل خطراً أمنياً)

3. حذف public/install.php  (best-effort)
   (إذا تعذَّر الحذف بسبب صلاحيات السيرفر، يجب حذفه يدوياً)

4. عرض resources/views/installer/success.blade.php
```

### الخطوة 13 — كيف يتعطَّل المثبِّت

بعد إنشاء `installed.lock`:

- **أي طلب على `/install.php`** → يُعيد HTTP 404 (الملف محذوف أو يتحقق من الـ lock).
- **أي طلب على `/installer/{token}`** → الـ controller يتحقق من `installed.lock` في أول سطر → `abort(404)`.
- **أي توكن آخر** → يُعيد 404 لعدم مطابقة الـ regex.

> ✅ المثبِّت معطَّل نهائياً. لا يوجد أي طريقة لتشغيله مجدداً بدون حذف `installed.lock` يدوياً.

---

## ملخص الخطوات

```
 1  ✅  التحقق من الملفات المطلوبة
 2  ✅  composer install --no-dev
 3  ✅  npm ci && npm run build
 4  ✅  php generate-installer-token.php
 5  ✅  رفع الملفات على السيرفر
 6  ✅  توجيه الدومين إلى /public
 7  ✅  إنشاء قاعدة البيانات
 8  ✅  فتح /install.php في المتصفح
 9  ✅  ملء النموذج
10  ✅  إرسال النموذج
11  ✅  تنفيذ أوامر Artisan
12  ✅  إنشاء installed.lock + حذف التوكن
13  ✅  المثبِّت معطَّل — التطبيق جاهز
```

---

*السابق: [01 — المعمارية](./01-system-architecture-ar.md) | التالي: [03 — الملفات](./03-files-created-ar.md)*
