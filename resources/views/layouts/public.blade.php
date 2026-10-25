<!DOCTYPE html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Das unabhängige Schweizer Verzeichnis für M&A- und Unternehmensnachfolge-Experten.')">
    @include('partials.theme-styles')
    @stack('head')
</head>
<body class="@yield('body_class')">
    <header class="site-header" id="siteHeader">
        <div class="container site-header__inner">
            <a class="site-logo" href="{{ route('home') }}">
                <img src="{{ asset('logo-nachfolge-experten/logo-nachfolge-experten.svg') }}" alt="nachfolge-experten.ch" width="44" height="40">
                <span class="site-logo__text">nachfolge-experten<span>.ch</span></span>
            </a>
            <button class="nav-toggle" type="button" aria-label="Menü" id="navToggle" aria-expanded="false">☰</button>
            <nav class="site-nav" id="siteNav" aria-label="Hauptnavigation">
                <a href="{{ route('home') }}#top5" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Top Experten</a>
                <a href="{{ route('cantons.index') }}" class="{{ request()->routeIs('cantons.*') ? 'is-active' : '' }}">Kantone</a>
                <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'is-active' : '' }}">Fachbeiträge</a>
                <a href="{{ route('events.index') }}" class="{{ request()->routeIs('events.*') ? 'is-active' : '' }}">Events</a>
                <a href="{{ route('map.index') }}" class="{{ request()->routeIs('map.*') ? 'is-active' : '' }}">Karte</a>
                <a href="{{ route('directory.index') }}" class="{{ request()->routeIs('directory.*', 'experts.*') ? 'is-active' : '' }}">Verzeichnis</a>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @elseif (auth()->user()->isExpert())
                        <a href="{{ route('expert.dashboard') }}">Mein Bereich</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post" style="display:inline;margin:0;">
                        @csrf
                        <button type="submit" class="btn btn--ghost btn--small">Abmelden</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a class="nav-cta" href="{{ route('registration.create') }}">Als Experte anmelden ↗</a>
                @endauth
            </nav>
        </div>
    </header>

    @hasSection('hero')
        @yield('hero')
    @endif

    <main class="page-main @unless(View::hasSection('hero') || View::hasSection('flush')) page-main--padded @endunless">
        @unless (View::hasSection('hero') || View::hasSection('flush'))
            <div class="container">
                @include('partials.flash')
            </div>
        @endunless
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container site-footer__grid">
            <div>
                <a class="site-logo" href="{{ route('home') }}">
                    <img src="{{ asset('logo-nachfolge-experten/nachfolge-experten-weiss.svg') }}" alt="" width="36" height="32">
                    <span class="site-logo__text">nachfolge-experten<span>.ch</span></span>
                </a>
                <p>Unabhängiges Schweizer Verzeichnis für M&A- und Unternehmensnachfolge. Eine Initiative der KMU Beratungen GmbH, Sarnen.</p>
                <p style="margin-top:0.75rem;"><a href="{{ route('registration.create') }}" style="font-weight:700;">Beraten und begleiten Sie Eigentümer bei der Weitergabe der Unternehmung? Listen Sie Ihr Profil.</a></p>
            </div>
            <div>
                <h4>Verzeichnis</h4>
                <a href="{{ route('home') }}#top5">Top Experten</a>
                <a href="{{ route('cantons.index') }}">Kantons-Auswahl</a>
                <a href="{{ route('blog.index') }}">Fachbeiträge</a>
                <a href="{{ route('map.index') }}">Schweiz-Karte</a>
            </div>
            <div>
                <h4>Kontakt / Rechtliches</h4>
                <a href="{{ route('legal.impressum') }}">Impressum</a>
                <a href="{{ route('legal.datenschutz') }}">Datenschutz</a>
                <a href="{{ route('legal.agb') }}">AGB</a>
                <span style="display:block;margin-top:0.5rem;color:inherit;opacity:0.85;font-size:0.9rem;">Brünigstrasse 144, 6060 Sarnen</span>
            </div>
        </div>
        <div class="container site-footer__bottom">
            © {{ date('Y') }} nachfolge-experten.ch · Brünigstrasse 144, 6060 Sarnen
        </div>
    </footer>

    <script>
        (function () {
            var header = document.getElementById('siteHeader');
            var toggle = document.getElementById('navToggle');
            var nav = document.getElementById('siteNav');

            function onScroll() {
                if (!header) return;
                header.classList.toggle('is-scrolled', window.scrollY > 12);
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            if (toggle && nav) {
                toggle.addEventListener('click', function () {
                    var open = nav.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            }

            if ('IntersectionObserver' in window) {
                var revealEls = document.querySelectorAll('.reveal-on-scroll');
                if (revealEls.length) {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                io.unobserve(entry.target);
                            }
                        });
                    }, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
                    revealEls.forEach(function (el) { io.observe(el); });
                }
            } else {
                document.querySelectorAll('.reveal-on-scroll').forEach(function (el) {
                    el.classList.add('is-visible');
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
