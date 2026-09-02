<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? config('app.name', 'CodaFácil') }}</title>

        @include('partials.favicons')
        @include('partials.google-tag')
        @vite(['resources/css/panel.css', 'resources/js/app.js'])
        @include('partials.theme-init')
        @fluxAppearance
    </head>
    <body class="panel-interface antialiased">
        <div class="panel-auth-shell">
            <aside class="panel-auth-aside">
                <div class="panel-auth-aside__inner">
                    <div class="space-y-10">
                        <a href="{{ route('home') }}" class="panel-brand">
                            <span class="panel-brand__mark">
                                <img src="{{ asset('logo.webp') }}" alt="{{ config('app.name', 'CodaFácil') }}" />
                            </span>

                            <span class="panel-brand__copy">
                                <span class="panel-brand__eyebrow text-white/60">Scaffold Operacional</span>
                                <span class="panel-brand__name text-white">{{ config('app.name', 'CodaFácil') }}</span>
                            </span>
                        </a>

                        <div class="max-w-xl space-y-5">
                            <span class="panel-auth-eyebrow">TailAdmin base</span>
                            <div>
                                <h1 class="text-5xl font-semibold leading-tight tracking-[-0.05em] text-white">
                                    Login, dashboards e backend prontos para virar novos produtos.
                                </h1>
                                <p class="mt-4 max-w-lg text-lg leading-8 text-white/72">
                                    A camada autenticada agora nasce como um shell próprio do scaffold, separada do site público e pronta para receber novos módulos sem remendos.
                                </p>
                            </div>
                        </div>

                        <div class="panel-auth-showcase-grid">
                            <div class="panel-auth-note">
                                <h2>Mesmo fluxo</h2>
                                <p>Cliente, backend, ACL, agente e configurações compartilham a mesma fundação visual.</p>
                            </div>

                            <div class="panel-auth-note">
                                <h2>Pronto para escalar</h2>
                                <p>O shell fica desacoplado do site público para ser reutilizado em outros sistemas sem retrabalho.</p>
                            </div>
                        </div>
                    </div>

                    <div class="text-sm text-white/60">
                        &copy; {{ date('Y') }} {{ config('app.name', 'CodaFácil') }}
                    </div>
                </div>
            </aside>

            <main class="panel-auth-main">
                <div class="panel-auth-card">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
