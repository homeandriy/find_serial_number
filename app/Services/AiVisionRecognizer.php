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
        $contents = file_get_contents($imagePath);

        if ($contents === false) {
            throw new RuntimeException('Не вдалося прочитати файл зображення.');
        }

        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $encodedImage = base64_encode($contents);

        return match ($agent['provider']) {
            'openai' => $this->openAi($agent, $mimeType, $encodedImage),
            'anthropic' => $this->anthropic($agent, $mimeType, $encodedImage),
            'gemini' => $this->gemini($agent, $mimeType, $encodedImage),
            default => throw new RuntimeException('Цей AI-постачальник поки не підтримується.'),
        };
    }

    /** @param array{model: string, token: string} $agent */
    private function openAi(array $agent, string $mimeType, string $encodedImage): string
    {
        $response = $this->client()->withToken($agent['token'])->post('https://api.openai.com/v1/responses', [
            'model' => $agent['model'],
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => self::Prompt],
                    ['type' => 'input_image', 'image_url' => "data:{$mimeType};base64,{$encodedImage}", 'detail' => 'high'],
                ],
            ]],
        ]);

        $this->ensureSuccessful($response->status());
        return $this->text(data_get($response->json(), 'output_text'));
    }

    /** @param array{model: string, token: string} $agent */
    private function anthropic(array $agent, string $mimeType, string $encodedImage): string
    {
        $response = $this->client()->withHeaders([
            'x-api-key' => $agent['token'],
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $agent['model'],
            'max_tokens' => 1200,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $encodedImage]],
                    ['type' => 'text', 'text' => self::Prompt],
                ],
            ]],
        ]);

        $this->ensureSuccessful($response->status());
        return $this->text(data_get($response->json(), 'content.0.text'));
    }

    /** @param array{model: string, token: string} $agent */
    private function gemini(array $agent, string $mimeType, string $encodedImage): string
    {
        $response = $this->client()->withHeaders(['x-goog-api-key' => $agent['token']])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($agent['model']).':generateContent',
            ['contents' => [['parts' => [
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $encodedImage]],
                ['text' => self::Prompt],
            ]]]],
        );

        $this->ensureSuccessful($response->status());
        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        return $this->text(collect($parts)->pluck('text')->filter()->implode("\n"));
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(90)->connectTimeout(15);
    }

    private function ensureSuccessful(int $status): void
    {
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("AI-постачальник повернув помилку HTTP {$status}.");
        }
    }

    private function text(mixed $text): string
    {
        $text = is_string($text) ? trim($text) : '';

        if ($text === '') {
            throw new RuntimeException('AI-постачальник не повернув текст.');
        }

        return $text;
    }
}
