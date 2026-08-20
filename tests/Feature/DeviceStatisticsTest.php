<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeviceStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-08-21 12:00:00', 'Europe/Kyiv');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_limits_daily_statistics_to_the_current_month(): void
    {
        $modem = $this->model('Stats Modem', 'modem', 'internet');
        $tuner = $this->model('Stats Tuner', 'tuner', 'television');

        $this->createDevice($modem, 'receipt', '2026-08-20 12:00:00');
        $this->createDevice($modem, 'issue', '2026-08-20 13:00:00');
        $this->createDevice($tuner, 'receipt', '2026-08-21 12:00:00');
        $this->createDevice($tuner, 'receipt', '2026-07-31 12:00:00');

        $this->getJson('/statistics?group_by=day')
            ->assertOk()
            ->assertJsonPath('group_by', 'day')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('operations.0.label', '20.08.2026')
            ->assertJsonPath('operations.0.receipt', 1)
            ->assertJsonPath('operations.0.issue', 1)
            ->assertJsonPath('services.0.label', 'Інтернет')
            ->assertJsonPath('services.0.total', 2)
            ->assertJsonPath('models.0.name', 'Stats Modem')
            ->assertJsonPath('models.0.total', 2);
    }

    public function test_it_limits_monthly_statistics_to_the_last_twelve_calendar_months(): void
    {
        $model = $this->model('Stats Modem', 'modem', 'internet');

        $this->createDevice($model, 'receipt', '2026-08-20 12:00:00');
        $this->createDevice($model, 'issue', '2026-08-21 12:00:00');
        $this->createDevice($model, 'receipt', '2025-09-01 12:00:00');
        $this->createDevice($model, 'receipt', '2025-08-31 12:00:00');

        $this->getJson('/statistics?group_by=month')
            ->assertOk()
            ->assertJsonPath('group_by', 'month')
            ->assertJsonPath('total', 3)
            ->assertJsonCount(2, 'operations')
            ->assertJsonPath('operations.0.label', '09.2025')
            ->assertJsonPath('operations.1.label', '08.2026')
            ->assertJsonPath('operations.1.total', 2);

        $this->getJson('/statistics?group_by=month&month=2025-09')
            ->assertOk()
            ->assertJsonPath('month', '2025-09')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('operations.0.label', '09.2025');
    }

    public function test_it_rejects_unknown_grouping(): void
    {
        $this->getJson('/statistics?group_by=year')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('group_by');
    }

    private function model(string $name, string $type, string $service): DeviceModel
    {
        return DeviceModel::create([
            'devices_name' => $name,
            'devices_type' => $type,
            'device_service' => $service,
        ]);
    }

    private function createDevice(DeviceModel $model, string $operation, string $registeredAt): void
    {
        Device::create([
            'recognized_text' => 'serial-'.$operation.'-'.$registeredAt,
            'operation_type' => $operation,
            'device_model_id' => $model->id,
            'devices_name' => $model->devices_name,
            'devices_type' => $model->devices_type,
            'device_service' => $model->device_service,
            'registered_at' => $registeredAt,
        ]);
    }
}