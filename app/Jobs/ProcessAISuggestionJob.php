<?php

namespace App\Jobs;

use App\Models\AiSuggestion;
use App\Services\AI\AIManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAISuggestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public AiSuggestion $suggestion)
    {
    }

    public function handle(AIManager $aiManager): void
    {
        // Stub: AI-assisted field suggestions will be wired in a later phase.
        $prompt = 'Review suggestion for field '.$this->suggestion->field_name;

        try {
            $aiManager->complete($prompt, 'ai_suggestion');
        } catch (\Throwable) {
            // Leave suggestion pending for manual review when providers fail.
        }
    }
}
