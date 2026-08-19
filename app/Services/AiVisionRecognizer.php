<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AiVisionRecognizer
{
    private const Prompt = 'Read every visible text item in this equipment-label photo. The important values are equipment serial numbers and MAC addresses, which use Latin letters and digits. Return only the recognized text: one item per line. Preserve capitalization and punctuation exactly when possible. Do not add explanations and do not invent values.';

    /** @param array{id: string, name: string, provider: string, model: string, token: string} $agent */
    public function recognize(array $agent, string $imagePath): string
    {
        [$contents, $mimeType] = $this->preparedImage($imagePath);

        return match ($agent['provider']) {
            'openai' => $this->openAi($agent, $mimeType, base64_encode($contents)),
            'anthropic' => $this->anthropic($agent, $mimeType, base64_encode($contents)),
            'gemini' => $this->gemini($agent, $mimeType, base64_encode($contents)),
            default => throw new RuntimeException('Цей AI-постачальник поки не підтримується.'),
        };
    }

    private function preparedImage(string $imagePath): array
    {
        $source = @imagecreatefromstring((string) file_get_contents($imagePath));

        if ($source === false) {
            throw new RuntimeException('Не вдалося прочитати файл зображення.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1800 / max($width, $height));
        $target = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        ob_start();
        imagejpeg($target, null, 85);
        $contents = (string) ob_get_clean();
        imagedestroy($target);
        imagedestroy($source);

        return [$contents, 'image/jpeg'];
    }

    private function openAi(array $agent, string $mimeType, string $image): string
    {
        $response = $this->client()->withToken($agent['token'])->post('https://api.openai.com/v1/responses', ['model' => $agent['model'], 'input' => [['role' => 'user', 'content' => [['type' => 'input_text', 'text' => self::Prompt], ['type' => 'input_image', 'image_url' => "data:{$mimeType};base64,{$image}", 'detail' => 'high']]]]]);
        return $this->responseText($response->status(), $response->body(), $agent['token'], data_get($response->json(), 'output.0.content.0.text'));
    }

    private function anthropic(array $agent, string $mimeType, string $image): string
    {
        $response = $this->client()->withHeaders(['x-api-key' => $agent['token'], 'anthropic-version' => '2023-06-01'])->post('https://api.anthropic.com/v1/messages', ['model' => $agent['model'], 'max_tokens' => 1200, 'messages' => [['role' => 'user', 'content' => [['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $image]], ['type' => 'text', 'text' => self::Prompt]]]]]);
        return $this->responseText($response->status(), $response->body(), $agent['token'], data_get($response->json(), 'content.0.text'));
    }

    private function gemini(array $agent, string $mimeType, string $image): string
    {
        $response = $this->client()->withHeaders(['x-goog-api-key' => $agent['token']])->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($agent['model']).':generateContent', ['contents' => [['parts' => [['inline_data' => ['mime_type' => $mimeType, 'data' => $image]], ['text' => self::Prompt]]]]]);
        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        return $this->responseText($response->status(), $response->body(), $agent['token'], collect($parts)->pluck('text')->filter()->implode("\n"));
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(90)->connectTimeout(15);
    }

    private function responseText(int $status, string $raw, string $token, mixed $text): string
    {
        $raw = str_replace($token, '***', $raw);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("HTTP {$status}: {$raw}");
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException("AI-постачальник не повернув текст. Сира відповідь: {$raw}");
        }

        return trim($text);
    }
}
