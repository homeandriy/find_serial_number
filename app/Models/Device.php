<?php

namespace App\Models;

use App\Enums\DeviceOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Device extends Model
{
    protected $fillable = [
        'recognized_text',
        'contract_number',
        'operation_type',
        'source_image_path',
        'device_model_id',
        'devices_name',
        'devices_type',
        'device_service',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'operation_type' => DeviceOperation::class,
            'registered_at' => 'datetime',
        ];
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class, 'device_model_id');
    }
}