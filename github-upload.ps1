# =============================================================================
#  github-upload.ps1
#  A reusable helper script to upload any project to GitHub easily and safely.
#  Author : Antigravity AI Assistant
#  Date   : 2026-05-02  |  v2.1
# =============================================================================

# ── Pretty helpers ─────────────────────────────────────────────────────────────
function Write-Step  { param([string]$msg) Write-Host "`n▶  $msg" -ForegroundColor Cyan }
function Write-OK    { param([string]$msg) Write-Host "  ✔  $msg" -ForegroundColor Green }
function Write-Warn  { param([string]$msg) Write-Host "  ⚠  $msg" -ForegroundColor Yellow }
function Write-Fail  { param([string]$msg) Write-Host "`n  ✖  $msg" -ForegroundColor Red }
function Write-Info  { param([string]$msg) Write-Host "     $msg" -ForegroundColor Gray }

# =============================================================================
# Convert-SshToHttps
# ──────────────────
# Converts a GitHub SSH URL to its HTTPS equivalent.
# Used when the user wants to fall back from SSH to HTTPS+PAT authentication.
#
#   git@github.com:owner/repo.git  →  https://github.com/owner/repo.git
# =============================================================================
function Convert-SshToHttps {
    param([string]$sshUrl)
    # Extract  owner/repo  from  git@github.com:owner/repo[.git]
    if ($sshUrl -match '^git@github\.com:([A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+?)(?\.git)?$') {
        return "https://github.com/$($Matches[1]).git"
    }
    throw "Cannot convert '$sshUrl' — not a recognised SSH GitHub URL."
}

# =============================================================================
# Test-SshGitHubAccess
# ────────────────────
# Returns $true when the local machine has a working SSH key registered with
# GitHub.  Runs `ssh -T git@github.com` with a 10-second timeout.
# =============================================================================
function Test-SshGitHubAccess {
    try {
        $result = & ssh -T git@github.com -o ConnectTimeout=10 -o BatchMode=yes 2>&1
        # GitHub returns exit code 1 even on success, so check stdout/stderr text
        return ($result -match 'successfully authenticated')
    } catch {
        return $false
    }
}

# =============================================================================
# URL NORMALIZATION FUNCTIONS
# =============================================================================
#
# Is-ValidGitHubRepoInput
# ───────────────────────
# Returns $true when the trimmed input matches one of the accepted formats:
#   • owner/repo  shorthand          e.g.  username/project-name
#   • HTTPS GitHub URL               e.g.  https://github.com/owner/repo[.git][/]
#   • SSH  GitHub URL                e.g.  git@github.com:owner/repo[.git]
#
# Returns $false for anything else:
#   • gitlab / bitbucket / other hosts
#   • random text with no recognised structure
#   • github.com without the https:// scheme
#   • accidental double-encoding like https://github.com/git@...
#
function Is-ValidGitHubRepoInput {
    param([string]$raw)

    $s = $raw.Trim().TrimEnd('/')

    # owner/repo  (no slashes other than the one separator, no protocol)
    if ($s -match '^[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+$') { return $true }

    # HTTPS GitHub URL  – host must be exactly github.com
    if ($s -match '^https://github\.com/[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?/?$') { return $true }

    # SSH GitHub URL
    if ($s -match '^git@github\.com:[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?$') { return $true }

    return $false
}

