<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PopularDeviceModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_models_by_the_number_of_saved_devices_and_fills_the_list_with_defaults(): void
    {
        $model = DeviceModel::query()->where('devices_name', 'Arris CM820')->firstOrFail();

        foreach ([1, 2] as $number) {
            Device::create([
                'recognized_text' => 'CM820'.$number,
                'device_model_id' => $model->id,
                'devices_name' => $model->devices_name,
                'devices_type' => $model->devices_type,
                'device_service' => $model->device_service,
                'operation_type' => 'receipt',
                'registered_at' => now(),
            ]);
        }

        $this->getJson('/device-models/popular')
            ->assertOk()
            ->assertJsonCount(10, 'models')
            ->assertJsonPath('models.0.id', $model->id)
            ->assertJsonPath('models.0.usage_count', 2);
    }
}