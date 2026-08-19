<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('device_models',function(Blueprint $table){$table->id();$table->string('devices_name');$table->enum('devices_type',['tuner','modem']);$table->enum('device_service',['internet','television']);$table->timestamps();$table->unique(['devices_name','devices_type','device_service']);});
  Schema::create('devices',function(Blueprint $table){$table->id();$table->text('recognized_text');$table->foreignId('device_model_id')->constrained('device_models')->restrictOnDelete();$table->string('devices_name');$table->string('devices_type');$table->string('device_service');$table->date('registered_at');$table->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('devices');Schema::dropIfExists('device_models');}
};