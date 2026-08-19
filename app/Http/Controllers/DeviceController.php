<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['devices' => $this->query($request)->get()]);
    }

    public function models(Request $request): JsonResponse
    {
        $query = DeviceModel::query();

        foreach (['devices_type', 'device_service'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter));
            }
        }

        return response()->json(['models' => $query->orderBy('devices_name')->get()]);
    }

    public function storeModel(Request $request): JsonResponse
    {
        return response()->json(['model' => DeviceModel::create($this->modelData($request))], 201);
    }

    public function updateModel(Request $request, DeviceModel $deviceModel): JsonResponse
    {
        $deviceModel->update($this->modelData($request));

        return response()->json(['model' => $deviceModel]);
    }

    public function destroyModel(DeviceModel $deviceModel): JsonResponse
    {
        $deviceModel->delete();

        return response()->json(status: 204);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['device' => Device::create($this->deviceData($request))], 201);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $device->update($this->deviceData($request));

        return response()->json(['device' => $device]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json(status: 204);
    }

    public function export(Request $request): StreamedResponse
    {
        $items = $this->query($request)->get();

        return response()->streamDownload(function () use ($items): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "sep=;\r\n");

            $encode = static fn (array $row): array => array_map(
                static fn (string $value): string => iconv('UTF-8', 'Windows-1251//TRANSLIT', $value),
                $row,
            );

            fputcsv($output, $encode(['Дата', 'Текст', 'Модель', 'Тип', 'Послуга']), ';');

            foreach ($items as $device) {
                fputcsv($output, $encode([
                    $device->registered_at->timezone('Europe/Kyiv')->format('d.m.Y H:i'),
                    $device->recognized_text,
                    $device->devices_name,
                    $device->devices_type,
                    $device->device_service,
                ]), ';');
            }

            fclose($output);
        }, 'equipment.csv', ['Content-Type' => 'text/csv; charset=Windows-1251']);
    }

    private function query(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'devices_type' => ['nullable', 'in:tuner,modem'],
            'device_service' => ['nullable', 'in:internet,television'],
            'search' => ['nullable', 'string'],
        ]);

        $query = Device::query();

        foreach (['devices_type', 'device_service'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['date_from'])) {
            $from = CarbonImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $filters['date_from'].' 00:00:00',
                'Europe/Kyiv',
            )->utc();

            $lastDate = $filters['date_to'] ?? $filters['date_from'];
            $to = CarbonImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $lastDate.' 23:59:59',
                'Europe/Kyiv',
            )->utc();

            $query->whereBetween('registered_at', [$from, $to]);
        } elseif (! empty($filters['date_to'])) {
            $to = CarbonImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $filters['date_to'].' 23:59:59',
                'Europe/Kyiv',
            )->utc();

            $query->where('registered_at', '<=', $to);
        }

        if (! empty($filters['search'])) {
            $query->where('recognized_text', 'like', '%'.$filters['search'].'%');
        }

        return $query->latest('registered_at')->latest('id');
    }

    private function modelData(Request $request): array
    {
        return $request->validate([
            'devices_name' => ['required', 'string', 'max:120'],
            'devices_type' => ['required', 'in:tuner,modem'],
            'device_service' => ['required', 'in:internet,television'],
        ]);
    }

    private function deviceData(Request $request): array
    {
        $data = $request->validate([
            'recognized_text' => ['required', 'string'],
            'device_model_id' => ['required', 'exists:device_models,id'],
            'registered_at' => ['required', 'date'],
        ]);

        $data['recognized_text'] = trim(str_replace(["\r\n", "\r", "\n"], '', $data['recognized_text']));
        $data['registered_at'] = CarbonImmutable::parse($data['registered_at'], 'Europe/Kyiv')->utc();

        $model = DeviceModel::findOrFail($data['device_model_id']);

        return [
            ...$data,
            'devices_name' => $model->devices_name,
            'devices_type' => $model->devices_type,
            'device_service' => $model->device_service,
        ];
    }
}
