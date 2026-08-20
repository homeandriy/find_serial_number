<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ApplicationLaunchTracker
{
    public function registerLaunch(): int
    {
        if (! Schema::hasTable('application_state')) {
            return 0;
        }

        DB::table('application_state')->insertOrIgnore(
            ['id' => 1],
            ['launch_count' => 0, 'created_at' => now(), 'updated_at' => now()],
        );

        DB::table('application_state')->where('id', 1)->increment('launch_count', 1, [
            'updated_at' => now(),
        ]);

        return (int) DB::table('application_state')->where('id', 1)->value('launch_count');
    }
}