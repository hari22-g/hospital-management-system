@echo off
echo Starting XAMPP Services...

REM Start XAMPP Control Panel
start "" "C:\xampp\xampp-control.exe"

REM Wait for XAMPP to start
timeout /t 5

REM Start Apache and MySQL via command line
cd /d C:\xampp
call apache_start.bat
timeout /t 2
call mysql_start.bat

REM Wait for services to fully start
timeout /t 5

REM Open the project in default browser
echo Opening Project in Browser...
start http://localhost/MedC/MedC/MedC/homepage.php

echo.
echo Project is running! 
echo Homepage: http://localhost/MedC/MedC/MedC/homepage.php
echo Admin Login: http://localhost/MedC/MedC/MedC/admin_login.php
echo.
pause
