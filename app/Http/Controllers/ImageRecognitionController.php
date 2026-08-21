<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiAgentRequest;
use App\Http\Requests\UpdateAiAgentRequest;
use App\Services\AiAgentRepository;
use App\Services\AiVisionRecognizer;
use App\Services\ImageCatalog;
use App\Services\ApplicationLocale;
use App\Services\ImageDirectorySettings;
use App\Services\NativeMenu;
use App\Services\TesseractRecognizer;
use App\Services\StartupLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Native\Desktop\Dialog;
use Native\Desktop\Facades\Shell;
use Native\Desktop\Facades\Window;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImageRecognitionController extends Controller
{
    public function locale(ApplicationLocale $locale): JsonResponse
    {
        return response()->json(['locale' => $locale->current(), 'supported' => $locale->supported()]);
    }

    public function updateLocale(
        Request $request,
        ApplicationLocale $locale,
        NativeMenu $menu,
    ): JsonResponse {
        $selected = $request->validate(['locale' => ['required', 'string', 'in:uk,en,pl']])['locale'];
        $updatedLocale = $locale->update($selected);

        $menu->register();

        return response()->json(['locale' => $updatedLocale]);
    }

    public function catalog(Request $request, ImageCatalog $catalog): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:12', 'max:100'],
        ]);

        return response()->json($catalog->page((int) ($data['page'] ?? 1), (int) ($data['per_page'] ?? 48)));
    }

    public function markRendererReady(StartupLog $startupLog): JsonResponse
    {
        $startupLog->mark('Перший інтерфейс відображено');

        return response()->json(status: 204);
    }
    public function openStartupLog(StartupLog $startupLog): JsonResponse
    {
        $startupLog->mark('Користувач відкриває лог запуску');
        $error = Shell::openFile($startupLog->path());

        if ($error !== '') {
            return response()->json(['message' => "Не вдалося відкрити лог запуску: {$error}"], 422);
        }

        return response()->json(status: 204);
    }
    public function image(string $image, ImageCatalog $catalog): BinaryFileResponse
    {
        try { return response()->file($catalog->pathFor($image)); } catch (RuntimeException) { abort(404); }
    }
    public function rotate(string $image, ImageCatalog $catalog): JsonResponse
    {
        try { $catalog->rotateClockwise($image); return response()->json(status: 204); } catch (RuntimeException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
    }
    public function openImage(string $image, ImageCatalog $catalog): JsonResponse
    {
        try {
            $error = Shell::openFile($catalog->pathFor($image));

            if ($error !== '') {
                return response()->json(['message' => "Не вдалося відкрити зображення: {$error}"], 422);
            }

            return response()->json(status: 204);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
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
    public function updateWindowTitle(
        Request $request,
        ApplicationLocale $locale,
    ): JsonResponse {
        $tab = $request->validate([
            "tab" => ["required", "string", "in:recognition,equipment,models,statistics,settings"],
        ])["tab"];

        app()->setLocale($locale->current());
        Window::get("main")->title(__("ui.".$tab)." — ".__("ui.app_name"));

        return response()->json(status: 204);
    }
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
