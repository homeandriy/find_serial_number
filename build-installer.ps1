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
Push-Location $projectDirectory
try {
    & $php artisan native:build win x64 --no-interaction
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} finally { Pop-Location }
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
