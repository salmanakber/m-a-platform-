<?php

namespace App\Services\Email;

use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Support\Facades\Http;

class ResendMailer
{
    public function __construct(private SettingsService $settings)
    {
    }

    /**
     * @param  list<string>  $to
     * @param  list<string>  $bcc
     */
    public function send(
        array $to,
        string $subject,
        string $html,
        ?string $text = null,
        array $bcc = []
    ): void {
        $apiKey = $this->settings->get(SettingKey::RESEND_API_KEY);
        $fromEmail = $this->settings->get(SettingKey::RESEND_FROM_EMAIL, 'noreply@nachfolge-experten.ch');
        $fromName = $this->settings->get(SettingKey::RESEND_FROM_NAME, 'Nachfolge-Experten.ch');

        if ($apiKey === null || $apiKey === '') {
            throw new \RuntimeException('Resend API key is not configured.');
        }

        $payload = [
            'from' => "{$fromName} <{$fromEmail}>",
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        if ($bcc !== []) {
            $payload['bcc'] = $bcc;
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.resend.com/emails', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Resend API error: '.$response->body());
        }
    }
}
