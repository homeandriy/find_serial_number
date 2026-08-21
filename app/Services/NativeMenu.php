<?php

namespace App\Services;

use App\Events\CheckForUpdates;
use App\Events\OpenStartupLog;
use App\Events\ShowAboutDialog;
use Native\Desktop\Facades\Menu;

final class NativeMenu
{
    public function register(): void
    {
        Menu::create(
            Menu::file(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
            Menu::make(
                Menu::label(__('ui.menu_open_startup_log'))->event(OpenStartupLog::class),
                Menu::label(__('ui.menu_check_updates'))->event(CheckForUpdates::class),
                Menu::label(__('ui.menu_about'))->event(ShowAboutDialog::class),
            )->label(__('ui.menu_help')),
        );
    }
}
