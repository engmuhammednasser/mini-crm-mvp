# 06 — استكشاف الأخطاء وإصلاحها

---

## كيفية استخدام هذا الدليل

لكل مشكلة:
1. ابحث عن الأعراض التي تصف وضعك.
2. اقرأ الأسباب المحتملة.
3. جرِّب الحلول بالترتيب.

---

## 1. خطأ 500 Internal Server Error

**الأعراض:** صفحة بيضاء أو رسالة "500 Internal Server Error" عند فتح أي صفحة.

**الأسباب المحتملة:**

| السبب | الحل |
|---|---|
| `.env` غير موجود | المثبِّت لم يكتبه بعد — تأكد من إتمام التثبيت |
| `APP_KEY` فارغ | شغِّل `php artisan key:generate --force` |
| خطأ في `.htaccess` | تأكد من تفعيل `mod_rewrite` على Apache |
| صلاحيات `storage/` خاطئة | `chmod -R 755 storage bootstrap/cache` |
| نسخة PHP غير متوافقة | تأكد من PHP 8.2+ |

**كيف تعرف السبب الدقيق:**

ابحث عن ملف السجلات:

```
storage/logs/laravel.log
```

آخر أسطر هذا الملف تحتوي على رسالة الخطأ التفصيلية.

---

## 2. الدومين يشير إلى المجلد الخاطئ (Document Root)

**الأعراض:**
- ظهور قائمة ملفات المشروع في المتصفح.
- ظهور محتوى `index.php` كنص خام.
- رسالة "403 Forbidden" على الصفحة الرئيسية.

**الحل:**

تأكد أن Document Root يشير إلى مجلد `public/` وليس جذر المشروع:

```
❌ خاطئ:  /home/user/public_html/mini-crm/
✅ صحيح:  /home/user/public_html/mini-crm/public/
```

على cPanel: Domains → Addon Domains → عدِّل Document Root.

---

## 3. فشل الاتصال بقاعدة البيانات

**الأعراض:** رسالة خطأ في النموذج: `Database connection failed: ...`

**التشخيص:** اقرأ نص الخطأ الكامل — يظهر في النموذج مباشرةً.

| رسالة الخطأ | السبب | الحل |
|---|---|---|
| `Access denied for user` | بيانات الدخول خاطئة | راجع DB User وPassword |
| `Unknown database` | اسم قاعدة البيانات خاطئ | تأكد من الاسم في cPanel |
| `Connection refused` | Host أو Port خاطئ | جرِّب `localhost` بدلاً من `127.0.0.1` أو العكس |
| `SQLSTATE[HY000] [2002]` | السيرفر لا يقبل الاتصال | تحقق من Host المُقدَّم من الاستضافة |
| `php_network_getaddresses` | Host غير صالح | استخدم `127.0.0.1` |

**ملاحظة:** بعض شركات الاستضافة تستخدم Host مختلفاً لقاعدة البيانات (مثل `mysql.yourdomain.com`). راجع لوحة التحكم أو الدعم الفني.

---

## 4. فشل كتابة ملف `.env`

**الأعراض:** رسالة خطأ: `Failed to write .env file` أو `Could not write to .env file. Check permissions.`

**الأسباب والحلول:**

```bash
# تأكد من أن المستخدم الذي يشغِّل PHP يملك صلاحية الكتابة
chmod 664 .env.example
chmod -R 755 storage

# إذا كان .env موجوداً بصلاحيات خاطئة:
chmod 664 .env
```

إذا لم تنفع الصلاحيات، تواصل مع الدعم الفني للاستضافة لمعرفة مستخدم PHP.

---

## 5. مشكلة صلاحيات مجلد `storage/`

**الأعراض:**
- خطأ 500 عند كتابة ملفات السجل.
- فشل حفظ الـ Sessions.
- فشل كتابة `installed.lock`.

**الحل:**

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# إذا كان السيرفر يستخدم www-data:
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

على cPanel يمكن تغيير الصلاحيات من **File Manager → تحديد المجلد → Permissions**.

---

## 6. فشل المايجريشن

**الأعراض:** رسالة خطأ: `migrate — SQLSTATE[42000]: Syntax error or access violation`

**الأسباب والحلول:**

| السبب | الحل |
|---|---|
| المستخدم لا يملك صلاحية `CREATE TABLE` | أعطه `All Privileges` من cPanel |
| قاعدة البيانات غير موجودة | أنشئها أولاً من cPanel |
| encoding خاطئ | تأكد من إنشاء القاعدة بـ `utf8mb4_unicode_ci` |
| ملف migration به خطأ | راجع `storage/logs/laravel.log` |

للتشخيص:

```bash
# عبر SSH
php artisan migrate --force --verbose
```

---

## 7. فشل `storage:link`

**الأعراض:** تحذير في سجل `laravel.log`: `Installer: storage:link failed`

> هذا الخطأ **غير حرج** — التثبيت يكتمل بدونه.

**الأسباب:**

- السيرفر لا يدعم `symlink()`.
- الرابط موجود مسبقاً من نشر سابق.
- الصلاحيات لا تسمح بإنشاء Symlink.

