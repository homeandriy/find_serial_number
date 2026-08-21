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
$userDataMarker = '// Serial Vision stable user data path'

if (-not $mainSource.Contains($userDataMarker)) {
    $userDataSnippet = @"

// Serial Vision stable user data path
app.setPath('userData', path.join(app.getPath('appData'), 'obladnannia-ta-dani'));
"@
    $mainSource = $mainSource.Replace("import path from 'path';", "import path from 'path';" + $userDataSnippet)
    [IO.File]::WriteAllText($mainEntrypoint, $mainSource, [Text.UTF8Encoding]::new($false))
}

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

$phpRuntime = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\electron-plugin\dist\server\php.js'
if (-not (Test-Path -LiteralPath $phpRuntime)) {
    throw "NativePHP Electron PHP runtime was not found: $phpRuntime"
}

$phpRuntimeSource = [IO.File]::ReadAllText($phpRuntime)
$startupMarker = '// Serial Vision safe startup v2'

if (-not $phpRuntimeSource.Contains($startupMarker)) {
    $phpRuntimeSource = $phpRuntimeSource.Replace("import { existsSync, mkdirSync, readFileSync, statSync, writeFileSync } from 'fs';", "import { appendFileSync, existsSync, mkdirSync, readFileSync, statSync, writeFileSync } from 'fs';")
    $phpRuntimeSource = [regex]::Replace($phpRuntimeSource, 'function shouldOptimize\([^)]*\) \{\s*[^}]*\}', "function shouldOptimize() {`n    return false;`n}")
    $phpRuntimeSource = $phpRuntimeSource.Replace('if (shouldOptimize()) {', 'if (shouldOptimize(store)) {')

    if (-not $phpRuntimeSource.Contains('function shouldOptimize()')) {
        throw 'Unexpected NativePHP optimize template.'
    }

    $runtimeSnippet = @(
        "mkdirpSync(join(storagePath, 'framework', 'testing'));"
        $startupMarker
        "const startupLogPath = join(app.getPath('userData'), 'startup.log');"
        'const logStartup = (message) => appendFileSync(startupLogPath, `[${new Date().toISOString()}] [electron] ${message}\n`);'
        "logStartup('Electron runtime initialized');"
    ) -join [Environment]::NewLine
    $phpRuntimeSource = $phpRuntimeSource.Replace("mkdirpSync(join(storagePath, 'framework', 'testing'));", $runtimeSnippet)
    $phpRuntimeSource = [regex]::Replace($phpRuntimeSource, "if \(shouldOptimize\(store\)\) \{\s*console\.log\('Caching view and routes\.\.\.'\);", "if (shouldOptimize(store)) {`r`n        logStartup('NativePHP optimize started');`r`n        console.log('Caching view and routes...');")
    $phpRuntimeSource = $phpRuntimeSource.Replace("store.set('optimized_version', app.getVersion());", "store.set('optimized_version', app.getVersion());`n            logStartup('NativePHP optimize finished');")
    $phpRuntimeSource = [regex]::Replace($phpRuntimeSource, "if \(shouldMigrateDatabase\(store\)\) \{\s*console\.log\('Migrating database\.\.\.'\);", "if (shouldMigrateDatabase(store)) {`r`n        logStartup('NativePHP migration started');`r`n        console.log('Migrating database...');")
    $phpRuntimeSource = $phpRuntimeSource.Replace("store.set('migrated_version', app.getVersion());", "store.set('migrated_version', app.getVersion());`n            logStartup('NativePHP migration finished');")
    $phpRuntimeSource = [regex]::Replace($phpRuntimeSource, "console\.log\('Starting PHP server\.\.\.'\);\s*const phpPort", "logStartup('PHP server starting');`r`n    console.log('Starting PHP server...');`r`n    const phpPort")

    if (-not $phpRuntimeSource.Contains("logStartup('PHP server starting')")) {
        throw 'Unexpected NativePHP PHP server template.'
    }

    [IO.File]::WriteAllText($phpRuntime, $phpRuntimeSource, [Text.UTF8Encoding]::new($false))
}
$startupLogLocationMarker = '// Serial Vision startup log path v1'

if (-not $phpRuntimeSource.Contains($startupLogLocationMarker)) {
    $legacyStartupLogPath = "const startupLogPath = join(storagePath, 'logs', 'startup.log');"
    $stableStartupLogPath = "const startupLogPath = join(app.getPath('userData'), 'startup.log');"

    if ($phpRuntimeSource.Contains($legacyStartupLogPath)) {
        $phpRuntimeSource = $phpRuntimeSource.Replace($legacyStartupLogPath, $startupLogLocationMarker + [Environment]::NewLine + $stableStartupLogPath)
    } elseif ($phpRuntimeSource.Contains($stableStartupLogPath)) {
        $phpRuntimeSource = $phpRuntimeSource.Replace($stableStartupLogPath, $startupLogLocationMarker + [Environment]::NewLine + $stableStartupLogPath)
    } else {
        throw 'NativePHP startup log path template was not found.'
    }

    [IO.File]::WriteAllText($phpRuntime, $phpRuntimeSource, [Text.UTF8Encoding]::new($false))
}
$diagnosticMarker = '// Serial Vision startup diagnostics v1'

