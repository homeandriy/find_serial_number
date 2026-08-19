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
        $temporaryDirectory = storage_path('app/ocr-temp/'.Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $bestText = '';
            $bestScore = -1;

            foreach ($this->variants($imagePath, $temporaryDirectory) as $variant) {
                $result = Process::timeout(20)->run([
                    (string) config('serial-number.tesseract_binary'),
                    '--tessdata-dir',
                    (string) config('serial-number.tessdata_directory'),
                    $variant,
                    'stdout',
                    '-l',
                    (string) config('serial-number.tesseract_languages'),
                    '--psm',
                    (string) config('serial-number.tesseract_page_segmentation_mode'),
                ]);

                if ($result->failed()) {
                    continue;
                }

                $text = trim($result->output());
                $score = $this->score($text);

                if ($score > $bestScore) {
                    $bestText = $text;
                    $bestScore = $score;
                }
            }

            if ($bestScore < 0) {
                throw new RuntimeException('Не вдалося розпізнати текст на зображенні.');
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

        $variants = [$imagePath];

        foreach ([-90, 90] as $angle) {
            $rotated = imagerotate($image, $angle, 0);
            $variant = $temporaryDirectory.'/rotated-'.$angle.'.jpg';
            imagejpeg($rotated, $variant, 95);
            $variants[] = $variant;
        }

        return $variants;
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

    private function score(string $text): int
    {
        preg_match_all('/[A-Za-z0-9][A-Za-z0-9:._-]{3,}/', $text, $matches);

        return array_sum(array_map('strlen', $matches[0]));
    }
}
