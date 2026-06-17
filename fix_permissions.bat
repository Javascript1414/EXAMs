@echo off
REM Fix permissions for upload directories

echo Fixing upload directory permissions...

REM Grant NETWORK SERVICE full permissions
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles" /grant "NETWORK SERVICE":(OI)(CI)F /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\profile_photos" /grant "NETWORK SERVICE":(OI)(CI)F /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\cover_photos" /grant "NETWORK SERVICE":(OI)(CI)F /T /Q

REM Grant SYSTEM full permissions
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles" /grant "SYSTEM":(OI)(CI)F /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\profile_photos" /grant "SYSTEM":(OI)(CI)F /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\cover_photos" /grant "SYSTEM":(OI)(CI)F /T /Q

REM Grant Users Modify permissions
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles" /grant "Users":(OI)(CI)M /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\profile_photos" /grant "Users":(OI)(CI)M /T /Q
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\cover_photos" /grant "Users":(OI)(CI)M /T /Q

echo.
echo Permissions updated successfully!
echo.

REM Verify
echo Verifying permissions:
icacls "C:\xampp\htdocs\EXAMs\uploads\profiles\profile_photos"

pause
