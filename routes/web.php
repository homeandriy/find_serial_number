<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ImageRecognitionController;
use App\Services\AiAgentRepository;
use App\Services\ImageCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', function (ImageCatalog $catalog, AiAgentRepository $agents) {
    return view('serial-number.index', [
        'images' => $catalog->all(),
        'imageDirectory' => $catalog->configuredDirectory(),
        'agents' => $agents->all(),
        'appVersion' => trim((string) file_get_contents(base_path('VERSION'))),
        'setupRequired' => app()->environment('local') || (int) config('serial-number.launch_count', 1) === 1,
    ]);
});

Route::get('/images/{image}', [ImageRecognitionController::class, 'image'])->where('image', '[A-Za-z0-9_-]+');
Route::post('/images/{image}/rotate', [ImageRecognitionController::class, 'rotate'])->where('image', '[A-Za-z0-9_-]+');
Route::delete('/images/{image}', [ImageRecognitionController::class, 'deleteImage'])->where('image', '[A-Za-z0-9_-]+');
Route::get('/image-directory', [ImageRecognitionController::class, 'imageDirectory']);
Route::post('/image-directory/open', [ImageRecognitionController::class, 'openImageDirectory']);
Route::put('/image-directory', [ImageRecognitionController::class, 'updateImageDirectory']);
Route::post('/image-directory/choose', [ImageRecognitionController::class, 'chooseImageDirectory']);
Route::post('/setup', [ImageRecognitionController::class, 'completeSetup']);
Route::post('/website', [ImageRecognitionController::class, 'openWebsite']);
Route::post('/images/{image}/recognize', [ImageRecognitionController::class, 'recognize'])->where('image', '[A-Za-z0-9_-]+');
Route::post('/images/{image}/recognize-ai/{agent}', [ImageRecognitionController::class, 'recognizeAi'])->where(['image' => '[A-Za-z0-9_-]+', 'agent' => '[A-Za-z0-9-]+']);
Route::get('/ai-agents', [ImageRecognitionController::class, 'agents']);
Route::post('/ai-agents', [ImageRecognitionController::class, 'storeAgent']);
Route::put('/ai-agents/{agent}', [ImageRecognitionController::class, 'updateAgent'])->where('agent', '[A-Za-z0-9-]+');
Route::delete('/ai-agents/{agent}', [ImageRecognitionController::class, 'deleteAgent'])->where('agent', '[A-Za-z0-9-]+');

Route::get('/devices', [DeviceController::class, 'index']);
Route::post('/devices', [DeviceController::class, 'store']);
Route::put('/devices/{device}', [DeviceController::class, 'update']);
Route::delete('/devices/{device}', [DeviceController::class, 'destroy']);
Route::post('/devices/{device}/source-image/open', [DeviceController::class, 'openSourceImage']);
Route::get('/device-models/popular', [DeviceController::class, 'popularModels']);
Route::get('/device-models', [DeviceController::class, 'models']);
Route::post('/device-models', [DeviceController::class, 'storeModel']);
Route::put('/device-models/{deviceModel}', [DeviceController::class, 'updateModel']);
Route::delete('/device-models/{deviceModel}', [DeviceController::class, 'destroyModel']);
Route::get('/devices-export', [DeviceController::class, 'export']);
