<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    public function getName(): string;

    public function isConfigured(): bool;

    /**
     * @throws \RuntimeException
     */
    public function complete(string $prompt): string;
}
