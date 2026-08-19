<?php
namespace App\Services;
use Illuminate\Support\Facades\Process;
use RuntimeException;
final class TesseractRecognizer
{
    public function recognize(string $imagePath): string
    {
        $result = Process::timeout(60)->run([
            (string) config('serial-number.tesseract_binary'),
            '--tessdata-dir',
            (string) config('serial-number.tessdata_directory'),
            $imagePath,
            'stdout',
            '-l',
            (string) config('serial-number.tesseract_languages'),
            '--psm',
            (string) config('serial-number.tesseract_page_segmentation_mode'),
        ]);
        if ($result->failed()) {
            $message = trim($result->errorOutput());
            throw new RuntimeException($message !== '' ? $message : 'Не вдалося розпізнати текст на зображенні.');
        }
        return trim($result->output());
    }
}
