<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AIProviderInterface
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function getName(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        $apiKey = $this->settings->get(SettingKey::AI_GEMINI_API_KEY);

        return $apiKey !== null && $apiKey !== '';
    }

    public function complete(string $prompt): string
    {
        $apiKey = $this->settings->get(SettingKey::AI_GEMINI_API_KEY);
        $model = $this->settings->get(SettingKey::AI_GEMINI_MODEL, 'gemini-1.5-flash');

        if ($apiKey === null || $apiKey === '') {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $response = Http::timeout(120)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('Gemini returned an empty response.');
        }

        return $text;
    }
}
