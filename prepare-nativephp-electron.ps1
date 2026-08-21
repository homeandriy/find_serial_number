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

$splashMarker = '// Serial Vision startup splash v3'
$legacySplashMarker = '// Serial Vision startup splash'

if (-not $mainSource.Contains($splashMarker) -and $mainSource.Contains($legacySplashMarker)) {
    $legacyStart = $mainSource.IndexOf($legacySplashMarker)
    $legacyEnd = $mainSource.IndexOf('/**', $legacyStart)

    if ($legacyStart -lt 0 -or $legacyEnd -lt 0) {
        throw 'Legacy Serial Vision splash patch could not be replaced safely.'
    }

    $mainSource = $mainSource.Substring(0, $legacyStart) + $mainSource.Substring($legacyEnd)
    $mainSource = $mainSource.Replace("import { app, BrowserWindow, nativeImage } from 'electron';", "import { app } from 'electron';")
    $mainSource = $mainSource.Replace("import { app, BrowserWindow } from 'electron';", "import { app } from 'electron';")
    $mainSource = [regex]::Replace($mainSource, "(?m)^import \{ pathToFileURL \} from 'url';\r?\n", '')
}

if (-not $mainSource.Contains($splashMarker)) {
    $mainSource = $mainSource.Replace("import { app } from 'electron';", "import { app, BrowserWindow, nativeImage } from 'electron';")
    $mainSource = $mainSource.Replace("import path from 'path';", "import path from 'path';`r`nimport { appendFileSync } from 'fs';")

    if (-not $mainSource.Contains("import { app, BrowserWindow, nativeImage } from 'electron';")) {
        throw 'Unexpected NativePHP Electron main template; BrowserWindow import was not patched.'
    }

    $splash = @"

// Serial Vision startup splash v3
const electronStartupStartedAt = Date.now();
const logElectronStartup = (message) => {
    try {
        const elapsed = (Date.now() - electronStartupStartedAt).toFixed(1);
        appendFileSync(path.join(app.getPath("userData"), "startup.log"), "[" + new Date().toISOString() + "] [electron-main pid=" + process.pid + "] [+" + elapsed + "ms] " + message + "\n");
    } catch (_) {
        // Startup diagnostics must never block the desktop application.
    }
};
logElectronStartup("Electron main entrypoint initialized");
let splashWindow = null;

app.whenReady().then(() => {
    logElectronStartup("Electron ready; creating splash window");

    splashWindow = new BrowserWindow({
        width: 540,
        height: 320,
        show: true,
        resizable: false,
        minimizable: false,
        maximizable: false,
        fullscreenable: false,
        autoHideMenuBar: true,
        title: 'Обладнання та дані',
        icon: defaultIcon,
        backgroundColor: '#0f172a',
    });

    const splashIcon = nativeImage.createFromPath(defaultIcon).toDataURL();
    const splashHtml = '<!doctype html><html lang="uk"><head><meta charset="utf-8"><style>body{margin:0;background:radial-gradient(circle at top left,#1e5b8e,#0f172a 62%);color:#f8fafc;font-family:Segoe UI,Arial,sans-serif;display:grid;place-items:center;height:100vh}.card{text-align:center}.logo{width:88px;height:88px;object-fit:contain;margin-bottom:22px}.title{font-size:28px;font-weight:700}.caption{margin-top:9px;color:#bfdbfe;font-size:16px}.loader{width:190px;height:5px;border-radius:999px;background:#243449;margin:30px auto 0;overflow:hidden}.loader:after{content:"";display:block;width:45%;height:100%;border-radius:inherit;background:#38bdf8;animation:loading 1.25s ease-in-out infinite}@keyframes loading{0%{transform:translateX(-110%)}100%{transform:translateX(330%)}}</style></head><body><div class="card"><img class="logo" src="' + splashIcon + '" alt="Serial Vision"><div class="title">Обладнання та дані</div><div class="caption">Запуск програми…</div><div class="loader"></div></div></body></html>';
    splashWindow.loadURL('data:text/html;charset=utf-8,' + encodeURIComponent(splashHtml));
    logElectronStartup("Splash content requested; NativePHP bootstrap starting");
    NativePHP.bootstrap(app, defaultIcon, phpBinary, certificate, appPath);
    logElectronStartup("NativePHP bootstrap invoked");
});

app.on('browser-window-created', (_, window) => {
    logElectronStartup("BrowserWindow created");
    if (!splashWindow || window === splashWindow) {
        return;
    }

    window.webContents.on('did-navigate', (_, url) => {
        logElectronStartup("BrowserWindow navigated: " + url);
        if (!url.startsWith('http://127.0.0.1') && !url.startsWith('http://localhost')) {
            return;
        }

        if (!splashWindow?.isDestroyed()) {
            logElectronStartup("Laravel window reached; closing splash");
            splashWindow.close();
        }

        splashWindow = null;
    });
});
"@
    $mainSource = $mainSource.Replace("const appPath = path.join(buildPath, 'app');", "const appPath = path.join(buildPath, 'app');" + $splash)
    $bootstrap = @"
/**
 * Turn on the lights for the NativePHP app.
 */
NativePHP.bootstrap(app, defaultIcon, phpBinary, certificate, appPath);
"@
    $mainSource = $mainSource.Replace($bootstrap, '')
    [IO.File]::WriteAllText($mainEntrypoint, $mainSource, [Text.UTF8Encoding]::new($false))
}

