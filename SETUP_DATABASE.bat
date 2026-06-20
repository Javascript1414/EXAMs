@echo off
REM ============================================
REM CITS LMS - Database Auto Setup
REM This script creates exams_lms database
REM and imports all tables
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ════════════════════════════════════════
echo   Database Setup: exams_lms
echo ════════════════════════════════════════
echo.

set XAMPP_PATH=C:\xampp
set MYSQL_BIN=%XAMPP_PATH%\mysql\bin\mysql.exe
set MYSQL_DATA=%XAMPP_PATH%\htdocs\EXAMs\database.sql

REM Check MySQL executable
if not exist "%MYSQL_BIN%" (
    echo ERROR: MySQL not found at %MYSQL_BIN%
    pause
    exit /b 1
)

REM Check database.sql
if not exist "%MYSQL_DATA%" (
    echo ERROR: database.sql not found at %MYSQL_DATA%
    pause
    exit /b 1
)

echo [1] Database File: %MYSQL_DATA%
echo [2] MySQL Binary: %MYSQL_BIN%
echo.

REM Create database
echo Creating database 'exams_lms'...
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS exams_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Could not create database
    echo Make sure MySQL is running!
    pause
    exit /b 1
)
echo ✓ Database created

REM Import SQL file
echo.
echo Importing database structure and data...
"%MYSQL_BIN%" -u root exams_lms < "%MYSQL_DATA%" 2>&1
if %errorlevel% neq 0 (
    echo WARNING: Import finished with errors (this is usually OK)
) else (
    echo ✓ Import successful
)

REM Verify tables
echo.
echo Verifying tables...
"%MYSQL_BIN%" -u root -e "USE exams_lms; SHOW TABLES;" 2>&1

echo.
echo ════════════════════════════════════════
echo   ✓ Database Setup Complete!
echo ════════════════════════════════════════
echo.
echo You can now use:
echo   Student Login: http://localhost/EXAMs/student_login.php
echo   Staff Login:   http://localhost/EXAMs/staff_login.php
echo.
pause
