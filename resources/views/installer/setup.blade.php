<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Installer</title>
    <meta name="description" content="One-time setup wizard for your Laravel application.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Reset & Tokens ─────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #0f1117;
            --surface:      #1a1d27;
            --surface-2:    #22263a;
            --border:       #2e3248;
            --accent:       #6c63ff;
            --accent-hover: #574fd6;
            --accent-glow:  rgba(108, 99, 255, 0.25);
            --text:         #e8eaf6;
            --text-muted:   #8b90a0;
            --success:      #4caf82;
            --danger:       #f05262;
            --danger-bg:    rgba(240, 82, 98, 0.12);
            --warning:      #f0b429;
            --radius:       12px;
            --radius-sm:    8px;
            --shadow:       0 20px 60px rgba(0,0,0,0.5);
            --font:         'Inter', system-ui, sans-serif;
        }

        html { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
            background: radial-gradient(ellipse at 20% 10%, rgba(108,99,255,0.12) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 90%, rgba(76,175,130,0.07) 0%, transparent 60%),
                        var(--bg);
        }

        /* ── Card ───────────────────────────────────────────────── */
        .card {
            width: 100%;
            max-width: 640px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #1e2240 0%, #252945 100%);
            border-bottom: 1px solid var(--border);
            padding: 2rem 2.5rem;
        }

        .card-header .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent-glow);
            border: 1px solid var(--accent);
            color: var(--accent);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 1rem;
        }

        .card-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
        }

        .card-header p {
            margin-top: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* ── Steps Indicator ────────────────────────────────────── */
        .steps {
            display: flex;
            gap: 0;
            padding: 1rem 2.5rem;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-muted);
            flex: 1;
            position: relative;
        }

        .step::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 60%;
            background: var(--border);
        }

        .step:last-child::after { display: none; }

        .step.active { color: var(--accent); }

        .step-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step.active .step-dot {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 0 12px var(--accent-glow);
        }

        /* ── Body ───────────────────────────────────────────────── */
        .card-body { padding: 2rem 2.5rem; }

        /* ── Alert ──────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: var(--danger-bg);
            border: 1px solid var(--danger);
            border-radius: var(--radius-sm);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            animation: shake .3s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-6px); }
            60%      { transform: translateX(6px); }
        }

        .alert-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }

        .alert-content { flex: 1; }

        .alert-title {
            font-weight: 600;
            color: var(--danger);
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .alert-body {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .alert-body ul { padding-left: 1rem; margin-top: 4px; }
        .alert-body ul li { margin-bottom: 2px; }

        /* ── Section heading ────────────────────────────────────── */
        .section-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Form ───────────────────────────────────────────────── */
        form { display: flex; flex-direction: column; gap: 1.5rem; }

        .form-section { display: flex; flex-direction: column; gap: 1rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }

        label {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        label .required { color: var(--danger); margin-left: 2px; }

        .input-wrap { position: relative; }

        .input-wrap .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
            pointer-events: none;
            transition: color .2s;
        }

        input[type="text"],
        input[type="url"],
        input[type="number"],
        input[type="password"] {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: var(--font);
            font-size: 0.9rem;
            padding: 10px 14px 10px 38px;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            -webkit-appearance: none;
        }

        input::placeholder { color: #4a4f6a; }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--accent); }

        input.error { border-color: var(--danger); }

        .field-error {
            font-size: 0.78rem;
            color: var(--danger);
            margin-top: 2px;
        }

        /* ── Toggle password ────────────────────────────────────── */
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.95rem;
            padding: 4px;
            transition: color .2s;
        }

        .toggle-pw:hover { color: var(--text); }

        /* ── Submit button ──────────────────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--accent);
            color: #fff;
            font-family: var(--font);
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 20px var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
            box-shadow: 0 6px 28px var(--accent-glow);
            transform: translateY(-1px);
        }

        .btn-submit:active { transform: translateY(0); }

        /* ── Loading spinner ────────────────────────────────────── */
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Footer note ────────────────────────────────────────── */
        .card-footer {
            padding: 1rem 2.5rem;
            background: var(--surface-2);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .lock-icon { color: var(--success); }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 520px) {
            .card-header, .card-body, .card-footer, .steps { padding-left: 1.25rem; padding-right: 1.25rem; }
            .form-row { grid-template-columns: 1fr; }
            .steps { gap: 0; overflow-x: auto; }
            .step { min-width: 80px; }
        }
    </style>
</head>
<body>

