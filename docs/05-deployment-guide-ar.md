# 05 — دليل النشر الكامل

---

## المتطلبات الأساسية

تأكد من توفر الآتي قبل البدء:

| المتطلب | الحد الأدنى |
|---|---|
| PHP | 8.2 أو أحدث |
| MySQL | 5.7 أو أحدث |
| إضافة PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml` |
| Composer | متاح على الجهاز المحلي |
| مساحة الاستضافة | يدعم PHP وMySQL |

---

## المرحلة الأولى — التحضير على الجهاز المحلي

### الخطوة 1 — تثبيت حزم PHP بدون حزم التطوير

```bash
composer install --no-dev --optimize-autoloader
```

> هذا يُقلِّل حجم مجلد `vendor/` ويُحسِّن الأداء في الإنتاج.

### الخطوة 2 — بناء ملفات الـ Frontend

```bash
npm ci
npm run build
```

بعد هذا الأمر يُنشأ مجلد `public/build/` يحتوي على ملفات CSS وJS المُجمَّعة.

### الخطوة 3 — توليد توكن التثبيت

```bash
php generate-installer-token.php --base-url=https://yourdomain.com
```

**استبدل** `https://yourdomain.com` بدومينك الفعلي.

**الناتج المتوقع:**

```
──────────────────────────────────────────────────────────────────────
  ✅  Installer token generated successfully!
──────────────────────────────────────────────────────────────────────

  Token saved to : storage/app/installer-token.txt

  Installer URLs :
    Via install.php  →  https://yourdomain.com/install.php
    Direct URL       →  https://yourdomain.com/installer/[token]
```

> ⚠️ احفظ رابط `install.php` في مكان آمن. ستحتاجه بعد الرفع.

---

## المرحلة الثانية — رفع الملفات على السيرفر

### الخطوة 4 — ما يجب رفعه

```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ lang/
✅ public/            (بما فيها install.php والـ build/)
✅ resources/
✅ routes/
✅ storage/           (بما فيها installer-token.txt)
✅ vendor/
✅ .env.example
✅ artisan
✅ composer.json
```

### ما لا يُرفع:

```
❌ .env               (سيُنشئه المثبِّت)
❌ .git/              (مجلد Git)
❌ node_modules/      (غير مطلوب في الإنتاج)
```

### الخطوة 5 — طريقة الرفع عبر FTP/SFTP

1. افتح برنامج FTP (FileZilla أو WinSCP).
2. اتصل بالسيرفر بالبيانات التي أعطاك إياها مزود الاستضافة.
3. ارفع جميع الملفات إلى المجلد المحدد على السيرفر.
4. تأكد من رفع **الملفات المخفية** (`htaccess.`, `.env.example`).

### الخطوة 6 — ضبط صلاحيات المجلدات (على Linux/cPanel)

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env.example
```

---

## المرحلة الثالثة — إعداد السيرفر

### الخطوة 7 — توجيه الدومين إلى `/public`

#### على cPanel:

1. سجِّل الدخول إلى cPanel.
2. افتح **Domains** أو **Addon Domains**.
3. في حقل **Document Root** اكتب مسار `public/` داخل مشروعك:
   ```
   public_html/mini-crm/public
   ```
4. احفظ التغييرات.

#### على Nginx:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/mini-crm/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### على Apache:

المشروع يأتي بملف `public/.htaccess` جاهز. تأكد فقط من:
- تفعيل `mod_rewrite`.
- ضبط `AllowOverride All` لمجلد `/public`.

---

### الخطوة 8 — إنشاء قاعدة البيانات من cPanel

1. افتح cPanel → **MySQL Databases**.
2. أنشئ قاعدة بيانات جديدة (مثال: `username_crm`).
3. أنشئ مستخدماً جديداً بكلمة مرور قوية.
4. أضف المستخدم إلى قاعدة البيانات بصلاحية **All Privileges**.
5. دوِّن البيانات التالية:
   - **DB Host:** عادةً `127.0.0.1`
   - **DB Port:** `3306`
   - **DB Name:** اسم القاعدة التي أنشأتها
   - **DB User:** اسم المستخدم
   - **DB Password:** كلمة المرور

---

## المرحلة الرابعة — تشغيل المثبِّت

### الخطوة 9 — فتح المثبِّت في المتصفح

```
https://yourdomain.com/install.php
```

سيُعيد التوجيه تلقائياً إلى صفحة الإعداد.

### الخطوة 10 — ملء النموذج

أدخل البيانات في النموذج:

| الحقل | ما تُدخل |
|---|---|
| Application Name | اسم تطبيقك (مثال: `Mini CRM`) |
| Application URL | `https://yourdomain.com` |
| DB Host | `127.0.0.1` (أو ما أعطاك إياه مزود الاستضافة) |
| DB Port | `3306` |
| Database Name | اسم القاعدة من الخطوة 8 |
| DB Username | اسم المستخدم من الخطوة 8 |
| DB Password | كلمة المرور من الخطوة 8 |

