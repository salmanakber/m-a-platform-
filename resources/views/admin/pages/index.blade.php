@extends('layouts.admin')

@section('title', 'Seiten')

@section('content')
    <header class="page-head"><h1 class="page-title" style="font-size:2rem;">Seiten</h1></header>
    <p style="color:var(--stone);">Rechtstexte und statische Inhalte.</p>

    @if ($pages->isEmpty())
        <div class="empty-state">Keine Seiten vorhanden.</div>
    @else
        <table class="data-table">
            <thead><tr><th>Slug</th><th>Titel</th><th>Veröffentlicht</th><th></th></tr></thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td><code>{{ $page->slug }}</code></td>
                        <td>{{ $page->title }}</td>
                        <td>{{ $page->is_published ? 'Ja' : 'Nein' }}</td>
                        <td><a href="{{ route('admin.pages.edit', $page) }}">Bearbeiten</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
