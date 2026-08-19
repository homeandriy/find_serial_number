<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class AiAgentRepository
{
    private const File = 'private/ai-agents.json';

    /** @return list<array{id: string, name: string, provider: string, model: string, has_token: bool}> */
    public function all(): array
    {
        return array_map(fn (array $agent): array => $this->publicAgent($agent), $this->read());
    }

    /** @return array{id: string, name: string, provider: string, model: string, token: string} */
    public function find(string $id): array
    {
        foreach ($this->read() as $agent) {
            if ($agent['id'] === $id) {
                return [
                    'id' => $agent['id'],
                    'name' => $agent['name'],
                    'provider' => $agent['provider'],
                    'model' => $agent['model'],
                    'token' => Crypt::decryptString($agent['token']),
                ];
            }
        }

        throw new RuntimeException('AI-агента не знайдено.');
    }

    /** @param array{name?: string, provider: string, model: string, token?: string} $attributes */
    public function save(array $attributes, ?string $id = null): array
    {
        $agents = $this->read();
        $index = $id === null ? null : array_find_key($agents, fn (array $agent): bool => $agent['id'] === $id);

        if ($id !== null && $index === null) {
            throw new RuntimeException('AI-агента не знайдено.');
        }

        $existing = $index === null ? null : $agents[$index];
        $token = $attributes['token'] ?? null;

        if ($existing === null && blank($token)) {
            throw new RuntimeException('Вкажіть API-токен для нового агента.');
        }

        $agent = [
            'id' => $existing['id'] ?? (string) Str::uuid(),
            'name' => filled($attributes['name'] ?? null) ? trim($attributes['name']) : trim($attributes['provider'].' · '.$attributes['model']),
            'provider' => $attributes['provider'],
            'model' => $attributes['model'],
            'token' => filled($token) ? Crypt::encryptString($token) : $existing['token'],
        ];

        if ($index === null) {
            $agents[] = $agent;
        } else {
            $agents[$index] = $agent;
        }

        Storage::disk('local')->put(self::File, json_encode($agents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $this->publicAgent($agent);
    }

    public function delete(string $id): void
    {
        $agents = array_values(array_filter($this->read(), fn (array $agent): bool => $agent['id'] !== $id));
        Storage::disk('local')->put(self::File, json_encode($agents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return list<array{id: string, name: string, provider: string, model: string, token: string}> */
    private function read(): array
    {
        if (! Storage::disk('local')->exists(self::File)) {
            return [];
        }

        $agents = json_decode(Storage::disk('local')->get(self::File), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($agents)) {
            throw new RuntimeException('Локальне сховище AI-агентів має некоректний формат.');
        }

        return $agents;
    }

    /** @param array{id: string, name: string, provider: string, model: string, token: string} $agent */
    private function publicAgent(array $agent): array
    {
        return [
            'id' => $agent['id'],
            'name' => $agent['name'],
            'provider' => $agent['provider'],
            'model' => $agent['model'],
            'has_token' => filled($agent['token']),
        ];
    }
}
