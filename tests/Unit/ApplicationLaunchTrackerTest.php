<?php

namespace Tests\Unit;

use App\Services\ApplicationLaunchTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApplicationLaunchTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_increments_the_single_application_launch_counter(): void
    {
        $tracker = app(ApplicationLaunchTracker::class);

        self::assertSame(1, $tracker->registerLaunch());
        self::assertSame(2, $tracker->registerLaunch());
        $this->assertDatabaseHas('application_state', ['id' => 1, 'launch_count' => 2]);
    }
}