**الحل اليدوي عبر SSH:**

```bash
php artisan storage:link
```

**الحل اليدوي بدون SSH:**

أنشئ رابطاً رمزياً يدوياً:

```bash
ln -s /full/path/to/storage/app/public /full/path/to/public/storage
```

أو تواصل مع الدعم الفني للاستضافة.

---

## 8. `APP_KEY` مفقود أو فارغ

**الأعراض:**
- خطأ: `No application encryption key has been specified.`
- لا يمكن تشغيل الـ Sessions.

**السبب:** فشل `key:generate` أثناء التثبيت، أو كتابة `.env` قبل التوليد.

**الحل عبر SSH:**

```bash
php artisan key:generate --force
```

**الحل اليدوي** (إذا لم يكن SSH متاحاً):

1. شغِّل على جهازك المحلي:
   ```bash
   php artisan key:generate --show
   ```
2. انسخ الناتج (مثال: `base64:xxxx...`).
3. افتح `.env` على السيرفر وأضف:
   ```env
   APP_KEY=base64:your_generated_key
   ```

---

## 9. توكن التثبيت مفقود

**الأعراض:** فتح `install.php` يُعيد **403 Forbidden** ورسالة: `Installer token not found.`

**الأسباب:**

- نسيت رفع `storage/app/installer-token.txt`.
- تم حذف الملف عن طريق الخطأ.

**الحل:**

شغِّل السكريبت مجدداً (على الجهاز المحلي أو السيرفر):

```bash
php generate-installer-token.php --base-url=https://yourdomain.com
```

ثم ارفع الملف الناتج `storage/app/installer-token.txt` على السيرفر.

---

## 10. المثبِّت معطَّل بالفعل (Already Installed)

**الأعراض:** فتح `install.php` يُعيد **404 Not Found**.

**السبب:** `storage/app/installed.lock` موجود — التثبيت اكتمل.

**إذا كنت تريد إعادة التثبيت من الصفر:**

> ⚠️ تحذير: هذا سيُعيد كتابة `.env` وتشغيل المايجريشن والسيدرز مجدداً.

1. احذف `storage/app/installed.lock`.
2. أعد توليد التوكن: `php generate-installer-token.php`.
3. أعد رفع `install.php` إلى `public/`.
4. افتح `install.php` مجدداً.

---

## 11. شاشة بيضاء بعد الرفع

**الأعراض:** صفحة بيضاء تماماً بدون أي رسالة خطأ.

**الأسباب:**

- `APP_DEBUG=false` يُخفي الأخطاء (وهو صحيح في الإنتاج).
- خطأ في `.env` أو كود PHP.

**التشخيص:**

1. افتح `storage/logs/laravel.log`.
2. اقرأ آخر رسالة خطأ.

إذا لم يكن `laravel.log` موجوداً:

```bash
chmod -R 777 storage/logs
```

---

## 12. ملفات CSS/JS لا تظهر

**الأعراض:** الصفحات تظهر بدون تنسيق (بدون CSS).

**الأسباب والحلول:**

| السبب | الحل |
|---|---|
| مجلد `public/build/` لم يُرفع | شغِّل `npm run build` ثم ارفع `public/build/` |
| `APP_URL` خاطئ في `.env` | تأكد أن `APP_URL=https://yourdomain.com` بدون `/` في النهاية |
| HTTPS/HTTP mismatch | تأكد من تطابق البروتوكول في `APP_URL` والرابط الفعلي |
| Vite manifest مفقود | ارفع `public/build/manifest.json` |

---

## 13. المستخدم المدير لم يُنشأ بعد التثبيت

**الأعراض:** لا يوجد حساب للدخول إلى التطبيق بعد التثبيت.

**السبب:** إما أن الـ Seeder لم يُشغَّل أو أنه لا يحتوي على مستخدم افتراضي.

**الحل:**

```bash
# عبر SSH
php artisan db:seed --force

# أو تشغيل seeder محدد
php artisan db:seed --class=UserSeeder --force
```

إذا لم يكن SSH متاحاً، أنشئ المستخدم يدوياً عبر phpMyAdmin أو أي أداة إدارة قواعدة بيانات.

---

## جدول سريع للأخطاء الشائعة

| العرَض | الحل السريع |
|---|---|
| 500 على كل الصفحات | راجع `storage/logs/laravel.log` |
| 403 على `install.php` | ارفع `installer-token.txt` |
| 404 على `install.php` | التثبيت مكتمل أو احذف `installed.lock` |
| قاعدة البيانات لا تتصل | راجع Host/Port/User/Password |
| `.env` لم يُكتَب | تحقق من صلاحيات الكتابة |
| لا CSS ولا JS | ارفع `public/build/` أو اضبط `APP_URL` |
| شاشة بيضاء | `storage/logs/laravel.log` يحتوي السبب |
| `APP_KEY` خطأ | `php artisan key:generate --force` |
| storage لا يعمل | `php artisan storage:link` عبر SSH |
| المايجريشن فشلت | تحقق من صلاحيات MySQL |

---

*السابق: [05 — دليل النشر](./05-deployment-guide-ar.md) | العودة إلى [الفهرس](./README.md)*
