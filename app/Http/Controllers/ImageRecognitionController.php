<?php
namespace App\Http\Controllers;
use App\Services\ImageCatalog;
use App\Services\TesseractRecognizer;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
final class ImageRecognitionController extends Controller
{
    public function image(string $image, ImageCatalog $catalog): BinaryFileResponse
    {
        try { return response()->file($catalog->pathFor($image)); } catch (RuntimeException) { abort(404); }
    }
    public function recognize(string $image, ImageCatalog $catalog, TesseractRecognizer $recognizer): JsonResponse
    {
        try { return response()->json(['text' => $recognizer->recognize($catalog->pathFor($image))]); }
        catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
}
