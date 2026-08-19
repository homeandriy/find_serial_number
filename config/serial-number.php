<?php
return [
    'image_directory' => env('SERIAL_IMAGES_PATH', base_path('images')),
    'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
    'tessdata_directory' => env('TESSDATA_DIRECTORY', base_path('tessdata')),
    'tesseract_languages' => env('TESSERACT_LANGUAGES', 'eng'),
    'tesseract_page_segmentation_mode' => (int) env('TESSERACT_PSM', 11),
];
