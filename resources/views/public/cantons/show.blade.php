@extends('layouts.public')

@section('title', 'Experten in '.$canton->name_de.' — nachfolge-experten.ch')
@section('meta_description', 'M&A- und Nachfolge-Experten im Kanton '.$canton->name_de.' finden.')
@section('flush', true)

@section('content')
<div class="nh-canton-show">
    <header class="nh-page-hero nh-page-hero--cream">
        <div class="nh-page-hero__inner nh-page-hero__inner--split">
            <div>
                <p class="nh-kicker"><a href="{{ route('cantons.index') }}" style="color:inherit;text-decoration:none;">← Alle Kantone</a> · {{ $canton->code }}</p>
                <h1>{{ $canton->name_de }}</h1>
                <p>
                    {{ $experts->count() }} freigeschaltete
                    Experte{{ $experts->count() === 1 ? '' : 'n' }} mit Standort in diesem Kanton.
                </p>
                <div class="nh-page-hero__actions">
                    <a class="nh-btn nh-btn--navy" href="{{ route('directory.index', ['kanton' => $canton->code]) }}">Im Verzeichnis</a>
                    <a class="nh-btn nh-btn--ghost nh-btn--on-light" href="{{ route('map.index', ['kanton' => $canton->code]) }}">Auf der Karte ↗</a>
                    <a class="nh-btn nh-btn--ghost nh-btn--on-light" href="{{ route('home', ['kanton' => $canton->code]) }}#kantone-top">Top 3 ansehen</a>
                </div>
            </div>
            <aside class="nh-canton-show__badge" aria-label="Kanton">
                <b>{{ $canton->code }}</b>
                <span>{{ $experts->count() }} Profile</span>
            </aside>
        </div>
    </header>

    <div class="nh-canton-show__body">
        @if ($experts->isEmpty())
            <div class="nh-empty nh-empty--panel">
                <strong>Noch keine Experten in {{ $canton->name_de }}.</strong>
                <p>In diesem Kanton sind derzeit keine geprüften Profile gelistet — der Platz ist noch frei.</p>
                <a class="nh-btn nh-btn--navy" href="{{ route('directory.index') }}">Im Verzeichnis ansehen ↗</a>
            </div>
        @else
            <div class="nh-profile-list">
                @foreach ($experts as $expert)
                    @php
                        $office = $expert->primaryOffice();
                        $group = $expert->professional_group ?: \App\Support\ProfessionalGroup::MA_SPECIALIST;
                    @endphp
                    <article class="nh-profile-row">
                        @if ($expert->logo_path)
                            <span class="nh-avatar nh-avatar--logo"><img src="{{ asset('storage/'.$expert->logo_path) }}" alt=""></span>
                        @else
                            <span class="nh-avatar" style="--g:{{ \App\Support\ProfessionalGroup::color($group) }}">{{ mb_strtoupper(mb_substr($expert->company_name, 0, 2)) }}</span>
                        @endif
                        <div class="nh-profile-row__copy">
                            <strong><a href="{{ route('experts.show', $expert->slug) }}">{{ $expert->company_name }}</a></strong>
                            <span>
                                @if ($office)
                                    {{ $office->city }} · {{ $canton->code }}
                                @endif
                            </span>
                            <div class="nh-tags">
                                <span>{{ \App\Support\ProfessionalGroup::label($group) }}</span>
                                @if ($expert->offers_buy)<span>Kauf</span>@endif
                                @if ($expert->offers_sell)<span>Verkauf</span>@endif
                            </div>
                        </div>
                        <a class="nh-btn nh-btn--navy nh-btn--small" href="{{ route('experts.show', $expert->slug) }}">Profil ↗</a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
