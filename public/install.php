<?php
/**
 * One-Time Web Installer Entry Point
 *
 * This file handles the very first step of installation.
 * It validates guard files and redirects to the secure installer route.
 *
 * SECURITY: This file should be deleted automatically after installation.
 */

// Resolve the Laravel base path (one level up from /public)
$basePath = dirname(__DIR__);

$lockFile  = $basePath . '/storage/app/installed.lock';
$tokenFile = $basePath . '/storage/app/installer-token.txt';

// If already installed → 404
if (file_exists($lockFile)) {
    http_response_code(404);
    exit('Not Found.');
}

// If token file is missing → 403
if (!file_exists($tokenFile)) {
    http_response_code(403);
    exit('Forbidden. Installer token not found.');
}

$token = trim(file_get_contents($tokenFile));

if (empty($token)) {
    http_response_code(403);
    exit('Forbidden. Installer token is empty.');
}

// Build the redirect URL using current request info
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Determine the base URL (strip /install.php from the script path)
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// If public/ is web root, $scriptDir will be '' — no sub-path needed
$basePath_url = $scriptDir === '' || $scriptDir === '/' ? '' : $scriptDir;

// Remove /public from basePath_url if present (for sub-directory deployments)
$installerUrl = $scheme . '://' . $host . $basePath_url . '/installer/' . urlencode($token);

header('Location: ' . $installerUrl, true, 302);
exit();
