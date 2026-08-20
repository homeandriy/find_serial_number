@echo off
setlocal
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=F:\Tools\php-8_5\php.exe"
set "COMPOSER_PHAR=C:\Users\Andriy\.config\herd\bin\composer.phar"
if not exist "%PHP_EXE%" ( echo PHP 8.5 not found: %PHP_EXE% & exit /b 1 )
if not exist "%COMPOSER_PHAR%" ( echo Composer not found: %COMPOSER_PHAR% & exit /b 1 )
cd /d "%PROJECT_DIR%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%PROJECT_DIR%prepare-nativephp-electron.ps1"
if errorlevel 1 exit /b %errorlevel%
set "PATH=F:\Tools\php-8_5;%PATH%"
"%PHP_EXE%" "%COMPOSER_PHAR%" native:dev
