<?php

namespace App\Services;

use App\Models\Device;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class DeviceStatistics
{
    /**
     * @return array{group_by: string, total: int, operations: list<array{key: string, label: string, receipt: int, issue: int, total: int}>, services: list<array{key: string, label: string, total: int}>, models: list<array{name: string, total: int}>}
     */
    public function summarize(string $groupBy, ?string $month = null): array
    {
        $periodExpression = $groupBy === 'month'
            ? "strftime('%Y-%m', registered_at, 'localtime')"
            : "strftime('%Y-%m-%d', registered_at, 'localtime')";

        [$startsAt, $endsAt] = $this->periodBounds($groupBy, $month);
        $devices = Device::query()->where('registered_at', '>=', $startsAt);

        if ($endsAt !== null) {
            $devices->where('registered_at', '<', $endsAt);
        }

        $operationRows = (clone $devices)
            ->selectRaw($periodExpression.' as period, operation_type, COUNT(*) as total')
            ->groupBy('period', 'operation_type')
            ->orderBy('period')
            ->get();

        $operations = $operationRows
            ->groupBy('period')
            ->map(function (Collection $rows, string $period) use ($groupBy): array {
                $receipt = (int) ($rows->firstWhere('operation_type', 'receipt')->total ?? 0);
                $issue = (int) ($rows->firstWhere('operation_type', 'issue')->total ?? 0);

                return [
                    'key' => $period,
                    'label' => $this->periodLabel($period, $groupBy),
                    'receipt' => $receipt,
                    'issue' => $issue,
                    'total' => $receipt + $issue,
                ];
            })
            ->values()
            ->all();

        $services = (clone $devices)
            ->selectRaw('device_service, COUNT(*) as total')
            ->groupBy('device_service')
            ->orderBy('device_service')
            ->get()
            ->map(static fn (object $row): array => [
                'key' => $row->device_service,
                'label' => match ($row->device_service) {
                    'internet' => 'Інтернет',
                    'television' => 'Телебачення',
                    default => $row->device_service,
                },
                'total' => (int) $row->total,
            ])
            ->all();

        $models = (clone $devices)
            ->selectRaw('devices_name, COUNT(*) as total')
            ->groupBy('devices_name')
            ->orderByDesc('total')
            ->orderBy('devices_name')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => $row->devices_name,
                'total' => (int) $row->total,
            ])
            ->all();

        return [
            'group_by' => $groupBy,
            'month' => $month,
            'total' => array_sum(array_column($operations, 'total')),
            'operations' => $operations,
            'services' => $services,
            'models' => $models,
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable|null} */
    private function periodBounds(string $groupBy, ?string $month): array
    {
        if ($groupBy === 'month' && $month !== null) {
            $startsAt = CarbonImmutable::createFromFormat('!Y-m', $month, 'Europe/Kyiv')->startOfMonth();

            return [$startsAt->utc(), $startsAt->addMonth()->utc()];
        }

        $now = CarbonImmutable::now('Europe/Kyiv');
        $startsAt = ($groupBy === 'month' ? $now->subMonths(11) : $now)->startOfMonth();

        return [$startsAt->utc(), null];
    }

    private function periodLabel(string $period, string $groupBy): string
    {
        if ($groupBy === 'month') {
            [$year, $month] = explode('-', $period);

            return $month.'.'.$year;
        }

        [$year, $month, $day] = explode('-', $period);

        return $day.'.'.$month.'.'.$year;
    }
}