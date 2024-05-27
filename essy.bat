@echo off
cd /d "%~dp0"  REM Navigate to the directory of the batch script

REM Start PHP server in the background
"C:\xampp\php\php.exe" server serve > nul 2>&1

REM Get machine's IP address
for /f "tokens=2 delims=:" %%i in ('ipconfig ^| findstr IPv4') do set ip=%%i

REM Start a browser to access the PHP server using the machine's IP address
start "http://%ip%:8000/"

REM Wait for a few seconds before exiting
timeout /t 2 /nobreak >nul
