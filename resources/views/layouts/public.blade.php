<!DOCTYPE HTML>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <title>@yield('title', config('app.name', 'CodaFácil'))</title>
        <meta name="description" content="@yield('description', 'Base Laravel com site público, front autenticado, backend, API mobile e agente local.')">
        @include('partials.favicons')
        @include('partials.google-tag')
        @vite(['resources/css/public-solid-state.css', 'resources/js/public-solid-state.js'])
    </head>
    <body class="is-preload solid-state-app">
        <div id="page-wrapper">
            <header id="header" class="@yield('header_class', 'alt')">
                <h1>
                    <a href="{{ route('home') }}" class="app-brand">
                        <span class="app-brand-badge">
                            <img src="{{ asset('logo.webp') }}" alt="{{ config('app.name', 'CodaFácil') }}">
                        </span>
                        <span>{{ config('app.name', 'CodaFácil') }}</span>
                    </a>
                </h1>
                <nav>
                    <a href="#menu" data-menu-toggle>Menu</a>
                </nav>
            </header>

            <nav id="menu">
                <div class="inner">
                    <h2>Navegação</h2>
                    <ul class="links">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('public.about') }}">Sobre</a></li>
                        <li><a href="{{ route('public.services') }}">Serviços</a></li>
                        <li><a href="{{ route('public.contact') }}">Contato</a></li>
                        @auth
                            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            @if (auth()->user()->is_admin)
                                <li><a href="{{ route('backend.dashboard') }}">Backend</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('login') }}">Entrar</a></li>
                        @endauth
                    </ul>
                    <a href="#" class="close" data-menu-close>Close</a>
                </div>
            </nav>

            @yield('content')

            <section id="footer">
                <div class="inner">
                    <h2 class="major">Operação pronta para novos produtos</h2>
                    <p>
                        Site público, front autenticado, backend com ACL, API mobile e agente local convivem na mesma base.
                        O objetivo é reduzir retrabalho sem perder clareza operacional.
                    </p>
                    <div class="app-footer-cta">
                        <ul class="contact">
                            <li class="icon solid fa-home">
                                {{ config('app.name', 'CodaFácil') }}<br />
                                Scaffold Laravel para novos sistemas<br />
                                Operação baseada em módulos reutilizáveis
                            </li>
                            <li class="icon solid fa-envelope"><a href="{{ route('public.contact') }}">Entrar em contato</a></li>
                            <li class="icon solid fa-layer-group"><a href="{{ route('public.services') }}">Ver módulos</a></li>
                        </ul>
                        <ul class="actions">
                            <li><a href="{{ route('public.services') }}" class="button">Explorar estrutura</a></li>
                        </ul>
                    </div>
                    <ul class="copyright">
                        <li>&copy; {{ date('Y') }} {{ config('app.name', 'CodaFácil') }}. All rights reserved.</li>
                        <li>Base visual adaptada a partir de Solid State, por HTML5 UP.</li>
                    </ul>
                </div>
            </section>
        </div>

        @include('partials.whatsapp-float')
    </body>
</html>
