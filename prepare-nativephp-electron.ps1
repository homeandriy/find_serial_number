param(
    [string] $ProjectDirectory = (Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'
$windowApi = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\electron-plugin\dist\server\api\window.js'
$mainEntrypoint = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\src\main\index.js'

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
if (-not (Test-Path -LiteralPath $mainEntrypoint)) {
    throw "NativePHP Electron main entrypoint was not found: $mainEntrypoint"
}

$mainSource = [IO.File]::ReadAllText($mainEntrypoint)
$splashMarker = '// Serial Vision startup splash'

if (-not $mainSource.Contains($splashMarker)) {
    $mainSource = $mainSource.Replace("import { app } from 'electron';", "import { app, BrowserWindow } from 'electron';")
    $mainSource = $mainSource.Replace("import path from 'path';", "import path from 'path';`r`nimport { pathToFileURL } from 'url';")

    if (-not $mainSource.Contains("import { app, BrowserWindow } from 'electron';")) {
        throw 'Unexpected NativePHP Electron main template; BrowserWindow import was not patched.'
    }

    $splash = @"

// Serial Vision startup splash
let splashWindow = null;

app.whenReady().then(() => {
    if (process.env.NODE_ENV !== 'production') {
        return;
    }

    splashWindow = new BrowserWindow({
        width: 540,
        height: 320,
        show: false,
        resizable: false,
        minimizable: false,
        maximizable: false,
        fullscreenable: false,
        autoHideMenuBar: true,
        title: 'Обладнання та дані',
        icon: defaultIcon,
        backgroundColor: '#0f172a',
    });

    const splashHtml = '<!doctype html><html lang="uk"><head><meta charset="utf-8"><style>body{margin:0;background:radial-gradient(circle at top left,#1e5b8e,#0f172a 62%);color:#f8fafc;font-family:Segoe UI,Arial,sans-serif;display:grid;place-items:center;height:100vh}.card{text-align:center}.logo{width:88px;height:88px;object-fit:contain;margin-bottom:22px}.title{font-size:28px;font-weight:700}.caption{margin-top:9px;color:#bfdbfe;font-size:16px}.loader{width:190px;height:5px;border-radius:999px;background:#243449;margin:30px auto 0;overflow:hidden}.loader:after{content:"";display:block;width:45%;height:100%;border-radius:inherit;background:#38bdf8;animation:loading 1.25s ease-in-out infinite}@keyframes loading{0%{transform:translateX(-110%)}100%{transform:translateX(330%)}}</style></head><body><div class="card"><img class="logo" src="' + pathToFileURL(defaultIcon).href + '" alt="Serial Vision"><div class="title">Обладнання та дані</div><div class="caption">Запуск програми…</div><div class="loader"></div></div></body></html>';
    splashWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(splashHtml));
    splashWindow.once('ready-to-show', () => splashWindow?.show());
});

app.on('browser-window-created', (_, window) => {
    if (!splashWindow || window === splashWindow) {
        return;
    }

    window.once('ready-to-show', () => {
        if (!splashWindow?.isDestroyed()) {
            splashWindow.destroy();
        }

        splashWindow = null;
    });
});
"@
    $mainSource = $mainSource.Replace("const appPath = path.join(buildPath, 'app');", "const appPath = path.join(buildPath, 'app');" + $splash)
    [IO.File]::WriteAllText($mainEntrypoint, $mainSource, [Text.UTF8Encoding]::new($false))
}
