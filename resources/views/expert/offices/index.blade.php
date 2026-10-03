@extends('layouts.expert')

@section('title', 'Standorte')

@section('content')
    <h1 style="font-family:var(--font-display);margin-top:0;">Standorte</h1>

    @if ($offices->isNotEmpty())
        <table class="data-table">
            <thead><tr><th>Bezeichnung</th><th>Adresse</th><th>Kanton</th><th>Primär</th><th></th></tr></thead>
            <tbody>
                @foreach ($offices as $office)
                    <tr>
                        <td>{{ $office->label ?? '—' }}</td>
                        <td>{{ $office->address_line }}<br>{{ $office->postal_code }} {{ $office->city }}</td>
                        <td>{{ $office->canton->name_de ?? '—' }}</td>
                        <td>{{ $office->is_primary ? 'Ja' : '' }}</td>
                        <td>
                            <details>
                                <summary>Bearbeiten</summary>
                                <form method="post" action="{{ route('expert.offices.update', $office) }}" style="margin-top:0.75rem;">
                                    @csrf
                                    @method('PUT')
                                    @include('expert.offices._fields', ['office' => $office])
                                    <button type="submit" class="btn btn--small">Speichern</button>
                                </form>
                                <form method="post" action="{{ route('expert.offices.destroy', $office) }}" onsubmit="return confirm('Standort löschen?');" style="margin-top:0.5rem;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--ghost btn--small">Löschen</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">Noch keine Standorte — fügen Sie unten einen hinzu.</div>
    @endif

    <h2 style="font-size:1.05rem;margin-top:2rem;">Neuer Standort</h2>
    <form method="post" action="{{ route('expert.offices.store') }}" class="card">
        @csrf
        @include('expert.offices._fields', ['office' => null])
        <button type="submit" class="btn">Hinzufügen</button>
    </form>
@endsection
