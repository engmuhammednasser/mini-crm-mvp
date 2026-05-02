# 🚀 Web Installer Guide

A secure, one-time web installer for production deployment of this Laravel application.

---

## Table of Contents

1. [How to Build a Release](#1-how-to-build-a-release)
2. [How to Upload to the Server](#2-how-to-upload-to-the-server)
3. [How to Point Your Domain to /public](#3-how-to-point-your-domain-to-public)
4. [How to Create the Database Manually](#4-how-to-create-the-database-manually)
5. [How to Generate the Installer Token](#5-how-to-generate-the-installer-token)
6. [How to Open the Installer](#6-how-to-open-the-installer)
7. [How to Verify the Installer Is Disabled](#7-how-to-verify-the-installer-is-disabled)
8. [Security Architecture](#8-security-architecture)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. How to Build a Release

Run these commands in your local development environment:

```bash
# 1. Install PHP dependencies (production mode — no dev packages)
composer install --no-dev --optimize-autoloader

# 2. Build frontend assets
npm ci
npm run build

# 3. Generate the installer token
php generate-installer-token.php --base-url=https://yourdomain.com
```

> **Note:** The token will be printed to your terminal and saved to `storage/app/installer-token.txt`.

Files to include in your release package / ZIP:

```
├── app/
├── bootstrap/
├── config/
├── database/
├── lang/
├── public/             ← includes install.php and compiled assets
├── resources/
├── routes/
├── storage/
│   └── app/
│       └── installer-token.txt   ← MUST be uploaded
├── vendor/
├── .env.example        ← required by the installer as a template
├── artisan
└── composer.json
```

> ⚠️ **Do NOT upload `.env`** — the installer will create it for you.

---

## 2. How to Upload to the Server

### Option A — FTP / SFTP

1. Connect to your server via FTP (FileZilla, WinSCP, etc.)
2. Upload all files from the project root to your hosting directory (e.g. `public_html/` or `~/myapp/`)
3. Make sure **all files including hidden ones** are uploaded (`.env.example`, `.htaccess`, etc.)

### Option B — SSH + Git

```bash
ssh user@yourserver.com
cd /var/www/
git clone https://github.com/yourrepo/mini-crm.git myapp
cd myapp
composer install --no-dev --optimize-autoloader
php generate-installer-token.php --base-url=https://yourdomain.com
```

### Permissions (Linux servers)

```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env.example
```

---

## 3. How to Point Your Domain to /public

The web root **must** point to the `/public` directory, not the project root.

### cPanel (Shared Hosting)

1. Log in to cPanel → **Domains** → **Addon Domains** (or Subdomains)
2. Set the **Document Root** to: `public_html/myapp/public`
3. Save changes.

Alternatively, if you upload inside `public_html/`:

```
public_html/
├── index.php      ← already there if you pointed root correctly
├── install.php
├── .htaccess
└── build/
```

### VPS / Dedicated (Nginx)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/myapp/public;

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

### VPS / Dedicated (Apache)

The project ships with `public/.htaccess` that handles URL rewriting automatically.  
Make sure `AllowOverride All` is set for your `<Directory>` block and `mod_rewrite` is enabled.

---

## 4. How to Create the Database Manually

The installer does **not** create the database — you must create it before running the installer.

### cPanel → MySQL Databases

1. Go to **cPanel → MySQL Databases**
2. Create a new database (e.g. `myuser_crm`)
3. Create a database user (e.g. `myuser_dbuser`) with a strong password
4. **Add the user to the database** with **All Privileges**
5. Note down:
   - Host: usually `127.0.0.1` or `localhost`
   - Port: `3306`
   - Database name
   - Username
   - Password

### SSH / phpMyAdmin

```sql
CREATE DATABASE mini_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON mini_crm.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 5. How to Generate the Installer Token

Run **once** before or after uploading:

```bash
php generate-installer-token.php --base-url=https://yourdomain.com
```

Output example:

```
──────────────────────────────────────────────────────────────────────
  ✅  Installer token generated successfully!
──────────────────────────────────────────────────────────────────────

  Token saved to : storage/app/installer-token.txt
  Token          : a3f8c2e1d9b4...

  Installer URLs :
    Via install.php  →  https://yourdomain.com/install.php
    Direct URL       →  https://yourdomain.com/installer/a3f8c2e1d9b4...

  ⚠️  Keep this token private. It grants full setup access.
  ⚠️  This token is single-use and will be deleted after installation.
```

> If you ran this locally before uploading, make sure you upload `storage/app/installer-token.txt` to the server.

---

## 6. How to Open the Installer

1. Open your browser and navigate to:

   ```
   https://yourdomain.com/install.php
   ```

   This will automatically redirect you to:
   ```
   https://yourdomain.com/installer/{token}
   ```

2. Fill in the form:
   - **Application Name** — your app's display name
   - **Application URL** — pre-filled from your domain (verify it's correct)
   - **DB Host** — usually `127.0.0.1`
   - **DB Port** — `3306`
   - **Database Name** — the database you created in step 4
   - **DB Username / Password** — the credentials from step 4

3. Click **🚀 Run Installation**

4. The installer will:
   - Test the database connection
   - Write `.env` (production config)
   - Generate the `APP_KEY`
   - Run all migrations
   - Seed the database
   - Create the storage symlink
   - Optimize the application
   - Lock itself from future access

---

## 7. How to Verify the Installer Is Disabled

After a successful installation, confirm the following:

### Check 1 — `installed.lock` exists

```bash
ls -la storage/app/installed.lock
# Should show the file with a timestamp inside
```

### Check 2 — Token file is gone

```bash
ls storage/app/installer-token.txt
# Should return: No such file or directory
```

### Check 3 — `install.php` is gone

```bash
ls public/install.php
# Should return: No such file or directory
```

### Check 4 — Browser test

Navigate to `https://yourdomain.com/install.php` — you should get a `404 Not Found` response.  
Navigate to `https://yourdomain.com/installer/anytoken` — you should also get `404`.

### Check 5 — Manual cleanup (if needed)

If `public/install.php` was not auto-deleted (e.g. permission issue):

```bash
rm public/install.php
```

---

## 8. Security Architecture

| Threat | Mitigation |
|---|---|
| Re-running the installer | `installed.lock` blocks all installer routes with `abort(404)` |
| Token brute-force | 64-char hex token (256-bit entropy) + `hash_equals()` to prevent timing attacks |
| Token in URL logs | Token is deleted from disk after successful install |
| `.env` exposure | Never served via web root; installer never echoes it |
| Shell injection | No `shell_exec`/`exec` — all commands via `Artisan::call()` |
| Unauthenticated access | Token constraint on route (`[a-f0-9]{64}`) rejects malformed tokens at router level |

---

## 9. Troubleshooting

| Problem | Solution |
|---|---|
| `403 Forbidden` on `install.php` | `storage/app/installer-token.txt` is missing — regenerate it |
| `404 Not Found` on `install.php` | `storage/app/installed.lock` exists — app is already installed |
| Database connection failed | Verify host/port/credentials; ensure DB user has privileges |
| `.env` write failed | Check `chmod 664 .env.example` and server write permissions |
| Migrations failed | Check DB user has `CREATE TABLE` privileges; check Laravel logs |
| `storage:link` failed | Manually run `php artisan storage:link` via SSH; non-fatal |
| Token route returns 404 | Token regex mismatch — ensure token is exactly 64 hex chars |
| App shows errors after install | Run `php artisan config:cache` and `php artisan optimize` via SSH |
