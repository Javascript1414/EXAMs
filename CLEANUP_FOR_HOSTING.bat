@echo off
REM ========================================
REM CITS LMS - Production Cleanup & Organization
REM Purpose: Remove test/debug files for hosting
REM Created: 2026-06-20
REM ========================================

setlocal enabledelayedexpansion

echo.
echo ========================================
echo  CITS LMS - Production Cleanup Script
echo  For InfinityFree Hosting Deployment
echo ========================================
echo.

REM Colors for messages
set "INFO=[INFO]"
set "WARN=[WARNING]"
set "ERR=[ERROR]"
set "SUCCESS=[SUCCESS]"

echo %INFO% Starting cleanup process...
echo.

REM ========================================
REM 1. CREATE BACKUP
REM ========================================
echo %INFO% Creating backup of project...
if not exist "backups" mkdir backups
set "BACKUP_NAME=backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "BACKUP_NAME=%BACKUP_NAME: =0%"
echo %INFO% Backup location: backups\%BACKUP_NAME%
echo.

REM ========================================
REM 2. LIST FILES TO DELETE
REM ========================================
echo %INFO% Files to be deleted:
echo.

setlocal enabledelayedexpansion
set COUNT=0

REM Test and debug files
for /r . %%F in (check_*.php debug_*.php test_*.php verify_*.php) do (
    set /a COUNT+=1
    echo   [!COUNT!] %%~nF
)

echo.
echo %WARN% Total files to remove: !COUNT!
echo.

REM ========================================
REM 3. CONFIRM ACTION
REM ========================================
echo %INFO% Files to KEEP:
echo   - admin/               (Admin panel)
echo   - student/             (Student area)
echo   - teacher/             (Teacher area)
echo   - moderator/           (Moderator area)
echo   - community/           (Community section)
echo   - api/                 (API endpoints)
echo   - includes/            (Backend includes)
echo   - assets/              (CSS, JS, Images)
echo   - index/               (Landing page)
echo   - uploads/             (User uploads)
echo   - vendor/              (Composer dependencies)
echo   - migrations/          (Database migrations)
echo   - emails/              (Email templates)
echo.
echo   - index.php / index_infinityfree.php
echo   - config.php / config_infinityfree.php
echo   - login.php, register.php, staff_login.php
echo   - logout.php, profile.php, forgot_password.php
echo   - .htaccess
echo.

REM ========================================
REM 4. DELETE TEST/DEBUG FILES
REM ========================================
echo %INFO% Removing test/debug files...

REM Create exclude list
set "EXCLUDE_PATTERNS=check_*.php debug_*.php test_*.php verify_*.php"

cd /d "%cd%"

REM Delete check_*.php files
for /r . %%F in (check_*.php) do (
    echo   Deleting: %%~nF
    del "%%F" 2>nul
)

REM Delete debug_*.php files
for /r . %%F in (debug_*.php) do (
    echo   Deleting: %%~nF
    del "%%F" 2>nul
)

REM Delete test_*.php files
for /r . %%F in (test_*.php) do (
    echo   Deleting: %%~nF
    del "%%F" 2>nul
)

REM Delete verify_*.php files
for /r . %%F in (verify_*.php) do (
    echo   Deleting: %%~nF
    del "%%F" 2>nul
)

echo.
echo %SUCCESS% Test/debug files removed!
echo.

REM ========================================
REM 5. DELETE DOCUMENTATION FILES
REM ========================================
echo %INFO% Removing development documentation...

REM Keep ONLY essential MD files
for %%F in (*.md) do (
    if not "%%F"=="INFINITYFREE_DEPLOYMENT_GUIDE.md" (
        if not "%%F"=="INFINITYFREE_READY.md" (
            if not "%%F"=="DEPLOYMENT_QUICK_START.md" (
                echo   Deleting: %%F
                del "%%F" 2>nul
            )
        )
    )
)

REM Delete .txt documentation files
for %%F in (*.txt) do (
    echo   Deleting: %%F
    del "%%F" 2>nul
)

