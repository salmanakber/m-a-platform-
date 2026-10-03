<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\Expert;
use App\Models\Lead;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;

class NotificationService
{
    public function __construct(
        private ResendMailer $mailer,
        private SettingsService $settings
    ) {
    }

    public function sendLeadNotifications(Lead $lead): void
    {
        $lead->loadMissing(['expert', 'canton']);

        $this->sendTemplated(
            'lead_notification_expert',
            [$lead->expert->email],
            $this->leadPlaceholders($lead),
            [
                'headline' => 'Neue Lead-Anfrage',
                'preheader' => 'Neue Anfrage von '.$lead->owner_name,
                'ctaUrl' => rtrim(config('app.url'), '/').'/login',
                'ctaLabel' => 'Zum Expertenbereich',
            ]
        );

        $lead->forceFill(['expert_notified_at' => now()])->save();

        $this->sendTemplated(
            'lead_confirmation_customer',
            [$lead->email],
            $this->leadPlaceholders($lead),
            [
                'headline' => 'Anfrage übermittelt',
                'preheader' => 'Ihre Anfrage an '.$lead->expert->company_name.' wurde weitergeleitet',
                'ctaUrl' => rtrim(config('app.url'), '/'),
                'ctaLabel' => 'Zur Website',
            ]
        );

        $lead->forceFill(['customer_notified_at' => now()])->save();
    }

    public function sendExpertRegistrationWelcome(Expert $expert): void
    {
        $expert->loadMissing('user');

        $this->sendTemplated(
            'expert_registration_welcome',
            [$expert->email],
            [
                'contact_name' => trim($expert->contact_person_name.' '.$expert->contact_person_last_name),
                'company_name' => $expert->company_name,
            ],
            [
                'headline' => 'Willkommen als Experte',
                'preheader' => 'Ihre Registrierung für '.$expert->company_name.' wurde erhalten',
                'ctaUrl' => rtrim(config('app.url'), '/').'/login',
                'ctaLabel' => 'Zum Login',
            ]
        );
    }

    public function notifyAdminNewExpertRegistration(Expert $expert): void
    {
        $adminEmail = $this->settings->get(SettingKey::ADMIN_NOTIFICATION_EMAIL);

        if ($adminEmail === null || $adminEmail === '') {
            return;
        }

        $subject = 'Neue Experten-Registrierung: '.$expert->company_name;
        $body = view('emails.partials.admin-new-expert', [
            'company' => $expert->company_name,
            'email' => $expert->email,
            'contact' => trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? '')),
            'reviewUrl' => rtrim(config('app.url'), '/').'/admin/experten/'.$expert->id,
        ])->render();

        $this->mailer->send(
            [$adminEmail],
            $subject,
            $this->wrap($subject, $body, [
                'headline' => 'Neue Registrierung',
                'preheader' => $expert->company_name.' wartet auf Prüfung',
                'ctaUrl' => rtrim(config('app.url'), '/').'/admin/experten/'.$expert->id,
                'ctaLabel' => 'Profil prüfen',
            ])
        );
    }

    public function sendPasswordReset(\App\Models\User $user, string $resetUrl): void
    {
        $expires = (string) config('auth.passwords.users.expire', 60);
        $subject = 'Passwort zurücksetzen — nachfolge-experten.ch';

        $bodyHtml = view('emails.partials.password-reset', [
            'name' => $user->name ?: 'Benutzer',
            'expiresMinutes' => $expires,
            'resetUrl' => $resetUrl,
        ])->render();

        $this->mailer->send(
            [$user->getEmailForPasswordReset()],
            $subject,
            $this->wrap($subject, $bodyHtml, [
                'headline' => 'Passwort zurücksetzen',
                'preheader' => 'Sicheren Link zum Zurücksetzen Ihres Passworts bei nachfolge-experten.ch',
                'ctaUrl' => $resetUrl,
                'ctaLabel' => 'Neues Passwort festlegen',
            ]),
            "Guten Tag {$user->name},\n\nsetzen Sie Ihr Passwort hier zurück:\n{$resetUrl}\n\nDer Link ist {$expires} Minuten gültig.\n\nnachfolge-experten.ch"
        );
    }

    /**
     * @param  list<string>  $recipients
     * @param  array<string, string|null>  $placeholders
     * @param  array{headline?: string, preheader?: string, ctaUrl?: string, ctaLabel?: string}  $layout
     */
    private function sendTemplated(string $key, array $recipients, array $placeholders, array $layout = []): void
    {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template === null) {
            throw new \RuntimeException("Email template [{$key}] not found.");
        }

        $subject = $this->render($template->subject, $placeholders);
        $bodyHtml = $this->render($template->body_html, $placeholders, true);
        $bodyText = $template->body_text ? $this->render($template->body_text, $placeholders) : null;

        $this->mailer->send(
            $recipients,
            $subject,
            $this->wrap($subject, $bodyHtml, $layout),
            $bodyText
        );
    }

    /**
     * @param  array{headline?: string, preheader?: string, ctaUrl?: string, ctaLabel?: string}  $layout
     */
    private function wrap(string $subject, string $bodyHtml, array $layout = []): string
    {
        return view('emails.layout', [
            'subject' => $subject,
            'bodyHtml' => $bodyHtml,
            'headline' => $layout['headline'] ?? null,
            'preheader' => $layout['preheader'] ?? null,
            'ctaUrl' => $layout['ctaUrl'] ?? null,
            'ctaLabel' => $layout['ctaLabel'] ?? null,
        ])->render();
    }

    /**
     * @param  array<string, string|null>  $placeholders
     */
    private function render(string $content, array $placeholders, bool $forHtml = false): string
    {
        $search = [];
        $replace = [];

        foreach ($placeholders as $key => $value) {
            $search[] = '{{'.$key.'}}';
            $raw = $value ?? '';
            $replace[] = $forHtml ? nl2br(e($raw), false) : $raw;
        }

        return str_replace($search, $replace, $content);
    }

    /** @return array<string, string|null> */
    private function leadPlaceholders(Lead $lead): array
    {
        return [
            'owner_name' => $lead->owner_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'message' => $lead->message,
            'company_name' => $lead->company_name,
            'industry' => $lead->industry,
            'canton_name' => $lead->canton?->name_de,
            'expert_company_name' => $lead->expert->company_name,
        ];
    }
}
