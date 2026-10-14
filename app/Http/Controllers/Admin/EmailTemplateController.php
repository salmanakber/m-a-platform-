<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Email\ResendMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function __construct(private ResendMailer $mailer)
    {
    }

    public function index(): View
    {
        $templates = EmailTemplate::query()->orderBy('key')->get();

        return view('admin.email-templates.index', [
            'templates' => $templates,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', [
            'template' => $emailTemplate,
            'samplePlaceholders' => $this->samplePlaceholders(),
            'previewHtml' => $this->renderPreview(
                $emailTemplate->subject,
                $emailTemplate->body_html
            ),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
        ]);

        $emailTemplate->update($validated);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', 'Vorlage gespeichert.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
        ]);

        return response()->json([
            'html' => $this->renderPreview($validated['subject'], $validated['body_html']),
            'subject' => $this->replacePlaceholders($validated['subject'], $this->samplePlaceholders()),
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
        ]);

        $to = $validated['test_email'] ?: Auth::user()?->email;
        if (! $to) {
            return back()->with('error', 'Keine Test-E-Mail-Adresse verfügbar.');
        }

        $subject = $validated['subject'] ?? $emailTemplate->subject;
        $body = $validated['body_html'] ?? $emailTemplate->body_html;

        try {
            $this->mailer->send(
                [$to],
                '[TEST] '.$this->replacePlaceholders($subject, $this->samplePlaceholders()),
                $this->renderPreview($subject, $body)
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Testmail fehlgeschlagen: '.$e->getMessage());
        }

        return back()->with('success', 'Testmail an '.$to.' gesendet.');
    }

    private function renderPreview(string $subject, string $bodyHtml): string
    {
        $placeholders = $this->samplePlaceholders();
        $resolvedSubject = $this->replacePlaceholders($subject, $placeholders);
        $resolvedBody = $this->replacePlaceholders($bodyHtml, $placeholders, true);

        return view('emails.layout', [
            'subject' => $resolvedSubject,
            'bodyHtml' => $resolvedBody,
            'headline' => 'Vorschau',
            'preheader' => $resolvedSubject,
            'ctaUrl' => rtrim(config('app.url'), '/').'/login',
            'ctaLabel' => 'Zum Login',
        ])->render();
    }

    /** @return array<string, string> */
    private function samplePlaceholders(): array
    {
        $invoiceDocument = view('emails.partials.invoice-document', [
            'invoiceNumber' => 'NE-'.now()->format('Y').'-00042',
            'invoiceDate' => now()->format('d.m.Y'),
            'paymentStatus' => 'pending',
            'companyName' => 'Muster M&A AG',
            'contactName' => 'Anna Beispiel',
            'email' => 'anna@muster-ma.ch',
            'serviceTitle' => 'Promotion Platz 1',
            'serviceDetail' => 'Zürich · Position 1',
            'amountFormatted' => number_format(290, 2, '.', "'"),
            'currency' => 'CHF',
            'notes' => 'Promotion Zürich · Position 1',
            'paymentHint' => 'Bitte überweisen Sie den Betrag unter Angabe der Rechnungsnummer.',
        ])->render();

        return [
            'owner_name' => 'Max Muster',
            'email' => 'max.muster@example.ch',
            'phone' => '+41 44 123 45 67',
            'message' => 'Wir planen eine Unternehmensnachfolge und suchen eine Erstberatung.',
            'company_name' => 'Muster M&A AG',
            'industry' => 'Maschinenbau',
            'canton_name' => 'Zürich',
            'expert_company_name' => 'KMU Beratungen GmbH',
            'contact_name' => 'Anna Beispiel',
            'name' => 'Anna Beispiel',
            'reset_url' => rtrim(config('app.url'), '/').'/passwort-zuruecksetzen/beispiel-token',
            'expires_minutes' => '60',
            'invoice_number' => 'NE-'.now()->format('Y').'-00042',
            'invoice_date' => now()->format('d.m.Y'),
            'amount' => number_format(290, 2, '.', "'"),
            'currency' => 'CHF',
            'payment_status' => 'pending',
            'payment_status_label' => 'Offen',
            'service_title' => 'Promotion Platz 1',
            'service_detail' => 'Zürich · Position 1',
            'notes' => 'Promotion Zürich · Position 1',
            'invoice_document' => $invoiceDocument,
        ];
    }

    /** @param  array<string, string>  $placeholders */
    private function replacePlaceholders(string $content, array $placeholders, bool $forHtml = false): string
    {
        $rawKeys = ['invoice_document'];
        $search = [];
        $replace = [];
        foreach ($placeholders as $key => $value) {
            $search[] = '{{'.$key.'}}';
            if (in_array($key, $rawKeys, true)) {
                $replace[] = $value;
            } else {
                $replace[] = $forHtml ? e($value) : $value;
            }
        }

        return str_replace($search, $replace, $content);
    }
}
