<?php

namespace App\Providers;

use App\Events\CheckForUpdates;
use App\Events\OpenStartupLog;
use App\Events\ShowAboutDialog;
use App\Services\ApplicationLaunchTracker;
use App\Services\StartupLog;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $startupLog = app(StartupLog::class);
        $startupLog->start();
        $startupLog->mark('Нативне меню та головне вікно ініціалізуються');
        Menu::create(
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
            Menu::make(
                Menu::label('Відкрити лог запуску')->event(OpenStartupLog::class),
                Menu::label('Перевірити оновлення')->event(CheckForUpdates::class),
                Menu::label('Про програму')->event(ShowAboutDialog::class),
            )->label('Help'),
        );

        Window::open()->width(1920)->height(1080);


        config()->set('serial-number.launch_count', app(ApplicationLaunchTracker::class)->registerLaunch());
        $startupLog->mark('Локальний стан запуску готовий');


        if (app()->environment('production') && config('nativephp.updater.enabled')) {
            Event::listen(CheckForUpdates::class, static fn (): mixed => AutoUpdater::checkForUpdates());
            Event::listen(UpdateAvailable::class, static fn (): mixed => AutoUpdater::downloadUpdate());
            Event::listen(UpdateDownloaded::class, static fn (): mixed => AutoUpdater::quitAndInstall());
            AutoUpdater::checkForUpdates();
        }

        $startupLog->mark('Laravel startup завершено');
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [];
    }
}