# =============================================================================
# Normalize-RepoUrl
# ─────────────────
# Accepts every valid GitHub repo format and returns a canonical URL.
#
# Normalisation rules applied in order:
#   1. Trim leading/trailing whitespace.
#   2. Remove a single trailing slash (browser copy artefact).
#   3. Detect SSH URL  → keep as-is, append .git if missing.
#   4. Detect HTTPS URL → keep scheme + path, append .git if missing.
#   5. Detect owner/repo shorthand → build https://github.com/owner/repo.git
#   6. Anything else → throw so the caller can ask the user again.
#
# Test examples (INPUT → EXPECTED OUTPUT)
# ─────────────────────────────────────────────────────────────────────────────
#   username/project-name
#       → https://github.com/username/project-name.git
#
#   https://github.com/username/project-name
#       → https://github.com/username/project-name.git
#
#   https://github.com/username/project-name.git
#       → https://github.com/username/project-name.git        (unchanged)
#
#   https://github.com/username/project-name/
#       → https://github.com/username/project-name.git        (slash stripped)
#
#   git@github.com:username/project-name
#       → git@github.com:username/project-name.git
#
#   git@github.com:username/project-name.git
#       → git@github.com:username/project-name.git            (unchanged)
#
# Invalid inputs (will throw):
#   https://github.com/git@github.com:username/project-name.git.git
#   random-text
#   github.com/username/project-name        (missing https://)
#   https://gitlab.com/username/project-name
# =============================================================================
function Normalize-RepoUrl {
    param([string]$raw)

    # ── Step 1: trim whitespace and trailing slash ──────────────────────────
    $s = $raw.Trim().TrimEnd('/')

    # ── Step 2: reject empty input ──────────────────────────────────────────
    if ([string]::IsNullOrWhiteSpace($s)) {
        throw "Repository URL cannot be empty."
    }

    # ── Step 3: SSH GitHub URL  (git@github.com:owner/repo[.git]) ──────────
    #   Do NOT convert to HTTPS; keep the SSH form exactly as-is.
    if ($s -match '^git@github\.com:[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?$') {
        if (-not $s.EndsWith('.git')) { $s += '.git' }
        return $s
    }

    # ── Step 4: HTTPS GitHub URL ────────────────────────────────────────────
    #   Must start with https://github.com/ – reject http://, gitlab, etc.
    if ($s -match '^https://github\.com/[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?$') {
        # Strip accidental duplicate .git  (safety guard)
        $s = $s -replace '\.git\.git$', '.git'
        if (-not $s.EndsWith('.git')) { $s += '.git' }
        return $s
    }

    # ── Step 5: owner/repo shorthand ────────────────────────────────────────
    #   Exactly one slash, no protocol prefix, no dots in between (only in names)
    if ($s -match '^[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+$') {
        return "https://github.com/$s.git"
    }

    # ── Step 6: nothing matched → unsupported format ────────────────────────
    throw "Unsupported GitHub repository format: '$raw'"
}

# =============================================================================
# Is-MalformedGitHubRemote
# ────────────────────────
# Heuristic check applied to an *existing* remote URL that was already stored
# in the repo's git config.  Returns $true when the URL looks broken.
#
# Patterns detected as malformed:
#   • Ends with .git.git
#   • Contains git@github.com inside an HTTPS URL  (https://github.com/git@…)
#   • Has no recognisable owner/repo segment after github.com
#   • Empty / whitespace only
# =============================================================================
function Is-MalformedGitHubRemote {
    param([string]$url)

    $u = $url.Trim()

    if ([string]::IsNullOrWhiteSpace($u))           { return $true }
    if ($u -match '\.git\.git')                     { return $true }
    if ($u -match 'github\.com/git@')               { return $true }
    if ($u -match 'github\.com/@')                  { return $true }

    # HTTPS URL must have owner/repo after github.com/
    if ($u -match '^https://') {
        if ($u -notmatch '^https://github\.com/[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?/?$') {
            return $true
        }
    }

    # SSH URL must have owner/repo after the colon
    if ($u -match '^git@') {
        if ($u -notmatch '^git@github\.com:[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+(\.git)?$') {
            return $true
        }
    }

    return $false
}

# ── Banner ─────────────────────────────────────────────────────────────────────
Clear-Host
Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════╗" -ForegroundColor Magenta
Write-Host "  ║        GitHub Upload Script  v2.0            ║" -ForegroundColor Magenta
Write-Host "  ║   Easily push any project to GitHub          ║" -ForegroundColor Magenta
Write-Host "  ╚══════════════════════════════════════════════╝" -ForegroundColor Magenta
Write-Host ""

# =============================================================================
# STEP 1 – Project path
# =============================================================================
Write-Step "Project path"
$defaultPath = (Get-Location).Path
$inputPath   = Read-Host "  Enter project path (press Enter for current folder: $defaultPath)"
$projectPath = if ([string]::IsNullOrWhiteSpace($inputPath)) { $defaultPath } else { $inputPath.Trim() }

if (-not (Test-Path $projectPath -PathType Container)) {
    Write-Fail "Folder not found: $projectPath"
    exit 1
}

Set-Location $projectPath
Write-OK "Working in: $projectPath"

# =============================================================================
# STEP 2 – Verify Git is installed
# =============================================================================
Write-Step "Checking Git installation"
try {
    $gitVersion = git --version 2>&1
    Write-OK "Git found → $gitVersion"
} catch {
    Write-Fail "Git is not installed or not in PATH."
    Write-Info  "Download Git from: https://git-scm.com/downloads"
    exit 1
}

