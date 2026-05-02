<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallerController extends Controller
{
    // --------------------------------------------------------------------------
    // Paths
    // --------------------------------------------------------------------------

    private function lockFile(): string
    {
        return storage_path('app/installed.lock');
    }

    private function tokenFile(): string
    {
        return storage_path('app/installer-token.txt');
    }

    private function envFile(): string
    {
        return base_path('.env');
    }

    private function envExampleFile(): string
    {
        return base_path('.env.example');
    }

    // --------------------------------------------------------------------------
    // Guards
    // --------------------------------------------------------------------------

    /**
     * Abort with 404 if the installation lock exists.
     */
    private function guardNotInstalled(): void
    {
        if (file_exists($this->lockFile())) {
            abort(404);
        }
    }

    /**
     * Abort with 403 if the token is invalid or the token file is missing.
     */
    private function guardToken(string $token): void
    {
        if (!file_exists($this->tokenFile())) {
            abort(403, 'Installer token file not found.');
        }

        $expected = trim(file_get_contents($this->tokenFile()));

        if (!hash_equals($expected, $token)) {
            abort(403, 'Invalid installer token.');
        }
    }

    // --------------------------------------------------------------------------
    // GET /installer/{token}
    // --------------------------------------------------------------------------

    public function showForm(Request $request, string $token)
    {
        $this->guardNotInstalled();
        $this->guardToken($token);

        // Pre-fill APP_URL from the current request domain
        $appUrl = $request->getSchemeAndHttpHost();

        return view('installer.setup', compact('token', 'appUrl'));
    }

    // --------------------------------------------------------------------------
    // POST /installer/{token}
    // --------------------------------------------------------------------------

    public function runInstall(Request $request, string $token)
    {
        $this->guardNotInstalled();
        $this->guardToken($token);

        // ── Validate input ───────────────────────────────────────────────────
        $validated = $request->validate([
            'app_name'    => ['required', 'string', 'max:100'],
            'app_url'     => ['required', 'url', 'max:255'],
            'db_host'     => ['required', 'string', 'max:255'],
            'db_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        $dbPassword = $validated['db_password'] ?? '';

        // ── Test DB connection ───────────────────────────────────────────────
        try {
            $pdo = new \PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $validated['db_host'],
                    $validated['db_port'],
                    $validated['db_database']
                ),
                $validated['db_username'],
                $dbPassword,
                [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]
            );
            unset($pdo); // close connection
        } catch (\PDOException $e) {
            return back()
                ->withInput()
                ->withErrors(['db_connection' => 'Database connection failed: ' . $e->getMessage()]);
        }

        // ── Write .env ───────────────────────────────────────────────────────
        try {
            $this->writeEnv($validated, $dbPassword);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['env_write' => 'Failed to write .env file: ' . $e->getMessage()]);
        }

        // ── Run Artisan commands ─────────────────────────────────────────────
        $errors = [];

        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            $errors[] = 'config:clear — ' . $e->getMessage();
        }

        try {
            Artisan::call('key:generate', ['--force' => true]);
        } catch (\Throwable $e) {
            $errors[] = 'key:generate — ' . $e->getMessage();
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            $errors[] = 'migrate — ' . $e->getMessage();
        }

        try {
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            $errors[] = 'db:seed — ' . $e->getMessage();
        }

        // storage:link can fail on shared hosts (e.g. symlink already exists) — handle gracefully
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $e) {
            // Non-fatal: log but continue
            logger()->warning('Installer: storage:link failed — ' . $e->getMessage());
        }

        try {
            Artisan::call('optimize');
        } catch (\Throwable $e) {
            $errors[] = 'optimize — ' . $e->getMessage();
        }

        // If critical commands failed, bail
        if (!empty($errors)) {
            return back()
                ->withInput()
                ->withErrors(['artisan' => 'Some Artisan commands failed: ' . implode('; ', $errors)]);
        }

        // ── Finalize ─────────────────────────────────────────────────────────
        // 1. Create installed.lock
        file_put_contents($this->lockFile(), date('Y-m-d H:i:s') . PHP_EOL);

        // 2. Delete installer token
        if (file_exists($this->tokenFile())) {
            @unlink($this->tokenFile());
        }

        // 3. Delete public/install.php (best-effort)
        $installPhp = public_path('install.php');
        if (file_exists($installPhp)) {
            @unlink($installPhp);
        }

        return view('installer.success');
    }

    // --------------------------------------------------------------------------
    // .env Writer
    // --------------------------------------------------------------------------

    /**
     * Reads .env.example as the template and writes a production-ready .env.
     * Only modifies the keys we know about; all other lines are preserved.
     */
    private function writeEnv(array $data, string $dbPassword): void
    {
        $template = file_exists($this->envExampleFile())
            ? file_get_contents($this->envExampleFile())
            : '';

        $replacements = [
            'APP_NAME'    => $this->quoteEnvValue($data['app_name']),
            'APP_ENV'     => 'production',
            'APP_DEBUG'   => 'false',
            'APP_URL'     => $data['app_url'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'     => $data['db_host'],
            'DB_PORT'     => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $this->quoteEnvValue($dbPassword),
        ];

        // For each key, update or uncomment the line in the template
        foreach ($replacements as $key => $value) {
            // Match: optional leading #, optional spaces, KEY=anything
            $pattern = '/^#?\s*' . preg_quote($key, '/') . '\s*=.*$/m';
            $replacement = $key . '=' . $value;

            if (preg_match($pattern, $template)) {
                $template = preg_replace($pattern, $replacement, $template);
            } else {
                // Key not found in template — append it
                $template .= PHP_EOL . $replacement;
            }
        }

        // Also update LOG_LEVEL to error in production
        $template = preg_replace('/^LOG_LEVEL\s*=.*$/m', 'LOG_LEVEL=error', $template);

        if (file_put_contents($this->envFile(), $template) === false) {
            throw new \RuntimeException('Could not write to .env file. Check permissions.');
        }
    }

    /**
     * Wrap a value in double quotes if it contains spaces or special chars,
     * and escape any embedded double quotes.
     */
    private function quoteEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        // If it contains spaces, quotes, or special shell chars, wrap in quotes
        if (preg_match('/[\s"\'\\\\#]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }
}
