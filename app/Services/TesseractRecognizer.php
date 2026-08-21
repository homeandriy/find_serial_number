<?php
namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

final class TesseractRecognizer
{
    public function recognize(string $imagePath): string
    {
        $binary = (string) config('serial-number.tesseract_binary');
        $tessdataDirectory = (string) config('serial-number.tessdata_directory');

        if (! is_file($binary)) {
            throw new RuntimeException('Локальний OCR недоступний: Tesseract не знайдено у встановленій програмі. Перевстановіть застосунок.');
        }

        if (! is_dir($tessdataDirectory)) {
            throw new RuntimeException('Локальний OCR недоступний: не знайдено мовні дані Tesseract. Перевстановіть застосунок.');
        }

        $temporaryDirectory = storage_path('app/ocr-temp/'.Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $bestText = '';
            $bestScore = -1;
            $failures = [];

            foreach ($this->variants($imagePath, $temporaryDirectory) as $variant) {
                $result = Process::timeout(20)->run([
                    $binary,
                    '--tessdata-dir',
                    $tessdataDirectory,
                    $variant,
                    'stdout',
                    '-l',
                    (string) config('serial-number.tesseract_languages'),
                    '--psm',
                    (string) config('serial-number.tesseract_page_segmentation_mode'),
                ]);

                if ($result->failed()) {
                    $failure = trim($result->errorOutput() ?: $result->output());

                    if ($failure !== '') {
                        $failures[] = $failure;
                    }

                    continue;
                }

                $text = trim($result->output());
                $relevantText = $this->relevantText($text);
                $score = $this->score($relevantText);

                if ($score > $bestScore) {
                    $bestText = $relevantText !== '' ? $relevantText : $text;
                    $bestScore = $score;
                }
            }

            if ($bestScore < 0) {
                $detail = $failures === [] ? '' : ' '.Str::limit(implode(' ', array_unique($failures)), 300);

                throw new RuntimeException('Не вдалося розпізнати текст на зображенні.'.$detail);
            }

            return $bestText;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /** @return array<int, string> */
    private function variants(string $imagePath, string $temporaryDirectory): array
    {
        $image = $this->loadImage($imagePath);

        if ($image === null) {
            return [$imagePath];
        }

        $variants = [];

        foreach ([0, -90, 90] as $angle) {
            $rotated = $angle === 0 ? $image : imagerotate($image, $angle, 0);
            $variants[] = $this->enhance($rotated, $temporaryDirectory, $angle);
        }

        return $variants;
    }

    private function enhance(\GdImage $image, string $temporaryDirectory, int $angle): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $enhanced = imagecreatetruecolor($width * 2, $height * 2);

        imagecopyresampled($enhanced, $image, 0, 0, 0, 0, $width * 2, $height * 2, $width, $height);
        imagefilter($enhanced, IMG_FILTER_GRAYSCALE);
        imagefilter($enhanced, IMG_FILTER_CONTRAST, -35);

        $path = $temporaryDirectory.'/variant-'.$angle.'.jpg';
        imagejpeg($enhanced, $path, 100);

        return $path;
    }

    private function loadImage(string $imagePath): \GdImage|null
    {
        return match (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($imagePath) ?: null,
            'png' => @imagecreatefrompng($imagePath) ?: null,
            'gif' => @imagecreatefromgif($imagePath) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($imagePath) ?: null) : null,
            default => null,
        };
    }

    private function relevantText(string $text): string
    {
        $lines = preg_split('/\R/', $text) ?: [];
        $relevantLines = array_filter($lines, fn (string $line): bool => $this->score($line) > 0);

        return trim(implode(PHP_EOL, $relevantLines));
    }

    private function score(string $text): int
    {
        preg_match_all('/(?:[A-Z][A-Z0-9:._-]{4,}|[0-9][A-Z0-9:._-]{4,})/', $text, $matches);

        return array_sum(array_map('strlen', $matches[0]));
    }
}
