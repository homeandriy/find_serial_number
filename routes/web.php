<?php

use App\Http\Controllers\ImageRecognitionController;
use App\Services\AiAgentRepository;
use App\Services\ImageCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', function (ImageCatalog $catalog, AiAgentRepository $agents) {
    return view('serial-number.index', [
        'images' => $catalog->all(),
        'imageDirectory' => config('serial-number.image_directory'),
        'agents' => $agents->all(),
    ]);
});

Route::get('/images/{image}', [ImageRecognitionController::class, 'image'])->where('image', '[A-Za-z0-9_-]+');
Route::post('/images/{image}/recognize', [ImageRecognitionController::class, 'recognize'])->where('image', '[A-Za-z0-9_-]+');
Route::post('/images/{image}/recognize-ai/{agent}', [ImageRecognitionController::class, 'recognizeAi'])->where(['image' => '[A-Za-z0-9_-]+', 'agent' => '[A-Za-z0-9-]+']);
Route::get('/ai-agents', [ImageRecognitionController::class, 'agents']);
Route::post('/ai-agents', [ImageRecognitionController::class, 'storeAgent']);
Route::put('/ai-agents/{agent}', [ImageRecognitionController::class, 'updateAgent'])->where('agent', '[A-Za-z0-9-]+');
Route::delete('/ai-agents/{agent}', [ImageRecognitionController::class, 'deleteAgent'])->where('agent', '[A-Za-z0-9-]+');
