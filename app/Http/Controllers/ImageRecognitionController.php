<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiAgentRequest;
use App\Http\Requests\UpdateAiAgentRequest;
use App\Services\AiAgentRepository;
use App\Services\AiVisionRecognizer;
use App\Services\ImageCatalog;
use App\Services\TesseractRecognizer;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImageRecognitionController extends Controller
{
    public function image(string $image, ImageCatalog $catalog): BinaryFileResponse
    {
        try {
            return response()->file($catalog->pathFor($image));
        } catch (RuntimeException) {
            abort(404);
        }
    }

    public function recognize(string $image, ImageCatalog $catalog, TesseractRecognizer $recognizer): JsonResponse
    {
        try {
            return response()->json(['text' => $recognizer->recognize($catalog->pathFor($image))]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function agents(AiAgentRepository $agents): JsonResponse
    {
        return response()->json(['agents' => $agents->all()]);
    }

    public function storeAgent(StoreAiAgentRequest $request, AiAgentRepository $agents): JsonResponse
    {
        try {
            return response()->json(['agent' => $agents->save($request->validated())], 201);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateAgent(UpdateAiAgentRequest $request, string $agent, AiAgentRepository $agents): JsonResponse
    {
        try {
            return response()->json(['agent' => $agents->save($request->validated(), $agent)]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function deleteAgent(string $agent, AiAgentRepository $agents): JsonResponse
    {
        $agents->delete($agent);

        return response()->json(status: 204);
    }

    public function recognizeAi(string $image, string $agent, ImageCatalog $catalog, AiAgentRepository $agents, AiVisionRecognizer $recognizer): JsonResponse
    {
        try {
            return response()->json(['text' => $recognizer->recognize($agents->find($agent), $catalog->pathFor($image))]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
