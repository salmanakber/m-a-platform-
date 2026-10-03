<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Expertenbereich') — {{ config('app.name') }}</title>
    @include('partials.theme-styles')
</head>
<body class="admin-shell admin-shell--expert">
    <aside class="admin-sidebar admin-sidebar--expert" aria-label="Expertenbereich">
        <a class="admin-sidebar__brand" href="{{ route('expert.dashboard') }}">
            <img src="{{ asset('logo-nachfolge-experten/logo-nachfolge-experten.svg') }}" alt="" width="28" height="26">
            <span>Mein Bereich</span>
        </a>
        <nav class="admin-sidebar__nav">
            <p class="admin-sidebar__label">Übersicht</p>
            <a href="{{ route('expert.dashboard') }}" class="{{ request()->routeIs('expert.dashboard') ? 'is-active' : '' }}">Dashboard</a>
            <a href="{{ route('expert.leads.index') }}" class="{{ request()->routeIs('expert.leads.*') ? 'is-active' : '' }}">Anfragen</a>

            <p class="admin-sidebar__label">Profil</p>
            <a href="{{ route('expert.profile.edit') }}" class="{{ request()->routeIs('expert.profile.*') ? 'is-active' : '' }}">Unternehmen</a>
            <a href="{{ route('expert.offices.index') }}" class="{{ request()->routeIs('expert.offices.*') ? 'is-active' : '' }}">Standorte</a>
            <a href="{{ route('expert.crawl.index') }}" class="{{ request()->routeIs('expert.crawl.*') ? 'is-active' : '' }}">Website-Import</a>

            <p class="admin-sidebar__label">Wachstum</p>
            <a href="{{ route('expert.promotions.index') }}" class="{{ request()->routeIs('expert.promotions.*') ? 'is-active' : '' }}">Promotionen</a>
            <a href="{{ route('expert.articles.index') }}" class="{{ request()->routeIs('expert.articles.*') ? 'is-active' : '' }}">Artikel</a>
        </nav>
        <div class="admin-sidebar__foot">
            <a href="{{ route('home') }}">Website</a>
            @auth
                @if (auth()->user()->expert?->is_public && auth()->user()->expert?->status === 'approved')
                    <a href="{{ route('experts.show', auth()->user()->expert->slug) }}" target="_blank" rel="noopener">Mein Profil</a>
                @endif
            @endauth
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <nav class="admin-breadcrumbs" aria-label="Brotkrumen">
                @hasSection('breadcrumbs')
                    @yield('breadcrumbs')
                @else
                    <span>Expertenbereich</span>
                @endif
            </nav>
            <div class="admin-topbar__user">
                <span>{{ auth()->user()->name ?? 'Experte' }}</span>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn--ghost btn--small">Abmelden</button>
                </form>
            </div>
        </header>

        <main class="admin-content reveal">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</body>
</html>
