<?php

$extrasDirectory = rtrim((string) env('NATIVEPHP_EXTRAS_PATH', base_path('extras')), DIRECTORY_SEPARATOR);
$tesseractDirectory = $extrasDirectory.DIRECTORY_SEPARATOR.'tesseract';

return [
    'image_directory' => env('SERIAL_IMAGES_PATH', base_path('images')),
    'tesseract_binary' => env('TESSERACT_BINARY', $tesseractDirectory.DIRECTORY_SEPARATOR.'tesseract.exe'),
    'tessdata_directory' => env(
        'TESSDATA_DIRECTORY',
        is_dir($tesseractDirectory.DIRECTORY_SEPARATOR.'tessdata')
            ? $tesseractDirectory.DIRECTORY_SEPARATOR.'tessdata'
            : base_path('tessdata'),
    ),
    'tesseract_languages' => env('TESSERACT_LANGUAGES', 'eng'),
    'tesseract_page_segmentation_mode' => (int) env('TESSERACT_PSM', 6),
    'default_popular_models' => [
        'Оптичний термінал GPON G-010G-P Nokia',
        'Оптичний термінал GPON G-010G-Q(R) NOKIA',
        'Оптичний термінал GPON G-140W-G NOKIA',
        'Оптичний термінал XPON FD701G-AX Stels',
        'Маршрутизатор TP-Link EC220-F5',
        'DCD 3011',
        'Arris CM820',
        'Код доступа',
        'Медіаплеєр iNeXT TV4',
        'Медіаплеєр iNeXT TV5',
    ],
];
