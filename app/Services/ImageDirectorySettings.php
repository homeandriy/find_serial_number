<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
final class ImageDirectorySettings {
 private const FILE='private/image-directory.txt';
 public function path(): string { $path=Storage::disk('local')->exists(self::FILE)?trim(Storage::disk('local')->get(self::FILE)):(string)config('serial-number.image_directory'); return $path; }
 public function update(string $path): string { $path=trim($path); if(!is_dir($path)){throw new RuntimeException('Вказана папка не існує або недоступна.');} Storage::disk('local')->put(self::FILE,$path); return $path; }
}