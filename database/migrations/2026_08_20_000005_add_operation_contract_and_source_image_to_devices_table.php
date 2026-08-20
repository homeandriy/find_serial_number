<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->string('contract_number', 20)->nullable()->after('recognized_text');
            $table->enum('operation_type', ['receipt', 'issue'])->default('receipt')->after('contract_number');
            $table->text('source_image_path')->nullable()->after('operation_type');
        });
        DB::table('devices')->whereNull('operation_type')->update(['operation_type' => 'receipt']);
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropColumn(['contract_number', 'operation_type', 'source_image_path']);
        });
    }
};