<?php

namespace App\Providers;

use App\Services\ApplicationLaunchTracker;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Menu::create(
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
            Menu::label('Help')->submenu(Menu::about('Про програму')),
        );
        Window::open()->width(1920)->height(1080);

        if (app()->environment('production')) {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        }

        config()->set('serial-number.launch_count', app(ApplicationLaunchTracker::class)->registerLaunch());

        if (app()->environment('production') && config('nativephp.updater.enabled')) {
            Event::listen(UpdateAvailable::class, static fn (): mixed => AutoUpdater::downloadUpdate());
            Event::listen(UpdateDownloaded::class, static fn (): mixed => AutoUpdater::quitAndInstall());
            AutoUpdater::checkForUpdates();
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
