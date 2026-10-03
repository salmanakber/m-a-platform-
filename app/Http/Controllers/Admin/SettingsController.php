<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SettingKey;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function edit(): View
    {
        $tab = request()->query('tab', 'general');

        return view('admin.settings.edit', [
            'tab' => $tab,
            'appName' => $this->settings->get(SettingKey::APP_NAME, config('app.name')),
            'adminNotificationEmail' => $this->settings->get(SettingKey::ADMIN_NOTIFICATION_EMAIL),
            'promotionMonthlyPrice' => $this->settings->get(SettingKey::PROMOTION_MONTHLY_PRICE_CHF, '0'),
            'mapsKeyMasked' => $this->settings->getMasked(SettingKey::GOOGLE_MAPS_API_KEY),
            'aiProviderOrder' => $this->settings->get(SettingKey::AI_PROVIDER_ORDER, 'gemini,groq,openai,anthropic'),
            'resendKeyMasked' => $this->settings->getMasked(SettingKey::RESEND_API_KEY),
            'resendFromEmail' => $this->settings->get(SettingKey::RESEND_FROM_EMAIL),
            'resendFromName' => $this->settings->get(SettingKey::RESEND_FROM_NAME),
            'resendReplyTo' => $this->settings->get(SettingKey::RESEND_REPLY_TO),
            'geminiKeyMasked' => $this->settings->getMasked(SettingKey::AI_GEMINI_API_KEY),
            'groqKeyMasked' => $this->settings->getMasked(SettingKey::AI_GROQ_API_KEY),
            'openaiKeyMasked' => $this->settings->getMasked(SettingKey::AI_OPENAI_API_KEY),
            'anthropicKeyMasked' => $this->settings->getMasked(SettingKey::AI_ANTHROPIC_API_KEY),
            'geminiModel' => $this->settings->get(SettingKey::AI_GEMINI_MODEL),
            'groqModel' => $this->settings->get(SettingKey::AI_GROQ_MODEL),
            'openaiModel' => $this->settings->get(SettingKey::AI_OPENAI_MODEL),
            'anthropicModel' => $this->settings->get(SettingKey::AI_ANTHROPIC_MODEL),
            'aiProviders' => [
                ['id' => 'gemini', 'label' => 'Google Gemini', 'model' => $this->settings->get(SettingKey::AI_GEMINI_MODEL), 'masked' => $this->settings->getMasked(SettingKey::AI_GEMINI_API_KEY)],
                ['id' => 'groq', 'label' => 'Groq', 'model' => $this->settings->get(SettingKey::AI_GROQ_MODEL), 'masked' => $this->settings->getMasked(SettingKey::AI_GROQ_API_KEY)],
                ['id' => 'openai', 'label' => 'OpenAI', 'model' => $this->settings->get(SettingKey::AI_OPENAI_MODEL), 'masked' => $this->settings->getMasked(SettingKey::AI_OPENAI_API_KEY)],
                ['id' => 'anthropic', 'label' => 'Anthropic', 'model' => $this->settings->get(SettingKey::AI_ANTHROPIC_MODEL), 'masked' => $this->settings->getMasked(SettingKey::AI_ANTHROPIC_API_KEY)],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'general');

        $validated = $request->validate([
            'tab' => ['nullable', 'string', 'in:general,maps,email,ai,promotion'],
            'app_name' => ['nullable', 'string', 'max:255'],
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
            'promotion_monthly_price_chf' => ['nullable', 'numeric', 'min:0'],
            'maps_google_api_key' => ['nullable', 'string'],
            'ai_provider_order' => ['nullable', 'string', 'max:255'],
            'resend_api_key' => ['nullable', 'string'],
            'resend_from_email' => ['nullable', 'email', 'max:255'],
            'resend_from_name' => ['nullable', 'string', 'max:255'],
            'resend_reply_to' => ['nullable', 'email', 'max:255'],
            'ai_gemini_api_key' => ['nullable', 'string'],
            'ai_groq_api_key' => ['nullable', 'string'],
            'ai_openai_api_key' => ['nullable', 'string'],
            'ai_anthropic_api_key' => ['nullable', 'string'],
            'ai_gemini_model' => ['nullable', 'string', 'max:120'],
            'ai_groq_model' => ['nullable', 'string', 'max:120'],
            'ai_openai_model' => ['nullable', 'string', 'max:120'],
            'ai_anthropic_model' => ['nullable', 'string', 'max:120'],
        ]);

        if ($tab === 'general') {
            if ($request->filled('app_name')) {
                $this->settings->set(SettingKey::APP_NAME, $validated['app_name'], SettingKey::GROUP_GENERAL);
            }

            $this->settings->set(
                SettingKey::ADMIN_NOTIFICATION_EMAIL,
                $validated['admin_notification_email'] ?? null,
                SettingKey::GROUP_EMAIL
            );
        }

        if ($tab === 'maps' && $request->filled('maps_google_api_key')) {
            $this->settings->set(
                SettingKey::GOOGLE_MAPS_API_KEY,
                $validated['maps_google_api_key'],
                SettingKey::GROUP_MAPS,
                true,
                true
            );
        }

        if ($tab === 'email') {
            $this->updateEncryptedIfFilled(SettingKey::RESEND_API_KEY, $request->input('resend_api_key'), SettingKey::GROUP_EMAIL);
            $this->settings->set(SettingKey::RESEND_FROM_EMAIL, $validated['resend_from_email'] ?? null, SettingKey::GROUP_EMAIL);
            $this->settings->set(SettingKey::RESEND_FROM_NAME, $validated['resend_from_name'] ?? null, SettingKey::GROUP_EMAIL);
            $this->settings->set(SettingKey::RESEND_REPLY_TO, $validated['resend_reply_to'] ?? null, SettingKey::GROUP_EMAIL);
        }

        if ($tab === 'ai') {
            if ($request->filled('ai_provider_order')) {
                $this->settings->set(
                    SettingKey::AI_PROVIDER_ORDER,
                    $validated['ai_provider_order'],
                    SettingKey::GROUP_AI
                );
            }

            $this->updateEncryptedIfFilled(SettingKey::AI_GEMINI_API_KEY, $request->input('ai_gemini_api_key'), SettingKey::GROUP_AI);
            $this->updateEncryptedIfFilled(SettingKey::AI_GROQ_API_KEY, $request->input('ai_groq_api_key'), SettingKey::GROUP_AI);
            $this->updateEncryptedIfFilled(SettingKey::AI_OPENAI_API_KEY, $request->input('ai_openai_api_key'), SettingKey::GROUP_AI);
            $this->updateEncryptedIfFilled(SettingKey::AI_ANTHROPIC_API_KEY, $request->input('ai_anthropic_api_key'), SettingKey::GROUP_AI);

            if ($request->has('ai_gemini_model')) {
                $this->settings->set(SettingKey::AI_GEMINI_MODEL, $validated['ai_gemini_model'] ?? null, SettingKey::GROUP_AI);
            }
            if ($request->has('ai_groq_model')) {
                $this->settings->set(SettingKey::AI_GROQ_MODEL, $validated['ai_groq_model'] ?? null, SettingKey::GROUP_AI);
            }
            if ($request->has('ai_openai_model')) {
                $this->settings->set(SettingKey::AI_OPENAI_MODEL, $validated['ai_openai_model'] ?? null, SettingKey::GROUP_AI);
            }
            if ($request->has('ai_anthropic_model')) {
                $this->settings->set(SettingKey::AI_ANTHROPIC_MODEL, $validated['ai_anthropic_model'] ?? null, SettingKey::GROUP_AI);
            }
        }

        if ($tab === 'promotion' && $request->filled('promotion_monthly_price_chf')) {
            $this->settings->set(
                SettingKey::PROMOTION_MONTHLY_PRICE_CHF,
                (string) $validated['promotion_monthly_price_chf'],
                SettingKey::GROUP_PROMOTION
            );
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => $tab])
            ->with('success', 'Einstellungen gespeichert.');
    }

    private function updateEncryptedIfFilled(string $key, ?string $value, string $group): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->settings->set($key, $value, $group, true, true);
    }
}
