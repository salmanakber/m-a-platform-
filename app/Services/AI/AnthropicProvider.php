<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AIProviderInterface
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    public function isConfigured(): bool
    {
        $apiKey = $this->settings->get(SettingKey::AI_ANTHROPIC_API_KEY);

        return $apiKey !== null && $apiKey !== '';
    }

    public function complete(string $prompt): string
    {
        $apiKey = $this->settings->get(SettingKey::AI_ANTHROPIC_API_KEY);
        $model = $this->settings->get(SettingKey::AI_ANTHROPIC_MODEL, 'claude-3-5-haiku-20241022');

        if ($apiKey === null || $apiKey === '') {
            throw new \RuntimeException('Anthropic API key is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4096,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'content.0.text');

        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('Anthropic returned an empty response.');
        }

        return $text;
    }
}