# =============================================================================
# STEP 3 – Initialize Git repository if needed
# =============================================================================
Write-Step "Git repository"
if (-not (Test-Path ".git" -PathType Container)) {
    Write-Warn ".git folder not found — initialising a new repository…"
    git init | Out-Null
    Write-OK "Repository initialised."
} else {
    Write-OK "Repository already initialised."
}

# =============================================================================
# STEP 4 – Create / update .gitignore with safe defaults
# =============================================================================
Write-Step "Updating .gitignore"

$ignoreBlocks = @"
# ── Environment & secrets ───────────────────────────────────────────────
.env
.env.*
!.env.example

# ── WordPress ───────────────────────────────────────────────────────────
wp-config.php

# ── PHP / Composer ──────────────────────────────────────────────────────
vendor/

# ── Node / npm ──────────────────────────────────────────────────────────
node_modules/

# ── Logs ────────────────────────────────────────────────────────────────
*.log
logs/

# ── Storage / cache (Laravel, Symfony, etc.) ────────────────────────────
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/

# ── IDE & editor files ──────────────────────────────────────────────────
.idea/
.vscode/
*.suo
*.user
*.swp
*.swo
*.sublime-workspace
*.sublime-project

# ── OS generated files ──────────────────────────────────────────────────
.DS_Store
Thumbs.db
Desktop.ini
"@

$gitignorePath = ".gitignore"

if (Test-Path $gitignorePath) {
    $existing = Get-Content $gitignorePath -Raw
    $newLines  = @()

    foreach ($line in ($ignoreBlocks -split "`n")) {
        $trimmed = $line.Trim()
        if ($trimmed -eq "" -or $trimmed.StartsWith("#")) {
            $newLines += $line
            continue
        }
        if ($existing -notmatch [regex]::Escape($trimmed)) {
            $newLines += $line
        }
    }

    if ($newLines.Count -gt 0) {
        Add-Content $gitignorePath ("`n# ── Added by github-upload.ps1 ────────────────────────────────────────`n" + ($newLines -join "`n"))
        Write-OK ".gitignore updated with missing entries."
    } else {
        Write-OK ".gitignore already contains all required entries."
    }
} else {
    Set-Content $gitignorePath $ignoreBlocks -Encoding UTF8
    Write-OK ".gitignore created."
}

# =============================================================================
# STEP 5 – Configure git user if not set
# =============================================================================
Write-Step "Git user identity"

$currentName  = git config user.name  2>&1
$currentEmail = git config user.email 2>&1

if ([string]::IsNullOrWhiteSpace($currentName)) {
    $gitName = Read-Host "  git user.name  is not set. Enter your name"
    git config user.name  "$gitName"
    Write-OK "user.name set to: $gitName"
} else {
    Write-OK "user.name  → $currentName"
}

if ([string]::IsNullOrWhiteSpace($currentEmail)) {
    $gitEmail = Read-Host "  git user.email is not set. Enter your email"
    git config user.email "$gitEmail"
    Write-OK "user.email set to: $gitEmail"
} else {
    Write-OK "user.email → $currentEmail"
}

# =============================================================================
# STEP 6 – Remove sensitive files from Git tracking (without deleting locally)
# =============================================================================
Write-Step "Removing sensitive files from tracking (if previously committed)"

$sensitivePatterns = @(
    ".env",
    "wp-config.php",
    "vendor/",
    "node_modules/"
)

foreach ($pattern in $sensitivePatterns) {
    $result = git rm -r --cached --ignore-unmatch $pattern 2>&1
    if ($result -match "rm '") {
        Write-Warn "Removed from tracking: $pattern  (file kept locally)"
    }
}

# =============================================================================
# STEP 7 – GitHub repository URL  (robust, generic, validated)
# =============================================================================
Write-Step "GitHub repository"

# ── Helper: print accepted formats ─────────────────────────────────────────
function Show-AcceptedFormats {
    Write-Host ""
    Write-Info "  Accepted formats:"
    Write-Info "    • username/project-name"
    Write-Info "    • https://github.com/username/project-name.git"
    Write-Info "    • https://github.com/username/project-name"
    Write-Info "    • https://github.com/username/project-name/"
    Write-Info "    • git@github.com:username/project-name.git"
    Write-Info "    • git@github.com:username/project-name"
    Write-Host ""
}

