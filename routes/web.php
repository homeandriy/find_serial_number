<?php
use App\Http\Controllers\ImageRecognitionController;
use App\Services\ImageCatalog;
use Illuminate\Support\Facades\Route;
Route::get('/', function (ImageCatalog $catalog) {
    return view('serial-number.index', ['images' => $catalog->all(), 'imageDirectory' => config('serial-number.image_directory')]);
});
Route::get('/images/{image}', [ImageRecognitionController::class, 'image'])->where('image', '[A-Za-z0-9_-]+');
Route::post('/images/{image}/recognize', [ImageRecognitionController::class, 'recognize'])->where('image', '[A-Za-z0-9_-]+');
