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

        if ($directory === null) {
            return [];
        }

        $images = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || ! $this->isSupported($file)) {
                continue;
            }

            $relativePath = substr($file->getRealPath(), strlen($directory) + 1);
            $uploadedAt = (new \DateTimeImmutable('@'.$file->getMTime()))->setTimezone(new \DateTimeZone('Europe/Kyiv'));

            $images[] = [
                'id' => $this->encode($relativePath),
                'name' => str_replace(DIRECTORY_SEPARATOR, ' / ', $relativePath),
                'size' => $file->getSize(),
                'uploaded_at' => $uploadedAt->format(DATE_ATOM),
                'uploaded_on' => $uploadedAt->format('Y-m-d'),
                'uploaded_label' => $uploadedAt->format('d.m.Y'),
            ];
        }

        usort($images, static fn (array $left, array $right): int => [$right['uploaded_at'], $left['name']] <=> [$left['uploaded_at'], $right['name']]);

        return $images;
    }

    public function pathFor(string $imageId): string
    {
        $directory = $this->directory();

        if ($directory === null) {
            throw new RuntimeException('Папка зображень не існує або недоступна.');
        }

        $relativePath = $this->decode($imageId);

        if ($relativePath === null || str_contains($relativePath, '..')) {
            throw new RuntimeException('Некоректний ідентифікатор зображення.');
        }

        $path = realpath($directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        if ($path === false || ! $this->isInsideDirectory($path, $directory) || ! is_file($path) || ! $this->isSupported(new SplFileInfo($path))) {
            throw new RuntimeException('Зображення не знайдено.');
        }

        return $path;
    }

    public function rotateClockwise(string $imageId): void
    {
        $path = $this->pathFor($imageId);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $source = match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException('Поворот доступний для JPG, PNG та WEBP-зображень.'),
        };

        if ($source === false) {
            throw new RuntimeException('Не вдалося відкрити зображення для повороту.');
        }

        $rotated = imagerotate($source, -90, 0);

        if ($rotated === false) {
            imagedestroy($source);
            throw new RuntimeException('Не вдалося повернути зображення.');
        }

        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        $saved = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($rotated, $path, 92),
            'png' => imagepng($rotated, $path),
            'webp' => imagewebp($rotated, $path, 92),
        };

        imagedestroy($source);
        imagedestroy($rotated);

        if (! $saved) {
            throw new RuntimeException('Не вдалося зберегти повернуте зображення.');
        }
    }

    public function delete(string $imageId): void
    {
        $path = $this->pathFor($imageId);

        if (! unlink($path)) {
            throw new RuntimeException('Не вдалося видалити зображення.');
        }
    }

    private function directory(): ?string
    {
        $directory = realpath((string) config('serial-number.image_directory'));

        return $directory !== false && is_dir($directory) ? $directory : null;
    }

    private function isSupported(SplFileInfo $file): bool
    {
        return in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS, true);
    }

    private function isInsideDirectory(string $path, string $directory): bool
    {
        return str_starts_with(strtolower($path), strtolower($directory.DIRECTORY_SEPARATOR));
    }

    private function encode(string $path): string
    {
        return rtrim(strtr(base64_encode(str_replace(DIRECTORY_SEPARATOR, '/', $path)), '+/', '-_'), '=');
    }

    private function decode(string $imageId): ?string
    {
        $padding = strlen($imageId) % 4;
        $decoded = base64_decode(strtr($imageId.str_repeat('=', $padding === 0 ? 0 : 4 - $padding), '-_', '+/'), true);

        return $decoded === false || $decoded === '' ? null : $decoded;
    }
}