### الخطوة 11 — تشغيل التثبيت

انقر على زر **Run Installation** وانتظر.

الأوامر التي تُنفَّذ تلقائياً:

```
config:clear       → مسح الـ cache
key:generate       → توليد APP_KEY
migrate            → إنشاء جداول قاعدة البيانات
db:seed            → تعبئة البيانات الأولية
storage:link       → ربط storage العام
optimize           → تحسين الأداء
```

عند النجاح تظهر صفحة **Installation Complete** ✅.

---

## المرحلة الخامسة — التحقق بعد التثبيت

### الخطوة 12 — التحقق من عمل الموقع

افتح المتصفح على:

```
https://yourdomain.com
```

يجب أن تظهر صفحة تسجيل الدخول أو الداشبورد.

### الخطوة 13 — التحقق من تعطيل المثبِّت

```
https://yourdomain.com/install.php
```

يجب أن يُعيد **404 Not Found**.

```
https://yourdomain.com/installer/anytoken1234
```

يجب أن يُعيد **404 Not Found**.

### الخطوة 14 — التحقق من `installed.lock`

عبر FTP أو File Manager في cPanel، تأكد من وجود:

```
storage/app/installed.lock
```

وغياب:

```
storage/app/installer-token.txt   ← يجب أن يكون محذوفاً
public/install.php                ← يجب أن يكون محذوفاً
```

إذا كان `install.php` لا يزال موجوداً، احذفه يدوياً من File Manager.

### الخطوة 15 — التحقق من ملف `.env`

عبر FTP، افتح `.env` وتحقق من:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your_generated_app_key
DB_CONNECTION=mysql
```

> ⚠️ لا تشارك محتوى هذا الملف مع أي أحد.

### الخطوة 16 — التحقق من الـ Storage Link

تصفح:

```
https://yourdomain.com/storage/
```

إذا ظهرت صفحة (حتى لو فارغة) بدلاً من 404، فالرابط يعمل.

إذا لم يعمل `storage:link` تلقائياً (وهو أمر شائع في بعض الاستضافات)، نفِّذ يدوياً عبر SSH:

```bash
php artisan storage:link
```

أو أنشئ الـ symlink يدوياً:

```bash
ln -s ../storage/app/public public/storage
```

### الخطوة 17 — التحقق من الـ Cache والتحسين

إذا ظهرت مشاكل في الـ Config أو الـ Routes، نفِّذ:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ملخص سريع

```
 1  composer install --no-dev
 2  npm ci && npm run build
 3  php generate-installer-token.php --base-url=https://yourdomain.com
 4  رفع الملفات على السيرفر
 5  توجيه الدومين إلى /public
 6  إنشاء قاعدة البيانات من cPanel
 7  فتح /install.php في المتصفح
 8  ملء النموذج والإرسال
 9  التحقق من عمل الموقع
10  التحقق من 404 على /install.php
11  التحقق من installed.lock
12  التحقق من .env
13  التحقق من storage link
```

---

*السابق: [04 — ملاحظات الأمان](./04-security-notes-ar.md) | التالي: [06 — استكشاف الأخطاء](./06-troubleshooting-ar.md)*
