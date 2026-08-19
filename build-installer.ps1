$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$php = 'F:\Tools\php-8_5\php.exe'
$builder = Join-Path $projectDirectory 'vendor\nativephp\desktop\resources\electron\electron-builder.mjs'
if (-not (Test-Path -LiteralPath $php)) { throw "PHP 8.5 not found: $php" }
$builderContent = [IO.File]::ReadAllText($builder)
$versionToken = '$' + '{version}'
$extensionToken = '$' + '{ext}'
if (-not $builderContent.Contains('signAndEditExecutable: false')) {
    $builderContent = $builderContent.Replace('    win: {', '    win: {' + [Environment]::NewLine + '        signAndEditExecutable: false,')
}
if (-not $builderContent.Contains('Serial Vision Installer version')) {
    $nsisConfig = '    nsis: {' + [Environment]::NewLine + '        oneClick: false,' + [Environment]::NewLine + '        allowToChangeInstallationDirectory: true,' + [Environment]::NewLine + '        runAfterFinish: true,' + [Environment]::NewLine + "        license: join(process.env.APP_PATH, 'LICENSE.txt'),"
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
Write-Host "Installer created: $installer"