# ── Check for an existing remote origin ────────────────────────────────────
$remotes    = git remote 2>&1
$repoUrl    = $null
$skipPrompt = $false

if ($remotes -contains "origin") {
    $existingOrigin = (git remote get-url origin 2>&1).Trim()
    Write-Info "Existing remote origin: $existingOrigin"

    if (Is-MalformedGitHubRemote $existingOrigin) {
        # ── Repair flow ──────────────────────────────────────────────────────
        Write-Host ""
        Write-Warn "The current remote origin looks malformed or invalid:"
        Write-Info "  $existingOrigin"
        Write-Warn "Please enter a valid GitHub repository URL to replace it."
        Show-AcceptedFormats
    } else {
        # ── Valid existing origin – ask to keep or replace ────────────────────
        Write-Host ""
        Write-OK "Remote origin is valid: $existingOrigin"
        $keepChoice = Read-Host "  Keep this remote? (Y / N)"
        if ($keepChoice -match '^[Yy]') {
            $repoUrl    = $existingOrigin
            $skipPrompt = $true
            Write-OK "Keeping existing remote: $repoUrl"
        } else {
            Write-Info "You chose to replace the remote. Enter the new URL:"
            Show-AcceptedFormats
        }
    }
} else {
    Show-AcceptedFormats
}

# ── Input loop – keep asking until a valid URL is entered ──────────────────
if (-not $skipPrompt) {
    while ($true) {
        $rawInput = Read-Host "  Enter GitHub repo URL or owner/repo"

        if ([string]::IsNullOrWhiteSpace($rawInput)) {
            Write-Fail "Input cannot be empty."
            Show-AcceptedFormats
            continue
        }

        if (-not (Is-ValidGitHubRepoInput $rawInput)) {
            Write-Fail "Unrecognised format: '$($rawInput.Trim())'"
            Write-Info "Only github.com repositories are supported."
            Show-AcceptedFormats
            continue
        }

        try {
            $repoUrl = Normalize-RepoUrl $rawInput
            break   # Valid – exit the loop
        } catch {
            Write-Fail $_.Exception.Message
            Show-AcceptedFormats
        }
    }
}

# ── Preview the normalised URL ─────────────────────────────────────────────
Write-Host ""
Write-Host "  Repository URL accepted:" -ForegroundColor Cyan
Write-Host "  $repoUrl" -ForegroundColor White
Write-Host ""
Write-OK "URL normalised successfully."

# =============================================================================
# STEP 8 – Add or update remote 'origin'
# =============================================================================
Write-Step "Configuring remote origin"

$remotes = git remote 2>&1
if ($remotes -contains "origin") {
    git remote set-url origin $repoUrl | Out-Null
    Write-OK "Remote 'origin' updated to: $repoUrl"
} else {
    git remote add origin $repoUrl | Out-Null
    Write-OK "Remote 'origin' added: $repoUrl"
}

# =============================================================================
# STEP 9 – Branch name
# =============================================================================
Write-Step "Branch name"
$branchInput = Read-Host "  Enter branch name (press Enter for default: main)"
$branch      = if ([string]::IsNullOrWhiteSpace($branchInput)) { "main" } else { $branchInput.Trim() }
Write-OK "Branch: $branch"

# =============================================================================
# STEP 10 – Commit message
# =============================================================================
Write-Step "Commit message"
$msgInput      = Read-Host "  Enter commit message (press Enter for default)"
$commitMessage = if ([string]::IsNullOrWhiteSpace($msgInput)) { "Initial project upload" } else { $msgInput.Trim() }
Write-OK "Commit message: $commitMessage"

# =============================================================================
# STEP 11 – Stage all files
# =============================================================================
Write-Step "Staging files (git add .)"
git add . 2>&1 | Out-Null
Write-OK "All files staged."

# =============================================================================
# STEP 12 – Commit
# =============================================================================
Write-Step "Committing"
$commitOutput = git commit -m $commitMessage 2>&1

if ($commitOutput -match "nothing to commit") {
    Write-Warn "Nothing new to commit. The working tree is clean."
} elseif ($LASTEXITCODE -ne 0) {
    Write-Fail "git commit failed:`n$commitOutput"
    exit 1
} else {
    Write-OK "Committed successfully."
}

# =============================================================================
# STEP 13 – Rename / create branch
# =============================================================================
Write-Step "Setting branch to '$branch'"
git branch -M $branch 2>&1 | Out-Null
Write-OK "Branch set."

