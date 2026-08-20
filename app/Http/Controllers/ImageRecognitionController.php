<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiAgentRequest;
use App\Http\Requests\UpdateAiAgentRequest;
use App\Services\AiAgentRepository;
use App\Services\AiVisionRecognizer;
use App\Services\ImageCatalog;
use App\Services\ImageDirectorySettings;
use App\Services\TesseractRecognizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Native\Desktop\Dialog;
use Native\Desktop\Facades\Shell;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImageRecognitionController extends Controller
{
    public function image(string $image, ImageCatalog $catalog): BinaryFileResponse
    {
        try { return response()->file($catalog->pathFor($image)); } catch (RuntimeException) { abort(404); }
    }
    public function rotate(string $image, ImageCatalog $catalog): JsonResponse
    {
        try { $catalog->rotateClockwise($image); return response()->json(status: 204); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function deleteImage(string $image, ImageCatalog $catalog): JsonResponse
    {
        try { $catalog->delete($image); return response()->json(status: 204); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function imageDirectory(ImageCatalog $catalog): JsonResponse { return response()->json(['path' => $catalog->configuredDirectory()]); }
    public function openImageDirectory(ImageCatalog $catalog): JsonResponse
    {
        $path = realpath($catalog->configuredDirectory());

        if ($path === false || ! is_dir($path)) {
            return response()->json(['message' => 'Папка зображень не існує або недоступна.'], 422);
        }

        $error = Shell::openFile($path);

        if ($error !== '') {
            return response()->json(['message' => "Не вдалося відкрити папку: {$error}"], 422);
        }

        return response()->json(status: 204);
    }
    public function updateImageDirectory(Request $request, ImageDirectorySettings $settings): JsonResponse
    {
        try { return response()->json(['path' => $settings->update($request->validate(['path' => ['required', 'string']])['path'])]); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function chooseImageDirectory(ImageDirectorySettings $settings): JsonResponse
    {
        $path = Dialog::new()
            ->title('Оберіть папку з зображеннями')
            ->button('Вибрати папку')
            ->defaultPath($settings->path())
            ->folders()
            ->asSheet('main')
            ->open();
        return response()->json(['path' => $path]);
    }
    public function completeSetup(Request $request, ImageDirectorySettings $settings): JsonResponse
    {
        try {
            $data = $request->validate(['accepted' => ['accepted'], 'path' => ['required', 'string']]);
            $path = $settings->update($data['path']);
            $settings->acceptAgreement();
            return response()->json(['path' => $path]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
    public function openWebsite(): JsonResponse { Shell::openExternal('https://webbooks.com.ua'); return response()->json(status: 204); }
    public function recognize(string $image, ImageCatalog $catalog, TesseractRecognizer $recognizer): JsonResponse
    {
        try { return response()->json(['text' => $recognizer->recognize($catalog->pathFor($image))]); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function agents(AiAgentRepository $agents): JsonResponse { return response()->json(['agents' => $agents->all()]); }
    public function storeAgent(StoreAiAgentRequest $request, AiAgentRepository $agents): JsonResponse
    {
        try { return response()->json(['agent' => $agents->save($request->validated())], 201); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function updateAgent(UpdateAiAgentRequest $request, string $agent, AiAgentRepository $agents): JsonResponse
    {
        try { return response()->json(['agent' => $agents->save($request->validated(), $agent)]); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function deleteAgent(string $agent, AiAgentRepository $agents): JsonResponse { $agents->delete($agent); return response()->json(status: 204); }
    public function recognizeAi(string $image, string $agent, ImageCatalog $catalog, AiAgentRepository $agents, AiVisionRecognizer $recognizer): JsonResponse
    {
        try { return response()->json(['text' => $recognizer->recognize($agents->find($agent), $catalog->pathFor($image))]); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
}
