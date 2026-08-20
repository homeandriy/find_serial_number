param(
    [string] $ProjectDirectory = (Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'
$windowApi = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\electron-plugin\dist\server\api\window.js'

if (-not (Test-Path -LiteralPath $windowApi)) {
    throw "NativePHP Electron window API was not found: $windowApi"
}

$source = [IO.File]::ReadAllText($windowApi)
$platformOnlyIcon = "(process.platform === 'linux' ? { icon: state.icon } : {})"

if ($source.Contains($platformOnlyIcon)) {
    $source = $source.Replace($platformOnlyIcon, '{ icon: state.icon }')
    [IO.File]::WriteAllText($windowApi, $source, [Text.UTF8Encoding]::new($false))
}

if (-not $source.Contains('{ icon: state.icon }')) {
    throw 'Unexpected NativePHP Electron window template; the application icon was not patched.'
}