# =============================================================================
# STEP 14 – Push to GitHub  (SSH-aware, with HTTPS fallback offer)
# =============================================================================
Write-Step "Pushing to GitHub…"

# ── Detect SSH vs HTTPS and handle authentication proactively ──────────────
$isSSH = $repoUrl -match '^git@'

if ($isSSH) {
    Write-Info "Protocol: SSH (git@github.com)"
    Write-Info "Checking if your SSH key is registered with GitHub…"

    $sshOk = Test-SshGitHubAccess

    if (-not $sshOk) {
        Write-Host ""
        Write-Warn "SSH authentication test failed!"
        Write-Info  "Your local SSH key is either missing or not added to your GitHub account."
        Write-Host ""
        Write-Host "  You have two options:" -ForegroundColor Cyan
        Write-Host "  [1] Switch to HTTPS + Personal Access Token (recommended — no SSH setup needed)" -ForegroundColor White
        Write-Host "  [2] Continue with SSH anyway (if you know your key is set up)" -ForegroundColor White
        Write-Host ""
        Write-Host "  How to set up SSH keys:" -ForegroundColor DarkGray
        Write-Host "    https://docs.github.com/en/authentication/connecting-to-github-with-ssh" -ForegroundColor DarkGray
        Write-Host ""

        $authChoice = Read-Host "  Enter 1 or 2"

        if ($authChoice -eq '1') {
            # ── Convert SSH → HTTPS and update remote ────────────────────────
            try {
                $httpsUrl = Convert-SshToHttps $repoUrl
                $repoUrl  = $httpsUrl
                git remote set-url origin $repoUrl | Out-Null
                Write-OK  "Remote switched to HTTPS: $repoUrl"
                $isSSH = $false
            } catch {
                Write-Fail "Could not convert URL: $($_.Exception.Message)"
                exit 1
            }
        } else {
            Write-Warn "Continuing with SSH. Push may fail if your key is not configured."
        }
    } else {
        Write-OK "SSH key verified — GitHub access confirmed."
    }
}

if (-not $isSSH) {
    Write-Info "Protocol: HTTPS"
    Write-Warn "When prompted for a password, enter your Personal Access Token (PAT) — NOT your GitHub password."
    Write-Host ""
    Write-Host "  How to create a PAT:" -ForegroundColor DarkGray
    Write-Host "    GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)" -ForegroundColor DarkGray
    Write-Host "    Required scope: repo (Full control of private repositories)" -ForegroundColor DarkGray
    Write-Host ""
}

Write-Host ""
git push -u origin $branch

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Fail "Push failed."
    Write-Host ""

    if ($isSSH) {
        Write-Host "  SSH troubleshooting:" -ForegroundColor Yellow
        Write-Info  "  1. Generate a key:   ssh-keygen -t ed25519 -C \"your@email.com\""
        Write-Info  "  2. Add to agent:     ssh-add ~/.ssh/id_ed25519"
        Write-Info  "  3. Copy public key:  Get-Content ~/.ssh/id_ed25519.pub | Set-Clipboard"
        Write-Info  "  4. Add to GitHub:    https://github.com/settings/ssh/new"
        Write-Info  "  5. Test connection:  ssh -T git@github.com"
        Write-Info  "  Or run this script again and choose HTTPS (option 1) instead."
    } else {
        Write-Host "  HTTPS troubleshooting:" -ForegroundColor Yellow
        Write-Info  "  • Do NOT use your GitHub account password — it no longer works for Git."
        Write-Info  "  • Use a Personal Access Token (PAT) as the password."
        Write-Info  "  • Create a PAT at: https://github.com/settings/tokens"
        Write-Info  "  • Required scope: repo"
        Write-Info  "  • If prompted, enter your GitHub USERNAME (not email) and PAT as password."
        Write-Info  "  • The remote has uncommitted history → run: git pull --rebase origin $branch"
    }
    exit 1
}

# =============================================================================
# SUCCESS
# =============================================================================
Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "  ║  🎉  Project pushed successfully to GitHub!              ║" -ForegroundColor Green
Write-Host "  ║                                                          ║" -ForegroundColor Green
Write-Host "  ║  Repository : $repoUrl" -ForegroundColor Green
Write-Host "  ║  Branch     : $branch" -ForegroundColor Green
Write-Host "  ╚══════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
