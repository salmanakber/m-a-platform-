<?php

namespace App\Services\AI;

use App\Models\AiRun;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;

class AIManager
{
    /** @var array<string, AIProviderInterface> */
    private array $providers;

    public function __construct(
        private SettingsService $settings,
        GeminiProvider $gemini,
        GroqProvider $groq,
        OpenAIProvider $openai,
        AnthropicProvider $anthropic
    ) {
        $this->providers = [
            $gemini->getName() => $gemini,
            $groq->getName() => $groq,
            $openai->getName() => $openai,
            $anthropic->getName() => $anthropic,
        ];
    }

    public function complete(string $prompt, ?string $purpose = null): string
    {
        $order = $this->providerOrder();
        $errors = [];

        foreach ($order as $providerName) {
            $provider = $this->providers[$providerName] ?? null;

            if ($provider === null || ! $provider->isConfigured()) {
                continue;
            }

            $run = AiRun::query()->create([
                'provider' => $providerName,
                'purpose' => $purpose,
                'status' => 'running',
                'prompt_excerpt' => mb_substr($prompt, 0, 500),
            ]);

            $started = microtime(true);

            try {
                $response = $provider->complete($prompt);
                $run->update([
                    'status' => 'completed',
                    'response' => $response,
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                ]);

                return $response;
            } catch (\Throwable $exception) {
                $errors[] = $providerName.': '.$exception->getMessage();
                $run->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                ]);
            }
        }

        throw new \RuntimeException(
            'All AI providers failed or are unconfigured. '.implode(' | ', $errors)
        );
    }

    /** @return list<string> */
    private function providerOrder(): array
    {
        $configured = $this->settings->get(
            SettingKey::AI_PROVIDER_ORDER,
            'gemini,groq,openai,anthropic'
        );

        $names = array_filter(array_map('trim', explode(',', (string) $configured)));

        if ($names === []) {
            return ['gemini', 'groq', 'openai', 'anthropic'];
        }

        return array_values($names);
    }
}
