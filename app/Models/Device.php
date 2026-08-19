<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class Device extends Model {protected $fillable=['recognized_text','device_model_id','devices_name','devices_type','device_service','registered_at'];protected function casts(): array{return ['registered_at'=>'datetime'];}public function model(): BelongsTo{return $this->belongsTo(DeviceModel::class,'device_model_id');}}