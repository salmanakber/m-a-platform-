<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    @include('partials.theme-styles')
</head>
<body class="admin-shell">
    <aside class="admin-sidebar" aria-label="Administration">
        <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('logo-nachfolge-experten/logo-nachfolge-experten.svg') }}" alt="" width="28" height="26">
            <span>Admin</span>
        </a>
        <nav class="admin-sidebar__nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.experts.index') }}" class="{{ request()->routeIs('admin.experts.*') ? 'is-active' : '' }}">Experten</a>
            <a href="{{ route('admin.leads.index') }}" class="{{ request()->routeIs('admin.leads.*') ? 'is-active' : '' }}">Anfragen</a>
            <a href="{{ route('admin.articles.index') }}" class="{{ request()->routeIs('admin.articles.*') ? 'is-active' : '' }}">Artikel</a>
            <a href="{{ route('admin.promotions.index') }}" class="{{ request()->routeIs('admin.promotions.*') ? 'is-active' : '' }}">Promotionen</a>
            <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices.*') ? 'is-active' : '' }}">Rechnungen</a>
            <a href="{{ route('admin.crawl.index') }}" class="{{ request()->routeIs('admin.crawl.*') ? 'is-active' : '' }}">Crawl</a>
            <a href="{{ route('admin.ai-suggestions.index') }}" class="{{ request()->routeIs('admin.ai-suggestions.*') ? 'is-active' : '' }}">KI-Vorschläge</a>
            <a href="{{ route('admin.import.index') }}" class="{{ request()->routeIs('admin.import.*') ? 'is-active' : '' }}">Import</a>
            <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'is-active' : '' }}">Seiten</a>
            <a href="{{ route('admin.email-templates.index') }}" class="{{ request()->routeIs('admin.email-templates.*') ? 'is-active' : '' }}">E-Mail-Vorlagen</a>
            <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">Einstellungen</a>
        </nav>
        <div class="admin-sidebar__foot">
            <a href="{{ route('home') }}">Öffentliche Website</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <nav class="admin-breadcrumbs" aria-label="Brotkrumen">
                @hasSection('breadcrumbs')
                    @yield('breadcrumbs')
                @else
                    <span>Administration</span>
                @endif
            </nav>
            <div class="admin-topbar__user">
                <span>{{ auth()->user()->name ?? 'Admin' }}</span>
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
