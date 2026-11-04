<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Settings\SettingsService;
use App\Support\InvoicePaymentStatus;
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

    public function sendInvoiceConfirmation(Invoice $invoice): void
    {
        $this->sendInvoiceEmail($invoice, 'invoice_confirmation', [
            'headline' => 'Bestätigung vor Rechnung',
            'preheader' => 'Bitte prüfen Sie die Angaben zu '.$invoice->invoice_number,
            'ctaUrl' => route('expert.invoices.show', $invoice),
            'ctaLabel' => 'Zum Login / Rechnung',
            'paymentHint' => 'Dies ist noch keine Rechnung — nur die Bestätigung der gewählten Leistung. Die Rechnung folgt in einer separaten E-Mail.',
        ]);
    }

    public function sendInvoiceCreated(Invoice $invoice): void
    {
        $hint = $this->settings->get(
            SettingKey::INVOICE_PAYMENT_HINT,
            'Bitte überweisen Sie den Betrag unter Angabe der Rechnungsnummer.'
        );

        $this->sendInvoiceEmail($invoice, 'invoice_created', [
            'headline' => 'Ihre Rechnung',
            'preheader' => 'Rechnung '.$invoice->invoice_number.' — bitte zur Zahlung',
            'ctaUrl' => route('expert.invoices.show', $invoice),
            'ctaLabel' => 'Rechnung im Login öffnen',
            'paymentHint' => is_string($hint) && $hint !== '' ? $hint : 'Bitte überweisen Sie den Betrag unter Angabe der Rechnungsnummer.',
        ]);
    }

    public function sendInvoicePaid(Invoice $invoice): void
    {
        $this->sendInvoiceEmail($invoice, 'invoice_paid', [
            'headline' => 'Zahlung bestätigt',
            'preheader' => 'Rechnung '.$invoice->invoice_number.' ist bezahlt',
            'ctaUrl' => route('expert.invoices.show', $invoice),
            'ctaLabel' => 'Zum Login / Rechnung',
            'paymentHint' => null,
        ]);
    }

    /**
     * @param  array{headline?: string, preheader?: string, ctaUrl?: string, ctaLabel?: string, paymentHint?: string|null}  $layout
     */
    private function sendInvoiceEmail(Invoice $invoice, string $templateKey, array $layout): void
    {
        $invoice->loadMissing(['expert', 'product', 'promotion.canton', 'linkedPromotion.canton']);
        $expert = $invoice->expert;
        $to = filled($invoice->billing_email) ? (string) $invoice->billing_email : (string) ($expert->email ?? '');

        if ($expert === null || $to === '') {
            throw new \RuntimeException('Invoice has no billing e-mail recipient.');
        }

        $placeholders = $this->invoicePlaceholders($invoice, $layout['paymentHint'] ?? null);
        $adminBcc = [];
        $adminEmail = $this->settings->get(SettingKey::ADMIN_NOTIFICATION_EMAIL);
        if (is_string($adminEmail) && $adminEmail !== '' && strcasecmp($adminEmail, $to) !== 0) {
            $adminBcc[] = $adminEmail;
        }

        $this->sendTemplated(
            $templateKey,
            [$to],
            $placeholders,
            [
                'headline' => $layout['headline'] ?? 'Rechnung',
                'preheader' => $layout['preheader'] ?? null,
                'ctaUrl' => $layout['ctaUrl'] ?? null,
                'ctaLabel' => $layout['ctaLabel'] ?? null,
            ],
            ['invoice_document'],
            $adminBcc
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function invoicePlaceholders(Invoice $invoice, ?string $paymentHint = null): array
    {
        $invoice->loadMissing(['expert', 'product', 'promotion.canton', 'linkedPromotion.canton']);
        $expert = $invoice->expert;
        $promo = $invoice->linkedPromotion ?? $invoice->promotion;

        $serviceTitle = filled($invoice->service_title)
            ? (string) $invoice->service_title
            : 'Leistung';
        $serviceDetail = (string) ($invoice->service_detail ?? '');

        if ($promo && blank($invoice->service_title)) {
            $canton = $promo->canton->name_de ?? ($promo->canton->code ?? '');
            $serviceDetail = trim($canton.' · Position '.$promo->position_number);
            $serviceTitle = 'Promotion Platz '.$promo->position_number;
        } elseif ($invoice->product && blank($invoice->service_title)) {
            $serviceTitle = $invoice->product->name;
            $serviceDetail = (string) ($invoice->product->description ?? '');
        }

        $contact = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));
        if ($contact === '') {
            $contact = $expert->company_name ?? 'Experte';
        }

        $billingEmail = filled($invoice->billing_email)
            ? (string) $invoice->billing_email
            : (string) ($expert->billing_email ?: $expert->email ?? '');
        $billingName = filled($invoice->billing_name)
            ? (string) $invoice->billing_name
            : (string) ($expert->billing_name ?: $expert->company_name ?? '—');
        $billingAddress = filled($invoice->billing_address_line)
            ? (string) $invoice->billing_address_line
            : (string) ($expert->billing_address_line ?? '');
        $billingPostal = filled($invoice->billing_postal_code)
            ? (string) $invoice->billing_postal_code
            : (string) ($expert->billing_postal_code ?? '');
        $billingCity = filled($invoice->billing_city)
            ? (string) $invoice->billing_city
            : (string) ($expert->billing_city ?? '');

        $net = $invoice->netAmount();
        $vat = $invoice->vatAmount();
        $gross = $invoice->grossAmount();
        $amountFormatted = number_format($gross, 2, '.', "'");
        $statusLabels = [
            InvoicePaymentStatus::PENDING => 'Offen',
            InvoicePaymentStatus::PAID => 'Bezahlt',
            InvoicePaymentStatus::OVERDUE => 'Überfällig',
            InvoicePaymentStatus::CANCELLED => 'Storniert',
        ];

        $sender = [
            'sender_name' => $this->settings->get(SettingKey::INVOICE_SENDER_NAME, 'nachfolge-experten.ch'),
            'sender_company' => $this->settings->get(SettingKey::INVOICE_SENDER_COMPANY, 'KMU Beratungen GmbH'),
            'sender_address' => $this->settings->get(
                SettingKey::INVOICE_SENDER_ADDRESS,
                "Brünigstrasse 144\n6060 Sarnen · Schweiz"
            ),
            'bank_name' => $this->settings->get(SettingKey::INVOICE_BANK_NAME),
            'bank_iban' => $this->settings->get(SettingKey::INVOICE_BANK_IBAN),
            'bank_bic' => $this->settings->get(SettingKey::INVOICE_BANK_BIC),
            'vat_uid' => $this->settings->get(SettingKey::INVOICE_VAT_UID),
        ];
        $periodLabel = null;
        if ($invoice->period_start || $invoice->period_end) {
            $from = optional($invoice->period_start)->format('d.m.Y') ?: '—';
            $to = optional($invoice->period_end)->format('d.m.Y') ?: '—';
            $periodLabel = $from.' – '.$to;
        }

        $qty = (float) ($invoice->quantity ?: 1);
        $unit = $invoice->unit_price !== null ? (float) $invoice->unit_price : $net;
        $qtyLabel = rtrim(rtrim(number_format($qty, 2, '.', "'"), '0'), '.').' × '.number_format($unit, 2, '.', "'").' CHF';

        $document = view('emails.partials.invoice-document', [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => optional($invoice->invoice_date)->format('d.m.Y') ?: now()->format('d.m.Y'),
            'paymentStatus' => $invoice->payment_status,
            'companyName' => $billingName,
            'contactName' => $contact,
            'email' => $billingEmail,
            'billingAddress' => $billingAddress,
            'billingPostal' => $billingPostal,
            'billingCity' => $billingCity,
            'serviceTitle' => $serviceTitle,
            'serviceDetail' => $serviceDetail,
            'periodLabel' => $periodLabel,
            'quantityLabel' => $qtyLabel,
            'netFormatted' => number_format($net, 2, '.', "'"),
            'vatRate' => $invoice->vat_rate !== null ? (float) $invoice->vat_rate : null,
            'vatFormatted' => number_format($vat, 2, '.', "'"),
            'amountFormatted' => $amountFormatted,
            'currency' => $invoice->currency ?: 'CHF',
            'notes' => $invoice->notes,
            'paymentHint' => $paymentHint,
            'senderName' => $sender['sender_name'] ?? 'nachfolge-experten.ch',
            'senderCompany' => $sender['sender_company'] ?? 'KMU Beratungen GmbH',
            'senderAddress' => $sender['sender_address'] ?? '',
            'bankName' => $sender['bank_name'] ?? null,
            'bankIban' => $sender['bank_iban'] ?? null,
            'bankBic' => $sender['bank_bic'] ?? null,
            'vatUid' => $sender['vat_uid'] ?? null,
        ])->render();

        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->format('d.m.Y') ?: '',
            'period' => $periodLabel ?? '',
            'quantity' => (string) $qty,
            'unit_price' => number_format($unit, 2, '.', "'"),
            'amount' => $amountFormatted,
            'currency' => $invoice->currency ?: 'CHF',
            'vat_rate' => $invoice->vat_rate !== null ? (string) $invoice->vat_rate : '',
            'payment_status' => $invoice->payment_status,
            'payment_status_label' => $statusLabels[$invoice->payment_status] ?? $invoice->payment_status,
            'company_name' => $billingName,
            'contact_name' => $contact,
            'email' => $billingEmail,
            'service_title' => $serviceTitle,
            'service_detail' => $serviceDetail,
            'notes' => $invoice->notes,
            'bank_iban' => $sender['bank_iban'] ?? '',
            'bank_name' => $sender['bank_name'] ?? '',
            'invoice_document' => $document,
        ];
    }

    /**
     * @param  list<string>  $recipients
     * @param  array<string, string|null>  $placeholders
     * @param  array{headline?: string, preheader?: string, ctaUrl?: string, ctaLabel?: string}  $layout
     * @param  list<string>  $rawHtmlKeys  placeholder keys inserted without escaping
     * @param  list<string>  $bcc
     */
    private function sendTemplated(
        string $key,
        array $recipients,
        array $placeholders,
        array $layout = [],
        array $rawHtmlKeys = [],
        array $bcc = []
    ): void {
        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template === null) {
            throw new \RuntimeException("Email template [{$key}] not found.");
        }

        $subject = $this->render($template->subject, $placeholders);
        $bodyHtml = $this->render($template->body_html, $placeholders, true, $rawHtmlKeys);
        $bodyText = $template->body_text ? $this->render($template->body_text, $placeholders) : null;

        $this->mailer->send(
            $recipients,
            $subject,
            $this->wrap($subject, $bodyHtml, $layout),
            $bodyText,
            $bcc
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
     * @param  list<string>  $rawHtmlKeys
     */
    private function render(string $content, array $placeholders, bool $forHtml = false, array $rawHtmlKeys = []): string
    {
        $search = [];
        $replace = [];

        foreach ($placeholders as $key => $value) {
            $search[] = '{{'.$key.'}}';
            $raw = $value ?? '';
            if (in_array($key, $rawHtmlKeys, true)) {
                $replace[] = $raw;
            } else {
                $replace[] = $forHtml ? nl2br(e($raw), false) : $raw;
            }
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
