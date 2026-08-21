$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$localPhp = 'F:\Tools\php-8_5\php.exe'
$php = if (Test-Path -LiteralPath $localPhp) {
    $localPhp
} else {
    (Get-Command php -ErrorAction Stop).Source
}
$builder = Join-Path $projectDirectory 'vendor\nativephp\desktop\resources\electron\electron-builder.mjs'
if (-not (Test-Path -LiteralPath $php)) { throw "PHP 8.5 not found: $php" }
$ocrArchive = Join-Path $projectDirectory 'resources\ocr\tesseract-runtime.zip'
$ocrDirectory = Join-Path $projectDirectory 'extras\tesseract'
if (-not (Test-Path -LiteralPath (Join-Path $ocrDirectory 'tesseract.exe'))) {
    if (-not (Test-Path -LiteralPath $ocrArchive)) { throw "Tesseract runtime archive not found: $ocrArchive" }
    New-Item -ItemType Directory -Force -Path $ocrDirectory | Out-Null
    Expand-Archive -LiteralPath $ocrArchive -DestinationPath $ocrDirectory -Force
}
$tessdataSource = Join-Path $projectDirectory 'resources\ocr\tessdata'
$tessdataTarget = Join-Path $ocrDirectory 'tessdata'
if (-not (Test-Path -LiteralPath (Join-Path $tessdataSource 'eng.traineddata'))) {
    throw "Tesseract language data not found: $tessdataSource"
}
New-Item -ItemType Directory -Force -Path $tessdataTarget | Out-Null
Copy-Item -Path (Join-Path $tessdataSource '*') -Destination $tessdataTarget -Recurse -Force
& (Join-Path $projectDirectory 'prepare-nativephp-electron.ps1') -ProjectDirectory $projectDirectory
$licenseSource = Join-Path $projectDirectory 'LICENSE.txt'
$nsisLicense = Join-Path $projectDirectory 'LICENSE.nsis.txt'

if (-not (Test-Path -LiteralPath $licenseSource)) {
    throw "License source was not found: $licenseSource"
}