$splashSafetyMarker = '// Serial Vision startup splash safety v1'

if (-not $mainSource.Contains($splashSafetyMarker)) {
    $unsafeSplashCloseGuard = 'if (!splashWindow?.isDestroyed()) {'
    $safeSplashCloseGuard = 'if (splashWindow && !splashWindow.isDestroyed()) {'

    if (-not $mainSource.Contains($unsafeSplashCloseGuard)) {
        throw 'NativePHP splash close guard template was not found.'
    }

    $mainSource = $mainSource.Replace($unsafeSplashCloseGuard, $safeSplashCloseGuard)
    $mainSource = $mainSource.Replace(
        $splashMarker,
        $splashMarker + [Environment]::NewLine + $splashSafetyMarker
    )
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
        "const startupLogDirectory = join(app.getPath('appData'), 'obladnannia-ta-dani');"
        "mkdirSync(startupLogDirectory, { recursive: true });"
        "const startupLogPath = join(startupLogDirectory, 'startup.log');"
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
    $stableStartupLogPath = "const startupLogPath = join(startupLogDirectory, 'startup.log');"

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
$startupConfigMarker = '// Serial Vision precomputed startup config v1'

if (-not $phpRuntimeSource.Contains($startupConfigMarker)) {
    $startupConfigPatch = @"
function retrieveNativePHPConfig() {
    return __awaiter(this, void 0, void 0, function* () {
        const appPath = getAppPath();
        const precomputedConfigPath = join(appPath, 'nativephp-startup-config.json');

        if (app.isPackaged && existsSync(precomputedConfigPath)) {
            try {
                const stdout = readFileSync(precomputedConfigPath, 'utf8');
                JSON.parse(stdout);
                logStartup('NativePHP precomputed config loaded');
                return { stdout, stderr: '' };
            }
            catch (error) {
                logStartup('NativePHP precomputed config is invalid; falling back to PHP');
            }
        }

        logStartup('NativePHP config command started');
        const env = Object.assign(Object.assign({}, process.env), getDefaultEnvironmentVariables());
        const phpOptions = {
            cwd: appPath,
            env,
        };
        const command = ['artisan', 'native:config'];
        if (runningSecureBuild()) {
            command.unshift(join(appPath, 'build', '__nativephp_app_bundle'));
        }
        const result = yield promisify(execFile)(state.php, command, phpOptions);
        logStartup('NativePHP config command finished');
        return result;
    });
}
"@

    $phpRuntimeSource = [regex]::Replace(
        $phpRuntimeSource,
        '(?s)function retrieveNativePHPConfig\(\) \{.*?\n\}',
        $startupConfigPatch,
        1
    )

    if (-not $phpRuntimeSource.Contains($startupConfigMarker)) {
        $phpRuntimeSource = $phpRuntimeSource.Replace(
            'function retrieveNativePHPConfig() {',
            $startupConfigMarker + [Environment]::NewLine + 'function retrieveNativePHPConfig() {'
        )
    }

    if (-not $phpRuntimeSource.Contains("logStartup('NativePHP precomputed config loaded')")) {
        throw 'NativePHP startup config template was not patched.'
    }

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
$autoUpdaterApi = Join-Path $ProjectDirectory 'vendor\nativephp\desktop\resources\electron\electron-plugin\dist\server\api\autoUpdater.js'
if (-not (Test-Path -LiteralPath $autoUpdaterApi)) {
    throw "NativePHP Electron auto-updater API was not found: $autoUpdaterApi"
}

$autoUpdaterApiSource = [IO.File]::ReadAllText($autoUpdaterApi)
$updaterDiagnosticsMarker = '// Serial Vision updater diagnostics v1'

if (-not $autoUpdaterApiSource.Contains($updaterDiagnosticsMarker)) {
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "import electronUpdater from 'electron-updater';",
        "import { appendFileSync, mkdirSync } from 'fs';" + [Environment]::NewLine +
        "import { app } from 'electron';" + [Environment]::NewLine +
        "import { join } from 'path';" + [Environment]::NewLine +
        "import electronUpdater from 'electron-updater';"
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        'const router = express.Router();',
        @"
const router = express.Router();
$updaterDiagnosticsMarker
const updaterLogPath = join(app.getPath('appData'), 'obladnannia-ta-dani', 'startup.log');
let updateCheckTimeout = null;
const logUpdater = (message) => {
    try {
        mkdirSync(join(app.getPath('appData'), 'obladnannia-ta-dani'), { recursive: true });
        appendFileSync(updaterLogPath, '[' + new Date().toISOString() + '] [electron-updater] ' + message + '\n');
    }
    catch (_) {
        // Updater diagnostics must never affect update delivery.
    }
};
const finishUpdateCheck = () => {
    if (updateCheckTimeout) {
        clearTimeout(updateCheckTimeout);
        updateCheckTimeout = null;
    }
};
"@
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "router.post('/check-for-updates', (req, res) => {" + [Environment]::NewLine + '    autoUpdater.checkForUpdates();',
        @"
router.post('/check-for-updates', (req, res) => {
    finishUpdateCheck();
    logUpdater('Запит перевірки отримано від Laravel; Electron updater готує HTTPS-запит до GitHub Releases homeandriy/find_serial_number.');
    updateCheckTimeout = setTimeout(() => {
        updateCheckTimeout = null;
        logUpdater('Відповідь GitHub Releases не отримана за 30 с; перевірка зависла або заблокована мережею.');
    }, 30000);
    autoUpdater.checkForUpdates();
"@
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "autoUpdater.addListener('checking-for-update', () => {" + [Environment]::NewLine,
        "autoUpdater.addListener('checking-for-update', () => {" + [Environment]::NewLine + "    logUpdater('HTTPS-запит надіслано; очікується метадані релізу (latest.yml).');" + [Environment]::NewLine
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "autoUpdater.addListener('update-available', (event) => {" + [Environment]::NewLine,
        "autoUpdater.addListener('update-available', (event) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('GitHub відповів: доступна версія ' + event.version + '; починається завантаження.');" + [Environment]::NewLine
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "autoUpdater.addListener('update-not-available', (event) => {" + [Environment]::NewLine,
        "autoUpdater.addListener('update-not-available', (event) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('GitHub відповів: новішої версії немає; поточна ' + event.version + '.');" + [Environment]::NewLine
    )
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace(
        "autoUpdater.addListener('error', (error) => {" + [Environment]::NewLine,
        "autoUpdater.addListener('error', (error) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('Помилка перевірки: ' + error.name + ': ' + error.message);" + [Environment]::NewLine
    )

    if (-not $autoUpdaterApiSource.Contains($updaterDiagnosticsMarker)) {
        throw 'NativePHP updater diagnostics template was not patched.'
    }

    [IO.File]::WriteAllText($autoUpdaterApi, $autoUpdaterApiSource, [Text.UTF8Encoding]::new($false))
}


$updaterEventDiagnosticsMarker = '// Serial Vision updater event diagnostics v1'

if (-not $autoUpdaterApiSource.Contains($updaterEventDiagnosticsMarker)) {
    $autoUpdaterApiSource = [regex]::Replace(
        $autoUpdaterApiSource,
        "autoUpdater\.addListener\('checking-for-update', \(\) => \{\r?\n",
        "autoUpdater.addListener('checking-for-update', () => {" + [Environment]::NewLine + "    " + $updaterEventDiagnosticsMarker + [Environment]::NewLine + "    logUpdater('HTTPS-запит надіслано; очікується метадані релізу (latest.yml).');" + [Environment]::NewLine,
        1
    )
    $autoUpdaterApiSource = [regex]::Replace(
        $autoUpdaterApiSource,
        "autoUpdater\.addListener\('update-available', \(event\) => \{\r?\n",
        "autoUpdater.addListener('update-available', (event) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('GitHub відповів: доступна версія ' + event.version + '; починається завантаження.');" + [Environment]::NewLine,
        1
    )
    $autoUpdaterApiSource = [regex]::Replace(
        $autoUpdaterApiSource,
        "autoUpdater\.addListener\('update-not-available', \(event\) => \{\r?\n",
        "autoUpdater.addListener('update-not-available', (event) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('GitHub відповів: новішої версії немає; поточна ' + event.version + '.');" + [Environment]::NewLine,
        1
    )
    $autoUpdaterApiSource = [regex]::Replace(
        $autoUpdaterApiSource,
        "autoUpdater\.addListener\('error', \(error\) => \{\r?\n",
        "autoUpdater.addListener('error', (error) => {" + [Environment]::NewLine + "    finishUpdateCheck();" + [Environment]::NewLine + "    logUpdater('Помилка перевірки: ' + error.name + ': ' + error.message);" + [Environment]::NewLine,
        1
    )

    if (-not $autoUpdaterApiSource.Contains($updaterEventDiagnosticsMarker)) {
        throw 'NativePHP updater event listeners could not be instrumented.'
    }

    [IO.File]::WriteAllText($autoUpdaterApi, $autoUpdaterApiSource, [Text.UTF8Encoding]::new($false))
}

$updaterRequestDiagnosticsMarker = '// Serial Vision updater request diagnostics v1'
$autoUpdaterApiMarker = '// Serial Vision updater rejection bridge v1'

if (-not $autoUpdaterApiSource.Contains($autoUpdaterApiMarker)) {
    $autoUpdaterCheck = @"
$autoUpdaterApiMarker
    $updaterRequestDiagnosticsMarker
    finishUpdateCheck();
    logUpdater('Запит перевірки отримано від Laravel; Electron updater готує HTTPS-запит до GitHub Releases homeandriy/find_serial_number.');
    updateCheckTimeout = setTimeout(() => {
        updateCheckTimeout = null;
        logUpdater('Відповідь GitHub Releases не отримана за 30 с; перевірка зависла або заблокована мережею.');
    }, 30000);
    void autoUpdater.checkForUpdates().catch((error) => {
        notifyLaravel('events', {
            event: '\\Native\\Desktop\\Events\\AutoUpdater\\Error',
            payload: {
                name: error.name,
                message: error.message,
                stack: error.stack,
            },
        });
    });
"@
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace('    autoUpdater.checkForUpdates();', $autoUpdaterCheck)

    if (-not $autoUpdaterApiSource.Contains($autoUpdaterApiMarker)) {
        throw 'Unexpected NativePHP auto-updater API template.'
    }

    [IO.File]::WriteAllText($autoUpdaterApi, $autoUpdaterApiSource, [Text.UTF8Encoding]::new($false))
}
if (-not $autoUpdaterApiSource.Contains($updaterRequestDiagnosticsMarker)) {
    $legacyUpdaterBridge = @"
$autoUpdaterApiMarker
    void autoUpdater.checkForUpdates().catch((error) => {
        notifyLaravel('events', {
            event: '\\Native\\Desktop\\Events\\AutoUpdater\\Error',
            payload: {
                name: error.name,
                message: error.message,
                stack: error.stack,
            },
        });
    });
"@
    $instrumentedUpdaterBridge = @"
$autoUpdaterApiMarker
    $updaterRequestDiagnosticsMarker
    finishUpdateCheck();
    logUpdater('Запит перевірки отримано від Laravel; Electron updater готує HTTPS-запит до GitHub Releases homeandriy/find_serial_number.');
    updateCheckTimeout = setTimeout(() => {
        updateCheckTimeout = null;
        logUpdater('Відповідь GitHub Releases не отримана за 30 с; перевірка зависла або заблокована мережею.');
    }, 30000);
    void autoUpdater.checkForUpdates().catch((error) => {
        notifyLaravel('events', {
            event: '\\Native\\Desktop\\Events\\AutoUpdater\\Error',
            payload: {
                name: error.name,
                message: error.message,
                stack: error.stack,
            },
        });
    });
"@
    $autoUpdaterApiSource = $autoUpdaterApiSource.Replace($legacyUpdaterBridge, $instrumentedUpdaterBridge)

    if (-not $autoUpdaterApiSource.Contains($updaterRequestDiagnosticsMarker)) {
        throw 'NativePHP updater request bridge could not be instrumented.'
    }

    [IO.File]::WriteAllText($autoUpdaterApi, $autoUpdaterApiSource, [Text.UTF8Encoding]::new($false))
}
