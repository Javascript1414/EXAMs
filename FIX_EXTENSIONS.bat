@echo off
REM ============================================
REM XAMPP PHP EXTENSIONS ENABLER
REM Enable GD and ZIP extensions
REM ============================================

echo.
echo ==== XAMPP PHP EXTENSIONS FIX ====
echo.

set PHP_INI=c:\xampp\php\php.ini

if not exist "%PHP_INI%" (
    echo ERROR: php.ini not found at %PHP_INI%
    pause
    exit /b 1
)

echo Backing up php.ini...
copy "%PHP_INI%" "%PHP_INI%.backup" > nul

echo.
echo Enabling GD extension...
powershell -Command "(Get-Content '%PHP_INI%') -replace '^;extension=gd', 'extension=gd' | Set-Content '%PHP_INI%'"

echo Enabling ZIP extension...
powershell -Command "(Get-Content '%PHP_INI%') -replace '^;extension=zip', 'extension=zip' | Set-Content '%PHP_INI%'"

echo.
echo Creating upload directories...
if not exist "c:\xampp\htdocs\EXAMs\uploads\profile_photos" mkdir "c:\xampp\htdocs\EXAMs\uploads\profile_photos"
if not exist "c:\xampp\htdocs\EXAMs\uploads\cover_photos" mkdir "c:\xampp\htdocs\EXAMs\uploads\cover_photos"
if not exist "c:\xampp\htdocs\EXAMs\uploads\study_materials" mkdir "c:\xampp\htdocs\EXAMs\uploads\study_materials"
if not exist "c:\xampp\htdocs\EXAMs\uploads\exam_materials" mkdir "c:\xampp\htdocs\EXAMs\uploads\exam_materials"
if not exist "c:\xampp\htdocs\EXAMs\uploads\certificates" mkdir "c:\xampp\htdocs\EXAMs\uploads\certificates"

echo.
echo ✓ Extensions enabled in php.ini
echo ✓ Upload directories created
echo.
echo IMPORTANT: You must RESTART Apache in XAMPP Control Panel!
echo.
pause