$licenseText = [IO.File]::ReadAllText($licenseSource, [Text.UTF8Encoding]::new($false))
# NSIS Unicode installers expect the license file in UTF-8. A BOM makes the
# encoding explicit for the installer UI and prevents Cyrillic mojibake.
[IO.File]::WriteAllText($nsisLicense, $licenseText, [Text.UTF8Encoding]::new($true))
$builderContent = [IO.File]::ReadAllText($builder)
$builderContent = $builderContent.Replace("license: join(process.env.APP_PATH, 'LICENSE.txt'),", "license: join(process.env.APP_PATH, 'LICENSE.nsis.txt'),")
$versionToken = '$' + '{version}'
$extensionToken = '$' + '{ext}'
$updaterFlagSource = "const updaterEnabled = process.env.NATIVEPHP_UPDATER_ENABLED === 'true';"
$updaterFlagTarget = "const updaterEnabled = process.env.NATIVEPHP_UPDATER_ENABLED === 'true' || Boolean(process.env.NATIVEPHP_UPDATER_CONFIG);"
$builderContent = $builderContent.Replace($updaterFlagSource, $updaterFlagTarget)
if (-not $builderContent.Contains($updaterFlagTarget)) {
    throw 'Unexpected NativePHP updater template.'
}
$builderContent = $builderContent.Replace('        signAndEditExecutable: false,' + [Environment]::NewLine, '')
if (-not $builderContent.Contains("icon: join(process.env.APP_PATH, 'public', 'icon.ico'),")) {
    $builderContent = $builderContent.Replace('    win: {', "    win: {" + [Environment]::NewLine + "        icon: join(process.env.APP_PATH, 'public', 'icon.ico'),")
}
if (-not $builderContent.Contains('Serial Vision Installer version')) {
    $nsisConfig = '    nsis: {' + [Environment]::NewLine + '        oneClick: false,' + [Environment]::NewLine + '        allowToChangeInstallationDirectory: true,' + [Environment]::NewLine + '        runAfterFinish: true,' + [Environment]::NewLine + "        license: join(process.env.APP_PATH, 'LICENSE.nsis.txt'),"
    $builderContent = $builderContent.Replace('    nsis: {', $nsisConfig)
    $oldArtifact = "artifactName: appName + '-$versionToken-setup.$extensionToken',"
    $newArtifact = "artifactName: 'Serial Vision Installer version $versionToken.$extensionToken',"
    $builderContent = $builderContent.Replace($oldArtifact, $newArtifact)
}
if (-not $builderContent.Contains('Serial Vision Installer version')) { throw 'Unexpected NativePHP electron-builder template.' }
[IO.File]::WriteAllText($builder, $builderContent, [Text.UTF8Encoding]::new($false))
$env:Path = 'F:\Tools\php-8_5;' + $env:Path
# NativePHP's builder template enables the updater only when this value is
# literally 'true'. The package command does not forward the config default.
$env:NATIVEPHP_UPDATER_ENABLED = 'true'
# Serial Vision precomputed Electron startup configuration
# NativePHP normally boots Laravel once only to return config('nativephp'). On
# cold Windows machines this can spend tens of seconds in Defender scanning PHP
# source. Generate the exact production config during packaging instead.
$startupConfigFile = Join-Path $projectDirectory 'nativephp-startup-config.json'
if (Test-Path -LiteralPath $startupConfigFile) {
    throw "Temporary startup config already exists: $startupConfigFile"
}
$startupConfigPhp = 'require ''vendor/autoload.php''; $app = require ''bootstrap/app.php''; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); echo json_encode(config(''nativephp''));'
$startupConfigOutput = & $php -r $startupConfigPhp
if ($LASTEXITCODE -ne 0) {
    throw "Could not generate the NativePHP startup configuration (exit code $LASTEXITCODE)."
}
$startupConfigJson = ($startupConfigOutput -join [Environment]::NewLine).Trim()
try {
    $null = $startupConfigJson | ConvertFrom-Json -ErrorAction Stop
} catch {
    throw "Generated NativePHP startup configuration is not valid JSON: $($_.Exception.Message)"
}
[IO.File]::WriteAllText($startupConfigFile, $startupConfigJson, [Text.UTF8Encoding]::new($false))
Push-Location $projectDirectory
try {
    & $php artisan native:build win x64 --no-interaction
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} finally {
    Pop-Location
    if (Test-Path -LiteralPath $startupConfigFile) {
        Remove-Item -LiteralPath $startupConfigFile -Force
    }
}
$version = (Get-Content -LiteralPath (Join-Path $projectDirectory 'VERSION') -Raw).Trim()
$installer = Join-Path $projectDirectory ("nativephp\electron\dist\Serial Vision Installer version $version.exe")
if (-not (Test-Path -LiteralPath $installer)) { throw "Installer was not created: $installer" }
$blockmap = "$installer.blockmap"
if (-not (Test-Path -LiteralPath $blockmap)) { throw "Installer blockmap was not created: $blockmap" }

$installerInfo = Get-Item -LiteralPath $installer
$stream = [IO.File]::OpenRead($installer)
try {
    $sha512 = [Convert]::ToBase64String([Security.Cryptography.SHA512]::Create().ComputeHash($stream))
} finally {
    $stream.Dispose()
}

$latestYml = @"
version: $version
files:
  - url: $($installerInfo.Name)
    sha512: $sha512
    size: $($installerInfo.Length)
path: $($installerInfo.Name)
sha512: $sha512
releaseDate: '$((Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ss.fffZ'))'
"@
[IO.File]::WriteAllText((Join-Path $projectDirectory 'nativephp\electron\dist\latest.yml'), $latestYml, [Text.UTF8Encoding]::new($false))

Write-Host "Installer created: $installer"
Write-Host "Updater metadata created: $(Join-Path $projectDirectory 'nativephp\electron\dist\latest.yml')"
