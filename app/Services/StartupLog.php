<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

final class StartupLog
{
    private ?int $startedAt = null;

    public function start(): void
    {
        $this->startedAt = hrtime(true);
        $this->write('Запуск Laravel розпочато');
    }

    public function mark(string $message): void
    {
        $this->write($message);
    }

    public function path(): string
    {
        return storage_path('logs/startup.log');
    }

    private function write(string $message): void
    {
        File::ensureDirectoryExists(dirname($this->path()));

        $elapsed = $this->startedAt === null
            ? 'n/a'
            : number_format((hrtime(true) - $this->startedAt) / 1_000_000, 1, '.', '');

        File::append($this->path(), sprintf("[%s] [+%sms] %s\n", now()->format('Y-m-d H:i:s.v'), $elapsed, $message));
    }
}