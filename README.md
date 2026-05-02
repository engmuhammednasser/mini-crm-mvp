<div align="center">

<img src="faviconV2_004.png" alt="Mini CRM Logo" width="80"/>

# Mini CRM — نظام إدارة علاقات العملاء

**A lightweight, fast, and production-ready CRM MVP built with Laravel 11**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![MIT License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

</div>

---

## 📋 نظرة عامة | Overview

> **هذا المشروع هو النسخة الأولى (MVP) من نظام CRM خفيف وسريع وقابل للتطوير، مبني بـ Laravel 11.**
>
> This is the first version (MVP) of a lightweight, fast, and scalable CRM system built with Laravel 11.

تم تصميم هذا النظام لتلبية احتياجات الشركات الصغيرة والمتوسطة التي تحتاج إلى:

- ✅ إدارة بيانات العملاء والـ Leads بطريقة منظمة
- ✅ واجهة بسيطة وسريعة بدون تعقيد
- ✅ قابلية التشغيل على أي استضافة (Shared Hosting / VPS)
- ✅ أساس قوي للتطوير والتوسعة مستقبلاً

---

## ✨ المميزات | Features

| الميزة | الوصف |
|--------|--------|
| 🔐 **تسجيل الدخول** | Login آمن للأدمن فقط — بدون Register |
| 📊 **Dashboard** | إحصائيات سريعة: إجمالي العملاء، Leads جديدة، عملاء نشطون |
| 👥 **إدارة العملاء** | CRUD كامل — إضافة / عرض / تعديل / حذف |
| 📱 **Responsive Design** | يعمل على الموبايل والتابلت والديسكتوب |
| 🌐 **Web Installer** | نظام تثبيت عبر المتصفح للـ Shared Hosting |
| ⚡ **خفيف وسريع** | بدون packages غير ضرورية |
| 🔒 **أمان عالي** | حماية كاملة من CSRF, XSS, SQL Injection |

---

## 🗂️ هيكل المشروع | Project Structure

```
Mini CRM/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CustomerController.php    # CRUD العملاء
│   │   │   ├── DashboardController.php   # الإحصائيات
│   │   │   └── InstallerController.php   # نظام التثبيت
│   │   └── Middleware/
│   ├── Models/
│   │   ├── Customer.php
│   │   └── User.php
├── database/
│   ├── migrations/                       # هيكل قاعدة البيانات
│   └── seeders/                          # بيانات الأدمن الافتراضية
├── resources/
│   └── views/
│       ├── auth/                         # صفحة Login
│       ├── customers/                    # صفحات CRUD
│       ├── dashboard/                    # الـ Dashboard
│       └── installer/                   # واجهة التثبيت
├── routes/
│   └── web.php
├── docs/                                 # توثيق كامل بالعربي
├── public/
│   └── install.php                       # نقطة دخول المثبِّت
└── README.md
```

---

## 🚀 التشغيل المحلي | Local Installation

### المتطلبات | Requirements

- PHP **8.2** أو أعلى
- Composer
- Node.js & npm
- MySQL **8.0** أو أعلى

### خطوات التثبيت | Steps

```bash
# 1. Clone المشروع
git clone https://github.com/YOUR_USERNAME/mini-crm.git
cd mini-crm

# 2. تثبيت PHP dependencies
composer install

# 3. تثبيت Node dependencies وبناء الـ Assets
npm install && npm run build

# 4. إعداد ملف البيئة
cp .env.example .env
php artisan key:generate

# 5. إعداد قاعدة البيانات في .env
# DB_DATABASE=mini_crm
# DB_USERNAME=root
# DB_PASSWORD=

# 6. تشغيل المايجريشن والـ Seeders
php artisan migrate --seed

# 7. تشغيل السيرفر
php artisan serve
```

🌐 افتح المتصفح على: **`http://localhost:8000`**

---

## 🔑 بيانات الدخول الافتراضية | Default Credentials

| الحقل | القيمة |
|-------|---------|
| **Email** | `admin@admin.com` |
| **Password** | `password` |

> ⚠️ **مهم:** غيّر كلمة المرور فوراً بعد أول تسجيل دخول في بيئة الإنتاج.

---

## 🌍 النشر على الاستضافة | Shared Hosting Deployment

يحتوي المشروع على **Web Installer** متكامل يتيح النشر على الـ Shared Hosting بدون SSH:

```
1. ارفع ملفات المشروع على السيرفر
2. وجِّه الدومين إلى مجلد /public
3. افتح: https://yourdomain.com/install.php
4. أدخل بيانات قاعدة البيانات وأكمل التثبيت
```

📖 [تفاصيل كاملة في دليل النشر](docs/05-deployment-guide-ar.md) | [دليل نظام التثبيت](INSTALLER.md)

---

## 📚 التوثيق | Documentation

| الملف | المحتوى |
|-------|---------|
| [00 — نظرة عامة](docs/00-overview-ar.md) | ما هو نظام التثبيت ولماذا يوجد |
| [01 — المعمارية](docs/01-system-architecture-ar.md) | هيكل النظام والمكونات |
| [02 — تدفق التثبيت](docs/02-installer-flow-ar.md) | خطوات التثبيت بالتفصيل |
| [03 — الملفات المُنشأة](docs/03-files-created-ar.md) | قائمة الملفات المضافة |
| [04 — ملاحظات الأمان](docs/04-security-notes-ar.md) | آليات الحماية المُطبَّقة |
| [05 — دليل النشر](docs/05-deployment-guide-ar.md) | النشر على الإنتاج خطوة بخطوة |
| [06 — استكشاف الأخطاء](docs/06-troubleshooting-ar.md) | حل المشكلات الشائعة |

---

## 🛠️ التقنيات المستخدمة | Tech Stack

| التقنية | الاستخدام |
|---------|-----------|
| **[Laravel 11](https://laravel.com/)** | PHP Framework — Backend & API |
| **[Tailwind CSS 3](https://tailwindcss.com/)** | Utility-first CSS Styling |
| **[Alpine.js](https://alpinejs.dev/)** | Lightweight JS for interactivity |
| **[MySQL 8](https://mysql.com/)** | Relational Database |
| **[Vite](https://vitejs.dev/)** | Frontend build tool |

---

## 🗺️ خارطة التطوير | Roadmap

هذا المشروع هو **الخطوة الأولى (MVP)**. الإصدارات القادمة ستشمل:

- [ ] 📊 **Charts & Analytics** — رسوم بيانية للمبيعات والـ Leads
- [ ] 👥 **Roles & Permissions** — صلاحيات متعددة (Admin / Agent)
- [ ] 📧 **Email Notifications** — إشعارات تلقائية بالإيميل
- [ ] 📁 **Import/Export** — استيراد وتصدير CSV/Excel
- [ ] 🔗 **Third-party Integrations** — ربط مع WhatsApp / Email APIs
- [ ] 🏢 **Multi-tenancy** — دعم أكثر من مؤسسة

---

## 👨‍💻 عن المشروع | About

تم تطوير هذا المشروع كـ **MVP قابل للتطوير**، مع التركيز على:

- ✔️ **نظافة الكود** — يتبع معايير Laravel best practices
- ✔️ **الأمان** — حماية كاملة من ثغرات الويب الشائعة  
- ✔️ **قابلية التوسع** — هيكل معياري يسمح بإضافة ميزات بسهولة
- ✔️ **التوثيق** — توثيق كامل باللغة العربية

---

## 📄 الرخصة | License

هذا المشروع مرخَّص بموجب رخصة [MIT](https://opensource.org/licenses/MIT).

---

<div align="center">

**صُنع بـ ❤️ باستخدام Laravel**

⭐ إذا أعجبك المشروع، لا تنسَ إعطاءه نجمة على GitHub!

</div>
