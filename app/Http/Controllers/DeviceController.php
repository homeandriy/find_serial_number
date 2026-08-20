<?php

namespace App\Http\Controllers;

use App\Enums\DeviceOperation;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Services\ImageCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Native\Desktop\Facades\Shell;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->listResponse($this->query($request), 'devices', $request));
    }

    public function models(Request $request): JsonResponse
    {
        $query = DeviceModel::query();

        foreach (['devices_type', 'device_service'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter));
            }
        }

        return response()->json($this->listResponse($query->orderBy('devices_name'), 'models', $request));
    }

    public function popularModels(): JsonResponse
    {
        $popular = DeviceModel::query()
            ->join('devices', 'devices.device_model_id', '=', 'device_models.id')
            ->select('device_models.id', 'device_models.devices_name', DB::raw('COUNT(devices.id) as usage_count'))
            ->groupBy('device_models.id', 'device_models.devices_name')
            ->orderByDesc('usage_count')
            ->orderBy('device_models.devices_name')
            ->limit(10)
            ->get();

        $selectedIds = $popular->pluck('id');
        $defaults = DeviceModel::query()
            ->whereIn('devices_name', config('serial-number.default_popular_models'))
            ->whereNotIn('id', $selectedIds)
            ->get()
            ->sortBy(fn (DeviceModel $model): int => array_search($model->devices_name, config('serial-number.default_popular_models'), true))
            ->map(fn (DeviceModel $model): array => ['id' => $model->id, 'devices_name' => $model->devices_name, 'usage_count' => 0]);

        return response()->json(['models' => $popular->concat($defaults)->take(10)->values()]);
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

    public function store(Request $request, ImageCatalog $catalog): JsonResponse
    {
        return response()->json(['device' => Device::create($this->deviceData($request, $catalog))], 201);
    }

    public function update(Request $request, Device $device, ImageCatalog $catalog): JsonResponse
    {
        $device->update($this->deviceData($request, $catalog, $device));

        return response()->json(['device' => $device]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json(status: 204);
    }

    public function openSourceImage(Device $device): JsonResponse
    {
        $path = $device->source_image_path;

        if ($path === null || ! File::isFile($path)) {
            return response()->json(['message' => 'Файл пов’язаного фото вже не існує або недоступний.'], 422);
        }

        Shell::showInFolder($path);

        return response()->json(status: 204);
    }

    public function export(Request $request): StreamedResponse
    {
        $items = $this->query($request)->get();

        return response()->streamDownload(function () use ($items): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "sep=;\r\n");

            $encode = static fn (array $row): array => array_map(
                static fn (mixed $value): string => iconv('UTF-8', 'Windows-1251//TRANSLIT', (string) $value),
                $row,
            );

            fputcsv($output, $encode([
                'Дата',
                'Номер договору',
                'Операція',
                'Текст',
                'Модель',
                'Тип',
                'Послуга',
                'Шлях до фото',
            ]), ';');

            foreach ($items as $device) {
                fputcsv($output, $encode([
                    $device->registered_at->timezone('Europe/Kyiv')->format('d.m.Y H:i'),
                    $device->contract_number,
                    $device->operation_type->label(),
                    $device->recognized_text,
                    $device->devices_name,
                    $device->devices_type,
                    $device->device_service,
                    $device->source_image_path,
                ]), ';');
            }

            fclose($output);
        }, 'equipment.csv', ['Content-Type' => 'text/csv; charset=Windows-1251']);
    }

    private function listResponse($query, string $key, Request $request): array
    {
        $total = (clone $query)->count();

        if ($total <= 5000) {
            return [$key => $query->get(), 'pagination' => null];
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 100;
        $pages = (int) ceil($total / $perPage);
        $page = min($page, $pages);

        return [
            $key => $query->forPage($page, $perPage)->get(),
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => $pages],
        ];
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
            $from = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $filters['date_from'].' 00:00:00', 'Europe/Kyiv')->utc();
            $lastDate = $filters['date_to'] ?? $filters['date_from'];
            $to = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $lastDate.' 23:59:59', 'Europe/Kyiv')->utc();
            $query->whereBetween('registered_at', [$from, $to]);
        } elseif (! empty($filters['date_to'])) {
            $to = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $filters['date_to'].' 23:59:59', 'Europe/Kyiv')->utc();
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

    private function deviceData(Request $request, ImageCatalog $catalog, ?Device $existing = null): array
    {
        $data = $request->validate([
            'recognized_text' => ['required', 'string'],
            'contract_number' => ['nullable', 'string', 'max:20'],
            'operation_type' => ['required', Rule::enum(DeviceOperation::class)],
            'source_image_id' => ['nullable', 'string'],
            'device_model_id' => ['required', 'exists:device_models,id'],
            'registered_at' => ['required', 'date'],
        ]);

        $sourceImagePath = $existing?->source_image_path;

        if (! empty($data['source_image_id'])) {
            try {
                $sourceImagePath = $catalog->pathFor($data['source_image_id']);
            } catch (RuntimeException) {
                throw ValidationException::withMessages(['source_image_id' => 'Вихідне фото не знайдено.']);
            }
        }

        unset($data['source_image_id']);
        $data['recognized_text'] = trim(str_replace(["\r\n", "\r", "\n"], '', $data['recognized_text']));
        $data['contract_number'] = ($contractNumber = trim((string) ($data['contract_number'] ?? ''))) === '' ? null : $contractNumber;
        $data['registered_at'] = CarbonImmutable::parse($data['registered_at'], 'Europe/Kyiv')->utc();

        $model = DeviceModel::findOrFail($data['device_model_id']);

        return [
            ...$data,
            'source_image_path' => $sourceImagePath,
            'devices_name' => $model->devices_name,
            'devices_type' => $model->devices_type,
            'device_service' => $model->device_service,
        ];
    }
}