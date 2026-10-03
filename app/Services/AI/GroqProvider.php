<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;

class GroqProvider implements AIProviderInterface
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function getName(): string
    {
        return 'groq';
    }

    public function isConfigured(): bool
    {
        $apiKey = $this->settings->get(SettingKey::AI_GROQ_API_KEY);

        return $apiKey !== null && $apiKey !== '';
    }

    public function complete(string $prompt): string
    {
        $apiKey = $this->settings->get(SettingKey::AI_GROQ_API_KEY);
        $model = $this->settings->get(SettingKey::AI_GROQ_MODEL, 'llama-3.1-8b-instant');

        if ($apiKey === null || $apiKey === '') {
            throw new \RuntimeException('Groq API key is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Groq request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('Groq returned an empty response.');
        }

        return $text;
    }
}
