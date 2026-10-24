@extends('layouts.admin')

@section('title', 'Crawl')

@section('content')
    <header class="page-head"><h1 class="page-title" style="font-size:2rem;">Website-Crawl</h1></header>

    @include('partials.flash')

    <div class="status-help" style="margin-bottom:1rem;">
        <strong>Wann funktioniert der Crawl?</strong>
        <span>
            Experte muss <b>freigegeben</b> sein, «Website-Crawl aktiv» anhaken und eine <b>Crawl-URL</b> (Blog/News-Seite) hinterlegen.
            Der Button führt den Crawl <b>sofort</b> aus. Gefundene Beiträge landen als <b>Entwurf</b> und müssen veröffentlicht werden.
        </span>
    </div>

    <div class="action-bar" style="border-top:none;padding-top:0;">
        <form action="{{ route('admin.crawl.run') }}" method="post">
            @csrf
            <button type="submit" class="btn">Täglichen Crawl jetzt starten</button>
        </form>
        <p style="color:var(--stone);margin:0;">Führt den Crawl sofort für alle freigegebenen Experten mit aktivem Crawl + URL aus.</p>
    </div>

    @if ($jobs->isEmpty())
        <div class="empty-state">Noch keine Crawl-Jobs.</div>
    @else
        <table class="data-table" style="margin-top:1.5rem;">
            <thead><tr><th>Experte</th><th>Status</th><th>Gestartet</th><th>Beendet</th></tr></thead>
            <tbody>
                @foreach ($jobs as $job)
                    <tr>
                        <td>{{ $job->expert->company_name ?? '—' }}</td>
                        <td>{{ $job->status }}</td>
                        <td>{{ $job->started_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td>{{ $job->finished_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $jobs->links() }}</div>
    @endif
@endsection
