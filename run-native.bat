@echo off
setlocal
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=F:\Tools\php-8_5\php.exe"
set "COMPOSER_PHAR=C:\Users\Andriy\.config\herd\bin\composer.phar"
if not exist "%PHP_EXE%" ( echo PHP 8.5 not found: %PHP_EXE% & exit /b 1 )
if not exist "%COMPOSER_PHAR%" ( echo Composer not found: %COMPOSER_PHAR% & exit /b 1 )
cd /d "%PROJECT_DIR%"
"%PHP_EXE%" "%COMPOSER_PHAR%" native:dev
