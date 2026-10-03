@extends('layouts.admin')

@section('title', 'Einstellungen')

@section('content')
    <h1 style="font-family:var(--font-display);margin-top:0;">Einstellungen</h1>

    <nav class="admin-tabs">
        <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}" class="{{ $tab === 'general' ? 'is-active' : '' }}">Allgemein</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'maps']) }}" class="{{ $tab === 'maps' ? 'is-active' : '' }}">Maps</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'email']) }}" class="{{ $tab === 'email' ? 'is-active' : '' }}">E-Mail</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'ai']) }}" class="{{ $tab === 'ai' ? 'is-active' : '' }}">KI</a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'promotion']) }}" class="{{ $tab === 'promotion' ? 'is-active' : '' }}">Promotionen</a>
    </nav>

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
                <label for="promotion_monthly_price_chf">Monatspreis Promotion (CHF)</label>
                <input class="form-control" type="number" step="0.01" min="0" name="promotion_monthly_price_chf" id="promotion_monthly_price_chf" value="{{ old('promotion_monthly_price_chf', $promotionMonthlyPrice) }}">
            </div>
        @endif

        <button type="submit" class="btn">Speichern</button>
    </form>
@endsection
