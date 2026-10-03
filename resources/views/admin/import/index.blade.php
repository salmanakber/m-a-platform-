@extends('layouts.admin')

@section('title', 'Import')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span>/</span>
    <span>Import</span>
@endsection

@section('content')
<div class="admin-page">
    <header class="admin-page-hero">
        <div>
            <p class="page-kicker">Daten</p>
            <h1>Experten-Import</h1>
            <p>Listen hochladen — Einträge landen als ausstehend und bleiben unsichtbar, bis Sie sie freigeben.</p>
        </div>
        <div class="admin-page-hero__actions">
            <a class="btn btn--ghost btn--small" href="{{ route('admin.import.template') }}">CSV-Vorlage</a>
            <a class="btn btn--small" href="{{ route('admin.experts.index', ['status' => 'pending']) }}">
                Pending prüfen{{ ($pendingImported ?? 0) > 0 ? ' ('.$pendingImported.')' : '' }}
            </a>
        </div>
    </header>

    @include('partials.flash')

    <div class="import-layout">
        <section class="admin-card">
            <div class="admin-card__head">
                <h2>1 · Datei wählen</h2>
                <p>XLSX, XLS oder CSV — Spalten werden automatisch erkannt.</p>
            </div>

            <form method="post" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" class="import-form">
                @csrf

                <label class="import-drop" for="file">
                    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv,text/csv">
                    <strong>Datei auswählen oder hier ablegen</strong>
                    <span>.xlsx · .xls · .csv</span>
                </label>
                @error('file')<div class="field-error">{{ $message }}</div>@enderror

                <label class="import-option {{ $defaultExists ? 'is-ready' : 'is-missing' }}">
                    <input type="checkbox" name="use_default" value="1" @checked(old('use_default') || $defaultExists)>
                    <span>
                        <b>Ablage-Datei verwenden</b>
                        <em>{{ $defaultPath }}</em>
                        <i>{{ $defaultExists ? 'Gefunden' : 'Noch nicht vorhanden' }}</i>
                    </span>
                </label>
                @error('use_default')<div class="field-error">{{ $message }}</div>@enderror

                <div class="import-form__foot">
                    <button type="submit" class="btn">Import starten</button>
                    <p>Duplikate werden aktualisiert, nicht doppelt angelegt.</p>
                </div>
            </form>
        </section>

        <aside class="admin-card admin-card--side">
            <div class="admin-card__head">
                <h2>Erkannte Spalten</h2>
                <p>Diese Header-Namen werden gemappt.</p>
            </div>
            <ul class="import-keys">
                <li><span>Firma / Name</span><code>company</code></li>
                <li><span>E-Mail</span><code>email</code></li>
                <li><span>Telefon</span><code>phone</code></li>
                <li><span>Website</span><code>website</code></li>
                <li><span>Adresse</span><code>address</code></li>
                <li><span>PLZ · Ort · Kanton</span><code>location</code></li>
                <li><span>Kauf / Verkauf</span><code>buy · sell</code></li>
            </ul>
        </aside>
    </div>

    <section class="admin-card" style="margin-top:1.15rem;">
        <div class="admin-card__head admin-card__head--row">
            <div>
                <h2>Import-Historie</h2>
                <p>Letzte Läufe mit Ergebniszahlen.</p>
            </div>
        </div>

        @if ($runs->isEmpty())
            <div class="empty-state empty-state--soft">Noch keine Imports durchgeführt.</div>
        @else
            <div class="table-scroll">
                <table class="entity-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Datei</th>
                            <th>Status</th>
                            <th>Neu</th>
                            <th>Update</th>
                            <th>Skip</th>
                            <th>Fehler</th>
                            <th>Zeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr>
                                <td>{{ $run->id }}</td>
                                <td>
                                    <p class="entity-name">{{ $run->filename ?: '—' }}</p>
                                    <p class="entity-sub">{{ $run->user->name ?? 'System' }}</p>
                                </td>
                                <td>
                                    <span class="pill pill--{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'muted') }}">
                                        {{ $run->status }}
                                    </span>
                                </td>
                                <td>{{ $run->rows_created }}</td>
                                <td>{{ $run->rows_updated }}</td>
                                <td>{{ $run->rows_skipped }}</td>
                                <td>
                                    {{ $run->rows_failed }}
                                    @if (! empty($run->errors))
                                        <details class="import-errors">
                                            <summary>Details</summary>
                                            <ul>
                                                @foreach ($run->errors as $err)
                                                    <li>
                                                        @if (! empty($err['row']))
                                                            Zeile {{ $err['row'] }}:
                                                        @endif
                                                        {{ $err['message'] ?? '' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td>{{ optional($run->finished_at ?? $run->created_at)->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
