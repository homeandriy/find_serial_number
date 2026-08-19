@echo off
setlocal
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=F:\Tools\php-8_5\php.exe"
set "COMPOSER_PHAR=C:\Users\Andriy\.config\herd\bin\composer.phar"
set "TESSERACT_DIR=C:\Program Files\Tesseract-OCR"
if not exist "%PHP_EXE%" ( echo PHP 8.5 not found: %PHP_EXE% & exit /b 1 )
if not exist "%COMPOSER_PHAR%" ( echo Composer not found: %COMPOSER_PHAR% & exit /b 1 )
if not exist "%TESSERACT_DIR%\tesseract.exe" ( echo Install Tesseract OCR first: winget install --id UB-Mannheim.TesseractOCR --exact & exit /b 1 )
cd /d "%PROJECT_DIR%"
"%PHP_EXE%" "%COMPOSER_PHAR%" install --no-interaction --prefer-dist
if errorlevel 1 exit /b 1
if not exist "tessdata" mkdir tessdata
copy /Y "%TESSERACT_DIR%\tessdata\eng.traineddata" "tessdata\eng.traineddata" >nul
if not exist "tessdata\ukr.traineddata" curl.exe -fL -o "tessdata\ukr.traineddata" https://github.com/tesseract-ocr/tessdata_fast/raw/main/ukr.traineddata
if errorlevel 1 exit /b 1
"%PHP_EXE%" artisan native:install --force --no-interaction
if errorlevel 1 exit /b 1
echo Setup complete. Put images into "%PROJECT_DIR%images" and run run-native.bat.
