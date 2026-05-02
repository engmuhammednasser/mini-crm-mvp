# 01 — معمارية نظام التثبيت

---

## نظرة عامة على المعمارية

يتكون نظام التثبيت من أربعة مكونات رئيسية تعمل معاً:

```
┌─────────────────────────────────────────────────────────┐
│                     المتصفح (Browser)                    │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│              public/install.php                         │
│  (نقطة الدخول — خارج إطار Laravel تماماً)               │
│  • يتحقق من installed.lock    → 404 إذا موجود           │
│  • يتحقق من installer-token.txt → 403 إذا غائب          │
│  • يُعيد التوجيه إلى /installer/{token}                 │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│           Laravel Router (routes/web.php)               │
│  GET  /installer/{token} → InstallerController@showForm │
│  POST /installer/{token} → InstallerController@runInstall│
│  (قيد الـ token: [a-f0-9]{64})                          │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│        InstallerController                              │
│  • guardNotInstalled() → abort(404) إذا installed.lock  │
│  • guardToken()        → abort(403) إذا token خاطئ      │
│  • showForm()          → عرض النموذج                   │
│  • runInstall()        → تنفيذ التثبيت الكامل          │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│         ملفات النظام (storage/app/)                     │
│  installer-token.txt   → التوكن الآمن                   │
│  installed.lock        → قفل يمنع إعادة التثبيت         │
└─────────────────────────────────────────────────────────┘
```

---

## 1. كيف يعمل `public/install.php`

هذا الملف هو **نقطة الدخول الأولى** للمثبِّت. يعمل خارج إطار Laravel تماماً كـ PHP خالص، مما يجعله يعمل حتى قبل اكتمال إعداد Laravel.

### تسلسل عمله:

```
فتح /install.php
        │
        ├─ هل يوجد storage/app/installed.lock ؟
        │         نعم → إرجاع HTTP 404 والتوقف
        │         لا  ↓
        │
        ├─ هل يوجد storage/app/installer-token.txt ؟
        │         لا  → إرجاع HTTP 403 والتوقف
        │         نعم ↓
        │
        ├─ هل محتوى التوكن فارغ؟
        │         نعم → إرجاع HTTP 403 والتوقف
        │         لا  ↓
        │
        └─ إعادة توجيه (302) إلى /installer/{token}
```

### الفائدة من هذا الملف:

- يخفي التوكن عن المستخدم النهائي (يُعيد التوجيه تلقائياً).
- يعمل كحارس أول قبل أي معالجة من Laravel.
- يُدير كشف HTTPS ويبني الـ URL الصحيح.

---

## 2. كيف تعمل مسارات `/installer/{token}`

في `routes/web.php`، تم تسجيل مسارَين خارج أي Middleware Group:

```php
Route::get('/installer/{token}', [InstallerController::class, 'showForm'])
    ->name('installer.show')
    ->where('token', '[a-f0-9]{64}');

Route::post('/installer/{token}', [InstallerController::class, 'runInstall'])
    ->name('installer.run')
    ->where('token', '[a-f0-9]{64}');
```

### نقاط التصميم المهمة:

| النقطة | التفصيل |
|---|---|
| خارج Middleware | المسارات لا تمر بـ `auth` أو أي Middleware آخر — الأمان يعتمد على التوكن |
| قيد الـ `where` | التوكن يجب أن يكون 64 حرفاً hex تحديداً — أي طلب بصيغة مختلفة يُعطي 404 تلقائياً |
| اسم المسار | `installer.show` و`installer.run` — يُستخدمان في الـ Blade لإنشاء action النموذج |

---

## 3. كيف يعمل `InstallerController`

### الدوال الرئيسية:

#### `guardNotInstalled()`
```
if (file_exists(installed.lock)) → abort(404)
```
تُستدعى في بداية كل طلب (GET وPOST). تمنع أي وصول بعد اكتمال التثبيت.

#### `guardToken(string $token)`
```
if (!file_exists(installer-token.txt)) → abort(403)
$expected = trim(file_get_contents(installer-token.txt))
if (!hash_equals($expected, $token)) → abort(403)
```
تقرأ التوكن المحفوظ وتقارنه بالتوكن الوارد في الـ URL بطريقة آمنة تمنع هجمات Timing Attack.

#### `showForm(Request $request, string $token)`
- يستدعي الحارسَين أعلاه.
- يُحدد `$appUrl` تلقائياً من `$request->getSchemeAndHttpHost()`.
- يُعيد view: `installer.setup` مع متغيري `$token` و`$appUrl`.

#### `runInstall(Request $request, string $token)`
التدفق التفصيلي:

