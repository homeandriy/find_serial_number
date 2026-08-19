<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class DeviceModel extends Model {protected $fillable=['devices_name','devices_type','device_service'];public function devices(): HasMany{return $this->hasMany(Device::class);}}