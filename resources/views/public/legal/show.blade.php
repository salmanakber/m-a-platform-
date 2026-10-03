@extends('layouts.public')

@section('title', $title)

@section('content')
<div class="container container--narrow reveal-on-scroll">
    <article class="form-panel">
        <p class="page-kicker">Rechtliches</p>
        <h1 class="page-title" style="font-size:2.35rem;">{{ $title }}</h1>
        @if ($body)
            <div class="legal-prose">{!! nl2br(e($body)) !!}</div>
        @else
            <p class="legal-prose" style="color:var(--stone);">Der verbindliche Text folgt aus den vom Kunden bereitgestellten Rechtsdokumenten und wird hier noch nicht erfunden.</p>
        @endif
    </article>
</div>
@endsection
