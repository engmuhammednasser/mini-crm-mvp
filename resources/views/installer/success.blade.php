<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Complete</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #0f1117;
            --surface: #1a1d27;
            --border:  #2e3248;
            --accent:  #6c63ff;
            --success: #4caf82;
            --text:    #e8eaf6;
            --muted:   #8b90a0;
            --radius:  12px;
            --shadow:  0 20px 60px rgba(0,0,0,0.5);
            --font:    'Inter', system-ui, sans-serif;
        }

        html, body {
            font-family: var(--font);
            background: radial-gradient(ellipse at 50% 0%, rgba(76,175,130,0.15) 0%, transparent 55%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            max-width: 520px;
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 3rem 2.5rem;
            text-align: center;
        }

        .check-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(76,175,130,0.15);
            border: 2px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 1.5rem;
            animation: pop .5s cubic-bezier(.17,.67,.4,1.3);
            box-shadow: 0 0 30px rgba(76,175,130,0.3);
        }

        @keyframes pop {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        h1 {
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: .5rem;
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .checklist {
            text-align: left;
            background: rgba(76,175,130,0.06);
            border: 1px solid rgba(76,175,130,0.2);
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checklist li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--text);
        }

        .checklist li .icon { font-size: 1rem; flex-shrink: 0; }

        .notice {
            background: rgba(240,180,41,0.08);
            border: 1px solid rgba(240,180,41,0.3);
            border-radius: 8px;
            padding: 1rem 1.25rem;
            font-size: 0.82rem;
            color: #f0b429;
            margin-bottom: 2rem;
            text-align: left;
            line-height: 1.5;
        }

        .btn-go {
            display: inline-block;
            padding: 12px 32px;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: background .2s, transform .1s;
            box-shadow: 0 4px 20px rgba(108,99,255,0.3);
        }

        .btn-go:hover { background: #574fd6; transform: translateY(-1px); }

        .footer-note {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="card" role="main">
    <div class="check-circle" aria-hidden="true">✅</div>

    <h1>Installation Complete!</h1>
    <p class="subtitle">
        Your application has been successfully installed and configured for production.
        The installer has been permanently disabled.
    </p>

    <ul class="checklist" aria-label="Completed steps">
        <li><span class="icon">✔️</span> Environment file (.env) written</li>
        <li><span class="icon">✔️</span> Application key generated</li>
        <li><span class="icon">✔️</span> Database migrations ran</li>
        <li><span class="icon">✔️</span> Database seeded</li>
        <li><span class="icon">✔️</span> Storage symlink created</li>
        <li><span class="icon">✔️</span> Application optimized</li>
        <li><span class="icon">✔️</span> Installer token deleted</li>
        <li><span class="icon">✔️</span> <code>installed.lock</code> created</li>
    </ul>

    <div class="notice" role="note">
        ⚠️ <strong>Security:</strong> Ensure <code>public/install.php</code> has been deleted from your server.
        The installer route will now return 404 automatically.
    </div>

    <a href="{{ url('/') }}" class="btn-go">Go to Application →</a>

    <p class="footer-note">🔒 This installer can no longer be accessed.</p>
</div>

</body>
</html>
