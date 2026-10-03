@extends('layouts.admin')

@section('title', 'KI-Vorschläge')

@section('content')
    <header class="page-head"><h1 class="page-title" style="font-size:2rem;">KI-Vorschläge</h1></header>

    @if ($suggestions->isEmpty())
        <div class="empty-state">Keine Vorschläge zur Prüfung.</div>
    @else
        <table class="data-table">
            <thead><tr><th>Experte</th><th>Feld</th><th>Status</th><th>Vorschlag</th><th>Aktionen</th></tr></thead>
            <tbody>
                @foreach ($suggestions as $suggestion)
                    <tr>
                        <td>{{ $suggestion->expert->company_name ?? '—' }}</td>
                        <td><code>{{ $suggestion->field_name }}</code></td>
                        <td>{{ $suggestion->status }}</td>
                        <td style="max-width:280px;">{{ \Illuminate\Support\Str::limit($suggestion->suggested_value, 120) }}</td>
                        <td>
                            @if ($suggestion->status === 'pending')
                                <form action="{{ route('admin.ai-suggestions.apply', $suggestion) }}" method="post" style="display:inline;">@csrf<button type="submit" class="btn btn--small">Anwenden</button></form>
                                <form action="{{ route('admin.ai-suggestions.reject', $suggestion) }}" method="post" style="display:inline;">@csrf<button type="submit" class="btn btn--ghost btn--small">Ablehnen</button></form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $suggestions->links() }}</div>
    @endif
@endsection
