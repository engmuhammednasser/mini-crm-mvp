<?php
/**
 * generate-installer-token.php
 *
 * Run this script ONCE before deploying to generate a secure one-time
 * installer token.
 *
 * Usage:
 *   php generate-installer-token.php
 *   php generate-installer-token.php --base-url=https://yourdomain.com
 *
 * The token is saved to: storage/app/installer-token.txt
 */

declare(strict_types=1);

// ── Parse arguments ──────────────────────────────────────────────────────────
$baseUrl = 'https://yourdomain.com';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) {
        $baseUrl = rtrim(substr($arg, strlen('--base-url=')), '/');
    }
}

// ── Paths ────────────────────────────────────────────────────────────────────
$scriptDir = __DIR__;
$storageDir = $scriptDir . '/storage/app';
$tokenFile  = $storageDir . '/installer-token.txt';
$lockFile   = $storageDir . '/installed.lock';

// ── Guard: already installed? ────────────────────────────────────────────────
if (file_exists($lockFile)) {
    echo "[ERROR] The application is already installed (installed.lock exists)." . PHP_EOL;
    echo "        Delete storage/app/installed.lock only if you are re-installing." . PHP_EOL;
    exit(1);
}

// ── Ensure storage/app directory exists ─────────────────────────────────────
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// ── Generate token ───────────────────────────────────────────────────────────
$token = bin2hex(random_bytes(32)); // 64 hex chars

// ── Save token ───────────────────────────────────────────────────────────────
if (file_put_contents($tokenFile, $token) === false) {
    echo "[ERROR] Could not write to {$tokenFile}. Check directory permissions." . PHP_EOL;
    exit(1);
}

// ── Output ───────────────────────────────────────────────────────────────────
$separator = str_repeat('─', 70);

echo PHP_EOL;
echo $separator . PHP_EOL;
echo "  ✅  Installer token generated successfully!" . PHP_EOL;
echo $separator . PHP_EOL;
echo PHP_EOL;
echo "  Token saved to : storage/app/installer-token.txt" . PHP_EOL;
echo "  Token          : " . $token . PHP_EOL;
echo PHP_EOL;
echo "  Installer URLs :" . PHP_EOL;
echo "    Via install.php  →  {$baseUrl}/install.php" . PHP_EOL;
echo "    Direct URL       →  {$baseUrl}/installer/{$token}" . PHP_EOL;
echo PHP_EOL;
echo "  ⚠️  Keep this token private. It grants full setup access." . PHP_EOL;
echo "  ⚠️  This token is single-use and will be deleted after installation." . PHP_EOL;
echo PHP_EOL;
echo $separator . PHP_EOL;
echo PHP_EOL;
