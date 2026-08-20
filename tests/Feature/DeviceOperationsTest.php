<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeviceOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_contract_operation_and_the_resolved_source_image_path(): void
    {
        $directory = storage_path('framework/testing/device-source-image');
        @mkdir($directory, 0777, true);
        file_put_contents($directory.'/label.jpg', 'image');
        config()->set('serial-number.image_directory', $directory);

        $model = DeviceModel::create([
            'devices_name' => 'Arris 820',
            'devices_type' => 'modem',
            'device_service' => 'internet',
        ]);
        $imageId = rtrim(strtr(base64_encode('label.jpg'), '+/', '-_'), '=');

        $response = $this->postJson('/devices', [
            'recognized_text' => '0015CF228E8B',
            'contract_number' => '123/45',
            'operation_type' => 'issue',
            'source_image_id' => $imageId,
            'device_model_id' => $model->id,
            'registered_at' => '2026-08-20T12:00',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('devices', [
            'recognized_text' => '0015CF228E8B',
            'contract_number' => '123/45',
            'operation_type' => 'issue',
            'source_image_path' => realpath($directory.'/label.jpg'),
        ]);
    }

    public function test_it_rejects_contract_numbers_longer_than_twenty_characters(): void
    {
        $model = DeviceModel::create([
            'devices_name' => 'Arris 820',
            'devices_type' => 'modem',
            'device_service' => 'internet',
        ]);

        $this->postJson('/devices', [
            'recognized_text' => '0015CF228E8B',
            'contract_number' => str_repeat('1', 21),
            'operation_type' => 'receipt',
            'device_model_id' => $model->id,
            'registered_at' => '2026-08-20T12:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('contract_number');
    }

    public function test_it_reports_when_a_related_source_image_no_longer_exists(): void
    {
        $device = Device::create([
            'recognized_text' => '0015CF228E8B',
            'operation_type' => 'receipt',
            'source_image_path' => storage_path('framework/testing/missing-photo.jpg'),
            'device_model_id' => DeviceModel::create([
                'devices_name' => 'Arris 820',
                'devices_type' => 'modem',
                'device_service' => 'internet',
            ])->id,
            'devices_name' => 'Arris 820',
            'devices_type' => 'modem',
            'device_service' => 'internet',
            'registered_at' => now(),
        ]);

        $this->postJson('/devices/'.$device->id.'/source-image/open')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Файл пов’язаного фото вже не існує або недоступний.');
    }
}