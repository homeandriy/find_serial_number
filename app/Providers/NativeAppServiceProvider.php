<?php

namespace App\Providers;

use App\Events\CheckForUpdates;
use App\Events\OpenStartupLog;
use App\Events\ShowAboutDialog;
use App\Services\ApplicationLaunchTracker;
use App\Services\ApplicationLocale;
use App\Services\NativeMenu;
use App\Services\StartupLog;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Events\AutoUpdater\CheckingForUpdate;
use Native\Desktop\Events\AutoUpdater\Error;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Events\AutoUpdater\UpdateNotAvailable;
use Native\Desktop\Facades\AutoUpdater;
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
        app()->setLocale(app(ApplicationLocale::class)->current());
        app(NativeMenu::class)->register();

        $startupLog->mark('Розширений лог запуску: меню готове; відкривається головне вікно');
        Window::open()->width(1920)->height(1080);
        $startupLog->mark('Головне вікно передано NativePHP');


        $startupLog->mark('Ініціалізація локального стану розпочата');
        config()->set('serial-number.launch_count', app(ApplicationLaunchTracker::class)->registerLaunch());
        $startupLog->mark('Локальний стан запуску готовий');


        $startupLog->mark('Перевірка оновлень планується у фоновому режимі');

        if (app()->environment('production') && config('nativephp.updater.enabled')) {
            Event::listen(CheckForUpdates::class, static function () use ($startupLog): void {
                $startupLog->mark('Оновлення: користувач запустив ручну перевірку; команда передана Electron updater для GitHub Releases homeandriy/find_serial_number');
                AutoUpdater::checkForUpdates();
            });
            Event::listen(CheckingForUpdate::class, static function () use ($startupLog): void {
                $startupLog->mark('Оновлення: Electron updater почав HTTPS-перевірку GitHub Releases; очікується відповідь сервера');
            });
            Event::listen(UpdateNotAvailable::class, static function (UpdateNotAvailable $event) use ($startupLog): void {
                $startupLog->mark("Оновлення: новішої версії немає; поточна {$event->version}");
            });
            Event::listen(UpdateAvailable::class, static function (UpdateAvailable $event) use ($startupLog): void {
                $startupLog->mark("Оновлення: доступна версія {$event->version}; завантаження розпочато");
                AutoUpdater::downloadUpdate();
            });
            Event::listen(UpdateDownloaded::class, static function (UpdateDownloaded $event) use ($startupLog): void {
                $startupLog->mark("Оновлення: версію {$event->version} завантажено; перезапуск для встановлення");
                AutoUpdater::quitAndInstall();
            });
            Event::listen(Error::class, static function (Error $event) use ($startupLog): void {
                $startupLog->mark("Оновлення: помилка {$event->name}: {$event->message}");
            });
            $startupLog->mark('Оновлення: автоматична перевірка передана Electron updater для GitHub Releases homeandriy/find_serial_number');
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
