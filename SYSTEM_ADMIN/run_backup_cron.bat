@echo off
REM PIMS Backup Cron Trigger
REM This script runs the PHP cron handler for PIMS backups.
REM You can schedule this .bat file in Windows Task Scheduler to run every hour.

SET PHP_PATH=C:\xampp\php\php.exe
SET CRON_SCRIPT=%~dp0cron_handler.php

if exist "%PHP_PATH%" (
    "%PHP_PATH%" "%CRON_SCRIPT%"
) else (
    echo PHP not found at %PHP_PATH%. Please check your XAMPP installation path.
    php "%CRON_SCRIPT%"
)

pause
