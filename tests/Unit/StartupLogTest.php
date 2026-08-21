<?php

namespace Tests\Unit;

use App\Services\StartupLog;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class StartupLogTest extends TestCase
{
    public function test_it_writes_timestamped_startup_markers(): void
    {
        $startupLog = app(StartupLog::class);
        File::delete($startupLog->path());

        $startupLog->start();
        $startupLog->mark('Тестова мітка');

        $this->assertFileExists($startupLog->path());
        $this->assertStringContainsString('Тестова мітка', File::get($startupLog->path()));
    }
}