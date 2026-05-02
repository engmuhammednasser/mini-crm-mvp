@echo off
:: =============================================================================
::  run-github-upload.bat
::  Launcher for github-upload.ps1
::  Double-click this file to run the GitHub upload script easily.
:: =============================================================================

title GitHub Upload Script

:: Make sure we run from the directory that contains this .bat file
cd /d "%~dp0"

echo.
echo  Starting GitHub Upload Script...
echo.

:: Run PowerShell with:
::   -NoProfile         – skip user profile for speed
::   -ExecutionPolicy   – allow unsigned scripts for this session only
::   -File              – path to the script
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0github-upload.ps1"

:: Pause so the window stays open after the script finishes (success or error)
echo.
pause