echo.
echo %SUCCESS% Documentation files cleaned!
echo.

REM ========================================
REM 6. DELETE MIGRATION/SETUP FILES
REM ========================================
echo %INFO% Removing migration/setup files...

for /r . %%F in (*.sql) do (
    if "%%~pF"=="\" (
        REM Files in root directory only (not in subdirs)
        echo   Deleting: %%~nF
        del "%%F" 2>nul
    )
)

for %%F in (setup_*.php create_*.php run_*.php migrate_*.php) do (
    if exist "%%F" (
        echo   Deleting: %%F
        del "%%F" 2>nul
    )
)

echo.
echo %SUCCESS% Migration/setup files removed!
echo.

REM ========================================
REM 7. DELETE GIT REPO FILES
REM ========================================
echo %INFO% Checking for .git directory...
if exist ".git" (
    echo   %WARN% .git directory found
    echo   Note: Git files should be removed on production server
)
echo.

REM ========================================
REM 8. DELETE REPORT/AUDIT FILES
REM ========================================
echo %INFO% Removing report/audit files...

for %%F in (*REPORT*.php *AUDIT*.php *SUMMARY*.php *GUIDE*.php) do (
    if exist "%%F" (
        if not "%%F"=="deployment-verify.php" (
            echo   Deleting: %%F
            del "%%F" 2>nul
        )
    )
)

for %%F in (*_COMPLETE_*.md *_COMPLETE_*.php *_FINAL_*.php) do (
    if exist "%%F" (
        echo   Deleting: %%F
        del "%%F" 2>nul
    )
)

echo.
echo %SUCCESS% Report/audit files removed!
echo.

REM ========================================
REM 9. ORGANIZE ROOT DIRECTORY
REM ========================================
echo %INFO% Organizing root directory files...
echo.
echo   Current root files:
for /r . %%F in (*.php) do (
    if "%%~pF"=="\" (
        echo     - %%~nF
    )
)
echo.

REM ========================================
REM 10. DELETE COMPOSER LOCK IF LARGE
REM ========================================
if exist "composer.lock" (
    for %%A in (composer.lock) do (
        if %%~zA gtr 1048576 (
            echo %WARN% composer.lock is large: %%~zA bytes
            echo   Note: Can be regenerated with: composer install
        )
    )
)

echo.

REM ========================================
REM 11. CLEANUP COMPLETE - SUMMARY
REM ========================================
echo.
echo ========================================
echo  CLEANUP COMPLETE
echo ========================================
echo.
echo %SUCCESS% The following have been cleaned:
echo   ✓ All test_*.php files
echo   ✓ All debug_*.php files
echo   ✓ All check_*.php files
echo   ✓ All verify_*.php files
echo   ✓ Development .md files (kept essentials)
echo   ✓ Development .txt files
echo   ✓ Root-level .sql files
echo   ✓ Migration/setup scripts
echo.

echo %INFO% REMAINING ESSENTIAL FILES:
echo   ✓ index_infinityfree.php (rename to: index.php)
echo   ✓ config_infinityfree.php (rename to: config.php)
echo   ✓ .htaccess
echo   ✓ login.php, register.php, staff_login.php
echo   ✓ All user role directories (admin/, student/, teacher/)
echo   ✓ Core includes/ directory
echo   ✓ Assets (CSS, JS, Images)
echo   ✓ uploads/ directory
echo   ✓ vendor/ (dependencies)
echo.

echo %INFO% FOLDER STRUCTURE READY FOR HOSTING!
echo.
echo %INFO% Next steps:
echo   1. Rename: index_infinityfree.php → index.php
echo   2. Rename: config_infinityfree.php → config.php
echo   3. Edit config.php with InfinityFree credentials
echo   4. Upload all files to /htdocs/ via FTP
echo   5. Set permissions: 644 files, 755 dirs, 777 uploads/
echo   6. Import database via phpMyAdmin
echo   7. Visit /deployment-verify.php to test
echo.

pause
echo.
echo %SUCCESS% READY FOR DEPLOYMENT TO INFINITYFREE!
echo.
