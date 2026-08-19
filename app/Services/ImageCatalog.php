<?php
namespace App\Services;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
final class ImageCatalog
{
    private const ALLOWED_EXTENSIONS = ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'tif', 'tiff', 'webp'];
    public function all(): array
    {
        $directory = $this->directory();
        if ($directory === null) return [];
        $images = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || ! $this->isSupported($file)) continue;
            $relativePath = substr($file->getRealPath(), strlen($directory) + 1);
            $images[] = ['id' => $this->encode($relativePath), 'name' => str_replace(DIRECTORY_SEPARATOR, ' / ', $relativePath), 'size' => $file->getSize()];
        }
        usort($images, static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));
        return $images;
    }
    public function pathFor(string $imageId): string
    {
        $directory = $this->directory();
        if ($directory === null) throw new RuntimeException('Папка зображень не існує або недоступна.');
        $relativePath = $this->decode($imageId);
        if ($relativePath === null || str_contains($relativePath, '..')) throw new RuntimeException('Некоректний ідентифікатор зображення.');
        $path = realpath($directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($path === false || ! $this->isInsideDirectory($path, $directory) || ! is_file($path) || ! $this->isSupported(new SplFileInfo($path))) throw new RuntimeException('Зображення не знайдено.');
        return $path;
    }
    private function directory(): ?string { $directory = realpath((string) config('serial-number.image_directory')); return $directory !== false && is_dir($directory) ? $directory : null; }
    private function isSupported(SplFileInfo $file): bool { return in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS, true); }
    private function isInsideDirectory(string $path, string $directory): bool { return str_starts_with(strtolower($path), strtolower($directory.DIRECTORY_SEPARATOR)); }
    private function encode(string $path): string { return rtrim(strtr(base64_encode(str_replace(DIRECTORY_SEPARATOR, '/', $path)), '+/', '-_'), '='); }
    private function decode(string $imageId): ?string
    {
        $padding = strlen($imageId) % 4;
        $decoded = base64_decode(strtr($imageId.str_repeat('=', $padding === 0 ? 0 : 4 - $padding), '-_', '+/'), true);
        return $decoded === false || $decoded === '' ? null : $decoded;
    }
}
