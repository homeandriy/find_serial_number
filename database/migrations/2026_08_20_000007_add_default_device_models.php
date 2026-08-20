<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (require database_path('data/default-device-models.php') as $name) {
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
        // The bundled models may be edited by a user; they are intentionally retained on rollback.
    }
};