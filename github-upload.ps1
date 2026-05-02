# =============================================================================
#  github-upload.ps1
#  A helper script to upload any project to GitHub easily and safely.
#  Author : Antigravity AI Assistant
#  Date   : 2026-05-02
# =============================================================================

# ── Pretty helpers ─────────────────────────────────────────────────────────────
function Write-Step  { param([string]$msg) Write-Host "`n▶  $msg" -ForegroundColor Cyan }
function Write-OK    { param([string]$msg) Write-Host "  ✔  $msg" -ForegroundColor Green }
function Write-Warn  { param([string]$msg) Write-Host "  ⚠  $msg" -ForegroundColor Yellow }
function Write-Fail  { param([string]$msg) Write-Host "`n  ✖  $msg" -ForegroundColor Red }
function Write-Info  { param([string]$msg) Write-Host "     $msg" -ForegroundColor Gray }

# ── Banner ─────────────────────────────────────────────────────────────────────
Clear-Host
Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════╗" -ForegroundColor Magenta
Write-Host "  ║        GitHub Upload Script  v1.0            ║" -ForegroundColor Magenta
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

# Validate that the folder exists
if (-not (Test-Path $projectPath -PathType Container)) {
    Write-Fail "Folder not found: $projectPath"
    exit 1
}

# Change into the project directory for all subsequent git commands
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

# These are the patterns we ALWAYS want to ignore.
# They are grouped for readability inside the file.
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
    # File exists – add only the lines that are not already present
    $existing = Get-Content $gitignorePath -Raw
    $newLines  = @()

    foreach ($line in ($ignoreBlocks -split "`n")) {
        $trimmed = $line.Trim()
        # Skip blank lines and comment-only lines when checking for duplicates
        if ($trimmed -eq "" -or $trimmed.StartsWith("#")) {
            $newLines += $line
            continue
        }
        if ($existing -notmatch [regex]::Escape($trimmed)) {
            $newLines += $line
        }
    }

    if ($newLines.Count -gt 0) {
        Add-Content $gitignorePath ("`n# ── Added by github-upload.ps1 ──────────────────────────────────────────`n" + ($newLines -join "`n"))
        Write-OK ".gitignore updated with missing entries."
    } else {
        Write-OK ".gitignore already contains all required entries."
    }
} else {
    # No .gitignore yet – create a fresh one
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

# Patterns that must never be committed
$sensitivePatterns = @(
    ".env",
    "wp-config.php",
    "vendor/",
    "node_modules/"
)

foreach ($pattern in $sensitivePatterns) {
    # --cached means: stop tracking the file/folder but KEEP it on disk
    $result = git rm -r --cached --ignore-unmatch $pattern 2>&1
    if ($result -match "rm '") {
        Write-Warn "Removed from tracking: $pattern  (file kept locally)"
    }
}

# =============================================================================
# STEP 7 – GitHub repository URL
# =============================================================================
Write-Step "GitHub repository"
$repoInput = Read-Host "  Enter GitHub repo URL or owner/repo (e.g. johndoe/my-project)"
$repoInput  = $repoInput.Trim()

# Convert owner/repo shorthand to full HTTPS URL
if ($repoInput -notmatch "^https?://") {
    $repoUrl = "https://github.com/$repoInput.git"
    Write-Info "Converted to: $repoUrl"
} else {
    # Ensure the URL ends with .git
    $repoUrl = if ($repoInput.EndsWith(".git")) { $repoInput } else { "$repoInput.git" }
}

Write-OK "Repository URL: $repoUrl"

# =============================================================================
# STEP 8 – Add or update remote 'origin'
# =============================================================================
Write-Step "Configuring remote origin"

$remotes = git remote 2>&1
if ($remotes -contains "origin") {
    git remote set-url origin $repoUrl | Out-Null
    Write-OK "Remote 'origin' updated."
} else {
    git remote add origin $repoUrl | Out-Null
    Write-OK "Remote 'origin' added."
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

# Handle the case where there is nothing new to commit
if ($commitOutput -match "nothing to commit") {
    Write-Warn "Nothing new to commit. The working tree is clean."
} elseif ($LASTEXITCODE -ne 0) {
    Write-Fail "git commit failed:`n$commitOutput"
    exit 1
} else {
    Write-OK "Committed successfully."
}

# =============================================================================
# STEP 13 – Rename/create branch
# =============================================================================
Write-Step "Setting branch to '$branch'"
git branch -M $branch 2>&1 | Out-Null
Write-OK "Branch set."

# =============================================================================
# STEP 14 – Push to GitHub
# =============================================================================
Write-Step "Pushing to GitHub…"
Write-Warn "(You may be prompted for your GitHub credentials.)"
Write-Info  "Tip: Use a Personal Access Token as your password."
Write-Host ""

git push -u origin $branch

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Fail "Push failed. Common causes:"
    Write-Info  "  • Wrong repository URL"
    Write-Info  "  • Authentication error (use a Personal Access Token, not your password)"
    Write-Info  "  • Remote branch has commits not in your local history → try git pull first"
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
