@extends('layouts.admin')

@section('title', 'E-Mail-Vorlagen')

@section('content')
    <header class="page-head"><h1 class="page-title" style="font-size:2rem;">E-Mail-Vorlagen</h1></header>

    @if ($templates->isEmpty())
        <div class="empty-state">Keine Vorlagen — bitte Seeder ausführen.</div>
    @else
        <table class="data-table">
            <thead><tr><th>Schlüssel</th><th>Betreff</th><th></th></tr></thead>
            <tbody>
                @foreach ($templates as $template)
                    <tr>
                        <td><code>{{ $template->key }}</code></td>
                        <td>{{ $template->subject }}</td>
                        <td><a href="{{ route('admin.email-templates.edit', $template) }}">Bearbeiten</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
