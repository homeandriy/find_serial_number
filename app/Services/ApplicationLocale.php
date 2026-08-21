<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ApplicationLocale
{
    public const DEFAULT = 'uk';

    private const SUPPORTED = ['uk', 'en', 'pl'];

    public function current(): string
    {
        if (! $this->ensureStorage()) {
            return self::DEFAULT;
        }

        DB::table('application_state')->insertOrIgnore([
            'id' => 1,
            'launch_count' => 0,
            'locale' => self::DEFAULT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (string) (DB::table('application_state')->where('id', 1)->value('locale') ?: self::DEFAULT);
    }

    public function update(string $locale): string
    {
        if (! in_array($locale, self::SUPPORTED, true)) {
            throw new InvalidArgumentException('Unsupported application locale.');
        }

        if (! $this->ensureStorage()) {
            return self::DEFAULT;
        }

        $this->current();

        DB::table('application_state')->where('id', 1)->update([
            'locale' => $locale,
            'updated_at' => now(),
        ]);

        app()->setLocale($locale);

        return $locale;
    }

    private function ensureStorage(): bool
    {
        if (! Schema::hasTable('application_state')) {
            return false;
        }

        if (! Schema::hasColumn('application_state', 'locale')) {
            Schema::table('application_state', function (Blueprint $table): void {
                $table->string('locale', 5)->default(self::DEFAULT);
            });
        }

        return true;
    }

    /** @return list<string> */
    public function supported(): array
    {
        return self::SUPPORTED;
    }
}
