@extends('layouts.expert')

@section('title', 'Videos')

@section('breadcrumbs')
    <a href="{{ route('expert.dashboard') }}">Dashboard</a>
    <span>/</span>
    <span>Videos</span>
@endsection

@section('content')
<div class="xp-page">
    <header class="xp-hero xp-hero--compact">
        <div class="xp-hero__copy">
            <p class="xp-hero__kicker">Medien</p>
            <h1>Meine Videos</h1>
            <p>
                Ein Video ist kostenlos. Weitere Videos werden nach Zahlung freigeschaltet
                ({{ number_format($extraPrice, 2, '.', "'") }} {{ $extraCurrency }} / Video).
                Top-5-Platzierungen auf der Startseite können separat verrechnet werden.
            </p>
        </div>
    </header>

    <div class="xp-panel" style="margin-bottom:1rem;">
        <div class="xp-panel__head">
            <div>
                <h2>Video hinzufügen</h2>
                <p>
                    @if ($freeAvailable)
                        Nächstes Video: <strong>kostenlos</strong>
                    @else
                        Nächstes Video: <strong>kostenpflichtig</strong> — Rechnung wird als Entwurf erstellt
                    @endif
                </p>
            </div>
        </div>
        <form method="post" action="{{ route('expert.videos.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Titel (optional)</label>
                    <input class="form-control" type="text" name="title" id="title" value="{{ old('title') }}" placeholder="z. B. Firmenvorstellung">
                </div>
                <div class="form-group">
                    <label for="url">Video-Link *</label>
                    <input class="form-control" type="text" name="url" id="url" value="{{ old('url') }}" placeholder="youtube.com/… oder vimeo.com/…" required>
                    <p class="field-hint">Domain reicht — https:// wird ergänzt.</p>
                    @error('url')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="btn">{{ $freeAvailable ? 'Kostenlos speichern' : 'Speichern & Rechnung anlegen' }}</button>
        </form>
    </div>

    <div class="xp-panel">
        <div class="xp-panel__head">
            <div>
                <h2>Ihre Videos</h2>
                <p>{{ $videos->count() }} Einträge</p>
            </div>
            <a class="btn btn--ghost btn--small" href="{{ route('expert.invoices.index') }}">Rechnungen</a>
        </div>

        @if ($videos->isEmpty())
            <div class="xp-empty">Noch keine Videos — laden Sie Ihr erstes (kostenlos) hoch.</div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Titel</th>
                            <th>Link</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($videos as $video)
                            <tr>
                                <td>{{ $video->sort_order }}</td>
                                <td>
                                    {{ $video->title ?: '—' }}
                                    @if ($video->is_complimentary)
                                        <span class="pill pill--success">1. frei</span>
                                    @endif
                                </td>
                                <td><a href="{{ $video->url }}" target="_blank" rel="noopener">Öffnen ↗</a></td>
                                <td>
                                    @if ($video->status === 'active')
                                        <span class="pill pill--success">Aktiv</span>
                                    @elseif ($video->status === 'pending_payment')
                                        <span class="pill pill--warning">Wartet auf Zahlung</span>
                                        @if ($video->invoice)
                                            <div style="font-size:.8rem;color:var(--stone);margin-top:.2rem;">
                                                <a href="{{ route('expert.invoices.show', $video->invoice) }}">{{ $video->invoice->invoice_number }}</a>
                                            </div>
                                        @endif
                                    @else
                                        <span class="pill pill--muted">{{ $video->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('expert.videos.destroy', $video) }}" onsubmit="return confirm('Video wirklich löschen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
