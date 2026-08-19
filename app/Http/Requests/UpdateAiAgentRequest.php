<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAiAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'provider' => ['required', 'in:openai,anthropic,gemini'],
            'model' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'token' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
