@extends('layouts.admin')

@section('title', 'Einstellungen')

@section('content')
    <h1 style="font-family:var(--font-display);margin-top:0;">Einstellungen</h1>

    <nav class="admin-tabs">
        <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}" class="{{ $tab === 'general' ? 'is-active' : '' }}">Allgemein</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'footer']) }}" class="{{ $tab === 'footer' ? 'is-active' : '' }}">Footer / Kontakt</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'invoice']) }}" class="{{ $tab === 'invoice' ? 'is-active' : '' }}">Rechnung / Bank</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'maps']) }}" class="{{ $tab === 'maps' ? 'is-active' : '' }}">Maps</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'email']) }}" class="{{ $tab === 'email' ? 'is-active' : '' }}">E-Mail</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'ai']) }}" class="{{ $tab === 'ai' ? 'is-active' : '' }}">KI</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'promotion']) }}" class="{{ $tab === 'promotion' ? 'is-active' : '' }}">Promotionen</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'account']) }}" class="{{ in_array($tab, ['account', 'password'], true) ? 'is-active' : '' }}">Mein Konto</a>
    </nav>

    @if (! in_array($tab, ['account', 'password'], true))
    <form method="post" action="{{ route('admin.settings.update') }}" class="card">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="{{ $tab }}">

        @if ($tab === 'general')
            <div class="form-group">
                <label for="app_name">Seitenname</label>
                <input class="form-control" type="text" name="app_name" id="app_name" value="{{ old('app_name', $appName) }}">
            </div>
            <div class="form-group">
                <label for="admin_notification_email">Admin-Benachrichtigung E-Mail</label>
                <input class="form-control" type="email" name="admin_notification_email" id="admin_notification_email" value="{{ old('admin_notification_email', $adminNotificationEmail) }}">
            </div>
        @endif

        @if ($tab === 'footer')
            <p class="field-hint" style="margin-top:0;">Öffentlicher Footer-Kontakt (Adresse und E-Mail). Events pflegen Experten bzw. Admins unter dem jeweiligen Expertenprofil.</p>
            <div class="form-group">
                <label for="footer_contact_lines">Kontaktadresse (mehrzeilig)</label>
                <textarea class="form-control" name="footer_contact_lines" id="footer_contact_lines" rows="4">{{ old('footer_contact_lines', $footerContactLines) }}</textarea>
            </div>
            <div class="form-group">
                <label for="footer_contact_email">Kontakt-E-Mail</label>
                <input class="form-control" type="email" name="footer_contact_email" id="footer_contact_email" value="{{ old('footer_contact_email', $footerContactEmail) }}">
            </div>
        @endif

        @if ($tab === 'maps')
            <div class="form-group">
                <label for="maps_google_api_key">
                    Google Maps API-Schlüssel
                    @if ($mapsKeyMasked)
                        <span style="color:var(--stone);">({{ $mapsKeyMasked }})</span>
                    @endif
                </label>
                <input class="form-control" type="password" name="maps_google_api_key" id="maps_google_api_key" autocomplete="off" placeholder="Neuen Schlüssel eingeben">
            </div>
        @endif

        @if ($tab === 'email')
            <div class="form-group">
                <label for="resend_api_key">
                    Resend API-Schlüssel
                    @if ($resendKeyMasked)
                        <span style="color:var(--stone);">({{ $resendKeyMasked }})</span>
                    @endif
                </label>
                <input class="form-control" type="password" name="resend_api_key" id="resend_api_key" autocomplete="off">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="resend_from_email">Absender E-Mail</label>
                    <input class="form-control" type="email" name="resend_from_email" id="resend_from_email" value="{{ old('resend_from_email', $resendFromEmail) }}">
                </div>
                <div class="form-group">
                    <label for="resend_from_name">Absender Name</label>
                    <input class="form-control" type="text" name="resend_from_name" id="resend_from_name" value="{{ old('resend_from_name', $resendFromName) }}">
                </div>
            </div>
            <div class="form-group">
                <label for="resend_reply_to">Reply-To</label>
                <input class="form-control" type="email" name="resend_reply_to" id="resend_reply_to" value="{{ old('resend_reply_to', $resendReplyTo) }}">
            </div>
        @endif

        @if ($tab === 'ai')
            <p style="color:var(--stone);font-size:0.9rem;margin:0 0 1rem;">Ein Eintrag pro Anbieter — Modell und API-Schlüssel gehören zusammen. Reihenfolge = Fallback-Kette.</p>
            <div class="form-group">
                <label for="ai_provider_order">Provider-Reihenfolge (Fallback)</label>
                <input class="form-control" type="text" name="ai_provider_order" id="ai_provider_order" value="{{ old('ai_provider_order', $aiProviderOrder) }}" placeholder="gemini,groq,openai,anthropic">
            </div>

            @foreach ($aiProviders as $provider)
                <fieldset class="settings-provider" style="margin:0 0 1rem;padding:1rem;border:1px solid var(--line-soft);">
                    <legend style="font-weight:700;padding:0 0.35rem;">{{ $provider['label'] }}</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ai_{{ $provider['id'] }}_model">Modell</label>
                            <input class="form-control" type="text" name="ai_{{ $provider['id'] }}_model" id="ai_{{ $provider['id'] }}_model" value="{{ old('ai_'.$provider['id'].'_model', $provider['model']) }}">
                        </div>
                        <div class="form-group">
                            <label for="ai_{{ $provider['id'] }}_api_key">
                                API-Schlüssel
                                @if ($provider['masked'])
                                    <span style="color:var(--stone);">({{ $provider['masked'] }})</span>
                                @endif
                            </label>
                            <input class="form-control" type="password" name="ai_{{ $provider['id'] }}_api_key" id="ai_{{ $provider['id'] }}_api_key" autocomplete="off" placeholder="Neu eingeben zum Ersetzen">
                        </div>
                    </div>
                </fieldset>
            @endforeach
        @endif

        @if ($tab === 'promotion')
            <div class="form-group">
                <label for="promotion_monthly_price_chf">Fallback Monatspreis Promotion (CHF)</label>
                <input class="form-control" type="number" step="0.01" min="0" name="promotion_monthly_price_chf" id="promotion_monthly_price_chf" value="{{ old('promotion_monthly_price_chf', $promotionMonthlyPrice) }}">
                <p class="field-hint">Detailpreise je Position: <a href="{{ route('admin.promotion-prices.index') }}">Promo-Preismatrix</a>.</p>
            </div>
        @endif

        @if ($tab === 'invoice')
            <p class="field-hint" style="margin-top:0;">Absender, Bankverbindung und Standard-MwSt. für alle Rechnungs-E-Mails. Pro Rechnung können Zeitraum, Menge und MwSt. im Studio überschrieben werden.</p>
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_sender_name">Absender (Marke)</label>
                    <input class="form-control" type="text" name="invoice_sender_name" id="invoice_sender_name" value="{{ old('invoice_sender_name', $invoiceSenderName) }}">
                </div>
                <div class="form-group">
                    <label for="invoice_sender_company">Firma Absender</label>
                    <input class="form-control" type="text" name="invoice_sender_company" id="invoice_sender_company" value="{{ old('invoice_sender_company', $invoiceSenderCompany) }}">
                </div>
            </div>
            <div class="form-group">
                <label for="invoice_sender_address">Adresse Absender (mehrzeilig)</label>
                <textarea class="form-control" name="invoice_sender_address" id="invoice_sender_address" rows="3">{{ old('invoice_sender_address', $invoiceSenderAddress) }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_bank_name">Bank</label>
                    <input class="form-control" type="text" name="invoice_bank_name" id="invoice_bank_name" value="{{ old('invoice_bank_name', $invoiceBankName) }}">
                </div>
                <div class="form-group">
                    <label for="invoice_bank_iban">IBAN</label>
                    <input class="form-control" type="text" name="invoice_bank_iban" id="invoice_bank_iban" value="{{ old('invoice_bank_iban', $invoiceBankIban) }}" placeholder="CH00 0000 0000 0000 0000 0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_bank_bic">BIC / SWIFT</label>
                    <input class="form-control" type="text" name="invoice_bank_bic" id="invoice_bank_bic" value="{{ old('invoice_bank_bic', $invoiceBankBic) }}">
                </div>
                <div class="form-group">
                    <label for="invoice_vat_uid">MWST-Nr. / UID</label>
                    <input class="form-control" type="text" name="invoice_vat_uid" id="invoice_vat_uid" value="{{ old('invoice_vat_uid', $invoiceVatUid) }}" placeholder="CHE-xxx.xxx.xxx MWST">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_default_vat_rate">Standard-MwSt. %</label>
                    <input class="form-control" type="number" step="0.01" min="0" max="100" name="invoice_default_vat_rate" id="invoice_default_vat_rate" value="{{ old('invoice_default_vat_rate', $invoiceDefaultVatRate) }}" placeholder="leer = keine MwSt.">
                    <p class="field-hint">Leer lassen, solange keine MwSt. ausgewiesen wird. Später z.&nbsp;B. 8.1.</p>
                </div>
            </div>
            <div class="form-group">
                <label for="invoice_payment_hint">Zahlungsinfo (Text auf Rechnung)</label>
                <textarea class="form-control" name="invoice_payment_hint" id="invoice_payment_hint" rows="3">{{ old('invoice_payment_hint', $invoicePaymentHint) }}</textarea>
            </div>
        @endif

        <button type="submit" class="btn">Speichern</button>
    </form>
    @else
        <form method="post" action="{{ route('admin.settings.update') }}" class="card" style="margin-bottom:1rem;">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" value="account">
            <p class="field-hint" style="margin-top:0;">Name und E-Mail Ihres Admin-Kontos.</p>
            <div class="form-row">
                <div class="form-group">
                    <label for="admin_name">Name</label>
                    <input class="form-control" type="text" name="admin_name" id="admin_name" value="{{ old('admin_name', $adminUser->name ?? '') }}" required>
                    @error('admin_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_email">E-Mail</label>
                    <input class="form-control" type="email" name="admin_email" id="admin_email" value="{{ old('admin_email', $adminUser->email ?? '') }}" required>
                    @error('admin_email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="btn">Konto speichern</button>
        </form>

        <form method="post" action="{{ route('admin.settings.password') }}" class="card">
            @csrf
            @method('PUT')
            <p class="field-hint" style="margin-top:0;">Passwort ändern (aktuelles Passwort erforderlich).</p>
            <div class="form-group">
                <label for="current_password">Aktuelles Passwort</label>
                <input class="form-control" type="password" name="current_password" id="current_password" required autocomplete="current-password">
                @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Neues Passwort</label>
                    <input class="form-control" type="password" name="password" id="password" required autocomplete="new-password">
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Neues Passwort bestätigen</label>
                    <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="btn">Passwort speichern</button>
        </form>
    @endif
@endsection
