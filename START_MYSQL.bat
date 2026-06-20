@echo off
REM Start XAMPP MySQL
REM This script will start MySQL service for XAMPP

echo ================================
echo Starting XAMPP MySQL...
echo ================================

cd /d C:\xampp

REM Check if mysql is already running
tasklist | findstr /I mysqld >nul
if %errorlevel% equ 0 (
    echo MySQL is already running!
    goto done
)

echo Starting MySQL service...

REM Try different methods to start MySQL
if exist "mysql_start.bat" (
    echo Using mysql_start.bat...
    call mysql_start.bat
) else if exist "mysql\bin\mysqld.exe" (
    echo Starting mysqld directly...
    start "MySQL" mysql\bin\mysqld.exe --defaults-file=mysql\bin\my.ini
) else (
    echo ERROR: Could not find MySQL executable!
    pause
    exit /b 1
)

echo.
echo Waiting 3 seconds for MySQL to start...
timeout /t 3 /nobreak

echo.
echo Testing MySQL connection...
mysql\bin\mysql -u root -e "SELECT 1" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ MySQL is running!
) else (
    echo ✗ MySQL failed to start
)

:done
echo.
echo MySQL should now be running!
echo Visit: http://localhost/EXAMs/mysql_fixer.php
echo.
pause
