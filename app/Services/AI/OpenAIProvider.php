<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        $apiKey = $this->settings->get(SettingKey::AI_OPENAI_API_KEY);

        return $apiKey !== null && $apiKey !== '';
    }

    public function complete(string $prompt): string
    {
        $apiKey = $this->settings->get(SettingKey::AI_OPENAI_API_KEY);
        $model = $this->settings->get(SettingKey::AI_OPENAI_MODEL, 'gpt-4o-mini');

        if ($apiKey === null || $apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('OpenAI returned an empty response.');
        }

        return $text;
    }
}