```
1. guardNotInstalled() + guardToken()
2. التحقق من المدخلات (Validation)
3. اختبار اتصال قاعدة البيانات (PDO مع timeout 5 ثوانٍ)
4. كتابة ملف .env
5. config:clear
6. key:generate --force
7. migrate --force
8. db:seed --force
9. storage:link (غير حرجة — تُسجَّل في log عند الفشل وتكمل)
10. optimize
11. إنشاء installed.lock
12. حذف installer-token.txt
13. حذف public/install.php (best-effort)
14. عرض installer.success
```

---

## 4. كيف يُكتَب ملف `.env`

الدالة `writeEnv()` في InstallerController تعمل كالتالي:

```
1. تقرأ .env.example كقالب أساسي
2. تُعرِّف قائمة بالمفاتيح التي ستُعدَّل:
   - APP_NAME    = القيمة من النموذج (مع اقتباس إذا لزم)
   - APP_ENV     = production  (ثابت)
   - APP_DEBUG   = false       (ثابت)
   - APP_URL     = من النموذج
   - DB_CONNECTION = mysql     (ثابت)
   - DB_HOST     = من النموذج
   - DB_PORT     = من النموذج
   - DB_DATABASE = من النموذج
   - DB_USERNAME = من النموذج
   - DB_PASSWORD = من النموذج (مع اقتباس إذا لزم)
3. لكل مفتاح:
   - إذا وُجد في القالب (حتى لو مُعلَّق بـ #) → يُحدَّث
   - إذا لم يُوجَد → يُضاف في النهاية
4. تُضبط LOG_LEVEL=error
5. يُكتَب الملف الناتج إلى .env
```

### التعامل مع القيم الخاصة (`quoteEnvValue`):

إذا احتوت القيمة على مسافات أو علامات اقتباس أو `#`، تُلَفُّ بعلامات اقتباس مزدوجة وتُهرَّب الأحرف الخاصة داخلها.

---

## 5. كيف تُنفَّذ أوامر Artisan

جميع الأوامر تُنفَّذ عبر `Artisan::call()` — واجهة PHP البرمجية الرسمية للـ Artisan — ولا يُستخدَم `shell_exec` أو `exec` إطلاقاً.

| الأمر | الطريقة | ملاحظة |
|---|---|---|
| `config:clear` | `Artisan::call('config:clear')` | يمسح الـ cache قبل الباقي |
| `key:generate --force` | `Artisan::call('key:generate', ['--force' => true])` | يُضبط APP_KEY في .env |
| `migrate --force` | `Artisan::call('migrate', ['--force' => true])` | ينشئ جداول قاعدة البيانات |
| `db:seed --force` | `Artisan::call('db:seed', ['--force' => true])` | يُضيف البيانات الأولية |
| `storage:link` | `Artisan::call('storage:link')` | **غير حرجة** — الفشل لا يوقف التثبيت |
| `optimize` | `Artisan::call('optimize')` | يُحسِّن الأداء (cache + autoload) |

### معالجة الأخطاء:

- الأوامر من `config:clear` حتى `optimize` (عدا `storage:link`) **حرجة**: فشل أي منها يوقف التثبيت ويُعيد رسالة خطأ.
- `storage:link` **غير حرجة**: فشلها يُسجَّل في `laravel.log` ويكمل التثبيت.

---

## مخطط تدفق بصري مبسَّط

```
Browser → /install.php
              │
        ┌─────┴─────┐
        │  Guards:  │
        │  lock?    │── نعم ──→ 404
        │  token?   │── لا  ──→ 403
        └─────┬─────┘
              │ Redirect 302
              ▼
     /installer/{token}  GET
              │
        ┌─────┴──────────────────────────────┐
        │  InstallerController::showForm()   │
        │  • guardNotInstalled()             │
        │  • guardToken()                    │
        │  • $appUrl = getSchemeAndHttpHost  │
        │  • return view('installer.setup')  │
        └─────┬──────────────────────────────┘
              │ User fills form & submits
              ▼
     /installer/{token}  POST
              │
        ┌─────┴──────────────────────────────────────────┐
        │  InstallerController::runInstall()             │
        │                                                │
        │  [1] Validate inputs                           │
        │  [2] PDO connection test (timeout: 5s)         │
        │  [3] writeEnv() → .env                         │
        │  [4] Artisan::call('config:clear')             │
        │  [5] Artisan::call('key:generate', --force)    │
        │  [6] Artisan::call('migrate', --force)         │
        │  [7] Artisan::call('db:seed', --force)         │
        │  [8] Artisan::call('storage:link')  [non-fatal]│
        │  [9] Artisan::call('optimize')                 │
        │  [10] create installed.lock                    │
        │  [11] delete installer-token.txt               │
        │  [12] delete public/install.php                │
        └─────┬──────────────────────────────────────────┘
              │
              ▼
     view('installer.success') ✅
```

---

*السابق: [00 — النظرة العامة](./00-overview-ar.md) | التالي: [02 — تدفق التثبيت](./02-installer-flow-ar.md)*
