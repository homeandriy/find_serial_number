@echo off
setlocal
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=F:\Tools\php-8_5\php.exe"
set "COMPOSER_PHAR=C:\Users\Andriy\.config\herd\bin\composer.phar"
if not exist "%PHP_EXE%" ( echo PHP 8.5 not found: %PHP_EXE% & exit /b 1 )
if not exist "%COMPOSER_PHAR%" ( echo Composer not found: %COMPOSER_PHAR% & exit /b 1 )
cd /d "%PROJECT_DIR%"
"%PHP_EXE%" "%COMPOSER_PHAR%" install --no-interaction --prefer-dist
if errorlevel 1 exit /b 1
"%PHP_EXE%" artisan native:install --force --no-interaction
if errorlevel 1 exit /b 1
echo Setup complete. Put images into "%PROJECT_DIR%images" and run run-native.bat.