if (-not $phpRuntimeSource.Contains($diagnosticMarker)) {
    if (-not $phpRuntimeSource.Contains($startupMarker)) {
        throw 'NativePHP startup base patch was not applied.'
    }

    $newline = [Environment]::NewLine
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        "const command = ['artisan', 'native:php-ini'];",
        ($diagnosticMarker + $newline + "        logStartup('NativePHP PHP INI command started');" + $newline + "        const command = ['artisan', 'native:php-ini'];")
    )
    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        'return yield promisify\(execFile\)\(state\.php, command, phpOptions\);',
        ("const result = yield promisify(execFile)(state.php, command, phpOptions);" + $newline + "        logStartup('NativePHP PHP INI command finished');" + $newline + '        return result;'),
        1
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        "const command = ['artisan', 'native:config'];",
        ("logStartup('NativePHP config command started');" + $newline + "        const command = ['artisan', 'native:config'];")
    )
    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        'return yield promisify\(execFile\)\(state\.php, command, phpOptions\);',
        ("const result = yield promisify(execFile)(state.php, command, phpOptions);" + $newline + "        logStartup('NativePHP config command finished');" + $newline + '        return result;'),
        1
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        "console.log('Starting PHP server...', `${state.php} artisan serve`, appPath, phpIniSettings);",
        ("logStartup('NativePHP serve application started');" + $newline + "        console.log('Starting PHP server...', `${state.php} artisan serve`, appPath, phpIniSettings);")
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        'ensureAppFoldersAreAvailable();',
        ("logStartup('NativePHP storage preparation started');" + $newline + '        ensureAppFoldersAreAvailable();' + $newline + "        logStartup('NativePHP storage preparation finished');")
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        "logStartup('PHP server starting');",
        ("logStartup('PHP server preparation started');" + $newline + "    logStartup('PHP server starting');")
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        'const phpPort = yield getPhpPort();',
        ("logStartup('PHP port selection started');" + $newline + '    const phpPort = yield getPhpPort();' + $newline + "        logStartup('PHP port selection finished');")
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        'const phpServer = callPhp([''-S'', `127.0.0.1:${phpPort}`, serverPath], {',
        ("logStartup('PHP server process started');" + $newline + '        const phpServer = callPhp([''-S'', `127.0.0.1:${phpPort}`, serverPath], {')
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        "console.log('PHP Server started on port: ', port);",
        ("console.log('PHP Server started on port: ', port);" + $newline + "                    logStartup('PHP server is listening');")
    )

    if (-not $phpRuntimeSource.Contains("logStartup('PHP server is listening')")) {
        throw 'NativePHP diagnostics patch did not apply.'
    }

    [IO.File]::WriteAllText($phpRuntime, $phpRuntimeSource, [Text.UTF8Encoding]::new($false))
}
$diagnosticCorrectionMarker = '// Serial Vision startup diagnostics v2'

if (-not $phpRuntimeSource.Contains($diagnosticCorrectionMarker)) {
    $newline = [Environment]::NewLine
    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        "(?s)(logStartup\('NativePHP config command started'\);.*?logStartup\(')NativePHP PHP INI command finished('\);)",
        '$1NativePHP config command finished$2',
        1
    )
    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        "(?m)^\s*logStartup\('PHP server preparation started'\);\r?\n\s*logStartup\('PHP server starting'\);\r?\n\s*logStartup\('PHP server preparation started'\);\r?\n\s*logStartup\('PHP server starting'\);",
        ("        logStartup('PHP server preparation started');" + $newline + "        logStartup('PHP server starting');"),
        1
    )
    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        "(?m)^(\s*)logStartup\('NativePHP migration finished'\);\r?\n\s*logStartup\('NativePHP migration finished'\);",
        '$1logStartup(''NativePHP migration finished'');',
        1
    )
    $phpRuntimeSource = $phpRuntimeSource.Replace(
        '// Serial Vision startup diagnostics v1',
        '// Serial Vision startup diagnostics v1' + $newline + $diagnosticCorrectionMarker
    )

    [IO.File]::WriteAllText($phpRuntime, $phpRuntimeSource, [Text.UTF8Encoding]::new($false))
}
$updaterRuntime = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\electron-plugin\dist\index.js'
if (-not (Test-Path -LiteralPath $updaterRuntime)) {
    throw "NativePHP Electron updater runtime was not found: $updaterRuntime"
}

$updaterRuntimeSource = [IO.File]::ReadAllText($updaterRuntime)
$updaterMarker = '// Serial Vision updater starts after Laravel boot'

if (-not $updaterRuntimeSource.Contains($updaterMarker)) {
    $updaterRuntimeSource = $updaterRuntimeSource.Replace('            autoUpdater.checkForUpdatesAndNotify();', "            $updaterMarker")

    if (-not $updaterRuntimeSource.Contains($updaterMarker)) {
        throw 'Unexpected NativePHP Electron updater template.'
    }

    [IO.File]::WriteAllText($updaterRuntime, $updaterRuntimeSource, [Text.UTF8Encoding]::new($false))
}