@echo off
REM ============================================
REM CITS LMS - Complete Setup Script
REM This script starts everything needed
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ════════════════════════════════════════
echo   CITS LMS - Complete Setup
echo ════════════════════════════════════════
echo.

REM Get XAMPP directory
set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%\" (
    echo ERROR: XAMPP not found at %XAMPP_PATH%
    pause
    exit /b 1
)

cd /d "%XAMPP_PATH%"

echo [1/3] Starting Apache...
call apache_start.bat >nul 2>&1
timeout /t 2 /nobreak >nul
echo     ✓ Apache started

echo.
echo [2/3] Starting MySQL...
call mysql_start.bat >nul 2>&1
timeout /t 3 /nobreak >nul
echo     ✓ MySQL started

echo.
echo [3/3] Opening Setup Page...
timeout /t 1 /nobreak >nul

REM Open browser
start "" http://localhost/EXAMs/auto_setup.php

echo.
echo ════════════════════════════════════════
echo   ✓ All services started!
echo ════════════════════════════════════════
echo.
echo   Browser should open in a moment...
echo   If not, visit: http://localhost/EXAMs/
echo.
pause
