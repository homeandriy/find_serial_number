<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
final class ImageDirectorySettings
{
    private const FILE = 'private/image-directory.txt';
    private const AGREEMENT_FILE = 'private/license-accepted.txt';
    public function path(): string { return $this->hasCustomPath() ? trim(Storage::disk('local')->get(self::FILE)) : (string) config('serial-number.image_directory'); }
    public function hasCustomPath(): bool { return Storage::disk('local')->exists(self::FILE) && trim(Storage::disk('local')->get(self::FILE)) !== ''; }
    public function hasAcceptedAgreement(): bool { return Storage::disk('local')->exists(self::AGREEMENT_FILE); }
    public function setupRequired(): bool { return ! $this->hasCustomPath() || ! $this->hasAcceptedAgreement(); }
    public function update(string $path): string
    {
        $path = trim($path);
        $resolvedPath = realpath($path);

        if ($resolvedPath === false || ! is_dir($resolvedPath)) {
            throw new RuntimeException('Вказана папка не існує або недоступна.');
        }

        Storage::disk('local')->put(self::FILE, $resolvedPath);

        return $resolvedPath;
    }
    public function acceptAgreement(): void { Storage::disk('local')->put(self::AGREEMENT_FILE, now()->toIso8601String()); }
}
