@echo off
rem Run the Laravel scheduler. Called by the "TanodLaravelScheduler" task every minute.
cd /d "%~dp0"
"C:\php-8.3.32-Win32-vs16-x64\php.exe" artisan schedule:run >> "storage\logs\schedule.log" 2>&1
