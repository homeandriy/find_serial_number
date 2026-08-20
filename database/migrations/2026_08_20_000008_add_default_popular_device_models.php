<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('serial-number.default_popular_models') as $name) {
            DB::table('device_models')->insertOrIgnore([
                'devices_name' => $name,
                'devices_type' => 'modem',
                'device_service' => 'internet',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Default models are user-editable data and are retained on rollback.
    }
};