<div class="card" role="main">

    {{-- Header --}}
    <div class="card-header">
        <div class="badge">
            <span>🔧</span> One-Time Setup
        </div>
        <h1>Application Installer</h1>
        <p>Configure your application for production deployment. This wizard runs only once.</p>
    </div>

    {{-- Step indicator --}}
    <div class="steps" role="navigation" aria-label="Setup steps">
        <div class="step active">
            <span class="step-dot">1</span>
            <span>Configure</span>
        </div>
        <div class="step">
            <span class="step-dot">2</span>
            <span>Migrate</span>
        </div>
        <div class="step">
            <span class="step-dot">3</span>
            <span>Done</span>
        </div>
    </div>

    {{-- Body --}}
    <div class="card-body">

        {{-- Global errors --}}
        @if ($errors->any())
            <div class="alert" role="alert" id="installer-errors">
                <span class="alert-icon">⚠️</span>
                <div class="alert-content">
                    <div class="alert-title">Please fix the following errors:</div>
                    <div class="alert-body">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('installer.run', $token) }}" id="installer-form" novalidate>
            @csrf

            {{-- ── Application Settings ─────────────────────────── --}}
            <div class="form-section">
                <div class="section-title">
                    <span>🚀</span> Application Settings
                </div>

                <div class="form-group">
                    <label for="app_name">Application Name <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="app_name"
                            name="app_name"
                            placeholder="My CRM"
                            value="{{ old('app_name', config('app.name', '')) }}"
                            required
                            autocomplete="off"
                            class="{{ $errors->has('app_name') ? 'error' : '' }}"
                        >
                        <span class="input-icon" aria-hidden="true">📛</span>
                    </div>
                    @error('app_name')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="app_url">Application URL <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input
                            type="url"
                            id="app_url"
                            name="app_url"
                            placeholder="https://yourdomain.com"
                            value="{{ old('app_url', $appUrl) }}"
                            required
                            autocomplete="off"
                            class="{{ $errors->has('app_url') ? 'error' : '' }}"
                        >
                        <span class="input-icon" aria-hidden="true">🌐</span>
                    </div>
                    @error('app_url')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- ── Database Settings ────────────────────────────── --}}
            <div class="form-section">
                <div class="section-title">
                    <span>🗄️</span> Database Connection (MySQL)
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="db_host">DB Host <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input
                                type="text"
                                id="db_host"
                                name="db_host"
                                placeholder="127.0.0.1"
                                value="{{ old('db_host', '127.0.0.1') }}"
                                required
                                autocomplete="off"
                                class="{{ $errors->has('db_host') ? 'error' : '' }}"
                            >
                            <span class="input-icon" aria-hidden="true">🖥️</span>
                        </div>
                        @error('db_host')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="db_port">DB Port <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input
                                type="number"
                                id="db_port"
                                name="db_port"
                                placeholder="3306"
                                value="{{ old('db_port', '3306') }}"
                                required
                                min="1"
                                max="65535"
                                autocomplete="off"
                                class="{{ $errors->has('db_port') ? 'error' : '' }}"
                            >
                            <span class="input-icon" aria-hidden="true">🔌</span>
                        </div>
                        @error('db_port')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="db_database">Database Name <span class="required">*</span></label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="db_database"
                            name="db_database"
                            placeholder="mini_crm"
                            value="{{ old('db_database') }}"
                            required
                            autocomplete="off"
                            class="{{ $errors->has('db_database') ? 'error' : '' }}"
                        >
                        <span class="input-icon" aria-hidden="true">📦</span>
                    </div>
                    @error('db_database')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="db_username">DB Username <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input
                                type="text"
                                id="db_username"
                                name="db_username"
                                placeholder="root"
                                value="{{ old('db_username') }}"
                                required
                                autocomplete="off"
                                class="{{ $errors->has('db_username') ? 'error' : '' }}"
                            >
                            <span class="input-icon" aria-hidden="true">👤</span>
                        </div>
                        @error('db_username')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="db_password">DB Password</label>
                        <div class="input-wrap">
                            <input
                                type="password"
                                id="db_password"
                                name="db_password"
                                placeholder="Leave blank if none"
                                value="{{ old('db_password') }}"
                                autocomplete="new-password"
                                class="{{ $errors->has('db_password') ? 'error' : '' }}"
                            >
                            <span class="input-icon" aria-hidden="true">🔑</span>
                            <button
                                type="button"
                                class="toggle-pw"
                                aria-label="Toggle password visibility"
                                onclick="togglePw()"
                                id="toggle-pw-btn"
                            >👁️</button>
                        </div>
                        @error('db_password')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-submit" id="submit-btn">
                <span id="btn-text">🚀 Run Installation</span>
                <span class="spinner" id="btn-spinner"></span>
            </button>

        </form>
    </div>

    {{-- Footer --}}
    <div class="card-footer">
        <span class="lock-icon">🔒</span>
        <span>Secure one-time installer — this page will be permanently disabled after setup.</span>
    </div>

</div>

<script>
    // Toggle password visibility
    function togglePw() {
        const input = document.getElementById('db_password');
        const btn   = document.getElementById('toggle-pw-btn');
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
            btn.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            btn.textContent = '👁️';
            btn.setAttribute('aria-label', 'Show password');
        }
    }

    // Show loading state on submit
    document.getElementById('installer-form').addEventListener('submit', function () {
        const btn     = document.getElementById('submit-btn');
        const text    = document.getElementById('btn-text');
        const spinner = document.getElementById('btn-spinner');
        btn.disabled      = true;
        btn.style.opacity = '0.75';
        text.textContent  = 'Installing…';
        spinner.style.display = 'block';
    });

    // Scroll to errors if any
    const errBox = document.getElementById('installer-errors');
    if (errBox) errBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
</script>

</body>
</html>
